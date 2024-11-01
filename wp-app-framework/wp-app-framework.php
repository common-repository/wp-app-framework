<?php
/*
Plugin Name: WP App Framework
Version: 0.1
Plugin URI: http://wpappframework.com/
Description: A powerful developer framework for WordPress
Author: WP App Framework
Author URI: http://wpappframework.com/
*/

/**
* Parent Class
*/
class WP_App_Framework
{
	public $paths = array();
	public $debug = FALSE;
	private static $_instance; 
	public $debug_on_destruct = FALSE;
	public $debug_msgs = array();
	static public $messages = array();
	static public $current_user;
	
	private function __construct()
	{
		$this->define_paths();
		$this->current_user = wp_get_current_user();
	}
	
	public static function getInstance()
	{
	    if (!self::$_instance)
	    {
	        self::$_instance = new WP_App_Framework();
	    }

	    return self::$_instance;
	}
	
	function __destruct()
	{
		if($this->debug_on_destruct && $this->current_user->caps['administrator']) :
			echo 'destructing...';
			/////////////////////////////////////////////////////////////////////
			echo 'Displaying Array $this->debug_msgs <br/><pre>';
			print_r($this->debug_msgs);
			echo '</pre>';
			/////////////////////////////////////////////////////////////////////
		endif;
		
	}
	
	function define_paths() {
		$this->paths['site_url'] = get_bloginfo('url');
		$this->paths['site_title'] = get_bloginfo('name');
		$this->paths['site_path'] = ABSPATH.'/';
		$this->paths['plugins_path'] = WP_PLUGIN_DIR.'/';
		$this->paths['plugins_uri'] = WP_PLUGIN_URL.'/';
		$this->paths['wpaf_path'] = WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$this->paths['wpaf_uri'] = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__));
		$this->paths['theme_path'] = get_stylesheet_directory() . '/';
		$this->paths['theme_uri'] = get_stylesheet_directory_uri() . '/';
	}
	
	/////////////////////////////////////////////////////////////////////
	// Load a WPAF Module
	// Module must be named wpaf-{module name}
	// Modules are found in the following order:
	// 	1) Current Theme/wpaf_modules/wpaf-{module name}/wpaf-{module name}.class.php
	// 	2) Plugin Directory/wpaf-{module name}/wpaf-{module name}.class.php
	//  3) Plugin Directory/modules/wpaf-{module name}/wpaf-{module name}.class.php
	// Class name must be WPAF_{module name} and should extend WP_App_Framework
	// Module name should be all lower class
	/////////////////////////////////////////////////////////////////////
	function load_module($mod_name) {
		$this->debug_msg('Loading Module "'.$mod_name.'"');
		
		//check current theme
		if(file_exists($this->paths['theme_path'].'wpaf_modules/wpaf-'.$mod_name.'/wpaf-'.$mod_name.'.class.php')) :
			$mod_path = $this->paths['theme_path'].'wpaf_modules/wpaf-'.$mod_name.'/wpaf-'.$mod_name.'.class.php';
			$mod_dir = $this->paths['theme_path'].'wpaf_modules/wpaf-'.$mod_name.'/';
			$this->debug_msg('Loading from theme.');
		elseif(file_exists($this->paths['plugins_path'].'wpaf-'.$mod_name.'/wpaf-'.$mod_name.'.class.php')) :
			$mod_path = $this->paths['plugins_path'].'wpaf-'.$mod_name.'/wpaf-'.$mod_name.'.class.php';
			$mod_dir = $this->paths['plugins_path'].'wpaf-'.$mod_name.'/';
			$this->debug_msg('Loading from external plugin.');
		elseif(file_exists($this->paths['wpaf_path'].'modules/wpaf-'.$mod_name.'/wpaf-'.$mod_name.'.class.php')) :
			$mod_path = $this->paths['wpaf_path'].'modules/wpaf-'.$mod_name.'/wpaf-'.$mod_name.'.class.php';
			$mod_dir = $this->paths['wpaf_path'].'modules/wpaf-'.$mod_name.'/';
			$this->debug_msg('Loading from WPAF Plugin.');
		else :
			$this->debug_msg('File not found!');
			return false;
		endif;
		
		include($mod_path);
		eval('$this->'.$mod_name.' =& new WPAF_'.$mod_name.';');
		$this->debug_msg('Module Loaded!');
	}
	
	function debug_msg($msg) {
		if($this->debug == TRUE) :
			echo '<div class="wpaf_debug_msg"><strong>WPAF Debug Message:</strong> '.$msg.'</div>';
		endif;	
		$this->debug_msgs[] = $msg;
	}
	
	function set_message($message = NULL, $type = 'success') {
		if($type != 'success') { $type = 'error'; }
		$msg['message'] = $message;
		$msg['type'] = $type;
		$this->messages[] = $msg;
	}
	
	function display_messages() {
		$msgs = $this->messages;
		if(count($msgs) == 0) { return true; }
		foreach ($msgs as $msg) {
			echo '<div class="message_box '.$msg['type'].'">'.$msg['message'].'</div>';
		}
		return true;
		
	}
	
	function bulk_update_meta($pid, $meta) {
		if(is_array($meta)) :  foreach($meta as $key => $val) :
			update_post_meta($pid, $key, $val);
		endforeach; endif;
	}
}

function set_up_wp_app_framework() {
	global $wpaf;
	$wpaf = WP_App_Framework::getInstance();
	
	/////////////////////////////////////////////////////////////////////
	// Load modules here...
	/////////////////////////////////////////////////////////////////////
	$wpaf->load_module('email');
	$wpaf->load_module('cfm');
}
add_action('after_setup_theme', 'set_up_wp_app_framework');


/////////////////////////////////////////////////////////////////////
// Allow login by email
/////////////////////////////////////////////////////////////////////
function wpaf_email_login_authenticate( $user, $username, $password ) {
	if ( !empty( $username ) )
		$user = get_user_by_email( $username );
	if ( $user )
		$username = $user->user_login;
	
	return wp_authenticate_username_password( null, $username, $password );
}
remove_filter( 'authenticate', 'wp_authenticate_username_password', 20, 3 );
add_filter( 'authenticate', 'wpaf_email_login_authenticate', 20, 3 );