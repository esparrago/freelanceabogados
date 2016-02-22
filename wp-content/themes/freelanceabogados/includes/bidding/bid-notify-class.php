<?php
/**
 * Hooks into WP comments to send bid notifications.
 */

// @todo maybe change to work independently from the default WP comment moderation settings
// @todo allow canceling notifications from the load params

/**
 * Wrapper class for hooking into WP comments object.
 */
class APP_Bid_Comments_Notify_Hook {

	/**
	 * Comment Type, the custom comment type to use.
	 * @var string
	 */
	private static $comment_type = '';

	/**
	 * Sets up the extended comments class.
	 * @param string comment_type The custom comment type
	 */
	public static function init( $comment_type ) {

		if ( ! $comment_type ) {
			trigger_error( 'No custom comment type defined.', E_USER_WARNING );
		}

		self::$comment_type = $comment_type;

		add_filter( 'comment_notification_recipients',	array( __CLASS__, 'notification_recipients' ), 999, 2 );

		add_filter( 'comment_moderation_subject',		array( __CLASS__, 'notify_email_subject' ), 999, 2 );
		add_filter( 'comment_notification_subject',		array( __CLASS__, 'notify_email_subject' ), 999, 2 );

		add_filter( 'comment_notification_text',		array( __CLASS__, 'notify_email_text' ), 999, 2 );
		add_filter( 'comment_moderation_text',			array( __CLASS__, 'notify_email_text' ), 999, 2 );

		add_filter( 'comment_notification_headers',		array( __CLASS__, 'notify_email_headers' ), 999, 2 );
		add_filter( 'comment_moderation_headers',		array( __CLASS__, 'notify_email_headers' ), 999, 2 );
	}

	/**
	 * Used to cancel admin notifications if admins disable them on the settings page.
	 */
	public static function notification_recipients( $recipients, $comment_id ) {
		global $app_bidding_options;

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $recipients;
		}

		if ( ! $app_bidding_options->notify_new_bid ) {
			return array();
		}

		return $recipients;
	}

	/**
	 * Modify the new comment author email subject.
	 *
	 * @uses apply_filters() Calls 'app_bid_notification_subject'
	 *
	 */
	public static function notify_email_subject( $subject, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $subject;
		}

		$post_title = get_the_title( $comment->comment_post_ID );
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$subject = sprintf( __( '[%s] New %s on - %s -', APP_TD ), $blogname, strtolower( appthemes_bidding_get_args('name') ), $post_title );

		return apply_filters( 'app_bid_notification_subject', wp_strip_all_tags( $subject ), $comment_id );
	}

	/**
	 * Allow html in email body.
	 */
	public static function notify_email_headers( $headers, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $headers;
		}

		$headers = str_replace( 'text/plain', 'text/html', $headers );

		return $headers;
	}

	/**
	 * Modify the new comment author email text.
	 *
	 * @uses apply_filters() Calls 'app_bid_notification_text'
	 *
	 */
	public static function notify_email_text( $notify_message, $comment_id ) {

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $notify_message;
		}

		$post = get_post( $comment->comment_post_ID );
		$author = get_userdata( $post->post_author );

		$bid = appthemes_get_bid( $comment->comment_ID );

		$notify_message  = sprintf( __( 'Hi %s,', APP_TD ), $author->display_name ) . "\r\n\r\n";
		$notify_message  .= sprintf( __( 'A new %s has been submitted on "%s" by %s.', APP_TD ), strtolower( appthemes_bidding_get_args('singular_name') ), $post->post_title, $comment->comment_author ) . "\r\n\r\n\r\n";

		$notify_message  .= sprintf( __( 'Amount: %s', APP_TD ), appthemes_get_price( $bid->get_amount(), $bid->get_currency() ) ) . "\r\n";

		$notify_message .= $comment->comment_content . "\r\n\r\n\r\n";

		$notify_message .= sprintf( __( 'Link: %s', APP_TD ), get_permalink( $comment->comment_post_ID ) ) . "\r\n\r\n";

		if ( author_can( $post->post_author, 'moderate_comments' ) ) {

			if ( EMPTY_TRASH_DAYS ) {
				$notify_message .= sprintf( __( 'Trash it: %s', APP_TD ), admin_url( "comment.php?action=trash&c=$comment_id" ) ) . "\r\n";
			} else {
				$notify_message .= sprintf( __( 'Delete it: %s', APP_TD ), admin_url( "comment.php?action=delete&c=$comment_id" ) ) . "\r\n";
				$notify_message .= sprintf( __( 'Spam it: %s', APP_TD ), admin_url( "comment.php?action=spam&c=$comment_id" ) ) . "\r\n";
			}

		}

		return apply_filters( 'app_bid_notification_text', wpautop( $notify_message ), $comment_id );
	}

}
