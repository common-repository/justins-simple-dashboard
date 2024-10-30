<?php
/*
Shared Help Class for WordPress Plugins
Version 1.0

Description: Provides a set of reusable functions to display contextual help, and other functions which might be called when a user can not manage a plugin.

Dependancies: none
 
Author: Justin Estrada
Plugin URI: http://justinestrada.com/wordpress-plugins/simple-dashboard
*/

if ( !class_exists('Simple_Dashboard_Admin_Help_v1_3') ) {
class Simple_Dashboard_Admin_Help_v1_3 {

/**
 *	Add a contextual help tab
 *  @package Plugin Help
 * 	@since 1.0
 *
 *  Replaces contextual help for WordPress version 3.3 and higher
 *
 * 	@params none
 * 	@return none
 */ 
function add_help_tab() {
	if ( method_exists($this, 'contextual_help') ) {	// from the extends class
		$screen = get_current_screen();

		$content = $this->contextual_help($screen->id);
		if ( !$content ) return;
	
		$params = array();
		if ( !is_array($content) ) $params[] = array('content'=>$content);
		else $params = $content; 

		//$help = array();
		foreach ( $params as $key=>$p ) {
			if ( isset($p['content']) && !empty($p['content']) ) {
				$content = $p['content'];
				if ( isset($p['id']) && !empty($p['id']) ) $id = $p['id'];
				else $id = ( isset($this->unique_id) ? $this->unique_id.'_'.$key : base_convert(rand(10e16, 10e20), 10, 36).'_'.$key );
				if ( isset($p['title']) && !empty($p['title']) ) $title = $p['title'];
				else $title = ( isset($this->base_plugin_title) ? $this->base_plugin_title : __('Additional Help', $this->unique_id) );
				
				$screen->add_help_tab( array(
					'id'	=> $id,
					'title'	=> $title,
					'content'	=> $content
					)
				);
			}
		}
	}
}

/**
 *	Remove plugin links
 *
 * 	If plugin access has been restricted, remove all links and display a notice.
 *
 *  @package WP_Help
 * 	@since 1.0
 * 	@param array $links Plugin links
 * 	@return string Restricted notice
 */
function remove_plugin_links($links)	{
	$links = array( _('Access is Restricted') );
	return $links;
}


}	// end class
}	// end if ( !class_exists...
?>