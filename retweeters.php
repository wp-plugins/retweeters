<?php
/*
Plugin Name: Retweeters
Plugin URI: http://www.linksalpha.com/
Description: Displays Twitter users that recently Retweeted your articles
Version: 1.2.1
Author: Vivek Puri
Author URI: http://vivekpuri.com
*/

define('WIDGET_NAME', 'Retweeters');
define('WIDGET_NAME_INTERNAL', 'widget_retweeter');
define('WIDGET_PREFIX', 'retweeter');
define('RETWEETERS', 'Displays Twitter users that recently Retweeted your articles. Also shows Tweet Count and Tweets for each blog post');
define('ERROR_INTERNAL', 'internal error');
define('ERROR_INVALID_URL', 'invalid url');

$retweeter_settings['title'] = array('label'=>'Title:', 'type'=>'text', 'default'=>'Retweeters');
$retweeter_settings['num'] = array('label'=>'Number of Items:', 'type'=>'text', 'default'=>'5', 'size'=>"3" );
$retweeter_settings['topic'] = array('label'=>'Blog Category:', 'type'=>'text', 'default'=>'', 'options'=>array('News', 'Technology', 'Entertainment', 'Business', 'LifeStyle', 'Sports'));
$retweeter_settings['topic_sub'] = array('label'=>'Blog Sub-Category:', 'type'=>'text', 'default'=>'', 'options'=>array('News'=>array('Asia', 'Crime', 'Europe', 'Health', 'India', 'Law', 'News', 'Opinion', 'Politics', 'US', 'World'), 'Technology'=>array('Energy', 'Gadgets', 'Gaming', 'Hacks', 'Internet', 'News', 'Opinion', 'Programming', 'Science'), 'Entertainment'=>array('Celebrity', 'Movies', 'Music', 'News', 'Opinion', 'TV', 'Videos'), 'Business'=>array('Asia', 'Auto', 'Europe', 'Market', 'Media', 'News', 'Opinion', 'Personal', 'Real Estate', 'US'), 'LifeStyle'=>array('Arts', 'Books', 'Education', 'Faith', 'Fashion', 'Food', 'Health', 'Home', 'News', 'Opinion', 'Travel'), 'Sports'=>array('Baseball', 'Basketball', 'Boxing', 'College', 'Football', 'Golf', 'Hockey', 'News', 'Opinion', 'Racing', 'School', 'Soccer', 'Tennis')));
$retweeter_settings['valid'] = array('default'=>'no');
$retweeter_settings['id'] = array('default'=>'');
$retweeter_settings['chars'] = array('label'=>'Post Title Character Count:', 'type'=>'text', 'default'=>'30', 'size'=>"3");
$retweeter_settings['tweet_comments'] = array('label'=>'Show Tweets as Comments on Blog Posts:', 'type'=>'checkbox', 'default'=>'1');
$retweeter_settings['tweet_count'] = array('label'=>'Show Tweet Count on Blog Posts:', 'type'=>'checkbox', 'default'=>'1');
$retweeter_settings['tweet_count_loc'] = array('label'=>'Show Tweet Counts next to:', 'type'=>'text', 'default'=>'', 'options'=>array('post_time'=>'Blog Post Time', 'comment_count'=>'Comment Count'));

$options = get_option(WIDGET_NAME_INTERNAL);
$current_globals = array('feed_url'=>$options['url'], 'topic'=>$options['topic'], 'topic_sub'=>$options['topic_sub'], 'tweet_count_loc'=>$options['tweet_count_loc']);

function retweeters_init() {
	wp_enqueue_script('jquery');
	wp_register_script('retweetersjs', WP_PLUGIN_URL .'/retweeters/retweeters.js');
	wp_enqueue_script('retweetersjs');
	wp_register_style('retweeterscss', WP_PLUGIN_URL . '/retweeters/retweeters.css');
	wp_enqueue_style('retweeterscss');
	add_action('admin_menu', 'retweeters_pages');
	add_action('admin_menu', 'retweeters_pages');
	add_action('comment_form', 'load_tweets');
	add_action('the_time', 'load_counters_time');
	add_action('comments_number', 'load_counters_comments');	
}
add_action('init', 'retweeters_init');
add_action('widgets_init', 'widget_retweeter_init');
add_action('init', 'load_tweets_page');

