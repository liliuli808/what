(function($){
	if(Api.Get('from')){
		$('#page-back').data('url', Api.Get('from'));
	}
	// Page Back
	$('#page-back').click(function(e){
		var backUrl = $(this).data('url');
		if(typeof backUrl == 'undefined' || backUrl.length == 0){
			window.history.go(-1);
		}else{
			window.location.href = backUrl;
		}
		e.preventDefault();
		return false;
	});
	// 绑定导航跳转
    window.bindDataUrlClickAction = function(){
        $('.data-url').click(function(){
            var url = $(this).data('url');
            if(url == undefined || 0 == url.length){
                return false;
            }else{
                window.location.href = url;
            }
        });
    }
    window.bindDataUrlClickAction();
})(jQuery);