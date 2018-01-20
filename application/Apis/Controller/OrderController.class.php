<?php
namespace Apis\Controller;
use Apis\Controller\ApiController;
use Common\Lib\Constant;
use Think\Log;

class OrderController extends ApiController{ 

	public function orderSubmit(){
		$configId = intval(I('post.config_id'));
		$openid = trim(I('post.openid'));
		$bucket = I('post.bucket',0,'intval');
        $ticketNum = I('post.ticketNum',0,'intval');
        $strategy = I('post.strategy',0,'intval');
        $send_code = intval(rand(1000,9999));
		try{
			// 检测address_id
			$addressId = intval(I('post.address_id'));
			if(1 > $addressId){
				throw new \Exception('Order submit param address_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}else{
				$address = M('wechat_user_address')
					->where(array(
						'config_id' => $configId,
						'openid' => $openid,
						'id' => $addressId,
						'status' => array('in', array(
							Constant::USER_ADDRESS_STATUS_ONUSE,
							Constant::USER_ADDRESS_STATUS_DEFAULT,
						)),
					))
					->find();
				if(0 == count($address)){
					throw new \Exception('Order submit param address_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
				}
			}

            //检测 strategy 水票
            if($strategy!=0){
                $strategy_tick=M('goods_strategy')->where(array('config_id' => $configId, 'id' => $strategy, 'type' => Constant::GOODS_STRATEGY_TYPE_TICKET, 'status' => Constant::GOODS_STRATEGY_TYPE_ONSALE))->find();
                if(empty($strategy_tick)){
                    throw new \Exception('Order submit param strategy is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
                }
            }

			// 检测pay_type
			$payType = intval(I('post.pay_type'));
			// TODO: 目前只支持线下支付
			if(!in_array($payType, array(
				Constant::B2C_ORDER_PAY_TYPE_CASH,
				Constant::B2C_ORDER_PAY_TYPE_WECHAT,
				Constant::B2C_ORDER_PAY_TYPE_TICKET,
				Constant::B2C_ORDER_PAY_TYPE_TICKET_CASH,
				Constant::B2C_ORDER_PAY_TYPE_TICKET_WECHAT,
			))){
				throw new \Exception('Order submit param pay_type is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
			// 检测订单商品
			$goods = json_decode($_POST['goods'], true);
			if(!is_array($goods) || 0 == count($goods)){
				throw new \Exception('Order submit param goods is illegal1', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}else{
				foreach ($goods as $value) {
					$goodsId = intval($value['goods_id']);
					$goodsNum = intval($value['goods_num']);
					$goodsTicket = intval($value['ticket']);
					$goodsStrategyId = intval($value['ticket_id']);
					if(1 > $goodsId || 1 > $goodsNum){
						throw new \Exception('Order submit param goods is illegal2', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
					}
					if($goodsTicket > $goodsNum){
						throw new \Exception('Order submit param goods is illegal3', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
					}
					//检测水票是否满足条件
					if($goodsStrategyId > 0){
						$ticket = M('goods_strategy') -> where(array('config_id' => $configId, 'id' => $goodsStrategyId, 'type' => Constant::GOODS_STRATEGY_TYPE_TICKET, 'status' => Constant::GOODS_STRATEGY_TYPE_ONSALE)) -> find();
						if(empty($ticket)){
							throw new \Exception('Order submit param ticket is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
						}
						if(time() > $ticket['end_time'] || time() < $ticket['start_time']){
							throw new \Exception('Order submit param ticket activity is out time', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
						}
						if(!empty($ticket['condition'])&& $goodsTicket > 0){
							$ticketCondition = json_decode($ticket['condition'], true);
							$use_num = explode(',', $ticketCondition['use_num']);
							if(in_array('lt', $use_num)){
								$ticket['use_num'] = ($use_num[1]-1) >= 0 ? '0,'.($use_num[1]-1) : '0,0';
							}else if(in_array('elt', $use_num)){
								$ticket['use_num'] = '0,'.$use_num[1];
							}else if(in_array('gt', $use_num)){
								$ticket['use_num'] = ($use_num[1]+1).',∞';
							}else if(in_array('egt', $use_num)){
								$ticket['use_num'] = $use_num[1].',∞';
							}else{
								$ticket['use_num'] = '0,∞';
							}
							$use_num = explode(',', $ticket['use_num']);
							$first = $use_num[0];
							$second = $use_num[1] == '∞' ? 9999999 : $use_num[1];
							if($first > 0 || $second > 0){
								if($goodsTicket > $second || $goodsTicket < $first){
									throw new \Exception('Order submit param ticket num is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
								}
							}
						}
					}
				}
			}
			
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$orderSn = $this->createB2COrderSn($configId);
		$comment = trim(I('post.comment'));

		if($payType == Constant::B2C_ORDER_PAY_TYPE_CASH){
			// 现金支付
			$payStatus = Constant::B2C_ORDER_PAY_STATUS_NOPAY;
			$orderStatus = Constant::B2C_ORDER_STATUS_CREATED;
		}else if($payType == Constant::B2C_ORDER_PAY_TYPE_WECHAT){
			// 微信支付
			$payStatus =  Constant::B2C_ORDER_PAY_STATUS_NOPAY;
			$orderStatus = Constant::B2C_ORDER_STATUS_WAITING;
		}else if($payType == Constant::B2C_ORDER_PAY_TYPE_TICKET){
            //水票支付
            $payStatus = Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
            $orderStatus = Constant::B2C_ORDER_STATUS_CREATED;
        }

		$transModel = M();
		$goodsModel = M('goods');
		$orderModel = M('b2c_order');
		$orderDetailModel = M('b2c_order_detail');

		// 微信用户订单提交成功模板消息数据
		$wechatUserMsgData = array();
		$orderDetailData = array();
		$orderPrice = 0.00;
		$orderOriginalPrice = 0.00;
		try{
			foreach ($goods as $value) {
				$goodsId = intval($value['goods_id']);
				$goodsNum = intval($value['goods_num']);
				$goodsTicket = intval($value['ticket']);
				$goodsTemp = $goodsModel->where(array(
					'id' => $goodsId,
					'config_id' => $configId,
					'status' => Constant::GOODS_STATUS_ONSALE
				))->find();
				if(0 == count($goodsTemp)){
					throw new \Exception('Order submit param goods is illegal4', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
				}else{
					// 单独定价
					// 查询用户是否存在单独定价
					$user_goods = M('user_goods') -> where(array(
						'config_id' => $configId,
						'openid' => $openid, 
						'goods_id' => $goodsId,
						))
						-> find();
					if(count($user_goods) > 0){
						$goodsTemp['price'] = $user_goods['goods_price'];
					}
					$subOriginalTotal = sprintf('%.2f', floatval($goodsTemp['price']) * $goodsNum);
					$subTotal = sprintf('%.2f', floatval($goodsTemp['price']) * ($goodsNum - $goodsTicket));
					array_push($orderDetailData, array(
						'goods_id' => $goodsId,
						'goods_name' => $goodsTemp['name'],
						'goods_price' => $goodsTemp['price'],
						'goods_num' => $goodsNum,
						'sub_total' => $subOriginalTotal,
						'goods_img' => $goodsTemp['img'],
					));
					$orderPrice += $subTotal;
					$orderOriginalPrice += $subOriginalTotal;
					array_push($wechatUserMsgData, array(
						'name' => $goodsTemp['name'],
						'standard' => $goodsTemp['standard'],
						'unit' => $goodsTemp['unit'],
						'num' => $goodsNum,
					));
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$orderPrice = $orderPrice + $bucket * Constant::B2C_ORDER_BUCKET_PRICE;
		$orderOriginalPrice = $orderOriginalPrice + $bucket * Constant::B2C_ORDER_BUCKET_PRICE;
		$transModel->startTrans();
        //判断是什么类型的支付
        if($orderPrice!=0 && $ticketNum!=0 && $payType==Constant::B2C_ORDER_PAY_TYPE_CASH){
            $payType=3;
        }elseif($orderPrice!=0 && $ticketNum!=0 && $payType==Constant::B2C_ORDER_PAY_TYPE_WECHAT){
            $payType=4;
        }else if($orderPrice==0 && $ticketNum!=0){
            $payType=2;
        }
		$orderData = array(
			'config_id' => $configId,
			'openid' => $openid,
			'address_id' => $addressId,
			'order_sn' => $orderSn,
			'order_price' => $orderPrice,
			'order_original_price' => $orderOriginalPrice,
			'comment' => $comment,
			'pay_type' => $payType,
			'pay_status' => $payStatus,
			'pay_id' => '',
			'order_status' => $orderStatus,
			'create_time' => time(),
			'bucket' => $bucket,
            'ticket_id'=> $strategy,
            'ticket_num' => $ticketNum,
            'send_code' => $send_code,
		);
		try{
			$orderId = intval($orderModel->add($orderData));
			if(1 > $orderId){
				$transModel->rollback();
				throw new \Exception('Order Submit Error, failed to insert order data', Constant::API_ORDER_SUBMIT_ERROR);
			}else{
				//处理水票信息
				foreach ($goods as $value) {
					$goodsId = intval($value['goods_id']);
					$goodsTicket = intval($value['ticket']);
					if($goodsTicket > 0){
						$userTicket = M('wechat_user_ticket') -> where(array(
							'config_id' => $configId,
							'openid' => $openid,
							'goods_id' => $goodsId,
							'status' => Constant::WECHAT_USER_TICKET_NORMAL,
                            'strategy_id'=>$strategy,
							))
							->field('id')
							->order('id asc, createtime desc')
							->limit($goodsTicket)
							->select();
						if(count($userTicket) <= 0){
							throw new \Exception('Order submit param ticket is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
						}
						if(count($userTicket) != $goodsTicket){
							throw new \Exception('Order submit param ticket is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
						}
						$userTicketId = '';
						foreach ($userTicket as $key => $value) {
							$userTicketId .= $value['id'].',';
						}
						$userTicketId = trim($userTicketId, ',');
						//var_dump($userTicket);
						$updateUserTicket = M('wechat_user_ticket') -> where(array(
							'id' => array('in', $userTicketId),
							)) 
							-> save(array('status' => Constant::WECHAT_USER_TICKET_ORDER_LOCKED, 'user_order_id' => $orderId));
						if($updateUserTicket == false){
							throw new \Exception('Update wechat_user_ticket error', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
						}
					}
				}
				foreach ($orderDetailData as &$goods) {
					$goods['order_id'] = $orderId;
				}
				if(1 == count($orderDetailData)){
					$orderDetailId = intval($orderDetailModel->add($orderDetailData[0]));
				}else{
					$orderDetailId = intval($orderDetailModel->addAll($orderDetailData));
				}
				if(1 > $orderDetailId){
					$transModel->rollback();
					throw new \Exception('Order Submit Error, failed to insert order data', Constant::API_ORDER_SUBMIT_ERROR);
				}else{
					$transModel->commit();
					$wechat = $this->getWechatObject($configId);
					//if($payType == Constant::B2C_ORDER_PAY_TYPE_CASH){
						$this->_sendWechatUserOrderSuccessTemplateMsg($wechat, $openid, $wechatUserMsgData, $orderData, $address, $orderId);
						$this->_sendStationDeliverNewOrderTemplateMsg($wechat, $wechatUserMsgData, $orderData, $address, $orderId);
					//}
					$this->apiReturn(Constant::API_SUCCESS, array('order_id'=>$orderId), '订单提交成功');
				}
			}
		}catch(\Exception $e){
			$transModel -> rollback();
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}


	public function getOrderList(){
		$configId = intval(I('post.config_id'));
		$openid = trim(I('post.openid'));
		$orderPayStatus = I('post.paystatus', Constant::B2C_ORDER_PAY_STATUS_ALL, 'intval');
		$orderStatus = I('post.orderstatus', Constant::B2C_ORDER_STATUS_ALL, 'intval');
		$orderPayType = I('post.paytype', Constant::B2C_ORDER_PAY_TYPE_ALL, 'intval');
		//验证订单支付状态
		if(!in_array($orderPayStatus, array(
			Constant::B2C_ORDER_PAY_STATUS_NOPAY,
			Constant::B2C_ORDER_PAY_STATUS_SUCCESS,
			Constant::B2C_ORDER_PAY_STATUS_FAILED,
			Constant::B2C_ORDER_PAY_STATUS_BACKING,
			Constant::B2C_ORDER_PAY_STATUS_BACKED,
			Constant::B2C_ORDER_PAY_STATUS_ALL,
		))){
			$this->apiReturn(Constant::API_GET_ORDER_LIST_PARAM_ERROR, $_POST, '订单列表参数错误');
		}
		//验证订单状态
		if(!in_array($orderStatus, array(
			Constant::B2C_ORDER_STATUS_CREATED,
			Constant::B2C_ORDER_STATUS_STATION_ACCEPT,
			Constant::B2C_ORDER_STATUS_DELIVERING,
			Constant::B2C_ORDER_STATUS_FINISHED,
			Constant::B2C_ORDER_STATUS_CANCELED,
			Constant::B2C_ORDER_STATUS_ALL,
		))){
			$this->apiReturn(Constant::API_GET_ORDER_LIST_PARAM_ERROR, $_POST, '订单列表参数错误');
		}
		//验证订单支付方式
		if(!in_array($orderPayType, array(
			Constant::B2C_ORDER_PAY_TYPE_CASH,
			Constant::B2C_ORDER_PAY_TYPE_WECHAT,
			Constant::B2C_ORDER_PAY_TYPE_TICKET,
            Constant::B2C_ORDER_PAY_TYPE_TICKET_CASH,
            Constant::B2C_ORDER_PAY_TYPE_TICKET_WECHAT,
			Constant::B2C_ORDER_PAY_TYPE_ALL,
		))){
			$this->apiReturn(Constant::API_GET_ORDER_LIST_PARAM_ERROR, $_POST, '订单列表参数错误');
		}
		$is_page = I('post.is_page', 0, 'intval');
		if($is_page == 1){
			$page = I('post.page', 1, 'intval');
			$pagesize = I('post.pagesize', 10, 'intval');
		}

		$orderModel = M('b2c_order');
		$where = array(
			'config_id' => $configId,
			'openid' => $openid,
			//'type' => Constant::B2C_ORDER_TYPE_NORMAL,
			);
		if($orderPayStatus != Constant::B2C_ORDER_PAY_STATUS_ALL){
			$where['pay_status'] = $orderPayStatus;
		}
		if($orderStatus != Constant::B2C_ORDER_STATUS_ALL){
			$where['order_status'] = $orderStatus;
		}
		if($orderPayType != Constant::B2C_ORDER_PAY_TYPE_ALL){
			$where['pay_type'] = $orderPayType;
		}
		// $orderParams = $orderModel->alias('o')
		// 	->join(C('DB_PREFIX')." wechat_user_address a on o.address_id = a.id ", 'left')
		// 	->field("o.id, o.address_id, o.order_sn, o.order_price, o.pay_type, o.pay_status, o.order_status,o.create_time, a.alias, a.name, a.tel, a.pcd, a.gps")
		// 	->where($where)
		// 	->order('o.id desc');
		$orderParams = $orderModel->where($where)
			->field("id, openid, config_id, address_id, order_sn, order_price, pay_type, pay_status, order_status, create_time, bucket, ticket_num")
			->order('order_status desc, create_time desc');
		if($is_page == 1){
			$orderParams->limit(($page-1)*$pagesize, $pagesize);
		}
		$orderList = $orderParams->select();
		foreach ($orderList as $key => &$value) {

			if($value['address_id'] > 0 ){
				$address = M('wechat_user_address')->where(array(
					'config_id' => $value['config_id'],
					'openid' => $value['openid'],
					'id' => $value['address_id'],
					))
				 	->find();
				if(!empty($address)){
					$value['alias'] = $address['alias'];
					$value['name'] = $address['name'];
					$value['tel'] = $address['tel'];
					$value['pcd'] = $address['pcd'];
					$value['gps'] = $address['gps'];
				}
			}
		}	
		unset($value);
		//获取订单商品详细
		foreach ($orderList as &$value) {
			$goodsDetail = M('b2c_order_detail') -> where(array('order_id' => $value['id'])) -> field('goods_id, goods_img, goods_name, goods_num, sub_total')->select();
			if(!empty($goodsDetail)){
				foreach ($goodsDetail as &$vo) {
					$vo['img'] = strval($vo['goods_img']);
					$vo['goods_name'] = strval($vo['goods_name']);
					if(mb_strlen($vo['goods_name'], 'utf8') > 10){
						$vo['goods_name_utf8_10'] = mb_substr($vo['goods_name'], 0, 10, 'utf8').'...';
					}else{
						$vo['goods_name_utf8_10'] = strval($vo['goods_name']);
					}
					$vo['goods_num'] = intval($vo['goods_num']);
					$vo['sub_total'] = sprintf("%.2f", $vo['sub_total']);
					unset($vo['goods_id']);
				}
				unset($vo);
			}
			$value['goods'] = $goodsDetail;
		}
		unset($value);
		
		//格式化信息
		if(!empty($orderList)){
			foreach($orderList as &$order){
				$order['id'] = intval($order['id']);
				if(isset($order['address_id']))
					$order['address_id'] = intval($order['address_id']);
				$order['order_sn'] = strval($order['order_sn']);
				$order['order_price'] = sprintf("%.2f", $order['order_price']);
				$order['pay_type'] = intval($order['pay_type']);
				$order['pay_status'] = intval($order['pay_status']);
				$order['order_status'] = intval($order['order_status']);
				$order['create_time'] = intval($order['create_time']);
				$order['bucket'] = intval($order['bucket']);
				$order['bucket_price'] = Constant::B2C_ORDER_BUCKET_PRICE;
				$order['ticket_num'] = intval($order['ticket_num']);
				if(isset($order['alias']))
					$order['alias'] = strval($order['alias']);
				if(isset($order['name']))
					$order['name'] = strval($order['name']);
				if(isset($order['tel']))
					$order['tel'] = strval($order['tel']);
				if(isset($order['pcd']))
					$order['pcd'] = strval($order['pcd']);
				if(isset($order['gps']))
					$order['gps'] = strval($order['gps']);
				unset($order['config_id']);
				unset($order['openid']);
			}
		}
		unset($order);
		$orderList = array('order' => $orderList, 'is_page' => $is_page);
		if($is_page == 1){
			$orderList['page'] = $page;
			$orderList['pagesize'] = $pagesize;
		}
		$this->apiReturn(Constant::API_SUCCESS, $orderList, '获取订单列表成功');
	}
	public function getOrderDetail(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$orderId = I('post.order_id', 0, 'intval');

		// 参数验证：order_id > 0 & order exists
		try{
			if(1 > $orderId){
				throw new \Exception('Get order info order_id illegal', Constant::API_ORDER_INFO_PARAM_ERROR);
			}else{
				$order = M('b2c_order')->where(array(
					'config_id' => $configId,
					'openid' => $openid,
					'id' => $orderId,
				))->find();
				if(0 == count($order)){
					throw new \Exception('Order is not exists', Constant::API_ORDER_NOT_EXIST);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$orderDetail = M('b2c_order_detail')->where(array(
			'order_id' => intval($order['id']),
		))->select();
		if(0 != count($orderDetail)){
			$orderGoods = array();
			foreach ($orderDetail as $value) {
				$goodsTemp = array();
				$goodsTemp['goods_name'] = strval($value['goods_name']);
				if(mb_strlen($value['goods_name'], 'utf8') > 10){
					$goodsTemp['goods_name_utf8_10'] = mb_substr($value['goods_name'], 0, 10, 'utf8').'...';
				}else{
					$goodsTemp['goods_name_utf8_10'] = strval($value['goods_name']);
				}
				$goodsTemp['goods_price'] = sprintf('%.2f', $value['goods_price']);
				$goodsTemp['sub_total'] = sprintf('%.2f', $value['sub_total']);
				$goodsTemp['goods_num'] = intval($value['goods_num']);
				$goodsTemp['goods_img'] = strval($value['goods_img']);
				array_push($orderGoods, $goodsTemp);
			}
		}else{
			$orderGoods = false;
		}

		//接单水工信息
		if($order['deliver_id'] > 0){
			$deliver = M('station_deliver') -> where(array('id' => $order['deliver_id'])) -> field('name, tel') -> find();
			$deliver['name'] = strval($deliver['name']);
			$deliver['tel'] = substr($deliver['tel'], 0 ,3).'****'.substr($deliver['tel'], -4);
			$deliver['tel'] = strval($deliver['tel']);
		}else{
			$deliver = false;
		}
		
		$address = M('wechat_user_address')->where(array(
			'config_id' => $configId,
			'id' => intval($order['address_id']),
		))->find();
		if(0 != count($address)){
			$addressInfo = array(
				'name' => strval($address['name']),
				'tel' => strval($address['tel']),
				'pcd' => strval($address['pcd']),
				'detail' => strval($address['detail']),
				'gps' => strval($address['gps']),
			);
		}else{
			$addressInfo = false;
		}

		$rtn = array(
			'order_id' => $order['id'],
			'order_price' => sprintf('%.2f', $order['order_price']),
			'pay_type' => intval($order['pay_type']),
			'pay_status' => intval($order['pay_status']),
			'comment' => strval($order['comment']),
			'order_status' => $order['order_status'],
			'ticket_num' => intval($order['ticket_num']),
			'create_time' => intval($order['create_time']),
			'order_sn' => strval($order['order_sn']),
			'bucket' => intval($order['bucket']),
			'bucket_price' => Constant::B2C_ORDER_BUCKET_PRICE,
		);
		if($orderGoods){
			$rtn['goods'] = $orderGoods;
		}
		if($addressInfo){
			$rtn['address'] = $addressInfo;
		}
		if($deliver){
			$rtn['deliver'] = $deliver;
		}
		$this->apiReturn(Constant::API_SUCCESS, $rtn, '获取订单详情成功');
	}

	/**
	 * 向下单的微信粉丝发送《订单提交成功》模板消息
	 * @param  object $wechat         Common\Lib\Wechat instance
	 * @param  string $openid         wechat user openid
	 * @param  array  $goodsInfoArr   goods info
	 * @param  array  $orderInfoArr   order info
	 * @param  array  $addressInfoArr address info
	 * @param  string  $type 0普通订单1组合商品订单
	 */
	private function _sendWechatUserOrderSuccessTemplateMsg($wechat, $openid, $goodsInfoArr, $orderInfoArr, $addressInfoArr, $orderId, $type=0){
		$first = '收货后请将配送码('.$orderInfoArr['send_code'].')告诉送水小哥,谢谢！';
		if($type == 0){
			$goodsInfoStr = $goodsInfoArr[0]['name'].$goodsInfoArr[0]['standard'].'/'.$goodsInfoArr[0]['unit'].' x '.$goodsInfoArr[0]['num'];
			if(1 != count($goodsInfoArr)){
				$goodsInfoStr .= ', ...';
			}
		}else{
			$goodsType = $goodsInfoArr['type'] == 1 ? '水票' : '套餐';
			$goodsInfoStr = $goodsType.$goodsInfoArr['name'].' X 1';
		}
		
		$orderSn = $orderInfoArr['order_sn'];
		$orderTime = date('Y-m-d H:i', $orderInfoArr['create_time']);
		$deliverTime = date('H:i', $orderInfoArr['create_time']).' ~ '.date('H:i', intval($orderInfoArr['create_time']) + 1800);
		$addressStr = $addressInfoArr['detail'];
		$remark = '您的订单我们已经收到，将尽快为您配送~~~';

    	$data = array(
    		'first'    => $first,
    		'keyword1' => $goodsInfoStr,
    		'keyword2' => $orderSn,
    		'keyword3' => $orderTime,
    		'keyword4' => $deliverTime,
    		'keyword5' => $addressStr,
    		'remark'   => $remark,
    	);
    	$configId = $wechat->getConfigId();
    	$prefix = $wechat->getWechatFunctionPrefix();
    	$file = 'order-detail.html';
    	$url = HOSTNAME.$prefix.'/'.$file.'#config_id='.$configId.'&openid='.$openid.'&oid='.$orderId;
    	try{
    		$templateId = $wechat->getTemplateMsgId('order_success_tpl_id');
    		$wechat->sendWechatTemplateMsg($openid, $templateId, $data, $url);
    	}catch(\Exception $e){
    		Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
    		record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
    	}
	}

	/**
	 * 向水站的水工发送《新订单提醒》模板消息
	 * @param  object $wechat         Common\Lib\Wechat instance
	 * @param  array  $goodsInfoArr   goods info
	 * @param  array  $orderInfoArr   order info
	 * @param  array  $addressInfoArr address info
	 * @param  int    $orderId 		  order id
	 * @param  string  $type 0普通订单1组合商品订单
	 */
	private function _sendStationDeliverNewOrderTemplateMsg($wechat, $goodsInfoArr, $orderInfoArr, $addressInfoArr, $orderId, $type = 0){
		$first = '新订水订单提醒';
		$orderSn = $orderInfoArr['order_sn'];
		if($type == 0){
			$goodsInfoStr = $goodsInfoArr[0]['name'].$goodsInfoArr[0]['standard'].'/'.$goodsInfoArr[0]['unit'].' x '.$goodsInfoArr[0]['num'];
			if(1 != count($goodsInfoArr)){
				$goodsInfoStr .= ', ...';
			}
		}else{
			$goodsType = $goodsInfoArr['type'] == 1 ? '水票' : '套餐';
			$goodsInfoStr = $goodsType.$goodsInfoArr['name'].' X 1';
		}
		$orderPrice = $orderInfoArr['order_price'].' 元';
		$addressInfoArr['tel'] = substr($addressInfoArr['tel'], 0 ,3).'****'.substr($addressInfoArr['tel'], -4);
		$addressStr = $addressInfoArr['name'].' '.$addressInfoArr['tel'];
		if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_CASH){
			$payType = '现金支付';
		}else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_WECHAT){
			$payType = '微信支付';
		}else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET){
			$payType = '水票支付';
		}else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET_CASH){
            $payType = '水票支付和线下支付';
        }else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET_WECHAT){
            $payType = '水票支付和微信支付';
        }
		$remark = '请及时处理（点击查看详情并派单）';
    	$data = array(
    		'first'    => $first,
    		'keyword1' => $orderSn,
    		'keyword2' => $goodsInfoStr,
    		'keyword3' => $orderPrice,
    		'keyword4' => $addressStr,
    		'keyword5' => $payType,
    		'remark'   => $remark,
    	);

    	$templateId = $wechat->getTemplateMsgId('order_push_tpl_id');
    	$configId = $wechat->getConfigId();
    	// 查看新订单推送方式
    	$deliverModel = Constant::DELIVER_MODEL;
	    	$delivers = M('wechat_user') -> where(array(
	    		'config_id' => $configId,
	    		'is_boss' => Constant::USER_TYPE_BOSS,
	    		))
	    		-> field('openid')
	    		-> select();
	    	$file = 'boss-order-list.html';
    	$prefix = $wechat->getWechatFunctionPrefix();
    	$url = HOSTNAME.$prefix.'/'.$file.'#config_id='.$configId.'&openid=OPENID&oid='.$orderId;
    	try{
    		foreach ($delivers as $deliver) {
    			$detailUrl = str_replace('OPENID', $deliver['openid'], $url);
	    		$wechat->sendWechatTemplateMsg($deliver['openid'], $templateId, $data, $detailUrl);
	    	}
    	}catch(\Exception $e){
    		Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
    		record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
    	}
	}

	/**
	 * 组合商品订单提交
	 */
	public function combineOrderSubmit(){
		$strategyModel = M('goods_strategy');
		$strategyDetailModel = M('goods_strategy_detail');
		$configId = intval(I('post.config_id'));
		$openid = trim(I('post.openid'));
		$type = intval(I('post.type'));
		$send_code = intval(rand(1000,9999));
		try{
			// 检测address_id
			$addressId = intval(I('post.address_id'));
			if(1 > $addressId){
				throw new \Exception('Order submit param address_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}else{
				$address = M('wechat_user_address')
					->where(array(
						'config_id' => $configId,
						'openid' => $openid,
						'id' => $addressId,
						'status' => array('in', array(
							Constant::USER_ADDRESS_STATUS_ONUSE,
							Constant::USER_ADDRESS_STATUS_DEFAULT,
						)),
					))
					->find();
				if(0 == count($address)){
					throw new \Exception('Order submit param address_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
				}
			}
			// 检测pay_type
			$payType = I('post.pay_type',0,'intval');
			// TODO: 目前只支持线下支付
			if(!in_array($payType, array(
				Constant::B2C_ORDER_PAY_TYPE_CASH,
				Constant::B2C_ORDER_PAY_TYPE_WECHAT,
			))){
				throw new \Exception('Order submit param pay_type is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
			// 检测订单类型
			if(!in_array($type, array(
				Constant::B2C_ORDER_SUBMIT_TYPE_TICKET,
				Constant::B2C_ORDER_SUBMIT_TYPE_PACKAGE,
			))){
				throw new \Exception('Order submit param type is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
			// 检测组合商品信息，水票->strategy_id  套餐->strategy_id
			$strategyId = I('strategy_id', 0, 'intval');
			if($strategyId <= 0){
				throw new \Exception('Order submit param strategy_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
			$strategy = $strategyModel -> where(array('id' => $strategyId, 'type' => $type, 'status' => Constant::B2C_STRATEGY_STATUS_ON)) -> find();
			if(empty($strategy)){
				throw new \Exception('strategy is not exists', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
			// 判断是否在活动中
			if($strategy['end_time'] < time() || $strategy['start_time'] > time()){
				throw new \Exception('strategy is not in activity', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
			// 判断如果是套餐，strategy_detail内容是否存在
			$strategyDetail = $strategyDetailModel -> where(array('sid' => $strategyId)) -> select();
			if(empty($strategyDetail)){
				throw new \Exception('strategy_detail is not exists', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}

		$orderSn = $this->createB2COrderSn($configId);

		if($payType == Constant::B2C_ORDER_PAY_TYPE_CASH){
			// 现金支付
			$payStatus = Constant::B2C_ORDER_PAY_STATUS_NOPAY;
		}else if($payType == Constant::B2C_ORDER_PAY_TYPE_WECHAT){
			// 微信支付
			$payStatus =  Constant::B2C_ORDER_PAY_STATUS_NOPAY;
		}
		$transModel = M();
		$orderModel = M('b2c_order');
		$orderDetailModel = M('b2c_order_detail');
		$userTicketModel = M('wechat_user_ticket');

		// 微信用户订单提交成功模板消息数据
		$wechatUserMsgData = array(
			'name' => $strategy['name'],
			'type' => $strategy['type'],
			'price' => $strategy['price'],
		);

		$transModel->startTrans();
		$orderData = array(
			'config_id' => $configId,
			'openid' => $openid,
			'address_id' => $addressId,
			'order_sn' => $orderSn,
			'order_price' => $strategy['price'],
			'order_original_price' => $strategy['price'],
			'pay_type' => $payType,
			'pay_status' => $payStatus,
			'pay_id' => '',
			'order_status' => 0,
			'create_time' => time(),
			'type' => 2,
			'send_code' =>$send_code,
		);
		try{
			$orderId = intval($orderModel->add($orderData));
			if(1 > $orderId){
				$transModel->rollback();
				throw new \Exception('Order Submit Error, failed to insert order data', Constant::API_ORDER_SUBMIT_ERROR);
			}else{
				//将水票数据添加到wechat_user_ticket
				$userTicketData = array();
				if($type == Constant::B2C_ORDER_SUBMIT_TYPE_TICKET){
					$userTicketDataTicket = array(
						'config_id' => $configId,
						'openid' => $openid,
						'goods_id' => $strategyDetail[0]['goods_id'],
						'goods_name' => $strategyDetail[0]['goods_name'],
						'goods_img' => $strategyDetail[0]['goods_img'],
						'goods_price' => $strategyDetail[0]['goods_price'],
						'name' => $strategy['name'],
						'price' => $strategy['price'],
						'status' => Constant::WECHAT_USER_TICKET_LOCKED,
						'createtime' => time(),
						'order_id' => $orderId,
						'strategy_id' => $strategy['id'],
						);
					// 活动
					if(time() >= $strategy['start_time'] && time() <= $strategy['end_time']){
						$userTicketDataTicket['condition'] = $strategy['condition'];
					}
					$userTicketDataTicket['start_time']=$strategy['start_time'];
					$userTicketDataTicket['end_time']=$strategy['end_time'];
					//$userTicketDataTicket['start_time'] = time();
					//$userTicketDataTicket['end_time'] = $userTicketDataTicket['start_time'] + Constant::API_TICKET_OUT_TIME;
					for($i=1; $i <= ($strategyDetail[0]['num'] + $strategyDetail[0]['givenum']); $i++){
						array_push($userTicketData, $userTicketDataTicket);
					}
				}else{
					foreach($strategyDetail as $detail){
						if($detail['type'] == 1){
							$detailStrategy = $strategyModel -> where(array('id' => $detail['goods_id'])) -> find();
							$detailStrategyDetail = $strategyDetailModel -> where(array('sid' => $detailStrategy['id'])) -> find(); 
							$userTicketDataPackage = array(
								'config_id' => $configId,
								'openid' => $openid,
								'goods_id' => $detailStrategyDetail['goods_id'],
								'goods_name' => $detailStrategyDetail['goods_name'],
								'goods_img' => $detailStrategyDetail['goods_img'],
								'goods_price' => $detailStrategyDetail['goods_price'],
								'name' => $detailStrategy['name'],
								'price' => $detailStrategy['price'],
								'status' => Constant::WECHAT_USER_TICKET_LOCKED,
								'strategy_id' => $detailStrategy['id'],
								'order_id' => $orderId,
								'createtime' => time(),
								);
							// 活动
							if(time() >= $detailStrategy['start_time'] && time() <= $detailStrategy['end_time']){
								$userTicketDataTicket['condition'] = $detailStrategy['condition'];
							}
							$userTicketDataPackage['start_time']=$detailStrategy['start_time'];
							$userTicketDataPackage['end_time']=$detailStrategy['end_time'];
							//$userTicketDataPackage['start_time'] = time();
							//$userTicketDataPackage['end_time'] = $userTicketDataPackage['start_time'] + Constant::API_TICKET_OUT_TIME;
							for($i=1; $i <= ($detailStrategyDetail['num'] + $detailStrategyDetail['givenum']); $i++){
								array_push($userTicketData, $userTicketDataPackage);
							}
						}
					}
				}
				// var_dump($detailStrategyDetail);
				if(1 == count($userTicketData)){
					$ticketId = intval($userTicketModel->add($userTicketData[0]));
				}else{
					$ticketId = intval($userTicketModel->addAll($userTicketData));
				}
				if(1 > $ticketId){
					$transModel->rollback();
					throw new \Exception('Order Submit Error, failed to insert wechat_user_ticket data', Constant::API_ORDER_SUBMIT_ERROR);
				}
				//order_detail
				// 组合商品为水票，套餐分别处理
				if($type == 1){
					$orderDetailData[] = array(
							'goods_id' => $strategyId,
							'goods_name' => $strategy['name'],
							'goods_price' => $strategy['price'],
							'goods_num' => 1,
							'sub_total' => $strategy['price'] * 1,
							'order_id' => $orderId,
							'goods_img' => $strategyDetail[0]['goods_img'],
							'goods_type' => Constant::B2C_ORDER_DETAIL_GOODS_TYPE_TICKET,
						);
				}else{
					foreach ($strategyDetail as $key => $value) {
						$orderDetailData[$key]['goods_id'] =  $value['goods_id'];
						$orderDetailData[$key]['goods_name'] =  $value['goods_name'];
						$orderDetailData[$key]['goods_price'] =  $value['goods_price'];
						$orderDetailData[$key]['goods_num'] =  1;
						$orderDetailData[$key]['sub_total'] =   $value['goods_price'] * $orderDetailData[$key]['goods_num'];
						$orderDetailData[$key]['order_id'] =  $orderId;
						$orderDetailData[$key]['goods_img'] = $value['goods_img'];
						if($value['type'] == 1){
							$orderDetailData[$key]['goods_type'] =  Constant::B2C_ORDER_DETAIL_GOODS_TYPE_TICKET;
						}else{
							$orderDetailData[$key]['goods_type'] =  Constant::B2C_ORDER_DETAIL_GOODS_TYPE_GOODS;
						}
					}
				}
				if($type == 1){
					$orderDetailId = intval($orderDetailModel->add($orderDetailData[0]));
				}else{
					$orderDetailId = intval($orderDetailModel->addAll($orderDetailData));
				}
				if(1 > $orderDetailId){
					$transModel->rollback();
					throw new \Exception('Order Submit Error, failed to insert order data', Constant::API_ORDER_SUBMIT_ERROR);
				}else{
					$transModel->commit();
					$wechat = $this->getWechatObject($configId);
					$this->_sendWechatUserOrderSuccessTemplateMsg($wechat, $openid, $wechatUserMsgData, $orderData, $address, $orderId,1);
					$this->_sendStationDeliverNewOrderTemplateMsg($wechat, $wechatUserMsgData, $orderData, $address, $orderId, 1);
					$this->apiReturn(Constant::API_SUCCESS, array('order_id'=>$orderId), '订单提交成功');
				}
			}
		}catch(\Exception $e){
			$transModel -> rollback();
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}

}
