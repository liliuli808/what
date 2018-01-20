$(function(){
    var aid = Api.Get('aid');
    if(1 > aid){
        G.tips('系统错误，请稍后再试...');
        setTimeout(function(){
            $('#page-back').trigger('click');
        }, 1000);
    }

    var _point = '0,0';
    var _rangePolygon = '';
    var _name = '';
    var ac;
    var _station_id =0;
    var checks=0;
    (function(){
        // 百度地图API功能
        function A(id) {
            return document.getElementById(id);
        }

        var map = new BMap.Map("l-map");
        map.centerAndZoom("北京",12);                   // 初始化地图,设置城市和地图级别。

        ac = new BMap.Autocomplete(    //建立一个自动完成的对象
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
            A("searchResultPanel").innerHTML = str;
        });

        var myValue;

        ac.addEventListener("onconfirm", function(e) {    //鼠标点击下拉列表后的事件
            var _value = e.item.value;
            myValue = _value.province +  _value.city +  _value.district +  _value.street +  _value.business;
            A("searchResultPanel").innerHTML = myValue;
            setPlace();
            var myGeo = new BMap.Geocoder();
            myGeo.getPoint(myValue, function(point){
                if (point) {
                    map.centerAndZoom(point, 11);
                    map.addOverlay(new BMap.Marker(point));
                    console.log(point);
                    console.log(_point);

                    myGeo.getLocation(point, function(rs){
                        var addComp = rs.addressComponents;
                        var _in = pointInStationRange(point);
                        //console.log(_in);
                        if(_in){
                            var _inStation = '';
                            var orderHtml = '<div class="tishi"><span>提示</span></div>'+'<div id="btnGroupss">您当前收货地址有以下水站，请选择</div>';
                            for(var i=0; i<_in.length; ++i){
                                _inStation = _inStation +_in[i]['name']+"\r\n";
                                // alert(_in[i]['id']);
                                _station_id=_in[i]['id'];
                                var html ='<div class="dizhi" onclick="checkshui('+_station_id+')">'
                                    +_in[i]['name']+
                                    '</div>';
                                orderHtml = orderHtml + html;
                            }
                            $('#shows').html(orderHtml);
                            checks=1;

                            //alert('您选择的位置是:\r\n'+addComp.province+'-'+addComp.city+'-'+addComp.district+'-'+addComp.street+'-'+addComp.streetNumber+'\r\n 在下列门店服务范围：\r\n'+_inStation);
                            $("#shows").show();
                            $("#bgs").show();
                        }else{
                            //alert('2');
                            var html = '<div class="tishi">'+
                                '<span>提示</span>'+
                                '</div>'+
                                '<div id="shuizhan" >您的收货地址不在本店的配送范围，请您联系客服或更改地址</div>'+
                                '<div class="anniu" onclick="quxiao()">知道了</div>';
                            $('#shows').html(html);
                            $("#shows").show();
                            $("#bgs").show();
                            //alert('您选择的位置是:\r\n'+addComp.province+'-'+addComp.city+'-'+addComp.district+'-'+addComp.street+'-'+addComp.streetNumber+'\r\n不在任何门店服务范围！');
                            checks=0;
                        }
                    });
                }
            });
        });

        function pointInStationRange(point){
            var stations = [];
            for(var i=0; i<_rangePolygon.length; ++i){
                var _id = _rangePolygon[i]['station_id'],
                    _name = _rangePolygon[i]['desc'],
                    _range = _rangePolygon[i]['range'];
                for(var j=0; j<_range.length; ++j){
                    var points = [];
                    for(var k=0; k<_range[j].length; ++k){
                        points.push(new BMap.Point(_range[j][k]['lng'], _range[j][k]['lat']));
                    }
                    var polygon = new BMap.Polygon(points, {strokeColor:"blue", strokeWeight:2, strokeOpacity:0.5});
                    map.addOverlay(polygon);
                    var result = BMapLib.GeoUtils.isPointInPolygon(point, polygon);
                    if(result){
                        stations.push({id:_id, name:_name});
                    }
                }
            }
            return stations.length > 0 ? stations : false;
        }


        function setPlace(){
            map.clearOverlays();    //清除地图上所有覆盖物
            function myFun(){
                var pp = local.getResults().getPoi(0).point;    //获取第一个智能搜索的结果
                console.log(pp);
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




    // 1. 获取地址信息
    Api.Post(Api.getUrl('getUserAddressInfo'), {address_id:Api.Get('aid')}, function(apiRtn){
        console.log(apiRtn['data']);
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var address = apiRtn['data'];
            $('#address-name').val(address['name']);
            $('#address-name').attr('placeholder', '您的姓名');
            $('#address-tel').val(address['tel']);
            $('#address-tel').attr('placeholder', '您的电话');
            $('#address-addr').val(address['pcd']);
            $('#address-detail').html(address['detail']);
            ac.setInputValue(address['pcd']);
            _point = address['gps'];
            _rangePolygon =JSON.parse(apiRtn['data']['data']);

        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });

    $('#address-detail').keydown(function(e){
        if(e.which == 13){
            return false;
        }
    });
    
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
        var detail = $('#address-detail').html().trim();
        if(0 == detail.length){
            G.tips('详细地址不能为空！');
            return false;
        }
        if(checks == 0){
            G.tips('选择的地址不在配送范围');
            return false;
        }
        $(this).html('提交中...');
        var tips = G.tips('地址提交中，请稍等...', 999999);
        Api.Post(Api.getUrl('saveAddress'), {
            address_id : Api.Get('aid'),
            name : $('#address-name').val(),
            tel : $('#address-tel').val(),
            pcd : $('#address-addr').val(),
            detail : $('#address-detail').html(),
            gps : _point,
            station_id :$("#station").val()
        },function(apiRtn){
            tips.remove();
            if(apiRtn['code'] == 0){
                G.tips('保存成功！');
                Cache.deleteApiCache('getUserAddressList');
                Cache.deleteApiCache('getDefaultAddress');
                Cache.deleteApiCache('getUserAddressInfo');
                setTimeout(function(){
                    $('#page-back').trigger('click');
                }, 1000);
            }else{
                G.tips('系统错误，请稍后再试...');
            }
            $('#address-save').html('确认');
        });
    });
});

function checkshui(e){
    $("#shows").hide();
    $("#bgs").hide();
    $("#station").val(e);
}
function quxiao(){
    $("#shows").hide();
    $("#bgs").hide();
    location.reload();
}





