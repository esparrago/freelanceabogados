<?php
/**
 * Map Reviews capabilities
 *
 * @package Components\Reviews
 */

add_filter( 'map_meta_cap', 'appthemes_reviews_map_capabilities', 10, 4 );

function appthemes_reviews_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		case 'add_review':
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			if ( ! $post ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Users can't review their own post
			// @todo Make overridable by add_theme_support
			if ( $post->post_author == $user_id ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Users can't review posts more than once
			// @todo Make overridable by add_theme_support
			$reviews = appthemes_get_post_reviews( $post->ID, array( 'user_id' => $user_id ) );
			if ( $reviews->get_total_reviews() ) {
				$caps[] = 'do_not_allow';
			}

			break;

		//@todo Add ability for admins to edit reviews
		case 'edit_review':
			$caps = array( 'exist' );

			$review = appthemes_get_review( $args[0] );
			if ( ! $review ) {
				$caps[] = 'do_not_allow';
				break;
			}

			if ( $review->get_author_ID() != $user_id ) {
				$caps[] = 'do_not_allow';
			}

			break;
	}
	return $caps;
}
