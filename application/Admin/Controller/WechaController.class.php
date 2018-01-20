<?php
/**
 * Created by PhpStorm.
 * User: 1006a
 * Date: 2018/1/20
 * Time: 17:34
 */

namespace Admin\Controller;


use EasyWeChat\Foundation\Application;
use Think\Controller;

class WechaController extends Controller
{
     private $_options =[
         'debug'     => true,
        'app_id'    => 'wxab2a084e9bd9e51a',// 测试号wxff19f26ac4398d8c
        'secret'    => 'e50503e7cfba2a9151ba42641b2f67bf',// 测试号d4624c36b6795d1d99dcf0547af5443d
        'token'     => 'abc',
        'aes_key'   => '',
        'log' => [
            'level' => 'debug',
            'file'  => '/tmp/easywechat.log',
        ],
    ];
     private  $_weixin;

     public function _initialize()
     {
         $this -> _weixin = new Application($this->_options);
     }

    public function getAll()
    {
        $access_token = $this->getAccessToken();
        $open_id = '';
        $templ_id = 'HMB7aYHVYKOVEqf0_cGyi6YLSSyTxx9uhNcrfHXvomE';
        $data = [
            'first'    => '1',
            'keyword1' => '2',
            'keyword2' => '3',
            'keyword3' => '4',
            'keyword4' => '5',
            'keyword5' => '6',
            'remark'   => '7',
        ];
        $url = '';
        $this->sendNotify($open_id,$templ_id,$data);
    }

    /**
     * [getAccessToken 获取access_token]
     * @param  $[refresh] [0不刷新,1刷新]
     * @return [type] [description]
     */
    public function getAccessToken($refresh = 0){
        $accessToken = $this -> _weixin -> access_token; // EasyWeChat\Core\AccessToken 实例
        if (!$refresh) {
            $token = $accessToken->getToken();
        } else {
            $token = $accessToken->getToken(true); // 强制重新从微信服务器获取 token.
        }
        return $token;
    }

    public function  getTicket()
    {
        $accessToken = $this->getAccessToken();

    }

    /*
   * 发送模板消息,后期加上事件推送，接收通知结果
   * @param string $openid  - 接收者用户ID
   * @param string $tem_id  - 微信模板ID
   * @param array  $data    - 模板内容
   * @param string $url     - 点击模板跳转的连接
   * */
    public function sendNotify($openid,$tem_id,$data,$url=''){
        $access_token = $this->getAccessToken();
        $toUrl = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;
        $param['touser'] = $openid;
        $param['template_id'] = $tem_id;
        $param['url']    = $url;
        $param['data']   = $data;
        return httpPost($toUrl,json_encode($param));
    }
}