<?php
/**
 * Reviews factory class
 *
 * @package Components\Reviews
 */
class APP_Review_Factory {

	### Post Reviews

	/**
	 * Creates and retrives an new post review object
	 *
	 * @param object $comment The WP_Comment_Query array that will be used as the review object
	 * @param int $rating The review rating
	 * @param array $meta (optional) Additional meta to be added to the review object.
	 * @return object The review object
	 */
	static function create_post_review( $comment, $rating, $meta = array() ) {
		$id = self::make( $comment );

		$review = self::set_post_review( $id, $rating, $meta );

		return $review;
	}

	/**
	 * Sets an existing WP comment as a review object and retrieves it
	 *
	 * @param int $comment_id The WordPress comment ID
	 * @param int $rating The review rating
	 * @param array $meta (optional) Additional meta to be added to the review object.
	 * @return object The review object
	 */
	static function set_post_review( $comment_id, $rating, $meta = array() ) {

		if ( empty( $rating ) ) {
			trigger_error( 'The rating cannot be empty.', E_USER_WARNING );
		}

		$review = self::retrieve( $comment_id );

		$review->set_type('post');
		$review->set_recipient( $review->get_post_ID() );

		$review->set_rating( $rating );
		$review->set_meta( $meta );

		if ( $review->is_approved() ) {
			// trigger approved reviews actions
			APP_Review_Handle::comment_approved( $comment_id, get_comment( $comment_id ) );
		}

		return $review;
	}

	### User Reviews

	/**
	 * Creates a new user review object
	 *
	 * @param int $recipient_id The user ID that is receiving the review
	 * @param object $comment The WP_Comment_Query array that will be used as the review object
	 * @param int $rating The review rating
	 * @param array $meta (optional) Additional meta to be added to the review object.
	 * @return object The review object
	 */
	static function create_user_review( $recipient_id, $comment, $rating, $meta = array() ) {

		// remove user review action hooked into 'wp_insert_comment' since the review is being created manually
		remove_action( 'wp_insert_comment', array( 'APP_Review_Handle', 'insert_comment' ), 10 );

		$id = self::make( $comment );

		$review = self::set_user_review( $recipient_id, $id, $rating, $meta );

		return $review;
	}

	/**
	 * Sets an existing WP_Comment_Query array as a review object and retrieves it
	 *
	 * @param int $recipient_id The user ID that is receiving the review
	 * @param int $comment_id The WordPress comment ID
	 * @param int $rating The review rating
	 * @param array $meta (optional) Additional meta to be added to the review object.
	 * @return object The review object
	 */
	static function set_user_review( $recipient_id, $comment_id, $rating, $meta = array() ) {

		if ( empty( $rating ) ) {
			trigger_error( 'The rating cannot be empty.', E_USER_WARNING );
		}

		$review = self::retrieve( $comment_id );

		$review->set_type('user');
		$review->set_recipient( $recipient_id );
		$review->set_rating( $rating );

		$review->set_meta( $meta );

		return $review;
	}

	/**
	 * Inserts a new comment as a review comment type and retrieves the new ID
	 *
	 * @param int $object_id The object ID (user_id or post_id) being reviewed
	 * @param object $comment The WP_Comment_Query array that will be used as the review object
	 * @return object The review object
	 */
	static protected function make( $comment ) {

		$defaults = array(
			'user_id'		=> get_current_user_id(),
			'comment_type'	=> appthemes_get_reviews_ctype(),
		);
		$comment = wp_parse_args( $comment, $defaults );

		// make sure the comment is created as pending to trigger status transition hooks
		$comment['comment_approved'] = 0;

		$id = wp_insert_comment( $comment );

		return $id;
	}

	/**
	 * Retrieves an existing review
	 *
	 * @param int $review_id The WordPress comment ID
	 * @return object The review object
	 */
	static function retrieve( $review_id ) {

		if ( ! is_numeric( $review_id ) ) {
			trigger_error( 'Invalid review id given. Must be numeric', E_USER_WARNING );
		}

		$comment = get_comment( $review_id );

		if ( ! $comment || $comment->comment_type != appthemes_get_reviews_ctype() ) {
			return false;
		}

		return new APP_Single_Review( $comment );
	}

