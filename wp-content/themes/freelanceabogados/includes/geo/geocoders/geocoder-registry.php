<?php

/**
 * Keeps track of all registered geocoders and their options
 */
class APP_Geocoder_Registry{

	/**
	 * Options object containing the Geocoder's options
	 * @var scbOptions
	 */
	public static $options;

	/**
	 * Currently registered geocoders
	 * @var array
	 */
	public static $geocoders;

	/**
	 * Registers a geocoder by creating a new instance of it
	 * @param  string $class_name Class to create an instance of
	 * @return void
	 */
	public static function register_geocoder( $class_name ){

		$instance = new $class_name;
		$identifier = $instance->identifier();

		self::$geocoders[$identifier] = $instance;

	}

	/**
	 * Returns an instance of a registered geocoder
	 * @param  string $geocoder_id Identifier of a registered geocoder
	 * @return mixed              Instance of the geocoder, or false on error
	 */
	public static function get_geocoder( $geocoder_id ){

		if ( !self::is_geocoder_registered( $geocoder_id ) )
			return false;

		return self::$geocoders[$geocoder_id];

	}

	/**
	 * Returns an array of registered geocoders
	 * @return array Registered gatewasys
	 */
	public static function get_geocoders(){

		return self::$geocoders;

	}

	/**
	 * Checks if a given geocoder is registered
	 * @param  string  $geocoder_id Identifier for registered geocoder
	 * @return boolean             True if the geocoder is registered, false otherwise
	 */
	public static function is_geocoder_registered( $geocoder_id ){

		return isset( self::$geocoders[ $geocoder_id ] );

	}

	/**
	 * Returns an array of active geocoders
	 * @return array Active geocoders
	 */
	public static function get_active_geocoder(){

		$active_geocoder = '';
		foreach ( self::$geocoders as $geocoder ) {

			if ( !self::is_geocoder_enabled( $geocoder->identifier() ) )
				continue;

			$active_geocoder = $geocoder;
		}

		return $active_geocoder;

	}

	/**
	 * Checks if a given geocoder is enabled
	 * @param  string  $geocoder_id Identifier for registered geocoder
	 * @return boolean             True if the geocoder is enabled, false otherwise
	 */
	public static function is_geocoder_enabled( $geocoder_id ){

		return self::$options->geocoder == $geocoder_id;

	}

	/**
	 * Registers an instance of scbOptions as the options handler
	 * Warning: Only use if you know what you're doing
	 * 
	 * @param  scbOptions $options Instance of scbOptions
	 * @return void
	 */
	public static function register_options( scbOptions $options ){

		self::$options = $options;

	}

	/**
	 * Returns the registered instance of the options handler
	 * @return scbOptions
	 */
	public static function get_options(){

		return self::$options;

	}

	/**
	 * Returns the options for the given registered geocoder
	 * @param  string $geocoder_id Identifier for registered geocoder
	 * @return array              Associative array of options. See APP_Geocoder::form()
	 */
	public static function get_geocoder_options( $geocoder_id ){

		return self::$options->get( array( 'geocoder_settings', $geocoder_id ), array() );

	}

}
