$(function(){

    Api.Post(Api.getUrl('getUserStationInfo'),{ }, function(apiRtn){
        console.log(apiRtn['data']);
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var station =JSON.parse(apiRtn['data']['data']);
            var goodsHtml = '';
            for(var i=0; i<station.length; ++i){
                var html = '<option value="'+station[i]['station_id']+'">'+station[i]['desc']+'</option>';
                goodsHtml = goodsHtml + html;
            }
            $("#station_id").html(goodsHtml);

        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });

    $('#deliver-bind-btn').click(function(){
        var name = $('#deliver-name').val().trim();
        var tel = $('#deliver-tel').val().trim();
        var station_id = $("#station_id").val();
        if(name.length == 0){
            G.tips('水工姓名不能为空！');
            return false;
        }
        if(tel.length == 0 || !G.testCellphoneNo(tel)){
            G.tips('水工电话格式错误！');
            return false;
        }
        if(station_id == 0){
            G.tips('所属水站没有选择！');
            return false;
        }
        Api.Post(Api.getUrl('bindDeliver'), {name:name,tel:tel,station_id:station_id}, function(apiRtn){
            G.tips(apiRtn['msg']);
        });
    });
});