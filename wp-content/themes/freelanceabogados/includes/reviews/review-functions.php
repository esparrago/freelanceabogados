<?php
/**
 * Reviews API
 *
 * @package Components\Reviews
 */

if ( ! defined( 'APP_REVIEWS_CTYPE' ) ) {
	define( 'APP_REVIEWS_CTYPE', 'review' );
}

// comments meta keys
define( 'APP_REVIEWS_C_DATA_KEY', '_' . APP_REVIEWS_CTYPE . '_data' );
define( 'APP_REVIEWS_C_RATE_KEY', '_' . APP_REVIEWS_CTYPE . '_rating' );
define( 'APP_REVIEWS_C_RECIPIENT_KEY', '_' . APP_REVIEWS_CTYPE . '_recipient' );
define( 'APP_REVIEWS_C_RECIPIENT_TYPE_KEY', '_' . APP_REVIEWS_CTYPE . '_recipient_type' );

// post meta keys
define( 'APP_REVIEWS_P_DATA_KEY', '_' . APP_REVIEWS_CTYPE . '_data' );
define( 'APP_REVIEWS_P_AVG_KEY', '_' . APP_REVIEWS_CTYPE . '_avg' );
define( 'APP_REVIEWS_P_REL_KEY', '_' . APP_REVIEWS_CTYPE . '_rel' );
define( 'APP_REVIEWS_P_TOTAL_KEY', '_' . APP_REVIEWS_CTYPE . '_total' );
define( 'APP_REVIEWS_P_STATUS_KEY', '_' . APP_REVIEWS_CTYPE . '_status' );

// user meta keys
define( 'APP_REVIEWS_U_DATA_KEY', '_' . APP_REVIEWS_CTYPE . '_data' );
define( 'APP_REVIEWS_U_AVG_KEY', '_' . APP_REVIEWS_CTYPE . '_avg' );
define( 'APP_REVIEWS_U_AVG_AUTHORED_KEY', '_' . APP_REVIEWS_CTYPE . '_authored_avg' );
define( 'APP_REVIEWS_U_REL_KEY', '_' . APP_REVIEWS_CTYPE . '_rel' );
define( 'APP_REVIEWS_U_TOTAL_KEY', '_' . APP_REVIEWS_CTYPE . '_total' );
define( 'APP_REVIEWS_U_TOTAL_AUTHORED_KEY', '_' . APP_REVIEWS_CTYPE . '_authored_total' );
define( 'APP_REVIEWS_U_VOTES_KEY', '_' . APP_REVIEWS_CTYPE . '_votes' );

### Core

/**
 * Sets an existing WP comment as a review object and retrieves it
 *
 * @param int $comment_id			The	WordPress comment ID
 * @param int $rating				The review rating
 * @param array $meta (optional)	Additional meta to be added to the review
 * @return object					The review object
 */
function appthemes_set_review( $comment_id, $rating, $meta = array() ) {
	return APP_Review_Factory::set_post_review( $comment_id, $rating, $meta );
}

/**
 * Creates and retrives an new post review object from a comment array
 *
 * @param object $comment			The WP_Comment_Query array that will be used as the review object
 * @param int $rating				The review rating
 * @param array $meta (optional)	Additional meta to be added to the review
 * @return object					The review object
 */
function appthemes_create_review( $comment, $rating, $meta = array() ) {
	return APP_Review_Factory::create_post_review( $comment, $rating, $meta );
}

/**
 * Retrieves an existing post review
 *
 * @param int $review_id	The review ID (WordPress comment ID)
 * @return object			The review object
 */
function appthemes_get_review( $id ) {
	return APP_Review_Factory::retrieve( $id );
}

/**
 * Retrieve a list of post reviews
 *
 * @param array	$args (optional)	WP_Comment_Query args to be used to fetch the reviews
 * @return array					List of reviews
 */
function appthemes_get_reviews( $args = array() ) {
	return APP_Review_Factory::get_reviews( $args );
}

/**
 * Retrieves the review rating
 *
 * @param int $review_id	The review ID
 * @return string			The review rating
 */
