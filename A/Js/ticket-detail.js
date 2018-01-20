$(function(){
    var id = Api.Get('id');
    var _combineOrderData = {
        type : 1,
        address_id : 0,
        strategy_id : 0,
    };

    if(Api.Get('aid')){
        Api.Post(Api.getUrl('getUserAddressInfo'), {address_id:Api.Get('aid')}, function(apiRtn){
            if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
                var address = apiRtn['data'];
                _combineOrderData['address_id'] = address['id'];
            }
        });
    }else{
        Api.Post(Api.getUrl('getDefaultAddress'), {}, function(apiRtn){
            if(apiRtn['code'] == 0 && apiRtn['data'].length != 0){
                var address = apiRtn['data'];
                _combineOrderData['address_id'] = address['id'];
            }
        });
    }

    if(1 > id){
        G.tips('系统错误，请稍后再试...');
        setTimeout(function(){
            $('#page-back').trigger('click');
        }, 1000);
    }else{
        Api.Post(Api.getUrl('getTicketGoodsDetail'), {goods_id:id}, function(apiRtn){
            if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
                var data = apiRtn['data'];
                $('#ticket-goods-name').html(data['name']);
                $('#ticket-goods-price').html(data['price']);
                var strategyHtml = '';
                for(var i=0; i<data['ticket'].length; ++i){
                    var strategy = data['ticket'][i];
                    var html = '<div class="ticket-strategy">'+
                        '<div class="col-5-3">'+
                            '<div class="strategy-name">'+strategy['ticket_name']+'</div>'+
                            '<div class="strategy-gift">水票x'+strategy['num']+'&nbsp;(赠品:水票x'+strategy['givenum']+')</div>'+
                        '</div>'+
                        '<div class="col-10-3">¥<span class="strategy-price" data-sid="'+strategy['ticket_id']+'">'+strategy['ticket_price']+'</span></div>'+
                        '<div class="col-10-1"><i class="fa fa-circle-o" data-sid="'+strategy['ticket_id']+'"></i></div>'+
                    '</div>';
                    strategyHtml = strategyHtml + html;
                }
                $('#ticket-detail-panel').append(strategyHtml);
                addStrategyActionListener();
            }else{
                G.tips('系统错误，请稍后再试...');
                setTimeout(function(){
                    $('#page-back').trigger('click');
                }, 1000);
            }
        });
    }
    function addStrategyActionListener(){
        $('.ticket-strategy').click(function(e){
            var sid = parseInt($(this).find('i').data('sid'));
            sid = isNaN(sid) ? 0 : sid;
            if(0 == sid){
                e.preventDefault();
                return false;
            }
            _combineOrderData['strategy_id'] = sid;
            $('.fa-dot-circle-o').addClass('fa-circle-o').removeClass('fa-dot-circle-o');
            $(this).find('i').addClass('fa-dot-circle-o').removeClass('fa-circle-o');
            $('#select-strategy-price').html('¥'+$('.strategy-price[data-sid="'+sid+'"]').html());
        });
    }
    $('#ticket-pay-btn').click(function(e){
        var addressId = parseInt(_combineOrderData['address_id']);
        addressId = isNaN(addressId) ? 0 : addressId;
        if(1 > addressId){
            G.confirm({
                tips : '还未选择收货地址，是否现在去选择？',
                confirmBtn : '去选择',
                cancelBtn : '算了',
                cancel : function(){
                    // alert('cancel');
                },
                confirm : function(){
                    // alert('confirm');
                    window.location.href = Api.U('address-list.html', {from:'ticket-detail.html', gid:id});
                }
            });
            return false;
        }
        var strategyId = parseInt(_combineOrderData['strategy_id']);
        strategyId = isNaN(strategyId) ? 0 : strategyId;
        if(1 > strategyId){
            G.tips('请先选择购买的水票');
            return false;
        }
        var submiting = G.tips('订单提交中...', 999999);
        Api.Post(Api.getUrl('combineOrderSubmit'), _combineOrderData, function(apiRtn){
            submiting.remove();
            console.log(apiRtn);
            if(apiRtn['code'] == 0){
                G.tips('订单提交成功！');
                setTimeout(function(){
                    window.location.href = Api.U('order-detail.html', {oid: apiRtn['data']['order_id']})
                }, 1000);
            }else{
                G.tips('系统错误，请稍后再试...');
                setTimeout(function(){
                    $('#page-back').trigger('click');
                }, 1000);
            }
        });
        e.preventDefault();
        return false;
    });
});