<?php
/**
 * Load Notifications module files
 *
 * @package Components\Notifications
 */

/**
 * Provides notifications features to any theme. Notifications can additionally notify users by email.
 *
 * Usage example:
 *
 * appthemes_send_notification( $user_id, 'Congratulations! You won the lottery!', 'notification', array( 'subject' => 'Special Message to you' ), 'send_mail' );
 *
 */

add_action( 'after_setup_theme', '_appthemes_load_notifications', 998 );

function _appthemes_load_notifications() {

	if ( ! current_theme_supports( 'app-notifications' ) ) {
		return;
	}

	require_once APP_FRAMEWORK_DIR . '/admin/class-tabs-page.php';

	// notifications
	require dirname( __FILE__ ) . '/notifications-factory.php';
	require dirname( __FILE__ ) . '/notifications-functions.php';
	require dirname( __FILE__ ) . '/notifications-class.php';

	$options = appthemes_load_notifications_options();
	$GLOBALS['app_notification_options'] = $options;

	if ( is_admin() ) {

		require dirname( __FILE__ ) . '/admin/admin.php';
		require dirname( __FILE__ ) . '/admin/settings.php';

		new APP_Notifications_Admin( $options );
	}

	if ( appthemes_notifications_get_args('admin_bar') ) {
		require dirname( __FILE__ ) . '/admin/admin-bar.php';
	}

	appthemes_load_notifications_options();
}

function appthemes_load_notifications_options() {

	extract( appthemes_notifications_get_args(), EXTR_SKIP );

	$defaults = array();
	$options = new scbOptions( 'app_notifications', false, $defaults );

	return $options;
}

function appthemes_notifications_get_args( $option = '' ) {

	if ( ! current_theme_supports('app-notifications') ) {
		return array();
	}

	list($args) = get_theme_support('app-notifications');

	$defaults = array(
		'images_url' => get_template_directory_uri() . '/includes/notifications/images/',
		'admin_top_level_page' => '',
		'admin_sub_level_page' => '',
		'admin_bar' => array(),
	);

	$final = wp_parse_args( $args, $defaults );

	if ( ! empty( $final['admin_bar'] ) ) {
		$admin_bar_defaults = array(
			'unread_count' => 10,
			'notifications_url' => '',
		);

		$final['admin_bar'] = wp_parse_args( $final['admin_bar'], $admin_bar_defaults );
	}

	if ( empty( $option ) ) {
		return $final;
	} elseif ( isset( $final[ $option ] ) ) {
		return $final[ $option ];
	} else {
		return false;
	}

}
