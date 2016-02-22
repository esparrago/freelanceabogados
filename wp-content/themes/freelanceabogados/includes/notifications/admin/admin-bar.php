<?php
/**
 * Notifications admin bar
 *
 * @package Components\Notifications\Admin
 */

add_action( 'admin_bar_menu', 'appthemes_notifications_admin_bar', 90 );

function appthemes_notifications_admin_bar( $wp_admin_bar ){

	$user = wp_get_current_user();
	$notifications = new APP_User_Notifications( $user->ID );

	$unread = count( $notifications->get_notifications( array( 'status' => 'unread' ) ) );

	$wp_admin_bar->add_node( array(
		'id'     => 'app-notifications',
		'parent' => 'top-secondary',
		'title'  => sprintf( __(  '%s Unread', APP_TD ), $unread ),
		'href'   => appthemes_notifications_get_args('notifications_url'),
		'meta'   => array( 'class' => 'opposite' )
	) );

	if( empty( $notifications ) ){
		$wp_admin_bar->add_node( array(
			'id'     => 'app-notification-none',
			'parent' => 'app-notifications',
			'title'  => __( 'No Notifications', APP_TD ),
		) );

	}

	foreach( $notifications->get_notifications() as $notification ){

		// @todo link to theme notifications page

		$wp_admin_bar->add_node( array(
			'id'     => 'app-notification' . $notification->get_ID(),
			'parent' => 'app-notifications',
			'title'  => wp_trim_words( $notification->message, 55, '...' ),
		) );

	}


}
