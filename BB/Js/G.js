(function(){
	function Tips(tips){
		this.tips = tips;
		Tips.prototype.init = function(){
			$('body').append('<div id="_G-tips-mask"></div>');
			$('body').append('<div id="_G-tips">'+tips+'</div>');
		}
		Tips.prototype.show = function(){
			this.init();
		}
		Tips.prototype.remove = function(){
			$('#_G-tips-mask').remove();
			$('#_G-tips').remove();
		}
	}
	function Confirm(options){
		this.options = options;
		this.confirmBtn = options.confirmBtn;
		this.cancelBtn = options.cancelBtn;
		this.tips = options.tips;
		this.mask = options.mask;
		Confirm.prototype.init = function(){
			if(this.mask){
				$('body').append('<div id="_G-confirm-mask"></div>');
			}
			$('body').append('<div id="_G-confirm">'+
				'<div id="_G-confirm-tips">'+this.tips+'</div>'+
				'<div id="_G-confirm-btns">'+
					'<span id="_G-cancel-btn">'+this.cancelBtn+'</span>'+
					'<span id="_G-confirm-btn">'+this.confirmBtn+'</span>'+
				'</div>'+
			'</div>');
		}
		Confirm.prototype.show = function(){
			var __this = this;
			this.init();
			$('#_G-confirm-mask').add($('#_G-cancel-btn')).click(function(){
				__this.remove();
				if(typeof __this.options.cancel == 'function'){
					__this.options.cancel();
				}
			});
			$('#_G-confirm-btn').click(function(){
				__this.remove();
				if(typeof __this.options.confirm == 'function'){
					__this.options.confirm();
				}
			});
		}
		Confirm.prototype.remove = function(){
			$('#_G-confirm-mask').remove();
			$('#_G-confirm').remove();
		}
	}
	function G(){
		G.prototype.tips = function(tips, sec){
			sec = (typeof sec == 'undefined') ? 1000 : parseInt(sec);
			var tips = new Tips(tips);
			tips.show();
			setTimeout(function(){
				tips.remove();
			}, sec);
			return tips;
		};
		G.prototype.confirm = function(options){
			var defaltOption = {tips:'确认要执行此操作么？',confirmBtn:'确认',cancelBtn:'取消',mask:true};
			options = $.extend(defaltOption, options);
			console.log(options);
			var confirm = new Confirm(options);
			confirm.show();
		};
		G.prototype.testCellphoneNo = function(no){
			return /^1[34578]\d{9}$/.test(no);
		};
	}
	window.G = new G();
})();