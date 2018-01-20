(function(){
    function Cart(){
        this._goods = [];
        this._repaintCallback = false;
        this._allGoods = [];
    }
    Cart.prototype.setRepaintCallBack = function(callback){
        if(typeof callback == 'function'){
            this._repaintCallback = callback;
            return true;
        }
        return false;
    }
    Cart.prototype.repaintFromCache = function(){
        return this.repaint();
    }
    Cart.prototype.repaint = function(){
        try{
            this._goods = JSON.parse(localStorage['_cartGoods']);
            var goods = [];
            var total = 0;
            for(var i=0; i<this._goods.length; ++i){
                if(typeof this._goods[i] == 'object' && this._goods[i] != null){
                    goods.push(this._goods[i]);
                    total = total + parseFloat(this._goods[i]['subTotal']);
                }
            }
            total = parseFloat(total).toFixed(2);
        }catch(e){
            var goods = false;
            var total = false;
        }
        this._allGoods = goods;
        if(typeof this._repaintCallback == 'function'){
            this._repaintCallback(goods, total);
        }
    }
    Cart.prototype.getCartGoods = function(){
        return this._allGoods;
    }
    Cart.prototype.cacheCartGoods = function(){
        localStorage['_cartGoods'] = JSON.stringify(this._goods);
    }
    Cart.prototype.removeGoods = function(gid){
        gid = parseInt(gid);
        if(isNaN(gid) || 1 > gid){
            return false;
        }else{
            var index = -1;
            for(var i=0; i<this._goods.length; ++i){
                if(this._goods[i] != null && this._goods[i]['gid'] == gid){
                    index = i;
                    break;
                }
            }
            if(index != -1){
                this._goods[index] = null;
            }
            this.cacheCartGoods();
            this.repaint();
            return true;
        }
    }
    Cart.prototype.removeAllGoods = function(){
        this._goods = [];
        this.cacheCartGoods();
        this.repaint();
        return true;
    }
    // goods = {id:1, name:'商品名称', price:16.8, num:1}
    Cart.prototype.addGoods = function(goods){
        var gid = parseInt(goods['id']);
        gid = isNaN(gid) ? 0 : gid;
        if(0 == gid) return false;
        var name = goods['name'].trim();
        if(0 == name.length) return false;
        goods['price'] = goods['price'].replace('¥', '');
        var  price = parseFloat(goods['price']);
        price = isNaN(price) ? 0 : price;
        if(0.01 > price) return false;
        var num = parseInt(goods['num']);
        num = isNaN(num) ? 0 : num;
        var subTotal = parseFloat(num * price);
        var index = -1;
        for(var i=0; i<this._goods.length; ++i){
            if(this._goods[i] != null && this._goods[i]['gid'] == gid){
                index = i;
                break;
            }
        }
        if(num == 0){
            var goodsObj = null;
        }else{
            var goodsObj = {
                gid : gid,
                name : name,
                img : goods['img'],
                price : price,
                subTotal : subTotal.toFixed(2),
                num : num,
            };
        }
        if(index == -1){
            this._goods.push(goodsObj);
        }else{
            this._goods[index] = goodsObj;
        }
        this.cacheCartGoods();
        this.repaint();
        return true;
    }
    window.Cart = new Cart();
})();
// 商品数量的增加与减少,与购物车里的列表呼应
(function () {
    window.bindGoodsActionListnener = function(){
        $('.decrease').click(function(){
            var gid = parseInt($(this).data('gid'));
            var num = parseInt($('.goods-num[data-gid="'+gid+'"]').eq(0).html());
            num =  isNaN(num) ? 0 : num;
            num--;
            num = 0 > num ? 0 : num;
            if(num == 0){
                Cart.removeGoods(gid);
            }else{
                var goods = {
                    id : gid,
                    name : $('.gname[data-gid="'+gid+'"]').eq(0).html(),
                    img : $('.gimg[data-gid="'+gid+'"]').eq(0).attr('src'),
                    price : $('.gprice[data-gid="'+gid+'"]').eq(0).html(),
                    num : num,
                };
                Cart.addGoods(goods);
            }
        });
        $('.plus').click(function(){
            var gid = parseInt($(this).data('gid'));
            var num = parseInt($('.goods-num[data-gid="'+gid+'"]').eq(0).html());
            num =  isNaN(num) ? 0 : num;
            num++;
            var goods = {
                id : gid,
                name : $('.gname[data-gid="'+gid+'"]').eq(0).html(),
                img : $('.gimg[data-gid="'+gid+'"]').eq(0).attr('src'),
                price : $('.gprice[data-gid="'+gid+'"]').eq(0).html(),
                num : num,
            };
            Cart.addGoods(goods);
        });
    }
    window.unbindGoodsActionListnener = function(){
        $('.decrease').unbind("click");
        $('.plus').unbind("click");
    }
    
    // 购物车点击
    $("#cart-img-btn").click(function(){
        $(this).animate({  
            bottom: '13.5rem' 
        }, 200);
        $('#cart-panel-mask').fadeIn();
    });
    $('#cart-panel-mask').click(function(){
        $('#cart-panel-mask').fadeOut();
        $("#cart-img-btn").animate({  
            bottom: '0.5rem' 
        }, 200);
    });
    $('#cart-panel-window').click(function(e){
        e.preventDefault();
        return false;
    });
    // 清空全部
    $("#cart-truncate").click(function () {
        Cart.removeAllGoods();
        $('#cart-panel-mask').trigger('click');
    });

    // 去结算
    $('#cart-panel-go-pay').click(function(){
        var goods = Cart.getCartGoods();
        if(!goods || goods.length == 0){
            G.tips('您还没有选中任何商品...');
        }else{
            window.location.href = 'order-submit.html';
        }
    });
})();
