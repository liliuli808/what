$(function(){
    $('.ticket-slide-item').click(function(){
        var index = $(this).index();
        $('.ticket-slide-item').removeClass('ticket-slide-item-active').eq(index).addClass('ticket-slide-item-active');
        $('.ticket-slide-conta').removeClass('ticket-slide-conta-active').eq(index).addClass('ticket-slide-conta-active');
        $('title').html($(this).html());
        $('#page-title').html($(this).html());
    });
    Api.Post(Api.getUrl('bossGetOrderList'), {
        is_page : 1, 
        page : 1, 
        pagesize : 50,
        deliver_type : 100,
    }, function(apiRtn){
        var finishOrderHtml = '',
            unfinishOrderHtml = '',
            orders = apiRtn['data']['order'];
        for(var i=0; i<orders.length; ++i){
            var order = Api.orderFieldEnumToChars(orders[i]);
            var orderHtml = '<div class="ticke-goods-item">'+
                    '<div id="order-info-panel">'+
                        '<div class="order-row row">'+
                            '<div class="col-10-6">'+
                                '<span class="OrderID">订单号'+
                                 order['order_sn']+
                                '</span>'+
                            '</div>'+
                            '<div class="col-5-2">'+
                               '<span class="Order-timer">'+order['create_time']+'</span>'+
                            '</div>'+
                        '</div>'+
                        // '<div class="order-row row">'+
                        //    '<div class="order-label">'
                        //         '<div class="goods-name col-10-7">'+order['goods_name']+'</div>'+
                        //         '<div class="goods-num col-10-1" id="api-bucket-num">x'+order['goods_num']+'</div>'+
                        //         '<div class="goods-subtotal col-5-1" id="api-bucket-price">¥'+order['sub_total']+'</div>'+
                        //     '</div>'+
                        //     '<div class="col-10-7 order-value">'+
                        //         '<span id="api-order-pay-type"></span>'+
                        //         '<span id="api-order-pay-status"></span>'+
                        //     '</div>'+
                        // '</div>'+
                        '<div class="row">'+
                            '<div class="order-label-name">'+order['address_name']+' （'+order['address_tel']+'）</div>'+
                            '<div class="order-valuee">地址：'+
                            '<span id="order-order-status">'+order['address_pcd']+order['address_detail']+'</span>'+
                            '</div>'+
                        '</div>'+
                        '<div class="row order-commentt">'+
                            '<div class="col-10-6">应收款：'+
                               '<span class="Order-money">￥'+order['order_price']+'</span>'+
                            '</div>'+
                            '<div class="col-5-2 send-orders" >'+
                                '<span class="Order-timer-orders"><a href="'+Api.U('boss-deliver.html', {orderid:order['order_id']})+'">立即派单</a></span>'+ 
                            '</div>'+
                        '</div>'+
                    '</div>'+
                '</div>';
            if(order['deliver_type'] == 1){

                finishOrderHtml = finishOrderHtml + orderHtml;
            }else{
                unfinishOrderHtml = unfinishOrderHtml + orderHtml;
            }
        }
         //console.log(finishOrderHtml.length);
         //console.log(unfinishOrderHtml.length);
        $('.ticket-slide-conta').eq(0).html(unfinishOrderHtml);
        $('.ticket-slide-conta').eq(1).html(finishOrderHtml);
    });
});