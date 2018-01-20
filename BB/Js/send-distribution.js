$(function(){
    var _deliverId = 0, _orderId=0;
    function addCombineGoodsActionListener(){
        $('.combine-goods-item').click(function(){
            $('.fa-dot-circle-o').addClass('fa-circle-o').removeClass('fa-dot-circle-o');
            $(this).find('.col-5-1').find('i').addClass('fa-dot-circle-o').removeClass('fa-circle-o');
            $('#cg-purchase-panel').animate({
                bottom : 0
            }, 400);
            _deliverId = $(this).find('.col-5-1').find('i').data('cgid');
            _orderId = $(this).find('.col-5-1').find('i').data('orderid');
        });
    }

    Api.Post(Api.getUrl('distributionList'), {
        is_page : 1,
        page : 1,
        pagesize : 30,
    }, function(apiRtn){
        console.log(apiRtn);
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var allprice = apiRtn['data']['order']['allprice'][0];
            var combineGoods = apiRtn['data']['order']['List'];
            var combineGoodsHtml = '';
            var orderHtmlall='';
            orderHtmlall+='<div class="distributin-n">'+
                                '<div class="distr-w">分销收益(元)</div>'+
                                '<span>'+allprice['allprice']+'</span>'+
                            '</div>';

            for(var i=0; i<combineGoods.length; ++i){
                var goodsHtml = '<div class="list-div">'+
                                    '<div id="order-bucket-panel">'+
                                        '<div class="col-2-1 fa">'+combineGoods[i]['create_time']+
                                                '<span id="bucket-info" class="fa">'+
                                                        '<i class="fa ">用户：</i>'+combineGoods[i]['goods_name_utf8_10']+
                                                '</span>'+
                                        '</div>'+
                                        '<div class="col-2-1">'+
                                                '<div class="col-3-2" id="bucket-operate">'+
                                                        '<i class="fa" id="bucket-plus"></i>'+
                                                        '<span id="bucket-num"></span>'+
                                                        '<i class="fa " id="bucket-minus"></i>'+
                                                '</div>'+
                                                '<div class="col-3-1 fa" id="order-bucket-price">+'+combineGoods[i]['price']+'元</div>'+
                                        '</div>'+
                                    '</div>'+
                                '</div>';
                combineGoodsHtml = combineGoodsHtml + goodsHtml;
            }
            $('.content-wrap').html(orderHtmlall + combineGoodsHtml);
        
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });

});