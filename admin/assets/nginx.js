jQuery(document).ready(function(){
	var news_section = jQuery('#latest_news');
	if(news_section.length>0){
		jQuery.get(news_url,function(data){
			news_section.find('.inside').html(data);
		});
	}
});