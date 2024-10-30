<?php
/*
Display Class for the Simple Dashboard Plugin
Version 1.0

Author: Justin Estrada
Plugin URI: http://justinestrada.com/wordpress-plugins/simple-dashboard
*/

if ( !class_exists('Custom_Dashboard_Help_Display') ) {
	Class Custom_Dashboard_Help_Display {

	/**
	 *	Add the widget to the dashboard
	 *	Widget is not displayed if the title or content are empty.
	 *
	 *  @package Simple Dashboard
	 *	@subpackage Display
	 * 	@since 2.0
	 * 	@params none
	 * 	@return void
	 */
	function add_dashboard_widget() {
		$options = $this->options;
		if ( !$options ) return;
		if ( !empty($options['title']) && !empty($options['content']) ) {
			wp_add_dashboard_widget( $this->unique_id, $options['title'], array($this, 'dashboard_content') );
		}
	} 

	/**
	 *	Display widget content
	 *  @package Simple Dashboard
	 *	@subpackage Display
	 * 	@since 2.0
	 * 	@params none
	 * 	@return string
	 */
	function dashboard_content() {
		$options = get_option('simple-dashboard');
		echo wpautop(stripslashes($options['content']));
	}

	}	// end class
}	// end if ( !class_exists...

?>