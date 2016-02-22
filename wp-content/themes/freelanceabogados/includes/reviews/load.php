<?php
/**
 * Load Reviews module files
 *
 * @package Components\Reviews
 */

add_action( 'after_setup_theme', '_appthemes_load_reviews', 998 );

define( 'APP_REVIEWS_VERSION', '1.0' );

/**
 * Inits the reviews API if supported by the theme.
 */
function _appthemes_load_reviews() {

	if ( !current_theme_supports( 'app-reviews' ) ) {
		return;
	}

	require_once APP_FRAMEWORK_DIR . '/admin/class-meta-box.php';
	require_once APP_FRAMEWORK_DIR . '/admin/class-tabs-page.php';

	// Reviews
	require dirname( __FILE__ ) . '/review-functions.php';
	require dirname( __FILE__ ) . '/review-comments.php';
	require dirname( __FILE__ ) . '/review-factory.php';
	require dirname( __FILE__ ) . '/review-class.php';
	require dirname( __FILE__ ) . '/review-handle.php';
	require dirname( __FILE__ ) . '/review-notify-class.php';
	require dirname( __FILE__ ) . '/review-enqueue.php';
	require dirname( __FILE__ ) . '/review-capabilities.php';

	$options = appthemes_load_reviews_options();

	if ( is_admin() ) {
		require dirname( __FILE__ ) . '/admin/admin.php';
		require dirname( __FILE__ ) . '/admin/settings.php';

		new APP_Review_Admin( $options );
	}

	extract( appthemes_reviews_get_args(), EXTR_PREFIX_ALL, 'review' );

	// Pre fill Relative Rating meta
	add_action( "save_post_{$review_post_type}", 'appthemes_pre_fill_post_rating', 10, 3 );
	add_action( 'profile_update', 'appthemes_pre_fill_user_rating', 10, 2 );
	add_action( 'user_register', 'appthemes_pre_fill_user_rating');

	// inits comment hooks to help handle custom comment types
	APP_Review_Comments::init( $review_comment_type, $review_auto_approve );

	// init email notfications
	APP_Review_Comments_Email_Notify::init( $review_comment_type );

	// init reviews data handling
	APP_Review_Handle::init( $review_comment_type );
}

/**
 * Retrieve reviews default global options.
 */
function appthemes_load_reviews_options() {
	global $app_reviews_options;

	if ( ! empty( $app_reviews_options ) && is_a( $app_reviews_options, 'scbOptions' ) ) {
		return $app_reviews_options;
	}

	$options = appthemes_reviews_get_args( 'options' );

	if ( empty( $options ) || ! is_a( $options, 'scbOptions' ) ) {
		$defaults = array(
			'notify_new_review' => 'yes',
		);
		$options = new scbOptions( 'app_reviews_options', false, $defaults );
	}
	$app_reviews_options = $options;

	return $options;
}

/**
 * Retrieve reviews theme support options.
 */
function appthemes_reviews_get_args( $option = '' ) {

	if ( ! current_theme_supports('app-reviews') ) {
		return array();
	}

	list( $args ) = get_theme_support('app-reviews');

	$defaults = array(
		'comment_type'         => APP_REVIEWS_CTYPE,
		'post_type'            => 'post',
		'auto_approve'         => true,
		'success_rate_min_val' => 3,
		'max_rating'           => 5,
		'admin_top_level_page' => false,
		'admin_sub_level_page' => false,
		'options'              => false,
		'url'                  => get_template_directory_uri() . '/includes/reviews'
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
 * Retrieve the reviews custom comment type.
 */
function appthemes_get_reviews_ctype() {
	return appthemes_reviews_get_args('comment_type');
}
