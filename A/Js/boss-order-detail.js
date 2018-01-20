$(function(){
    if(Api.Get('is_template') && Api.Get('is_template') == 1){
        Cache.deleteApiCache('getOrderDetail');
    }
    Api.Post(Api.getUrl('getBossOrderDetail'), {order_id:Api.Get('oid')}, function(apiRtn){
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var order = Api.orderFieldEnumToChars(apiRtn['data']);
            $('#api-order-sn').html(order['order_sn']);
            $('#api-order-pay-type').html(order['pay_type']) ;
            $('#api-order-pay-status').html(order['pay_status']);
            $('#api-order-order-status').html(order['order_status']);
            $('#api-order-ticket-num').html(order['ticket_num']);
            $('#api-order-create-time').html(order['create_time']);
            $('#api-order-comment').html(order['comment']);
            $('#api-order-price').html('¥'+order['order_price']);
            $('#api-order-address-name').html(order['address']['name']);
            $('#api-order-address-tel').html(order['address']['tel']);
            $('#api-order-address-detail').html(order['address']['pcd']+order['address']['detail']);
            //$('#api-bucket-num').html('x'+order['bucket']);
            //$('#api-bucket-price').html('¥'+parseFloat(order['bucket']*order['bucket_price']).toFixed(2));
            var goodsHtml = '';
            for(var i=0; i<order['goods'].length; ++i){
                var goods = order['goods'][i];
                goodsHtml = goodsHtml + '<div class="order-detail-goods-item">'+
                    '<div class="goods-img col-5-1">'+
                        // '<img src="./Images/nav-1.png">'+
                        '<img src="'+HOSTNAME+goods['goods_img']+'">'+
                    '</div>'+
                    '<div class="goods-name col-2-1">'+goods['goods_name_utf8_10']+'</div>'+
                    '<div class="goods-num col-10-1">x'+goods['goods_num']+'</div>'+
                    '<div class="goods-subtotal col-5-1">¥'+goods['sub_total']+'</div>'+
                '</div>';
            }
            $('#order-detail-goods-panel').prepend(goodsHtml);
            if(typeof order['deliver'] != 'undefined'){
                $('#api-order-deliver-name').html(order['deliver']['name']);
                $('#api-order-deliver-tel').html(order['deliver']['tel']);
                $('#deliver-hr').show();
                $('#order-detail-deliver-panel').show();
            }
        }else{
            G.tips('系统错误，请稍候再试...');
            //setTimeout(function(){
            //    $('#page-back').trigger('click');
            //}, 1000);
        }
    });
});