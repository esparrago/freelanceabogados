<?php
/**
 * Disputes component.
 *
 * Handles disputes for a post containing a collection of participants.
 *
 * @package Components\Disputes
 */

add_action( 'after_setup_theme', '_appthemes_load_disputes', 999 );

/**
 * Inits the disputes API if supported by the theme.
 */
function _appthemes_load_disputes() {

	if ( ! current_theme_supports('app-disputes') ) {
		return;
	}

	require dirname( __FILE__ ) . '/dispute-functions.php';

	if ( is_admin() ) {
		require dirname( __FILE__ ) . '/admin/settings.php';
		require dirname( __FILE__ ) . '/admin/disputes-list.php';
		require dirname( __FILE__ ) . '/admin/single-dispute.php';
	}

}

/**
 * Retrieve disputes theme support options.
 */
function appthemes_disputes_get_args( $option = '' ) {

	if ( ! current_theme_supports('app-disputes') ) {
		return array();
	}

	list( $args ) = get_theme_support('app-disputes');

	$defaults = array(
		'post_type' => 'post',
		'comment_type' => array( 'dispute' => __( 'Disputes', APP_TD ) ),
		'verbiages_callback' => 'get_post_status_object',
		'labels' => array(
			'disputer'	=> __( 'Seller', APP_TD ),
			'disputee'	=> __( 'Buyer', APP_TD ),
			'disputers'	=> __( 'Sellers', APP_TD ),
			'disputees'	=> __( 'Buyers', APP_TD ),
			'pay' => __( 'Pay Service Provider', APP_TD ),
			'refund' => __( 'Refund Service Buyer', APP_TD ),
		),
		'options' => '',
		'enable_disputes' => true,
		'allow_comments' => false,
		'admin_top_level_page' => false,
		'admin_sub_level_page' => false,
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
