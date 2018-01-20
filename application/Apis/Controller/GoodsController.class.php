<?php
namespace Apis\Controller;
use Apis\Controller\Api;
use Common\Lib\Constant;
use Apis\Model\goodsModel;
use Think\Log;
class GoodsController extends ApiController{
	private $_goodsModel;
	public function __construct(){
		parent::__construct();
		$this->_goodsModel = D('Goods');
	}
	/**
	 * [B2C商城] 获取推荐商品列表
	 * @return [type] [description]
	 */
	public function getRecommendGoods(){
		$configId = intval(I('post.config_id'));
		$isPage = I('post.is_page', 0, 'intval');
		if($isPage == 1){
			$page = I('post.page', 1, 'intval');
			$pageSize = I('post.pagesize', 10, 'intval');
		}
		
		$goodsParams = M('goods')->alias('g')
			->field('g.id as goods_id, g.cate_id, g.name, g.img, g.price, g.standard, g.unit, g.sales, gc.name as cate_name')
			->join(C('DB_PREFIX').'goods_cate as gc on gc.id = g.cate_id')
			->where(array(
				'g.config_id' => $configId,
				'g.status' => Constant::GOODS_STATUS_ONSALE,
				'g.is_recommended' => Constant::GOODS_IS_RECOMMENDED,
			))
			->order('g.list_order asc');
		if($isPage == 1){
			$goodsParams->limit(($page-1)*$pageSize, $pageSize);
		}
		$goods = $goodsParams->select();
		// 单独定价
		// 查询用户是否存在单独定价
		$user_goods = M('user_goods') -> where(array(
			'config_id' => $configId,
			'openid' => trim(I('post.openid')), 
			))
			-> select();
		if(count($user_goods) > 0){
			foreach ($goods as $key => &$value) {
				foreach ($user_goods as $k => $v) {
					if($value['goods_id'] == $v['goods_id']){
						$value['price'] = $v['goods_price'];
						break;
					}
				}
			}
			unset($value);
		}
		$goods = $this->_goodsModel->formatGoodsData($goods);
		//拼装分页信息
		$goods = array('goods'=> $goods, 'isPage' => $isPage);
		if($isPage == 1){
			$goods['page'] = $page;
			$goods['pageSize'] = $pageSize;
		}
		$this->apiReturn(Constant::API_SUCCESS, $goods, '获取推荐商品列表成功');
	}

	
	/**
	 * [B2C商城] 获取商品列表
	 * @param  [type] [description]
	 * @return [type] [description]
	 */
	public function getGoodsList(){

		$configId = intval(I('post.config_id'));
		$sortByCate = I('post.sort_by_cate', 1, 'intval');
		// $isPage = I('post.is_page', 0, 'intval');
		// if($isPage == 1){
		// 	$page = I('post.page', 1, 'intval');
		// 	$pageSize = I('post.pagesize', 10, 'intval');
		// }

		$where = array(
				'config_id' => $configId,
				'status' => Constant::GOODS_STATUS_ONSALE,
			);
		$goodsParams = M('goods')
			->field('id as goods_id, cate_id, name, img, price, standard, unit, sales')
			->where($where)
			->limit(Constant::GOODS_LIMIT)
			->order('list_order asc');
		$goodscate = M('goods_cate')
			->field('id,name')
			->where(array(
				'config_id' => $configId,
				'status' => Constant::GOODS_CATE_STATUS_ON,
				))
			->order('list_order asc')
			->select();
		// if($isPage == 1){
		// 	$goodsParams->limit(($page-1)*$pageSize, $pageSize);
		// }
		$goods = $goodsParams->select();
		// 单独定价
		// 查询用户是否存在单独定价
		$user_goods = M('user_goods') -> where(array(
			'config_id' => $configId,
			'openid' => trim(I('post.openid')), 
			))
			-> select();
		if(count($user_goods) > 0){
			foreach ($goods as $key => &$value) {
				foreach ($user_goods as $k => $v) {
					if($value['goods_id'] == $v['goods_id']){
						$value['price'] = $v['goods_price'];
						break;
					}
				}
			}
			unset($value);
		}
		//是否按分类进行分组，1:是，0:否  (break或者先循环goods unset($goods[$key]))
		if($sortByCate == 1){
			$goodss = array();
			foreach($goodscate as $key => &$v1){
				$goodss[$v1['id']]['cate_id'] = $v1['id'];
				$goodss[$v1['id']]['cate_name'] = $v1['name'];
				$goodss[$v1['id']]['cate_goods'] = array();
				foreach($goods as $key1 => &$v2){
					if(101 > count($goodss[$v1['id']]['cate_goods'])){
						if($v1['id'] == $v2['cate_id']){
							$v2 = $this->_goodsModel->formatGoodsDataSingle($v2);
							$goodss[$v1['id']]['cate_goods'][] = $v2;
						}
					}else{
						break;
					}
				}
			}
			unset($v1);
			$goods = array_values($goodss);
		}else{
			$goods = $this->_goodsModel->formatGoodsData($goods);
		}
		
		$newGoods = array('goods' => $goods, 'sort_by_cate' => $sortByCate);
		// if($isPage == 1){
		// 	$newGoods['page'] = $page;
		// 	$newGoods['pagesize'] = $pageSize;
		// }
		$this->apiReturn(Constant::API_SUCCESS, $newGoods, '获取商品列表成功');
	}
	/**
	 * [B2C商城] 获取商品分类列表
	 * @param  [type] [description]
	 * @return [type] [description]
	 */
	public function getGoodsCateList(){
		$configId = intval(I('post.config_id'));
		$where = array(
				'config_id' => $configId,
				'status' => Constant::GOODS_CATE_STATUS_ON,
			);
		$goodsCate = M('goods_cate')
			->field('id, name, pid')
			->where($where)
			->order('list_order asc')
			->select();
		if(empty($goodsCate)){
			$this->apiReturn(Constant::API_GOODS_CATE_IS_NOT_EXIST, array(), '商品分类不存在');
		}
		//重装无限极分类
		$goodsCate = $this->_goodsModel->getNewCateList($goodsCate, 0);
		$this->apiReturn(Constant::API_SUCCESS, $goodsCate, '获取商品分类列表成功');
	}
	
