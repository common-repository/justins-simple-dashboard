<?php
/*
Admin Class for WordPress Plugins
Version 1.0

Description: Provides a set of reusable functions required by most plugins for activation and options pages, and a few that are helpful during plugin development and/or troubleshooting

Dependancies: package WP_Help (Simple_Dashboard_Admin_Help)
 
Author: Justin Estrada
Plugin URI: http://justinestrada.com/wordpress-plugins/simple-dashboard
*/

require_once($path.'includes/Simple_Dashboard_Admin/help.php');

if ( !class_exists('Simple_Dashboard_Admin_v1_4') ) {
class Simple_Dashboard_Admin_v1_4 extends Simple_Dashboard_Admin_Help_v1_3 {
/**
 *	Minimum plugin settings
 *
 *	Set variables needed on plugin option pages
 *
 *  @package WP_Admin
 * 	@since 1.0
 * 	@param $plugin string The Wordpress plugin (folder/file.php)
 *	@param $path string The complete plugin folder path (used for include files)
 *	@param $url string The complete plugin folder url (used for script or image links)
 *	@param $unique_id string The plugin text domain
 *	@param $version string The plugin version
 * 	@param $plugin_title string The plugin title (for use on the options page)
 * 	@param $user_can string Minimum user capability required to manage the plugin
 *	@param $additional array Additional settings needed by specific plugins
 * 	@return void
 */
function base_settings($plugin, $path, $url, $unique_id, $version, $plugin_title, $user_can = 'manage_options', $additional = array() ) {
	$this->base_plugin = $plugin;
	$this->base_path = $path;
	$this->base_url = $url;
	$this->unique_id = $unique_id;
	$this->base_version = $version;
	$this->base_plugin_title = $plugin_title;
	$this->user_can = $user_can;
	if ( !empty($additional) ) {
		foreach ( $additional as $key=>$value ) $this->$key = $value;
	}
}

/**
 *	Plugin activation
 *  
 *  Checks minimum requirements.
 *  If met, ensure any previously set options are retained, and new ones set as needed.
 *  If the plugin has additional installation requirements, calls the custom function to perform them.
 * 
 *  @package WP_Plugin_Admin
 * 	@since 1.2
 * 	@params none
 * 	@return void
 */
function activate_plugin() {
	global $wp_version;

	$min_php = ( isset($this->min_php) ? $this->min_php : '5.2.4' );
	$min_wp = ( isset($this->min_wp) ? $this->min_wp : '3.1' );

	$min = true;
	
	if ( version_compare(PHP_VERSION, $min_php) == -1 ) $min = false;	
	if ( version_compare($wp_version, $min_wp) == -1 ) $min = false;
	
	if ( $min == false ) {
		$msg = '<p>'. ucwords(str_replace('-', ' ', $this->unique_id)).__(' requires WordPress version ').$min_wp. __(' and PHP version ').$min_php.'<br />';
		$msg .= __( 'You are running Wordpress version ').$wp_version. __(' and PHP version ').PHP_VERSION.'</p>';
		if ( is_plugin_active($this->base_plugin) ) deactivate_plugins($this->base_plugin);
		exit( $msg );
	}	else	{
		// set the basic options
		$options = $this->get_options();
		update_option($this->unique_id, $options);
		// do any installation steps required by the extends class
		if ( method_exists($this, 'custom_activate_plugin') ) $this->custom_activate_plugin();
	}
}


/**
 *	Display status messages
 *  @package WP_Plugin_Admin 
 * 	@since 1.0
 * 	@param array $msgs Messages to be displayed
 * 	@return string
 */
function display_messages($msgs) {
	if ( !is_array($msgs) ) $msgs = array($msgs);
	return '<div class="updated"><span class="dashicons dashicons-yes"></span> '.implode('<br />', $msgs).'</div>';
}

/**
 *	Add a plugin option page entry to the admin settings menu
 *  @package WP_Plugin_Admin
 * 	@since 1.0
 * 	@params none
 * 	@return void
 */
function add_menu() {
	if ( !isset($this->menu_title) && !property_exists($this, 'menu_title') ) $menu_title = $this->base_plugin_title;
	else $menu_title = $this->menu_title;
	if ( !isset($this->user_can) || !property_exists($this, 'user_can') ) $this->user_can = 'manage_options';
	$this->options_page = add_options_page( $this->base_plugin_title, $menu_title, $this->user_can, $this->unique_id, array($this, 'do_action') );
}

/**
 *	Add a settings link to the plugin at plugins.php
 *  @package WP_Plugin_Admin 
 * 	@since 1.0
 * 	@param array $links Plugin links
 * 	@return array Updated $links array
 */
function add_settings_link($links) {
	$settings_link = '<a href="options-general.php?page='.$this->unique_id.'">'._('Settings').'</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

/**
 *	Add additional links to the plugin description at plugins.php
 *  @package WP_Plugin_Admin 
 * 	@since 1.2
 * 	@param array $links Plugin links
 *	@param string $file current plugin file
 * 	@return array Updated $links array
 */
function add_plugin_meta_links($links, $plugin) {
	if ( $plugin == $this->base_plugin ) {
		if ( method_exists($this, 'add_custom_meta_links') ) {
			$new_links = $this->add_custom_meta_links();
			if ( !is_array($new_links) ) $new_links = array($new_links);
			$links = array_merge($links, $new_links);
		}
	}
	return $links;
}

/**
 *	Get plugin options and/or set defaults
 *  @package WP_Admin
 * 	@since 1.0
 * 	@params none
 * 	@return array $options Plugin Options, including any missing defaults
 */
function get_options() {
	$options = get_option($this->unique_id);
	if ( !$options ) $options = array();
	
	if ( method_exists($this, 'default_options') ) {
		$defaults = $this->default_options(); // set by the child class
		foreach ( $defaults as $key=>$value ) {
			if ( !isset($options[$key]) ) $options[$key] = $value;
		}
	}
	return $options;
}

/**
 *	Display a text file
 *	If Markdown Converter Plus is available, converts the text file from Markdown to HTML, applying any additional formatting set in the optional $settings array. Otherwise, outputs the text file after running nl2br() on the content. 
 *
 *  @package WP_Plugin_Admin 
 * 	@since 1.0
 *	@param string $file File name
 * 	@param boolean $backlink True adds a link to the main plugin options page
 *  @param array $settings Optional array of additional settings
 * 	@return string The file content for on-screen display, or an error message if the file can not be found
 */
function show_text_file($file='readme.txt', $back_link = true, $settings = array() ) {
	$path = str_replace(WP_PLUGIN_DIR.'/', '', $this->base_path);
	if ( $back_link ) echo '<div class="clear"><a href="options-general.php?page='.$this->unique_id.'">'._('Return to the Settings Page').'</a></div>';

	$markdown = 'markdown-converter-plus/markdown-converter-plus.php';
	
	if ( !class_exists('Wordpress_Markdown_Converter_Plus') && file_exists(WP_PLUGIN_DIR.'/'.$markdown) ) {
		require_once( WP_PLUGIN_DIR.'/'.$markdown );
	}	else {
		if ( !class_exists('Markdown_Converter_Plus') && file_exists(WP_PLUGIN_DIR.'/'.plugin_basename(__FILE__).'/'.$markdown) )	{
			require_once( WP_PLUGIN_DIR.'/'.plugin_basename(__FILE__).'/'.$markdown );
		}
	}
	
	if ( class_exists('Markdown_Converter_Plus') ) {
		$params = array('file' => $file, 'path' => $path);
		if ( !empty($settings) ) $params = array_merge($params, $settings);
		
		// ensure defaults have reasonable values
		$defaults = array('remove_h1'=>true, 'reformat'=>true, 'append_file_name'=>true, 'convert_readme_links'=>true, 'screenshots'=>true, 'anchors' => 'both', 'add_top_link'=>true, 'top_link_text'=>'['.__('Top of Page').']', 'contributors'=>array('Justin Estrada'=>'http://justinestrada.com/wordpress_plugins.php?plugin='.$this->unique_id), 'download_link' =>'http://justinestrada.com/wordpress_plugins.php?plugin='.$this->unique_id );
		foreach ( $defaults as $key=>$value ) if ( !isset($params[$key]) ) $params[$key] = $value;
	
		$markdown2HTML = new Markdown_Converter_Plus;
		return $markdown2HTML->markdown_to_HTML($params);
		
	}	else	{
		$path = WP_PLUGIN_DIR.'/'.$path;
		if ( substr($path, -1) != '/' ) $path.= '/';
		if ( !$content = @file_get_contents($path.$file) ) {
			return sprintf( __("The %s file was missing or unreadable"), $file).$path;
		}	else	{
			if ( $this->unique_id != 'markdown-converter-plus' ) {
				$top = '<h4>Text below is display from readme.txt file.</h4>';
			}	else	{
				$top = '<br />';
			}
			$content = nl2br($content);
			return $top.$content;
		}
	}	
}



/**
 *	Output a WordPress editor meta-box
 *
 *	Versions prior to 3.3 require editor_init($screen_id) to be called during the page load, to set up the necessary javascript.
 *  3.3 or higher replaces the_editor() with wp_editor();
 * 	
 *  @package WP_Plugin_Admin 
 * 	@since 1.2
 * 	@param string $content Content to be displayed in the text area
 * 	@return string
 */
function make_editor($content) {
	if ( !function_exists('wp_editor') ) require_once( ABSPATH.'wp-includes/general-template.php' );
	wp_editor(stripslashes($content), 'content' );
}

/**
 *	Output a basic plugin footer
 *
 *	Outputs a plugin footer with copyright notice and links to the author and plugin home pages.
 *
 *  @package WP_Plugin_Admin 
 * 	@since 1.0
 * 	@param string $add Additional text to add to the default footer
 * 	@return string
 */
function basic_footer($add = '') {
	$year = date('Y');
	$str = '<div style="border-top: 1px solid; width:100%;margin:1em 0;clear:both;">';
	$str .= '<p><a href="http://justinestrada.com" title="'.$this->base_plugin_title.'">'.$this->base_plugin_title.'</a> &copy; '.$year.' <a href="mailto:justin@justinestrada.com" title="Email Justin Estrada" target="_blank">Justin Estrada</a></p>';
	$str .= $add;
	$str .= '</div>';
	return $str;
}


/**
 *	Check user access against an array of allowed roles.
 *  Default is administrator only (1/2014)
 *
 *  @package WP_Plugin_Admin 
 * 	@since 1.3
 * 	@param none
 * 	@return string $role
 */
function check_user_access() {
	if ( property_exists($this, 'allowed_roles') ) $allowed_roles = $this->allowed_roles;
	else $allowed_roles = array('administrator');
	global $user_ID;
	if ( !empty($user_ID) ) {
		$user = new WP_User($user_ID);
		if (!is_array($user->roles)) $user->roles = array($user->roles);
			foreach ($user->roles as $role) {
	  			if (in_array($role, $allowed_rows)) {
				return $role;
			}
		}
	}
	return false;
}

/**
 *	Create a pulldown menu from an array
 *
 *  @package WP_Plugin_Admin 
 * 	@since 1.1
 * 	@param str $label The select name
 * 	@param array $options The option values
 *	@param str $selected The selected value
 *	@param str $js The select javascript
 * 	@return string
 */
function pulldown_menu_1($label, $options, $selected, $js='') {
	$str = '<select name="'.$label.'" id="'.$label.'" '.$js.'>';
	foreach ( $options as $o ) {
		$s = ( $selected == $o ? ' selected="selected"' : '' );
		$str .= "<option$s>$o</option>";
	}
	$str .= '</select>';
	return $str;
}
/**
 *	Create pulldown menu from a 2-d array
 *
 *  @package WP_Plugin_Admin 
 * 	@since 1.2
 * 	@param str $label The select name
 * 	@param array $options a 2-dimensional array of keys and values
 *	@param str $selected The selected value
 *	@param str $js The select javascript
 * 	@return string
 */
function pulldown_menu_2($label, $options, $selected, $js='') {
	$str = '<select name="'.$label.'" id="'.$label.'" '.$js.'>';
	foreach ( $options as $o=>$v ) {
		$s = ( $selected == $o ? ' selected="selected"' : '' );
		$str .= '<option'.$s.' value="'.$o.'">'.$v.'</option>';
	}
	$str .= '</select>';
	return $str;
}

/**
 *	Show screen info on admin pages
 *
 *  @package WP_Plugin_Admin 
 * 	@since 1.1
 * 	@params none
 * 	@return void
 */
function show_screen_info($screen_vars = false) {	
	global $current_screen, $pagenow;
	$input = array();
	$screen_vars = array('page_title', 'id', 'parent_base', 'parent_file', 'action', 'taxonomy');

	foreach ( $screen_vars as $var ) $input[$var] = '';
	$title = get_admin_page_title();
	if ( $title ) $input['page_title'] = $title;
	else $input['page_title'] = '';
		
	foreach ( $current_screen as $key=>$value ) {
		if ( in_array($key, $screen_vars) ) $input[$key] = $value;
	}
	$str = '<div style="clear:both;background-color:white;margin-top:5em;">';
	$str .= "<p>pagenow = $pagenow</p>";
	foreach ( $input as $key=>$value ) $vars[] = $key.' = '.$value;
	$str .= '<!-- screen_data_start -->'.implode("<br />", $vars).'<!-- screen_data_end -->';
	$str .= '</div>';
	echo $str;
}

}	// end class
}	// end if ( !class_exists...
?>