function appthemes_get_rating( $review_id ) {
	$review = appthemes_get_review( $review_id );
	return $review->get_rating();
}

### Post Reviews

/**
 * Retrieves the reviews collection for a specific post
 *
 * @param int $post_id				The post ID to retrieve reviews from
 * @param array	$args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @return object					The post review collection
 */
function appthemes_get_post_reviews( $post_id, $args = array() ) {
	return APP_Review_Factory::get_post_reviews( $post_id, $args );
}

/**
 * Retrieves the post average reviews rating
 *
 * @param int $post_id				The post ID
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @param bool $cached (optional)	Whether to retrieve the cached average or from the custom WP_Comment_Query (defaults to TRUE if there are no params)
 * @return string					The average rating for a given post
 */
function appthemes_get_post_avg_rating( $post_id, $args = array(), $cached = true ) {
	if ( empty( $args ) || $cached ) {
		return get_post_meta( $post_id, APP_REVIEWS_P_AVG_KEY, true );
	}
	$reviews = appthemes_get_post_reviews( $post_id, $args );
	return $reviews->get_avg_rating();
}

/**
 * Retrieves the total reviews for a post
 *
 * @param int $post_id				The post ID
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @param bool $cached (optional)	Whether to retrieve the cached total (default) or from the custom WP_Comment_Query (always defaults to TRUE if there are no params)
 * @return int						The total number of reviews for a given post
 */
function appthemes_get_post_total_reviews( $post_id, $args = array(), $cached = true ) {
	if ( empty( $args ) || $cached ) {
		$total = get_post_meta( $post_id, APP_REVIEWS_P_TOTAL_KEY, true );
	} else {
		$reviews = appthemes_get_post_reviews( $post_id, $args );
		$total = $reviews->get_total_reviews();
	}

	return (int) $total;
}

### User Reviews

/**
 * Sets an existing WP comment as a user review object and retrieves it
 *
 * @param int $recipient_id			The user ID that is being reviewed
 * @param int $comment_id			The WordPress comment ID
 * @param int $rating				The review rating
 * @param array $meta (optional)	Additional meta to be added to the review
 * @return object					The review object
 */
function appthemes_set_user_review( $recipient_id, $comment_id, $rating, $meta = array() ) {
	return APP_Review_Factory::set_user_review( $recipient_id, $comment_id, $rating, $meta );
}

/**
 * Creates a new user review object from a comment array
 *
 * @param int $recipient_id			The user ID that is being reviewed
 * @param object $comment			The WP_Comment_Query array that will be used as the review object
 * @param int $rating				The review rating
 * @param array $meta (optional)	Additional meta to be added to the review
 * @return object					The review object
 */
function appthemes_create_user_review( $recipient_id, $comment, $rating, $meta = array() ) {
	return APP_Review_Factory::create_user_review( $recipient_id, $comment, $rating, $meta );
}

/**
 * Retrieves the reviews collection for a specific user (user is the review recipient)
 *
 * @param int $user_id				The user ID to retrieve reviews from
 * @param array	$args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @return object					The user review collection
 */
function appthemes_get_user_reviews( $user_id, $args = array() ) {
	return APP_Review_Factory::get_user_reviews( $user_id, $args );
}

/**
 * Retrieves the reviews collection for a specific user and post (user is the review recipient)
 *
 * @param int $user_id				The user ID to retrieve reviews from
 * @param int $post_id				The post ID to retrieve reviews from
 * @param array	$args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @return object					The user review collection
 */
function appthemes_get_user_post_review( $user_id, $post_id, $args = array() ) {
	$args['post_id'] = $post_id;
	$reviews = appthemes_get_user_reviews( $user_id, $args );
	return reset( $reviews->reviews );
}

/**
 * Retrieves the authored reviews collection for a specific user and post (user is the review author)
 *
 * @param int $user_id				The user ID to retrieve reviews from
 * @param int $post_id				The post ID to retrieve reviews from
 * @param array	$args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @return object					The user review collection
 */
