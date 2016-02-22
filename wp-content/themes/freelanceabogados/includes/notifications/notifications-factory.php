<?php
/**
 * Load Notifications module factory classes
 *
 * @package Components\Notifications
 */

class APP_Notification_Factory {

	/**
	 * Prepares and returns a new notification instance
	 *
	 * provides:
	 * appthemes_new_notification
	 *
	 * @return APP_User_Notification The notification object
	 */
	public static function create( $recipient_id, $message, $type = '', $meta = array(), $params = array() ) {
		$notification = new APP_User_Notification( $recipient_id );

		$notification->send( $message, $type, $meta, $params );

		do_action( 'appthemes_new_notification', $notification, $recipient_id );

		return $notification;
	}

	/**
	 * Retrieves the notifications for a specific user
	 *
	 * @param int $user_id The user ID to retrieve notifications from
	 * @param array $params (optional) Parameters to filter notification results
	 * @return array Notifications list
	 */
	public static function get_notifications( $user_id, $params = array() ) {
		return new APP_User_Notifications( $user_id, $params );
	}

	/**
	 * Retrieves a single notification
	 *
	 * @param int $notification_id The notification ID
	 * @return object|null A single notification object
	 */
	public static function get_notification( $notification_id ) {
		list( $user_id,, ) = explode( '-', $notification_id );

		$params = array(
		    'id' => $notification_id,
		);
		$notifications = self::get_notifications( $user_id, $params );

		return reset( $notifications->results );
	}

	/**
	 * Updates a notification status
	 *
	 * @param int $notification_id The notitication ID
	 * @param string $new_status The new status
	 * @return object The notification object
	 */
	public static function update_status( $notification_id, $new_status ) {
		$notification = self::get_notification( $notification_id );

		if ( ! $notification ) {
			return false;
		}

		$notification->set_status( $new_status );

		return $notification;
	}

	/**
	 * Deletes a notification
	 *
	 * @param int $notification_id The notitication ID
	 * @return bool True on succcess, False on failure
	 */
	public static function delete_notification( $notification_id ) {
		$notification = self::get_notification( $notification_id );

		return $notification->delete_meta();
	}

}
