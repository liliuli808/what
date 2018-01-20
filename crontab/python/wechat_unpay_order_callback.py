#!/usr/bin/python
#-*-coding:utf-8-*-
"""
    对每天07:00-07:10之间的天气推送任务进行监控
"""
__author__ = 'v_guojunzhou'
import os
import sys
import datetime
import time
from lib import mysql

ROOT_PATH = '{0}/'.format(os.path.abspath(os.path.dirname(__file__)))
CALLBACK_TIME = 1800

def get_mysql_connect(conf_file, db_conf):
    """
        获取数据库连接
    """
    try:
        mysql_db = mysql.MySqlDB()
        mysql_db.init(conf_file)
        conn = mysql_db.fetch_dbhandler(db_conf)
        cursor = conn.cursor()
        return conn, cursor
    except Exception as e:
        print 'Get Mysql Connection Error'
        exit()

def callback_wechat_unpay_b2c_order(cursor, conn, delta=1):
    print time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))
    now = time.mktime(datetime.datetime.now().timetuple())
    start_time = now - 86400 * delta
    field = 'id, order_sn'
    where = 'order_status = 6 and pay_type = 1 and pay_status = 0 and create_time <= {0}'.format(now-CALLBACK_TIME)
    sql = 'select {field} from yds_b2c_order where {where}'.format(field=field, where=where)
    print 'Select Wechat Pay Overtime Orders: {0}'.format(sql)
    cursor.execute(sql)
    orders = cursor.fetchall()
    if(len(orders) != 0):
        for order in orders:
            update_sql = 'update yds_b2c_order set order_status = 5, pay_status = 2'\
            ' where id = {0}'.format(order[0])
            try:
                cursor.execute(update_sql)
                conn.commit()
                print 'Callback Order: {0}\t{1}, State: Success'.format(order[0], order[1])
            except:  
                print 'Callback Order: {0}\t{1}, State: Failed'.format(order[0], order[1])
                conn.rollback() 
    else:
        print 'No Order Need To Be Callback'

if __name__ == '__main__':
    db_conf_file = '{0}conf/db.conf'.format(ROOT_PATH)
    mysql_connect, mysql_cursor = get_mysql_connect(db_conf_file, 'yds_debug')
    callback_wechat_unpay_b2c_order(mysql_cursor, mysql_connect)
