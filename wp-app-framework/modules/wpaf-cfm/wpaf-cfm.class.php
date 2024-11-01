<?php 
/**
* Custom field module for WP App Framework
*/
class WPAF_cfm
{
	private $wpaf;
	public $post_id;
	public $areas;
	
	function __construct() {
		global $wpaf, $post;
		$this->wpaf =& $wpaf;
		
		$this->post_id = $post->ID;
		
		if(!$this->post_id && $_GET['post']) {
			$this->post_id = $_GET['post'];
		}
		
		$this->wpaf->debug_msg('WPAF_cfm Loaded!');
		
		//Hook it in!
		add_action('admin_menu', array(&$this, 'cfm_init'));
		add_action('save_post', array(&$this, 'cfm_save'));
		
	}
	

	
	function cfm_init() {
		/////////////////////////////////////////////////////////////////////
		// Get the config
		/////////////////////////////////////////////////////////////////////
		$areas = $this->areas;
		

		/////////////////////////////////////////////////////////////////////
		// Initialize boxes
		/////////////////////////////////////////////////////////////////////
		if(is_array($areas)) :
		foreach ($areas as $key => $area) :
				unset($path);
					/////////////////////////////////////////////////////////////////////
					// Figure out which post types
					/////////////////////////////////////////////////////////////////////
					if($area['post_type'] == 'all') :
						$post_types=get_post_types('','names'); 
					else :
						$post_types = explode(',',$area['post_type']);
					endif;

					foreach($post_types as $pt) :
						$pt = trim($pt);
						add_meta_box( 'cfm_'.$key, $area['title'], 
		                array(&$this, 'cfm_generate_box'), $pt, $area['location'], $area['position'], array('area'=>$key));
					endforeach;
			endforeach;
		endif;
	}

	function cfm_generate_box($post, $args) {

		if($this->areas[$args['args']['area']]['file_path']) :
			include($this->areas[$args['args']['area']]['file_path']);
		elseif(file_exists($this->wpaf->paths['theme_path'].'custom_meta_boxes/'.$args['args']['area'].'.php')) :
			include($this->wpaf->paths['theme_path'].'custom_meta_boxes/'.$args['args']['area'].'.php');
		else :
			echo '<strong>No Meta Box Template Found!</strong>';
		endif;
	}

	function cfm_save($post_id) {
		
			/////////////////////////////////////////////////////////////////////
			// If there's no CFM data, we have no business here
			/////////////////////////////////////////////////////////////////////
			if(!is_array($_POST['cfm_data']))
				return $post_id;

		  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
		  // to do anything
		  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		    return $post_id;

		  // Check permissions
		  if ( 'page' == $_POST['post_type'] ) {
		    if ( !current_user_can( 'edit_page', $post_id ) )
		      return $post_id;
		  } else {
		    if ( !current_user_can( 'edit_post', $post_id ) )
		      return $post_id;
		  }

		  // OK, we're authenticated: we need to find and save the data

		if(is_array($_POST['cfm_data'])) :  foreach($_POST['cfm_data'] as $key => $val) :
			update_post_meta($post_id, $key, $val);
		endforeach; endif;
		   return $post_id;
	}



	/////////////////////////////////////////////////////////////////////
	// 
	// Utilities below to generate form and recall.
	// Use these functions to generate form fields.  
	// We automatically add _cfm_ to the beginning of each field name
	// 
	/////////////////////////////////////////////////////////////////////
	function cfm_get($post_id, $name) {
		return get_post_meta($post_id, '_cfm_'.$name, TRUE);
	}
	// Generate a simple text input
	function cfm_input($name, $echo = TRUE, $attr = null) {
		$attrs = '';
		if(is_array($attr)) :  foreach($attr as $key => $val) :
			$attrs .= $key.'="'.htmlspecialchars($val, ENT_QUOTES).'" ';
		endforeach; endif;
		$o = '<input type="text" name="cfm_data[_cfm_'.$name.']" value="'.get_post_meta($this->post_id, '_cfm_'.$name, true).'" '.$attrs.' />';
		if($echo) {
			echo $o;
		}
		return $o;
	}

	function cfm_tinymce($name, $echo = TRUE, $attr = null) {
		global $post;
		$attrs = '';
		if(is_array($attr)) :  foreach($attr as $key => $val) :
			$attrs .= $key.'="'.htmlspecialchars($val, ENT_QUOTES).'" ';
		endforeach; endif;
		$o = '<textarea name="cfm_data[_cfm_'.$name.']" id="cfm_'.$name.'" '.$attrs.'>'.get_post_meta($this->post_id, '_cfm_'.$name, true).'</textarea>
	<script type="text/javascript">
	jQuery(document).ready(function() {
		tinyMCE.init({
		theme : "advanced",
		mode : "exact",
		elements : "cfm_'.$name.'",
		width : "600",
		height : "200"
		});
	});
	</script>
	';
		if($echo) {
			echo $o;
		}
		return $o;
	}

