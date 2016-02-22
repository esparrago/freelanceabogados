<?php
/**
 * Notifications module classes
 *
 * @package Components\Notifications
 */

/**
 * Class that provides user notifications
 * Notifications are stored as user meta
 *
 */
class APP_User_Notification {

	/**
	 * The notification recipient user id
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * The notification unique id
	 * @var int
	 */
	protected $id = 0;

	/**
	 * The notification meta
	 * @var array
	 */
	protected $meta = array(
		'time'		=> '',
		'subject'	=> '',
		'message'	=> '',
		'sender'	=> '',
		'type'		=> 'info',
		'status'	=> 'unread',
	);

	/**
	 * Sets up the notification object
	 *
	 * @param int user_id The notification recipient user id
	 * @param array data (optional) Data to pre-populate the notification meta
	 */
	public function __construct( $user_id, $data = '' ) {
		$this->user_id = $user_id;

		if ( empty( $data['id'] ) ) {
		// generate a unique id for new notifications
			$this->id = $user_id . '-' . uniqid() . '-' . current_time( 'timestamp' );
		} else {
			$this->id = $data['id'];
		}

		if ( $data ) {
			$this->meta = wp_parse_args( $data, $this->meta );
		}

	}

	/**
	 * Sends a notification to the user (stores the notification as user meta)
	 *
	 * @param string $message The notification message
	 * @param string $type (optional) The notification type
	 * @param array $meta (optional) Additional notification meta
	 * @param array $params (optional) Notification params. Available options: 'send_mail'
	 */
	public function send( $message, $type = 'notification', $meta = array(), $params = array() ) {

		$this->meta = array_merge( $this->meta, array(
			'id'		=> $this->id,
			'time'		=> current_time( 'timestamp' ),
			'message'	=> $message,
			'type'		=> $type,
			'status'	=> 'unread',
		) );
		$this->meta = wp_parse_args( $meta, $this->meta );

		$this->set_status( $this->meta['status'] );

		do_action( "appthemes_new_notification_$type", $this, $this->user_id );

		if ( ! empty( $params['send_mail'] ) ) {
			$this->send_email( $params['send_mail'] );
		}

	}

	/**
	 * Notifies the message recipient by email
	 *
	 * @param bool|array (optional) $params A boolean value or an associatiave array with email data.
	 *										If set to TRUE, will use notification data.
	 *										If using an associative array, expects: (int|array) 'recipient_id', (string) 'subject', (string) 'content'
	 */
	protected function send_email( $params = '' ) {

		if ( ! is_array( $params ) ) {
			$params = array( $params );
		}

		$defaults = array(
			'recipient_id'	=> $this->user_id,
			'subject'		=> sprintf( _x( '[%1$s] %2$s', 'Email subject: 1 - blog name, 2 - subject', APP_TD ), get_bloginfo( 'name' ), $this->subject ),
			'content'		=> $this->message,
		);
		$params = wp_parse_args( $params, $defaults );

		extract( $params );

		foreach( (array) $recipient_id as $user_id ) {
			$recipient = get_user_by( 'id', $user_id );
			if ( ! $recipient ) {
				trigger_error( 'Recipient ID is not a valid user ID.', E_USER_WARNING );
			}

			$to[] = $recipient->user_email;
		}

		appthemes_send_email( $to, $subject, $content );
	}

	/**
	 * Retrieve data from inaccessible properties
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->get_data() ) ) {
			return $this->get_data( $name );
		}
		return null;
	}

	/**
	 * Retrieves the notification ID
	 */
	public function get_id() {
		return $this->get_data( 'id' );
	}

	/**
	 * Retrieves the notification recipient ID
	 */
	public function get_recipient_ID() {
		return $this->get_data( 'user_id' );
	}

	/**
	 * Retrieve specific or all notitications data
	 *
	 * @param string $part Data part to be retrieved
	 * @return array|string The value or list of data values
	 */
	public function get_data( $part = '' ) {

		$basic = array(
			'user_id' => $this->user_id,
			'id' => $this->id,
		);
		$fields = array_merge( $basic, (array) $this->meta );

		if ( empty( $part ) ) {
			return $fields;
		} elseif ( isset( $fields[$part] ) ) {
			return $fields[$part];
		}
	}

	/**
	 * Updates the notification meta
	 *
	 * @param string $prev_data (optional) Used to delete previously existing data
	 * @return bool True on success, False on failure
	 */
	protected function update_meta( $prev_data = '' ) {
		global $wpdb;

		if ( $prev_data ) {
			$this->delete_meta( $prev_data );
		}

		// prefix option with site
		$option_name = $wpdb->get_blog_prefix() . APP_NOTIFICATIONS_U_KEY;

		return add_user_meta( $this->user_id, $option_name, $this->meta );
	}

