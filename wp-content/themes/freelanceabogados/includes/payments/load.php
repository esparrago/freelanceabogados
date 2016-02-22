<?php
/**
 * Payments load
 *
 * @package Components\Payments
 */

add_action( 'after_setup_theme', '_appthemes_load_payments', 998 );
add_action( 'after_setup_theme', '_appthemes_load_price_format', 999 );

function _appthemes_load_payments() {
	if ( !current_theme_supports( 'app-payments' ) )
		return;

	require APP_FRAMEWORK_DIR . '/load-p2p.php';
	require_once APP_FRAMEWORK_DIR . '/includes/tables.php';
	require_once APP_FRAMEWORK_DIR . '/admin/class-meta-box.php';
	require_once APP_FRAMEWORK_DIR . '/admin/class-tabs-page.php';

	require dirname( __FILE__ ) . '/utils/queue.php';
	require dirname( __FILE__ ) . '/utils/log.php';

	// Orders
	require dirname( __FILE__ ) . '/order-class.php';
	require dirname( __FILE__ ) . '/order-factory.php';
	require dirname( __FILE__ ) . '/order-draft-class.php';
	require dirname( __FILE__ ) . '/order-receipt-class.php';
	require dirname( __FILE__ ) . '/order-functions.php';
	require dirname( __FILE__ ) . '/order-processing.php';
	require dirname( __FILE__ ) . '/order-upgrade.php';

	require dirname( __FILE__ ) . '/order-templates.php';
	require dirname( __FILE__ ) . '/item-registry.php';

	if( is_admin() ){

		require dirname( __FILE__ ) . '/admin/admin.php';
		require dirname( __FILE__ ) . '/admin/order-list.php';
		require dirname( __FILE__ ) . '/admin/order-single.php';
		require dirname( __FILE__ ) . '/admin/settings.php';
		require dirname( __FILE__ ) . '/admin/order-meta.php';

		APP_Connected_Post_Orders::init();
		APP_Connected_User_Orders::init();

	}

	// Gateways
	require_once( dirname( __FILE__ ) . '/gateways/gateway-class.php' );
	require_once( dirname( __FILE__ ) . '/gateways/boomerang-class.php' );
	require_once( dirname( __FILE__ ) . '/gateways/gateway-registry.php' );
	require_once( dirname( __FILE__ ) . '/gateways/gateway-functions.php' );

	require_once( dirname( __FILE__ ) . '/gateways/paypal/paypal.php' );
	require_once( dirname( __FILE__ ) . '/gateways/bank-transfer/bank-transfer.php' );

	// Load escrow
	require_once( dirname( __FILE__ ) . '/escrow/load.php' );

	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		require_once( dirname( __FILE__ ) . '/gateways/test/test.php' );
	}

	new APP_Order_Summary;
	$processing = new APP_Order_Processing();
	add_action( 'init', array( $processing, 'process' ) );

	extract( appthemes_payments_get_args(), EXTR_SKIP );

	if( !current_theme_supports( 'app-price-format') ){
		add_theme_support( 'app-price-format', array() );
	}

	appthemes_load_items();
	appthemes_load_options();

}

function appthemes_load_items(){

	extract( appthemes_payments_get_args(), EXTR_SKIP );

	if( !empty( $items ) ){
		foreach( $items as $item ){

			if( !isset( $item['type'] ) || !isset( $item['title'] ) )
				continue;

			if( !isset( $item['meta'] ) )
				$item['meta'] = array();

			APP_Item_Registry::register( $item['type'], $item['title'], $item['meta'] );

		}
	}
}

function appthemes_load_options(){

	extract( appthemes_payments_get_args(), EXTR_SKIP );


	if( is_admin() )
		APP_Connected_Post_Orders::add_post_type( $items_post_types );

	if( $options ){
		APP_Gateway_Registry::register_options( $options );
	}
	else{

		$defaults = array(
			'currency_code' => 'USD',
			'currency_identifier' => 'symbol',
			'currency_position' => 'left',
			'thousands_separator' => ',',
			'decimal_separator' => '.',
			'tax_charge' => 0,
			'gateways' => array(
				'enabled' => array()
			)
		);

		$options = new scbOptions( 'payments', false, $defaults );
		APP_Gateway_Registry::register_options( $options );
	}
}

function appthemes_payments_get_args( $option = '' ){

	if( !current_theme_supports( 'app-payments' ) )
		return array();

	list($args) = get_theme_support( 'app-payments' );
	$format = appthemes_price_format_get_args( '', true );
	$defaults = array(
		'items' => array(),
		'items_post_types' => array( 'post' ),
		'options' => false,
		'images_url' => get_template_directory_uri() . '/includes/payments/images/',
		'templates' => array(
			'bank-transfer' => 'order-bank-transfer.php',
		),
		'escrow' => false,
	);

	$final = wp_parse_args( $args, $defaults );
	if( empty( $option ) )
		return $final;
	else
		return $final[ $option ];

}

function _appthemes_load_price_format() {
	if ( !current_theme_supports( 'app-price-format' ) )
		return;

	require dirname( __FILE__ ) . '/utils/currencies.php';
	require dirname( __FILE__ ) . '/utils/numbers.php';
}

function appthemes_price_format_get_args( $option = '', $force = false ){

	if( !current_theme_supports( 'app-price-format' ) && !$force )
		return array();

	list($args) = get_theme_support( 'app-price-format' );
	$defaults = array(
		'currency_default' => 'USD',
		'currency_identifier' => 'symbol',
		'currency_position' => 'left',
		'thousands_separator' => ',',
		'decimal_separator' => '.',
		'hide_decimals' => false,
	);

	$args = wp_parse_args( $args, $defaults );
	if( !in_array( $args['currency_identifier'], array( 'symbol', 'code') ) ){
		$args['currency_identifier'] = 'symbol';
	}

	if( empty( $option ) )
		return $args;
	else
		return $args[ $option ];

}

