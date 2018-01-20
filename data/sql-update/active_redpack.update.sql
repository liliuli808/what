#组合商品表添加活动时间，使用条件字段
ALTER TABLE `yds_goods_strategy`
ADD COLUMN `start_time`  int(10) NOT NULL DEFAULT 0 COMMENT '活动开始时间' AFTER `list_order`,
ADD COLUMN `end_time`  int(10) NOT NULL DEFAULT 2000000000 COMMENT '活动结束时间' AFTER `start_time`,
ADD COLUMN `condition`  char(128) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '活动中水票使用条件' AFTER `end_time`;

#用户水票表添加活动，使用条件字段
ALTER TABLE `yds_wechat_user_ticket`
ADD COLUMN `start_time`  int(10) NOT NULL DEFAULT 0 COMMENT '活动开始时间' AFTER `user_order_id`,
ADD COLUMN `end_time`  int(10) NOT NULL DEFAULT 2000000000 COMMENT '活动结束时间' AFTER `start_time`,
ADD COLUMN `condition`  char(128) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '水票使用条件' AFTER `end_time`;