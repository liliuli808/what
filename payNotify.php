<?php
file_put_contents('./pay.log', "\r\n".date('Y-m-d H:i:s', time())."\r\n".$GLOBALS['HTTP_RAW_POST_DATA'], FILE_APPEND | LOCK_EX);

error_reporting(0);
// $GLOBALS['HTTP_RAW_POST_DATA'] = '<xml><appid><![CDATA[wx0fe96669461105b0]]></appid>
// <attach><![CDATA[test]]></attach>
// <bank_type><![CDATA[CFT]]></bank_type>
// <cash_fee><![CDATA[1]]></cash_fee>
// <fee_type><![CDATA[CNY]]></fee_type>
// <is_subscribe><![CDATA[Y]]></is_subscribe>
// <mch_id><![CDATA[1375833602]]></mch_id>
// <nonce_str><![CDATA[bs1zdub7pj9pgckenkgowxaf0bpn7k3c]]></nonce_str>
// <openid><![CDATA[oClftwolEmpUaQ2eA-EakfvSmCE4]]></openid>
// <out_trade_no><![CDATA[b2c2170622123504403258]]></out_trade_no>
// <result_code><![CDATA[SUCCESS]]></result_code>
// <return_code><![CDATA[SUCCESS]]></return_code>
// <sign><![CDATA[5822CCB9BE1723298B20FE843C27A803]]></sign>
// <time_end><![CDATA[20170623124956]]></time_end>
// <total_fee>1</total_fee>
// <trade_type><![CDATA[JSAPI]]></trade_type>
// <transaction_id><![CDATA[4009682001201706237027935748]]></transaction_id>
// </xml>';

$_POST = array();
$_GET = array();
$_GET['g'] = 'Home';
$_GET['m'] = 'Wechat';
$_GET['a'] = 'wechatPayNotify';
include_once("index.php");