<?php 
/**
* An email module for WP App Framework
*
* This will eventually be expanded to support SMTP and Amazon SES
*/
class WPAF_email
{
	private $wpaf;
	
	function __construct() {
		global $wpaf;
		$this->wpaf =& $wpaf;
		
		$this->wpaf->debug_msg('WPAF_email Loaded!');
	}
	
	function send($to, $subject, $message) {
		$headers = 'From: "'.$this->wpaf->paths['site_title'].'" <'.get_bloginfo('admin_email').'>' . PHP_EOL .
		           'X-Mailer: PHP-' . phpversion() . PHP_EOL;
		
		mail($to, $subject, $message, $headers);
	}
}