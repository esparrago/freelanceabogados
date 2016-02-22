<?php
/**
 * Hooks into WP comments to catch bids being posted.
 */

/**
 * Wrapper class for hooking into WP comments object.
 */
class APP_Bid_Handle {

	public static $comment_type;

	public static $data = array(
		'amount' 	=> 0,
		'currency' 	=> 'USD',
		'meta' 		=> array(),
	);

	/**
	 * Initializes the class by setting the comment type and some important WP comment hooks
	 *
	 * @param string $comment_type	The comment type that identifies a comment
	 */
	public static function init( $comment_type ) {

		self::$comment_type = $comment_type;

        add_action( 'transition_comment_status',			array( __CLASS__, 'comment_status_transition' ), 10, 3 );

        add_action( "comment_approved_{$comment_type}",		array( __CLASS__, 'comment_approved' ), 10, 2 );
        add_action( "comment_unapproved_{$comment_type}",	array( __CLASS__, 'comment_unapproved' ), 10, 2 );

		add_action( 'pre_comment_on_post',					array( __CLASS__, 'validate_comment' ) );
        add_action( 'wp_insert_comment',					array( __CLASS__, 'insert_comment' ), 10, 2 );
        add_action( 'edit_comment',							array( __CLASS__, 'edit_comment' ), 10 );
		add_action( 'comment_post_redirect',				array( __CLASS__, 'redirect' ), 10, 2 );
    }

	/**
	 * Validates the bid data being posted in case javascript is disabled.
	 * On errors, redirects to the request 'url_referer'. If the referer URL does not exists ends code execution.
	 *
	 * @uses apply_filters() Calls 'appthemes_validate_bid'
	 *
	 */
	public static function validate_comment( $post_id ) {

		$type = ( isset( $_POST['comment_type'] ) ) ? trim( $_POST['comment_type'] ) : null;

		if ( self::$comment_type != $type ) {
			return;
        }

		$errors = apply_filters( 'appthemes_validate_bid', _appthemes_bids_error_obj(), $post_id );

		if ( $errors->get_error_codes() ) {

			if ( isset( $_REQUEST['url_referer'] ) ) {
				wp_safe_redirect( $_REQUEST['url_referer'] );
				exit();
			} else {
				wp_die( $errors->get_error_message() );
			}
		}
	}

    /**
	 * Triggers the comment status transition when bids are approved.
	 */
    public static function comment_approved( $comment_id, $comment ) {
        self::comment_status_transition( 'approved', 'approved', $comment );
    }

    /**
	 * Triggers the comment status transition when bids are unapproved.
	 */
    public static function comment_unapproved( $comment_id, $comment ) {
        self::comment_status_transition( 'unapproved', 'unapproved', $comment );
    }

	/**
	 * Custom comment type status changes to update the bid meta.
	 *
	 * @uses do_action() Calls 'appthemes_bid_{$new_status}'
	 * @uses do_action() Calls 'appthemes_bid_{$old_status}_to_{$new_status}'
	 *
	 */
	public static function comment_status_transition( $new_status, $old_status, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return;
        }

		$bid = appthemes_get_bid( $comment->comment_ID );

		// updates the bid collection on the user and post meta
		self::update_bid_collections( $bid, $new_status );

        do_action( "appthemes_bid_{$new_status}", $bid, $old_status );

		// no change in or out
		if ( $new_status != 'approved' && $old_status != 'approved' ) {
			return;
        }

        do_action( "appthemes_bid_{$old_status}_to_{$new_status}", $bid );

	}

	/**
	 * Handles the posted bid data when editing a bid.
	 *
	 * @uses do_action() Calls 'appthemes_update_bid'
	 *
	 */
	public static function edit_comment( $id ) {

		$comment = get_comment( $id );

		$bid = self::handle_bid( $id, $comment );
		if ( ! $bid ) {
			return false;
        }

		do_action( 'appthemes_update_bid', $bid );
	}

	/**
	 * Handles the posted bid data when posting a new bid.
	 *
	 * @uses do_action() Calls 'appthemes_new_bid'
	 *
	 */
	public static function insert_comment( $id, $comment ) {

		$bid = self::handle_bid( $id, $comment );
		if ( ! $bid ) {
			return false;
        }

		do_action( 'appthemes_new_bid', $bid );
	}

	/**
	 * Handles the posted bid data.
	 *
	 * @uses apply_filters() Calls 'appthemes_handle_bid'
	 *
	 */
	private static function handle_bid( $id, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return;
        }

		$bid_data = apply_filters( 'appthemes_handle_bid', self::$data );

		if ( ! $bid_data || ! is_array( $bid_data ) ) {
			return;
        }

		$bid_data = wp_parse_args( $bid_data, self::$data );

		extract( $bid_data );

		return appthemes_set_bid( $id, $amount, $currency, $meta );
	}

	/**
	 * Adds/deletes a bid on the bid collection of the parent post.
	 */
	private static function update_bid_collections( $bid, $status ) {

		if ( empty( $bid ) ) {
			return;
		}

		// delete the bid from the collection
		$operation = -1;

		if ( 'approved' == $status ) {
			// add the bid to the collection
			$operation = 1;
		}

		APP_Bid_Factory::update_post_bids( $bid, $operation );
		APP_Bid_Factory::update_user_bids( $bid, $operation );
	}

	/**
	 * Provides a new hook to allow redirecting the user after a bid
	 *
	 * @uses apply_filters() Calls 'appthemes_bid_post_redirect'
	 *
	 */
	public static function redirect( $location, $comment ) {

		if ( self::$comment_type != $comment->comment_type ) {
			return $location;
		}

		return apply_filters( 'appthemes_bid_post_redirect', $location, $comment );
	}
}

/**
 * Helper function to store error objects.
 */
function _appthemes_bids_error_obj() {
	static $errors;

	if ( ! $errors ) {
		$errors = new WP_Error();
	}
	return $errors;
}