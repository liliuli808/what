# 用户分销字段添加
ALTER TABLE `yds_wechat_user`
ADD COLUMN `deeps`  int(10) NOT NULL DEFAULT 1 COMMENT '用户分销深度' AFTER `station_id`,
ADD COLUMN `path`  char(255) CHARACTER SET utf8 NOT NULL DEFAULT '0-' COMMENT '用户分销路径' AFTER `deeps`,
ADD COLUMN `qr_code`  char(255) CHARACTER SET utf8 NOT NULL DEFAULT '' COMMENT '分销二维码' AFTER `path`;
# 添加联合唯一索引
ALTER TABLE `yds_wechat_user`
ADD UNIQUE INDEX `deep_path_index` (`deeps`, `path`) USING BTREE ;