function retweeters_pages() {
	if ( function_exists('add_submenu_page') ) {
		$page = add_submenu_page('plugins.php', 'Retweeters', 'Retweeters', 'manage_options', 'retweeters', 'retweeters_conf');
		$page = add_submenu_page('index.php', __('Retweeters Stats'), __('Retweeters Stats'), 'manage_options', 'retweeters', 'retweeters_stats_display');
	}
}

function retweeters_conf() {
	global $retweeter_settings, $current_globals;
	
	$options = get_option(WIDGET_NAME_INTERNAL);
	if ( isset($_POST['submit']) ) {
		if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
			die(__('Cheatin&#8217; uh?'));
		}
		foreach($retweeter_settings as $key => $field) {
			$options[$key] = $field['default'];
			$field_name = sprintf('%s_%s', WIDGET_PREFIX, $key);
			if ($field['type'] == 'text') {
				$value = strip_tags(stripslashes($_POST[$field_name]));
				$options[$key] = $value;
			} else {				
				$options[$key] = $_POST[$field_name];
			}
			if (in_array($key, array_keys($current_globals))) {
				$current_globals[$key] = $_POST[$field_name];
			}
		}
		update_option(WIDGET_NAME_INTERNAL, $options);
		$options = retweeter_feed_update();
	}
	
	$html  = '<div class="rts_header"><big><strong>Retweeters</strong></big></div>';
	$html .= '<table class="rts_tbl_main"><tr><td style="width:50%; padding-right:30px; border-right:1px dotted #BFBFBF;">';
	$html .= '<div class="rts_header2"><big><strong>Setup</strong></big></div>';
	$html .= '<div style="text-align:left;">';
	$html .= '<form action="" method="post" id="retweeters-conf" style="width:90%;">';
	$html .= '<fieldset class="rts_fieldset">';
	$html .= '<legend>Settings:</legend>';
	
	$curr_field = 'title';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	if (!$field_value) {
		$field_value = $retweeter_settings[$curr_field]['default'];
	}
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:10px"><input style="width:400px;" class="widefat" id="'.$field_name.'" name="'.$field_name.'" type="text" value="'.$field_value.'" /></div>';

	$html .= '<div><table><tr><td style="width:150px;">';
	
	$curr_field = 'topic';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:10px">';
	$html .= '<select id="'.$field_name.'" name="'.$field_name.'"';
	foreach ($retweeter_settings[$curr_field]['options'] as $select_option) {
		if ($select_option == $current_globals['topic']) {
			$html .= '<option value="'.$select_option.'" selected>'.$select_option.'</option>';
		} else {
			$html .= '<option value="'.$select_option.'">'.$select_option.'</option>';
		}
	}
	$html .= '</select></div></td><td>';

	$curr_field = 'topic_sub';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:10px">';
	$html .= '<select id="'.$field_name.'" name="'.$field_name.'"';
	if (!empty($current_globals['topic'])) {
		$current_selected_topic_sub_tmp = $current_globals['topic'];
	} else {
		$current_selected_topic_sub_tmp = 'News';
	}
	foreach ($retweeter_settings[$curr_field]['options'][$current_selected_topic_sub_tmp] as $select_option) {
		if ($select_option == $current_globals['topic_sub']) {
			$html .= '<option value="'.$select_option.'" selected>'.$select_option.'</option>';
		} else {
			$html .= '<option value="'.$select_option.'">'.$select_option.'</option>';
		}
	}
	$html .= '</select></div></td></tr><tr><td style="width:150px;">';

	$curr_field = 'num';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	if (!$field_value) {
		$field_value = $retweeter_settings[$curr_field]['default'];
	}
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div><input id="'.$field_name.'" name="'.$field_name.'" type="text" value="'.$field_value.'" size="'.$retweeter_settings[$curr_field]['size'].'" /></div>';

	$html .= '<div style="padding-bottom:10px"><small>(at most 20)</small></div></td><td style="padding:0x 0px 0px 30px;vertical-align:top;">';

	$curr_field = 'chars';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	if (!$field_value) {
		$field_value = $retweeter_settings[$curr_field]['default'];
	}
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:10px"><input id="'.$field_name.'" name="'.$field_name.'" type="text" value="'.$field_value.'" size="'.$retweeter_settings[$curr_field]['size'].'" /></div></td></tr></table></div>';

	$html .= '</fieldset><fieldset class="rts_fieldset" style="margin-top:20px;"><legend>Features:</legend>';
	
	$curr_field = 'tweet_comments';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	if (!$field_value and empty($options[id])) {
		$field_value = $retweeter_settings[$curr_field]['default'];
	}
	$checked = '';
	if($field_value) {
		$checked = "checked";
	}
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:15px;"><input id="'.$field_name.'" name="'.$field_name.'" type="checkbox" '.$checked.' /></div>';
	
	$curr_field = 'tweet_count';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = $options[$curr_field];
	if (!$field_value and empty($options[id])) {
		$field_value = $retweeter_settings[$curr_field]['default'];
	}
	$checked = '';
	if($field_value) {
		$checked = "checked";
	}
	$html .= '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:15px"><input id="'.$field_name.'" name="'.$field_name.'" type="checkbox" '.$checked.' /></div>';
	
	$curr_field = 'tweet_count_loc';
	$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
	$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
	$html .= '<div id="box_'.$field_name.'"><div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
	$html .= '<div style="padding-bottom:10px">';
	$html .= '<select id="'.$field_name.'" name="'.$field_name.'"';
	foreach ($retweeter_settings[$curr_field]['options'] as $key=>$val) {
		if ($key == $current_globals['tweet_count_loc']) {
			$html .= '<option value="'.$key.'" selected>'.$val.'</option>';
		} else {
			$html .= '<option value="'.$key.'">'.$val.'</option>';
		}
	}
	$html .= '</select></div></div>';
	
	$html .= '</fieldset>';
	$html .= '<div style="padding-top:20px;"><input type="submit" name="submit" value="Update Options" /></div>';
	$html .= '</form>';
	$html .= '</div></td><td style="padding:0px 30px 0px 30px;">';
	
	$html .= '<div class="rts_header2"><big><strong>Stats</strong></big></div>';
	$html .= load_feed_stats();
	$html .= '</td></tr></table>';
	$html .= '<div style="margin-top:10px;margin-right:20px;background-color:#eceff5;padding:5px;">Powered by <a style="vertical-align:baseline;" href="http://www.linksalpha.com"><img src="http://linksalpha.s3.amazonaws.com/static/LALOGO12PX1.png" /></a></div>';
	echo $html;
}

