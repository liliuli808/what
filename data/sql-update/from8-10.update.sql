# 新增用户单独定价表
CREATE TABLE `yds_user_goods` (
`id`  int(11) UNSIGNED NOT NULL AUTO_INCREMENT ,
`config_id`  int(11) NOT NULL DEFAULT 0 ,
`openid`  char(128) CHARACTER SET utf8 NOT NULL DEFAULT '' ,
`goods_id`  int(11) NOT NULL DEFAULT 0 COMMENT '用户对应另价商品' ,
`goods_price`  decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '商品新价格' ,
`old_goods_price`  decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '商品原价' ,
`create_time`  int(10) NOT NULL DEFAULT 0 COMMENT '创建时间' ,
PRIMARY KEY (`id`),
INDEX `index` (`config_id`, `openid`, `goods_id`) USING BTREE 
)ENGINE=InnoDB DEFAULT CHARSET=utf8;

# 更新用户表，添加老板字段
ALTER TABLE `yds_wechat_user`
ADD COLUMN `is_boss`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '0非老板1老板' AFTER `bucket`;

# 更新订单表，添加订单是否分派字段
ALTER TABLE `yds_b2c_order`
ADD COLUMN `deliver_type`  tinyint(1) NOT NULL DEFAULT 0 COMMENT '0未派单1已派单' AFTER `type`;



