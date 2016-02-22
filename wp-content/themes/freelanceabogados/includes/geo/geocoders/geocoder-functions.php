<?php

/**
 * Registers a geocoder with the APP_Geocoder_Registry
 * @param  string $class_name Name of the class to be used as a Geocoder
 * @return void
 */
function appthemes_register_geocoder( $class_name ) {

	APP_Geocoder_Registry::register_geocoder( $class_name );

}


function appthemes_geocode_address( $address ) {

	$geocoder = APP_Geocoder_Registry::get_active_geocoder();
	$options = APP_Geocoder_Registry::get_geocoder_options( $geocoder->identifier() );

	$geocoder->options = $options;

	if( $geocoder->has_required_vars() ){
		$geocoder->geocode_address( $address );

		$result = array();
		$result['coords'] = $geocoder->get_coords();
		$result['bounds'] = $geocoder->get_bounds();
		$result['address'] = $geocoder->get_address();

		return $result;
	} else {
		return false;
	}

}

function appthemes_geocode_lat_lng( $lat, $lng ) {

	$geocoder = APP_Geocoder_Registry::get_active_geocoder();
	$options = APP_Geocoder_Registry::get_geocoder_options( $geocoder->identifier() );

	$geocoder->options = $options;

	if( $geocoder->has_required_vars() ) {
		$geocoder->geocode_lat_lng( $lat, $lng );

		$result = array();
		$result['bounds'] = $geocoder->get_bounds();
		$result['address'] = $geocoder->get_address();

		return $result;
	} else {
		return false;
	}

}

function appthemes_get_geocoder_string_array() {
	$geocoders = array();
	foreach ( APP_Geocoder_Registry::get_geocoders() as $geocoder ) {
		if( current_user_can( 'manage_options' ) )
				$geocoders[ $geocoder->identifier() ] = $geocoder->display_name( 'dropdown' );
	}

	return $geocoders;
}

/**
 * Extends APP_Geo_Query::parse_query to utilize the installed APP_Geocoder
 */
class APP_Geo_Query_2 extends APP_Geo_Query {

	function parse_query( $wp_query ) {

		extract( appthemes_geo_get_args() );

		$location = trim( $wp_query->get( 'location' ) );
		if ( !$location )
			return;

		$wp_query->is_search = true;

		$smart_radius = false;

		$radius = is_numeric($wp_query->get( 'radius' )) ? $wp_query->get( 'radius' ) : false;
		if ( !$radius )
			$radius = !empty($default_radius) && is_numeric( $default_radius ) ? $default_radius : false;

		$transient_key = 'app_geo_2_' . md5( $location );

		if ( defined( 'WP_DEBUG' ) )
			$geo_coord = false;
		else
			$geo_coord = get_transient( $transient_key );

		if ( !$geo_coord ) {
			$geocode = appthemes_geocode_address( $location );

			if ( !$radius ) {
				if ( isset( $geocode['bounds'] ) ) {

					$distance_a = self::distance(
						$geocode['bounds']['ne_lat'],
						$geocode['bounds']['sw_lng'],
						$geocode['bounds']['sw_lat'],
						$geocode['bounds']['sw_lng'],
						$unit
					);

					$distance_b = self::distance(
						$geocode['bounds']['ne_lat'],
						$geocode['bounds']['ne_lng'],
						$geocode['bounds']['ne_lat'],
						$geocode['bounds']['sw_lng'],
						$unit
					);

					// Find the longest distance, so we can make a square that covers the full area.
					$longer_distance = $distance_a > $distance_b ? $distance_a : $distance_b;

					// Make a square out of the non-square bounds.
					$distance_c = sqrt( pow($longer_distance, 2) * 2 );

					 
					 // Since distance is a diameter, and since the bounds are a square,
					 // use half the "diameter" of the square to make a radius (circle)
					 // so that it covers the area of the square bounds.
					 
					$radius = $distance_c / 2;

					$smart_radius = true;

				}
			}

			if ( !empty( $geocode['coords']->lat ) ) {
				$geo_coord = array(
					'lat' => $geocode['coords']->lat,
					'lng' => $geocode['coords']->lng,
				);
				set_transient( $transient_key, $geo_coord, 60*60*24*7 ); // Cache for a week
			}
		}

		// Final fallback just in case $wp_query->get( 'radius' ) and default_radius are not set and smart_radius fails due to API not returning a bounds/viewport.
		if ( !$radius )
			$radius = 50;

		if ( $geo_coord ) {
			$wp_query->set( 'app_geo_query', apply_filters('appthemes_geo_query', array(
				'lat' => $geo_coord['lat'],
				'lng' => $geo_coord['lng'],
				'rad' => $radius,
				'smart_radius' => $smart_radius,
			) ) );
		} else {
			// Fall back to basic string matching
			$wp_query->set( 'meta_query', array(
				array(
					'key' => 'address',
					'value' => $location,
					'compare' => 'LIKE'
				)
			) );
		}
	}
}