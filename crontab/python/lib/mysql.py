#!/usr/bin/env python
# -*- coding:utf8 -*-
# time: 2014/10/27 16:20
# mail: lvleibing01@baidu.com
# author: lvleibing01
# desc:

import random
import configobj
import pymysql


class Singleton(type):

    def __call__(cls, *args, **kwargs):

        if '_instance' not in vars(cls):
            cls._instance = super(Singleton, cls).__call__(*args, **kwargs)
        else:
            def skip_init(self, *args, **kwargs):
                pass
            cls.__init__ = skip_init

        return cls._instance


class AbstractDB(object):
    """Abstract db module
    """

    __metaclass__ = Singleton

    def __init__(self):

        self.dbhandler_dict = {}

        pass

    def init(self, *args, **kwargs):

        return True

    def __del__(self):

        pass

    def exit(self):

        return True


class MySqlDB(AbstractDB):
    """MySQL database collection frame
    """

    def __init__(self):

        AbstractDB.__init__(self)

    def init(self, config_path):

        AbstractDB.init(self, config_path)

        self.read_config(config_path)

        return True

    def __del__(self):

        AbstractDB.__del__(self)

        for conn in self.dbhandler_dict:
            self.push_back_dbhandler(conn)

    def exit(self):

        AbstractDB.exit(self)

        return True

    def read_config(self, config_path):
        """
        """

        self.config = configobj.ConfigObj(config_path)
        self.config = self.config['MySqlDB']

        return True

    def init_conn(self, conn):

        conn.set_character_set('utf8')
        conn.autocommit(True)

        return True

    def connect(self, db_cluster_key):
        """
        """

        conn = None
        if db_cluster_key not in self.config:
            return conn

        db_cluster_config = self.config[db_cluster_key]
        db_cluster_config_section = random.sample(db_cluster_config.sections, 1)[0]
        db_config = db_cluster_config[db_cluster_config_section]
        db_config['port'] = int(db_config['port'])
        try:
            conn = pymysql.connect(**db_config)
            #self.init_conn(conn)
            self.dbhandler_dict[conn] = conn
        except Exception as e:
            conn = None

        return conn

    def fetch_dbhandler(self, db_cluster_key):
        """
        """

        return self.connect(db_cluster_key)

    def push_back_dbhandler(self, conn):

        try:
            if conn in self.dbhandler_dict:
                self.dbhandler_dict[conn].close()
        except Exception as e:
            pass

        return True


if __name__ == '__main__':

    mysql_db = MySqlDB()
    mysql_db.init('../conf/db.conf')
    conn = mysql_db.fetch_dbhandler('send_task_test')

    cursor = conn.cursor()
    cursor.execute('Select * from send_task LIMIT 100')
    rows = cursor.fetchall()
    print rows
    for row in rows:
        print row

    cursor.close()
    mysql_db.push_back_dbhandler(conn)
