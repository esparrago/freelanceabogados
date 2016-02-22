<?php
/**
 * Functions related with proposals.
 *
 * Proposals meta keys:
 *	_hrb_featured
 *	_hrb_delivery
 *	_hrb_accept_site_terms
 *	_hrb_credits_required
 *	_hrb_development_terms
 *	_hrb_candidate_decision
 *	_hrb_candidate_decision_timestamp
 *	_hrb_candidate_decision_notes
 *	_hrb_employer_decision
 *	_hrb_employer_decision_timestamp
 *	_hrb_employer_decision_notes
 *	_hrb_status
 *
 */


### Proposal 'fake' Object

/**
 * Wrapper for 'appthemes_get_bid()' that retrieves an extended 'bid' object with additonal relevant data wrapped as a 'proposal'.
 */
function hrb_get_proposal( $bid, $force_refresh = false ) {

	if ( ! is_a( $bid, 'APP_Single_Bid' ) ) {
		$proposal = appthemes_get_bid( (int) $bid );
	} else {
		if ( ! $force_refresh ) {
			$proposal = $bid;
		} else {
			$proposal = appthemes_get_bid( $bid->get_id() );
		}
	}

	if ( ! $proposal ) {
		return false;
    }

	// assign the parent project to the proposal
	$proposal->project = hrb_get_project( $proposal->get_post_ID() );

	// assign the budget labels to the proposal
	$budget_labels = hrb_get_proposal_budget_labels( $proposal->project->_hrb_budget_type );

	foreach ( $budget_labels as $field => $label ) {
		$proposal->$field = $label;
	}

	// @todo should fetch the proposal status directly

	// flag the prpoposal as selected if the author is a candidate to the project
	//$proposal->selected = (bool) hrb_p2p_get_candidate_p2p_id( $proposal->get_post_ID(), $proposal->get_user_id() );

	$proposal->selected = ( $proposal->_hrb_status == HRB_PROPOSAL_STATUS_SELECTED );

	return $proposal;
}


### Verbiages / Labels

/**
 * Retrieves all the proposals statuses verbiages or a single status verbiage.
 */
function hrb_get_proposals_statuses_verbiages( $status = '', $post_status = '' ) {

	if ( ! $status ) {
		if ( 'publish' == $post_status ) {
			$status = HRB_PROPOSAL_STATUS_PENDING;
		} elseif( $post_status ) {
			$status = 'not_assigned';
		}
	}

	$verbiages = array(
		HRB_PROPOSAL_STATUS_ACTIVE   => __( 'Active', APP_TD ),
		HRB_PROPOSAL_STATUS_SELECTED => __( 'Selected', APP_TD ),
		HRB_PROPOSAL_STATUS_ACCEPTED => __( 'Assigned', APP_TD ),
		HRB_PROPOSAL_STATUS_PENDING  => __( 'Pending', APP_TD ),
        HRB_PROPOSAL_STATUS_DECLINED => __( 'Declined', APP_TD ),
		HRB_PROPOSAL_STATUS_CANCELED => __( 'Canceled', APP_TD ),
		'not_assigned' => __( 'Not Assigned', APP_TD ),
	);

	$verbiage = hrb_get_verbiage_values( $verbiages, $status );

	return $verbiage;
}

/**
 * Conditionally retrieves budget related labels based on the budget type.
 */
function hrb_get_proposal_budget_labels( $budget_type, $field = '' ) {

	if ( 'fixed' == $budget_type ) {
		$label['label_delivery_type_slug'] = 'days';
		$label['label_delivery_type'] = __( 'Deliver in', APP_TD );
		$label['label_delivery_unit'] = __( 'Days', APP_TD );
		$label['label_milestone_type'] = '%';
	} else {
		$label['label_delivery_type_slug'] = 'hours';
		$label['label_delivery_type'] = __( 'Work Hours', APP_TD );
		$label['label_delivery_unit'] = __( 'Hours', APP_TD );
		$label['label_milestone_type'] = __( 'Hours/Week', APP_TD );
	}

	return hrb_get_verbiage_values( $label, $field );
}


### Meta

/**
 * Retrieves the 'form field name'/'meta field name' pairs identifying the fields that need to be handled on the main proposal form.
 *
 * Key = form field name (as used in the proposal form)
 * Value = meta key name (as saved in comment meta)
 *
 * @uses apply_filters() Calls 'hrb_proposal_form_meta_fields'
 *
 */
