<?php
namespace Home\Controller;
use Home\Controller\HomebaseController;
use Common\Lib\Wechat;
use Think\Log;
use Common\Lib\Constant;
class WechatController extends HomebaseController{
	private $_wechat = false;

	public function __construct(){
		parent::__construct();
		C('SHOW_ERROR_MSG', false);
        C('SHOW_PAGE_TRACE', false);
        if(array_pop(explode('/', __ACTION__)) == 'wechatPayNotify'){
        }else{
        	$configId = intval(I('get.config_id'));
			try{
				$this->_wechat = new Wechat($configId);
			}catch(\Exception $e){   
				echo $e->getMessage();
				Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
				record_error($e);
				exit();
			}
        }
	}

	/**
	 * 页面入口，申请微信页面授权
	 * @return [type] [description]
	 */
	public function authorize(){
		$prefix = trim(strval(I('get.prefix')));
		$file = trim(strval(I('get.file')));
		if(isset($_GET['code']) && 0 != strlen(trim(I('get.code')))){
        	try{
        		$user = $this->_wechat->wechatUserAuthorizeCodeToAccessToken();
        	}catch(\Exception $e){
        		Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
				echo '['.$e->getCode().'] '.$e->getMessage();
				record_error($e);
				exit();
        	}
        	$url = 'http://'.$_SERVER['SERVER_NAME'].'/'.$prefix.'/'.$file.'#config_id='.$this->_wechat->getConfigId().'&openid='.$user['openid'];
        	header("Location:".$url);
        }else{
	    	$url = 'http://'.$_SERVER['SERVER_NAME'].U('Home/Wechat/authorize', array('config_id'=>$this->_wechat->getConfigId(), 'prefix'=>$prefix, 'file'=>$file));
			$this->_wechat->getWechatUserAuthorize($url);
        }
	}

	/**
	 * 微信开启服务器配置和事件回调（除支付通知外）地址
	 * 
	 * 服务器地址，如：
	 *     http://standard.edshui.com/index.php?g=Home&m=Wechat&a=index&config_id=2
	 * @return [type] [description]
	 */
	public function index(){
		// 开启服务器配置
		if(isset($_GET['echostr'])){
            $this->_wechat->openWechatServerMode();
        // 事件回调
        }else{
        	Log::write('Request $GLOBALS[\'HTTP_RAW_POST_DATA\']: '.$GLOBALS['HTTP_RAW_POST_DATA'], 'INFO');

        	try{
        		// 绑定微信关注事件
	            $this->_wechat->setEventCallback(Wechat::WX_EVENT_SUBSCRIBE, $this, 'subscribeCallback');
	            // 绑定微信取消关注事件
	            $this->_wechat->setEventCallback(Wechat::WX_EVENT_UNSUBSCRIBE, $this, 'unsubscribeCallback');
	            // 绑定微信用户发送消息事件
	            $this->_wechat->setEventCallback(Wechat::WX_EVENT_TEXT, $this, 'textCallback');
	            // 绑定微信菜单点击事件
	            $this->_wechat->setEventCallback(Wechat::WX_EVENT_CLICK, $this, 'clickCallback');
	            $this->_wechat->handleEvent();
        	}catch(\Exception $e){
        		echo $e->getMessage();
				Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
				record_error($e);
				exit();
        	}
        }
	}

	/**
	 * 关注回调函数
	 * @param  array 	$user    [description]
	 * @param  integer 	$sceneId [description]
	 * @param  string 	$ticket  [description]
	 * @return [type]          	 [description]
	 */
	public function subscribeCallback($user, $sceneId, $ticket){
		$userModel = M('wechat_user');
        $wxUser = $userModel->where(array('openid'=>$user['openid']))->find();
        if(0 == count($wxUser)){
            $user['nickname'] = $sceneId;
            $user['config_id'] = $this->_wechat->getConfigId();
            $user['tagid_list'] = json_encode($user['tagid_list']);
            $user['path'] = '0-';
            // 分销
            if($sceneId > 0 && $sceneId <= 100000){
            	$user['station_id'] = $sceneId;
            }else if($sceneId > 100000){
            	$sceneId = $sceneId - 100000;
            	$parentUser = $userModel -> where(array('id' => $sceneId, 'config_id' =>$this->_wechat->getConfigId())) -> find();
            	if(!empty($parentUser)){
            		$user['path'] = $parentUser['path'].'-';
            		$user['deeps'] = $parentUser['deeps'] + 1;	
            	}
            }
            $userId = $userModel->add($user);
            // 生成临时二维码
            $sceneId = 100000+$userId;
            $qrCode = $this->_wechat->createParameteredWechatQr($sceneId, false);
            $userModel -> save(array('id' => $userId, 'qr_code' => $qrCode, 'path' => $user['path'].$userId)); 
        }else{
        	$data = array(
				'subscribe' => 1,
			);
			if($sceneId > 0 && $sceneId <= 100000){
				$data['station_id'] = $sceneId;
			}
	        $userModel->where(array('openid'=>$user['openid']))->save($data);
        }
	}