function retweeters_stats_display($show=True) {
	$html  = '<div class="rts_header"><big><strong>Retweeters Stats</strong></big></div>';
	$html .= load_feed_stats();
	echo $html;
}

function load_feed_stats() {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$valid_feed = $options['valid'];
	if (!$valid_feed) {
		$html = '<div>Your blog has not yet been detected by the plugin. Please click on the "Update Options button" to get started</div>';
		return $html;
	}
	$id = $options['id'];
	if (!$id) {
		$html = '<div>Your blog has not yet been detected by the plugin. Please click on the "Update Options button" to get started</div>';
		return $html;
	}
	$response = fetch_feed_stats($id);
	if (empty($response)) {
		$html = '<div>No blog posts detected for this blog in the past 30 days</div>';
		return $html;
	}
	if (empty($response->results)) {
		$html = '<div>No blog posts detected for this blog in the past 30 days</div>';
		return $html;
	}
	$html = '<div><h4>Recent <a href="'.get_bloginfo(url).'">'.get_bloginfo('name').'</a> Blog Posts Indexed by <a target="_blank" href="http://www.linksalpha.com">LinksAlpha.com</a></h4></div>';
 	foreach ($response->results as $post) {
 		
 		$html .= '<div style="padding-bottom:2px;padding-top:2px;"><a target="_blank" href="http://www.linksalpha.com/link?id='.$post->id.'">'.$post->title.'</a></div>';
 		$html .= '<div style="padding-bottom:10px;border-bottom:1px dotted #BFBFBF;"><img class="rts_favicons" src="http://d25b87jrm423ks.cloudfront.net/static/twitter_favicon.ico" />&nbsp;'.$post->count_tweets.'&nbsp;&nbsp;&nbsp;&nbsp;';
 		$html .= '<img class="rts_favicons" src="http://d25b87jrm423ks.cloudfront.net/static/facebook_favicon.ico" />&nbsp;'.$post->count_fb_share.'&nbsp;&nbsp;&nbsp;&nbsp;';
 		$html .= '<img class="rts_favicons" src="http://d25b87jrm423ks.cloudfront.net/static/bitly_favicon.ico" />&nbsp;'.$post->count_bitly;
 		$html .= '</div>';
 	}
 	return $html;
	
}