function appthemes_get_user_authored_post_review( $user_id, $post_id, $args = array() ) {
	$args['post_id'] = $post_id;
	$reviews = appthemes_get_user_authored_reviews( $user_id, $args );
	return reset( $reviews->reviews );
}

/**
 * Retrieves the user average reviews rating (user is the review recipient)
 *
 * @param int $user_id				The user ID to retrieve the average from
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @param bool $cached (optional)	Whether to retrieve the cached average or from the custom WP_Comment_Query (defaults to TRUE if there are no params)
 * @return string					The average reviews for a given user (user is reviwee)
 */
function appthemes_get_user_avg_rating( $user_id, $args = array(), $cached = true ) {
	if ( empty( $args ) || $cached ) {
		return get_user_option( APP_REVIEWS_U_AVG_KEY, $user_id );
	}
	$reviews = appthemes_get_user_reviews( $user_id, $args );
	return $reviews->get_avg_rating();
}

/**
 * Retrieves the total reviews for a user (user is the review recipient)
 *
 * @param int $user_id				The user ID to retrieve the totals from
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @param bool $cached (optional)	Whether to retrieve the cached total (default) or from the custom WP_Comment_Query (always defaults to TRUE if there are no params)
 * @return string					The total reviews for a given user (user is reviwee)
 */
function appthemes_get_user_total_reviews( $user_id, $args = array(), $cached = true ) {
	if ( empty( $args ) || $cached ) {
		return get_user_option( APP_REVIEWS_U_TOTAL_KEY, $user_id );
	}
	$reviews = appthemes_get_user_reviews( $user_id, $args );
	return $reviews->get_total_reviews();
}

/**
 * Retrieves a user success rate based on the minimum value set as successfull rating
 *
 * @uses apply_filters() Calls 'appthemes_review_user_success_rate'
 *
 * @param int $user_id				The review recipient
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @return string					The user success rate or -1 if it was not rated yet
 */
function appthemes_get_user_success_rate( $user_id, $args = array() ) {

	$success_rate_min_val = appthemes_reviews_get_args('success_rate_min_val');

	$args = array(
		'meta_query' => array(
			array(
				'key' => APP_REVIEWS_C_RATE_KEY,
				'value' => $success_rate_min_val,
				'type' => 'NUMERIC',
				'compare' => '>=',
			)
		)
	);

	$reviews = appthemes_get_user_reviews( $user_id, $args );

	$total_success_reviews = $reviews->get_total_reviews();
	$total_reviews = appthemes_get_user_total_reviews( $user_id );

	if ( $total_reviews == 0 ) {
		$success_rate = -1;
	} else {
		$success_rate = round( ( $total_success_reviews / $total_reviews ) * 100, 0 );
	}

	return apply_filters( 'appthemes_user_reviews_success_rate', $success_rate, $user_id, $reviews, $args );
}

### User Authored Reviews

/**
 * Retrieves the reviews collection authored by a specific user (user is the review author)
 *
 * @param int $user_id				The reviewer user ID
 * @param array	$args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @return object					The user authored reviews collection
 */
function appthemes_get_user_authored_reviews( $user_id, $args = array() ) {
	return APP_Review_Factory::get_user_authored_reviews( $user_id, $args );
}

/**
 * Retrieves the average reviews rating given by a specific author (user is the review author)
 *
 * @param int $user_id				The reviewer user ID
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @param bool $cached (optional)	Whether to retrieve the cached average (default) or from the custom WP_Comment_Query (always defaults to TRUE if there are no params)
 * @return string					The authored reviews average rating
 */
function appthemes_get_user_authored_avg_rating( $user_id, $args = array(), $cached = true ) {
	if ( empty( $args ) || $cached ) {
		return get_user_option( APP_REVIEWS_U_AVG_AUTHORED_KEY, $user_id );
	}
	$reviews = appthemes_get_user_authored_reviews( $user_id, $args );
	return $reviews->get_avg_rating();
}

