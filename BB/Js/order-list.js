$(function(){
	$('.order-slide-item').click(function(){
		var index = $(this).index();
		$('.order-slide-item').removeClass('order-slide-item-active').eq(index).addClass('order-slide-item-active');
		$('.order-slide-container').removeClass('order-slide-container-active').eq(index).addClass('order-slide-container-active');
	});
});
$(function(){
    Api.Post(Api.getUrl('getOrderList'), {
        is_page : 1, 
        page : 1, 
        pagesize : 20,
        paystatus : 100,
        paytype : 100,
        orderstatus : 100
    }, function(apiRtn){
        var finishOrderHtml = '',
            unfinishOrderHtml = '',
            orders = apiRtn['data']['order'];
        for(var i=0; i<orders.length; ++i){
            var order = Api.orderFieldEnumToChars(orders[i]);
            var goodsHtml = '';
            for(var j=0; j<orders[i]['goods'].length; ++j){
                var goods = orders[i]['goods'][j];
                var goodsItemHtml = '<div class="order-content-item">'+
                    '<div class="goods-img col-5-1">'+
                        '<img src="'+HOSTNAME+goods['img']+'">'+
                    '</div>'+
                    '<div class="goods-name col-2-1">'+goods['goods_name']+'</div>'+
                    '<div class="goods-num col-10-1">x'+goods['goods_num']+'</div>'+
                    '<div class="goods-subtotal col-5-1">¥'+goods['sub_total']+'</div>'+
                '</div>';
                goodsHtml = goodsHtml + goodsItemHtml;
            }
            var orderHtml = '<a href="'+Api.U('order-detail.html', {oid:order['id']})+'">'+
                '<div class="order-item">'+
                    '<div class="order-row row">'+
                        '<div class="col-10-3 order-label">订单号</div>'+
                        '<div class="col-10-7 order-value">'+
                            '<span>'+order['order_sn']+'</span>'+
                            '<span><i class="fa fa-angle-right"></i></span>'+
                        '</div>'+
                    '</div>'+
                    '<div class="order-row row">'+
                        '<div class="col-10-3 order-label">支付方式</div>'+
                        '<div class="col-10-7 order-value">'+
                            '<span>'+order['pay_type']+'</span>'+
                            '<span>'+order['pay_status']+'</span>'+
                        '</div>'+
                    '</div>'+
                    
                    '<div class="order-row row">'+
                        '<div class="col-10-3 order-label">水票使用</div>'+
                        '<div class="col-10-7 order-value">'+
                            '<span>'+order['ticket_num']+'张</span>'+
                        '</div>'+
                    '</div>'+
                    
                    '<div class="order-row row">'+
                        '<div class="col-10-3 order-label">下单时间</div>'+
                        '<div class="col-10-7 order-value">'+
                            '<span>'+order['create_time']+'</span>'+
                            '<span>1小时内送达</span>'+
                        '</div>'+
                    '</div>'+
                    '<div class="order-content-item">'+

                        '<div class="goods-name col-10-7">押桶</div>'+
                        '<div class="goods-num col-10-1">x'+order['bucket']+'</div>'+
                        '<div class="goods-subtotal col-5-1">¥'+parseFloat(order['bucket']*order['bucket_price']).toFixed(2)+'</div>'+
                    '</div>'+                
                    '<div class="order-content">'+goodsHtml+'</div>'+
                    '<div class="order-row row order-operate">'+
                        '<div class="col-10-3 order-label">订单价格</div>'+
                        '<div class="col-10-7 order-value">'+
                            '<span class="col-3-1">¥'+order['order_price']+'</span>'+
                            // '<span class="col-3-1"><span>取消订单</span></span>'+
                            // '<span class="col-3-1"><span>去支付</span></span>'+
                        '</div>'+
                    '</div>'+
                '</div>'+
            '</a>';
            console.log(order['order_status']);
            if(order['order_status'] == '已送达'){
                finishOrderHtml = finishOrderHtml + orderHtml;
            }else{
                unfinishOrderHtml = unfinishOrderHtml + orderHtml;
            }
        }
        console.log(finishOrderHtml.length);
        console.log(unfinishOrderHtml.length);
        $('.order-slide-container').eq(0).html(unfinishOrderHtml);
        $('.order-slide-container').eq(1).html(finishOrderHtml);
    });
});
