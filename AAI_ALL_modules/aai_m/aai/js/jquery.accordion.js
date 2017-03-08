(function($){
	$.fn.accordion = function(options){

		var defaults = {
			clickItem : $('.same_open'),
			contentItem : $('.same_open_content'),
			contentWidth : '340px',
			closeTime : 500,
			openTime : 500,
			tmpInput : $('#tmp'),
			clickItemBgColor : '#9d8757',
			contentItemBgColor : '#e2dbcc'

		}
		var obj = $.extend(defaults,options);

		obj.clickItem.css('backgroundColor',obj.clickItemBgColor);
		obj.contentItem.css('backgroundColor',obj.contentItemBgColor);

		obj.clickItem.click(function(){
			var _item = obj.tmpInput.val();
			var _data = $(this).attr('data');
			obj.contentItem.animate({width:0},obj.closeTime);
			$('#'+_data).animate({width:obj.contentWidth},obj.openTime,function(){
				obj.tmpInput.val(_data);
			});
		});

		return this;
	}
})(jQuery);