function fetch_feed_stats($id) {
	if (!$id) {
		return array();
	}
	$url = 'http://www.linksalpha.com/a/feedhealth?id='.$id;
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 3;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($url)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = retweeter_json_decode($response_json);
			if (!$response->errorCode) {
				return $response;
			}
		}
	}
	return array();
}

function load_tweets($text) {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$option_load_tweets = $options['tweet_comments'];
	if (!$option_load_tweets) {
		return ;
	}	
	load_link_tweets();
}

function load_link_tweets() {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$option_valid_feed = $options['valid'];
	if (!$option_valid_feed) {
		return ;
	}
	$link = get_permalink();
	$response = fetch_tweets($link);
	if (empty($response)) {
		return ;
	}
	if (empty($response->results)) {
		return ;
	}
	$html = '<div style="padding:30px 0px 0px 0px">';
	$html .= '<div style="padding:0px 0px 5px 0px;"><span><h3><a target="_blank" href="http://www.linksalpha.com/link?id='.$response->link_id.'">Social Trackbacks</a></h3></span><span><input type="hidden" id="rts_blog_url" name="rts_blog_url" value="'.get_bloginfo('url').'" /></span></div>';
	$html .= '<div id="rts_tweet_content">';
	$html .= show_tweets($response->results);
	if ($response->next_page) {
		$html .= '<div id="rts_tweets_load_page_box"><input type="hidden" id="rts_tweets_load_page" name="rts_tweets_load_page" value="'.$response->next_page.'" /></div>';
		$html .= '<div id="rts_tweets_load_button"><input type="hidden" id="rts_current_link" name="rts_current_link" value="'.$link.'" /><input type="button" id="rts_tweets_load" name="rts_tweets_load" value="more" /></div>';
	}
	$html .= '</div>';	
	echo $html;
}

function load_tweets_page() {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$option_valid_feed = $options['valid'];
	if (!$option_valid_feed) {
		return ;
	}
	if (!empty($_POST['retweeter_link'])) {
		$link = $_POST['retweeter_link'];
	}
	if (!empty($_POST['retweeter_tweets_page'])) {
		$page = $_POST['retweeter_tweets_page'];
	}
	if (!isset($link) or !isset($page)) {
		return false;
	}
	$response = fetch_tweets($link, $page);
	if (empty($response)) {
		return false;
	}
	if (empty($response->results)) {
		return false;
	}
	$html = show_tweets($response->results);
	if ($response->next_page) {
		$html .= '<div id="rts_tweets_load_page_box"><input type="hidden" id="rts_tweets_load_page" name="rts_tweets_load_page" value="'.$response->next_page.'" /></div>';
	}
	echo $html;
}

function show_tweets($tweets) {
	$html = '';
	foreach ($tweets as $key=>$val) {
		$html .= '<div class="rts_tweet_box"><div><table><tr>';
		$html .= '<td style="width:40px;"><img src="'.$val->profile_image_url.'"></td>';
		$html .= '<td><div><span style="font-weight:bold"><a target="_blank" href="http://twitter.com/'.$val->from_user.'">'.$val->from_user.'</a></span><span>&nbsp;'.$val->tweet.'</span></div>';
		$created_at = date("Y-m-d H:i:s", $val->created_at);
		$created_at = prettyTime($created_at);
		$html .= '<div style="color:#6F6F6F;font-size:11px;">'.$created_at.'</div>';
		$html .= '</td></tr></table></div>';
		$html .= '</div>';
	}
	$html .= '</div>';
	return $html;
}

function fetch_tweets($link, $page=0) {
	if (!$link) {
		return array();
	}
	$url = 'http://www.linksalpha.com/a/tweets?link='.$link.'&page='.$page;
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 3;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($url)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = retweeter_json_decode($response_json);
			if (!$response->errorCode) {
				return $response;
			}
		}
	}
	return array();
}

function load_counters_time($text) {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$option_valid_feed = $options['valid'];
	if (!$option_valid_feed) {
		echo $text;
		return ;
	}
	$option_tweet_count = $options['tweet_count'];
	if (!$option_tweet_count) {
		echo $text;
		return ;
	}
	$option_tweet_count_loc = $options['tweet_count_loc'];
	if ($option_tweet_count_loc != 'post_time') {
		echo $text;
		return ;
	}
	$link = get_permalink();
	$html = load_tweet_count($link);
	if ($html) {
		echo $text.'&nbsp;&nbsp;'.$html;
	} else {
		echo $text;
	}
}

