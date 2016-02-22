<?php

add_action( 'after_setup_theme', '_appthemes_load_bidding', 998 );
add_filter( 'appthemes_ctypes_count_exclude', 'appthemes_exclude_bids_from_comments' );

define( 'APP_BIDDING_VERSION', '1.0' );

/**
 * Inits the bidding API if supported by the theme.
 */
function _appthemes_load_bidding() {

	if ( ! current_theme_supports('app-bidding') ) {
		return;
	}

	// Bidding
	require dirname( __FILE__ ) . '/bid-functions.php';
	require dirname( __FILE__ ) . '/bid-comments.php';
	require dirname( __FILE__ ) . '/bid-notify-class.php';
	require dirname( __FILE__ ) . '/bid-factory.php';
	require dirname( __FILE__ ) . '/bid-class.php';
	require dirname( __FILE__ ) . '/bid-handle.php';
	require dirname( __FILE__ ) . '/bid-capabilities.php';

	$options = appthemes_load_bidding_options();
	$GLOBALS['app_bidding_options'] = $options;

	if ( is_admin() ) {
		require_once APP_FRAMEWORK_DIR . '/admin/class-tabs-page.php';

		require dirname( __FILE__ ) . '/admin/admin.php';
		require dirname( __FILE__ ) . '/admin/settings.php';

		new APP_Bid_Admin( $options );
	}

	extract( appthemes_bidding_get_args(), EXTR_PREFIX_ALL, 'bid' );

	// init bid coment type
	APP_Bid_Comments::init( $bid_comment_type, $bid_auto_approve );

	appthemes_load_bidding_options();
}

/**
 * Retrieve bidding default global options.
 */
function appthemes_load_bidding_options(){

	extract( appthemes_bidding_get_args(), EXTR_SKIP );

	$defaults = array(
		'notify_new_bid' => 'yes',
	);

	return new scbOptions( 'app_bidding', false, $defaults );
}

/**
 * Retrieve bidding theme support options.
 */
function appthemes_bidding_get_args( $option = '' ) {

	if ( ! current_theme_supports('app-bidding') ) {
		return array();
	}

	list( $args ) = get_theme_support('app-bidding');

	$defaults = array(
		'comment_type' => APP_BIDDING_BIDS_CTYPE,
		'post_type' => 'post',
		'auto_approve' => true,
		'admin_top_level_page' => false,
		'admin_sub_level_page' => false,
		//'name' => __( 'Bids', APP_TD ),
		//'singular_name' => __( 'Bid', APP_TD ),
	);

	$final = wp_parse_args( $args, $defaults );

	if ( empty( $option ) ) {
		return $final;
	} elseif ( isset( $final[ $option ] ) ) {
		return $final[ $option ];
	} else {
		return false;
	}

}

/**
 * Retrieve the bids custom comment type.
 */
function appthemes_get_bidding_ctype() {
	return appthemes_bidding_get_args( 'comment_type' );
}

/**
 * Exclude the bids custom comment type from 'comment' counts.
 */
function appthemes_exclude_bids_from_comments( $types ) {
    $types[] = appthemes_get_bidding_ctype();
    return $types;
}
