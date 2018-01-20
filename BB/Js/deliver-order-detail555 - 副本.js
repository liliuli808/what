$(function(){
    Api.Post(Api.getUrl('deliverGetOrderInfo'), {order_id:Api.Get('oid')}, function(apiRtn){
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var order = Api.orderFieldEnumToChars(apiRtn['data']);
            console.log(order);
            $('#api-address-name').html(order['address']['name']);
            $('#api-address-tel').html(order['address']['tel']);
            $('#api-address-detail').html(order['address']['pcd']+order['address']['detail']);
            $('#api-order-sn').html(order['order_sn']);
            $('.api-order-price').html('¥'+order['order_price']);
            $('#api-create-time').html(order['create_time_ymd']);
            $('#api-pay-type').html(order['pay_type']);
            $('#order-comment').html(order['comment']);
            var goodsHtml = ''; 
            for(var i=0; i<order['goods'].length; ++i){
                var goods = order['goods'][i];
                var html = '<div class="order-goods-item">'+
                    '<div class="col-5-1">'+
                        '<img src="'+HOSTNAME+goods['goods_img']+'">'+
                    '</div>'+
                    '<div class="col-5-3">'+
                        '<div class="goods-name">'+goods['goods_name']+'</div>'+
                        '<div class="goods-num">x'+goods['goods_num']+'</div>'+
                    '</div>'+
                    '<div class="col-5-1 goods-subtotal">¥'+goods['sub_total']+'</div>'+
                '</div>';
                goodsHtml = goodsHtml + html;
            }
            $('#bucket-panel').before(goodsHtml);
            $('#api-bucket-num').html('x'+parseInt(order['bucket']));
            $('#api-bucket-price').html('¥'+parseFloat(order['bucket'] * BUCKETPRICE).toFixed(2));
            $('#order-status').html(order['order_status']);
            if(order['is_accept'] == 0){
                $('#order-accept-btn').show();
            }
            if(typeof order['deliver'] == 'object'){
                $('#api-deliver-name').html(order['deliver']['name']);
                $('#api-deliver-tel').html(order['deliver']['tel']);
                $('#api-deliver-accepttime').html(order['deliver_accept_time']);
                $('#order-deliver-info-hr').show();
                $('#order-deliver-panel').show();
                if(order['deliver']['openid'] == Api.Get('openid') && order['order_status'] == '配送中'){
                    $('#order-finish-btn').show();
                }
            }
        }else{
            G.tips('系统错误，请稍候再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });

    $('#order-accept-btn').click(function(){
        Api.Post(Api.getUrl('deliverAcceptOrder'), {order_id:Api.Get('oid')}, function(apiRtn){
            G.tips(apiRtn['msg']);
            if(apiRtn['code'] == 0){
                setTimeout(function(){
                    Cache.deleteApiCache('deliverGetOrderInfo');
                    Cache.deleteApiCache('deliverGetOrderList');
                    window.location.reload();
                }, 1000);
            }
        });
    });
    $('#order-finish-btn').click(function(){
        var bucket = parseInt($('#api-bucket-num').html().replace('x', ''));
        bucket = isNaN(bucket) ? 0 : bucket;
        G.confirm({
            tips : '确认此订单押桶数量为'+bucket+'？',
            confirmBtn : '是的，押'+bucket+'个',
            cancelBtn : '不对，再改改',
            confirm : function(){
                 if($('#api-pay-type').html() == '线下支付'){
                    var price = parseFloat($('.api-order-price').eq(0).html().replace('¥', ''));
                    price = isNaN(price) ? 0.00 : price.toFixed(2);
                    G.confirm({
                        tips : '此订单为线下支付，确认已经收款'+price+'元？',
                        confirmBtn : '是的，已收款',
                        cancelBtn : '还没...',
                        confirm : function(){
                            Api.Post(Api.getUrl('deliverFinishOrder'), {order_id:Api.Get('oid')}, function(apiRtn){
                                if(apiRtn['code'] == 0){
                                    G.tips('订单配送完成！');
                                    Cache.deleteApiCache('deliverGetOrderInfo');
                                    Cache.deleteApiCache('deliverGetOrderList');
                                }else{
                                    G.tips('系统错误，请稍候再试...');
                                }
                                setTimeout(function(){
                                    window.location.reload();
                                }, 1000);
                            });
                        },
                    });
                }else{
                    Api.Post(Api.getUrl('deliverFinishOrder'), {order_id:Api.Get('oid')}, function(apiRtn){
                        if(apiRtn['code'] == 0){
                            G.tips('订单配送完成！');
                            Cache.deleteApiCache('deliverGetOrderInfo');
                            Cache.deleteApiCache('deliverGetOrderList');
                            Cache.deleteApiCache('getOrderDetail');
                        }else{
                            G.tips('系统错误，请稍候再试...');
                        }
                        setTimeout(function(){
                            window.location.reload();
                        }, 1000);
                    });
                }
            },
        });
    });
});