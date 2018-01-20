(function () {
    // 定位当前位置
    if(Cache.get('_position')){
        var _positionData = Cache.get('_position');
        $('#index-top-user-position').find('span').html(_positionData['dsn']);
    }else{
        (function(){
            var map = new BMap.Map("allmap");
            var point = new BMap.Point(116.331398,39.897445);
            map.centerAndZoom(point, 12);
            var geolocation = new BMap.Geolocation();
            var _positionData = {
                gps : '',
                address : '',
                dsn : '',
            };
            geolocation.getCurrentPosition(function(r){
                if(this.getStatus() == BMAP_STATUS_SUCCESS){
                    _positionData['gps'] = r.point.lat+','+r.point.lng;
                    var geoc = new BMap.Geocoder();
                    geoc.getLocation(r.point, function(rs){
                        // 获取用户文字地理位置
                        var addComp = rs.addressComponents;
                        _positionData['address'] = addComp.city+'-'+addComp.district+'-'+addComp.street+'-'+addComp.streetNumber;
                        _positionData['dsn'] = addComp.district+addComp.street+addComp.streetNumber;;
                        $('#index-top-user-position').find('span').html(_positionData['dsn']);
                        Api.Post(Api.getUrl('saveUserGps'), _positionData, function(apiRtn){
                            if(apiRtn['code'] == 0){
                                Cache.cache('_position', _positionData, 3600);
                            }
                        });
                    });
                }else{
                    G.tips('定位失败！');
                }        
            },{enableHighAccuracy: true});
        })();
    }
        

    window.bindIndexCateGoodsActionListener = function(){
        var halfWindowWidth = parseInt($(window).width() / 2);
        $(".index-goods-cate").on('click', function(){
            var index = $(this).index();
            $(this).addClass("index-goods-cate-active").siblings().removeClass("index-goods-cate-active");
            $('.index-goods-panel').removeClass("index-goods-panel-active");
            $('.index-goods-panel').eq(index).addClass("index-goods-panel-active");

            var thisMiddle = 0;
            for(var i=0; i<index; ++i){
                thisMiddle += $(".index-goods-cate").eq(i).width();
            }
            thisMiddle += parseInt($(this).width() / 2);
            var scrollWidth = parseInt(thisMiddle - halfWindowWidth);
            $(this).parent().parent().eq(0).animate({  
                scrollLeft: scrollWidth 
            }, 500);
        })
    }
})();
$(function(){
    Api.Post(Api.getUrl('getCarousel'), {type : 0}, function(data){
        var carousels = data['data'];
        var carouselHtml = '';
        for(var i=0; i<carousels.length; ++i){
            var html = '<a href="'+carousels[i]['url']+'" class="index-carousel-item">'+
                '<img src="'+HOSTNAME+carousels[i]['img']+'" />'+
            '</a>';
            carouselHtml = carouselHtml + html;
        }
        setTimeout(function(){
            if(0 != carouselHtml.length){
                $('#index-carousel').html(carouselHtml);
            }
            $('#index-carousel').carousel({
                scrollDuration : 3000
            });
        }, 50);
    });
    Api.Post(Api.getUrl('getGoodsList'), {sort_by_cate : 1}, function(data){
        var cateGoods = data['data']['goods'],
            cateHtmlTotal = '',
            cateGoodsHtmlTotal = '';

        for(var ci=0; ci<=cateGoods.length-1; ++ci){
            if(ci == 0){
                var cateHtml = '<div class="index-goods-cate index-goods-cate-active">'+
                    '<span>'+cateGoods[ci]['cate_name']+'</span>'+
                '</div>'
                var cateGoodsHtml = '<div class="index-goods-panel index-goods-panel-active">';
            }else{
                var cateHtml = '<div class="index-goods-cate">'+
                    '<span>'+cateGoods[ci]['cate_name']+'</span>'+
                '</div>'
                var cateGoodsHtml = '<div class="index-goods-panel">';
            }
            cateHtmlTotal = cateHtmlTotal + cateHtml;
            for(var cgi=0; cgi<=cateGoods[ci]['cate_goods'].length-1; ++cgi){
                var goods = cateGoods[ci]['cate_goods'][cgi];
                var goodsHtml = '<div class="index-goods-item">'+
                    '<div class="col-10-3 goods-img">'+
                        // '<a href="'+Api.U('goods-detail.html', {gid:goods['goods_id']})+'">'+
                            '<img data-gid="'+goods['goods_id']+'" class="gimg" src="'+HOSTNAME+goods['img']+'">'+
                        // '</a>'+
                    '</div> '+
                    '<div class="col-10-7 goods-info">'+
                        '<div class="goods-name gname" data-gid="'+goods['goods_id']+'">'+goods['name_utf8_10']+'</div>'+
                        '<div class="goods-unit">'+goods['standard']+'/'+goods['unit']+'</div>'+
                        '<div class="goods-sales">销量：'+goods['sales']+'</div>'+
                        '<div class="goods-desc">'+
                            '<span class="goods-price gprice" data-gid="'+goods['goods_id']+'">¥'+goods['price']+'</span>'+
                            '<div class="goods-operate">'+
                                '<i class="fa fa-minus decrease" data-gid="'+goods['goods_id']+'"></i>'+
                                '<span class="goods-num" data-gid="'+goods['goods_id']+'">0</span>'+
                                '<i class="fa fa-plus plus" data-gid="'+goods['goods_id']+'"></i>'+
                            '</div>'+
                        '</div>'+
                    '</div>'+
                '</div>';
                cateGoodsHtml = cateGoodsHtml + goodsHtml;
            }
            cateGoodsHtml = cateGoodsHtml + '</div>';
            cateGoodsHtmlTotal = cateGoodsHtmlTotal + cateGoodsHtml;
        }
        $('#index-goods-cates').find('div.horizontal-scroll-container').html(cateHtmlTotal);
        $('#index-cate-goods-container').html(cateGoodsHtmlTotal);
        bindIndexCateGoodsActionListener();
        Cart.setRepaintCallBack(function(goods, totalPrice){
            unbindGoodsActionListnener();
            $('.goods-num').html('0');
            $('#cart-img-btn span').html('0');
            $('#cart-panel-goods').html('');
            var cartHtml = '';
            // 重置首页商品数量
            for(var i=0; i<goods.length; ++i){
                $('.goods-num[data-gid="'+goods[i]['gid']+'"]').html(goods[i]['num']);
                var cartGoodsHtml = '<div class="cart-panel-goods-item">'+
                    '<div class="cart-goods-name gname" data-gid="'+goods[i]['gid']+'">'+goods[i]['name']+'</div>'+
                    '<div class="cart-goods-subtotal">¥'+goods[i]['subTotal']+'</div>'+
                    '<div class="cart-goods-operate">'+
                        '<i class="fa fa-minus decrease" data-gid="'+goods[i]['gid']+'"></i>'+
                        '<span class="goods-num" data-gid="'+goods[i]['gid']+'">'+goods[i]['num']+'</span>'+
                        '<i class="fa fa-plus plus" data-gid="'+goods[i]['gid']+'"></i>'+
                    '</div>'+
                '</div>';
                cartHtml = cartHtml + cartGoodsHtml;
            }
            $('#cart-panel-goods').html(cartHtml);
            $('#cart-panel-total span').html('¥ '+totalPrice);
            $('#cart-img-btn span').html(goods.length);
            if(goods.length == 0){
                $('#cart-img-btn span').hide();
            }else{
                $('#cart-img-btn span').show();
            }
            bindGoodsActionListnener();
        });
        Cart.repaintFromCache();
        $('body').animate({  
            scrollTop: 0 
        }, 500);
    });
});
