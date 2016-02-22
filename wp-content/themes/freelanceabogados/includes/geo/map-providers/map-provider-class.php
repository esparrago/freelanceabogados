<?php

/**
 * Base class for Map Providers
 */
abstract class APP_Map_Provider {

	/**
	 * Unique identifier for this map_provider
	 * @var string
	 */
	private $identifier;

	/**
	 * Display names used for this Map Provider
	 * @var array
	 */
	private $display;

	public $options;
	public $map_provider_vars;

	public $icon_base_url;

	/**
	 * Creates the Map Provider class with the required information to display it
	 *
	 * @param string  $display_name The display name
	 * @param string  $identifier   The unique indentifier used to indentify your payment type
	 */
	public function __construct( $identifier, $args ) {

		$defaults = array(
			'dropdown' => $identifier,
			'admin' => $identifier,
		);

		$args = wp_parse_args( $args, $defaults );

		$this->display = array(
			'dropdown' => $args['dropdown'],
			'admin' => $args['admin'],
		);

		$this->identifier = $identifier;

	}

	public function map_init( $div_id, $center_lat, $center_lng, $zoom = '', $auto_zoom = false ) {

	}

	public function app_map_pin( $color = '', $shape = '' ) {
		$pins = array(
			'blue',
			'gray',
			'green',
			'orange',
			'purple',
			'red',
			'teal',
			'yellow',
		);

		$pin = !in_array( $color, $pins ) ? 'teal' : $color;

		return get_template_directory_uri() . '/images/map-pin-'.$pin.( !empty( $shape ) ? '-' . $shape : '' ).'.png';
	}

	public function add_marker( $args ){
	
	}

	public function init(){ }

	public function enqueue_scripts() {

		if ( is_admin() ) {
			if ( did_action('admin_enqueue_scripts') ) {
				$this->script_vars();
			} else {
				add_action('admin_enqueue_scripts', array( $this, 'script_vars' ) , 1 );
			}
		} else {
			if ( did_action('wp_enqueue_scripts') ) {
				$this->script_vars();
			} else {
				add_action('wp_enqueue_scripts', array( $this, 'script_vars' ) , 1 );
			}
		}

		$this->_enqueue_scripts();
	}

	public function script_vars() {
		?>
		<script type="text/javascript">
			var appthemes_map_icon = {
				'use_app_icon' : true,
				'app_icon' : 'teal',
				'app_icon_url' : '<?php echo $this->app_map_pin(); ?>',
				'app_icon_base_url' : '<?php echo get_template_directory_uri() . '/images'; ?>',
				'app_icon_width' : '24',
				'app_icon_height' : '25',
				'app_icon_point_x' : '11',
				'app_icon_point_y' : '26',
				'app_icon_click_coords' : [1, 1, 19, 17],
				'app_icon_shadow_url' : '<?php echo get_template_directory_uri() . '/images/map-pin-shadow.png'; ?>',
				'app_icon_shadow_width' : '24',
				'app_icon_shadow_height' : '2',
				'app_icon_shadow_point_x' : '11',
				'app_icon_shadow_point_y' : '2',
				'app_popup_offset_x' : '0',
				'app_popup_offset_y' : '-24',
			};
		</script>
		<?php 

		$this->map_provider_vars();
	}

	private function map_provider_vars() {
		$this->_map_provider_vars();
		if ( !empty( $this->map_provider_vars ) ) {
		?>
		<script type="text/javascript">
		var appthemes_map_vars = {
			<?php
			foreach( $this->map_provider_vars as $map_var_key => $map_var_value  ) {
				echo "'".$map_var_key."' : '".$map_var_value."',"."\n"; 
			}
			?>
		};
		</script>
		<?php 
		}
	}

	public abstract function has_required_vars();
	
	public function _map_provider_vars(){}

	/**
	 * Provides the display name for this Map Provider
	 *
	 * @return string
	 */
	public final function display_name( $type = 'dropdown' ) {
		return $this->display[$type];
	}

	/**
	 * Provides the unique identifier for this Map Provider
	 *
	 * @return string
	 */
	public final function identifier() {
		return $this->identifier;
	}
}
