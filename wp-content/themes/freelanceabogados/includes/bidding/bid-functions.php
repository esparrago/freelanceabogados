<?php
/**
 * Bidding API functions.
 */

define( 'APP_BIDDING_BIDS_CTYPE', 'bid' );

// comments meta keys
define( 'APP_BIDS_C_DATA_KEY', '_bid_data' );
define( 'APP_BIDS_C_AMOUNT_KEY', '_bid_amount' );
define( 'APP_BIDS_C_CURRENCY_KEY', '_bid_currency' );

// post meta keys
define( 'APP_BIDS_P_DATA_KEY', '_bid_data' );
define( 'APP_BIDS_P_AMOUNT_AVG_KEY', '_bid_amount_avg' );
define( 'APP_BIDS_P_STATUS_KEY', '_bid_status' );
define( 'APP_BIDS_P_BIDS_KEY', '_bids' );

// user meta keys
define( 'APP_BIDS_U_DATA_KEY', '_bid_data' );
define( 'APP_BIDS_U_AMOUNT_AVG_KEY', '_bid_amount_avg' );
define( 'APP_BIDS_U_BIDS_KEY', '_bids' );

### Core

/**
 * Sets an existing WP comment as a bid object and retrieves it.
 *
 * @param int $comment_id			The WordPress comment ID
 * @param string $amount			The bid amount
 * @param string $currency			The bid currency code
 * @param array $meta (optional)	Additional meta to be added to the bid
 * @return object					The created bid object
 */
function appthemes_set_bid( $comment_id, $amount, $currency, $meta = array() ) {
	return APP_Bid_Factory::set( $comment_id, $amount, $currency, $meta );
}

/**
 * Creates and retrieves a new bid object.
 *
 * @param object $comment			A WP comment object
 * @param string $amount			The bid amount
 * @param string $currency			The currency code
 * @param array $meta (optional)	Additional meta to be added to the bid
 * @return object					The created bid object
 */
function appthemes_make_bid( $comment, $amount, $currency, $meta = array() ) {
	return APP_Bid_Factory::create( $comment, $amount, $currency, $meta );
}

/**
 * Retrieve a list of bids.
 *
 * @param array $args	WP query params to be used on the bids query
 * @return array		List of bids
 */
function appthemes_get_bids( $args ) {
	return APP_Bid_Factory::get_bids( $args );
}

/**
 * Retrieve a specific bid.
 *
 * @param int $id	The bid ID
 * @return object	The bid object
 */
function appthemes_get_bid( $id ) {
	return APP_Bid_Factory::retrieve( $id );
}

/**
 * Get the amount for a specific bid.
 *
 * @param int $id	The bid ID
 * @return string	The bid amount
 */
function appthemes_get_bid_amount( $id ) {
	$bid = appthemes_get_bid( $id );
	return $bid->get_amount();
}

### Post Bids

/**
 * Retrieves all the bids for a specific post.
 *
 * @param int $post_id				The post ID to retrieve bids from
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @return array					List of bid objects
 */
function appthemes_get_post_bids( $post_id, $args = array() ) {
	return APP_Bid_Factory::get_post_bid_collection( $post_id, $args );
}

/**
 * Retrieves the average bid amount for a post.
 *
 * @param int $post_id				The post ID
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @param bool $cached (optional)	Whether to retrieve the cached average or from the custom WP_Comment_Query (defaults to TRUE if there are no params)
 * @return string					The average amount
 */
function appthemes_get_post_avg_bid( $post_id, $args = array(), $cached = true ) {
	$bid_col = appthemes_get_post_bids( $post_id, $args );
	return $bid_col->get_avg_amount( empty( $args ) && $cached );
}

/**
 * Retrieves the total bids for post.
 *
 * @param int $post_id				The post ID
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @param bool $cached (optional)	Whether to retrieve the cached total (default) or from the custom WP_Comment_Query (always defaults to TRUE if there are no params)
 * @return int						The total bids
 */
function appthemes_get_post_total_bids( $post_id, $args = array(), $cached = true ) {
	$bid_col = appthemes_get_post_bids( $post_id, $args );
	return $bid_col->get_total_bids( empty( $args ) && $cached );
}

