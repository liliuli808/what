(function($){
	$.fn.carousel = function(option){
		var $this = this,
			imgIndex = 0,
			totalImg = this.find('img').length;
		var interval = setInterval(function(){
			imgIndex++;
			if(imgIndex == totalImg){
				imgIndex = 0;
			}
			$this.parent().animate({  
                scrollLeft: imgIndex * $(window).width() 
            }, 500);
		}, option['scrollDuration']);
	}
})(jQuery);