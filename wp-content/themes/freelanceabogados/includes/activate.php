<?php
/**
 * Functions related with projects and proposals activation.
 *
 * Some Notes:
 * . Most of the callbacks in this file use anonymous naming conventions (prefixed with '_') because they shouldn't be called outside of a hook
 *
 */

add_action( 'appthemes_transaction_completed', '_hrb_handle_completed_transaction' );
add_action( 'appthemes_transaction_completed', '_hrb_handle_completed_credits_transaction' );

add_action( 'appthemes_transaction_activated', '_hrb_activate_plan', 10 );
add_action( 'appthemes_transaction_activated', '_hrb_activate_credits_plan');

add_action( 'appthemes_transaction_activated', '_hrb_activate_addons', 11 );
add_action( 'appthemes_transaction_activated', '_hrb_maybe_change_user_role_employer' );

add_action( 'pending_to_publish', '_hrb_handle_moderated_post' );

add_action( 'hrb_project_activate', '_hrb_maybe_change_user_role_employer' );

add_action( 'appthemes_new_bid', '_hrb_maybe_activate_proposal' );

add_action( 'appthemes_bid_approved', 'hrb_subtract_credits', 10 );
add_action( 'appthemes_bid_approved', '_hrb_maybe_activate_addons', 11 );
add_action( 'appthemes_bid_approved', '_hrb_maybe_change_user_role_freelancer', 12 );


### Hooks Callbacks

# Projects

/**
 * Handle a completed Order by updating the 'purchased' post status to 'publish' or 'pending'.
 */
function _hrb_handle_completed_transaction( $order ) {

	$post = hrb_get_order_post( $order, HRB_PROJECTS_PTYPE );

	if ( ! $post ) {
		return;
    }

	// update the post to 'pending'
	hrb_update_post_status( $post->ID, 'pending' );

	// keep it 'pending' fot moderation or 'publish' it immediately
	if ( ! hrb_moderate_projects() ) {
		hrb_update_post_status( $post->ID, 'publish' );
	}

}

/**
 * Handles a post that changed from 'pending' to 'publish' by checking if it has connected Orders.
 * If the post has connected Orders, enqueues an action callback to activate the related Order (transaction).
 */
function _hrb_handle_moderated_post( $post ) {

	if ( $post->post_type != HRB_PROJECTS_PTYPE ) {
		return;
	}

	$order = appthemes_get_order_connected_to( $post->ID );

	if ( ! $order || $order->get_status() == APPTHEMES_ORDER_FAILED || 'publish' != $post->post_status ) {
		return;
	}

	add_action( 'save_post', '_hrb_activate_parent_order', 11 );
}

/**
 * Activates the Order connected to a post.
 */
function _hrb_activate_parent_order( $post_id ) {

	if ( get_post_type( $post_id ) != HRB_PROJECTS_PTYPE ) {
		return;
	}

	$order = appthemes_get_order_connected_to( $post_id );

	if ( ! $order ) {
		return;
	}

	$order->activate();
}

/**
 * Activates an Order plan by updating the purchased post meta with the duration for each of the purchased addons.
 */
function _hrb_activate_plan( $order ) {

	if ( ! hrb_get_order_post( $order, HRB_PROJECTS_PTYPE ) ) {
		return;
    }

	if ( ! $project_data = hrb_get_order_plan_data( $order ) ) {
		return;
    }

	extract( $project_data );

	if ( hrb_is_publishable( $post ) ) {
		hrb_update_post_status( $post_id, 'publish' );
	}

    hrb_update_project_duration( $post_id, $plan_data['duration'] );
}

/**
 * Activates the addons for an Order by updating the purchased post meta with their durations.
 */
function _hrb_activate_addons( $order ) {
	global $hrb_options;

	if ( ! hrb_get_order_post( $order, HRB_PROJECTS_PTYPE ) ) {
		return;
	}

	if ( ! $project_data = hrb_get_order_plan_data( $order ) ) {
		return;
    }

	extract( $project_data );

	foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {

		foreach( $order->get_items( $addon ) as $item ) {

			// consider the durations for included and separate addons
			if ( ! empty( $plan_data[ $addon ] ) ) {
				hrb_add_addon( $item['post_id'], $addon, $plan_data[ $addon . '_duration' ] );
			} else {
				hrb_add_addon( $item['post_id'], $addon, $hrb_options->addons[ $addon ]['duration'] );
			}
		}

	}

}

