<?php
namespace Apis\Controller;
use Apis\Controller\ApiController;
use Think\Log;
use Common\Lib\Constant;
use Common\Lib\Wechat;
use Common\Lib\Tool;


class BossController extends ApiController{

    //绑定老板
    public function bindBoss(){
        $configId = I('post.config_id', 0, 'intval');
        $openid = I('post.openid', '', 'trim');
        $name = I('post.name', '', 'trim');
        $tel = I('post.tel', '', 'trim');
        try{
            if(0 == strlen($name)){
                throw new \Exception('老板姓名不能为空', Constant::API_FAILED);
            }
            if(0 == strlen($tel) || !Tool::checkPhone($tel)){
                throw new \Exception('老板电话格式错误', Constant::API_FAILED);
            }
            $wechatUser = M('wechat_user')->where(array('openid'=>$openid))->find();
            if(0 == count($wechatUser)){
                throw new \Exception('老板绑定失败', Constant::API_FAILED);
            }else{
                $deliver = M('wechat_user')->where(array(
                    'config_id' => $configId,
                    'openid' => $openid,
                    'is_boss' =>1,
                ))->find();

                $boss = M('boss')->where(array(
                    'config_id' => $configId,
                    'openid' => $openid,
                ))->find();
                
                if(0 != count($deliver)){
                    throw new \Exception('你已经进行过老板绑定了', Constant::API_FAILED);
                }elseif (0 != count($boss)) {
                    throw new \Exception('你已经进行过老板绑定了', Constant::API_FAILED);
                }
            }
        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
        }
        $deliverId = M('boss')->add(array(
            'config_id' => $configId,
            'openid' => $openid,
            'name' => $name,
            'tel' => $tel,
            'create_time' => time(),
            // 'status' => 0,
            'status' => 0,
        ));
        if(1 > intval($deliverId)){
            $this->apiReturn(Constant::API_FAILED, '', '系统错误，请稍候再试...');
        }else{
            $this->apiReturn(Constant::API_SUCCESS, array('id'=>$deliverId), '老板绑定成功！');
        }
    }

