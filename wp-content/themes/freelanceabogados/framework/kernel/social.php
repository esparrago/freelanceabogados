<?php
/**
 * Social API
 *
 * @package Framework\Social
 */

class APP_Social_Networks {

	static private $networks = array();

	static function register_network( $type, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'title' => $type,
			'base_url' => 'http://' . $type . '.com',
			'user_url' => 'http://' . $type . '.com/%s',
			'sanitize' => array( __CLASS__, 'sanitize_default' ),
		) );
		self::$networks[ $type ] = $args;

	}

	static function get_title( $type ) {

		if ( ! isset( self::$networks[ $type] ) ) {
			return;
		}

		$settings = self::$networks[ $type ];

		return $settings['title'];
	}

	static function get_url( $type, $username_or_url = '' ) {
		return esc_url( self::_get_url( $type, $username_or_url ) );
	}

	static function get_url_raw( $type, $username_or_url = '' ) {
		return esc_url_raw( self::_get_url( $type, $username_or_url ) );
	}

	static private function _get_url( $type, $username_or_url = '' ) {

		if ( ! isset( self::$networks[ $type ] ) ) {
			return;
		}

		$settings = self::$networks[ $type ];

		if ( empty( $username_or_url ) ) {
			return $settings['base_url'];
		}

		$username_or_url = trim( $username_or_url );

		// do a simple URL check on the data added by the user. If the user added an URL use it as the social network URL
		if ( strpos( $username_or_url, 'http' ) === 0 || strpos( $username_or_url, '/' ) !== FALSE ) {
			return $username_or_url;
		}

		return sprintf( $settings['user_url'], $username_or_url );
	}

	static function get_tip( $type ) {

		if ( ! isset( self::$networks[ $type ] ) ) {
			return;
		}

		$settings = self::$networks[ $type ];

		if ( ! isset( $settings['tip'] ) ) {
			$settings['tip'] = sprintf(
				__( 'Enter your %1$s username or URL here. If you add your username the URL will look like this: "%2$s", where "%3$s" is your username.', APP_TD ),
				self::get_title( $type ),
				self::get_url( $type, 'AppThemes' ),
				'AppThemes'
			);
		}

		return $settings['tip'];
	}

	static function sanitize( $type, $input ) {
		$sanitize = self::get_sanitize_method( $type );
		return $sanitize( $input );
	}

	static function get_sanitize_method( $type ) {
		$settings = self::$networks[ $type ];
		return $settings['sanitize'];
	}

	static function sanitize_default( $username ) {
		return sanitize_user( $username, true );
	}

	static function get_support() {
		$networks = array_keys( self::$networks );
		return apply_filters( 'appthemes_social_networks', $networks );
	}
}

APP_Social_Networks::register_network( 'facebook', array(
	'title' => __( 'Facebook', APP_TD ),
) );

APP_Social_Networks::register_network( 'twitter', array(
	'title' => __( 'Twitter', APP_TD ),
) );

APP_Social_Networks::register_network( 'linkedin', array(
	'title' => __( 'LinkedIn', APP_TD ),
	'user_url' => 'http://linkedin.com/in/%s/'
) );

APP_Social_Networks::register_network( 'google-plus', array(
	'title' => __( 'Google+', APP_TD ),
	'base_url' => 'http://plus.google.com/',
	'user_url' => 'http://plus.google.com/%s/',
	'tip' => sprintf(
		__( 'Enter your Google+ ID or URL here. If you add your Google+ ID the URL will look like this: "%s" where the number is your ID.', APP_TD ),
		'http://plus.google.com/108097040296611426034/'
	),
) );

APP_Social_Networks::register_network( 'youtube', array(
	'title' => __( 'YouTube', APP_TD ),
	'user_url' => 'http://youtube.com/user/%s/'
) );

APP_Social_Networks::register_network( 'instagram', array(
	'title' => __( 'Instagram', APP_TD ),
) );

APP_Social_Networks::register_network( 'pinterest', array(
	'title' => __( 'Pinterest', APP_TD ),
) );

APP_Social_Networks::register_network( 'github', array(
	'title' => __( 'Github', APP_TD ),
) );

APP_Social_Networks::register_network( 'path', array(
	'title' => __( 'Path', APP_TD ),
) );

APP_Social_Networks::register_network( 'vimeo', array(
	'title' => __( 'Vimeo', APP_TD ),
) );

APP_Social_Networks::register_network( 'flickr', array(
	'title' => __( 'Flickr', APP_TD ),
) );

APP_Social_Networks::register_network( 'picasa', array(
	'title' => __( 'Picasa', APP_TD ),
	'base_url' => 'http://picasaweb.google.com/',
	'user_url' => 'http://picasaweb.google.com/%s/',
	'tip' => sprintf(
		__( 'Enter your Picasa ID or URL here. If you add your Picasa ID the URL will look like this: "%s" where the number is your ID.', APP_TD ),
		'http://picasaweb.google.com/108097040296611426034/'
	),
) );

APP_Social_Networks::register_network( 'foursquare', array(
	'title' => __( 'Foursquare', APP_TD ),
) );

APP_Social_Networks::register_network( 'wordpress', array(
	'title' => __( 'WordPress', APP_TD ),
	'user_url' => 'http://%s.wordpress.com/'
) );

