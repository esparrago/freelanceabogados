<?php

/**
 * Geocoder using Google Maps API v3
 */
class APP_Google_Geocoder extends APP_Geocoder{

	private $api_url = 'http://maps.googleapis.com/maps/api/geocode/json';

	/**
	 * Sets up the gateway
	 */
	public function __construct() {
		parent::__construct( 'google', array(
			'dropdown' => __( 'Google', APP_TD ),
			'admin' => __( 'Google', APP_TD )
		) );
	}

	public function has_required_vars() {

		if ( empty( $this->options['geo_region'] ) && empty( $_POST['geocoder_settings']['google']['geo_region'] ) ) return 'Region';
		if ( empty( $this->options['geo_language'] ) && empty( $_POST['geocoder_settings']['google']['geo_language'] ) ) return 'Language';
		if ( empty( $this->options['geo_unit'] ) && empty( $_POST['geocoder_settings']['google']['geo_unit'] )  ) return 'Unit';

		return true;
	}

	public function geocode_address( $address ) {
		$args = array(
			'address' => urlencode($address)
		);

		return $this->geocode_api($args);
	}
	
	public function geocode_lat_lng( $lat, $lng ) {
		$args = array(
			'latlng' => (float) $lat . ',' . (float) $lng,
		);

		return $this->geocode_api($args);
	}

	public function geocode_api( $args ) {

		$defaults = array(
			'region' => 'US',
			'language' => 'en',
		);

		$options = wp_parse_args( $this->options, $defaults );	

		$defaults = array(
			'sensor' => 'false',
			'region' => $options['region'],
			'language' => $options['language'],
		);

		$args = wp_parse_args($args, $defaults);

		$response = wp_remote_get( add_query_arg( $args, $this->api_url ) );
	
		if ( 200 != wp_remote_retrieve_response_code( $response ) )
			return false;

		$this->geocode_results = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( !$this->geocode_results || 'OK' != $this->geocode_results['status'] )
			return false;

		$this->process_geocode();
	}

	public function set_bounds() {

		if ( isset( $this->geocode_results['results'][0]['geometry'] ) ) {

			$geometry = $this->geocode_results['results'][0]['geometry'];

			// bounds are not always returned, so fall back to viewport
			$bounds_type = isset( $geometry['bounds'] ) ? 'bounds' : 'viewport';

			$this->_set_bounds(
				$geometry[$bounds_type]['northeast']['lat'],
				$geometry[$bounds_type]['northeast']['lng'],
				$geometry[$bounds_type]['southwest']['lat'],
				$geometry[$bounds_type]['southwest']['lng'] 
			);
		}
	}
	
	public function set_coords() {
	
		if( isset( $this->geocode_results['results'][0]['geometry']['location'] ) ) {
			$point = $this->geocode_results['results'][0]['geometry']['location'];

			$this->_set_coords( $point['lat'], $point['lng'] );
		}
	}

	public function set_address() {
		if( isset( $this->geocode_results['results'][0]['formatted_address'] ) ) {
			$formatted_address = $this->geocode_results['results'][0]['formatted_address'];
			$this->_set_address( $formatted_address );
		}
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

appthemes_register_geocoder( 'APP_Google_Geocoder' );
