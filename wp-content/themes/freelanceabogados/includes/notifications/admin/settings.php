<?php
/**
 * Defines the Notifications Settings Administration Panel
 *
 * @package Components\Notifications\Admin\Settings
 */
class APP_Notifications_Settings_Admin extends APP_Conditional_Tabs_Page {

	/**
	 * Sets up the page
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Notifications Settings', APP_TD ),
			'menu_title' => __( 'Notifications Settings', APP_TD ),
			'page_slug' => 'app-notifications-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
			'conditional_parent' => appthemes_notifications_get_args( 'admin_top_level_page' ),
			'conditional_page' => appthemes_notifications_get_args( 'admin_sub_level_page' ),
		);
	}

	function conditional_create_page(){
		$top_level = appthemes_notifications_get_args( 'admin_top_level_page' );
		$sub_level = appthemes_notifications_get_args( 'admin_sub_level_page' );
		if( ! $top_level &&  ! $sub_level )
			return true;
		else
			return false;

	}
	/**
	 * Creates the tabs for the page
	 * @return void
	 */
	function init_tabs() {
	}

}
