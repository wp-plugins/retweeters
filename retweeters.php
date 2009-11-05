<?php
/*
Plugin Name: Retweeters
Plugin URI: http://www.linksalpha.com/
Description: Displays Twitter users that recently Retweeted your articles
Version: .10
Author: Vivek Puri
Author URI: http://vivekpuri.com
*/

define('WIDGET_NAME', 'Retweeters');
define('WIDGET_NAME_INTERNAL', 'widget_retweeter');
define('WIDGET_PREFIX', 'retweeter');
define('RETWEETERS', 'Displays Twitter users that recently Retweeted your articles');
define('ERROR_INTERNAL', 'internal error');
define('ERROR_INVALID_URL', 'invalid url');

$retweeter_settings['title'] = array('label'=>'Title:', 'type'=>'text', 'default'=>'Retweeters');
$retweeter_settings['num'] = array('label'=>'Number of Items:', 'type'=>'text', 'default'=>'5', 'size'=>"3" );
$retweeter_settings['topic'] = array('label'=>'Blog Category:', 'type'=>'text', 'default'=>'', 'options'=>array('News', 'Technology', 'Entertainment', 'Business', 'LifeStyle', 'Sports'));
$retweeter_settings['topic_sub'] = array('label'=>'Blog Sub-Category:', 'type'=>'text', 'default'=>'', 'options'=>array('News'=>array('Asia', 'Crime', 'Europe', 'Health', 'India', 'Law', 'News', 'Opinion', 'Politics', 'US', 'World'), 'Technology'=>array('Energy', 'Gadgets', 'Gaming', 'Hacks', 'Internet', 'News', 'Opinion', 'Programming', 'Science'), 'Entertainment'=>array('Celebrity', 'Movies', 'Music', 'News', 'Opinion', 'TV', 'Videos'), 'Business'=>array('Asia', 'Auto', 'Europe', 'Market', 'Media', 'News', 'Opinion', 'Personal', 'Real Estate', 'US'), 'LifeStyle'=>array('Arts', 'Books', 'Education', 'Faith', 'Fashion', 'Food', 'Health', 'Home', 'News', 'Opinion', 'Travel'), 'Sports'=>array('Baseball', 'Basketball', 'Boxing', 'College', 'Football', 'Golf', 'Hockey', 'News', 'Opinion', 'Racing', 'School', 'Soccer', 'Tennis')));
$retweeter_settings['valid'] = array('default'=>'no');
$retweeter_settings['id'] = array('default'=>'');
$retweeter_settings['chars'] = array('label'=>'Post Title Character Count:', 'type'=>'text', 'default'=>'30', 'size'=>"3");

$options = get_option(WIDGET_NAME_INTERNAL);
$current_feed_url = $options['url'];
$current_selected_topic = $options['topic'];
$current_selected_topic_sub = $options['topic_sub'];

function retweeter_feed_update()
{
	$options = get_option(WIDGET_NAME_INTERNAL);
	$topic = $options['topic'];
	$topic_sub = $options['topic_sub'];
	$url = get_option('siteurl');

	if (!$url) {
		echo '<div style="text-align:center;"><span style="background-color:#e2c822">&nbsp;&nbsp;Not able to detect Blog URL.&nbsp;&nbsp;</span></div>';
		echo '<div style="text-align:center"><span style="background-color:#e2c822">&nbsp;&nbsp;Please contact LinksAlpha.com Support @linksalpha&nbsp;&nbsp;</span></div>';
		return false;
	}

	$link = 'http://api.linksalpha.net/addfeed.php?url='.$url.'&topic='.$topic.'&topic_sub='.$topic_sub;
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($link)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = json_decode($response_json);
		}
	}
	if (!$response) {
		echo '<div style="text-align:center;"><span style="background-color:#e2c822">&nbsp;&nbsp;Error occured&nbsp;&nbsp;</span></div>';
		echo '<div style="text-align:center"><span style="background-color:#e2c822">&nbsp;&nbsp;Please try again&nbsp;&nbsp;</span></div>';
		return false;
	}
	if (isset($response->error)) {
		if ($response->error == ERROR_INTERNAL or $response->error == '') {
			echo '<div style="text-align:center;"><span style="background-color:#e2c822">&nbsp;&nbsp;Error occured&nbsp;&nbsp;</span></div>';
			echo '<div style="text-align:center"><span style="background-color:#e2c822">&nbsp;&nbsp;Please try again&nbsp;&nbsp;</span></div>';
		} elseif ($response->error = ERROR_INVALID_URL) {
			echo '<div style="text-align:center;"><span style="background-color:#e2c822">&nbsp;&nbsp;Not able to detect Blog URL&nbsp;&nbsp;</span></div>';
			echo '<div style="text-align:center"><span style="background-color:#e2c822">&nbsp;&nbsp;LinksAlpha.com support has been notified&nbsp;&nbsp;</span></div>';
		}
	}
	if (isset($response->success)) {
		$feed_id = $response->id;
		$feed_new = $response->new;
		$options['valid'] = 'yes';
		$options['id'] = $feed_id;
		update_option(WIDGET_NAME_INTERNAL, $options);
		if ($feed_new == 'no') {
			echo '<div style="text-align:center;"><span style="background-color:#e2c822">&nbsp;&nbsp;Widget has been updated&nbsp;&nbsp;</span></div>';
		} else {
			echo '<div style="text-align:center;"><span style="background-color:#e2c822">&nbsp;&nbsp;Widget has been updated&nbsp;&nbsp;</span></div>';
			echo '<div style="text-align:center"><span style="background-color:#e2c822">&nbsp;&nbsp;Data will refresh in 2 mins&nbsp;&nbsp;</span></div>';
		}
	}
	return $options;
}


