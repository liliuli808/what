$(function(){
    var _orderData = {
            address_id : 0,
            pay_type : -1, // 线下支付
            goods : [],
            comment : '',
            bucket : 0,
        },
        _orderPrice = 0.00,
        urlDumping = 0;
    if(Api.Get('aid')){
        Api.Post(Api.getUrl('getUserAddressInfo'), {address_id:Api.Get('aid')}, function(apiRtn){
            if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
                $('.order-address-panel').eq(1).show();
                var address = apiRtn['data'];
                $('#address-name').html(address['name']);
                $('#address-tel').html(address['tel']);
                $('#address-detail').html(address['pcd']+address['detail']);
                _orderData['address_id'] = address['id'];
            }else{
                $('.order-address-panel').eq(0).show();
            }
        });
    }else{
        Api.Post(Api.getUrl('getDefaultAddress'), {}, function(apiRtn){
            if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
                $('.order-address-panel').eq(1).show();
                var address = apiRtn['data'];
                $('#address-name').html(address['name']);
                $('#address-tel').html(address['tel']);
                $('#address-detail').html(address['pcd']+address['detail']);
                _orderData['address_id'] = address['id'];
            }else{
                $('.order-address-panel').eq(0).show();
            }
        });
    }
     
    if(Api.Get('ticket')){
        try{
            var ticket = JSON.parse(Api.Get('ticket'));
        }catch(e){
            var ticket = [];
        }
        var ticketNum = 0;
        var strategys=0;

        for(var i=0; i<ticket.length; ++i){
            num = parseInt(ticket[i]['num']);
            num = isNaN(num) ? 0 : num;
            ticketNum = ticketNum + num;

            strategy= parseInt(ticket[i]['strategy']);
            strategy = isNaN(strategy) ? 0 : strategy;
            strategys=strategys+strategy;
        }
        if(ticketNum != 0){
            _orderData['ticketNum'] = ticketNum;
            _orderData['strategy'] = strategys;
            $('#order-ticket').html('本次使用'+ticketNum+'张水票');
        }
    }

    Cart.setRepaintCallBack(function(goods, totalPrice){
        console.log(goods);
        console.log(totalPrice);
        // 1. 展示购物车中的商品 & 记录订单的商品信息
        var cartGoods = Cart.getCartGoods();
        _orderPrice = totalPrice;
        if(0 == urlDumping && (!cartGoods || cartGoods.length == 0)){
            G.tips('您还没有选中任何商品...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }else{
            var goodsHtml = '';
            var _orderDataGoods = [];
            for(var i=0; i<cartGoods.length; ++i){
                var html = '<div class="order-goods-item row">'+
                    '<div class="goods-img col-5-1">'+
                        '<img src="'+cartGoods[i]['img']+'">'+
                    '</div>'+
                    '<div class="goods-name col-2-1">'+cartGoods[i]['name']+'</div>'+
                    '<div class="goods-num col-10-1">x'+cartGoods[i]['num']+'</div>'+
                    '<div class="goods-subtotal col-5-1">¥'+cartGoods[i]['subTotal']+'</div>'+
                '</div>';
                try{
                    var ticket = JSON.parse(Api.Get('ticket'));
                }catch(e){
                    var ticket = [];
                }
                var ticketNum = 0;
                for(var j=0; j<ticket.length; ++j){
                    if(ticket[j]['gid'] == cartGoods[i]['gid']){
                        ticketNum = parseInt(ticket[j]['num']);
                        ticketNum = isNaN(ticketNum) ? 0 : ticketNum;
                        console.log(ticketNum);
                        _orderPrice = parseFloat(_orderPrice - ticketNum * cartGoods[i]['price']).toFixed(2);
                    }
                }
                var goods = {goods_id : cartGoods[i]['gid'], goods_num: cartGoods[i]['num'], ticket: ticketNum};
                _orderDataGoods.push(goods);
                goodsHtml = goodsHtml + html;
            }
            $('#order-goods-panel').html(goodsHtml);
            $('.order-total-price').html('¥'+_orderPrice);
            _orderData['goods'] = _orderDataGoods;
            $('#order-ticket-panel').data(
                'url', Api.U('order-ticket.html', {from:'order-submit.html', goods:JSON.stringify(_orderDataGoods)})
            );
            if(_orderPrice == 0){
                $('.payment-item').eq(0).addClass('payment-item-unavaliable');
                $('.payment-item').eq(1).addClass('payment-item-unavaliable');
                _orderData['pay_type'] = 2;
            }
        }

        // 2. 监听押桶按钮 & 记录订单的押桶信息
        $('#bucket-plus').click(function(){
            var _orderDataBucket = 0;
            _orderDataBucket = parseInt($('#bucket-num').html());
            _orderDataBucket = isNaN(_orderDataBucket) ? 0 : _orderDataBucket;
            _orderDataBucket++;
            var bucketPrice = parseFloat(_orderDataBucket * BUCKETPRICE);
            bucketPrice = isNaN(bucketPrice) ? 0.00 : bucketPrice;
            bucketPrice = bucketPrice.toFixed(2);
            $('#order-bucket-price').html('¥'+bucketPrice);
            var totalPrice = (parseFloat(_orderPrice) + parseFloat(bucketPrice)).toFixed(2);
            $('.order-total-price').html('¥'+totalPrice);
            $('#bucket-num').html(_orderDataBucket);
            _orderData['bucket'] = _orderDataBucket;
            if(totalPrice != 0){
                $('.payment-item').eq(0).removeClass('payment-item-unavaliable');
                $('.payment-item').eq(1).removeClass('payment-item-unavaliable');
                _orderData['pay_type'] = -1;
            }
        });
        $('#bucket-minus').click(function(){
            var _orderDataBucket = 0;
            _orderDataBucket = parseInt($('#bucket-num').html());
            _orderDataBucket = isNaN(_orderDataBucket) ? 0 : _orderDataBucket;
            _orderDataBucket--;
            _orderDataBucket = (0 > _orderDataBucket) ? 0 : _orderDataBucket;
            var bucketPrice = parseFloat(_orderDataBucket * BUCKETPRICE);
            bucketPrice = isNaN(bucketPrice) ? 0.00 : bucketPrice;
            bucketPrice = bucketPrice.toFixed(2);
            $('#order-bucket-price').html('¥'+bucketPrice);
            var totalPrice = (parseFloat(_orderPrice) + parseFloat(bucketPrice)).toFixed(2);
            $('.order-total-price').html('¥'+totalPrice);
            $('#bucket-num').html(_orderDataBucket);
            _orderData['bucket'] = _orderDataBucket;
            if(totalPrice == 0){
                $('.payment-item').eq(0).addClass('payment-item-unavaliable');
                $('.payment-item').eq(1).addClass('payment-item-unavaliable');
                $('.fa-dot-circle-o').removeClass('fa-dot-circle-o');
                _orderData['pay_type'] = 2;
            }
        });

        // 3. 绑定结算事件 & 检测订单数据完整
        $('#order-pay-btn').click(function(e){
            if($(this).data('submiting') == 1){
                e.preventDefault();
                return false;
            }else{
                $(this).data('submiting', 1);
                $(this).html('结算中...');
                if(orderSubmitCheckAndPost() === false){
                    setTimeout(function(){
                        $('#order-pay-btn').data('submiting', 0);
                    }, 1000);
                    $('#order-pay-btn').html('去结算<i class="fa fa-angle-right"></i>');
                }
                e.preventDefault();
                return false;
            }
        });

        // 选择支付方式s
        $('.payment-item').click(function(){
            if($(this).hasClass('payment-item-unavaliable')){
                G.tips('目前不能选择该支付方式...');
                return false;
            }
            $('.payment-item').find('.col-10-1').find('i').removeClass('fa-dot-circle-o');
            $(this).find('.col-10-1').find('i').addClass('fa-dot-circle-o');
            var index = $(this).index();
            // wechat pay
            if(index == 0){
                _orderData['pay_type'] = 1;
            }else if(index == 1){
                _orderData['pay_type'] = 0;
            }
        });

        function orderSubmitCheckAndPost(){
            var comment = $('#order-comment').html();
            comment = comment.trim();
            _orderData['comment'] = comment;
            var bucket = _orderData['bucket'];
            bucket = parseInt(bucket);
            bucket = isNaN(bucket) ? 0 : bucket;
            _orderData['bucket'] = bucket;
            var addressId = parseInt(_orderData['address_id']);
            addressId = isNaN(addressId) ? 0 : addressId;
            if(0 == addressId){
                G.tips('请先选择/添加地址...');
                return false;
            }else{
                _orderData['address_id'] = addressId;
            }

            var payType = parseInt(_orderData['pay_type']);
            payType = isNaN(payType) ? 0 : payType;
            if(payType == -1){
                G.tips('请先选择支付方式...');
                return false;
            }
            var goods = _orderData['goods'];
            if(typeof goods != 'object' || goods.length == 0){
                G.tips('请先选择/添加商品...');
                return false;
            }else{
                _orderData['goods'] = JSON.stringify(goods);
            }
            var submitingTips = G.tips('订单提交中，请稍等...', 5000);
            Api.Post(Api.getUrl('orderSubmit'), _orderData, function(apiRtn){

                submitingTips.remove();
                if(apiRtn['code'] != 0){
                    G.tips('系统错误，请稍候再试...');
                    _orderData['goods'] = JSON.parse(_orderData['goods']);
                    $('#order-pay-btn').data('submiting', 0);
                    $('#order-pay-btn').html('去结算<i class="fa fa-angle-right"></i>');
                }else{
                    G.tips('订单提交成功！');
                    Cache.deleteApiCache('getOrderList');
                    Cache.deleteApiCache('getOrderAvalibleTicketList');
                    if(payType == 1){
                        var orderId = parseInt(apiRtn['data']['order_id']);
                        orderId = isNaN(orderId) ? 0 : orderId;
                        if(orderId != 0){
                            Api.Post(Api.getUrl('getWechatPayJsParameters'), {order_id:orderId}, function(apiRtn){
                                if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
                                    var wechatPayParam = JSON.parse(apiRtn['data']);
                                    callpay(wechatPayParam);
                                }else{
                                    G.tips('系统错误，请稍后再试...');
                                }
                            });
                        }
                    }else{
                        setTimeout(function(){
                            urlDumping = 1;
                            Cart.removeAllGoods();
                            window.location.href = 'order-list.html';                    
                        }, 1000);
                    }
                }
            });
        }
    });
    Cart.repaintFromCache();

    //调用微信支付
    function callpay(jsApiParameters){
        if(typeof WeixinJSBridge == "undefined"){
            if(document.addEventListener){
                document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
            }else if(document.attachEvent){
                document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
                document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
            }
        }else{
            jsApiCall(jsApiParameters);
        }
    }

    function jsApiCall(jsApiParameters){
        WeixinJSBridge.invoke(
            'getBrandWCPayRequest', jsApiParameters,
            function(res){
                if(res.err_msg == 'get_brand_wcpay_request:ok'){
                    G.tips('支付成功！');
                }else{
                    //G.tips('支付失败，请尽快支付...');
                    G.tips(res.err_msg);
                }
                //setTimeout(function(){
                //    urlDumping = 1;
                //    Cart.removeAllGoods();
                //    window.location.href = 'order-list.html';
                //}, 1000);
            }
        );
    }
});
