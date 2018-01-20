<?php
namespace Apis\Controller;
use Apis\Controller\ApiController;
use Think\Log;
use Common\Lib\Constant;
use Common\Lib\Wechat;
use Common\Lib\Tool;


class DistributionController extends ApiController{


    //查看今日业绩
    public function distributionList(){
        $configId = I('post.config_id', 0, 'intval');
        $openid = I('post.openid', '', 'trim');
        try{
            $user = M('wechat_user')->where(array(
                'openid' => $openid,
                'config_id' => $configId,
            ))->find();
            if(0 == count($user)){
                throw new \Exception('没有这个用户', Constant::API_FAILED);

            }
        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
        }

        $is_page = I('post.is_page', 0, 'intval');
        if($is_page == 1){
            $page = I('post.page', 1, 'intval');
            $pagesize = I('post.pagesize', 10, 'intval');
        }
        $distributionModel = M('distribution_detail');
        $where = array('config_id' => $configId,'user_openid'=>$openid);
      
        $orderParams = $distributionModel->where($where)->order('create_time desc');

        if($is_page == 1){
            $orderParams->limit(($page-1)*$pagesize, $pagesize);
        }

        $data = array();

        $data['allprice']=$orderParams -> field('sum(price) as allprice')->select();
        foreach ($data['allprice'] as $key => $value) {
            $value['allprice'] = sprintf("%.2f", $value['allprice']);
        }
        unset($value);

        $data['List'] = $orderParams -> select();
        foreach ($data['List'] as &$vo) {
            $wechat_user=M("wechat_user")->where(array('openid'=>$vo['member_openid'],'config_id'=>$configId))->find();
            if(mb_strlen(base64_decode($wechat_user['nickname']), 'utf8') > 10){
                $vo['goods_name_utf8_10'] = mb_substr(trim(base64_decode($wechat_user['nickname'])), 0, 10, 'utf8').'...';
            }else{
                $vo['goods_name_utf8_10'] = strval(trim(base64_decode($wechat_user['nickname'])));
            }
            $vo['create_time'] = date('Y-m-d H:i:s',$vo['create_time']);
        }
        unset($vo);

        $orderList = array('order' => $data, 'is_page' => $is_page);
        if($is_page == 1){
            $orderList['page'] = $page;
            $orderList['pagesize'] = $pagesize;
        }
        $this->apiReturn(Constant::API_SUCCESS, $orderList, '获取分销列表成功！');
    }
}