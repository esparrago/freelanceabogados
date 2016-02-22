<?php
/**
 * Bidding meta capabilities.
 */

add_filter( 'map_meta_cap', 'appthemes_bid_map_capabilities', 10, 4 );

function appthemes_bid_map_capabilities( $caps, $cap, $user_id, $args ){

	switch( $cap ) {

        case 'add_bid':
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			if ( ! $post ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Users can't bid on their own post
			// @todo Make overridable by add_theme_support
			if ( $post->post_author == $user_id ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Users can't bid posts more than once
			// @todo Make overridable by add_theme_support
			$bids = appthemes_get_post_bids( $post->ID, array( 'user_id' => $user_id ) );
			if ( $bids->get_total_bids() ) {
				$caps[] = 'do_not_allow';
			}

			break;

		//@todo Add ability for admins to edit bids
		case 'edit_bid':
			$caps = array( 'exist' );

			$bid = appthemes_get_bid( $args[0] );
			if ( ! $bid ){
				$caps[] = 'do_not_allow';
				break;
			}

			if ( $bid->get_user_id() != $user_id ) {
				$caps[] = 'do_not_allow';
			}

			break;
	}
	return $caps;
}