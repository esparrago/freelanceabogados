<?php

class APP_Google_Map_Provider extends APP_Map_Provider {
	public function __construct() {
		parent::__construct( 'google', array(
			'dropdown' => __( 'Google', APP_TD ),
			'admin' => __( 'Google', APP_TD )
		) );
	}
	
	public function init() {
		
	}

	public function has_required_vars() {

		if ( empty( $this->options['geo_region'] ) && empty( $_POST['map_provider_settings']['google']['geo_region'] ) ) return 'Region';
		if ( empty( $this->options['geo_language'] ) && empty( $_POST['map_provider_settings']['google']['geo_language'] ) ) return 'Language';
		if ( empty( $this->options['geo_unit'] ) && empty( $_POST['map_provider_settings']['google']['geo_unit'] ) ) return 'Unit';

		return true;
	}

	public function _enqueue_scripts() {
		$google_maps_url = is_ssl() ? 'https://maps-api-ssl.google.com/maps/api/js' : 'http://maps.google.com/maps/api/js';

		$params = array(
			'v' => 3,
			'sensor' => 'false',
			'region' => $this->options['geo_region'],
			'language' => $this->options['geo_language'],
		);

		$google_maps_url = add_query_arg( $params, $google_maps_url );

		wp_enqueue_script( 'google-maps-api', $google_maps_url, array(), '3', true );

		$appthemes_maps_url = get_template_directory_uri() . '/includes/geo/map-providers/google-maps.js';

		wp_enqueue_script( 'appthemes-maps', $appthemes_maps_url, array(), '1', true );

	}

	public function form() {

		$general = array(
			'title' => __( 'General Information', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Region Biasing', APP_TD ),
					'desc' => sprintf( __( 'Find your two-letter ccTLD region code <a href="%s">here</a>.', APP_TD ), 'http://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Country_code_top-level_domains' ),
					'type' => 'text',
					'name' => 'geo_region',
					'extra' => array( 'size' => 2 ),
					'tip' => __( "When a user enters 'Florence' in the location search field, you can let Google know that they probably meant 'Florence, Italy' rather than 'Florence, Alabama'.", APP_TD )
				),
				array(
					'title' => __( 'Language', APP_TD ),
					'desc' => sprintf( __( 'Find your two-letter language code <a href="%s">here</a>.', APP_TD ), 'https://spreadsheets.google.com/pub?key=p9pdwsai2hDMsLkXsoM05KQ&gid=1' ),
					'type' => 'text',
					'name' => 'geo_language',
					'extra' => array( 'size' => 2 ),
					'tip' => __( "Used to let Google know to use this language in the formatting of addresses and for the map controls.", APP_TD )
				),
				array(
					'title' => __( 'Distance Unit', APP_TD ),
					'type' => 'radio',
					'name' => 'geo_unit',
					'values' => array(
						'km' => __( 'Kilometers', APP_TD ),
						'mi' => __( 'Miles', APP_TD ),
					),
					'tip' => __( 'Use Kilometers or Miles for your site\'s unit of measure for distances.', APP_TD ),
				),
			)
		);

		return $general;

	}
}

appthemes_register_map_provider( 'APP_Google_Map_Provider' );
