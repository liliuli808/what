$(function(){
    Api.Post(Api.getUrl('deliverGetOrderList'), {}, function(apiRtn){
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var orders = apiRtn['data'];
            var orderHtml = '';
            for(var i=0; i<orders.length; ++i){
                var order = Api.orderFieldEnumToChars(orders[i]);
                var html = '<div class="order-item data-url" data-url="'+Api.U('deliver-order-detail.html', {oid:order['order_id']})+'">'+
                    '<div class="row">'+
                        '<div class="order-label">订单编号</div>'+
                        '<div class="order-value">'+
                            '<span class="order-sn">'+order['order_sn']+'</span>'+
                            '<span class="order-status">'+order['order_status']+'</span>'+
                        '</div>'+
                    '</div>'+
                    '<div class="row">'+
                        '<div class="order-label">配送时间</div>'+
                        '<div class="order-value">'+order['deliver_time']+'</div>'+
                    '</div>'+
                    '<div class="row">'+
                        '<div class="order-label">配送地址</div>'+
                        '<div class="order-value order-address">'+order['address_pcd']+order['address_detail']+'</div>'+
                    '</div>'+
                '</div>';
                orderHtml = orderHtml + html;
            }
            $('#order-container-panel').html(orderHtml);
            window.bindDataUrlClickAction();
        }else{
            G.tips('系统错误，请稍候再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });
});
