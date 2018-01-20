<?php
namespace Apis\Controller;
use Apis\Controller\Api;
use Common\Lib\Constant;
class CarouselController extends ApiController{
	/**
	 * [B2C商城|B2B商城] 获取轮播图
	 * @return [type] [description]
	 */
	public function getCarousel(){
		$configId = intval(I('post.config_id'));
		$type = I('post.type', 0, 'intval');
		//验证订单支付状态
		if(!in_array($type, array(
			Constant::CAROUSEL_TYPE_B2C,
			Constant::CAROUSEL_TYPE_B2B,
		))){
			$this->apiReturn(Constant::API_CAROUSEL_PARAM_ERROR, $_POST, '轮播图请求参数错误');
		}
		$carousel = M('carousel')->where(array(
			'config_id' => $configId,
			'type' => $type,
			'status' => Constant::CAROUSEL_STATUS_ON,
			))
			->field('name, url, img')
			->order('list_order asc')
			->limit(Constant::CAROUSEL_SHOW_LIMIT)
			->select();
		if(!empty($carousel)){
			foreach ($carousel as $key => &$value) {
				$value['name'] = strval(trim($value['name']));
				$value['url'] = strval(trim($value['url']));
				$value['img'] = strval(trim($value['img']));
			}
			unset($value);
		}
		$this->apiReturn(Constant::API_SUCCESS, $carousel, '获取轮播图列表成功');
	}
}