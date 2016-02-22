<?php
/**
 * Hooks and functions related with credits.
 *
 * Credits allows submitting and featuring proposals
 *
 */

add_action( 'init', '_hrb_legacy_credit_reset_option', 10 );
add_action( 'init', '_hrb_register_credit_plans_post_type', 11 );
add_action( 'init', '_hrb_schedule_credits_reset', 12 );

add_action( 'user_register', 'hrb_give_credits' );


### Hooks Callbacks

/**
 * Update new 'credit_offer' option with legacy 'credits_reset' option, if available.
 */
function _hrb_legacy_credit_reset_option() {
	global $hrb_options;

	// for users with the legacy option checked default the credits offer to the number set in 'credits_given'
	if ( ! empty( $hrb_options->credits_reset ) && empty( $hrb_options->credits_offer ) ) {
		$hrb_options->credits_reset = '';
		$hrb_options->credits_offer = $hrb_options->credits_given;
	}

}

/**
 * Registers the proposals credits plans post type.
 */
function _hrb_register_credit_plans_post_type() {

	$labels = array(
		'name' => __( 'Credit Plans', APP_TD ),
		'singular_name' => __( 'Credit Plans', APP_TD ),
		'add_new' => __( 'Add New', APP_TD ),
		'add_new_item' => __( 'Add New Plan', APP_TD ),
		'edit_item' => __( 'Edit Plan', APP_TD ),
		'new_item' => __( 'New Plan', APP_TD ),
		'view_item' => __( 'View Plan', APP_TD ),
		'search_items' => __( 'Search Plans', APP_TD ),
		'not_found' => __( 'No Plans found', APP_TD ),
		'not_found_in_trash' => __( 'No Plans found in Trash', APP_TD ),
		'parent_item_colon' => __( 'Parent Plan:', APP_TD ),
		'menu_name' => __( 'Credit Plans', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,
		'supports' => array( 'page-attributes' ),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => false,
	);

	register_post_type( HRB_PROPOSAL_PLAN_PTYPE, $args );

	$plans = new WP_Query( array( 'post_type' => array( HRB_PRICE_PLAN_PTYPE, HRB_PROPOSAL_PLAN_PTYPE ), 'nopaging' => 1 ) );
	foreach( $plans->posts as $plan ) {
		$data = get_post_custom( $plan->ID );
		if ( isset( $data['title'] ) ) {
			APP_Item_Registry::register( $plan->post_name, $data['title'][0] );
		}
	}

}

/**
 * Schedules an automatic credits reset, if enabled in the settings menu.
 */
function _hrb_schedule_credits_reset() {
	global $hrb_options;

	if ( ! $hrb_options->credits_offer ) {
		return;
	}

	if ( ! wp_next_scheduled( 'hrb_reset_users_credits' ) ) {
		wp_schedule_event( time(), 'daily', 'hrb_reset_users_credits' );
	}
}

/**
 * Give free credits to users or a given user.
 */
function hrb_reset_users_credits( $user_id = 0 ) {
	global $hrb_options;

	if ( ! $hrb_options->credits_offer || '01' != date('d') ) {
		return;
	}

	// reset all users
	if ( ! $user_id ) {

		// iterate though all valid user roles (employers/freelancers)
		$users = hrb_get_users( array( 'number' => 0 ) );

		foreach( $users->results as $user ) {
			hrb_give_credits( $user->ID, $hrb_options->credits_offer );
		}

	} else {
		// reset a specific user
		hrb_give_credits( $user_id, $hrb_options->credits_offer );
	}

}

/**
 * Gives credits to a specific user. Defaults to the given credits option.
 */
function hrb_give_credits( $user_id, $credits = 0 ) {
	global $hrb_options;

	if ( ! $credits ) {
		$credits = (int) $hrb_options->credits_given;
	}

	hrb_update_user_credits( $user_id, $credits );
}

### Helper Functions

/**
 * Retrieve all the available credits options (where credits can be spent).
 *
 * @uses apply_filters() Calls 'hrb_credits_options'
 *
 */
function hrb_credits_options() {
	$credits_options = array( 'credits_apply', 'credits_apply_edit', 'credits_feature' );

	//  key = main name used for comparison
	//  value = setting name (as available in the options global)
	$credits_options = array(
		'send_proposal'		=> 'credits_apply',
		'edit_proposal'		=> 'credits_apply_edit',
		'feature_proposal'	=> 'credits_feature'
	);

	return apply_filters( 'hrb_credits_options', $credits_options );
}

/**
 * Check if credits are active by summing up the required credits for all the credits options.
 */
function hrb_credit_plans_active() {
	return (bool) hrb_required_credits_to();
}

/**
 * Update user credits.
 *
 * @uses do_action() Calls 'hrb_updated_user_credits'
 *
 */
function hrb_update_user_credits( $user_id, $credits ) {

	$curr_credits = hrb_get_user_credits( $user_id );

	$curr_credits += $credits;

	if ( $curr_credits < 0 ) {
		$curr_credits = 0;
	}
	update_user_meta( $user_id, 'hrb_credits', $curr_credits );

	do_action( 'hrb_updated_user_credits', $user_id, $credits, $curr_credits );
}

/**
 * Retrieves the total credits available for a specific user.
 */
function hrb_get_user_credits( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	return (int) get_user_meta( $user_id, 'hrb_credits', true );
}

/**
 * Checks if a user has a certain number of required credits.
 */
function hrb_user_has_required_credits( $required, $user_id = 0 ) {

	$credits = hrb_get_user_credits( $user_id );

	if ( ! $required ) {
		return true;
	}
	return ( $credits >= $required );
}

/**
 * Check if the user has enough credits for a specific option.
 */
function hrb_user_has_credits_to( $option, $user_id = 0 ) {
	$required = hrb_required_credits_to( $option );

	return hrb_user_has_required_credits( $required , $user_id );
}

/**
 * Retrieves the required credits for 'purchasing' a specific option.
 *
 * @uses apply_filters() Calls 'hrb_required_credits_to'
 *
 */
function hrb_required_credits_to( $options = '' ) {
	global $hrb_options;

	$available_options = hrb_credits_options();

	$required = 0;

	if ( ! empty( $options ) )  {
		$diff_options = array_diff( (array) $options , array_keys( $available_options ) );

		if ( ! empty( $diff_options ) ) {
			trigger_error( 'Invalid credits option!' );
		}
	}

	foreach( array_flip( $available_options ) as $option ) {
		if ( empty( $options ) || in_array( $option, (array) $options ) ) {
			$required += (int) $hrb_options->$available_options[ $option ];
		}
	}

	return apply_filters( 'hrb_required_credits_to', $required, $options );
}

/**
 * Checks if users need credits for certain site features.
 */
function hrb_credits_enabled() {
	return hrb_required_credits_to();
}
