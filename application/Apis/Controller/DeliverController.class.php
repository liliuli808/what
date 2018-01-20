<?php
namespace Apis\Controller;
use Apis\Controller\ApiController;
use Think\Log;
use Common\Lib\Constant;
use Common\Lib\Wechat;
use Common\Lib\Tool;

class DeliverController extends ApiController{

	public function orderList(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		try{
			$deliver = M('station_deliver')->where(array(
				'openid' => $openid,
				'config_id' => $configId,
				'status' => 1,
			))->find();
			if(0 == count($deliver)){
				throw new \Exception('你并不是水工', Constant::API_FAILED);
				
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$orderModel = M('b2c_order');
		$orderList = $orderModel->where(array(
			'config_id' => $configId,
			'deliver_id' => $deliver['id'],
		))->order('order_status asc, deliver_accept_time desc')->select();

		$data = array();
		$addressModel = M('wechat_user_address');
		foreach ($orderList as $order) {
			$address = $addressModel->where(array('id'=>$order['address_id']))->find();
			array_push($data, array(
				'order_id' => $order['id'],
				'order_sn' => $order['order_sn'],
				'order_status' => $order['order_status'],
				'address_id' => $address['id'],
				'address_name' => $address['name'],
				'address_tel' => substr($address['tel'], 0 ,3).'****'.substr($address['tel'], -4),
				'address_pcd' => $address['pcd'],
				'address_detail' => $address['detail'],
				'deliver_time' => date('Y-m-d H:i', $order['create_time']+3600).' 前',
			));
		}
		$this->apiReturn(Constant::API_SUCCESS, $data, '获取订单列表成功！');
	}

	public function bindDeliver(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$name = I('post.name', '', 'trim');
		$tel = I('post.tel', '', 'trim');
        $station_id = I('post.station_id',0,'intval');
		try{
			if(0 == strlen($name)){
				throw new \Exception('水工姓名不能为空', Constant::API_FAILED);
			}
			if(0 == strlen($tel) || !Tool::checkPhone($tel)){
				throw new \Exception('水工电话格式错误', Constant::API_FAILED);
			}
            $station=M('station')->where(array('config_id'=>$configId,'status'=>Constant::STATION_STATUS_ONSALE,'id'=>$station_id))->select();
            if(0 == count($station)){
                throw new \Exception('选择的水站错误', Constant::API_FAILED);
            }
			$wechatUser = M('wechat_user')->where(array('openid'=>$openid))->find();
			if(0 == count($wechatUser)){
				throw new \Exception('水工绑定失败', Constant::API_FAILED);
			}else{
				$deliver = M('station_deliver')->where(array(
					'config_id' => $configId,
					'openid' => $openid,
				))->find();
				if(0 != count($deliver)){
					throw new \Exception('你已经进行过水工绑定了', Constant::API_FAILED);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$deliverId = M('station_deliver')->add(array(
			'config_id' => $configId,
			'openid' => $openid,
			'name' => $name,
			'tel' => $tel,
			'create_time' => time(),
            'station_id' =>$station_id,
			// 'status' => 0,
			'status' => 0,
		));
		if(1 > intval($deliverId)){
			$this->apiReturn(Constant::API_FAILED, '', '系统错误，请稍候再试...');
		}else{
			$this->apiReturn(Constant::API_SUCCESS, array('id'=>$deliverId), '水工绑定成功！');
		}
	}

	/**
	 * 水工获取订单详情，用于新订单推送的详情展示
	 * @return [type] [description]
	 */
	public function getOrderInfo(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$orderId = I('post.order_id', 0, 'intval');

		// 参数验证：order_id > 0 & order exists
		try{
			if(1 > $orderId){
				throw new \Exception('order is not exists', Constant::API_DELIVER_ORDER_INFO_PARAM_ERROR);
			}else{
				$order = M('b2c_order')->where(array(
					'config_id' => $configId,
					'id' => $orderId
				))->find();
				if(0 == count($order)){
					throw new \Exception('order is not exists', Constant::API_DELIVER_ORDER_INFO_PARAM_ERROR);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$isAccept = intval($order['deliver_id']) == 0 ? 0 : 1;
		if($isAccept){
			$deliver = M('station_deliver')->where(array(
				'config_id' => $configId,
				// 对于历史订单，可能存在接单水工已经离职或休假的情况，展示订单详情时，不对水工状态做出限制
				// 'status' => Constant::STATION_DELIVER_STATUS_OPEN,
				'id' => intval($order['deliver_id']),
			))->find();
			if(0 == count($deliver)){
				$deliverInfo = false;
			}else{
				$deliver['tel'] = substr($deliver['tel'], 0 ,3).'****'.substr($deliver['tel'], -4);
				$deliverInfo = array(
					'name' => strval($deliver['name']),
					'tel' => strval($deliver['tel']),
					'openid' => strval($deliver['openid']),
				);
			}
		}
		$orderDetail = M('b2c_order_detail')->where(array(
			'order_id' => intval($order['id']),
		))->select();
		if(0 != count($orderDetail)){
			$orderGoods = array();
			foreach ($orderDetail as $value) {
				$goodsTemp = array();
				$goodsTemp['goods_id'] = strval($value['goods_id']);
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

		$address = M('wechat_user_address')->where(array(
			'config_id' => $configId,
			'id' => intval($order['address_id']),
		))->find();
		if(0 != count($address)){
			$address['tel'] = substr($address['tel'], 0 ,3).'****'.substr($address['tel'], -4);
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
			'is_accept' => $isAccept,
			'create_time' => $order['create_time'],
			'order_sn' => $order['order_sn'],
			'comment' => $order['comment'],
			'bucket' => intval($order['bucket']),
			'order_status' => intval($order['order_status']),
			'deliver_accept_time' => intval($order['deliver_accept_time']),
		);
		if($isAccept && $deliverInfo){
			$rtn['deliver'] = $deliverInfo;
		}
		if($orderGoods){
			$rtn['goods'] = $orderGoods;
		}
		if($addressInfo){
			$rtn['address'] = $addressInfo;
		}

		$this->apiReturn(Constant::API_SUCCESS, $rtn, '获取订单详情成功');
	}

	/**
	 * 水工接单
	 * @return [type] [description]
	 */
	public function acceptOrder(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$orderId = I('post.order_id', 0, 'intval');

		// 参数验证：order_id > 0 & order exists
		try{
			if(1 > $orderId){
				throw new \Exception('Order is not exists', Constant::API_DELIVER_ACCEPT_ORDER_PARAM_ERROR);
			}else{
				$orderModel = M('b2c_order');
				$order = $orderModel->where(array(
					'config_id' => $configId,
					'id' => $orderId
				))->find();
				if(0 == count($order)){
					throw new \Exception('Order is not exists', Constant::API_DELIVER_ACCEPT_ORDER_PARAM_ERROR);
				}else{
					if(0 != intval($order['deliver_id'])){
						throw new \Exception('Order allready accepted', Constant::API_DELIVER_ORDER_ALREADY_ACCEPT);
					}else if(Constant::B2C_ORDER_STATUS_CANCELED == intval($order['order_status'])){
						throw new \Exception('Order is canceled', Constant::API_DELIVER_ORDER_ALREADY_CANCELED);
					}else{
						$deliver = M('station_deliver')->where(array(
							'config_id' => $configId,
							// 对于水工接单，必须为启用状态
							'status' => Constant::STATION_DELIVER_STATUS_OPEN,
							'openid' => $openid,
						))->find();
						if(0 == count($deliver)){
							throw new \Exception('Deliver info error', Constant::API_DELIVER_INFO_ERROR);
						}else{
							$orderData = array(
								'deliver_id' => intval($deliver['id']),
								'deliver_accept_time' => time(),
								'order_status' => Constant::B2C_ORDER_STATUS_DELIVERING,
								'deliver_type' =>1,
							);
							$saveOrder = intval($orderModel->where(array('id'=>$orderId))->save($orderData));
							if($saveOrder){
								$boss_deliver=M('boss_deliver')->where(array('config_id'=>$configId,'order_id'=>$orderId))->save(array('status'=>1));
								if($boss_deliver){
									$deliver['tel'] = substr($deliver['tel'], 0 ,3).'****'.substr($deliver['tel'], -4);
									$rtn = array(
										'order_id' => $orderId,
										'name' => strval($deliver['name']),
										'tel' => strval($deliver['tel']),
									);
									$wechat = new Wechat($configId);
									$goodsInfo = M('b2c_order_detail')
										->field('goods_name as name, goods_num as num')
										->where('order_id = '.$orderId)
										->select();
									$this->_sendWechatUserOrderDelivingTplMsg($wechat, $order, $goodsInfo, $rtn);
									$this->apiReturn(Constant::API_SUCCESS, $rtn, '接单成功，请尽快配送');
								}else{
									throw new \Exception('系统错误，接单失败', Constant::API_FAILED);
								}
							}else{
								throw new \Exception('系统错误，接单失败', Constant::API_FAILED);
							}
						}
					}
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}

	/**
	 * 确认送法并收款
	 * @return [type] [description]
	 */
	public function finishOrder(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$orderId = I('post.order_id', 0, 'intval');
		try{
			if(1 > $orderId){
				throw new \Exception('Order is not exists', Constant::API_ORDER_NOT_EXIST);
			}else{
				$orderModel = M('b2c_order');
				$order = $orderModel->where(array(
					'config_id' => $configId,
					'id' => $orderId
				))->find();
				if(0 == count($order)){
					throw new \Exception('Order is not exists', Constant::API_ORDER_NOT_EXIST);
				}
			}
			$deliver = M('station_deliver')->where(array(
				'config_id' => $configId,
				// 对于水工接单，必须为启用状态
				'status' => Constant::STATION_DELIVER_STATUS_OPEN,
				'openid' => $openid,
			))->find();
			if(0 == count($deliver)){
				throw new \Exception('Order is not allow finished', Constant::API_ORDER_CAN_NOT_FINISH);
			}else{
				if($order['deliver_id'] != $deliver['id']){
					throw new \Exception('Order is not allow finished', Constant::API_ORDER_CAN_NOT_FINISH);
				}
				if($order['order_status'] != Constant::B2C_ORDER_STATUS_DELIVERING){
					throw new \Exception('Order is not allow finished', Constant::API_ORDER_CAN_NOT_FINISH);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}

		$orderData = array(
			'order_status' => Constant::B2C_ORDER_STATUS_FINISHED,
			'finish_time' => time(),
		);
		if($order['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_CASH){
			$orderData['pay_status'] = Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
		}elseif ($order['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET) {
			$orderData['pay_status'] = Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
		}elseif ($order['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET_CASH) {
			$orderData['pay_status'] = Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
		}elseif ($order['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET_WECHAT) {
			$orderData['pay_status'] = Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
		}
		$saveOrder = intval($orderModel->where(array('id'=>$orderId))->save($orderData));
		if($saveOrder){
			$orderDetail = M('b2c_order_detail')
				->field('goods_name as name, goods_num as num, goods_type, goods_id')
				->where('order_id = '.$orderId)
				->select();
			//销量,销量是否更改无影响
			$goodsId = array();
			foreach ($orderDetail as $key => $value) {
				if($value['goods_type'] == 0){
					$goodsId[$key]['goods_id'] = $value['goods_id']; 
					$goodsId[$key]['goods_num'] = $value['num'];
				}else{
					$goodsId[$key]['goods_id'] = M('goods_strategy') -> where(array('id' => $value['goods_id'])) -> getField('goods_id');
					$goodsId[$key]['goods_num'] = M('goods_strategy_detail') -> where(array('sid' => $value['goods_id'])) -> getField('num');
				}
			}
			//循环更新商品销量
			if(!empty($goodsId)){
				for($i=0;$i<count($goodsId);$i++){
					M('goods') -> where(array('id' => $goodsId[$i]['goods_id'])) -> setInc('sales',intval($goodsId[$i]['goods_num']));
				}
			}
			$userTicketModel = M('wechat_user_ticket');
			//如果是购买水票,修改用户水票状态为可用
			if($order['type'] == Constant::B2C_ORDER_TYPE_COMBINE){
				$userTicketModel -> where(array('order_id' => $orderId, 'status' => Constant::WECHAT_USER_TICKET_LOCKED)) -> save(array('status' => Constant::WECHAT_USER_TICKET_NORMAL));
			}
			//如果订单使用水票
			$tickets = $userTicketModel -> where(array(
				'user_order_id' => $orderId,
				'status' => Constant::WECHAT_USER_TICKET_ORDER_LOCKED,
				))
				->field('id')
				->select();
			if(!empty($tickets)){
				foreach ($tickets as $key => $value) {
					$userTicketModel -> where(array('id' => $value['id'])) -> save(array('status' => Constant::WECHAT_USER_TICKET_USED));
				}
			}
			$this->_sendWechatUserOrderFinishTplMsg($configId, $order, $orderDetail);
			$this->apiReturn(Constant::API_SUCCESS, array(), '确认送达成功');
		}else{
			$this->apiReturn(Constant::API_FAILED, array(), '系统错误，确认送达失败');
		}
	}
	/**
	 * 向微信用户发送订单确认送达模板消息
	 * @param  [type] $configId    [description]
	 * @param  [type] $order       [description]
	 * @param  [type] $orderDetail [description]
	 * @return [type]              [description]
	 */
	public function bossGetOrderList(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$deliver_type = I('post.deliver_type', 100, 'intval');
		try{
			if(!in_array($deliver_type, array(
				Constant::B2C_ORDER_DELIVER_TYPE_NONE,
				Constant::B2C_ORDER_DELIVER_TYPE_ALLREADY,
				Constant::B2C_ORDER_DELIVER_TYPE_BOTH,
			))){
				throw new \Exception('param deliver_type is illegal', Constant::API_FAILED);
			}
			$boss = M('wechat_user')->where(array(
				'openid' => $openid,
				'config_id' => $configId,
				'is_boss' => Constant::USER_TYPE_BOSS,
			))->find();
			if(0 == count($boss)){
				throw new \Exception('你并不是老板', Constant::API_FAILED);
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$is_page = I('post.is_page', 0, 'intval');
		if($is_page == 1){
			$page = I('post.page', 1, 'intval');
			$pagesize = I('post.pagesize', 10, 'intval');
		}
		$orderModel = M('b2c_order');
		$where = array('config_id' => $configId,'order_status' => 0);
		if($deliver_type != Constant::B2C_ORDER_DELIVER_TYPE_BOTH){
			$where['deliver_type'] = $deliver_type;
		}
		$orderParams = $orderModel->where($where)->order('create_time desc');
		if($is_page == 1){
			$orderParams->limit(($page-1)*$pagesize, $pagesize);
		}
		$orderList = $orderParams -> select();
		$data = array();
		$addressModel = M('wechat_user_address');
		foreach ($orderList as $order) {
			$address = $addressModel->where(array('id'=>$order['address_id']))->find();
			array_push($data, array(
				'order_id' => $order['id'],
				'order_sn' => $order['order_sn'],
				'order_status' => $order['order_status'],
				'order_price' => $order['order_price'],
				'address_id' => $address['id'],
				'address_name' => $address['name'],
				'address_tel' => substr($address['tel'], 0 ,3).'****'.substr($address['tel'], -4),
				'address_pcd' => $address['pcd'],
				'address_detail' => $address['detail'],
				'create_times' => date('Y-m-d H:i:s', $order['create_time']),
				'deliver_type' => intval($order['deliver_type']),
				'configId' => $configId,
				'openid' => $openid,
			));
		}
		$orderList = array('order' => $data, 'is_page' => $is_page);
		if($is_page == 1){
			$orderList['page'] = $page;
			$orderList['pagesize'] = $pagesize;
		}
		$this->apiReturn(Constant::API_SUCCESS, $orderList, '获取订单列表成功！');
	}
	/**
	 * 向微信用户发送订单确认送达模板消息
	 * @param  [type] $configId    [description]
	 * @param  [type] $order       [description]
	 * @param  [type] $orderDetail [description]
	 * @return [type]              [description]
	 */
	private function _sendWechatUserOrderFinishTplMsg($configId, $order, $orderDetail){
		try{
			$wechat = new Wechat($configId);
			$orderSn = $order['order_sn'];
			$goodsInfoStr = $orderDetail[0]['name'].' x '.$orderDetail[0]['num'];
			if(1 != count($orderDetail)){
				$goodsInfoStr .= ', ...';
			}
			$first = '您的订单已经确认送达';
			$remark = '感谢您对'.$wechat->getWechatName().'的支持';
			$data = array(
	    		'first'    => $first,
	    		'keyword1' => $orderSn,
	    		'keyword2' => $goodsInfoStr,
	    		'remark'   => $remark,
	    	);
    		$prefix = $wechat->getWechatFunctionPrefix();
    		$file = 'order-detail.html';
    		$url = HOSTNAME.$prefix.'/'.$file.'#config_id='.$configId.'&openid='.$order['openid'].'&oid='.$order['id'].'&is_template=1';;
	    	$templateId = $wechat->getTemplateMsgId('order_finish_tpl_id');
    		$wechat->sendWechatTemplateMsg($order['openid'], $templateId, $data, $url);
		}catch(\Exception $e){
    		Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
    		record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
    	}
	}

	/**
	 * 向微信用户发送发货通知模板消息
	 * @param  [type] $wechat         [description]
	 * @param  [type] $openid         [description]
	 * @param  [type] $goodsInfoArr   [description]
	 * @param  [type] $deliverInfoArr [description]
	 * @return [type]                 [description]
	 */
	private function _sendWechatUserOrderDelivingTplMsg($wechat, $order, $goodsInfoArr, $deliverInfoArr){
		$first = '您的订水订单已经开始配送了！';
		$wechatName = $wechat->getWechatName();
		$goodsInfoStr = $goodsInfoArr[0]['name'].' x '.$goodsInfoArr[0]['num'];
		if(1 != count($goodsInfoArr)){
			$goodsInfoStr .= ', ...';
		}
		$wechatTel = $wechat->getWechatTel();
		$deliverInfoStr = $deliverInfoArr['name'].$deliverInfoArr['tel'];
		$remark = '请您稍等，我们正在火速为您送达~~~';

    	$data = array(
    		'first'    => $first,
    		'keyword1' => $wechatName,
    		'keyword2' => $goodsInfoStr,
    		'keyword3' => $wechatTel,
    		'keyword4' => $deliverInfoStr,
    		'remark'   => $remark,
    	);
    	try{
    		$prefix = $wechat->getWechatFunctionPrefix();
    		$file = 'order-detail.html';
    		$url = HOSTNAME.$prefix.'/'.$file.'#config_id='.$configId.'&openid='.$order['openid'].'&oid='.$order['id'].'&is_template=1';
    		$templateId = $wechat->getTemplateMsgId('order_deliver_tpl_id');
    		$wechat->sendWechatTemplateMsg($order['openid'], $templateId, $data, $url);
    	}catch(\Exception $e){
    		Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
    		record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
    	}
	}


	/**
	*检查水工提交的配送码是否正确
	* send_code [b2b_order]*
	*****/

	public function sendCode(){
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$orderId = I('post.order_id', 0, 'intval');
		$sendCode = I('post.send_code', 0, 'intval');
		try{
			if(1 > $orderId){
				throw new \Exception('Order is not exists', Constant::API_ORDER_NOT_EXIST);
			}else{
				$orderModel = M('b2c_order');
				$order = $orderModel->where(array(
					'config_id' => $configId,
					'id' => $orderId
				))->find();
				if(0 == count($order)){
					throw new \Exception('Order is not exists', Constant::API_ORDER_NOT_EXIST);
				}
			}
			$deliver = M('station_deliver')->where(array(
				'config_id' => $configId,
				// 对于水工接单，必须为启用状态
				'status' => Constant::STATION_DELIVER_STATUS_OPEN,
				'openid' => $openid,
			))->find();
			if(0 == count($deliver)){
				throw new \Exception('Order is not allow finished', Constant::API_ORDER_CAN_NOT_FINISH);
			}else{
				if($order['deliver_id'] != $deliver['id']){
					throw new \Exception('Order is not allow finished', Constant::API_ORDER_CAN_NOT_FINISH);
				}
				if($order['order_status'] != Constant::B2C_ORDER_STATUS_DELIVERING){
					throw new \Exception('Order is not allow finished', Constant::API_ORDER_CAN_NOT_FINISH);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$saveOrder = intval($orderModel->where(array('id'=>$orderId,'send_code'=>$sendCode))->select());
		if($saveOrder){
			$this->apiReturn(Constant::API_SUCCESS, array(), '配送码正确');
		}else{
			$this->apiReturn(Constant::API_FAILED, array(), '系统错误，配送码不正确');
		}
	}
}