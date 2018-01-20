$(function(){
	var _strategyId = 0, _addressId = 0;
	function addCombineGoodsActionListener(){
		$('.combine-goods-item').click(function(){
			$('.fa-dot-circle-o').addClass('fa-circle-o').removeClass('fa-dot-circle-o');
			$(this).find('.col-5-1').find('i').addClass('fa-dot-circle-o').removeClass('fa-circle-o');
			$('#cg-purchase-panel').animate({
				bottom : 0
			}, 400);
			_strategyId = $(this).find('.col-5-1').find('i').data('cgid');
		});
	}

    if(Api.Get('aid')){
        Api.Post(Api.getUrl('getUserAddressInfo'), {address_id:Api.Get('aid')}, function(apiRtn){
            if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
                var address = apiRtn['data'];
                _addressId = address['id'];
            }
        });
    }else{
        Api.Post(Api.getUrl('getDefaultAddress'), {}, function(apiRtn){
            if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
                var address = apiRtn['data'];
                _addressId = address['id'];
            }
        });
    }
	
	Api.Post(Api.getUrl('getCombineGoodsList'), {}, function(apiRtn){
		console.log(apiRtn);
		if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var combineGoods = apiRtn['data'];
        	var combineGoodsHtml = '';
            for(var i=0; i<combineGoods.length; ++i){
            	var goodsHtml = '<div class="combine-goods-item" data-cgid="'+combineGoods[i]['id']+'">'+
			        '<div class="row cg-item-goods-name">'+
			            '<div class="col-5-4">'+combineGoods[i]['name']+'</div>'+
			            '<div class="col-5-1"><i class="fa fa-circle-o" data-cgid="'+combineGoods[i]['id']+'"></i></div>'+
			        '</div>'+
			        '<div class="row cg-goods">'+
			            '<div class="cg-goods-left">'+
			                '<div class="cg-goods-img col-5-2">'+
			                    '<img src="'+HOSTNAME+combineGoods[i]['package'][0]['goods_img']+'">'+
			                '</div>'+
			                '<div class="cg-goods-name col-5-3">'+combineGoods[i]['package'][0]['goods_name']+'</div>'+
			                '<div class="cg-goods-num col-5-3">x'+combineGoods[i]['package'][0]['num']+'</div>'+
			            '</div>'+
			            '+'+
			            '<div class="cg-goods-right">'+
			                '<div class="cg-goods-img col-5-2">'+
			                    '<img src="'+HOSTNAME+combineGoods[i]['package'][1]['goods_img']+'">'+
			                '</div>'+
			                '<div class="cg-goods-name col-5-3">'+combineGoods[i]['package'][1]['goods_name']+'</div>'+
			                '<div class="cg-goods-num col-5-3">x'+combineGoods[i]['package'][1]['num']+'</div>'+
			            '</div>'+
			        '</div>'+
			        '<div class="row cg-goods-price">'+
			            '<span>仅需：¥<span class="combine-goods-price">'+combineGoods[i]['price']+'</span></span>'+
			        '</div>'+
			    '</div>';
			    combineGoodsHtml = combineGoodsHtml + goodsHtml;
            }
            $('#cg-goods-end').before(combineGoodsHtml);
            addCombineGoodsActionListener();
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
	});

	$('#cg-purchase-panel').click(function(e){
		if(1 > _strategyId){
			G.tips('请先选择要购买的套餐');
			return false;
		}
        if(1 > _addressId){
            G.confirm({
                tips : '还未选择收货地址，是否现在去选择？',
                confirmBtn : '去选择',
                cancelBtn : '算了',
                cancel : function(){
                   // alert('cancel');
                },
                confirm : function(){
                    //alert('confirm');
                    window.location.href = Api.U('address-list.html', {from:'combine-goods.html', sgid:_strategyId});
                }
            });
            return false;
        }
		var data = {
			type : 2,
			address_id : _addressId,
			strategy_id : _strategyId,
		};
		var submiting = G.tips('订单提交中，请稍等...', 999999);
		Api.Post(Api.getUrl('combineOrderSubmit'), data, function(apiRtn){
			submiting.remove();
			if(apiRtn['code'] == 0){
				G.tips('订单提交成功！');
				Cache.deleteApiCache('getUserTickets');
				Cache.deleteApiCache('getOrderList');
				setTimeout(function(){
                    window.location.href = Api.U('order-list.html');
                }, 1000);
			}else{
	            G.tips('系统错误，请稍后再试...');
	        }
		});
	});
});