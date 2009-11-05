jQuery(document).ready(function($){
	$("select#retweeter_topic").change(function(){
		var options_new;
		var topic = $(this).val();
		if(topic == 'News') {
			options_new = 'Asia,Crime,Europe,Health,India,Law,News,Opinion,Politics,US,World';
		} else if(topic == 'Technology') {
			options_new = 'Energy,Gadgets,Gaming,Hacks,Internet,News,Opinion,Programming,Science';
		} else if(topic == 'Entertainment') {
			options_new = 'Celebrity,Movies,Music,News,Opinion,TV,Videos';
		} else if(topic == 'Business') {
			options_new = 'Asia,Auto,Europe,Market,Media,News,Opinion,Personal,Real Estate,US';
		} else if(topic == 'LifeStyle') {
			options_new = 'Arts,Books,Education,Faith,Fashion,Food,Health,Home,News,Opinion,Travel';
		} else if(topic == 'Sports') {
			options_new = 'Baseball,Basketball,Boxing,College,Football,Golf,Hockey,News,Opinion,Racing,School,Soccer,Tennis';
		}
		options_new = options_new.split(',');
		var options = '';
		jQuery.each(options_new, function() {
			options += '<option value="' + this + '">' + this + '</option>';
		});
		$("select#retweeter_topic_sub").html(options);
	});
})