### User Bids

/**
 * Retrieves all the bids for a specific user.
 *
 * @param int $user_id				The user ID to retrieve bids from
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @return array					List of bid objects
 */
function appthemes_get_user_bids( $user_id, $args = array() ) {
	return APP_Bid_Factory::get_user_bid_collection( $user_id, $args );
}

/**
 * Retrieves the user bid for a specific post.
 *
 * @param int $user_id				The user ID
 * @param int $post_id				The post ID
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @return object					A single bid object
 */
function appthemes_get_user_post_bid( $user_id, $post_id, $args = array() ) {
	$args['post_id'] = $post_id;
	$bids = APP_Bid_Factory::get_user_bid_collection( $user_id, $args );
	return reset( $bids->bids );
}

/**
 * Retrieves the average bid amount from a user.
 *
 * @param int $user_id				The user ID
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @param bool $cached (optional)	Whether to retrieve the cached average or re-calculate it (defaults to TRUE if there are no params)
 * @return string					The average amount
 */
function appthemes_get_user_avg_amount( $user_id, $args = array(), $cached = true ) {
	$bid_col = APP_Bid_Factory::get_user_bid_collection( $user_id, $args );
	return $bid_col->get_avg_amount( empty( $args ) && $cached );
}

/**
 * Retrieves the user total bids.
 *
 * @param int $user_id				The post ID
 * @param array $args (optional)	The WP Query params to be passed to the query
 * @param bool $cached (optional)	Whether to retrieve the cached total or re-calculate it (defaults to TRUE if there are no params)
 * @return int						The total bids
 */
function appthemes_get_user_total_bids( $user_id, $args = array(), $cached = true ) {
	$bid_col = APP_Bid_Factory::get_user_bid_collection( $user_id, $args );
	return $bid_col->get_total_bids( empty( $args ) && $cached );
}

### Common

/**
 * Updates the bid meta. Meta is stored as an associative array.
 *
 * If $public is set to true, the value will also be stored on it's own meta key.
 * @param int $id              The bid ID
 * @param string $meta_key     The meta key
 * @param string $meta_value   The meta value
 * @param bool $public (optional) Whether the value should be stored on it's own meta key
 */
function appthemes_update_bid_meta( $id, $meta_key, $meta_value, $public = false ) {
	$bid = appthemes_get_bid( $id );
	return $bid->update_meta( $meta_key, $meta_value, $public );
}

/**
 * Deletes public metadata from the bid. The bid base metadata cannot be deleted.
 *
 * @param int $id              The bid ID
 * @param string $meta_key     The meta key
 * @param string $meta_value   The meta value
 */
function appthemes_delete_bid_meta( $id, $meta_key, $meta_value ) {
	$bid = appthemes_get_bid( $id );
	return $bid->delete_meta( $meta_key, $meta_value );
}

/**
 * Retrieves bid meta.
 *
 * @param int $id						The bid ID
 * @param string $meta_key (optional)	The meta key to retrieve data from
 * @param bool $single (optional)		If set to TRUE then the function will return a single result, as a string.
 *										If false, or not set, then the function returns an array of the custom fields.
 * @return type
 */
function appthemes_get_bid_meta( $id, $meta_key = '', $single = false  ) {
	$bid = appthemes_get_bid( $id );
	return $bid->get_meta( $meta_key, $single );
}

### Status

/**
 * Activates a bid.
 *
 * @param int $id	The bid ID
 * @return boolean	The approve boolean result
 */
function appthemes_activate_bid( $id ) {
	$bid = appthemes_get_bid( $id );
	return $bid->approve();
}

/**
 * Cancels a bid and deletes it if '$trash' is set to true.
 * 
 * @param int $id					The bid ID
 * @param boolean $trash (optional)	If set to true, bid is updated to 'trash', otherwise is set to 'hold'
 * @return int						The result (1 = canceled; 0 = not canceled)
 */
function appthemes_cancel_bid( $id, $trash = false ) {
	$bid = appthemes_get_bid( $id );
	return $bid->cancel( $trash );
}
