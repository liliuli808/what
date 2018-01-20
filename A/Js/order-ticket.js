$(function(){
	window.bindTicketItemClickListener = function(){
		$('.ticket-item').click(function(){
			if($(this).hasClass('ticket-unavaliabe')){
				return false;
			}else{

				//if($(this).data('only') == 1){
					$('.ticket-item').removeClass('ticket-selected');
				//}
				$('.ticket-item[data-only="1"]').removeClass('ticket-selected');
				$(this).toggleClass('ticket-selected');
			}
		});
		$('#order-ticket-finish-panel').click(function(){
			var data = [];
			for(var i=0; i<$('.ticket-selected').length; ++i){
				var gid = parseInt($('.ticket-selected').eq(i).data('gid')),
					tnum = parseInt($('.ticket-selected').eq(i).data('tnum')),
                    strategy = parseInt($('.ticket-selected').eq(i).data('strategy'));
				gid = isNaN(gid) ? 0 : gid;
				tnum = isNaN(tnum) ? 0 : tnum;
                strategy = isNaN(strategy) ? 0 : strategy;
				try{
					var cartGoods = JSON.parse(_cartGoods);
				}catch(e){
					var cartGoods = [];
				}
				var index = -1;
				var num = 0;
				for(var i=0; i<cartGoods.length; ++i){
					if(cartGoods[i]['goods_id'] == gid){
						index = i;
						num = parseInt(cartGoods[i]['goods_num']);
						num = isNaN(num) ? 0 : num;
						break;
					}
				}
				if(index != -1){
					data.push({gid:gid, strategy:strategy,num:Math.min(num, tnum)});
				}
			}
			if(data.length != 0){
				window.location.href = Api.U('order-submit.html', {ticket:JSON.stringify(data)});
			}else{
				$('#page-back').trigger('click');
			}
		});
	}

	var _cartGoods = Api.Get('goods');
    console.log(_cartGoods);
	if(_cartGoods){
		Api.Post(Api.getUrl('getOrderAvalibleTicketList'), {goods:_cartGoods}, function(apiRtn){
			console.log(apiRtn);
			if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
				var ticketHtml = '';
                for(var i=0; i<apiRtn['data'].length; ++i){
                	var ticket = apiRtn['data'][i];
                	var only = parseInt(ticket['only']);
			        console.log(only);
                	if(ticket['status'] == 0){
                		var html = '<div class="ticket-item ticket-unavaliabe">';
                	}else{
                		var html = '<div data-only="'+only+'" data-gid="'+ticket['goods_id']+'" data-tnum="'+ticket['left_num']+'" data-strategy="'+ticket['strategy_id']+'" class="ticket-item">';
                	}
                	html = html + '<div class="col-4-1">'+
			                '<img class="user-ticket-img" src="'+HOSTNAME+ticket['goods_img']+'">'+
			            '</div>'+
			            '<div class="col-4-3">'+
			                '<div class="row ticket-goods-name">'+ticket['goods_name']+'</div>'+
			                '<div class="row">'+
			                    '<div class="ticket-num-span" style="color:#999999;">有效期：'+ticket['end_time'].substring(0, 10)+'</div>';
			        var useNum = parseInt(ticket['use_num'].split(',')[0]);
			        useNum = isNaN(useNum) ? 1 : useNum;
			        useNum = 1 > useNum ? 1 : useNum;
			        if(only == 1){
			        	html = html + '<div class="ticket-num-span" style="color:red;">'+useNum+'桶起送 不可混用</div>';
			        }else{
			        	html = html + '<div class="ticket-num-span" style="color:red;">'+useNum+'桶起送 可混用</div>';
			        }
                   	html = html + '<div class="ticket-num">'+
			                        '<div class="small orange" data-total="'+ticket['ticket_num']+'" data-num="'+ticket['left_num']+'"></div>'+
			                    '</div>'+
			                '</div>'+
			            '</div>'+
			        '</div>';
			        ticketHtml = ticketHtml + html;
                }
                $('#order-ticket-panel').html(ticketHtml);
                window.bindTicketItemClickListener();
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
				G.tips('您目前还没有水票...');
            }
		});
	}else{
		G.tips('系统错误，请稍候再试...');
        setTimeout(function(){
            $('#page-back').trigger('click');
        }, 1000);
	}


		
});