function hrb_get_proposal_form_meta_fields() {
	$fields = array (
		'featured'			=> '_hrb_featured',
		'delivery'			=> '_hrb_delivery',
		'accept_site_terms' => '_hrb_accept_site_terms',
		'credits_required'  => '_hrb_credits_required',
	);
	return apply_filters( 'hrb_proposal_form_meta_fields', $fields );
}


### Helper Functions

/**
 * Iterates through a list of bid objects and retrieves it as a list of proposals with the additional meta that makes up a proposal.
 */
function _hrb_get_bids_as_proposals( $bids ) {

	if ( ! is_a( $bids, 'APP_Bid_Collection' ) ) {
		trigger_error( 'Invalid Bid collection found while trying to retrieve bids as proposals.', E_USER_WARNING );
	}

	$proposals['results'] = array();

	foreach( $bids->bids as $bid ) {
		$proposals['results'][] = hrb_get_proposal( $bid );
	}

	$proposals['found'] = $bids->found;

	return $proposals;
}

/**
 * Wrapper for 'appthemes_get_user_bids()' that retrieves a list of proposals with additional relevant meta.
 */
function hrb_get_proposals_by_user( $user_id, $args = array() ) {
	$bids = appthemes_get_user_bids( $user_id, $args );

	return _hrb_get_bids_as_proposals( $bids );
}

/**
 * Wrapper for 'appthemes_get_post_bids()' that retrieves a list of proposals with additional relevant meta.
 */
function hrb_get_proposals_by_post( $post_id, $args = array() ) {
	$bids = appthemes_get_post_bids( $post_id, $args );

	return _hrb_get_bids_as_proposals( $bids );
}

/**
 * Retrieves the average delivery time for the proposals of a given post.
 */
function hrb_get_post_avg_proposal_delivery( $post_id ) {
	$bids = hrb_get_proposals_by_post( $post_id );

	if ( ! $bids['results'] ) {
		return 0;
	}

	$delivery_values = array();

	foreach( $bids['results'] as $bid ) {
		$delivery_values[] = $bid->_hrb_delivery;
	}

	if ( ! $delivery_values ) {
		return 0;
	}

	return (int) ( array_sum( $delivery_values ) / count( $delivery_values ) );
}

/**
 * Updates the status of a proposal.
 *
 * @uses do_action() Calls 'hrb_proposal_status_{$status}'
 * @uses do_action() Calls 'hrb_proposal_status_transition'
 *
 */
function hrb_update_proposal_status( $proposal, $status ) {

    $old_status = $proposal->_hrb_status;

	appthemes_update_bid_meta( $proposal->get_id(), '_hrb_status', $status, true );

	if ( HRB_PROPOSAL_STATUS_SELECTED == $status ) {
		appthemes_update_bid_meta( $proposal->get_id(), '_hrb_employer_decision', '' );
		appthemes_update_bid_meta( $proposal->get_id(), '_hrb_employer_notes', '' );
	}

	if ( $old_status != $status ) {
		do_action( "hrb_proposal_status_{$status}", $proposal );
		do_action( "hrb_proposal_status_transition", $status, $old_status, $proposal );
	}
}

/**
 * Retrieves all the unique statuses for a list of proposals.
 */
function hrb_get_proposals_statuses( $proposals ) {
	$proposals = wp_list_pluck( $proposals, '_hrb_status' );
	$statuses = array_unique( array_filter( $proposals, 'strlen' ) );

	$statuses[] = HRB_PROPOSAL_STATUS_ACTIVE;

	return $statuses;
}

/**
 * Retrieves the status for a given proposal.
 */
function hrb_get_proposal_status( $proposal_id ) {

	$proposal = hrb_get_proposal( $proposal_id );

	return ( $proposal->_hrb_status ? $proposal->_hrb_status : ( 'publish' != $proposal->project->post_status ? 'not_assigned' : 'pending' ) );
}

/**
 * Cancels a proposal.
 */
function hrb_cancel_proposal( $proposal_id ) {

	$proposal = hrb_get_proposal( $proposal_id );

	hrb_update_proposal_status( $proposal, HRB_PROPOSAL_STATUS_CANCELED );

	return appthemes_cancel_bid( $proposal_id );
}


### Conditionals

/**
 * Checks if posted proposals need moderation.
 *
 * @uses apply_filters() Calls 'hrb_moderate_proposals'
 *
 */
function hrb_moderate_proposals() {
	return apply_filters( 'hrb_moderate_proposals', false );
}
