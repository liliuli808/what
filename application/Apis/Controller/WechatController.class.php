<?php
namespace Apis\Controller;
use Apis\Controller\ApiController;
use Common\Lib\Constant;
use Think\Log;
use Common\Lib\Wechat;
use Apis\Controller\UserController;
class WechatController extends ApiController{

	public function getWechatPayJsParameters(){
		if($_SERVER['SERVER_ADDR'] == '::1'){
        	$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		}
		$configId = I('post.config_id', 0, 'intval');
		$openid = I('post.openid', '', 'trim');
		$orderId = I('post.order_id', 0, 'intval');
		try{
			if(1 > $orderId){
				throw new \Exception('获取微信支付参数失败！1', Constant::API_FAILED);
			}else{
				$order = M('b2c_order')->where(array('id'=>$orderId, 'openid'=>$openid))->find();
				if(0 == count($order)){
					throw new \Exception('获取微信支付参数失败！2', Constant::API_FAILED);
				}
			}
			$user = M('wechat_user')->where(array('openid'=>$openid))->find();
			if(0 == count($user)){
				throw new \Exception('获取微信支付参数失败！3', Constant::API_FAILED);
			}
			// 更新订单状态
			if($order['order_status'] != Constant::B2C_ORDER_STATUS_WAITING){
				throw new \Exception('获取微信支付参数失败！4', Constant::API_FAILED);
			}
//			$order['order_price'] = 0.01;
			$outTradeNo = $this->_getWechatPayOutTradeNo($order['order_sn']);
			$wechat = new Wechat($configId);
			$payParam = $wechat->getJsApiParameters($outTradeNo, $openid, intval(floatval($order['order_price']) * 100));
		}catch(\Exception $e){
			$exceptionCode = $e->getCode() == 0 ? -1 : $e->getCode();
			Log::write('['.$exceptionCode.'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($exceptionCode, $_POST, $e->getMessage());
		}
		M('wechat_pay_log')->add(array(
			'wechat_user_id' => $user['id'],
			'openid' => $openid,
			'order_id' => $order['id'],
			'out_trade_no' => $outTradeNo,
			'transaction_id' => '',
			'price' => intval($order['order_price'] * 100),
			'create_time' => time(),
			'status' => 0,
		));
		$this->apiReturn(Constant::API_SUCCESS, $payParam, '获取微信支付参数成功');
	}

	/**
	 * 获取微信用户水票列表(详细)
	 */
	public function getOrderAvalibleTicketList(){
		$userController = new UserController();
		$userTickets = $userController -> getUserTickets(1);
		// 获取订单商品ids
		$goods = json_decode($_POST['goods'], true);
		try{
			if(!is_array($goods) || 0 == count($goods)){
				throw new \Exception('GET user ticket error', Constant::API_GET_USER_TICKETS_PARAM_ERROR);
			}else{
				foreach ($goods as $value) {
					$goodsId = intval($value['goods_id']);
					if(1 > $goodsId){
						throw new \Exception('GET user ticket error', Constant::API_GET_USER_TICKETS_PARAM_ERROR);
					}
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		// 0 可用 1 不满足起送数量 2 不匹配 3 过期 4 已用完
		foreach ($userTickets as $key => &$value) {
			$value['status'] = intval(2);
  			if($value['left_num'] <= 0){
				$value['status'] = intval(4);
				break;
			}
			if(strtotime($value['end_time']) < time()){
				$value['status'] = intval(3);
				break;
			}
			foreach ($goods as $key => $good) {
				if($value['goods_id'] == $good['goods_id']){
					
					$use_num = explode(',', $value['use_num']);
					$first = $use_num[0];
					$second = $use_num[1] == '∞' ? 9999999 : $use_num[1];
					if($first > 0 || $second > 0){
						if($good['goods_num'] > $second || $good['goods_num'] < $first){
							$value['status'] = intval(1);
							break;
						}
					}
					$value['status'] = intval(0);
				}
			}
		}
		unset($value);
		// 重新排序，可用在上，不可用在下
		foreach ($userTickets as $key => $value) {
			$param1[$key] = $value['status'];
			$param2[$key] = $value['createtime'];
		}
		array_multisort($param1, SORT_ASC,$param2, SORT_DESC, $userTickets);
		$this->apiReturn(Constant::API_SUCCESS, $userTickets, '获取用户水票列表成功');
	}
	private function _getWechatPayOutTradeNo($orderSn){
		return $orderSn.array_pop(explode('.', $this->getNowMicrotime()));
	}
}