<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
class DistributionController extends AdminbaseController{
	protected $_distributionModel;
	
	public function _initialize() {
		parent::_initialize();
		$this->_distributionModel = D("Distribution");
	}
	/**
	 * [C_getDistributionList 获取所有分销列表]
	 */
	public function C_getDistributionList(){
		$configId = 0;
		$keyword = '';
		$level = 0;
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}else{
			$configId = intval($_SESSION['CONFIG_ID']);
		}
		if(IS_POST){
			if($this->_isAdmin){
				if(intval(I('post.config')) != -1) $configId = intval(I('post.config'));
			}
			$keyword = trim(I('post.keyword'));
			$level = intval(I('post.level'));
		}
		$data = $this->_distributionModel->getDistributionList($keyword, $level, $configId);
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = $wechatConfigModel -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$this->assign('data', $data);
		$this->assign('where', array('config_id'=>$configId, 'keyword'=>$keyword, 'level'=>$level));
		$this->display();
	}
	/**
	 * [C_getDistributionList 获取用户分销列表]
	 */
	public function C_getUserDistributionList(){
		$keyword = '';
		$level = 0;
		$openid = '';
		if(IS_POST){
			$keyword = trim(I('post.keyword'));
			$level = intval(I('post.level'));
			$openid = trim(I('post.openid'));
		}
		if(IS_GET){
			$openid = trim(I('get.openid'));
			if(strlen($openid) <=0) $this->error('访问错误');
		}
		$data = $this->_distributionModel->getUserDistributionList($keyword, $level, $openid);
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = M('wechat_config') -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$this->assign('data', $data);
		$this->assign('where', array('keyword'=>$keyword, 'level'=>$level, 'openid'=>$openid));
		$this->display();
	}

	//添加分销规则
	public function C_addDistribution(){
		
		if($_SESSION['CONFIG_ID'] != 0) $where['config_id'] = $_SESSION['CONFIG_ID'];
		$arr = M('distribution') -> where($where) -> find();
		$this -> assign('arr', $arr);
		$this -> assign('config_id', $_SESSION['CONFIG_ID']);
		$this -> display();
	}

	//添加
	public function C_editDistributionPost(){
		$number = I('post.number', '', 'trim');
		$configId = I('post.config_id', 0, 'intval');
		$arr = M('distribution')->where('config_id='.$configId)->find();
		if(0 == count($arr)){
			$data['config_id'] = $configId;
			$data['number'] = $number;
			$str = M('distribution')->add($data);
		}else{
			$str = M('distribution')->where('config_id='.$configId)->save(array('number'=>$number));
		}

		if($str === false){
			$this->error('系统错误，更新分销信息信息失败！');
		}else{
			$this->success('更新分销信息成功！');
		}

	}
}