function retweeters_load($id, $num)
{

	if (!$id) {
		return false;
	}
	$link = 'http://linksalpha.appspot.com/a/retweeters?id='.$id.'&count='.$num;
	require_once(ABSPATH.WPINC.'/class-snoopy.php');
	$snoop = new Snoopy;
	$snoop->read_timeout = 3;
	$snoop->agent = 'Retweeters  - '.get_option('siteurl');
	$response = '';
	if($snoop->fetchtext($link)){
		if (strpos($snoop->response_code, '200')) {
			$response_json = $snoop->results;
			$response = json_decode($response_json);
			if (!$response->errorCode) {
				return $response;
			}
		}
	}
	return false;
}


function retweeter_init()
{
	function retweeter($args)
	{
		$options = get_option(WIDGET_NAME_INTERNAL);
		// Check if this feed is valid
		$valid_feed = $options['valid'];
		if (!$valid_feed) {
			return ;
		}
		//
		$data = retweeters_load($options['id'], $options['num']);
		$cache_key = WIDGET_PREFIX.'_recent';
		if (count($data) > 0) {
			wp_cache_set($cache_key, $data, 'widget');
		} else {
			$data = wp_cache_get($cache_key, 'widget');
		}

		if (!$data) {
			return ;
		}
		$html = '<table style="background-color:#FFF;width:100%;border:2px solid #7f93bc;padding:0px;border-collapse: collapse;"><tr><th style="font-size:14px;background-color:#7f93bc;padding:2px 0px 2px 0px;color:#FFF;text-align:center;">';
		$html .= $options['title'];
		$html .= '</th></tr><tr><td><table style="width:100%;padding:0px;border-collapse: collapse;">';
		$count = $options['num'];
		$i = 1;
		foreach ($data->results as $row) {
			$i++;
			$html .= '<tr>';
			$border = 1;
			if ($i > $count) {
				$border = 0;
			}
			$html .= '<td style="padding:5px 5px 5px 8px;border-bottom:'.$border.'px dotted #cccccc;vertical-align:top;width:40px;"><a target="_blank" href="http://twitter.com/'.$row->from_user.'"><img src="'.$row->profile_image_url.'" style="width:40px; height:40px;" /></a></td>';
			$html .= '<td style="padding:5px 5px 5px 3px;border-bottom:'.$border.'px dotted #cccccc;vertical-align:top;">';
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

	function retweeter_setup()
	{
		$options = $newoptions = get_option(WIDGET_NAME_INTERNAL);
		if ( $options != $newoptions ) {
			update_option(WIDGET_NAME_INTERNAL, $newoptions);
			retweeter_register();
		}
	}

	function retweeter_settings()
	{
		global $retweeter_settings;
		global $current_feed_url;
		global $current_selected_topic;
		global $current_selected_topic_sub;

		$options = get_option(WIDGET_NAME_INTERNAL);
		if ( isset($_POST[WIDGET_PREFIX.'-submit']) ) {
			foreach($retweeter_settings as $key => $field) {
				$options[$key] = $field['default'];
				$field_name = sprintf('%s_%s', WIDGET_PREFIX, $key);
				if ($field['type'] == 'text') {
					$value = strip_tags(stripslashes($_POST[$field_name]));
					$options[$key] = $value;
				}
			}
			$options['valid'] = 'no';
			update_option(WIDGET_NAME_INTERNAL, $options);
			$options = retweeter_feed_update();
		}


		$curr_field = 'title';
		$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
		$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
		if (!$field_value) {
			$field_value = $retweeter_settings[$curr_field]['default'];
		}
		echo '<div style="padding-bottom:10px"><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label>';
		echo '<input class="widefat" id="'.$field_name.'" name="'.$field_name.'" type="text" value="'.$field_value.'" /></div>';

		$curr_field = 'topic';
		$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
		$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
		echo '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
		echo '<div style="padding-bottom:10px">';
		echo '<select id="'.$field_name.'" name="'.$field_name.'"';
		foreach ($retweeter_settings[$curr_field]['options'] as $select_option) {
			if ($select_option == $current_selected_topic) {
				echo '<option value="'.$select_option.'" selected>'.$select_option.'</option>';
			} else {
				echo '<option value="'.$select_option.'">'.$select_option.'</option>';
			}
		}
		echo '</select></div>';

		$curr_field = 'topic_sub';
		$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
		$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
		echo '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
		echo '<div style="padding-bottom:10px">';
		echo '<select id="'.$field_name.'" name="'.$field_name.'"';
		if ($current_selected_topic) {
			$current_selected_topic_sub_tmp = $current_selected_topic;
		} else {
			$current_selected_topic_sub_tmp = 'News';
		}
		foreach ($retweeter_settings[$curr_field]['options'][$current_selected_topic_sub_tmp] as $select_option) {
			if ($select_option == $current_selected_topic_sub) {
				echo '<option value="'.$select_option.'" selected>'.$select_option.'</option>';
			} else {
				echo '<option value="'.$select_option.'">'.$select_option.'</option>';
			}
		}
		echo '</select></div>';


		$curr_field = 'num';
		$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
		$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
		if (!$field_value) {
			$field_value = $retweeter_settings[$curr_field]['default'];
		}
		echo '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
		echo '<div><input id="'.$field_name.'" name="'.$field_name.'" type="text" value="'.$field_value.'" size="'.$retweeter_settings[$curr_field]['size'].'" /></div>';

		echo '<div style="padding-bottom:10px"><small>'. _e('(at most 20)').'</small></div>';

		$curr_field = 'chars';
		$field_name = sprintf('%s_%s', WIDGET_PREFIX, $curr_field);
		$field_value = htmlspecialchars($options[$curr_field], ENT_QUOTES);
		if (!$field_value) {
			$field_value = $retweeter_settings[$curr_field]['default'];
		}
		echo '<div><label for="'.$field_name.'">'.$retweeter_settings[$curr_field]['label'].'</label></div>';
		echo '<div style="padding-bottom:10px"><input id="'.$field_name.'" name="'.$field_name.'" type="text" value="'.$field_value.'" size="'.$retweeter_settings[$curr_field]['size'].'" /></div>';

		if ($options['valid'] == 'no') {
			echo '<div style="padding-bottom:10px"><div style="padding:3px;border:1px solid #dd3c10;background-color:#ffebe8">Alert: Blog URL was not detected. LinksAlpha.com support has been notified</div></div>';
		}

		echo '<input type="hidden" id="'.WIDGET_PREFIX.'-submit" name="'.WIDGET_PREFIX.'-submit" value="1" />';
	}

	function retweeter_register()
	{
		wp_enqueue_script('jquery');
		wp_register_script('retweetersjs', WP_PLUGIN_URL .'/retweeters/retweeters.js');
		wp_enqueue_script('retweetersjs');

		$dims = array('width' => 250, 'height' => 300);
		$widget_ops = array('classname' => 'retweeter', 'description' => RETWEETERS);
		wp_register_sidebar_widget(WIDGET_NAME, WIDGET_NAME, 'retweeter', $widget_ops);
		wp_register_widget_control(WIDGET_NAME, WIDGET_NAME, 'retweeter_settings', $dims, $widget_ops);
		add_action('sidebar_admin_setup', 'retweeter_setup');
	}

	retweeter_register();
}

add_action('widgets_init', 'retweeter_init');

?>