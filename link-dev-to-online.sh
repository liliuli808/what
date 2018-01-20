#!/bin/bash
web_path=$(cd `dirname $0`; pwd)
web_online_path=$web_path/online

# [开发模式]系统数据库连接配置文件：/data/conf/db.php
dev_db_conf_path=$web_path/data/conf/db.php
# [线上模式]系统数据库连接配置文件：/online/data/conf/db.php
online_db_conf_path=$web_online_path/data/conf/db.php
# [开发模式]系统公共模块配置文件：/application/Common/Conf/config.php
dev_common_config_path=$web_path/application/Common/Conf/config.php
# [线上模式]系统公共模块配置文件：/online/application/Common/Conf/config.php
online_common_config_path=$web_online_path/application/Common/Conf/config.php
# [开发模式]系统公共模块配置文件：/crontab/python/conf/db.conf
dev_python_db_config_path=$web_path/crontab/python/conf/db.conf
# [线上模式]系统公共模块配置文件：/online/crontab/python/conf/db.conf
online_python_db_config_path=$web_online_path/crontab/python/conf/db.conf
# 询问是否执行
echo -n -e "\033[31mDo you want to override? \n$dev_db_conf_path \n$dev_common_config_path \n$dev_python_db_config_path \nyes or no?  \033[0m"
while true; do
    read answer
    if [ "$answer" == "no" ]; then
        exit 4
    elif [ "$answer" == "yes" ]; then
        break
    fi
    echo -n "Enter yes or no: "
done

# 删除[开发模式]下的配置文件
echo -e "\033[31mrm $dev_db_conf_path ... \033[0m"
$(`rm $dev_db_conf_path`)
echo -e '\033[31mrm '$dev_common_config_path' ...\033[0m'
$(`rm $dev_common_config_path`)
echo -e '\033[31mrm '$dev_python_db_config_path' ...\033[0m'
$(`rm $dev_python_db_config_path`)

# 复制[线上模式]下的配置文件到指定位置
echo -e '\033[31mcp '$online_db_conf_path' '$dev_db_conf_path' ...\033[0m'
$(`cp $online_db_conf_path $dev_db_conf_path`)
echo -e '\033[31mcp '$online_common_config_path' '$dev_common_config_path' ...\033[0m'
$(`cp $online_common_config_path $dev_common_config_path`)
echo -e '\033[31mcp '$online_python_db_config_path' '$dev_python_db_config_path' ...\033[0m'
$(`cp $online_python_db_config_path $dev_python_db_config_path`)




