<?php
/**
 * Defines the Reviews Settings Administration Panel
 *
 * @package Components\Reviews\Admin\Settings
 */
class APP_Reviews_Settings_Admin extends APP_Conditional_Tabs_Page {

	/**
	 * Sets up the page
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Reviews Settings', APP_TD ),
			'menu_title' => __( 'Reviews Settings', APP_TD ),
			'page_slug' => 'app-reviews-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 11,
			'conditional_parent' => appthemes_reviews_get_args( 'admin_top_level_page' ),
			'conditional_page' => appthemes_reviews_get_args( 'admin_sub_level_page' ),
		);
	}

	function conditional_create_page(){
		$top_level = appthemes_reviews_get_args( 'admin_top_level_page' );
		$sub_level = appthemes_reviews_get_args( 'admin_sub_level_page' );
		if ( ! $top_level &&  ! $sub_level ) {
			return true;
		} else {
			return false;
		}

	}

	function init_tabs() {

		$this->tabs->add( 'reviews', __( 'Reviews', APP_TD ) );

		$this->tab_sections['reviews']['notifications'] = array(
			'title' => __( 'Notifications', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'New Reviews', APP_TD ),
					'type' => 'checkbox',
					'name' => 'notify_new_review',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Notify admin on new reviews?', APP_TD ),
				),
			),
		);

	}

}

