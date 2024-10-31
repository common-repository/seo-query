<?php
/*
Plugin Name: SEO Query
Plugin URI: http://www.thugeek.com/web/plugin-seo-query-pour-wordpress/
Description: Displays search engine query
Version: 1.3
Author: Sébastien Bulté (Meuhsli)
Author URI: http://www.thugeek.com/
*/

//initialisation
load_plugin_textdomain('seoquery','wp-content/plugins/seoquery');
register_activation_hook(__FILE__,'seoquery_install');
register_deactivation_hook( __FILE__, 'seoquery_dropdb' );
add_action('wp','seoquery_getphrase');

$searchdb = $wpdb->prefix . 'seoquery';

function seoquery_install()
{
	global $wpdb, $searchdb;
	$sql= "CREATE TABLE ".$wpdb->prefix . 'seoquery'." (post BIGINT, query VARCHAR( 255 ) NULL, seeit BIGINT);";
	$wpdb->query($sql);
}

function seoquery_dropdb()
{
	global $wpdb, $searchdb;
	$sql = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'seoquery ;';
	$wpdb->query($sql);
}

function get_query() {
	$terms = null;
	$query = explode('&q=', $_SERVER['HTTP_REFERER']);
	if($query[1] == '') {$query = explode('?q=', $_SERVER['HTTP_REFERER']);}
	$query = explode('&', $query[1]);
	$query = urldecode($query[0]);
	$query = str_replace("'", '', $query);
	$query = str_replace('"', '', $query);
	//$terms = utf8_encode(urldecode($query));
	$terms = $query;

	$long_query = 20; //MAX LENGH OF THE QUERY
	if(strlen($terms) > $long_query)
		$terms = null;

	return $terms;
}

function PostId() {
	global $wp_query;
	return $thePostID = $wp_query->post->ID;
}

function onecookie(){
	$page = PostId();
	$timestamp = time() + 86400;
	setcookie("cookie_query_".$page,'1', $timestamp , COOKIEPATH);
}

function seoquery_getphrase(){
	global $wpdb, $searchdb;

	$google_str = '/^http:\/\/www.google\.([a-z]{2,3})|(co\.[a-z]{2})\//i';
	if ( preg_match($google_str, $_SERVER['HTTP_REFERER']) ) {
		$terms = get_query();
	}

	if($terms){
		$page = PostId();
		if (is_single()){
			if(!isset($_COOKIE['cookie_query_'.$page])){
				$sql = "SELECT LOWER(query), seeit FROM $searchdb WHERE post = $page and query=LOWER('$terms') ;";
				$result = $wpdb->get_results($sql, ARRAY_A);
				if ($result){
					foreach ($result as $line){
						$seeitnow = $line['seeit'] + 1;
						$wpdb->query("UPDATE $searchdb SET seeit = $seeitnow WHERE post = $page and query = LOWER('$terms');");
					}
				}else{
					$terms = mysql_real_escape_string($terms);
					$wpdb->query("INSERT INTO $searchdb VALUES ($page , LOWER('$terms'), 1);");
					onecookie();
				}
			}
		}
	}
}

function topphrases($count_show_query = 10)
{
	global $wpdb, $searchdb;
	//seoquery_getphrase();
	$count_show_query = 10; //number of query to display on page
	$page = PostId();
	$sql = "SELECT query, seeit FROM $searchdb WHERE post = ".$page." ORDER BY seeit DESC limit 0, $count_show_query;";
	$result = $wpdb->get_results($sql, ARRAY_A);

	if (is_single()){
		if ($result){
			foreach ($result as $line){
				echo $line['query'].", ";
			}
		}else{
			echo 'Wait and see more on the <a href="http://www.thugeek.com/">thugeek blog</a>';
		}
	}
}
?>