	/**
	 * Main method to add/remove a review on a user/post review collection.
	 */
	static private function update_reviews( $review, $review_collection, $value = 1 ) {

		if ( $value > 0 ) {
			$review_collection->add_review( $review );
		} else {
			$review_collection->delete_review( $review );
		}

		return $review_collection;
	}

	/**
	 * Adds/removes a review on a post review collection.
	 * A negative operation removes the review, while a positive value will add it.
	 */
	static function update_post_reviews( $review, $operation = 1 ) {
		$post_review_col = self::get_post_reviews( $review->get_post_ID() );

		return self::update_reviews( $review, $post_review_col, $operation );
	}

	/**
	 * Adds/removes a review on a user review collection.
	 * A negative operation removes the review, while a positive value will add it.
	 */
	static function update_user_reviews( $review, $operation = 1 ) {

		// update received reviews aggregates
		$user_review_col = self::get_user_reviews( $review->get_recipient_id() );
		self::update_reviews( $review, $user_review_col, $operation );

		// update authored reviews aggregates
		$user_authored_review_col = self::get_user_authored_reviews( $review->get_author_ID() );
		self::update_reviews( $review, $user_authored_review_col, $operation );

		return $user_review_col;
	}

	/**
	 * Adds/removes a review on a user review collection.
	 * A negative operation removes the review, while a positive value will add it.
	 */
	static function update_user_authored_reviews( $review, $operation = 1 ) {
		$user_review_col = new APP_User_Reviews( $review->get_author_ID(), array( 'relation' => 'authored' ) );

		return self::update_reviews( $review, $user_review_col, $operation );
	}

	/**
	 * Retrieves the reviews collection for a specific post
	 *
	 * @param int $post_id The post ID to retrieve reviews from
	 * @param array $args (optional) WP_Comment_Query args to be used to fetch the review collection
	 * @return object The post review collection
	 */
	static function get_post_reviews( $post_id, $args = array() ) {
		return new APP_Post_Reviews( $post_id, $args );
	}

	/**
	 * Retrieves the reviews collection for a specific user
	 *
	 * @param int $user_id The user ID to retrieve reviews from
	 * @param array $args (optional) WP_Comment_Query args to be used to fetch the review collection
	 * @return object The user review collection
	 */
	static function get_user_reviews( $user_id, $args = array() ) {
		return new APP_User_Reviews( $user_id, $args );
	}

	/**
	 * Retrieves the authored reviews collection for a specific user
	 *
	 * @param int $user_id The user ID to retrieve reviews from
	 * @param array $args (optional) WP_Comment_Query args to be used to fetch the review collection
	 * @return object The user review collection
	 */
	static function get_user_authored_reviews( $user_id, $args = array() ) {

		$defaults = array(
			'relation'	 => 'author',
			'user_id'	 => $user_id,
			'meta_key'	 => '',
			'meta_value' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		return self::get_user_reviews( $user_id, $args );
	}


	/**
	 * Helper method to retrieve reviews comment types
	 *
	 * @param array $args (optional) WP_Comment_Query args to be used to fetch the reviews
	 * @return array List of comments
	 */
	private static function _get_reviews( $args = array() ) {
		$defaults = array(
			'status' => 'approve',
		);
		$args = wp_parse_args( $args, $defaults );

		$args['type'] = appthemes_get_reviews_ctype();

		return get_comments( $args );
	}

	/**
	 * Retrieve a list of reviews
	 *
	 * @param array $args (optional) WP_Comment_Query args to be used to fetch the reviews
	 * @return array List of reviews
	 */
	public static function get_reviews( $args = array() ) {
		$reviews = array();

		$reviews_comments = self::_get_reviews( $args );

		if ( ! is_array( $reviews_comments ) ) {
			return (int) $reviews_comments;
		}

		foreach( $reviews_comments as $review ) {
			$reviews[ $review->comment_ID ] = self::retrieve( $review->comment_ID );
		}
		return $reviews;
	}

}
