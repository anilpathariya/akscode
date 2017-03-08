$(function(){
	$('.tab_container').accordion({
		clickItem : $('.same_open'),
		contentItem : $('.same_open_content'),
		contentWidth : '260px',
		closeTime : 500,
		openTime : 500,
		tmpInput : $('#tmp'),
		clickItemBgColor : '#9d8757',
		contentItemBgColor : '#e2dbcc'
	});
});

$("#aaiInformation").click(function() {
	$(this).css("display","none");
	$("#passengerInfo").css("display","block");
});
$(".closeBtn").click(function() {
	$("#passengerInfo").css("display","none");
	$("#aaiInformation").css("display","block");
});
		
	
 