	function cfm_textarea($name, $echo = TRUE, $attr = null) {
		global $post;
		$attrs = '';
		if(is_array($attr)) :  foreach($attr as $key => $val) :
			$attrs .= $key.'="'.htmlspecialchars($val, ENT_QUOTES).'" ';
		endforeach; endif;
		$o = '<textarea name="cfm_data[_cfm_'.$name.']" id="cfm_'.$name.'" '.$attrs.'>'.get_post_meta($this->post_id, '_cfm_'.$name, true).'</textarea>';
		if($echo) {
			echo $o;
		}
		return $o;
	}

	// Generate a dropdown menu.  Pass values as an associative array
	function cfm_dropdown($name, $values, $blank = true, $echo = true, $attr = null) {
		if (!is_array($values)) :
			$o = 'No Values';
		endif; //!is_array($value)
		if (is_array($values)) :
			if($blank) {
				$tmp[' '] = 'Select One...';
				$values = $tmp + $values;
			}
			global $post;
			$sel = get_post_meta($this->post_id, '_cfm_'.$name, true);
			$attrs = '';
			if(is_array($attr)) :  foreach($attr as $key => $val) :
				$attrs .= $key.'="'.htmlspecialchars($val, ENT_QUOTES).'" ';
			endforeach; endif;
			$o = '<select name="cfm_data[_cfm_'.$name.']" '.$attrs.' />';			
			foreach($values as $key => $val) :
				if($key == $sel) {
					$selected = ' selected="selected" ';
				} else {
					$selected = '';
				}
				$o .= '<option value="'.$key.'" '.$selected.'>'.$val.'</option>';
			endforeach;
			$o .= '</select>';
		endif; //!is_array($value)
		if($echo) {
			echo $o;
		}
		return $o;
	}


	function cfm_dd_list_states() {
			$o = array('AL'=>"Alabama",
		                'AK'=>"Alaska", 
		                'AZ'=>"Arizona", 
		                'AR'=>"Arkansas", 
		                'CA'=>"California", 
		                'CO'=>"Colorado", 
		                'CT'=>"Connecticut", 
		                'DE'=>"Delaware", 
		                'DC'=>"District Of Columbia", 
		                'FL'=>"Florida", 
		                'GA'=>"Georgia", 
		                'HI'=>"Hawaii", 
		                'ID'=>"Idaho", 
		                'IL'=>"Illinois", 
		                'IN'=>"Indiana", 
		                'IA'=>"Iowa", 
		                'KS'=>"Kansas", 
		                'KY'=>"Kentucky", 
		                'LA'=>"Louisiana", 
		                'ME'=>"Maine", 
		                'MD'=>"Maryland", 
		                'MA'=>"Massachusetts", 
		                'MI'=>"Michigan", 
		                'MN'=>"Minnesota", 
		                'MS'=>"Mississippi", 
		                'MO'=>"Missouri", 
		                'MT'=>"Montana",
		                'NE'=>"Nebraska",
		                'NV'=>"Nevada",
		                'NH'=>"New Hampshire",
		                'NJ'=>"New Jersey",
		                'NM'=>"New Mexico",
		                'NY'=>"New York",
		                'NC'=>"North Carolina",
		                'ND'=>"North Dakota",
		                'OH'=>"Ohio", 
		                'OK'=>"Oklahoma", 
		                'OR'=>"Oregon", 
		                'PA'=>"Pennsylvania", 
		                'RI'=>"Rhode Island", 
		                'SC'=>"South Carolina", 
		                'SD'=>"South Dakota",
		                'TN'=>"Tennessee", 
		                'TX'=>"Texas", 
		                'UT'=>"Utah", 
		                'VT'=>"Vermont", 
		                'VA'=>"Virginia", 
		                'WA'=>"Washington", 
		                'WV'=>"West Virginia", 
		                'WI'=>"Wisconsin", 
		                'WY'=>"Wyoming");
		return $o;
	}
	function cfm_dd_list_posts($post_type = 'post') {
			$args = array(
				'post_type' => $post_type,
				'numberposts' => -1,
				'order' => 'ASC',
				'orderby' => 'title'
				); 
			$posts = get_posts($args);
			if ($posts) {
				foreach ($posts as $post) {
					$o[$post->ID] = $post->post_title;
				}
			}

		return $o;
	}
	
}