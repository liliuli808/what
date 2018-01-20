<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class DeliverController extends AdminbaseController{

	public function C_deliverList(){
		if($this->_isAdmin){
			$wechatConfigModel = M('wechat_config');
			$wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
			$this->assign('wechatconfig', $wechatConfig);
		}
		$where = '1=1 and status <> 2';
		if(IS_POST){
			
			if(intval(I('post.status')) != -1){
				$where .= ' and status='.intval(I('post.status'));
			}
			$keyword = trim(I('post.keyword'));
			if(0 != strlen($keyword)) $where .= ' and (name like "%'.$keyword.'%" or tel like "%'.$keyword.'%")';
			if(!$this->_isAdmin){
 				$where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
			}else{
				if(intval(I('post.config')) != -1) $where .= ' and config_id = '.intval(I('post.config'));
			}
			$this -> assign('where', $where);
 		}else{
            if($_SESSION['CONFIG_ID'] != 0){
                $where .= ' and config_id = '.intval($_SESSION['CONFIG_ID']);
            }
        }

 		$deliverModel = M('station_deliver');
		$count = $deliverModel
			->where($where)
			->count();

		$page = $this->page($count, $this->perpage);
		$data['show'] = $page->show('Admin');
		$data['data'] = $deliverModel
			->where($where)
			->limit($page->firstRow, $page->listRows)
			->order('id desc')
			->select();
		// 加入微信服务号名称
		if($this->_isAdmin){
			foreach ($data['data'] as $key => &$value) {
				$value['wechat_name'] = M('wechat_config') -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
			}
			unset($value);
		}
		$this->assign('data', $data);
		$this->display();
	}
	public function C_editDeliver(){
		$id = I('get.did', 0, 'intval');
		if($id <= 0) $this->error('访问错误');

		$where = array('id' => $id, 'status' => array('lt', '2'));
		if($_SESSION['CONFIG_ID'] != 0) $where['config_id'] = $_SESSION['CONFIG_ID'];
		$deliver = M('station_deliver') -> where($where) -> find();
		if(empty($deliver)) $this->error('访问的水工不存在');

		$this -> assign('deliver', $deliver);
		$this -> display();
	}
	public function C_editDeliverPost(){
		$name = I('post.name', '', 'trim');
		if(0 == strlen($name)){
			$this->error('水工姓名不能为空！');
		}
		$tel = I('post.tel', 0);
		if(empty($tel)) $this->error('水工电话不能为空！');
		if(!preg_match("/^1[34578]{1}\d{9}$/",strval($tel))) $this->error('水工电话格式不正确！');
		$configId = I('post.config_id', 0, 'intval');
		$deliverId = I('post.id', 0, 'intval');
		$deliverData = array(
			'id' => $deliverId,
			'config_id' => $configId,
			'name' => $name,
			'tel' => $tel,
		);

		
		if(M('station_deliver')->save($deliverData) === false){
			$this->error('系统错误，更新水工信息失败！');
		}else{
			$this->success('更新水工信息成功！');
		}
	}

	/**
	 * 禁用水工
	 * @return string json
	 */	
	public function C_closeDeliver(){
		$deliverModel = M('station_deliver');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>0));
		if(false !== $saveDeliver){
			$this->success('禁用水工成功！');
		}else{
			$this->error('禁用水工失败！');
		}
	}

	/**
	 * 启用水工
	 * @return string json
	 */	
	public function C_openDeliver(){
		$deliverModel = M('station_deliver');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>1));
		if(false !== $saveDeliver){
			$this->success('启用水工成功！');
		}else{
			$this->error('启用水工失败！');
		}
	}

	/**
	 * 删除水工
	 * @return string json
	 */	
	public function C_deleteDeliver(){
		$deliverModel = M('station_deliver');
		if($this->_isAdmin){
			$where = array('id'=>intval(I('get.cid')));
		}else{
			$where = array('id'=>intval(I('get.cid')), 'config_id'=>$_SESSION['CONFIG_ID']);
		}
		$saveDeliver = $deliverModel->where($where)->save(array('status'=>2));
		if(false !== $saveDeliver){
			$this->success('删除水工成功！');
		}else{
			$this->error('删除水工失败！');
		}
	}
}