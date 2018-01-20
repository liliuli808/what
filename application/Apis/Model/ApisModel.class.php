<?php
namespace Apis\Model;
use Think\Model;
class ApisModel extends Model{
	//自动验证
	protected $_validate = array(
			//array(验证字段,验证规则,错误提示,验证条件,附加规则,验证时间)
	);
	
	protected function _before_write(&$data) {
		parent::_before_write($data);
	}
}