	/**
	 * 取消关注回调函数
	 * @param  array $user [description]
	 * @return [type]      [description]
	 */
	public function unsubscribeCallback($user){
		$userModel = M('wechat_user');
		$data = array(
			'station_id' => 0,
			'subscribe' => 0,
		);
        $userModel->where(array('openid'=>$user['openid']))->save($data);
	}	

	/**
	 * 发送文本消息时的回调
	 * @param  [type] $text [description]
	 * @return [type]       [description]
	 */
	public function textCallback($user, $sendText){
		// 水工绑定
		if($sendText == '我是水工'){
			$responseText = $this->_bindDeliver($user['openid'], $sendTextBlankArr);
		}elseif($sendText == '我是老板'){
			$responseText = $this->_bindBossDeliver($user['openid'], $sendTextBlankArr);
		}elseif($sendText == '查看统计'){
			$responseText = $this->_tongji($user['openid'], $sendTextBlankArr);
		}else{
			$responseText = $sendText;
		}

		$this->_wechat->xmlTextResponse($user['openid'], $responseText);

		$userId = M('wechat_user')->where(array('openid'=>$user['openid']))->getField('id');
		M('wechat_user_msg')->add(array(
			'config_id' 	=> $this->_wechat->getConfigId(),
			'user_id' 		=> $userId,
			'nickname' 		=> base64_encode($user['nickname']),
			'msg' 			=> $sendText,
			'response'		=> $responseText,
			'create_time' 	=> time(),
		));
	}

	public function clickCallback(){

	}

