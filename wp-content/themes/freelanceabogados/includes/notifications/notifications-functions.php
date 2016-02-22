<?php
/**
 * Load Notification module API
 *
 * @package Components\Notifications
 */

// default meta key for all the notifications meta
define( 'APP_NOTIFICATIONS_U_KEY', '_app_notifications' );

/**
 * Creates and sends a new notification
 *
 * provides:
 * appthemes_new_notification
 * appthemes_new_notification_[$type]
 *
 * @param int $recipient_id The recipient user ID
 * @param string $message The notification message
 * @param string $type (optional) The notification type
 * @param array	$meta (optional) Additional notification meta
 *
 * 	Default meta keys:
 * 	- id        => Message ID ( default: user_id + uniqid() + current timestamp )
 * 	- time'     => current timestamp
 * 	- subject   => message subject
 * 	- message'  => message to be sent
 * 	- type'     => message type  ( default: notification )
 * 	- status'   => message status ( default: unread )
 *
 * @param array	$params (optional) Notification params.
 *
 * 	Available options:
 * 	- send_mail => notify users by email. Expects associative array with meta data to be used on the email
 *
 * 	Default meta for 'send_mail':
 * 	- recipient_id  => user ID
 * 	- subject       => defaults to the notification subject meta
 * 	- content       => defaults to the notification message
 *
 * @return APP_User_Notification The created notification object
 */
function appthemes_send_notification( $recipient_id, $message, $type = 'notification', $meta = array(), $params = array() ) {
	return APP_Notification_Factory::create( $recipient_id, $message, $type, $meta, $params );
}

/**
 * Retrieves a list of notifications for a specific user given a status or notification type
 *
 * @param int $user_id The user ID to retrieve notifications from
 * @param array $params (optional) Additional parameters to filter notifications results.
 *
 * 	Expects notifications meta keys ( e.g.: 'status' => 'unread' )
 *
 * 	Reserved meta keys:
 * 	- limit     => -1 to returns all notifications; n to return n notifications
 * 	- offset    => Number of notifications to displace or pass over
 * 	- order     => orders notifications (default is 'DESC'). Accepted values: ASC, DESC
 *
 * 	Default meta keys:
 * 	- see $meta param in appthemes_send_notification()
 *
 * @return array The notifications list sorted by time descending. List index represents the notification timestamp
 */
function appthemes_get_notifications( $user_id, $params = array() ) {
	return APP_Notification_Factory::get_notifications( $user_id, $params );
}

/**
 * Retrieves a single notification
 *
 * @param int $notification_id The notification ID
 * @return object|null A single notification object
 */
function appthemes_get_notification( $notification_id ) {
	return APP_Notification_Factory::get_notification( $notification_id );
}

/**
 * Updates a notification status
 *
 * provides:
 * appthemes_notification_[$status]
 *
 * @param int $notification_id The notitication ID
 * @param string $new_status The new status
 * @return object The notification object
 */
function appthemes_set_notification_status( $notification_id, $new_status ) {
	return APP_Notification_Factory::update_status( $notification_id, $new_status );
}

/**
 * Deletes a single notification
 *
 * @param int $notification_id The notification ID
 * @return bool True on succcess, False on failure
 */
function appthemes_delete_notification( $notification_id ) {
	return APP_Notification_Factory::delete_notification( $notification_id );
}

/**
 * Retrieve unread notifications for a user
 *
 * @param int $user_id The user ID
 * @param array $params (optional) See $meta param in appthemes_send_notification()
 * @return array The unread notifications list for the user
 */
function appthemes_get_user_unread_notifications( $user_id, $params = array() ) {
	$defaults = array(
		'status' => 'unread',
	);
	$params = wp_parse_args( $params, $defaults );

	return appthemes_get_notifications( $user_id, $params );
}

/**
 * Retrieve total unread notifications for a user
 *
 * @param int $user_id The user ID
 * @param array $params (optional) See $meta param in appthemes_send_notification()
 * @return int Total number of unread notifications
 */
function appthemes_get_user_total_unread_notifications( $user_id, $params = array() ) {
	$notifications = appthemes_get_user_unread_notifications( $user_id, $params );

	return $notifications->found;
}