	/**
	 * Deletes the notification meta (always checks for prev data to delete the correct notification)
	 *
	 * @return bool True on success, False on failure
	 */
	public function delete_meta( $prev_data = '' ) {
		global $wpdb;

		if ( ! $prev_data ) {
			$prev_data = $this->meta;
		}

		// prefix option with site
		$option_name = $wpdb->get_blog_prefix() . APP_NOTIFICATIONS_U_KEY;

		return delete_user_meta( $this->user_id, $option_name, $prev_data );
	}

	/**
	 * Sets the notification status meta value
	 *
	 * @param string $status The status to be assigned to the notification
	 * @return bool True on success, False on failure
	 */
	public function set_status( $status ) {
		$prev_data = $this->meta;

		$this->meta['status'] = $status;

		if ( ! $this->update_meta( $prev_data ) ) {
			return false;
		}

		do_action( "appthemes_notification_$status", $this, $this->user_id );

		return true;
	}

}

/**
 * Class that defines a collection of user notifications
 *
 */
class APP_User_Notifications {

	/**
	 * The notification recipient user id
	 * @var int
	 */
	protected $user_id;

	/**
	 * The temporary notifications list
	 * @var array
	 */
	protected $notifications = array();

	/**
	 * The queried notifications list
	 * @var array
	 */
	public $results = array();

	/**
	 * Total found notifications
	 * @var int
	 */
	public $found = 0;

	/**
	 * Sets up the notification list object
	 *
	 * @param int user_id The notifications recipient user id
	 * @param array $params (optional) See 'get_notifications()' method
	 */
	function __construct( $user_id, $params = '' ) {
		global $wpdb;

		$this->user_id = $user_id;

		// prefix option with site
		$option_name = $wpdb->get_blog_prefix() . APP_NOTIFICATIONS_U_KEY;

		$meta = get_user_meta( $user_id, $option_name );

		if ( ! $meta ) {
			return;
		}

		foreach( $meta as $data ) {
			$this->notifications[] = new APP_User_Notification( $this->user_id, $data );
		}

		$this->results = $this->get_notifications( $params );
	}

	/**
	 * Retrieves a list of notifications for a specific user given a status or notification type
	 *
	 * @param array $params (optional)	Additional parameters to filter notifications results.
	 *									Expects notifications meta keys ( e.g.: 'status' => 'unread' )
	 *									Reserved meta keys:
	 *									- limit => -1 to returns all notifications; n to return n notifications
	 *									- offset => number of post to displace or pass over
	 *									- order => orders notifications (default is 'DESC'). Accepted values: ASC, DESC
	 * @return array The notifications list sorted by time descending. List index represents the notification timestamp
	 */
	function get_notifications( $params = '' ) {

		if ( ! empty( $params ) && ! is_array( $params ) ) {
			trigger_error( 'Cannot retrieve notifications (\'params\' is not a valid array).', E_USER_WARNING );
		}

		$reserved_params = array(
			'limit'		=> -1,
			'offset'	=> 0,
			'order'		=> 'DESC'
		);
		$params = wp_parse_args( $params, $reserved_params );

		extract( $params, EXTR_SKIP );

		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'DESC';
		}

		// skip reserved params
		foreach( $reserved_params as $key => $value ) {
			unset( $params[$key] );
		}

		$list = array();
		$uniq = 1;

		foreach( $this->notifications as $notification ) {
			if ( empty( $params ) || $this->query( $params, $notification->get_data() ) ) {
				$list[ $notification->time . $uniq ] = $notification;
			}
			$uniq++;
		}

		$this->found = count( $list );

		// sort list by time
		if ( 'ASC' == strtoupper( $order ) ) {
			ksort( $list );
		} else {
			krsort( $list );
		}

		// limit results
		if ( $limit <= 0 ) {
			$list = array_slice( $list, $offset );
		} else {
			$list = array_slice( $list, $offset, $limit, $preserve_keys = true );
		}

		return $list;
	}

	/**
	 * Queries the notification meta data
	 *
	 * @param array $params Parameters to be used to query the notification
	 * @param array $data The data to be queried
	 * @return bool True if the data was found, False otherwise
	 */
	protected function query( $params, $data ) {

		// @todo maybe use wp_list_filter()

		foreach( $params as $key => $value ) {

			if ( !isset( $data[$key] ) ) {
				return false;
			}

			if ( ! in_array( $data[$key], (array) $value ) ) {
				return false;
			}

		}
		return true;
	}

}
