$(function(){
    $('#deliver-bind-btn').click(function(){
        var name = $('#deliver-name').val().trim();
        var tel = $('#deliver-tel').val().trim();
        if(name.length == 0){
            G.tips('水工姓名不能为空！');
            return false;
        }
        if(tel.length == 0 || !G.testCellphoneNo(tel)){
            G.tips('水工电话格式错误！');
            return false;
        }
        Api.Post(Api.getUrl('bindDeliver'), {name:name,tel:tel}, function(apiRtn){
            G.tips(apiRtn['msg']);
        });
    });
});