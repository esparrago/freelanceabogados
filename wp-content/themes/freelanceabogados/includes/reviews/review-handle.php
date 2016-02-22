<?php
/**
 * Reviews helpers
 *
 * @package Components\Reviews
 */

/**
 * Wrapper class for hooking into WP comments object.
 */
class APP_Review_Handle {

	/**
	 * The reviews comment type
	 * @var string
	 */
	public static $comment_type;

	/**
	 * The default review data
	 * @var array
	 */
	public static $data = array(
		'rating' => 0,
		'meta'	 => array(),
	);

	/**
	 * Initializes the class by setting the comment type and some important WP comment hooks
	 *
	 * @param string $comment_type	The comment type that identifies a comment
	 */
	public static function init( $comment_type ) {

		self::$comment_type = $comment_type;

		add_action( 'transition_comment_status', array( __CLASS__, 'comment_status_transition' ), 10, 3 );

		add_action( "comment_approved_{$comment_type}",	array( __CLASS__, 'comment_approved' ), 10, 2 );
		add_action( "comment_unapproved_{$comment_type}", array( __CLASS__, 'comment_unapproved' ), 10, 2 );

		add_action( 'pre_comment_on_post', array( __CLASS__, 'validate_comment' ) );
		add_action( 'wp_insert_comment', array( __CLASS__, 'insert_comment' ), 10, 2 );

		add_action( 'edit_comment',	array( __CLASS__, 'edit_comment' ), 10 );
		add_action( 'comment_post_redirect', array( __CLASS__, 'redirect' ), 10, 2 );
	}

	/**
	 * Validates a review before being inserted in the DB
	 * Redirects the user to a URL referer if is exists in the $_REQUEST, otherwise, ends execution with an error
	 *
	 * @uses apply_filters() Calls 'appthemes_validate_review'
	 *
	 */
	public static function validate_comment( $post_id ) {

		$type = ( isset( $_POST['comment_type'] ) ) ? trim( $_POST['comment_type'] ) : null;

		if ( self::$comment_type != $type ) {
			return;
		}

		$errors = apply_filters( 'appthemes_validate_review', _appthemes_reviews_error_obj(), $post_id );

		if ( $errors->get_error_codes() ) {

			set_transient( 'app-notices', $errors );

			if ( isset( $_REQUEST['url_referer'] ) ) {
				wp_safe_redirect( $_REQUEST['url_referer'] );
				exit();
			} else {
				wp_die( $errors->get_error_message() );
			}
		}
	}

	/**
	 * Trigger the comment status transition when reviews are approved
	 */
	public static function comment_approved( $comment_id, $comment ) {
		self::comment_status_transition( 'approved', 'approved', $comment );
	}

	/**
	 * Trigger the comment status transition when reviews are unapproved
	 */
	public static function comment_unapproved( $comment_id, $comment ) {
		self::comment_status_transition( 'unapproved', 'unapproved', $comment );
	}

	/**
	 * Provides action hooks on reviews status transitions
	 *
	 * @uses do_action() Calls 'appthemes_bid_{$new_status}'
	 * @uses do_action() Calls 'appthemes_bid_{$old_status}_to_{$new_status}'
	 *
	 */
	public static function comment_status_transition( $new_status, $old_status, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return;
		}

		$review = appthemes_get_review( $comment->comment_ID );

		// updates the review collection on the user and post meta
		self::update_review_collections( $review, $new_status );

		do_action( "appthemes_review_{$new_status}", $review, $old_status );

		// no change in or out
		if ( $new_status != 'approved' && $old_status != 'approved' ) {
			return;
		}

		do_action( "appthemes_review_{$old_status}_to_{$new_status}", $review );
	}

	/**
	 * Updates any meta data on edited reviews
	 *
	 * @uses do_action() Calls 'appthemes_update_post_review'
	 * @uses do_action() Calls 'appthemes_update_user_review'
	 *
	 */
	public static function edit_comment( $id ) {

		$comment = get_comment( $id );

		$review = self::handle_review( $id, $comment );
		if ( ! $review ) {
			return false;
		}

		$type = $review->get_type();

		if ( 'user' == $type ) {
			$object_id = $review->get_recipient_id();
		} else {
			$object_id = $review->get_post_ID();
		}

		do_action( "appthemes_update_{$type}_review", $review, $object_id );
	}

	/**
	 * Extends wp_insert_comment() by providing a review filter used to store additional data:
	 *
	 * @uses do_action() Calls 'appthemes_new_post_review'
	 * @uses do_action() Calls 'appthemes_new_user_review'
	 *
	 */
	public static function insert_comment( $id, $comment ) {

		$review = self::handle_review( $id, $comment );
		if ( ! $review ) {
			return false;
		}

		$type = $review->get_type();

		if ( 'user' == $type ) {
			$object_id = $review->get_recipient_id();
		} else {
			$object_id = $review->get_post_ID();
		}

		do_action( "appthemes_new_{$type}_review", $review, $object_id );
	}

	/**
	 * Handles the posted review data.
	 *
	 * @uses apply_filters() Calls 'appthemes_handle_review'
	 *
	 */
	public static function handle_review( $id, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return;
		}

		$review_data = apply_filters( 'appthemes_handle_review', self::$data );

		if ( ! $review_data || ! is_array( $review_data ) ) {
			return;
		}

		$review_data = wp_parse_args( $review_data, self::$data );

		extract( $review_data );

		// checks for a 'user_id' key in handled data to identify a 'user' review
		if ( ! empty( $user_id ) ) {
			return appthemes_set_user_review( $user_id, $id, $rating, $meta );
		} else {
			return appthemes_set_review( $id, $rating, $meta );
		}

	}

	/**
	 * Adds/deletes a review on the review collection of the parent post.
	 */
	private static function update_review_collections( $review, $status ) {

		if ( empty( $review ) ) {
			return;
		}

		// delete the review from the collection
		$operation = -1;

		if ( 'approved' == $status ) {
			// add the review to the collection
			$operation = 1;
		}

		// update post and user aggregates meta for the current review recipient
		APP_Review_Factory::update_post_reviews( $review, $operation );
		APP_Review_Factory::update_user_reviews( $review, $operation );
	}

	/**
	 * Provides a new hook to allow redirecting the user after a review
	 *
	 * @uses apply_filters() Calls 'appthemes_review_post_redirect'
	 *
	 */
	public static function redirect( $location, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return $location;
		}

		return apply_filters( 'appthemes_review_post_redirect', $location, $comment );
	}

}

/**
 * Helper function to store error objects
 */
function _appthemes_reviews_error_obj() {
	static $errors;

	if ( ! $errors ) {
		$errors = new WP_Error();
	}
	return $errors;
}