    //查看今日业绩
    public function bossList(){
        $configId = I('post.config_id', 0, 'intval');
        $openid = I('post.openid', '', 'trim');
        try{

            $boss = M('wechat_user')->where(array(
                'openid' => $openid,
                'config_id' => $configId,
                'is_boss' => Constant::USER_TYPE_BOSS,
            ))->find();
            if(0 == count($boss)){
                throw new \Exception('你并不是老板', Constant::API_FAILED);

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
        $orderModel = M('b2c_order');
        $where = array('orders.config_id' => $configId);
        $where['orders.pay_status']=Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
        $where['orders.order_status']=Constant::B2C_ORDER_STATUS_FINISHED;

        //$begintime=strtotime(date("Y/m/d H:i:s",mktime(0,0,0,date("m"),date("d"),date("Y"))));
        //$endtime=strtotime(date("Y/m/d H:i:s",mktime(23,59,59,date("m"),date("d"),date("Y"))));
        $begintime=strtotime(date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y'))));
        $endtime=strtotime(date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1));
        $timee=time();


   
        $where['orders.finish_time'] =array('between',"$begintime,$endtime");
       

        $data = array();

        //全部的数量和价格
        $data['alls'] = $orderModel->alias('orders')
            ->join('yds_b2c_order_detail as detail on orders.id=detail.order_id')
            ->where($where)
            ->field('orders.id, orders.openid, orders.config_id, detail.order_id, detail.goods_name,sum(detail.goods_num) as goods_allnum,sum(detail.sub_total) as sub_alltotal')->select();

        foreach($data['alls'] as &$value){
            $value['goods_allnum'] = intval($value['goods_allnum']);
            $value['sub_alltotal'] = sprintf("%.2f", $value['sub_alltotal']);
        }
        unset($value);

        $orderParams = $orderModel->alias('orders')
            ->join('yds_b2c_order_detail as detail on orders.id=detail.order_id')
            ->where($where)
            ->field('orders.id, orders.openid, orders.config_id, orders.order_price, orders.create_time, orders.finish_time, detail.goods_type as goods_type, detail.goods_id, detail.goods_name,sum(detail.goods_num) as goods_num,sum(detail.sub_total) as sub_total')
            ->group('detail.goods_id,detail.goods_type')
            ->order('orders.create_time desc');



        if($is_page == 1){
            $orderParams->limit(($page-1)*$pagesize, $pagesize);
        }
        $data['orderList'] = $orderParams -> select();
        foreach ($data['orderList'] as &$vo) {
            $vo['goods_name'] = strval($vo['goods_name']);
            if(mb_strlen($vo['goods_name'], 'utf8') > 10){
                $vo['goods_name_utf8_10'] = mb_substr($vo['goods_name'], 0, 10, 'utf8').'...';
            }else{
                $vo['goods_name_utf8_10'] = strval($vo['goods_name']);
            }
            $vo['goods_num'] = intval($vo['goods_num']);
            $vo['sub_total'] = sprintf("%.2f", $vo['sub_total']);
            if($vo['goods_type']==0){
                $vo['goods_type']="桶";
            }elseif ($vo['goods_type']==1) {
                $vo['goods_type']="水票";
            }else{
                $vo['goods_type']="个";
            }
        }
        unset($vo);

        $orderList = array('order' => $data, 'is_page' => $is_page);
        if($is_page == 1){
            $orderList['page'] = $page;
            $orderList['pagesize'] = $pagesize;
        }
        $this->apiReturn(Constant::API_SUCCESS, $orderList, '获取订单列表成功！');
    }


    //查看今日业绩
    public function bossBefore(){
        $configId = I('post.config_id', 0, 'intval');
        $openid = I('post.openid', '', 'trim');
        try{

            $boss = M('wechat_user')->where(array(
                'openid' => $openid,
                'config_id' => $configId,
                'is_boss' => Constant::USER_TYPE_BOSS,
            ))->find();
            if(0 == count($boss)){
                throw new \Exception('你并不是老板', Constant::API_FAILED);

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
        $orderModel = M('b2c_order');
        $where = array('orders.config_id' => $configId);
        $where['orders.pay_status']=Constant::B2C_ORDER_PAY_STATUS_SUCCESS;
        $where['orders.order_status']=Constant::B2C_ORDER_STATUS_FINISHED;

        $begintime=strtotime(date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d'),date('Y'))));
        $endtime=strtotime(date("Y-m-d H:i:s",mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1));
        $timee=time();
        $where['orders.finish_time'] =array('elt',$begintime);
      
        $data = array();

        //全部的数量和价格
        $data['alls'] = $orderModel->alias('orders')
            ->join('yds_b2c_order_detail as detail on orders.id=detail.order_id')
            ->where($where)
            ->field('orders.id, orders.openid, orders.config_id, detail.order_id, detail.goods_name,sum(detail.goods_num) as goods_allnum,sum(detail.sub_total) as sub_alltotal')->select();

        foreach($data['alls'] as &$value){
            $value['goods_allnum'] = intval($value['goods_allnum']);
            $value['sub_alltotal'] = sprintf("%.2f", $value['sub_alltotal']);
        }
        unset($value);

        $orderParams = $orderModel->alias('orders')
            ->join('yds_b2c_order_detail as detail on orders.id=detail.order_id')
            ->where($where)
            ->field('orders.id, orders.openid, orders.config_id, orders.order_price, orders.create_time, orders.finish_time, detail.goods_id, detail.goods_type as goods_type, detail.goods_name,sum(detail.goods_num) as goods_num,sum(detail.sub_total) as sub_total')
            ->group('detail.goods_id,detail.goods_type')
            ->order('orders.create_time desc');



        if($is_page == 1){
            $orderParams->limit(($page-1)*$pagesize, $pagesize);
        }
        $data['orderList'] = $orderParams -> select();
        foreach ($data['orderList'] as &$vo) {
            $vo['goods_name'] = strval($vo['goods_name']);
            if(mb_strlen($vo['goods_name'], 'utf8') > 10){
                $vo['goods_name_utf8_10'] = mb_substr($vo['goods_name'], 0, 10, 'utf8').'...';
            }else{
                $vo['goods_name_utf8_10'] = strval($vo['goods_name']);
            }
            $vo['goods_num'] = intval($vo['goods_num']);
            $vo['sub_total'] = sprintf("%.2f", $vo['sub_total']);
        }
        unset($vo);

        $orderList = array('order' => $data, 'is_page' => $is_page);

        if($is_page == 1){
            $orderList['page'] = $page;
            $orderList['pagesize'] = $pagesize;
        }
        $this->apiReturn(Constant::API_SUCCESS, $orderList, '获取订单列表成功！');
    }

    //老板派单的显示水工的信息
    public function bossDeliver(){
        $configId = I('post.config_id', 0, 'intval');
        $openid = I('post.openid', '', 'trim');
        $orderid=I('post.orderid',0,'intval');
        try{
            $boss = M('wechat_user')->where(array(
                'openid' => $openid,
                'config_id' => $configId,
                'is_boss' => Constant::USER_TYPE_BOSS,
            ))->find();
            if(0 == count($boss)){
                throw new \Exception('你并不是老板', Constant::API_FAILED);
            }

            $order=M('b2c_order')->where(array(
                    'config_id' =>$configId,
                    'id' =>$orderid,
                    'order_status' =>Constant::B2C_ORDER_STATUS_CREATED,
                ))->find();
            if(0 == count($order)){
                throw new \Exception("没有这个订单",  Constant::API_FAILED);
            }else{
                $address_id = $order['address_id'];
                $address = M('wechat_user_address')->where(array('config_id'=>$configId,'status'=>Constant::USER_ADDRESS_STATUS_DEFAULT,'id'=>$address_id))->find();
                if(0 == count($address)){
                    throw new \Exception("没有这个收货地址",  Constant::API_FAILED);
                }else{
                    $station_id = $address['station_id'];
                    $station = M('station')->where(array('config_id'=>$configId,'status'=>Constant::STATION_STATUS_ONSALE,'id'=>$station_id))->find();
                    if(0 == count($station)){
                        throw new \Exception("没有这个水站",  Constant::API_FAILED);
                    }
                }
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
        $deliverModel = M('station_deliver');
        $where = array('config_id' => $configId);
        $where['status'] = Constant::STATION_DELIVER_STATUS_OPEN;
        $where['station_id'] = $station_id;
        $deliverParams = $deliverModel->where($where)->order('create_time desc');
        if($is_page == 1){
            $deliverParams->limit(($page-1)*$pagesize, $pagesize);
        }
        $deliverList = $deliverParams -> select();
   
        $orderList = array('deliver' => $deliverList, 'orderid' => $orderid, 'is_page' => $is_page);

        if($is_page == 1){
            $orderList['page'] = $page;
            $orderList['pagesize'] = $pagesize;
        }
        $this->apiReturn(Constant::API_SUCCESS, $orderList, '获取水工列表成功！');
    }

    //老板开始派单
    public function bossDeliverSubmit(){
        $configId = intval(I('post.config_id')); 
        $openid = trim(I('post.openid')); 
        $deliver_id = I('post.deliver_id',0,'intval');
        $orderid = I('post.orderid',0,'intval');

        $transModel = M();
        $goodsModel = M('goods');
        $orderModel = M('b2c_order');
        $orderDetailModel = M('b2c_order_detail');
        $bossDeliverModel = M('boss_deliver');

        // 微信用户订单提交成功模板消息数据
        $wechatUserMsgData = array();
        $orderDetailData = array();
        $orderPrice = 0.00;
        $orderOriginalPrice = 0.00;
        try{

            //检测老板 $openid
            $user = M('wechat_user')
            ->where(array('config_id'=>$configId,'openid'=>$openid,'is_boss'=>Constant::USER_TYPE_BOSS))
            ->find();
            if(0 == count($user)){
                throw new \Exception('boosDelive submit param boss11 is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
            }else{
                $boss=M('boss')->where(array('config_id'=>$configId,'openid'=>$openid,'status'=>Constant::STATION_BOSS_STATUS_OPEN))->find();
            }
            // 检测水工 $deliver_id
            if(1 > $deliver_id){
                throw new \Exception('boosDelive submit param deliver_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
            }else{
                $deliver = M('station_deliver')
                    ->where(array(
                        'config_id' => $configId,
                        'id' => $deliver_id,
                        'status' => Constant::STATION_DELIVER_STATUS_OPEN,
                    ))->find();
                if(0 == count($deliver)){
                    throw new \Exception('bossDeliver submit param deliver_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
                }
            }

            // 检测订单 $orderid
            if(1 > $orderid){
                throw new \Exception('boosDelive submit param orderid is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
            }else{
                $order = M('b2c_order')
                    ->where(array(
                        'config_id' => $configId,
                        'id' => $orderid,
                        'order_status' => Constant::B2C_ORDER_STATUS_CREATED,
                    ))->find();
                if(0 == count($order)){
                    throw new \Exception('bossDeliver submit param orderid is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
                }
            }

            // 检测address_id
            $addressId = intval($order['address_id']);
            if(1 > $addressId){
                throw new \Exception('bossDeliver submit param address_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
            }else{
                $address = M('wechat_user_address')
                    ->where(array(
                        'config_id' => $configId,
                        'id' => $addressId,
                        'status' => array('in', array(
                            Constant::USER_ADDRESS_STATUS_ONUSE,
                            Constant::USER_ADDRESS_STATUS_DEFAULT,
                        )),
                    ))
                    ->find();
                if(0 == count($address)){
                    throw new \Exception('bossDeliver submit param address_id is illegal', Constant::API_ORDER_SUBMIT_PARAM_ERROR);
                }
            }

            //检查订单详情
            $orderDetail=$orderDetailModel->where('order_id='.$orderid)->select();
            foreach ($orderDetail as $value) {
               $goodsTemp = $goodsModel->where(array(
                    'id' => $value['goods_id'],
                    'config_id' => $configId,
                    'status' => Constant::GOODS_STATUS_ONSALE
                ))->find();

               array_push($wechatUserMsgData, array(
                    'name' => $value['goods_name'],
                    'standard' => $goodsTemp['standard'],
                    'unit' => $goodsTemp['unit'],
                    'num' => $value['goods_num'],
                ));
            }
            //检查订单信息
            $orderData = $orderModel->where('id='.$orderid)->find();

        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
        }
        
        try{
            //处理老板派单信息
            $bossDeliverData=array(
                    'config_id' => $configId,
                    'boss_openid' => $openid,
                    'deliver_openid' => $deliver['openid'],
                    'boss_id' =>$boss['id'],
                    'deliver_id' => $deliver['id'],
                    'boss_name' =>$boss['name'],
                    'deliver_name' =>$deliver['name'],
                    'create_time' =>time(),
                    'status' =>0,
                    'order_id' => $orderid,
                );
            $bossDeliverId = intval($bossDeliverModel->add($bossDeliverData));
            if(1 > $bossDeliverId){
                throw new \Exception('bossDeliver Submit Error, failed to insert bossDeliver data', Constant::API_ORDER_SUBMIT_ERROR);
            }else{
                $wechat = $this->getWechatObject($configId);
                $this->_sendStationDeliverNewOrderTemplateMsg($wechat, $wechatUserMsgData, $orderData, $address, $orderid,$deliver_id);
                $this->apiReturn(Constant::API_SUCCESS, array('order_id'=>$orderid), '派单成功');
            }
        }catch(\Exception $e){
            $transModel -> rollback();
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            record_error($e);
            $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
        }
    }

    //给水工发送抢单的信息

    /**
     * 向水站的水工发送《新订单提醒》模板消息
     * @param  object $wechat         Common\Lib\Wechat instance
     * @param  array  $goodsInfoArr   goods info
     * @param  array  $orderInfoArr   order info
     * @param  array  $addressInfoArr address info
     * @param  int    $orderId        order id
     * @param  string  $type 0普通订单1组合商品订单
     */
    private function _sendStationDeliverNewOrderTemplateMsg($wechat, $goodsInfoArr, $orderInfoArr, $addressInfoArr, $orderId,$deliver_id){
        $first = '新订水订单提醒';
        $orderSn = $orderInfoArr['order_sn'];
        if($orderInfoArr['type'] == 0){
            $goodsInfoStr = $goodsInfoArr[0]['name'].$goodsInfoArr[0]['standard'].'/'.$goodsInfoArr[0]['unit'].' x '.$goodsInfoArr[0]['num'];
            if(1 != count($goodsInfoArr)){
                $goodsInfoStr .= ', ...';
            }
        }else{
            $goodsType = $goodsInfoArr['type'] == 1 ? '水票' : '套餐';
            $goodsInfoStr = $goodsType.$goodsInfoArr['name'].' X 1';
        }
        $orderPrice = $orderInfoArr['order_price'].' 元';
        $addressInfoArr['tel'] = substr($addressInfoArr['tel'], 0 ,3).'****'.substr($addressInfoArr['tel'], -4);
        $addressStr = $addressInfoArr['name'].' '.$addressInfoArr['tel'];
        if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_CASH){
            $payType = '现金支付';
        }else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_WECHAT){
            $payType = '微信支付';
        }else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET){
            $payType = '水票支付';
        }else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET_CASH){
            $payType = '水票支付和线下支付';
        }else if($orderInfoArr['pay_type'] == Constant::B2C_ORDER_PAY_TYPE_TICKET_WECHAT){
            $payType = '水票支付和微信支付';
        }
        $remark = '请及时处理（点击查看详情并配送）';
        $data = array(
            'first'    => $first,
            'keyword1' => $orderSn,
            'keyword2' => $goodsInfoStr,
            'keyword3' => $orderPrice,
            'keyword4' => $addressStr,
            'keyword5' => $payType,
            'remark'   => $remark,
        );

        $templateId = $wechat->getTemplateMsgId('order_push_tpl_id');
        $configId = $wechat->getConfigId();
        $delivers = M('station_deliver')->where(array(
            'config_id' => $configId,
            'status' => 1,
            'id' =>$deliver_id,
        ))->find();
        $file = 'deliver-order-detail.html';
        
        $prefix = $wechat->getWechatFunctionPrefix();
        $url = HOSTNAME.$prefix.'/'.$file.'#config_id='.$configId.'&openid=OPENID&oid='.$orderId;
        try{
            $detailUrl = str_replace('OPENID', $delivers['openid'], $url);
            $wechat->sendWechatTemplateMsg($delivers['openid'], $templateId, $data, $detailUrl);
        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            record_error($e);
            $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
        }
    }

    //老板派单的时候查看详情
    public function getBossOrderDetail(){
        $configId = I('post.config_id', 0, 'intval');
        $orderId = I('post.order_id', 0, 'intval');

        // 参数验证：order_id > 0 & order exists
        try{
            if(1 > $orderId){
                throw new \Exception('Get order info order_id illegal', Constant::API_ORDER_INFO_PARAM_ERROR);
            }else{
                $order = M('b2c_order')->where(array(
                    'config_id' => $configId,
                    'id' => $orderId
                ))->find();
                if(0 == count($order)){
                    throw new \Exception('Order is not exists', Constant::API_ORDER_NOT_EXIST);
                }
            }
        }catch(\Exception $e){
            Log::write('['.$e->getCode().'] '.$e->getMessage(), 'ERR');
            $this->apiReturn($e->getCode(), $_POST, $e->getMessage());
        }
        $orderDetail = M('b2c_order_detail')->where(array(
            'order_id' => intval($order['id']),
        ))->select();
        if(0 != count($orderDetail)){
            $orderGoods = array();
            foreach ($orderDetail as $value) {
                $goodsTemp = array();
                $goodsTemp['goods_name'] = strval($value['goods_name']);
                if(mb_strlen($value['goods_name'], 'utf8') > 10){
                    $goodsTemp['goods_name_utf8_10'] = mb_substr($value['goods_name'], 0, 10, 'utf8').'...';
                }else{
                    $goodsTemp['goods_name_utf8_10'] = strval($value['goods_name']);
                }
                $goodsTemp['goods_price'] = sprintf('%.2f', $value['goods_price']);
                $goodsTemp['sub_total'] = sprintf('%.2f', $value['sub_total']);
                $goodsTemp['goods_num'] = intval($value['goods_num']);
                $goodsTemp['goods_img'] = strval($value['goods_img']);
                array_push($orderGoods, $goodsTemp);
            }
        }else{
            $orderGoods = false;
        }

        //接单水工信息
        if($order['deliver_id'] > 0){
            $deliver = M('station_deliver') -> where(array('id' => $order['deliver_id'])) -> field('name, tel') -> find();
            $deliver['name'] = strval($deliver['name']);
            $deliver['tel'] = substr($deliver['tel'], 0 ,3).'****'.substr($deliver['tel'], -4);
            $deliver['tel'] = strval($deliver['tel']);
        }else{
            $deliver = false;
        }

        $address = M('wechat_user_address')->where(array(
            'config_id' => $configId,
            'id' => intval($order['address_id']),
        ))->find();
        if(0 != count($address)){
            $addressInfo = array(
                'name' => strval($address['name']),
                'tel' => strval($address['tel']),
                'pcd' => strval($address['pcd']),
                'detail' => strval($address['detail']),
                'gps' => strval($address['gps']),
            );
        }else{
            $addressInfo = false;
        }

        $rtn = array(
            'order_id' => $order['id'],
            'order_price' => sprintf('%.2f', $order['order_price']),
            'pay_type' => intval($order['pay_type']),
            'pay_status' => intval($order['pay_status']),
            'comment' => strval($order['comment']),
            'order_status' => $order['order_status'],
            'ticket_num' => intval($order['ticket_num']),
            'create_time' => intval($order['create_time']),
            'order_sn' => strval($order['order_sn']),
            'bucket' => intval($order['bucket']),
            'bucket_price' => Constant::B2C_ORDER_BUCKET_PRICE,
        );
        if($orderGoods){
            $rtn['goods'] = $orderGoods;
        }
        if($addressInfo){
            $rtn['address'] = $addressInfo;
        }
        if($deliver){
            $rtn['deliver'] = $deliver;
        }
        $this->apiReturn(Constant::API_SUCCESS, $rtn, '获取订单详情成功');
    }


}