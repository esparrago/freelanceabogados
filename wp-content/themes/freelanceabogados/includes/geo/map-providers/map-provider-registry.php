<?php

/**
 * Keeps track of all registered map_providers and their options
 */
class APP_Map_Provider_Registry{

	/**
	 * Options object containing the Map Provider's options
	 * @var scbOptions
	 */
	public static $options;

	/**
	 * Currently registered map_providers
	 * @var array
	 */
	public static $map_providers;

	/**
	 * Registers a map_provider by creating a new instance of it
	 * @param  string $class_name Class to create an instance of
	 * @return void
	 */
	public static function register_map_provider( $class_name ){

		$instance = new $class_name;
		$identifier = $instance->identifier();

		self::$map_providers[$identifier] = $instance;

	}

	/**
	 * Returns an instance of a registered map_provider
	 * @param  string $map_provider_id Identifier of a registered map_provider
	 * @return mixed              Instance of the map_provider, or false on error
	 */
	public static function get_map_provider( $map_provider_id ){

		if ( !self::is_map_provider_registered( $map_provider_id ) )
			return false;

		return self::$map_providers[$map_provider_id];

	}

	/**
	 * Returns an array of registered map_providers
	 * @return array Registered gatewasys
	 */
	public static function get_map_providers(){

		return self::$map_providers;

	}

	/**
	 * Checks if a given map_provider is registered
	 * @param  string  $map_provider_id Identifier for registered map_provider
	 * @return boolean             True if the map_provider is registered, false otherwise
	 */
	public static function is_map_provider_registered( $map_provider_id ){

		return isset( self::$map_providers[ $map_provider_id ] );

	}

	public static function get_active_map_provider(){

		$active_map_provider = '';
		foreach ( self::$map_providers as $map_provider ) {

			if ( !self::is_map_provider_enabled( $map_provider->identifier() ) )
				continue;

			$active_map_provider = $map_provider;
		}

		return $active_map_provider;

	}

	/**
	 * Checks if a given map_provider is enabled
	 * @param  string  $map_provider_id Identifier for registered map_provider
	 * @return boolean             True if the map_provider is enabled, false otherwise
	 */
	public static function is_map_provider_enabled( $map_provider_id ){

		return self::$options->map_provider == $map_provider_id;

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
	 * Returns the options for the given registered map_provider
	 * @param  string $map_provider_id Identifier for registered map_provider
	 * @return array              Associative array of options. See APP_Map Provider::form()
	 */
	public static function get_map_provider_options( $map_provider_id ){

		return self::$options->get( array( 'map_provider_settings', $map_provider_id ), array() );

	}
}
