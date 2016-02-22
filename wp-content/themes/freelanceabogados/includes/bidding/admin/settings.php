<?php

/**
 * Defines the Payments Settings Administration Panel
 */
class APP_Bidding_Settings_Admin extends APP_Conditional_Tabs_Page {

	/**
	 * Sets up the page
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Bidding Settings', APP_TD ),
			'menu_title' => __( 'Bidding Settings', APP_TD ),
			'page_slug' => 'app-bidding-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
			'conditional_parent' => appthemes_bidding_get_args( 'admin_top_level_page' ),
			'conditional_page' => appthemes_bidding_get_args( 'admin_sub_level_page' ),
		);
	}

	function conditional_create_page(){
		$top_level = appthemes_bidding_get_args( 'admin_top_level_page' );
		$sub_level = appthemes_bidding_get_args( 'admin_sub_level_page' );
		if( ! $top_level &&  ! $sub_level ) {
			return true;
		} else {
			return false;
		}

	}
	/**
	 * Creates the tabs for the page
	 * @return void
	 */
	function init_tabs() {
		$this->tabs->add( 'bidding', __( 'Bidding', APP_TD ) );

		$this->tab_sections['bidding']['notifications'] = array(
			'title' => __( 'Notifications', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'New Bids', APP_TD ),
					'type' => 'checkbox',
					'name' => 'notify_new_bid',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Notify admin on new bids?', APP_TD ),
				),
			),
		);
	}
}

