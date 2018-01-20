# 增加异常信息表
CREATE TABLE `yds_online`.`yds_error_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `error_code` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '错误代码',
  `error_msg` CHAR(255) NOT NULL DEFAULT '' COMMENT '错误信息',
  `create_time` INT(10) NOT NULL DEFAULT 0 COMMENT '错误发生时间',
  `handler` CHAR(30) NOT NULL DEFAULT '' COMMENT '处理人',
  `handle_time` INT(10) NOT NULL DEFAULT 0 COMMENT '处理时间',
  `status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '错误状态：0:未处理，1:处理中，2:已解决',
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COMMENT = '易点水－错误信息表';

# 修改异常信息表error_code类型
ALTER TABLE `yds_online`.`yds_error_log` 
CHANGE COLUMN `error_code` `error_code` INT(11) NOT NULL DEFAULT '0' COMMENT '错误代码' ;

# 新增粉丝所属水站字段
ALTER TABLE `yds_online`.`yds_wechat_user` 
ADD COLUMN `station_id` INT(11) NOT NULL DEFAULT 0 AFTER `config_id`;

# 新增后台账号所属水站字段
ALTER TABLE `yds_online`.`yds_users` 
ADD COLUMN `station_id` INT(11) NOT NULL DEFAULT 0 AFTER `config_id`;

# 新增异常信息表file,line字段
ALTER TABLE `yds_online`.`yds_error_log`
ADD COLUMN `file`  char(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '错误所在文件' AFTER `status`,
ADD COLUMN `line`  smallint(5) NOT NULL DEFAULT 0 COMMENT '错误所在行' AFTER `file`;
