<?php
namespace Apis\Controller;
use Think\Controller;
use Common\Lib\Wechat;
use Think\Log;
use Common\Lib\Constant;

class ApiController extends Controller{
	protected $_apiRequestInfo = array();
	protected $_apiRequestLogId = 0;

	public function __construct(){
		parent::__construct();
		C('SHOW_ERROR_MSG', false);
        C('SHOW_PAGE_TRACE', false);

        $this->_apiRequestInfo = array(
        	'config_id' => intval($_POST['config_id']),
        	'openid' => trim($_POST['openid']),
        	'module_name' => CONTROLLER_NAME,
        	'action_name' => ACTION_NAME,
        	'code' => -1,
        	'msg' => '',
        	'request_time' => date('Y-m-d H:i:s'),
        	'request' => json_encode($_POST),
        	'return' => '',
        	'request_microtime' => $this->getNowMicrotime(),
        	'return_microtime' => '0.00',
        	'use_microtime' => '0.00',
        	'ip' => $this->getRequestIp(),
        );
        $this->_apiRequestLogId = M('api_request_log')->add($this->_apiRequestInfo);

         try{
         	$this->_checkApiSign();
         }catch(\Exception $e){
         	Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
		    $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
         }
	}

	/**
	 * 监测签名
	 * config_id=2&openid=abcdef&timestamp=1496204993000
	 * @return [type] [description]
	 */
	protected function _checkApiSign(){
		if(!isset($_POST['config_id']) || 1 > intval($_POST['config_id'])){
			throw new \Exception('No validate config_id', Constant::API_SIGN_ERROR);
		}
		if(!isset($_POST['timestamp']) || 1 > intval($_POST['timestamp'])){
			throw new \Exception('No validate timestamp', Constant::API_SIGN_ERROR);
		}
		if(!isset($_POST['openid']) || 0 == strlen(trim($_POST['openid']))){
			throw new \Exception('No validate openid', Constant::API_SIGN_ERROR);
		}
		if(!isset($_POST['sign']) || 0 == strlen(trim($_POST['sign']))){
			throw new \Exception('No validate sign', Constant::API_SIGN_ERROR);
		}
		$signItems = array(
			'config_id='.intval($_POST['config_id']),
			'openid='.trim($_POST['openid']),
			'timestamp='.intval($_POST['timestamp']),
		);
		$signStr = implode('&', $signItems);
		if(trim($_POST['sign']) != md5($signStr)){
			throw new \Exception('Sign check error', Constant::API_SIGN_ERROR);
		}
	}

	/**
	 * 获取指定configID的微信服务号帮助类
	 * @param  [type] $configId [description]
	 * @return [type]           [description]
	 */
	protected function getWechatObject($configId){
		try{
			$wechat = new Wechat($configId);
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), array(), $e->getMessage());
		}
		return $wechat;
	}

	/**
	 * 接口返回
	 * @param  integer $code [description]
	 * @param  array   $data [description]
	 * @param  string  $msg  [description]
	 * @return [type]        [description]
	 */
	protected function apiReturn($code = 0, $data = array(), $msg = ''){
		$apiRtn = array(
			'code' => intval($code),
			'msg' => is_string($msg) ? $msg : json_encode($msg),
			'data' => is_array($data) ? $data : array(0 => $data),
		);
		$returnMicrotime = $this->getNowMicrotime();
		$apiRequestData = array(
			'code' => $apiRtn['code'],
			'msg' => substr($apiRtn['msg'], 0, 128),
			'return' => json_encode($apiRtn),
			'return_microtime' => $returnMicrotime,
			'use_microtime' => $returnMicrotime - $this->_apiRequestInfo['request_microtime']
		);
		$model = M('api_request_log');
		$model->where(array('id' => $this->_apiRequestLogId))->save($apiRequestData);
		echo json_encode($apiRtn);
		exit();
	}

	protected function getNowMicrotime(){
		list($micro, $timestamp) = explode(' ', microtime());
		return sprintf('%.6f', $micro+$timestamp);
	}

	protected function getRequestIp(){
		$ip = ($_SERVER["HTTP_VIA"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
		return ($ip) ? $ip : $_SERVER["REMOTE_ADDR"];
	}

	/**
	 * 创建订单号
	 * @param  [type] $configId [description]
	 * @return [type]           [description]
	 */
	protected function createB2COrderSn($configId){
		list($micro, $timestamp) = explode(' ', microtime());
		$orderSnExt = array_pop(explode('.', sprintf('%.4f', $micro)));
		return 'b2c'.$configId.date('ymdH').$orderSnExt;
	}
}