	/**
	 * 微信支付回调
	 * @return [type] [description]
	 */
	public function wechatPayNotify($data=array()){
		$notifyData = @json_decode(@json_encode(simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		$configId = M('wechat_config')->where(array('appid'=>$notifyData['appid']))->getField('id');
		$wechat = new Wechat($configId);
		if(0 == count($data)){
			$wechat->wechatPayNotify($this, 'wechatPayNotify');
		}else{
			// 1. 回调去重
			$payLogModel = M('wechat_pay_log');
			$orderSnLast = substr($data['out_trade_no'],-6);
			$orderSn = explode($orderSnLast,$data['out_trade_no'])[0];
			$payLog = $payLogModel->where(array(
				'openid' => $data['openid'], 
				'out_trade_no' => $data['out_trade_no'],
			))->find();
			// 防止重复接收微信消息
			if(0 == count($payLog) || 1 == $payLog['status']){
				// var_dump('Repeated Pay Notify And Prevent');
				return false;
			}else{
				try{
					$orderModel = M('b2c_order');
					$userTicketModel = M('wechat_user_ticket');
					// 处理订单结果
					$order = $orderModel -> where(array(
						'order_sn' => $orderSn,
						'openid' => $data['openid'],
						))
						->field('id, type, create_time, address_id, order_sn, pay_type, order_price, order_status')
						->find();
					if($order['pay_type'] != Constant::B2C_ORDER_PAY_TYPE_WECHAT){
						throw new \Exception('Order pay type is not wechat', Constant::API_CALLBACK_ERROR);
					}
					if($order['order_status'] != Constant::B2C_ORDER_STATUS_WAITING){
						throw new \Exception('Order status error', Constant::API_CALLBACK_ERROR);
					}
					//更新订单状态
					$orderModel ->  where(array(
						'order_sn' => $orderSn,
						'openid' => $data['openid'],
						))
						-> save(array('order_status' => Constant::B2C_ORDER_STATUS_CREATED, 'pay_status' => Constant::B2C_ORDER_PAY_STATUS_SUCCESS));
					if($order['type'] == Constant::B2C_ORDER_TYPE_NORMAL){
						//如果订单使用水票
						$tickets = $userTicketModel -> where(array(
							'user_order_id' => $order['id'],
							'status' => Constant::WECHAT_USER_TICKET_ORDER_LOCKED,
							))
							->field('id')
							->select();
						if(!empty($tickets)){
							foreach ($tickets as $key => $value) {
								$userTicketModel -> where(array('id' => $value['id'])) -> save(array('status' => Constant::WECHAT_USER_TICKET_USED));
							}
						}
					}
					if($order['type'] == Constant::B2C_ORDER_TYPE_COMBINE){
						$userTicketModel -> where(array('order_id' => $order['id'], 'status' => Constant::WECHAT_USER_TICKET_LOCKED)) -> save(array('status' => Constant::WECHAT_USER_TICKET_NORMAL));
					}
					// 2. 修改微信支付日志
					$payLogModel->where(array(
						'openid' => $data['openid'], 
						'out_trade_no' => $data['out_trade_no'],
					))->save(array(
						'transaction_id' => $data['transaction_id'],
						'status' => 1,
						'pay_price' => $data['total_fee'],
						'pay_time' => time(),
						'notify_data' => json_encode($data),
					));
				}catch(\Exception $e){
					Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
					record_error($e);
				}
				// 新订单提醒
				$type = $order['type'] == 0 ? 0 : 1;
				$wechatUserMsgData = M('b2c_order_detail') -> where(array('order_id' => $order['id'])) -> field('goods_name, goods_num') -> select();
				$address = M('wechat_user_address') -> where(array('id' => $order['address_id'])) -> field('detail, name, tel') -> find();
				$this->_sendWechatUserOrderSuccessTemplateMsg($wechat, $data['openid'], $wechatUserMsgData, $order, $address, $order['id'], $type);
				// 水工消息提醒
				$this->_sendStationDeliverNewOrderTemplateMsg($wechat, $wechatUserMsgData, $order, $address, $order['id']);
				
			}
		}
	}

	/**
	 * 《我是水工 姓名 电话》水工绑定
	 * @param  [type] $openid   [description]
	 * @param  [type] $blankArr [description]
	 * @return [type]           [description]
	 */
	private function _bindDeliver($openid, $blankArr){
		$deliverModel = M('station_deliver');
		$where = array(
			'config_id' => $this->_wechat->getConfigId(),
			'openid' => $openid,
		);
		$deliverCount = intval($deliverModel->where($where)->count());
		if($deliverCount > 0){
			return '您已经是水工了，不能重复绑定。';
		}else{
			return '<a href="'.HOSTNAME.'A/deliver-bind.html#config_id='.$this->_wechat->getConfigId().'&openid='.$openid.'">跳转水工绑定页面，进行绑定</a>';
		}
	}

	/**
	 * 《我是老板 》老板绑定
	 * @param  [type] $openid   [description]
	 * @param  [type] $blankArr [description]
	 * @return [type]           [description]
	 */
	private function _bindBossDeliver($openid, $blankArr){
		$deliverModel = M('boss');
		$where = array(
			'config_id' => $this->_wechat->getConfigId(),
			'openid' => $openid,
		);
		$deliverCount = intval($deliverModel->where($where)->count());

		$userModel = M('wechat_user');
		$wheres = array(
			'config_id' => $this->_wechat->getConfigId(),
			'openid' => $openid,
			'is_boss' =>1,
		);
		$userCount = intval($userModel->where($wheres)->count());
		if($userCount > 0){
			return '您已经是老板了，不能重复绑定。';
		}elseif($deliverCount > 0){
			return '申请成为老板正在审核，不能重复绑定。';
		}else{
			return '<a href="'.HOSTNAME.'A/boss-bind.html#config_id='.$this->_wechat->getConfigId().'&openid='.$openid.'">跳转老板绑定页面，进行绑定</a>';
		}
	}

	/**
	 * 《查看销售统计 》
	 *
	 */
	private function _tongji($openid, $blankArr){
		$deliverModel = M('wechat_user');
		$where = array(
			'config_id' => $this->_wechat->getConfigId(),
			'openid' => $openid,
			'is_boss' =>1,
		);
		$deliverCount = intval($deliverModel->where($where)->count());
		if($deliverCount > 0){
			return '<a href="'.HOSTNAME.'A/boss-list.html#config_id='.$this->_wechat->getConfigId().'&openid='.$openid.'">跳转统计页面，进行查看</a>';
		}else{
			return '您暂时不是老板了，不能查看统计。';
		}
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
		$first = '您的订水订单已经提交到水站了！';
		if($type == 0){
			$goodsInfoStr = $goodsInfoArr[0]['goods_name'].' x '.$goodsInfoArr[0]['goods_num'];
			if(1 != count($goodsInfoArr)){
				$goodsInfoStr .= ', ...';
			}
		}else{
			$goodsInfoStr = $goodsInfoArr[0]['goods_name'].' X 1';
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
			$goodsInfoStr = $goodsInfoArr[0]['goods_name'].' x '.$goodsInfoArr[0]['goods_num'];
			if(1 != count($goodsInfoArr)){
				$goodsInfoStr .= ', ...';
			}
		}else{
			$goodsInfoStr = $goodsInfoArr[0]['goods_name'].' X 1';
		}
		$orderPrice = $orderInfoArr['order_price'].' 元';
		$addressStr = $addressInfoArr['name'].' '.$addressInfoArr['tel'];
		if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_CASH){
			$payType = '现金支付';
		}else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_WECHAT){
			$payType = '微信支付';
		}else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET){
			$payType = '水票支付';
		}
		$remark = '请及时处理（点击查看详情并抢单配送）';
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
    	$delivers = M('station_deliver')->where(array(
    		'config_id' => $configId,
    		'status' => 1,
    	))->select();

    	$prefix = $wechat->getWechatFunctionPrefix();
    	$file = 'deliver-order-detail.html';
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
}