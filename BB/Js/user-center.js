$(function(){
    Api.Post(Api.getUrl('getUserInfo'), {}, function(apiRtn){
        console.log(apiRtn);
        if(apiRtn['code'] == 0 && JSON.stringify(apiRtn['data']) != '{}'){
            var data = apiRtn['data'];
            $('#user-name').html(data['nickname']);
            $('#user-integral').html(data['integral']);
            $('#user-image').attr('src', data['headimgurl']);
        }else{
            G.tips('系统错误，请稍后再试...');
            setTimeout(function(){
                $('#page-back').trigger('click');
            }, 1000);
        }
    });
});