function load_counters_comments($text) {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$option_valid_feed = $options['valid'];
	if (!$option_valid_feed) {
		echo $text;
		return ;
	}
	$option_tweet_count = $options['tweet_count'];
	if (!$option_tweet_count) {
		echo $text;
		return ;
	}
	$option_tweet_count_loc = $options['tweet_count_loc'];
	if ($option_tweet_count_loc != 'comment_count') {
		echo $text;
		return ;
	}
	$link = get_permalink();
	$html = load_tweet_count($link);
	if ($html) {
		echo $text.'&nbsp;&nbsp;'.$html;	
	} else {
		echo $text;
	}
}

function load_tweet_count($link=NULL) {
	if (!$link) {
		$link = get_permalink();
	}
	if (!$link) {
		return false;
	}
	$response = fetch_tweet_count($link);	
	if (empty($response->count)) {
		return false;
	}
	$html = '<a class="rts_tweet_count_link" target="_blank" href="http://www.linksalpha.com/link?id='.$response->link_id.'"><div class="rts_counter"><span style="font-size:105%">'.$response->count.'</span> tweets</div></a>';
	return $html;
}

function fetch_tweet_count($link) {
	if (!$link) {
		return array();
	}
	$url = 'http://www.linksalpha.com/a/tweetcount?link='.$link;
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 3;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($url)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = retweeter_json_decode($response_json);
			if (!$response->errorCode) {
				return $response;
			}
		}
	}
	return array();
}

function load_retweeters() {
	$options = get_option(WIDGET_NAME_INTERNAL);
	$valid_feed = $options['valid'];
	if (!$valid_feed) {
		return ;
	}
	$id = $options['id'];
	if (!$id) {
		return ;
	}
	$data = fetch_retweeters($id, $options['num']);
	$cache_key = WIDGET_PREFIX.'_recent';
	if (count($data) > 0) {
		wp_cache_set($cache_key, $data, 'widget');
	} else {
		$data = wp_cache_get($cache_key, 'widget');
	}
	if (!$data) {
		return ;
	}
	if (empty($data->results)) {
		return ;
	}
	$html = '<table class="rts_widget"><tr><th>';
	$html .= $options['title'];
	$html .= '</th></tr><tr><td><table>';
	$count = $options['num'];
	$i = 1;
	foreach ($data->results as $row) {
		$i++;
		$html .= '<tr>';
		$border = 1;
		if ($i > $count) {
			$border = 0;
		}
		$html .= '<td style="padding:5px 5px 5px 8px;border-bottom:'.$border.'px dotted #cccccc;vertical-align:top;width:40px;margin:0px;"><a target="_blank" href="http://twitter.com/'.$row->from_user.'"><img src="'.$row->profile_image_url.'" style="width:40px; height:40px;" /></a></td>';
		$html .= '<td style="padding:5px 5px 5px 3px;border-bottom:'.$border.'px dotted #cccccc;vertical-align:top;margin:0px;">';
		$html .= '<div><a target="_blank" href="http://twitter.com/'.$row->from_user.'" style="font-size:12px;color:black">'.$row->from_user.'</a></div>';
		$html .= '<div><a target="_blank" href="http://www.linksalpha.com/link?id='.$row->link_id.'" style="font-size:11px;color:gray;text-decoration:underline;">"'.substr($row->title, 0, $options['chars']).'"</a></div>';
		$html .= '</td>';
		$html .= '</tr>';
		if ($i > $count) {
			break;
		}
	}
	$html .= '</table>';
	$html .= '</td></tr></table>';
	echo $html;
	return ;
}

function fetch_retweeters($id, $num) {
	if (!$id) {
		return false;
	}
	$link = 'http://www.linksalpha.com/a/retweeters?id='.$id.'&count='.$num;
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 3;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($link)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = retweeter_json_decode($response_json);
			if (!$response->errorCode) {
				return $response;
			}
		}
	}
	return false;
}

