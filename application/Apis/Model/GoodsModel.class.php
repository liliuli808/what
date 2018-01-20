<?php
namespace Apis\Model;
use Apis\Model\ApisModel;
class GoodsModel extends ApisModel{
	//自动验证
	protected $_validate = array(
			//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
			//array('name', 'require', '！', 1, 'regex', 3),
	);
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
	protected $tableName = 'goods';

	/**
	 * 格式化商品信息
	 * @param  $goods [description]
	 */
	public function formatGoodsData($goods){
		foreach ($goods as &$good) {
			if(isset($good['goods_id'])){
				$good['goods_id'] = intval($good['goods_id']);
			}
			if(isset($good['cate_id'])){
				$good['cate_id'] = intval($good['cate_id']);
			}
			if(isset($good['name'])){
				$good['name'] = strval(trim($good['name']));
				if(mb_strlen($good['name'], 'utf8') > 12){
					$good['name_utf8_10'] = mb_substr($good['name'], 0, 12, 'utf8').'...';
				}else{
					$good['name_utf8_10'] = $good['name'];
				}
			}
			if(isset($good['img'])){
				$good['img'] = strval(trim($good['img']));
			}
			if(isset($good['price'])){
				$good['price'] = sprintf('%.2f', $good['price']);
			}
			if(isset($good['standard'])){
				$good['standard'] = strval(trim($good['standard']));
			}
			if(isset($good['unit'])){
				$good['unit'] = strval(trim($good['unit']));
			}
			if(isset($good['sales'])){
				$good['sales'] = intval($good['sales']);
			}
			if(isset($good['cate_name'])){
				$good['cate_name'] = strval(trim($good['cate_name']));
				if(mb_strlen($good['cate_name'], 'utf8') > 10){
					$good['cate_name_utf8_10'] = mb_substr($good['cate_name'], 0, 10, 'utf8').'...';
				}else{
					$good['cate_name_utf8_10'] = $good['cate_name'];
				}
			}
		}
		return $goods;
	}
	/**
	 * 格式化商品信息
	 * @param  $goods [description]
	 */
	public function formatGoodsDataSingle($goods){
			if(isset($goods['goods_id'])){
				$goods['goods_id'] = intval($goods['goods_id']);
			}
			if(isset($goods['cate_id'])){
				$goods['cate_id'] = intval($goods['cate_id']);
			}
			if(isset($goods['name'])){
				$goods['name'] = strval(trim($goods['name']));
				if(mb_strlen($goods['name'], 'utf8') > 12){
					$goods['name_utf8_10'] = mb_substr($goods['name'], 0, 12, 'utf8').'...';
				}else{
					$goods['name_utf8_10'] = $goods['name'];
				}
			}
			if(isset($goods['img'])){
				$goods['img'] = strval(trim($goods['img']));
			}
			if(isset($goods['price'])){
				$goods['price'] = sprintf('%.2f', $goods['price']);
			}
			if(isset($goods['standard'])){
				$goods['standard'] = strval(trim($goods['standard']));
			}
			if(isset($goods['unit'])){
				$goods['unit'] = strval(trim($goods['unit']));
			}
			if(isset($goods['sales'])){
				$goods['sales'] = intval($goods['sales']);
			}
			if(isset($goods['cate_name'])){
				$goods['cate_name'] = strval(trim($goods['cate_name']));
				if(mb_strlen($goods['cate_name'], 'utf8') > 10){
					$goods['cate_name_utf8_10'] = mb_substr($goods['cate_name'], 0, 10, 'utf8').'...';
				}else{
					$goods['cate_name_utf8_10'] = $goods['cate_name'];
				}
			}
		
		return $goods;
	}
	//无限极分类+格式化
	public function getNewCateList($cate, $pid){
		$arr = array();
		foreach($cate as &$vo){
			if($vo['pid'] == $pid){
				//格式化
				if(isset($vo['id'])){
					$vo['id'] = intval($vo['id']);
				}
				if(isset($vo['pid'])){
					$vo['pid'] = intval($vo['pid']);
				}
				if(isset($vo['name'])){
					$vo['name'] = strval(trim($vo['name']));
					if(mb_strlen($vo['name'], 'utf8') > 10){
						$vo['name_utf8_10'] = mb_substr($vo['name'], 0, 10, 'utf8').'...';
					}else{
						$vo['name_utf8_10'] = $vo['name'];
					}
				}
				$next = $this->getNewCateList($cate, $vo['id']);
				if(!empty($next)){
					$vo['child'] = $next;
				}else{
					$vo['child'] = array();
				}
				
				
				$arr[] = $vo;
			}
			unset($vo);
		}
		return $arr;
	}
}