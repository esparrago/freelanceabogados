<?php

/**
 * Registers a map_provider with the APP_Map Provider_Registry
 * @param  string $class_name Name of the class to be used as a Map Provider
 * @return void
 */
function appthemes_register_map_provider( $class_name ) {
	APP_Map_Provider_Registry::register_map_provider( $class_name );
}

function appthemes_get_map_provider_string_array() {
	$map_providers = array();
	foreach ( APP_Map_Provider_Registry::get_map_providers() as $map_provider ) {
		if( current_user_can( 'manage_options' ) )
				$map_providers[ $map_provider->identifier() ] = $map_provider->display_name( 'dropdown' );	
	}

	return $map_providers;
}

function appthemes_load_map_provider() {
	$map_provider = APP_Map_Provider_Registry::get_active_map_provider();
	$map_provider->options = APP_Map_Provider_Registry::get_map_provider_options( $map_provider->identifier() );
	if ( true !== ( $required = $map_provider->has_required_vars() ) && is_admin() ) {

		$map_provider_tab = $map_provider->identifier() . '-map_provider';
		$url = admin_url(add_query_arg( array(
				'page' => 'app-geo-settings',
				'tab' => $map_provider_tab
			), 'admin.php' ));

		APP_Geo_Settings_Admin::$geo_settings_warning = sprintf( __('%s %s is missing a required settings value: %s, <a href="%s">Click here to update settings.</a>', APP_TD ), $map_provider->display_name( 'admin' ), __('Map Provider', APP_TD ), $required, $url );

		add_action( 'admin_notices', array( 'APP_Geo_Settings_Admin', 'admin_settings_needed_warning' ) );
	}

	if ( true === ( $required = $map_provider->has_required_vars() ) )
		$map_provider->enqueue_scripts();
}