function retweeter_feed_update(){
	$options = get_option(WIDGET_NAME_INTERNAL);
	$topic = $options['topic'];
	$topic_sub = $options['topic_sub'];
	$url = get_option('siteurl');
	$desc = get_bloginfo('description');
	
	if (!$url) {
		$html  = '<div class="rts_error"><span>Not able to detect Blog URL.</span>';
		$html .= '<span>&nbsp;Please contact <a href="http://www.linksalpha.com" target="_blank">LinksAlpha.com</a> Support at support@linksalpha.com</span></div>';
		echo $html;
		return false;
	}

	$link = 'http://api.linksalpha.net/addfeed.php?url='.urlencode($url).'&topic='.$topic.'&topic_sub='.$topic_sub.'&desc='.urlencode($desc);
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($link)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = retweeter_json_decode($response_json);
		}
	}
	if (!$response) {
		echo '<div class="rts_error"><span>Error occured&nbsp;&nbsp;</span>';
		echo '<span>&nbsp;Please try again</span></div>';
		return false;
	}
	if (isset($response->error)) {
		if ($response->error == ERROR_INTERNAL or $response->error == '') {
			echo '<div class="rts_error"><span>Error occured</span>';
			echo '<span>&nbsp;Please try again</span></div>';
		} elseif ($response->error = ERROR_INVALID_URL) {
			echo '<div class="rts_error"><span>Not able to detect Blog URL</span></div>';
			echo '<span>&nbsp;LinksAlpha.com support has been notified</span></div>';
		}
	}
	if (isset($response->success)) {
		$feed_id = $response->id;
		$feed_new = $response->new;
		$options['valid'] = 'yes';
		$options['id'] = $feed_id;
		update_option(WIDGET_NAME_INTERNAL, $options);
		if ($feed_new == 'no') {
			echo '<div class="rts_success"><span>Widget has been updated</span></div>';
		} else {
			echo '<div class="rts_success"><span>Widget has been updated</span>';
			echo '<span>&nbsp;Data will refresh in 2 mins</span></div>';
		}
	}
	return $options;
}

function widget_retweeter_init() {
	$dims = array('width' => 250, 'height' => 300);
	$widget_ops = array('classname' => 'retweeter', 'description' => RETWEETERS);
	wp_register_sidebar_widget(WIDGET_NAME, WIDGET_NAME, 'load_retweeters', $widget_ops);
	wp_register_widget_control(WIDGET_NAME, WIDGET_NAME, 'retweeter_settings', $dims, $widget_ops);
	add_action('sidebar_admin_setup', 'retweeter_setup');
}
	
function retweeter_settings() {
	$html = '<div style="text-align:center;padding:10px 5px 15px 5px;"><a style="font-size:14px;" href="'.get_option('siteurl').'/wp-admin/plugins.php?page=retweeters'.'">Click Here to Edit Settings</a></div>';
	echo $html;
}
	
function retweeter_setup() {
	$options = $newoptions = get_option(WIDGET_NAME_INTERNAL);
	if ( $options != $newoptions ) {
		update_option(WIDGET_NAME_INTERNAL, $newoptions);
		retweeter_register();
	}
}

function retweeter_json_decode($str) {
	if (function_exists("json_decode")) {
	    return json_decode($str);
	} else {
		if (!class_exists('Services_JSON')) {
			require_once("JSON.php");
		}
	    $json = new Services_JSON();
	    return $json->decode($str);
	}
}

/* 
 * JavaScript Pretty Date 
 * Copyright (c) 2008 John Resig (jquery.com) 
 * Licensed under the MIT license. 
 */ 
// Slight modification to handle datetime. 
function prettyTime($fromTime) {
	$fromTime = strtotime($fromTime);
    $toTime = time();
    $diff = round(abs($toTime - $fromTime));
    $dayDiff = floor($diff / 86400); 
    if(is_nan($dayDiff) || $dayDiff < 0) { 
        return 'few moments ago';
    } 
    if($dayDiff == 0) { 
        if($diff < 60) { 
            return 'Just now'; 
        } elseif($diff < 120) { 
            return '1 minute ago'; 
        } elseif($diff < 3600) { 
            return floor($diff/60) . ' minutes ago'; 
        } elseif($diff < 7200) { 
            return '1 hour ago'; 
        } elseif($diff < 86400) { 
            return floor($diff/3600) . ' hours ago'; 
        } 
    } elseif($dayDiff == 1) { 
        return 'Yesterday'; 
    } elseif($dayDiff < 7) { 
        return $dayDiff . ' days ago'; 
    } elseif($dayDiff == 7) { 
        return '1 week ago'; 
    } elseif($dayDiff < (7*6)) { // Modifications Start Here 
        // 6 weeks at most 
        return ceil($dayDiff/7) . ' weeks ago'; 
    } elseif($dayDiff < 365) { 
        return ceil($dayDiff/(365/12)) . ' months ago'; 
    } else { 
        $years = round($dayDiff/365); 
        return $years . ' year' . ($years != 1 ? 's' : '') . ' ago'; 
    } 
}

?>