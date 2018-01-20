<?php
namespace Admin\Controller;
use Common\Controller\AdminbaseController;
use Common\Lib\Constant;
use Common\Lib\Wechat;
use Apis\Controller\ApiController;


class OrderController extends AdminbaseController{

    public function C_orderList(){
        if($this->_isAdmin){
            $wechatConfigModel = M('wechat_config');
            $wechatConfig = $wechatConfigModel -> where(array('status' => 1)) -> select();
            $this->assign('wechatconfig', $wechatConfig);
        }
        $where = '1=1';
        if(IS_POST){
            if(intval(I('post.status')) != -1) $where .= ' and o.order_status='.intval(I('post.status'));
            if(intval(I('post.pay_type')) != -1) $where .= ' and o.pay_type='.intval(I('post.pay_type'));
            if(intval(I('post.pay_status')) != -1) $where .= ' and o.pay_status='.intval(I('post.pay_status'));

            if(0 != strlen(trim(I('post.start_time')))) $stime = strtotime(trim(I('post.start_time')).' 00:00:00');
            if(0 != strlen(trim(I('post.end_time')))) $etime = strtotime(trim(I('post.end_time')).' 23:59:59');
            if($stime) $where .= ' and o.create_time >= "'.$stime.'"';
            if($etime) $where .= ' and o.create_time <= "'.$etime.'"';

            $keyword = trim(I('post.keyword'));
            if(0 != strlen($keyword)) $where .= ' and (o.order_sn like "%'.$keyword.'%" or o.openid like "%'.$keyword.'%")';
            if($_SESSION['CONFIG_ID'] != 0){
                $where .= ' and o.config_id = '.$_SESSION['CONFIG_ID'];
            }else{
                if(intval(I('post.config')) != -1) $where .= ' and o.config_id = '.intval(I('post.config'));
            }
            $this->assign('where', $_POST);
        }
        if(IS_GET){
            $keyword = trim(I('get.openid'));
            if(0 != strlen($keyword)) $where .= ' and (o.openid like "%'.$keyword.'%")';
        }
        if($_SESSION['CONFIG_ID'] != 0){
            $where .= ' and o.config_id = '.$_SESSION['CONFIG_ID'];
        }
        $count = M('b2c_order')->alias('o')
            ->field('o.*, u.nickname, u.headimgurl')
            ->join(C('DB_PREFIX').'wechat_user as u on u.openid=o.openid')
            ->where($where)
            ->count();
        $page = $this->page($count, $this->perpage);
        $data['data'] = M('b2c_order')->alias('o')
            ->join(C('DB_PREFIX').'wechat_user_address as a on a.id=o.address_id')
            ->where($where)
            ->limit($page->firstRow, $page->listRows)
            ->order('o.create_time desc')
            ->select();
        // 加入微信服务号名称
        if($this->_isAdmin){
            foreach ($data['data'] as $key => &$value) {
                $value['wechat_name'] = M('wechat_config') -> where(array('id' => $value['config_id'])) -> getField('wechat_name');
            }
            unset($value);
        }
        $data['show'] = $page->show('Admin');
        $this->assign('data', $data);
        $this->display();
    }

    public function C_addOrder()
    {
        $goods=M('goods')->select();
        $stations = M('station')->select();
        $arr = [];
        foreach($stations as &$val){
            array_push($arr, array(
                'desc' => strval(trim($val['desc'])),
                'range' => json_decode($val['range']),
                'station_id' => intval($val['id']),
            ));
        }
        unset($val);
        $this->assign('station',json_encode($arr));
        $this->assign('goods',$goods);
        $this->display();
    }


    public function doAddOrder()
    {
        if(IS_POST)
        {
            $order_sn = I('post.serverNum');
            $user = I('post.user');
            $tel = I('post.tel');
            $address = I('post.address');
            $num = I('post.nums');
            $good_id = I('post.commity');
            $detail=I('post.detail');
            $station=I('post.stations');
            $point=I('post.point');


            $commity = M('goods')->where('id='.$good_id)->find();
            $addressData = [
                'config_id'=>6,
                'name'=>$user,
                'tel'=>$tel,
                'pcd'=>$address,
                'detail'=>$detail,
                'station_id'=>$station,
                'gps'=>$point,
                'create_time'=>time(),
                'status'=>1
            ];
            $address_id = M('wechat_user_address')->add($addressData);
            if($address_id > 0)
            {
                $orderData = [
                    'order_sn'=>$order_sn,
                    'station_id'=>$station,
                    'address_id'=>$address_id,
                    'config_id'=>6,
                    'order_price'=>$commity['price']*$num,
                    'order_original_price'=>$commity['price']*$num,
                    'pay_type'=>1,
                    'pay_status'=>1,
                    'pay_id' => '',
                    'bucket' => 0,
                    'type'=>0,
                    'order_status'=>0,
                    'create_time'=>time()
                ];
               $order_id =  M('b2c_order')->add($orderData);
               if($order_id>0)
               {
                   $detail_id = M('b2c_order_detail')->add([
                       'goods_id'=>$commity['id'],
                       'order_id'=>$order_id,
                       'goods_name'=>$commity['name'],
                       'goods_price'=>$commity['price'],
                       'goods_num'=>$num,
                       'sub_total'=>$commity['price']*$num,
                       'goods_img'=>$commity['img'],
                   ]);
                   if($detail_id >0)
                   {
                       $this->success('录入成功',U('C_addOrder'));
                        $api = new ApiController();
                        $wechat = $api->getWechatObject(6);
                       $wechatUserMsgData = array();
                       array_push($wechatUserMsgData, array(
                           'name' => $commity['name'],
                           'standard' => $commity['standard'],
                           'unit' => $commity['unit'],
                           'num' => $num,
                       ));

                   }else{
                       $this->error('录入失败',U('C_addOrder'));
                   }
               }
            }
        }
    }


}