	/**
	 * [B2C商城] 获取商品详情 
	 * @param  [type] [description]
	 * @return [type] [description]
	 */
	public function getGoodsDetail(){
		$configId = intval(I('post.config_id'));
		$goodsId = I('post.id', 0, 'intval');
		if($goodsId <= 0){
			$this->apiReturn(Constant::API_GOODS_IS_NOT_EXIST, array(), '商品不存在');
		}
		$where = array(
				'config_id' => $configId,
				'status' => Constant::GOODS_STATUS_ONSALE,
				'id' => $goodsId,
			);
		$goods = M('goods')
			->field('id as goods_id, cate_id, name, img, album, price, standard, unit, sales, name as cate_name, desc')
			->where($where)
			->find();
		if(empty($goods)){
			$this->apiReturn(Constant::API_GOODS_IS_NOT_EXIST, array(), '商品不存在');
		}
		// 单独定价
		// 查询用户是否存在单独定价
		$user_goods = M('user_goods') -> where(array(
			'config_id' => $configId,
			'openid' => trim(I('post.openid')), 
			'goods_id' => $goods['goods_id'],
			))
			-> find();
		if(count($user_goods) > 0){
			$goods['price'] = $user_goods['goods_price'];
		}
		$goods['desc'] = htmlspecialchars_decode($goods['desc']);
		$goods['desc'] = str_replace('standard/data/upload', 'data/upload', $goods['desc']);
		$goods = $this->_goodsModel->formatGoodsDataSingle($goods);
		$goods['album'] =json_decode($goods['album'],true);
		foreach($goods['album'] as &$vo){
			$vo['url'] = strval(trim($vo['url']));
			$vo['alt'] = strval(trim($vo['alt']));
		}
		unset($vo);
		$this->apiReturn(Constant::API_SUCCESS, $goods, '获取商品详情成功');
	}
	/**
	 * 获取水票商品列表
	 * @return [type] [description]
	 */
	public function getTicketGoodsList(){
		$configId = intval(I('post.config_id'));

		$ticketgoods = M('goods') -> where(array('config_id' => $configId)) ->field('id, name, price, img') -> order('list_order asc') -> select();
		if(!empty($ticketgoods)){
			foreach ($ticketgoods as $key => &$value) {
				$value['is_active'] = intval(0);
				// $goodsStrategy = M('goods_strategy') -> where("type = ". Constant::GOODS_STRATEGY_TYPE_TICKET. " and status = ". Constant::GOODS_STRATEGY_TYPE_ONSALE. " and config_id = ". $configId. " and goods_id = ". $value['id']) -> field('id, name, price, start_time, end_time, condition') -> select();

				$goodsStrategy = M('goods_strategy') -> where(array(
					'type' => Constant::GOODS_STRATEGY_TYPE_TICKET,
					'status' => Constant::GOODS_STRATEGY_TYPE_ONSALE,
					'config_id' => $configId,
					'goods_id' => $value['id'],
					))
					-> field('id, name, price, start_time, end_time, condition')
					-> select();
				if(!empty($goodsStrategy)){
					foreach ($goodsStrategy as $ko =>&$vo) {
						//获取水票详细
						//判断过期
						if($vo['start_time'] > time() || $vo['end_time'] < time()){
							unset($goodsStrategy[$ko]);
							continue;
						}
						$ticketStrategy = M('goods_strategy_detail') -> where(array('sid' => $vo['id'])) -> field('num, is_give, givenum') -> find();
						$vo['ticket_id'] = intval($vo['id']);
						$vo['ticket_name'] = strval(trim($vo['name']));
						if(mb_strlen($vo['ticket_name'], 'utf8') > 12){
							$vo['ticket_name_utf8_10'] = mb_substr($vo['ticket_name'], 0, 12, 'utf8').'...';
						}else{
							$vo['ticket_name_utf8_10'] = $vo['ticket_name'];
						}
						$vo['ticket_price'] = sprintf('%.2f', $vo['price']);
						if(!empty($ticketStrategy)){
							$vo['num'] = intval($ticketStrategy['num']);
							$vo['is_give'] = intval($ticketStrategy['is_give']);
							$vo['givenum'] = intval($ticketStrategy['givenum']);
						}
						if($value['is_active'] != 1){
							if($vo['start_time'] > 0 && ($vo['start_time'] <= time() && $vo['end_time'] >= time())){
								$value['is_active'] = intval(1);
							}
						}
						$vo['start_time'] = strval(date('Y-m-d H:i:s', $vo['start_time']));
						$vo['end_time'] = strval(date('Y-m-d H:i:s', $vo['end_time']));
						if(!empty($vo['condition'])){
							$vo['only'] = intval(json_decode($vo['condition'], true)['only']);
							$use_num = explode(',', json_decode($vo['condition'], true)['use_num']);
							if(in_array('lt', $use_num)){
								$vo['use_num'] = ($use_num[1]-1) >= 0 ? '0,'.($use_num[1]-1) : '0,0';
							}else if(in_array('elt', $use_num)){
								$vo['use_num'] = '0,'.$use_num[1];
							}else if(in_array('gt', $use_num)){
								$vo['use_num'] = ($use_num[1]+1).',∞';
							}else if(in_array('egt', $use_num)){
								$vo['use_num'] = $use_num[1].',∞';
							}else{
								$vo['use_num'] = '0,∞';
							}
							$vo['use_num'] = strval($vo['use_num']);
						}else{
							$vo['only'] = intval(0);
							$vo['use_num'] = '0,∞';
						}
						unset($vo['condition']);
						unset($vo['id']);
						unset($vo['name']);
						unset($vo['price']);
					}
					unset($vo);
					$goodsStrategy = array_values($goodsStrategy);
					$value['ticket'] = $goodsStrategy;
				}else{
					unset($ticketgoods[$key]);
				}
			}
			unset($value);
		}
		if(!empty($ticketgoods)){
			foreach ($ticketgoods as $key => &$value) {
				$value['id'] = intval($value['id']);
				$value['name'] = strval(trim($value['name']));
				if(mb_strlen($value['name'], 'utf8') > 12){
					$value['name_utf8_10'] = mb_substr($value['name'], 0, 12, 'utf8').'...';
				}else{
					$value['name_utf8_10'] = $value['name'];
				}
				$value['price'] = sprintf('%.2f', $value['price']);
				$value['img'] = strval(trim($value['img']));
			}
			unset($value);
		}
		$this->apiReturn(Constant::API_SUCCESS, $ticketgoods, '获取水票商品列表成功');
	}
	/**
	 * 获取制定水票商品所有购买策略
	 * @return [type] [description]
	 */
	public function getTicketGoodsDetail(){
		$configId = intval(I('post.config_id'));
		$goodsId = I('post.goods_id', 0, 'intval');
		try{
			if($goodsId <= 0){
				throw new \Exception('Get ticket detail error', Constant::API_TICKET_DETAIL_PARAM_ERROR);
			}
			$goods = M('goods') -> where(array('id' => $goodsId, 'config_id' => $configId, 'status' => Constant::GOODS_STATUS_ONSALE, 'is_allowticket' => Constant::GOODS_TICKET_ALLOW)) -> field('id, name, price, img, album') -> find();
			if(empty($goods)){
				throw new \Exception('No ticket for goods', Constant::API_TICKET_NOT_EXIST);
			}
			if(!empty($goods)){
				$goodsStrategy = M('goods_strategy') -> where("type = ".Constant::GOODS_STRATEGY_TYPE_TICKET." and status = ".Constant::GOODS_STRATEGY_TYPE_ONSALE." and config_id = ".$configId." and goods_id = ".$goods['id']." and start_time < ".time()." and end_time > ".time()) -> field('id, name, price, condition, start_time, end_time') -> order('list_order asc') -> select();
				if(!empty($goodsStrategy)){
					foreach ($goodsStrategy as &$vo) {
						//获取水票详细
						$ticketStrategy = M('goods_strategy_detail') -> where(array('sid' => $vo['id'])) -> field('num, is_give, givenum') -> find();
						$vo['ticket_id'] = intval($vo['id']);
						$vo['ticket_name'] = strval(trim($vo['name']));
						if(mb_strlen($vo['ticket_name'], 'utf8') > 12){
							$vo['ticket_name_utf8_10'] = mb_substr($vo['ticket_name'], 0, 12, 'utf8').'...';
						}else{
							$vo['ticket_name_utf8_10'] = $vo['ticket_name'];
						}
						$vo['ticket_price'] = sprintf('%.2f', $vo['price']);
						if(!empty($ticketStrategy)){
							$vo['num'] = intval($ticketStrategy['num']);
							$vo['is_give'] = intval($ticketStrategy['is_give']);
							$vo['givenum'] = intval($ticketStrategy['givenum']);
						}
						if($vo['start_time'] < time() && $vo['end_time'] > time()){
							$vo['is_active'] = intval(1);
						}else{
							$vo['is_active'] = intval(0);
						}
						$vo['start_time'] = strval(date('Y-m-d H:i:s', $vo['start_time']));
						$vo['end_time'] = strval(date('Y-m-d H:i:s', $vo['end_time']));
						if(!empty($vo['condition'])){
							$vo['only'] = intval(json_decode($vo['condition'], true)['only']);
							$use_num = explode(',', json_decode($vo['condition'], true)['use_num']);
							if(in_array('lt', $use_num)){
								$vo['use_num'] = ($use_num[1]-1) >= 0 ? '0,'.($use_num[1]-1) : '0,0';
							}else if(in_array('elt', $use_num)){
								$vo['use_num'] = '0,'.$use_num[1];
							}else if(in_array('gt', $use_num)){
								$vo['use_num'] = ($use_num[1]+1).',∞';
							}else if(in_array('egt', $use_num)){
								$vo['use_num'] = $use_num[1].',∞';
							}else{
								$vo['use_num'] = '0,∞';
							}
							$vo['use_num'] = strval($vo['use_num']);
						}else{
							$vo['only'] = intval(0);
							$vo['use_num'] = strval('0,∞');
						}
						unset($vo['condition']);
						unset($vo['id']);
						unset($vo['name']);
						unset($vo['price']);
					}
					unset($vo);
					$goods['ticket'] = $goodsStrategy;
				}else{
					throw new \Exception('No ticket for goods', Constant::API_TICKET_NOT_EXIST);
				}
			}
		}catch(\Exception $e){
			Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
			$this->apiReturn($e->getCode(), $_POST, $e->getMessage());
		}
		$goods['id'] = intval($goods['id']);
		$goods['name'] = strval(trim($goods['name']));
		if(mb_strlen($goods['name'], 'utf8') > 12){
			$goods['name_utf8_10'] = mb_substr($goods['name'], 0, 12, 'utf8').'...';
		}else{
			$goods['name_utf8_10'] = $goods['name'];
		}
		$goods['price'] = sprintf('%.2f', $goods['price']);
		$goods['img'] = strval(trim($goods['img']));
		$goods['album'] = json_decode($goods['album'], true);
		array_unshift($goods['album'], array('url' => $goods['img'], 'alt' => '商品图片'));
		foreach ($goods['album'] as $key => &$value) {
			$value['url'] = strval($value['url']);
			$value['alt'] = strval($value['alt']);
		}
		unset($value);
		$this->apiReturn(Constant::API_SUCCESS, $goods, '获取水票商品详情成功');
	}
	/**
	 *  获取套餐商品列表
	 * @return [type] [description]
	 */
	public function getCombineGoodsList(){
		$configId = intval(I('post.config_id'));
		$goodsStrategy =  M('goods_strategy') -> where("type = ".Constant::GOODS_STRATEGY_TYPE_PACKAGE. " and status = ". Constant::GOODS_STRATEGY_TYPE_ONSALE. " and config_id = ". $configId) -> field('id, name, price') -> select();
		if(!empty($goodsStrategy)){
			foreach ($goodsStrategy as $key => &$value) {
				$package = M('goods_strategy_detail') -> where(array(
					'sid' => $value['id'],
					))
				 	->field('goods_id, num, type, goods_name, goods_img, goods_price')
				 	->select();
				foreach ($package as &$vo) {
					if($vo['type'] == 1){
						$detailStrategyDetail = M('goods_strategy_detail') -> where(array('sid' => $vo['goods_id'])) -> find(); 
						$vo['goods_img'] = strval(trim($detailStrategyDetail['goods_img']));
						$vo['num'] = intval($vo['num']) * (intval($detailStrategyDetail['num']) + intval($detailStrategyDetail['givenum']));
						$vo['goods_name'] = strval(trim($detailStrategyDetail['goods_name']));
						if(mb_strlen($vo['goods_name'], 'utf8') > 12){
								$vo['goods_name_utf8_10'] = mb_substr($vo['goods_name'], 0, 12, 'utf8'). '...';
							}else{
								$vo['goods_name_utf8_10'] = $vo['goods_name'];
							}
					}else{
						// $goods = M('goods') -> where(array('id' => $vo['goods_id'], 'config_id' => $configId)) -> field('name, img') -> find();
						// if(!empty($goods)){
							$vo['goods_name'] = strval(trim($vo['goods_name']));
							if(mb_strlen($vo['goods_name'], 'utf8') > 12){
								$vo['goods_name_utf8_10'] = mb_substr($vo['goods_name'], 0, 12, 'utf8'). '...';
							}else{
								$vo['goods_name_utf8_10'] = $vo['goods_name'];
							}
							$vo['goods_img'] = strval(trim($vo['goods_img']));
							$vo['num'] = intval($vo['num']);
						// }
					}
					unset($vo['goods_id']);
					unset($vo['type']);
				}
				unset($vo);
				$value['package'] = $package;
			}
			unset($value);
		}
		if(!empty($goodsStrategy)){
			foreach ($goodsStrategy as $key => &$value) {
				$value['id'] = intval($value['id']);
				$value['name'] = strval(trim($value['name']));
				if(mb_strlen($value['name'], 'utf8') > 12){
					$value['name_utf8_10'] = mb_substr($value['name'], 0, 12, 'utf8'). '...';
				}else{
					$value['name_utf8_10'] = $value['name'];
				}
				$value['price'] = sprintf('%.2f', $value['price']);
			}
			unset($value);
		}
		
		$this->apiReturn(Constant::API_SUCCESS, $goodsStrategy, '获取套餐列表成功');
	}
}