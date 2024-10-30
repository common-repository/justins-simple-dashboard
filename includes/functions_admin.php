<?php
/*
Admin Class for the Simple Dashboard Plugin
Version 1.0

Author: Justin Estrada
Plugin URI: http://justinestrada.com/wordpress-plugins/simple-dashboard
*/

require_once($path.'includes/Simple_Dashboard_Admin/admin.php');	// add the plugin admin class

if ( !class_exists('Custom_Dashboard_Help_Admin') ) {
Class Custom_Dashboard_Help_Admin extends Simple_Dashboard_Admin_v1_4 {

/**
 *	Activate the plugin
 *
 *	Allows this plugin to add additional to the basic activate_plugin() in package Plugin Admin
 *
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 2.2
 * 	@params none
 * 	@return void
*/


/**
 *	Add custom links on the plugins list page at plugin.php
 *
 *	Allows this plugin to add additional links to the plugin description
 *
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 3.0
 * 	@params none
 * 	@return array $links
*/
function add_custom_meta_links() {
	$links = array();
	$links[] = '<a href="mailto:justin@justinestrada.com">'.__('Help Contact', $this->unique_id).'</a>';
	
	return $links;
}


/**
 *	Set default options
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 2.2
 * 	@params none
 * 	@return array of options
 */
function default_options() {
	$options = array();
	$options['version'] = $this->base_version;
	$options['title'] = '';
	$options['content'] = '';
	$options['removeDashboardWidgets'] = '';
	$options['user_can'] = 'manage_options';
	$options['lock_position'] = false;
	$options['background'] = '';
	$options['color'] = '';
	$options['widgets'] = array();
	$options['upgrade'] = false;
	return $options;
}	

/**
 *	Plugin options page form handler
 *
 *	Calls appropriate function when user enters the plugin options page or selects an action.
 *
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 2.2
 * 	@params none
 * 	@return void
 */
function do_action() {
	if ( function_exists( 'current_user_can' ) && !current_user_can( $this->user_can ) ) exit( __( 'You do not have permission to be here', $this->unique_id) );	
	
	$saved = __( "Your options have been saved. Don't forget to visit the <a href='".get_site_url()."/wp-admin/index.php'>Dashboard</a> to make sure the widget looks the way you expected, and test any links in your content.", $this->unique_id );
	$not_saved = __( "Your options were not updated. Did you make any changes?", $this->unique_id );
	$reminder = __( 'Reminder: your widget will not display if the title is blank.', $this->unique_id );
	$cancelled = __( 'Your changes were cancelled', $this->unique_id);
	$choices = array('save'=>__('Save Changes', $this->unique_id), 'delete'=>__( 'Save This and Delete the Old Version', $this->unique_id ), 'cancel'=>__('Cancel', $this->unique_id), 'readme'=>__('View Readme File', $this->unique_id), 'options'=>__('Manage Options', $this->unique_id), 'copy'=>__('Copy From Version 1', $this->unique_id) );

	$top = $this->base_plugin_title;
	$msgs = array();

	if ( !isset($_POST['simple_dashboard_submit']) || $_POST['simple_dashboard_submit'] != $choices['readme'] ) $top .= __(': Settings', $this->unique_id);

	echo '<div class="wrap"><h2>'.$top.'</h2>';
	
	if ( isset($_POST['simple_dashboard_submit']) )	{
		check_admin_referer('simple_dashboard_nonce');
		$action = $_POST['simple_dashboard_submit'];
		switch ( $action )	{
			case( $choices['save']):
				$result = $this->save_options();
				if ( $result ) $msgs[] = $saved;
				else $msgs[] = $not_saved;
				$options = $this->get_options();
				if ( empty($options['title']) ) $msgs[] = $reminder;
				$this->manage_options($choices, $msgs);
			break;
			
			case( $choices['cancel'] ):
				$msgs[] = $cancelled;
				$this->manage_options($choices, $msgs);
			break;
			
			case( $choices['readme'] ):
				echo $this->show_text_file();
			break;
		}
	}	else	{
		$this->manage_options($choices, $msgs);
	}
	
	echo '</div>';
	echo $this->basic_footer();
}

/**
 *	Save plugin options
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 2.2
 * 	@params none
 * 	@return void
 */
function save_options()	{
	$input = $this->validate_options();
	return update_option( $this->unique_id, $input );
}

/**
 *	Validate user options
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 2.2
 * 	@params none
 * 	@return array Validated user input
 */
function validate_options()	{
	$input = $this->get_options();
	//$input = $this->default_options();

	$input['title'] = ( isset($_POST['title']) ? trim(preg_replace('/\s+/', ' ', wp_filter_nohtml_kses(stripslashes($_POST['title'])))) : '' );
	$input['content'] = ( isset($_POST['content']) ? wp_filter_post_kses(stripslashes($_POST['content'])) : '' );
	$input['removeDashboardWidgets'] = ( isset($_POST['removeDashboardWidgets']) ? '1' : '0' );

	return($input);
}

/**
 *	Manage plugin options
 *  @package Simple Dashboard
 *	@subpackage Admin
 * 	@since 2.2
 * 	@param array $choices Text strings for submit buttons
 *	@params array $msgs Status messages generated by the previous submit
 * 	@return void
 */
function manage_options($choices, $msgs) {
	$options = $this->get_options();
	/* var_dump($options); */
	$buttons = array('<div class="clear" style="margin:1em 0;">');
	$buttons[] = '<input type="submit" name="simple_dashboard_submit" class="button-primary" value="'.$choices['save'].'" />';
	$buttons[] ='<input type="submit" class="button-primary" name="simple_dashboard_submit" value="'.$choices['cancel'].'" />';
	$buttons[] = '<input type="submit" name="simple_dashboard_submit" value="'.$choices['readme'].'" />';
	$buttons[] = '</div>';

	if ( !empty($msgs) ) echo $this->display_messages($msgs);
	
	echo '<form method="post" name="simple_dashboard_options" id="simple_dashboard_options_form" action="">';
	wp_nonce_field('simple_dashboard_nonce');
	
	//if ( !$options['upgrade'] )	echo implode(' ', $buttons);
	
	echo '
	<h3>'.__('Dashboard Widget Title and Content').'</h3>
	<p><strong><span class="dashicons dashicons-welcome-write-blog"></span> '.__( 'Title:').'</strong> 
	<input type="text" size="30" maxlength="63" name="title" value="'.$options['title'].'" /> '.__('<span class="sub-input">'.__('If left blank, the widget will not appear on the dashboard.').'</span>', $this->unique_id);	
	echo '</p>';
	
	$this->make_editor($options['content']);

	echo '<h3>'.__('Dashboard Extra Options').'</h3>';
	echo '<label for="remove_dashboard_widgets"><span class="dashicons dashicons-admin-post"></span></label>&nbsp;<input type="checkbox" name="removeDashboardWidgets" value="1"';
  	if ($options['removeDashboardWidgets'] == 1){
    	echo ' checked/>';
  	} else {
    	echo ' />';    
  	}
  	echo '<span class="sub-input">'.__('Remove all other vanilla WordPress Dashboard widgets.').'<br/>'.__('Specifically:').'</span>';
  	echo '<ul>
  			<li>Removes Right Now</li>
  			<li>Removes Recent Comments</li>
  			<li>Removes Incoming Links</li>
  			<li>Removes Plugins</li>
  			<li>Removes Quick Press</li>
  			<li>Removes Recent Drafts</li>
  			<li>Removes WordPress blog</li>
  			<li>Removes Other WordPress News</li>
  		  </ul>';

	echo implode(' ', $buttons);
	echo '</form>';
}


}	// end class
}	// end if ( !class_exists...
?>