jQuery(document).ready(function($){
	var mozInfoMoreShown = false;
	$('body').on('click','#moz-info-show-more-info',function(){
		if($('.moz-info .more-info').is(':visible')){
			mozInfoMoreShown = true;
		}else{
			mozInfoMoreShown = true;
		}
		if(mozInfoMoreShown){
			$('#moz-info-show-more-info').text('Show More Info');
			$('.moz-info .more-info').slideUp(200);
			mozInfoMoreShown = false;
		}else{
			$('#moz-info-show-more-info').text('Show Less Info');
			$('.moz-info .more-info').slideDown(200);
			mozInfoMoreShown = true;
		}
		return false;
	});
});