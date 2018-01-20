<?php
namespace Apis\Controller;
use Think\Log;

class AuthController extends ApiController{

	public function __construct(){
		parent::__construct();
	}

	// /**
	//  * 生成系统级参数，及引导用户微信授权
	//  * @return [type] [description]
	//  */
	// public function auth(){
	// 	if(isset($_GET['code']) && 0 != strlen(trim(I('get.code')))){
	// 		$configId = intval(I('get.config_id'));
	// 		$authId = trim(strval(I('get.auth_id')));
 //        	$wechat = $this->getWechatObject($configId);
 //        	try{
 //        		$openid = $wechat->wechatUserAuthorizeCodeToAccessToken();
 //        	}catch(\Exception $e){
 //        		Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
	// 			$this->apiReturn($e->getCode(), array(), $e->getMessage());
 //        	}
 //        	$saveAuthLog = M('api_auth_log')->where(array('config_id'=>$configId, 'auth_id'=>$authId))->save(array('openid'=>$openid, 'auth_time'=>$this->getNowMicrotime()));
 //        	if(1 > $saveAuthLog){
 //        		$this->apiReturn(self::API_SAVE_AUTH_LOG_OPENID_ERROR, array(), 'Save auth log openid error');
 //        	}else{
 //        		$auth = M('api_auth_log')->where(array('config_id'=>$configId, 'auth_id'=>$authId))->find();
 //        		$this->apiReturn(self::API_SUCCESS, $auth, 'Auth success');
 //        	}
 //        }else{
	// 		$configId = intval(I('post.config_id'));
	// 		$authId = trim(strval(I('post.auth_id')));
	// 		$authLogId = M('api_auth_log')->add(array(
	// 			'config_id' => $configId,
	// 			'auth_id' => $authId,
	// 			'openid' => '',
	// 			'create_time' => $this->getNowMicrotime(),
	// 			'auth_time' => 0,
	// 		));
	// 		if(1 > intval($authLogId)){
	// 			$this->apiReturn(self::API_SAVE_AUTH_LOG_ERROR, array(), 'Save auth log error');
	// 		}else{
	// 			$wechat = $this->getWechatObject($configId);
	//         	$url = 'http://'.$_SERVER['SERVER_NAME'].U('Apis/Auth/auth', array('config_id'=>$configId, 'auth_id'=>$authId));
	// 			$url = $wechat->getWechatUserAuthorize($url);
	// 			$this->apiReturn(self::API_SUCCESS, $url, 'Send wechat authorize request success');
	// 		}
 //        }
	// }

	/**
	 * 测试接口
	 * @return [type] [description]
	 */
	public function demo(){
		$rtn = array(
			'return' => '['.date('Y-m-d H:i:s').'] Request success and return with this msg.',
			'request' => $_POST,
		);
		$this->apiReturn(0, $rtn, '请求成功');
	}
}