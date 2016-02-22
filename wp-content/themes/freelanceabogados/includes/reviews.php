<?php
/**
 * Functions related with user reviews.
 */

add_filter( 'comments_clauses', '_hrb_join_authored_received_reviews', 10 );
add_filter( 'appthemes_review_link', '_hrb_set_review_link', 10, 2 );

add_action( 'appthemes_new_user_review', '_hrb_maybe_activate_review' );


### Hooks Callbacks

/**
 * Query modifier that changes the reviews clauses in order to retrieve authored and received reviews for an user.
 */
function _hrb_join_authored_received_reviews( $clauses ) {
	global $wp_query;

	// make sure to join review relation only if requested
	if ( isset( $wp_query->query_vars['hrb_apply_filter_reviews'] ) && strpos( $clauses['where'], '_AND_AUTHORED_' ) !== FALSE ) {
		$clauses['join'] = str_replace( 'INNER', 'LEFT', $clauses['join'] );
		$clauses['where'] = str_replace( '_AND_AUTHORED_', '', $clauses['where'] );
		$clauses['where'] = str_replace( 'AND user_id =', 'AND user_id =', $clauses['where'] );
		$clauses['where'] = str_replace( 'AND ( (', 'OR ( (', $clauses['where'] );
	}
	return $clauses;
}

/**
 * Check if a review need moderation before being activated.
 */
function _hrb_maybe_activate_review( $review ) {

	// check if review should be moderated
	hrb_maybe_activate_review( $review );
}

function _hrb_set_review_link( $link, $comment ) {

	$workspace_id = hrb_get_review_workspace( appthemes_get_review( $comment->comment_ID ) );

	return hrb_get_workspace_url( $workspace_id );
}

### Helper Functions

/**
 * Checks if reviews are moderated.
 */
function hrb_moderate_reviews() {
	return false;
}

/**
 * See 'hrb_maybe_activate_review()'.
 */
function hrb_maybe_activate_review( $review ) {

	if ( hrb_moderate_reviews() ) {
		return;
	}

	appthemes_activate_review( $review->get_id() );
}