/**
 * If the user is posting a project with the temporary 'can_edit_projects' cap make him an employer/freelancer (if not already).
 */
function _hrb_maybe_change_user_role_employer( $post ) {

    if ( is_a( $post, 'APP_Order' ) ) {
        $user_id = $post->get_author();
    } else {
        $user_id = $post->post_author;
    }

	if ( hrb_check_user_role( HRB_ROLE_EMPLOYER, $user_id ) ) {
		return;
	}

	// use the temp cap 'can_edit_projects' to check if the user can post projects
    if ( ! user_can( $user_id, 'can_edit_projects' ) && ! user_can( $user_id, 'manage_options' ) ) {
        hrb_assign_role_caps( $user_id, HRB_ROLE_BOTH );
    }

}


### Conditionals

/**
 * Check if project needs to be published.
 */
function hrb_is_publishable( $post ) {
	return in_array( $post->post_status, array( 'draft', 'pending', 'expired' ) );
}


### Helper Functions

/**
 * Mirrors 'appthemes_transaction_activated' by triggering an activation hook when charging is disabled and a new project is posted.
 *
 * @uses do_action() Calls 'hrb_project_activate'
 *
 */
function hrb_activate_free_project( $post ) {
    do_action( 'hrb_project_activate', $post );
}



# Proposals

/**
 * Handles a completed Credits Plan Order with no 'purchased' posts (separate plan) by instantly activating it.
 */
function _hrb_handle_completed_credits_transaction( $order ) {

	if ( ! hrb_get_separate_plan_purchase( $order, HRB_PROPOSAL_PLAN_PTYPE ) ) {
		return;
	}

	$order->activate();
}

/**
 * Activates a Credits Plan Order by updating the customer credits balance.
 */
function _hrb_activate_credits_plan( $order ) {

	$plan_data = hrb_get_separate_plan_purchase( $order, HRB_PROPOSAL_PLAN_PTYPE );

	if ( ! $plan_data ) {
		return;
	}

	extract( $plan_data );

	hrb_give_credits( $order->get_author(), $plan_data['credits'] );
}

/**
 * Activates a proposal immediately if it does not need moderation.
 */
function _hrb_maybe_activate_proposal( $proposal ) {

	if ( hrb_moderate_proposals() ) {
		return;
    }
	appthemes_activate_bid( $proposal->get_id() );
}

/**
 * After a proposal is approved subtract the credits required to post the proposal.
 * If the user does not have sufficient credtis notifies him and keeps the proposal unapproved.
 */
function hrb_subtract_credits( $proposal ) {

	$credits_required = $proposal->_hrb_credits_required;

	if ( $credits_required > 0 ) {
		$user_id = $proposal->get_user_id();

		if ( ! hrb_user_has_required_credits( $credits_required, $user_id ) ) {

			hrb_insufficient_credits_notify( $user_id, $credits_required, $proposal );

			$proposal->cancel();
			return;
		}

		$updated_credits = $credits_required * -1;

		hrb_update_user_credits( $user_id, $updated_credits );
	}

}

/**
 * Activates a proposal addon by updating it's meta with the addon data.
 *
 * @todo use constant name for meta key
 */
function _hrb_maybe_activate_addons( $proposal ) {

    if ( $proposal->_hrb_featured ) {
        // store the 'featured' flag on a separate key for easier use in queries
        appthemes_update_bid_meta( $proposal->get_id(), '_hrb_featured', current_time('timestamp'), $public = true );
    }

}

/**
 * If the user is bidding on a project with the temporary 'can_edit_bids' make him an employer/freelancer (if not already).
 */
function _hrb_maybe_change_user_role_freelancer( $proposal ) {

	$user_id = $proposal->get_user_id();

	if ( hrb_check_user_role( HRB_ROLE_FREELANCER, $user_id ) ) {
		return;
	}

	// use the temp cap 'can_edit_bids' to check if the user can post proposals
    if ( ! user_can( $user_id, 'can_edit_bids' ) && ! user_can( $user_id, 'manage_options' ) ) {
        hrb_assign_role_caps( $user_id, HRB_ROLE_BOTH );
    }

}
