$(function(){

    $('.ticket-slide-item').click(function(){
        var index = $(this).index();
        $('.ticket-slide-item').removeClass('ticket-slide-item-active').eq(index).addClass('ticket-slide-item-active');
        $('.ticket-slide-conta').removeClass('ticket-slide-conta-active').eq(index).addClass('ticket-slide-conta-active');
        $('title').html($(this).html());
        $('#page-title').html($(this).html());

    });

    if(Api.Get('index') == 1){
        $('.ticket-slide-item').trigger('click');
    }

    if(Api.Get('from')){
        $('#page-back').data('url', Api.Get('from'));
    }

    Api.Post(Api.getUrl('bossList'), {
        is_page : 1,
        page : 1,
        pagesize : 30
    }, function(apiRtn){
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var finishOrderHtml = '',
            orderHtmlall='',

            orders = apiRtn['data']['order']['orderList'],
            alls = apiRtn['data']['order']['alls'][0];

            
            orderHtmlall+='<div class="row panal-w">' +
            '<div class="col-2-1 ticket-slide-item1 ">' +
            '<div class="blue">' + alls['goods_allnum'] + '</div>' +
            '<div class="fa-a">销售量(桶)</div>' +
            '</div>' +
            '<div class="col-2-1 ticket-slide-item1">' +
            '<div class="red">￥' + alls['sub_alltotal'] + '</div>' +
            '<div class="fa-a">销售额(元)</div>' +
            '</div>' +
            '</div>';


        for(var i=0; i<orders.length; ++i){
            var order = Api.orderFieldEnumToChars(orders[i]);
            var orderHtml=
                '<div class="ticke-goods-item">' +
                '<div id="order-info-panel">' +
                '<div class="order-row row">' +
                '<div class="order-label">' +
                '<div class="goods-name col-10-6">' + order['goods_name_utf8_10'] + '</div>' +
                '<div class="goods-num col-10-2" id="api-bucket-num">' + order['goods_num'] + order['goods_type'] +'</div>' +
                '<div class="goods-subtotal col-5-1" id="api-bucket-price">￥' + order['sub_total'] + '</div>' +
                '</div>' +
                '<div class="col-10-7 order-value">' +
                '<span id="api-order-pay-type"></span>' +
                '<span id="api-order-pay-status"></span>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';
            finishOrderHtml = finishOrderHtml +  orderHtml;
        }
        $('#now_bosslist').html(orderHtmlall + finishOrderHtml);
        $('.data-url').unbind('click');
        window.bindDataUrlClickAction();
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });


    Api.Post(Api.getUrl('bossBefore'), {
        is_page : 1,
        page : 1,
        pagesize : 30
    }, function(apiRtn){
        var unfinishOrderHtml = '',
            orderHtmlall='',

            orders = apiRtn['data']['order']['orderList'],
            alls = apiRtn['data']['order']['alls'][0];
        //console.log(apiRtn['data']);

            orderHtmlall+='<div class="row panal-w">' +
            '<div class="col-2-1 ticket-slide-item1 ">' +
            '<div class="blue">' + alls['goods_allnum'] + '</div>' +
            '<div class="fa-a">销售量(桶)</div>' +
            '</div>' +
            '<div class="col-2-1 ticket-slide-item1">' +
            '<div class="red">￥' + alls['sub_alltotal'] + '</div>' +
            '<div class="fa-a">销售额(元)</div>' +
            '</div>' +
            '</div>';


        for(var i=0; i<orders.length; ++i){
            var order = Api.orderFieldEnumToChars(orders[i]);

                 var orderHtml=
                    '<div class="ticke-goods-item">' +
                    '<div id="order-info-panel">' +
                    '<div class="order-row row">' +
                    '<div class="order-label">' +
                    '<div class="goods-name col-10-6">' + order['goods_name_utf8_10'] + '</div>' +
                    '<div class="goods-num col-10-2" id="api-bucket-num">' + order['goods_num'] + '桶</div>' +
                    '<div class="goods-subtotal col-5-1" id="api-bucket-price">￥' + order['sub_total'] + '</div>' +
                    '</div>' +
                    '<div class="col-10-7 order-value">' +
                    '<span id="api-order-pay-type"></span>' +
                    '<span id="api-order-pay-status"></span>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';


            // if(orders['deliver_type'] == 2){
            //     finishOrderHtml = finishOrderHtml +  orderHtml;
            // }else{
                 unfinishOrderHtml = unfinishOrderHtml  + orderHtml;
            // }
        }
        $('#before_boosslist').html(orderHtmlall + unfinishOrderHtml);
        // console.log(finishOrderHtml.length);
        // console.log(unfinishOrderHtml.length);
        //$('.ticket-slide-conta').eq(0).html(orderHtmlall + unfinishOrderHtml);
        //$('.ticket-slide-conta').eq(1).html(orderHtmlall + finishOrderHtml);
    });
});