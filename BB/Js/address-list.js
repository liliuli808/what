$(function(){
    // 2. 绑定事件监听
    window.addAddressActionListener = function(){
        $('.set-default').click(function(){
            var aid = $(this).find('i').data('aid'),
                $this = $(this);
            Api.Post(Api.getUrl('setDefaultAddress'), {address_id : aid}, function(apiRtn){
                if(apiRtn['code'] == 0){
                    G.tips('设置默认地址成功！');
                    $('.address-default').html('<i class="fa fa-circle-o" data-aid="'+$('.address-default').eq(0).find('i').data('aid')+'"></i>设为默认').removeClass('address-default').addClass('set-default');
                    $('.fa-dot-circle-o').removeClass('fa-dot-circle-o').addClass('fa-circle-o');
                    $this.addClass('address-default');
                    $this.html('<i class="fa fa-dot-circle-o" data-aid="'+aid+'"></i>默认地址');
                    window.unbindAddressActionListener();
                    window.addAddressActionListener();
                    Cache.deleteApiCache('getUserAddressList');
                    Cache.deleteApiCache('getDefaultAddress');
                }else{
                    G.tips('系统错误，请稍后再试...');
                }
            });
        });
        $('.edit-address').click(function(e){
            var aid = $(this).find('i').data('aid');
            var url = Api.U('address-edit.html', {aid:aid});
            window.location.href = url;
            e.preventDefault();
            return false;
        });
        $('.delete-address').click(function(e){
            var aid = parseInt($(this).find('i').data('aid'));
            aid = isNaN(aid) ? 0 : aid;
            if(aid == 0){
                G.tips('系统错误，请稍后再试...');
                return false;
            }
            Api.Post(Api.getUrl('deleteAddress'), {address_id:aid}, function(apiRtn){
                if(apiRtn['code'] == 0){
                    G.tips('删除成功！');
                    Cache.deleteApiCache('getUserAddressList');
                    Cache.deleteApiCache('getDefaultAddress');
                    $('.address-item[data-aid="'+aid+'"]').remove();
                }else{
                    G.tips('系统错误，请稍后再试...');
                }
            });
            return false;
        });

        if(Api.Get('from')){
            
            $('.address-item').click(function(){
                var aid = parseInt($(this).data('aid'));
                aid = isNaN(aid) ? 0 : aid;
                if(aid > 0){
                    //window.location.href = Api.U('order-submit.html', {aid:aid});
                    window.location.href = Api.U(Api.Get('from'), {aid:aid,id:Api.Get('gid')});
                }
            });
            $('#page-back').data('url', Api.Get('from')+"#id="+Api.Get('gid'));
        }
    }
    window.unbindAddressActionListener = function(){
        $('.set-default').unbind('click');
        $('.edit-address').unbind('click');
    }
    // 1. 获取地址列表
    var addressListCacheId = Api.Post(Api.getUrl('getUserAddressList'), {}, function(apiRtn){
        var addressList = apiRtn['data'];
        var addressHtml = '';
        if(!isNaN(parseInt(addressList.length)) && parseInt(addressList.length) != 0){
            $('#no-address').hide();
            for(var i=0; i<addressList.length; ++i){
                var address = addressList[i],
                    html = '<div class="address-item" data-aid="'+address['id']+'">'+
                        '<div class="address-info" class="row">'+
                            '<div class="row">'+
                                '<div class="col-10-3">'+
                                    '<i class="fa fa-user"></i>'+address['name']+
                                '</div>'+
                                '<div class="col-5-3">'+
                                    '<i class="fa fa-phone"></i>'+address['tel']+
                                '</div>'+
                            '</div>'+
                            '<div class="row">'+
                                '<i class="fa fa-map-marker"></i>'+address['pcd']+'-'+address['detail']+
                            '</div>'+
                        '</div>'+
                        '<div class="address-operate row">';
                if(address['status'] == 1){
                    html = html + '<div class="col-5-3 address-default">'+
                        '<i class="fa fa-dot-circle-o" data-aid="'+address['id']+'"></i>默认地址'+
                    '</div>';
                }else{
                    html = html + '<div class="col-5-3 set-default">'+
                        '<i class="fa fa-circle-o" data-aid="'+address['id']+'"></i>设为默认'+
                    '</div>';
                }
                html = html + '<div class="col-5-1 edit-address">'+
                            '<i class="fa fa-edit" data-aid="'+address['id']+'"></i>编辑'+
                        '</div>'+
                        '<div class="col-5-1 delete-address">'+
                            '<i class="fa fa-trash" data-aid="'+address['id']+'"></i>删除'+
                        '</div>'+
                    '</div>'+
                '</div>';
                addressHtml = addressHtml + html;
            }
            $('#address-panel').html(addressHtml);
        }
        if(!isNaN(parseInt(addressList.length)) && parseInt(addressList.length) == 0){
            var gid = isNaN(parseInt(Api.Get('gid'))) ? 0 : Api.Get('gid');
            $('#address-add').attr('href', "address-add.html#from="+Api.Get('from')+"&gid="+gid);
        }
        window.addAddressActionListener();
    });
});