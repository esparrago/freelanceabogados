<?php

add_action('admin_init', 'hrb_get_addons_setup_tab_init');

function hrb_get_addons_setup_tab_init() {
	global $admin_page_hooks;

	add_action( 'tabs_'.$admin_page_hooks['app-payments'].'_page_app-payments-settings', array( 'hrb_get_addons_Settings_Tab', 'init' ) );
}

class hrb_get_addons_Settings_Tab {

	private static $page;

	static function init( $page ) {

		self::$page = $page;

		$page->tabs->add_after( 'general', 'projects', __( 'Addons', APP_TD ) );

		$fields = array();

		foreach ( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {
			$fields = array_merge( $fields, self::generate_fields( $addon ) );
		}

		$page->tab_sections['projects']['addons'] = array(
			'title' => __( 'Projects', APP_TD ),
			'renderer' => array( __CLASS__, 'render' ),
			'fields' => $fields
		);
	}

	static function render( $section ) {
		$columns = array(
			'type' => __( 'Type', APP_TD ),
			'enabled' => __( 'Enabled', APP_TD ),
			'price' => __( 'Price', APP_TD ),
			'duration' => __( 'Duration', APP_TD ),
		);

		$header = '';
		foreach ( $columns as $key => $label ) {
			$header .= html( 'th', $label );
		}

		$rows = '';
		foreach ( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {
			$row = html( 'td', APP_Item_Registry::get_title( $addon ) );

			foreach ( self::generate_fields( $addon ) as $field ) {
				$row .= html( 'td', self::$page->input( $field ) );
			}

			$rows .= html( 'tr', $row );
		}

		echo html( 'table id="featured-pricing" class="widefat"', html( 'tr', $header ), html( 'tbody', $rows ) );
	}

	private static function generate_fields( $addon ) {
		return array(
			array(
				'type' => 'checkbox',
				'name' => array( 'addons', $addon, 'enabled' ),
				'desc' => __( 'Yes', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => array( 'addons', $addon, 'price' ),
				'sanitize' => 'appthemes_absfloat',
				'extra' => array( 'size' => 3 ),
			),
			array(
				'type' => 'text',
				'name' => array( 'addons', $addon, 'duration' ),
				'sanitize' => 'absint',
				'extra' => array( 'size' => 3 ),
				'desc' => __( 'days', APP_TD )
			),
		);
	}
}

