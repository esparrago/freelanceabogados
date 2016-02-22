<?php

/**
 * Base class for Geocoders
 */
abstract class APP_Geocoder {

	/**
	 * Unique identifier for this geocoder
	 * @var string
	 */
	private $identifier;

	/**
	 * Display names used for this Geocoder
	 * @var array
	 */
	private $display;

	public $options;

	/**
	 * Creates the Geocoder class with the required information to display it
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

	/**
	 * Returns an array representing the form to output for admin configuration
	 * @return array scbForms style form array
	 */
	public abstract function form();

	/**
	 * Returns 
	 * @return 
	 */
	public abstract function has_required_vars();

	/**
	 * Processes a geocode request for an address 
	 * @param  string $address		Address to be geocoded.
	 *
	 * @return void
	 */
	public abstract function geocode_address( $address );

	/**
	 * Processes a reverse geocode request for a lat and lng
	 * @param  string $lat 	  Latitude to be geocoded
	 * @param  string $lat 	  Longitude to be geocoded
	 *
	 * @return void
	 */
	public abstract function geocode_lat_lng( $lat, $lng );

	/**
	 * Returns 
	 * @return 
	 */	
	public abstract function set_bounds();

	/**
	 * Returns 
	 * @return 
	 */	
	public abstract function set_coords();

	/**
	 * Returns 
	 * @return 
	 */	
	public abstract function set_address();

	/**
	 * Returns 
	 * @return 
	 */	
	public function process_geocode() {
		$this->set_bounds();
		$this->set_coords();
		$this->set_address();
		$this->calculate_radius();
	}

	/**
	 * Returns 
	 * @return 
	 */
	public final function _set_bounds( $ne_lat, $ne_lng, $sw_lat, $sw_lng ) {
		$this->bounds = array(
			'ne_lat' => $ne_lat,
			'ne_lng' => $ne_lng,
			'sw_lat' => $sw_lat,
			'sw_lng' => $sw_lng,
		);
	}

	/**
	 * Returns 
	 * @return 
	 */
	public final function _set_coords( $lat, $lng ) {
		$this->coords->lat = $lat;
		$this->coords->lng = $lng;
	}

	/**
	 * Returns 
	 * @return 
	 */
	public final function _set_address( $address ) {
		$this->address = $address;
	}

	/**
	 * Returns 
	 * @return 
	 */
	public final function get_address() {
		return !empty( $this->address ) ? $this->address : false;
	}

	/**
	 * Returns 
	 * @return 
	 */
	public final function get_coords() {
		return !empty( $this->coords ) ? $this->coords : false;
	}

	/**
	 * Returns 
	 * @return 
	 */	
	public final function get_bounds() {
		return !empty( $this->bounds ) ? $this->bounds : false;
	}

	/**
	 * Returns 
	 * @return 
	 */	
	public final function get_radius() {
		return !empty( $this->radius ) ? $this->radius : false;
	}

	/**
	 * Returns 
	 * @return 
	 */	
	protected function distance( $lat_1, $lng_1, $lat_2, $lng_2, $unit ) {
		$earth_radius = ('mi' == $unit) ? 3959 : 6371;

		$delta_lat = $lat_2 - $lat_1;
		$delta_lon = $lng_2 - $lng_1;
		$alpha    = $delta_lat/2;
		$beta     = $delta_lon/2;
		$a        = sin(deg2rad($alpha)) * sin(deg2rad($alpha)) + cos(deg2rad($lat_1)) * cos(deg2rad($lat_2)) * sin(deg2rad($beta)) * sin(deg2rad($beta)) ;
		$c        = asin(min(1, sqrt($a)));
		$distance = 2 * $earth_radius * $c;
		$distance = round( $distance, 4 );

		return $distance;
	}

	public function calculate_radius() {

		if ( empty( $this->bounding_box['ne_lat'] ) ) return false;

		$unit = 'mi';

		$distance_a = $this->distance(
			$this->bounding_box['ne_lat'],
			$this->bounding_box['sw_lng'],
			$this->bounding_box['sw_lat'],
			$this->bounding_box['sw_lng'],
			$unit
		);

		$distance_b = $this->distance(
			$this->bounding_box['ne_lat'],
			$this->bounding_box['ne_lng'],
			$this->bounding_box['ne_lat'],
			$this->bounding_box['sw_lng'],
			$unit
		);

		// Find the longest distance, so we can make a square that covers the full area.
		$longer_distance = $distance_a > $distance_b ? $distance_a : $distance_b;

		// Make a square out of the non-square bounds.
		$distance_c = sqrt( pow($longer_distance, 2) * 2 );

		/* 
		 * Since distance is a diameter, and since the bounds are a square,
		 * use half the "diameter" of the square to make a radius (circle)
		 * so that it covers the area of the square bounds.
		 */
		$this->radius = $distance_c / 2;

	}
	
	/**
	 * Provides the display name for this Geocoder
	 *
	 * @return string
	 */
	public final function display_name( $type = 'dropdown' ) {
		return $this->display[$type];
	}

	/**
	 * Provides the unique identifier for this Geocoder
	 *
	 * @return string
	 */
	public final function identifier() {
		return $this->identifier;
	}

}