/**
 * Retrieves the total reviews given by a specific author (user is the review author)
 *
 * @param int $user_id				The reviewer user ID
 * @param array $args (optional)	WP_Comment_Query args to be used to fetch the review collection
 * @param bool $cached (optional)	Whether to retrieve the cached total (default) or from the custom WP_Comment_Query (always defaults to TRUE if there are no params)
 * @return int						The authored total number of reviews
 */
function appthemes_get_user_authored_total_reviews( $user_id, $args = array(), $cached = true ) {
	if ( empty( $args ) || $cached ) {
		return get_user_option( APP_REVIEWS_U_TOTAL_AUTHORED_KEY, $user_id );
	}
	$reviews = appthemes_get_user_authored_reviews( $user_id, $args );
	return $reviews->get_total_reviews();
}

/**
 * Pre fills Relative rating to 0 for new posts and
 * updated posts with empty meta
 *
 * @param int $post_ID		The new/updated post ID
 * @param object $post		The new/updated post object
 * @param boolean $update	Whether this is update event or not
 */
function appthemes_pre_fill_post_rating( $post_ID, $post, $update ) {
	if ( $update && get_post_meta( $post_ID, APP_REVIEWS_P_REL_KEY, true ) ) {
		return;
	}
	update_post_meta( $post_ID, APP_REVIEWS_P_REL_KEY, 0 );
}

/**
 * Pre fills Relative rating to 0 for new users and
 * updated profiles with empty meta
 *
 * @param int $user_id				The new/updated user ID
 * @param object|boolean $user_id	Whether this is update event or not;
 *									On Update event will passed WP_User object with old data, on Registration - nothing
 */
function appthemes_pre_fill_user_rating( $user_id, $update = false ) {
	if ( $update && get_user_option( APP_REVIEWS_U_REL_KEY, $user_id ) ) {
		return;
	}
	update_user_option( $user_id, APP_REVIEWS_U_REL_KEY, 0 );
}

### Meta

/**
 * Updates the review meta. Meta is stored as an associative array.
 * If $public is set to true, the value will also be stored on it's own meta key.
 *
 * @param int $id              The review ID
 * @param string $meta_key     The meta key
 * @param string $meta_value   The meta value
 * @param bool $public (optional) Whether the value should be stored on it's own meta key
 */
function appthemes_update_review_meta( $id, $meta_key, $meta_value, $public = false ) {
	$review = appthemes_get_review( $id );
	return $review->update_meta( $meta_key, $meta_value, $public );
}

/**
 * Deletes public metadata from the review. The review base metadata cannot be deleted.
 * @param int $id              The review ID
 * @param string $meta_key     The meta key
 * @param string $meta_value   The meta value
 */
function appthemes_delete_review_meta( $id, $meta_key, $meta_value ) {
	$review = appthemes_get_review( $id );
	return $review->delete_meta( $meta_key, $meta_value );
}

/**
 * Retrieves review meta.
 * @param int $id						The review ID
 * @param string $meta_key (optional)	The meta key to retrieve data from
 * @param bool $single (optional)		If set to TRUE then the function will return a single result, as a string.
 *										If false, or not set, then the function returns an array of the custom fields.
 * @return type
 */
function appthemes_get_review_meta( $id, $meta_key = '', $single = false  ) {
	$review = appthemes_get_review( $id );
	return $review->get_meta( $meta_key, $single );
}

### Status

/**
 * Activates a review by setting the status to 'approve' (comment status)
 *
 * @param int $review_id The review ID
 */
function appthemes_activate_review( $review_id ) {
	$review = appthemes_get_review( $review_id );

	$review->approve();
}

/**
 * Cancels a review and deletes it if '$trash' is set to true.
 * @param int $id					The review ID
 * @param boolean $trash (optional)	If set to true, review is updated to 'trash', otherwise is set to 'hold'
 * @return int						The result (1 = canceled; 0 = not canceled)
 */
function appthemes_cancel_review( $id, $trash = false ) {
	$review = appthemes_get_review( $id );
	return $review->cancel( $trash );
}