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
import urllib
import urllib2
import json

ROOT_PATH = '{0}/'.format(os.path.abspath(os.path.dirname(__file__)))
HXSJ_CONFIG_ID = 2
TEMPLATE_ID = 'fx25OjgifAk6ChFn9Q7kFiUNNTyhujPynUjwhs4Z-g0'
DEVELOPER = [
    # 汇信世嘉－郭军周
    'oClftwolEmpUaQ2eA-EakfvSmCE4',
    # 汇信世嘉－汪炳旭
    'oClftwpauoNVQwWxfDI53Nxo9WLk',
    # 汇信世嘉－李岩
    # 'oClftwg7tJtEWspy60Ifk6duQ-zM',
]

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

def get_wechat_access_token(cursor, conn):
    now = time.mktime(datetime.datetime.now().timetuple())
    field = 'token'
    where = 'config_id = {0} and expire_time > {1}'.format(HXSJ_CONFIG_ID, now)
    order_and_limit = 'order by id desc limit 1'
    sql = 'select {0} from yds_wechat_access_token where {1} {2}'.format(field, where, order_and_limit)
    print 'Hxsj Access Token Sql: {0}'.format(sql)
    cursor.execute(sql)
    access_token = cursor.fetchall()
    if(len(access_token) != 0):
        print access_token[0][0]
        return access_token[0][0]
    else:
        print 'No Access Token Found. exit()'
        exit()

def get_yds_error(cursor, conn, delta=1):
    field = 'id, error_code, error_msg, file, line, create_time'
    where = 'status = 0'
    sql = 'select {field} from yds_error_log where {where}'.format(field=field, where=where)
    print 'Yds Error Sql: {0}'.format(sql)
    cursor.execute(sql)
    errors = cursor.fetchall()
    if(len(errors) != 0):
        print '{0} Errors Found, Sending Wechat Warning Msg...'.format(len(errors))
        return errors
    else:
        print 'No Error Found. exit()'
        exit()

def send_wechat_error_warning_msg(cursor, conn, errors):
    token = get_wechat_access_token(cursor, conn)
    url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={0}'.format(token)
    for error in errors:
        for openid in DEVELOPER:
            data = {
                'touser' : openid,
                'template_id' : TEMPLATE_ID,
                'url' : '',
                'topcolor' : '#0D9CCC',
                'data' : {
                    'first' : {
                        'value' : '系统产生了一个异常',
                        'color' : '#888888',
                    },
                    'keyword1' : {
                        'value' : error[1],
                        'color' : '#888888',
                    },
                    'keyword2' : {
                        'value' : error[2],
                        'color' : '#888888',
                    },
                    'keyword3' : {
                        'value' : '{0}[{1}]'.format(error[3], error[4]),
                        'color' : '#888888',
                    },
                    'keyword4' : {
                        'value' : time.strftime("%Y-%m-%d %H:%M:%S", time.localtime(error[5])),
                        'color' : '#888888',
                    },
                    'remark' : {
                        'value' : '\n请及时处理...\n',
                        'color' : '#888888',
                    },
                },
            }
            print data
            send_res = http_post(url, data)

def http_post(url, data):
    data  = json.dumps(data)
    request = urllib2.Request(url=url, data=data)
    result = urllib2.urlopen(request)
    result = result.read()
    result = json.loads(result)
    print result
    if result['errcode'] == 0 and result['errmsg'] == 'ok':
        return True
    else:
        return False

if __name__ == '__main__':
    print time.strftime('%Y-%m-%d %H:%M:%S',time.localtime(time.time()))
    db_conf_file = '{0}conf/db.conf'.format(ROOT_PATH)
    mysql_connect, mysql_cursor = get_mysql_connect(db_conf_file, 'yds_debug')
    errors = get_yds_error(mysql_cursor, mysql_connect)
    send_wechat_error_warning_msg(mysql_cursor, mysql_connect, errors);
