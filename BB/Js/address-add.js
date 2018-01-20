$(function(){
    var _point = '0,0';
    (function(){
        // 百度地图API功能
        function G(id) {
            return document.getElementById(id);
        }

        var map = new BMap.Map("l-map");
        map.centerAndZoom("北京",12);                   // 初始化地图,设置城市和地图级别。

        var ac = new BMap.Autocomplete(    //建立一个自动完成的对象
            {"input" : "address-addr"
            ,"location" : map
        });

        ac.addEventListener("onhighlight", function(e) {  //鼠标放在下拉列表上的事件
            var str = "";
            var _value = e.fromitem.value;
            var value = "";
            if (e.fromitem.index > -1) {
                value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            }    
            str = "FromItem<br />index = " + e.fromitem.index + "<br />value = " + value;
            
            value = "";
            if (e.toitem.index > -1) {
                _value = e.toitem.value;
                value = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            }    
            str += "<br />ToItem<br />index = " + e.toitem.index + "<br />value = " + value;
            G("searchResultPanel").innerHTML = str;
        });

        var myValue;
        ac.addEventListener("onconfirm", function(e) {    //鼠标点击下拉列表后的事件
            var _value = e.item.value;
            myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            G("searchResultPanel").innerHTML = myValue;
            
            setPlace();
        });

        function setPlace(){
            map.clearOverlays();    //清除地图上所有覆盖物
            function myFun(){
                var pp = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
                _point = pp.lng+','+pp.lat;
                map.centerAndZoom(pp, 18);
                map.addOverlay(new BMap.Marker(pp));    //添加标注
            }
            var local = new BMap.LocalSearch(map, { //智能搜索
                onSearchComplete: myFun
            });
            local.search(myValue);
        }
    })();

    $('#address-detail').keydown(function(e){
        if(e.which == 13){
            return false;
        }
    });
    
    if(Api.Get('from')){
        $('#page-back').data('url', Api.Get('from')+"#id="+Api.Get('gid'));
    }
    // 2. 绑定事件回调
    $('#address-save').click(function(){
        var name = $('#address-name').val().trim();
        if(0 == name.length){
            G.tips('联系人不能为空！');
            return false;
        }
        var tel = $('#address-tel').val().trim();
        if(0 == tel.length || !G.testCellphoneNo(tel)){
            G.tips('联系电话格式不正确！');
            return false;
        }
        var addr = $('#address-addr').val().trim();
        if(0 == addr.length || _point == '0,0'){
            G.tips('收货地址不合法！');
            return false;
        }
        var detail = $('#address-detail').val().trim();
        if(0 == detail.length || detail == '尽可能详细'){
            G.tips('详细地址不能为空！');
            return false;
        }
        $(this).html('提交中...');
        var tips = G.tips('地址提交中，请稍等...', 999999);
        Api.Post(Api.getUrl('addAddress'), {
            name : name,
            tel : tel,
            pcd : addr,
            detail : detail,
            gps : _point,
        },function(apiRtn){
            tips.remove();
            if(apiRtn['code'] == 0){
                G.tips('保存成功！');
                Cache.deleteApiCache('getUserAddressList');
                Cache.deleteApiCache('getDefaultAddress');
                setTimeout(function(){
                    $('#page-back').trigger('click');
                }, 1000);
            }else{
                G.tips('系统错误，请稍后再试...');
            }
            $('#address-save').html('提交');
        });
    });
});
