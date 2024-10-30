<?php
/*
Simple Dashboard widget
==============================================================================

Show custom help or announcements on the dashboard

Info for WordPress:
==============================================================================
Plugin Name: Simple Dashboard
Plugin URI: http://justinestrada.com/wordpress-plugins/simple-dashboard
Description: Adds a widget with custom text to the dashboard. Great for Admin-only announcements or making a short README for  site contributors. Display basic help or link to more detailed information.
Version: 1.0
Text Domain: simple-dashboard
Author: Justin Estrada
Author URI: http://justinestrada.com

==============================================================================
License: GNU General Public License, version 2, as published by the Free Software Foundation.

*/

// Don't do any of this unless you're on an admin page...
if ( is_admin() ) {
	global $pagenow;
	//if ( $pagenow == 'index.php' ) 
	simple_dashboard_dashboard_load();
	//$pages = array("plugins.php", "options-general.php");
	//if ( in_array($pagenw, $pages) ) 
	simple_dashboard_admin_load();
}	

// define plugin variables
function simple_dashboard_vars() {
	$vars = array();
	$vars['plugin_title'] = __('Simple Dashboard');
	$vars['version'] = '1.0';
	$vars['unique_id'] = 'simple-dashboard';
	$vars['path'] = plugin_dir_path(__FILE__);
	$vars['plugin'] = plugin_basename(__FILE__);
	$vars['url'] = plugin_dir_url(__FILE__);
	$vars['min_php'] = '5.2.4';
	$vars['min_wp'] = '4.0';
	
	return $vars;
}

function simple_dashboard_css() {
?>
	<style type="text/css">
		.sub-input {
	    display: block;
	    font-size: 12px;
	}
	#simple_dashboard_options_form ul {
	    list-style: circle;
	    padding-left: 15px;
	}
	#simple_dashboard_options_form ul li {
	    margin: 0;
	}
	</style>
<?php
}
add_action( 'admin_head', 'simple_dashboard_css' );

// load display functions
function simple_dashboard_dashboard_load() {
	extract( simple_dashboard_vars() );	
	$options = get_option($unique_id);
	
	if ( $options ) {
		// load the help widget
		$title = ( isset($options['title']) ? $options['title'] : '' );
		if ( !empty($title) ) {
			if ( !class_exists('Custom_Dashboard_Help_Display') ) require_once( $path.'includes/functions_display.php' );
			if ( class_exists('Custom_Dashboard_Help_Display') ) {
				$simple_dashboard = new Custom_Dashboard_Help_Display;
				$simple_dashboard->unique_id = $unique_id;
				$simple_dashboard->options = $options;
				if ( !empty($title) ) add_action( "wp_dashboard_setup", array($simple_dashboard, 'add_dashboard_widget') );
			}
		}
	}
}

// load management functions
function simple_dashboard_admin_load() {	
	extract( simple_dashboard_vars() );	
	$options = get_option($unique_id);

 	$user_can = ( isset($options['user_can']) ? $options['user_can'] : 'manage_options' );
 	
	// 	Avoid problems with current_user_can
	if ( !function_exists( 'wp_get_current_user' ) ) {
		if ( file_exists(ABSPATH.'wp-includes/pluggable.php') ) require_once( ABSPATH.'wp-includes/pluggable.php' );
	}
	
 	if ( function_exists( 'current_user_can' ) && current_user_can( $user_can ) ) {
		require_once( $path.'includes/functions_admin.php' );
		if ( class_exists('Custom_Dashboard_Help_Admin') ) {
			$simple_dashboard = new Custom_Dashboard_Help_Admin;
			$simple_dashboard->base_settings($plugin, $path, $url, $unique_id, $version, $plugin_title, $user_can);
			$simple_dashboard->min_wp = $min_wp;
			$simple_dashboard->min_php = $min_php;	
			
			// Activate the plugin
			register_activation_hook( __FILE__, array($simple_dashboard, 'activate_plugin') );
			
			// Add links on the plugins page (plugins.php)		
			add_filter( "plugin_action_links_$plugin", array($simple_dashboard, 'add_settings_link') );
			add_filter("plugin_row_meta", array($simple_dashboard, 'add_plugin_meta_links'),10,2);

			// Add the plugin to the settings menu
			add_action( 'admin_menu', array($simple_dashboard, 'add_menu') );
		}
	}	else	{	// user can not manage the plugin
		require_once($path.'includes/Simple_Dashboard_Admin/help.php');
		if ( class_exists('Simple_Dashboard_Admin_Help_v1_3') ) {
			add_filter( "plugin_action_links_$plugin", array('Simple_Dashboard_Admin_Help_v1_3', 'remove_plugin_links'),10,1 );
		}
	}	
}


function simple_dashboard_remove_widgets() {
	extract( simple_dashboard_vars() );	
	$options = get_option($unique_id);

	if ($options) {
		if ( $options['removeDashboardWidgets'] ) {
			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );   // Right Now
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' ); // Recent Comments
			remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );  // Incoming Links
			remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );   // Plugins
			remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );  // Quick Press
			remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );  // Recent Drafts
			remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );   // WordPress blog
			remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );   // Other WordPress News
			/* use 'dashboard-network' as the second parameter to remove widgets from a network dashboard. */
		}
	}
}
add_action( 'wp_dashboard_setup', 'simple_dashboard_remove_widgets' );

