<?php
namespace Apis\Controller;
use Apis\Controller\ApiController;
use Common\Lib\Constant;
use Think\Log;
use Common\Lib\Tool;
class UserController extends ApiController{
	private $_userAddressModel;
	public function __construct(){
		parent::__construct();
		$this->_userAddressModel = M('wechat_user_address');
	}
	
	/**
	 * 获取用户收货地址列表
	 * @param [type] [description]
	 */
	
	public function getUserAddressList(){
		$configId = I('post.config_id', '0', 'intval');
		$openId = I('post.openid', '', 'trim');
		$address = $this->_userAddressModel->where(array(
			'config_id' => $configId,
			'openid' => $openId,
			'status' => array('not in', array(
				Constant::USER_ADDRESS_STATUS_DEL,
				)),
			))
			->field('id, alias, name, tel, pcd, detail, gps, status')
			->order('status desc,id desc')
			->select();
		//格式化信息
		foreach($address as &$vo){
			$vo['id'] = intval($vo['id']);
			$vo['alias'] = strval(trim($vo['alias']));
			$vo['name'] = strval(trim($vo['name']));
			$vo['tel'] = strval(trim($vo['tel']));
			$vo['pcd'] = strval(trim($vo['pcd']));
			$vo['detail'] = strval(trim($vo['detail']));
			if(mb_strlen($vo['detail'], 'utf8') > 10){
					$vo['detail_utf8_10'] = mb_substr($vo['detail'], 0, 10, 'utf8').'...';
				}else{
					$vo['detail_utf8_10'] = $vo['detail'];
				}
			$vo['gps'] = strval(trim($vo['gps']));
			$vo['status'] = intval($vo['status']);
		}
		unset($vo);
		$this->apiReturn(Constant::API_SUCCESS, $address, '获取用户收货地址列表成功');
	}
	/**
	 * 获取用户收货地址详情
	 * @param [type] [description]
	 */
	public function getUserAddressInfo(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$addressId = I('post.address_id', '0', 'intval');
        $arr = array();
		try{
			if($addressId <=0){
				throw new \Exception('Get user address param error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}
            $address = $this->_userAddressModel
					->where(array(
						'config_id' => $configId,
						'openid' => $openId,
						'id' => $addressId,
						'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
					))
					->field('id, alias, name, tel, pcd, detail, gps, status')
					->find();
			if(empty($address)){
				throw new \Exception('User address is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
			}

            $station=M('station')->where(array('config_id'=>$configId,'status'=>Constant::STATION_STATUS_ONSALE))->field('id as station_id, desc, range')->select();
		    if(empty($station)){
                throw new \Exception('Station is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
            }
        }catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(),$_POST,$e->getMessage());
		}


        //区水站的信息
        foreach($station as &$val){
            array_push($arr, array(
                'desc' => strval(trim($val['desc'])),
                'range' => json_decode($val['range']),
                'station_id' => intval($val['station_id']),
            ));
        }
        unset($val);
        //格式化信息
        $address['data'] = json_encode($arr);
        $address['id'] = intval($address['id']);
        $address['alias'] = strval(trim($address['alias']));
        $address['name'] = strval(trim($address['name']));
        $address['tel'] = strval(trim($address['tel']));
        $address['pcd'] = strval(trim($address['pcd']));
        $address['detail'] = strval(trim($address['detail']));
        if(mb_strlen($address['detail'], 'utf8') > 10){
            $address['detail_utf8_10'] = mb_substr($address['detail'], 0, 10, 'utf8').'...';
        }else{
            $address['detail_utf8_10'] = $address['detail'];
        }
        $address['gps'] = strval(trim($address['gps']));
        $address['status'] = intval($address['status']);
        $this->apiReturn(Constant::API_SUCCESS, $address, '获取用户收货地址详情成功');
	}


    /**
     * 获取水店详情
     * @param [type] [description]
     */
    public function getUserStationInfo(){
        $configId = intval(I('post.config_id'));
        $arr = array();
        try{
            $station=M('station')->where(array('config_id'=>$configId,'status'=>Constant::STATION_STATUS_ONSALE))->field('id as station_id, desc, range')->select();
            if(empty($station)){
                throw new \Exception('Station is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
            }
        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            record_error($e);
            $this->apiReturn($e->getCode(),$_POST,$e->getMessage());
        }

        //区水站的信息
        foreach($station as &$val){
            array_push($arr, array(
                'desc' => strval(trim($val['desc'])),
                'range' => json_decode($val['range']),
                'station_id' => intval($val['station_id']),
            ));
        }
//        foreach($station as &$val){
//
//                $val['desc'] = strval(trim($val['desc']));
//                $val['range'] = json_decode($val['range']);
//                $val['station_id'] = intval($val['station_id']);
//
//        }

        unset($val);
        //格式化信息
        $station['data'] = json_encode($arr);
        $this->apiReturn(Constant::API_SUCCESS, $station, '获取水店详情成功');
    }

	/**
	 * 添加用户收货地址
	 * @param [type] [description]
	 */
	public function addAddress(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$status = I('post.status', '0', 'intval');
		$phone = I('post.tel', '', 'trim');
		$name = I('post.name', '', 'trim');
		$pcd = I('post.pcd', '', 'trim');
		$detail = I('post.detail', '', 'trim');
		try{
			if(!in_array($status, array(
				Constant::USER_ADDRESS_STATUS_ONUSE,
				Constant::USER_ADDRESS_STATUS_DEFAULT,
				Constant::USER_ADDRESS_STATUS_DEL,
			))){
				throw new \Exception('Add address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}
			if(!empty($phone)){
				if(!Tool::checkPhone($phone))
					throw new \Exception('Add address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}else{
				throw new \Exception('Add address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}
			if(empty($name))
				throw new \Exception('Add address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			if(empty($pcd))
				throw new \Exception('Add address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		
		$data = array(
			'config_id' => $configId,
			'openid' => $openId,
			'alias' => I('post.alias', '', 'trim'),
			'name' => $name,
			'tel' => $phone,
			'pcd' => $pcd,
			'detail' => $detail,
			'gps' => I('post.gps', '', 'trim'),
            'station_id' => I('post.station_id', '', 'intval'),
			'status' => $status,
			);
		//判断如果用户无可用收货地址，默认为默认地址
		$oldAddress = $this->_userAddressModel -> where(array(
			'config_id' => $configId,
			'openid' => $openId,
			'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
			)) 
			-> getField('id');
		if(intval($oldAddress) <= 0){
			$data['status'] = 1;
		}
		$transModel = M();
		$transModel->startTrans();
		try{
			$data['create_time'] = time();
			$addressId = intval($this->_userAddressModel->add($data));
			if($addressId < 0){
				$transModel->rollback();
				throw new \Exception('Add address error', Constant::API_USER_ADDRESS_ADD_ERROR);
			}
			//如果设置状态为默认,修改其他状态
			if($status == 1){
				$setDefaultRes = $this->_userAddressModel->where(array(
					'config_id' => $configId,
					'openid' => $openId,
					'id' => array('not in', array(
						$addressId,
						)),
					'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
					))
					->save(array('status'=>0));
				if($setDefaultRes === false){
					$transModel->rollback();
					throw new \Exception('Save address error', Constant::API_USER_ADDRESS_SAVE_ERROR);
				}
			}
			$transModel->commit();
			$this->apiReturn(Constant::API_SUCCESS, array('address_id' => $addressId), '添加用户收货地址成功');
		}catch(\Exception $e){
			$transModel->rollback();
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}
	/**
	 * 保存用户收货地址
	 * @param [type] [description]
	 */
	public function saveAddress(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$addressId = I('post.address_id', '0', 'intval');
		// $status = I('post.status', '0', 'intval');
		$phone = I('post.tel', '', 'trim');
		$name = I('post.name', '', 'trim');
		$pcd = I('post.pcd', '', 'trim');
		$detail = I('post.detail', '', 'trim');
		try{
			//验证收货地址是否存在
			if(1 > $addressId){
				throw new \Exception('Save address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}else{
				$address = $this->_userAddressModel
					->where(array(
						'config_id' => $configId,
						'openid' => $openId,
						'id' => $addressId,
						'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
					))
					->find();
				if(0 == count($address)){
					throw new \Exception('User address is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
				}
			}
			// if(!in_array($status, array(
			// 	Constant::USER_ADDRESS_STATUS_ONUSE,
			// 	Constant::USER_ADDRESS_STATUS_DEFAULT,
			// 	Constant::USER_ADDRESS_STATUS_DEL,
			// ))){
			// 	throw new \Exception('保存用户收货地址参数错误', Constant::API_USER_ADDRESS_PARAM_ERROR);
			// }
			if(!empty($phone)){
				if(!Tool::checkPhone($phone))
					throw new \Exception('Save address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}else{
				throw new \Exception('Save address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}
			if(empty($name))
				throw new \Exception('Save address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			if(empty($pcd))
				throw new \Exception('Save address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		
		$data = array(
			'config_id' => $configId,
			'openid' => $openId,
			'alias' => I('post.alias', '', 'trim'),
			'name' => $name,
			'tel' => $phone,
			'pcd' => $pcd,
			'detail' => $detail,
			'gps' => I('post.gps', '', 'trim'),
            'station_id' => I('post.station_id', '', 'intval'),
			// 'status' => $status,
		);
		$transModel = M();
		$transModel->startTrans();
		try{
			$data['id'] = $addressId;
			if( $this->_userAddressModel->save($data) === false){
				$transModel->rollback();
				throw new \Exception('Save address error', Constant::API_USER_ADDRESS_SAVE_ERROR);
			}
			//如果设置状态为默认,修改其他状态
			// if($status == 1){
			// 	$setDefaultRes = $this->_userAddressModel->where(array(
			// 		'config_id' => $configId,
			// 		'openid' => $openId,
			// 		'id' => array('not in', array(
			// 			$addressId,
			// 			)),
			// 		'status' => array('not in', array(
			// 				Constant::USER_ADDRESS_STATUS_DEL,
			// 			)),
			// 		))
			// 	->save(array('status'=>0));
			// 	if($setDefaultRes === false){
			// 		$transModel->rollback();
			// 		throw new \Exception('Save address error', Constant::API_USER_ADDRESS_SAVE_ERROR);
			// 	}
			// }
			$transModel->commit();
			$this->apiReturn(Constant::API_SUCCESS, array('address_id' => $addressId), '保存用户收货地址成功');
		}catch(\Exception $e){
			$transModel -> rollback();
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}
	/**
	 * 设置默认收货地址
	 */
	public function setDefaultAddress(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$addressId = I('post.address_id', '0', 'intval');
		$transModel = M();
		$transModel->startTrans();
		try{
			if($addressId <=0){
				$transModel->rollback();
				throw new \Exception('User address is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
			}
			$address = $this->_userAddressModel
					->where(array(
						'config_id' => $configId,
						'openid' => $openId,
						'id' => $addressId,
						'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
					))
					->find();
			if(empty($address)){
				$transModel->rollback();
				throw new \Exception('User address is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
			}
			if($this->_userAddressModel->save(array('id' => $addressId, 'status'=>1)) !== false){
				$setDefaultRes = $this->_userAddressModel->where(array(
					'config_id' => $configId,
					'openid' => $openId,
					'id' => array('not in', array(
						$addressId,
						)),
					'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
					))
				->save(array('status'=>0));
				if($setDefaultRes !== false){
					$transModel->commit();
					$this->apiReturn(Constant::API_SUCCESS, array('address_id' => $addressId), '设置用户默认收货地址成功');
					die();
				}
			}
			$transModel->rollback();
			throw new \Exception('Set default address error', Constant::API_USER_ADDRESS_SET_SEFAULT_ERROR);
		}catch(\Exception $e){
			$transModel -> rollback();
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		
	}
	/**
	 * 删除收货地址
	 */
	public function deleteAddress(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$addressId = I('post.address_id', '0', 'intval');
		try{
			//验证收货地址是否存在
			if(1 > $addressId){
				throw new \Exception('Delete address error', Constant::API_USER_ADDRESS_PARAM_ERROR);
			}else{
				$address = $this->_userAddressModel
					->where(array(
						'config_id' => $configId,
						'openid' => $openId,
						'id' => $addressId,
						'status' => array('not in', array(
							Constant::USER_ADDRESS_STATUS_DEL,
						)),
					))
					->find();
				if(0 == count($address)){
					throw new \Exception('User address is not exists', Constant::API_USER_ADDRESS_NOT_EXIST);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		try{
			if( !$this->_userAddressModel -> where(array('id' => $addressId)) -> save(array('status' => Constant::USER_ADDRESS_STATUS_DEL)) ){
				throw new \Exception('Delete address error', Constant::API_USER_ADDRESS_DELETE_ERROR);
			}
			$this->apiReturn(Constant::API_SUCCESS, array('address_id' => $addressId), '删除用户收货地址成功');
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}
	/**
	 * 获取用户默认收货地址
	 */
	public function getDefaultAddress(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$address = M('wechat_user_address') -> where(array(
			'config_id' => $configId,
			'openid' => $openId,
			'status' => Constant::USER_ADDRESS_STATUS_DEFAULT,
			))
			->field('id, alias, name, tel, pcd, detail, gps')
			->find();
		if(!empty($address)){
			$address['id'] = intval($address['id']);
			$address['alias'] = strval(trim($address['alias']));
			$address['name'] = strval(trim($address['name']));
			$address['tel'] = strval(trim($address['tel']));
			$address['pcd'] = strval(trim($address['pcd']));
			$address['detail'] = strval(trim($address['detail']));
			if(mb_strlen($address['detail'], 'utf8') > 10){
					$address['detail_utf8_10'] = mb_substr($address['detail'], 0, 10, 'utf8').'...';
				}else{
					$address['detail_utf8_10'] = $address['detail'];
				}
			$address['gps'] = strval(trim($address['gps']));
		}else{
			$address = array();
		}
		$this->apiReturn(Constant::API_SUCCESS, $address, '获取用户默认收货地址成功');
	}
	/**
	 * 我的水票列表
	 */
	public function getUserTickets($type = 0){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$ticketModel = M('wechat_user_ticket');
        //用户购买的水票的信息
		$tickets = $ticketModel -> where(array(
			'config_id' => $configId,
			'openid' => $openId,
			'status' => array('in',array(
				Constant::WECHAT_USER_TICKET_NORMAL,
				Constant::WECHAT_USER_TICKET_USED,
				Constant::WECHAT_USER_TICKET_ORDER_LOCKED,
				)),
			))
			-> field('goods_id, strategy_id, goods_name, goods_img, count(id) as ticket_num, start_time, end_time, name, price, condition, createtime')
			-> group('strategy_id')
			-> order('createtime asc')
			-> select();
		if(!empty($tickets)){
			foreach ($tickets as $key => &$value) {
				$num = $ticketModel -> where(array('strategy_id' => $value['strategy_id'], 'status' => Constant::WECHAT_USER_TICKET_NORMAL, 'openid' => $openId,'end_time' => array('gt',time())))-> field("count(id) as left_num, end_time") -> find();
				$value['left_num'] = $num['left_num'];  //查询用户购买的水票还没有使用的个数
                $value['end_time'] = $num['end_time'];  //查询用户购买的水票时间没有到期的时间
				if($value['end_time'] < time()){
					$value['status']=intval(3);
				}
			}
			unset($value);
		}
		
		foreach ($tickets as $key => &$ticket) {
			$ticket['status'] = intval(0);

			if($ticket['statuss'] ==3){
				$ticket['status'] = intval(3); //水票过期了
			}
			if($ticket['left_num'] == 0){
				$ticket['status'] = intval(4); //水票用完了
			}
			$ticket['start_time'] = strval(date('Y-m-d H:i:s', $ticket['start_time']));
			$ticket['end_time'] = strval(date('Y-m-d H:i:s', $ticket['end_time']));
			if(!empty($ticket['condition'])){
				$ticket['only'] = intval(json_decode($ticket['condition'], true)['only']); //是不是通用的
				$use_num = explode(',', json_decode($ticket['condition'], true)['use_num']); //使用的条件
				if(in_array('lt', $use_num)){
					$ticket['use_num'] = ($use_num[1]-1) >= 0 ? '0,'.($use_num[1]-1) : '0,0'; //小于
				}else if(in_array('elt', $use_num)){
					$ticket['use_num'] = '0,'.$use_num[1];      //小于等于
				}else if(in_array('gt', $use_num)){
					$ticket['use_num'] = ($use_num[1]+1).',∞';    //大于
				}else if(in_array('egt', $use_num)){
					$ticket['use_num'] = $use_num[1].',∞';    //大于等于
				}else{
					$ticket['use_num'] = '0,∞';
				}
			}else{
				$ticket['only'] = intval(0);
				$ticket['use_num'] = '0,∞';
			}
			$ticket['use_num'] = strval($ticket['use_num']);   //水票使用的范围
			$ticket['goods_id'] = intval($ticket['goods_id']);  //水票所属的商品id
			$ticket['ticket_num'] = intval($ticket['ticket_num']);  //这个用户一共有多少的水票
			$ticket['left_num'] = intval($ticket['left_num']);       //这个用户一共有多少可以使用的水票
			//$ticket['use_num'] = $ticket['ticket_num'] - $ticket['left_num'];
			$ticket['goods_name'] = strval(trim($ticket['goods_name']));   //商品的名称
			if(mb_strlen($ticket['goods_name'], 'utf8') > 12){
					$ticket['goods_name_utf8_10'] = mb_substr($ticket['goods_name'], 0, 12, 'utf8').'...';
				}else{
					$ticket['goods_name_utf8_10'] = $ticket['goods_name'];
				}
			$ticket['goods_img'] = strval(trim($ticket['goods_img']));  //水票的图片
			$ticket['strategy_id'] = intval($ticket['strategy_id']);    //所属的水票的id
			$ticket['name'] = strval(trim($ticket['name']));          //水票的名称
			$ticket['createtime'] = intval($ticket['createtime']);    //创建的时间
			if(mb_strlen($ticket['name'], 'utf8') > 12){
				$ticket['name_utf8_10'] = mb_substr($ticket['name'], 0, 12, 'utf8').'...';
			}else{
				$ticket['name_utf8_10'] = $ticket['name'];
			}
			$ticket['price'] = sprintf('%.2f', $ticket['price']);    //水票的价格
			unset($ticket['condition']);
		}
		unset($ticket);
		// 重新排序，可用在上，不可用在下
		foreach ($tickets as $key => $value) {
			$param1[$key] = $value['status'];
			$param2[$key] = $value['createtime'];
		}
		array_multisort($param1, SORT_ASC, $param2, SORT_DESC, $tickets);
		// 用户水票(详细)
		if($type == 1){
			return $tickets;
			exit();
		}
		$this->apiReturn(Constant::API_SUCCESS, $tickets, '获取用户水票列表成功');
	}
	/**
	 * 我的水桶
	 */
	public function getUserBucket(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$orderModel = M('b2c_order');
		$bucketOrderNormal = $orderModel 
			-> where(array(
				'type' => Constant::B2C_ORDER_TYPE_NORMAL,
				'openid' => $openId,
				'order_status' => array('in', array(
					Constant::B2C_ORDER_STATUS_FINISHED,
					))
				)) 
			-> field('sum(bucket) as bucket_num') 
			-> find();
		$bucketOrderBucket = $orderModel 
			-> where(array(
				'type' => Constant::B2C_ORDER_TYPE_BUCKET,
				'openid' => $openId,
				'order_status' => array('in', array(
					Constant::B2C_ORDER_STATUS_CREATED,
					Constant::B2C_ORDER_STATUS_STATION_ACCEPT,
					Constant::B2C_ORDER_STATUS_DELIVERING,
					Constant::B2C_ORDER_STATUS_FINISHED,
					))
				)) 
			-> field('sum(bucket) as bucket_num') 
			-> find();
		$bucketOrder['bucket_num'] = $bucketOrderNormal['bucket_num'] - $bucketOrderBucket['bucket_num'];
		//更新用户水桶信息
		try{
			if(M('wechat_user') -> where(array('openid' => $openId)) -> save(array('bucket' => intval($bucketOrder['bucket_num']))) === false){
				throw new \Exception('Get user bucket info error', Constant::API_USER_GET_BUCKET_ERROR);
			}
			$bucketOrder['bucket_num'] = intval($bucketOrder['bucket_num']);
			$bucketOrder['bucket_price'] = Constant::B2C_ORDER_BUCKET_PRICE;
			$this->apiReturn(Constant::API_SUCCESS, $bucketOrder, '获取我的水桶成功');
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			record_error($e);
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
	}
	/**
	 *  获取用户信息
	 */
	public function getUserInfo(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$userModel = M('wechat_user');
		try{
			$user = $userModel -> where(array('openid' => $openId)) -> field('nickname, headimgurl') -> find();
			if(empty($user)){
				throw new \Exception('Get user info error', Constant::API_USER_ERROR);
			}
			$user['nickname'] = strval(trim(base64_decode($user['nickname'])));
			$user['headimgurl'] = strval(trim($user['headimgurl']));
			$user['integral'] = intval(0);
			$this->apiReturn(Constant::API_SUCCESS, $user, '获取用户信息成功');
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		
	}
	/**
	 *  上报用户地理位置
	 */
	public function saveUserGps(){
		$configId = intval(I('post.config_id'));
		$openId = I('post.openid', '', 'trim');
		$address = I('post.address', '', 'trim');
		$gps = I('post.gps', '', 'trim');
		try{
			if(strlen($address) <= 0){
				throw new \Exception('用户地理微信信息错误', Constant::API_FAILED);
			}
			if(strlen($gps) <= 0){
				throw new \Exception('用户地理微信信息错误', Constant::API_FAILED);
			}
			$userGps = M('wechat_user_point') -> where(array(
				'config_id' => $configId,
				'openid' => $openId,
				'address' => $address,
				))
				-> field('id')
				-> find();
			if(empty($userGps)){
				$userGpsData = array(
					'config_id' => $configId,
					'openid' => $openId,
					'address' => $address,
					'gps' => $gps,
					);
				$userGpsId = M('wechat_user_point') -> add($userGpsData);
				$userGpsId = intval($userGpsId);
				if($userGpsId < 0){
					throw new \Exception('Add user wechat address error', Constant::API_ADD_USER_POINT_ERROR);
				}
				$this->apiReturn(Constant::API_SUCCESS, array('id'=>$userGpsId), '添加用户地理微信信息成功');
			}else{
				$this->apiReturn(Constant::API_SUCCESS, array('id'=>intval($userGps['id'])), '添加用户地理微信信息成功');
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		
	}
}
