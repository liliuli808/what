$(function(){
    var _bucketNum = 0;
    Api.Post(Api.getUrl('getUserBucket'), {}, function(apiRtn){
        console.log(apiRtn);
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var data = apiRtn['data'];
            _bucketNum = data['bucket_num'];
            $('#api-bucket-num').html(data['bucket_num']);
            $('#api-bucket-price').html('¥'+parseFloat(data['bucket_price'] * parseInt(data['bucket_num'])).toFixed(2));
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });
    $('.fa-plus').click(function(){
        var num = parseInt($('#bucket-num').html());
        num = isNaN(num) ? 0 : num;
        num++;
        if(num > _bucketNum){
            num = _bucketNum;
        }
        $('#bucket-num').html(num)
        return false;
    });
    $('.fa-minus').click(function(){
        var num = parseInt($('#bucket-num').html());
        num = isNaN(num) ? 0 : num;
        num--;
        if(0 > num) num = 0;
        $('#bucket-num').html(num)
        return false;
    });
    $('#bucket-save').click(function(){
        G.tips('退桶功能暂未开放，敬请等待');
        return false;
    });
});