<?php
/**
 * Reviews notifications class
 *
 * @todo Allow canceling review notifications from the load params
 * @package Components\Reviews
 */
class APP_Review_Comments_Email_Notify {

	/**
	 * Comment Type, the custom comment type to use
	 * @var string
	 */
	private static $comment_type = '';

	/**
	 * Sets up the extended comments class
	 *
	 * @param string comment_type	The custom comment type
	 */
	public static function init( $comment_type ) {

		if ( ! $comment_type ) {
			trigger_error( 'No custom comment type defined.', E_USER_WARNING );
		}

		self::$comment_type = $comment_type;

		add_filter( 'comment_notification_recipients', array( __CLASS__, 'notification_recipients' ), 999, 2 );

		add_filter( 'comment_moderation_subject', array( __CLASS__, 'notify_email_subject' ), 999, 2 );
		add_filter( 'comment_notification_subject', array( __CLASS__, 'notify_email_subject' ), 999, 2 );

		add_filter( 'comment_notification_text', array( __CLASS__, 'notify_email_text' ), 999, 2 );
		add_filter( 'comment_moderation_text', array( __CLASS__, 'notify_email_text' ), 999, 2 );

		add_filter( 'comment_notification_headers', array( __CLASS__, 'notify_email_headers' ), 999, 2 );
		add_filter( 'comment_moderation_headers', array( __CLASS__, 'notify_email_headers' ), 999, 2 );

		add_action( 'appthemes_new_user_review', array( __CLASS__, 'notify_user_review' ), 999, 2 );
	}

	/**
	 * Used to cancel admin notifications if admins disable them on the settings page.
	 */
	public static function notification_recipients( $recipients, $comment_id ) {
		global $app_reviews_options;

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $recipients;
		}

		if ( ! $app_reviews_options->notify_new_review ) {
			return array();
		}

		return $recipients;
	}

	/**
	 * Used only for user reviews where the reviewer is the post author, since WP does not send notifications in this case
	 * The email is sent using framework instead of WP internal email function.
	 */
	public static function notify_user_review( $review, $recipient_id ) {

		$post = get_post( $review->get_post_ID() );

		if ( $review->user_id != $post->post_author ){
			return;
		}

		$subject = self::notify_email_subject( '', $review->id, $recipient_id );
		$content = self::notify_email_text( '', $review->id, $recipient_id );

		$address = get_userdata( $recipient_id )->user_email;

		appthemes_send_email( $address, $subject, $content );
	}

	/**
	 * Modifies the new comment author email subject
	 *
	 * @uses apply_filters() Calls 'appthemes_post_review_notification_subject'
	 * @uses apply_filters() Calls 'appthemes_user_review_notification_subject'
	 *
	 */
	public static function notify_email_subject( $subject, $comment_id, $recipient_id = 0 ) {

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $subject;
		}

		$review = appthemes_get_review( $comment->comment_ID );

		$post_title = get_the_title( $comment->comment_post_ID );
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		$subject = sprintf( __( '[%1$s] New review on "%2$s"', APP_TD ), $blogname, $post_title );

		$type = $review->get_type();

		return apply_filters( "appthemes_{$type}_review_notification_subject", wp_strip_all_tags( $subject ), $comment_id, $recipient_id );
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
	 * Modifies the new comment author email text
	 *
	 * @uses apply_filters() Calls 'appthemes_post_review_notification_text'
	 * @uses apply_filters() Calls 'appthemes_user_review_notification_text'
	 *
	 */
	public static function notify_email_text( $notify_message, $comment_id, $recipient_id = 0 ) {

		$comment = get_comment( $comment_id );

		if ( $comment->comment_type != self::$comment_type ) {
			return $notify_message;
		}

		$post = get_post( $comment->comment_post_ID );

		if ( $recipient_id ) {
			$recipient = get_userdata( $recipient_id );
		} else {
			$recipient = get_userdata( $post->post_author );
		}

		$review = appthemes_get_review( $comment->comment_ID );

		$notify_message = sprintf( __( 'Hi %s,', APP_TD ), $recipient->display_name ) . "\r\n\r\n";

		$notify_message .= sprintf( __( 'A new review has been submitted on "%1$s" by %2$s.', APP_TD ), $post->post_title, $comment->comment_author ) . "\r\n\r\n\r\n";

		$notify_message .= sprintf( __( 'Rating: %1$s/%2$s', APP_TD ), $review->get_rating(), appthemes_reviews_get_args( 'max_rating' ) ) . "\r\n";

		$notify_message .= $comment->comment_content . "\r\n\r\n\r\n";

		$notify_message .= __( 'Review link', APP_TD ) . "\r\n" . get_comment_link( $comment ) . "\r\n";

		if ( user_can( $recipient->ID, 'manage_options' ) ) {

			if ( EMPTY_TRASH_DAYS ) {
				$notify_message .= sprintf( __( 'Trash it: %s', APP_TD ), admin_url( "comment.php?action=trash&c=$comment_id" ) ) . "\r\n";
			} else {
				$notify_message .= sprintf( __( 'Delete it: %s', APP_TD ), admin_url( "comment.php?action=delete&c=$comment_id" ) ) . "\r\n";
			}

			$notify_message .= sprintf( __( 'Spam it: %s', APP_TD ), admin_url( "comment.php?action=spam&c=$comment_id" ) ) . "\r\n";
		}

		$type = $review->get_type();

		return apply_filters( "appthemes_{$type}_review_notification_text", wpautop( $notify_message ), $comment_id, $recipient_id );
	}

}
