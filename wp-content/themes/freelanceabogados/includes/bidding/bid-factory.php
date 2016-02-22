<?php
/**
 * Main class for creating bid objects.
 */
class APP_Bid_Factory {

	/**
	 * Creates a bid object from a newly inserted comment.
	 */
	protected static function make( $comment, $amount, $currency, $meta = array() ) {

		$defaults = array(
			'user_id'		=> get_current_user_id(),
			'comment_type'	=> appthemes_get_bidding_ctype(),
		);
		$comment = wp_parse_args( $comment, $defaults );

		// make sure the comment is created as pending to trigger status transition hooks
		$comment['comment_approved'] = 0;

		$id = wp_insert_comment( $comment );

		$bid = self::set( $id, $amount, $currency, $meta );

		return $bid;
	}

	/**
	 * Creates a bid object given a comment array.
	 */
	static function create( $comment, $amount, $currency = 'USD', $meta = array() ) {
		$bid = self::make( $comment, $amount, $currency, $meta );
		return $bid;
	}

	/**
	 * Sets the base properties for a bid object.
	 */
	static function set( $comment_id, $amount, $currency = 'USD', $meta = array() ) {

		if ( empty( $amount ) ) {
			trigger_error( 'Bid amount cannot be empty.', E_USER_WARNING );
        }

		$bid = self::retrieve( $comment_id );

		$bid->set_amount( $amount, $currency );
		$bid->set_meta( $meta );

		return $bid;
	}

	/**
	 * Retrieves an existing bid object.
	 */
	static function retrieve( $bid_id ) {

		if ( ! is_numeric( $bid_id ) ) {
			trigger_error( 'Invalid bid id given. Must be numeric', E_USER_WARNING );
		}

		$comment = get_comment( $bid_id );
		if ( ! $comment || $comment->comment_type != appthemes_get_bidding_ctype() ) {
			return false;
		}

		return new APP_Single_Bid( $comment );
	}

	/**
	 * Main method to add/remove a bid on a user/post bid collection.
	 */
	static private function update_bids( $bid, $bid_collection, $value = 1 ) {

		if ( $value > 0 ) {
			$bid_collection->add_bid( $bid );
		} else {
			$bid_collection->delete_bid( $bid );
		}

		return $bid_collection;
	}

	/**
	 * Adds/removes a bid on a post bid collection.
	 * A negative operation removes the bid, while a positive value will add it.
	 */
	static function update_post_bids( $bid, $operation = 1 ) {
		$post_bid_col = self::get_post_bid_collection( $bid->get_post_ID() );

		return self::update_bids( $bid, $post_bid_col, $operation );
	}

	/**
	 * Adds/removes a bid on a user bid collection.
	 * A negative operation removes the bid, while a positive value will add it.
	 */
	static function update_user_bids( $bid, $operation = 1 ) {
		$user_bid_col = self::get_user_bid_collection( $bid->get_user_id() );

		return self::update_bids( $bid, $user_bid_col, $operation );
	}

	/**
	 * Retrieves the bid collections for a given post.
	 */
	static function get_post_bid_collection( $post_id, $args = array() ) {
		return new APP_Post_Bid_Collection( $post_id, $args );
	}

	/**
	 * Retrieves the bid collections for a given user.
	 */
	static function get_user_bid_collection( $user_id, $args = array() ) {
		return new APP_User_Bid_Collection( $user_id, $args );
	}

	/**
	 * Queries the database using a 'WP_Comment_Query()' to retrieve existing bids.
	 */
	private static function _get_bids( $args = array() ) {
		$defaults = array(
			'status' => 'approve',
		);
		$args = wp_parse_args( $args, $defaults );

		$args['type'] = appthemes_get_bidding_ctype();

		return get_comments( $args );
	}

	/**
	 * Queries the database using a 'WP_Comment_Query()' to retrieve existing bids.
	 */
	static function get_bids( $args = array() ) {
		$bids = array();

		$bids_comments = self::_get_bids( $args );

		if ( ! is_array( $bids_comments ) ) {
			return (int) $bids_comments;
		}

		foreach( $bids_comments as $bid ) {
			$bids[ $bid->comment_ID ] = self::retrieve( $bid->comment_ID );
		}
		return $bids;
	}

}
