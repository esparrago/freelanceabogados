<?php

if ( is_admin() ) {
	add_action( 'init', 'appthemes_register_geo_settings', 12);
	add_action( 'tabs_freelance_page_app-geo-settings_page_content', 'appthemes_load_map_provider' );
}

/**
 * Registers the geo settings page
 * @return void
 */
function appthemes_register_geo_settings(){
	new APP_Geo_Settings_Admin( APP_Geocoder_Registry::get_options() );
}

/**
 * Defines the Geo Settings Administration Panel
 */
class APP_Geo_Settings_Admin extends APP_Tabs_Page {

	static $geo_settings_warning;

	/**
	 * Sets up the page
	 * @return void
	 */
	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Geo Settings', APP_TD ),
			'menu_title' => __( 'Geo Settings', APP_TD ),
			'page_slug' => 'app-geo-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 12,
		);

	}

	/**
	 * Creates the tabs for the page
	 * @return void
	 */
	protected function init_tabs() {

		$this->tabs->add( 'general', __( 'General', APP_TD ) );

		$this->tab_sections['general']['geocoders'] = array(
			'title' => __( 'Installed Geocoders', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Geocoder', APP_TD ),
					'type' => 'select',
					'name' => 'geocoder',
					'values' => appthemes_get_geocoder_string_array(),
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
				array(
					'title' => __( 'Default Standard Location Search Radius', APP_TD ),
					'type' => 'text',
					'name' => 'default_radius',
					'extra' => array( 'size' => 2),
					'tip' => __( 'The default search radius used for a location based search. If you leave this blank, the radius will automatically be calculated based on the location query.', APP_TD ),
				),
				
			)
		);

		$geocoders = APP_Geocoder_Registry::get_geocoders();
		foreach ( $geocoders as $geocoder ) {
			if ( APP_Geocoder_Registry::is_geocoder_enabled( $geocoder->identifier() ) || ( !empty( $_POST['geocoder'] ) && $geocoder->identifier() == $_POST['geocoder'] ) ) {
				if ( ( !empty( $_POST['geocoder'] ) && $geocoder->identifier() == $_POST['geocoder'] ) || empty( $_POST['geocoder'] ) ){
					$options = APP_Geocoder_Registry::get_geocoder_options( $geocoder->identifier() );

					$geocoder->options = $options;

					if ( true !== ( $required = $geocoder->has_required_vars() ) ) {

						$geocoder_tab = $geocoder->identifier() . '-geocoder';
						$url = admin_url(add_query_arg( array(
								'page' => $this->args['page_slug'],
								'tab' => $geocoder_tab
							), 'admin.php' ));

						self::$geo_settings_warning = sprintf( __('%s %s is missing a required settings value: %s, <a href="%s">Click here to update settings.</a>', APP_TD ), $geocoder->display_name( 'admin' ), __( 'Geocoder', APP_TD ), $required, $url );

						add_action( 'admin_notices', array( $this, 'admin_settings_needed_warning' ) );

					}
					
					$this->load_settings_tab( $geocoder, 'geocoder' );

				}
			}
		}

		$this->tab_sections['general']['map_providers'] = array(
			'title' => __( 'Installed Map Providers', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Map Provider', APP_TD ),
					'type' => 'select',
					'name' => 'map_provider',
					'values' => appthemes_get_map_provider_string_array(),
				),
			)
		);		

		$map_providers = APP_Map_Provider_Registry::get_map_providers();
		foreach ( $map_providers as $map_provider ) {

			if ( APP_Map_Provider_Registry::is_map_provider_enabled( $map_provider->identifier() ) || ( !empty( $_POST['map_provider'] ) && $map_provider->identifier() == $_POST['map_provider'] ) ) {

				if ( ( !empty( $_POST['map_provider'] ) && $map_provider->identifier() == $_POST['map_provider'] ) || empty( $_POST['map_provider'] ) ) {

					$options = APP_Map_Provider_Registry::get_map_provider_options( $map_provider->identifier() );

					$map_provider->options = $options;

					if ( true !== ( $required = $map_provider->has_required_vars() ) ) {
					
						$map_provider_tab = $map_provider->identifier() . '-map_provider';
						$url = admin_url(add_query_arg( array(
								'page' => $this->args['page_slug'],
								'tab' => $map_provider_tab
							), 'admin.php' ));

						$this->geo_settings_warning = sprintf( __('%s %s is missing a required settings value: %s, <a href="%s">Click here to update settings.</a>', APP_TD ), $map_provider->display_name( 'admin' ), __('Map Provider', APP_TD ), $required, $url );

						add_action( 'admin_notices', array( $this, 'admin_settings_needed_warning' ) );

					}

					$this->load_settings_tab( $map_provider, 'map_provider' );

				}
			}
		}

		$this->tab_sections['general']['map'] = array(
			'title' => __( 'Example Map', APP_TD ),
			'renderer' => array( __CLASS__, 'map_render' ),
			'fields' => array(),
		);

	}

	function admin_settings_needed_warning() {
		if ( !empty( self::$geo_settings_warning ) ) {
			self::admin_msg( self::$geo_settings_warning );
		}
	}
	
	function map_render() {

		$map_provider = APP_Map_Provider_Registry::get_active_map_provider();

		$options = APP_Map_Provider_Registry::get_map_provider_options( $map_provider->identifier() );

		$map_provider->options = $options;

		if ( true !== ( $required = $map_provider->has_required_vars() ) ) {

			$map_provider_tab = $map_provider->identifier() . '-map_provider';
			$url = admin_url(add_query_arg( array(
					'page' => $_GET['page'],
					'tab' => $map_provider_tab
				), 'admin.php' ));

			printf( __('%s is missing a required settings value: %s, <a href="%s">Click here to update settings.</a>', APP_TD ), $map_provider->display_name( 'admin' ), $required, $url );

			return;
		}

		
		echo html('div', array('id'=>'map_div', 'style'=>'margin:10px 0 0 0;width:410px;height:350px;position:relative;'));

		?>
			<script type="text/javascript">
				jQuery(function() {
					var markers_opts = [
						{
							"lat" : 37.789903,
							"lng" : -122.400785,
							'marker_text' : '1',
							'popup_content' : '<h2>AppThemes</h2>',
							'draggable' : true,
							'icon_color' : 'teal',
							'icon_shape' : 'round',
						}
					];

					jQuery('#map_div').appthemes_map({
						zoom: 15,
						auto_zoom: false,
						markers: markers_opts,
						center_lat: 37.789903,
						center_lng: -122.400785,
						marker_drag_end: function( lat, lng ) {
							console.log("lat: " + lat + ", lng: " + lng);
						}
						
					});
				});
			</script>
		<?php

		return;
	}

	/**
	 * Loads the gateway form fields into tabs
	 * @param  string $gateway Gateway identifier
	 * @return array           Array for the checkbox to enable the gateway
	 */
	function load_settings_tab( $class, $type ){

		$types = array(
			'map_provider' => 'Map Provider',
			'geocoder' => 'Geocoder'
		);

		$form_values = $class->form();
		$nicename = $class->identifier();

		$tab_section_name = $nicename  . '-' . $type;

		if( array_key_exists( 'fields', $form_values ) ){

			// Wrap values
			foreach ( $form_values['fields'] as $key => $block ) {

				$value = $block['name'];
				$form_values['fields'][$key]['name'] = array( $type . '_settings', $nicename, $value );

			}

			$this->tab_sections[ $tab_section_name ][ 'general_settings' ] = $form_values;
		}else{

			// Wrap values
			foreach ( $form_values as $s_key => $section ){
				foreach ( $section['fields'] as $key => $block ) {

					$value = $block['name'];
					$form_values[$s_key]['fields'][$key]['name'] = array( $type . '_settings', $nicename, $value );

				}
			}

			$this->tab_sections[ $tab_section_name ] = $form_values;
		}

		// Only add a tab for gateways with a form
		$title = $class->display_name( 'admin' );
		if( $form_values ){
			$this->tabs->add( $tab_section_name, $types[ $type ] . ' - ' . $title );
		}
	}
}
