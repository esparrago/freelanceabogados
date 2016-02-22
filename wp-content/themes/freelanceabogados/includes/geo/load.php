<?php

add_action( 'after_setup_theme', '_appthemes_load_geo_2', 999 );

function _appthemes_load_geo_2() {
	if ( !current_theme_supports( 'app-geo-2' ) )
		return;

	require_once APP_FRAMEWORK_DIR . '/admin/class-meta-box.php';
	require_once APP_FRAMEWORK_DIR . '/admin/class-tabs-page.php';

	// Geocoders
	require dirname( __FILE__ ) . '/geocoders/geocoder-class.php';
	require dirname( __FILE__ ) . '/geocoders/geocoder-registry.php';
	require dirname( __FILE__ ) . '/geocoders/geocoder-functions.php';

	require dirname( __FILE__ ) . '/geocoders/google-geocoder.php';

	// Map Providers
	require dirname( __FILE__ ) . '/map-providers/map-provider-class.php';
	require dirname( __FILE__ ) . '/map-providers/map-provider-registry.php';
	require dirname( __FILE__ ) . '/map-providers/map-provider-functions.php';

	require dirname( __FILE__ ) . '/map-providers/google-maps.php';

	if( is_admin() ){
		require dirname( __FILE__ ) . '/admin/settings.php';
	}

	remove_action( 'parse_query', array( 'APP_Geo_Query', 'parse_query' ) ); // Remove (so that it can be overridden) action hook originally registered in framework/includes/geo.php
	add_action( 'parse_query', array( 'APP_Geo_Query_2', 'parse_query' ) );

	extract( appthemes_geo_2_get_args(), EXTR_SKIP );

	if ( $options ){
		APP_Geocoder_Registry::register_options( $options );
		APP_Map_Provider_Registry::register_options( $options );
	} else {
		$defaults = array(
			'geocoder' => 'google',
			'map_provider' => 'google',
			'geo_unit' => 'mi',
		);

		$options = new scbOptions( 'appthemes_geo_2', false, $defaults );

		APP_Geocoder_Registry::register_options( $options );
		APP_Map_Provider_Registry::register_options( $options );
	}
}

function appthemes_geo_2_get_args(){

	if( !current_theme_supports( 'app-geo-2' ) )
		return array();

	list($args) = get_theme_support( 'app-geo-2' );
	$defaults = array(
		'geocoder' => 'google',
		'map_provider' => 'google',
		'geo_unit' => 'mi',
		'options' => false,
	);

	return wp_parse_args( $args, $defaults );
}
