$(function(){
    $('#deliver-bind-btn').click(function(){
        var name = $('#boss-name').val().trim();
        var tel = $('#boss-tel').val().trim();
        if(name.length == 0){
            G.tips('老板姓名不能为空！');
            return false;
        }
        if(tel.length == 0 || !G.testCellphoneNo(tel)){
            G.tips('老板电话格式错误！');
            return false;
        }
        Api.Post(Api.getUrl('bindBoss'), {name:name,tel:tel}, function(apiRtn){
            G.tips(apiRtn['msg']);
        });
    });
});