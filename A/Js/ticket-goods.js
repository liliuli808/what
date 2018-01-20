$(function(){
    $('.ticket-slide-item').click(function(){
        var index = $(this).index();
        $('.ticket-slide-item').removeClass('ticket-slide-item-active').eq(index).addClass('ticket-slide-item-active');
        $('.ticket-slide-container').removeClass('ticket-slide-container-active').eq(index).addClass('ticket-slide-container-active');
        $('title').html($(this).html());
        $('#page-title').html($(this).html());
    });
    if(Api.Get('index') == 1){
        $('.ticket-slide-item').trigger('click');
    }
    if(Api.Get('from')){
            $('#page-back').data('url', Api.Get('from'));
        }
    Api.Post(Api.getUrl('getTicketGoodsList'), {}, function(apiRtn){
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var ticketGoods = apiRtn['data'];
            var ticketGoodsHtml = '';
            for(var key in ticketGoods){
                try{
                    var goods = ticketGoods[key];
                    var ticketHtml = '<div class="ticket-goods-item data-url" data-url="'+Api.U('ticket-detail.html', {id:goods['id']})+'">'+
                        '<div class="col-4-1">'+
                            '<img src="'+HOSTNAME+goods['img']+'">';
                    if(goods['is_active'] == 1){
                        ticketHtml = ticketHtml + '<div class="ticket-img-desc">活动中</div>';
                    }
                    ticketHtml = ticketHtml + '</div>'+
                        '<div class="col-4-3">'+
                            '<div class="row">'+goods['name']+'</div>'+
                            '<div class="row">'+
                                '<span class="goods-price">原价:'+goods['price']+'/桶</span>'+
                                '<span class="goods-strategy">&nbsp;'+goods['ticket'][0]['ticket_name']+'</span>'+
                            '</div>'+
                            '<div class="row">'+
                                '<span class="goods-purchase data-url" data-url="ticket-goods-detail.html">购买</span>'+
                            '</div>'+
                        '</div>'+
                    '</div>';
                    ticketGoodsHtml = ticketGoodsHtml + ticketHtml;
                }catch(e){
                    continue;
                }
            }
            $('#ticket-goods-container').html(ticketGoodsHtml);
            $('.data-url').unbind('click');
            window.bindDataUrlClickAction();
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });
    Api.Post(Api.getUrl('getUserTickets'), {}, function(apiRtn){
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var ticketGoods = apiRtn['data'];
            var ticketGoodsHtml = '';
            for(var i=0; i<ticketGoods.length; ++i){
                try{
                    var goods = ticketGoods[i];
                    var ticketHtml = '<div class="ticket-item">'+
                        '<div class="col-5-1" style="height:4.2rem;">'+
                            '<img class="user-ticket-img" src="'+HOSTNAME+goods['goods_img']+'">'+
                        '</div>'+
                        '<div class="col-5-3" style="height:4.2rem;">'+
                            '<div class="row ticket-goods-name">'+goods['goods_name']+'</div>'+
                            '<div class="row">'+
                                '<span class="ticket-num-span">数量:'+goods['left_num']+'/'+goods['ticket_num']+'</span>'+
                                // '<span class="ticket-use data-url">立即使用</span>'+
                            '</div>'+
                        '</div>'+
                        '<div class="col-5-1" style="height:4.2rem;">'+
                            '<div class="ticket-num">'+
                                '<div class="small orange" data-total="'+goods['ticket_num']+'" data-num="'+goods['left_num']+'"></div>'+
                            '</div>'+
                        '</div>'+
                    '</div>';
                    ticketGoodsHtml = ticketGoodsHtml + ticketHtml;
                }catch(e){
                    continue;
                }
            }
            $('#my-ticket-container').html(ticketGoodsHtml);
            $('.ticket-num').each(function(i, obj){
                var total = $(obj).find('div').eq(0).data('total');
                var num = $(obj).find('div').eq(0).data('num');
                $(obj).find('div').eq(0).percircle({
                    text : num+'/'+total,
                    percent: num * 100 / total
                });
                $(obj).unbind('click');
            });
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });
});