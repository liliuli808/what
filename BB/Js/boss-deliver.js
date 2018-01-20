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

	Api.Post(Api.getUrl('bossDeliver'), {
		is_page : 1,
        page : 1,
        pagesize : 30,
        orderid:Api.Get('orderid'),
	}, function(apiRtn){
		console.log(apiRtn);
		if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var combineGoods = apiRtn['data']['deliver'];
            var orderid = apiRtn['data']['orderid'];
        	var combineGoodsHtml = '';
            for(var i=0; i<combineGoods.length; ++i){
            	var goodsHtml = '<div class="combine-goods-item" data-cgid="'+combineGoods[i]['id']+'">'+
			        '<div class="row cg-item-goods-name">'+
			            '<div class="col-5-4">'+combineGoods[i]['name']+'</div>'+
			            '<div class="col-5-1"><i class="fa fa-circle-o" data-cgid="'+combineGoods[i]['id']+'" data-orderid="'+orderid+'"></i></div>'+
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
		if(1 > _deliverId){
			G.tips('请先选择要派单的水工');
			return false;
		}
		var data = {
			deliver_id : _deliverId,
			orderid:_orderId,
		};
		var submiting = G.tips('派单中，请稍等...', 999999);
		Api.Post(Api.getUrl('bossDeliverSubmit'), data, function(apiRtn){
			submiting.remove();
			if(apiRtn['code'] == 0){
				G.tips('水工派单成功！');
				setTimeout(function(){
                    window.location.href = Api.U('order-list.html');
                }, 1000);
			}else{
	            G.tips('系统错误，请稍后再试...');
	        }
		});
	});
});