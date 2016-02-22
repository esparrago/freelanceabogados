<?php
/**
 * Functions related with agreement and their relation with proposals and candidates.
 *
 * Some notes:
 * . Agreement data is stored in the related proposal meta
 * . Each selected proposal creates a p2p connection between the project and the proposal author, representing a 'candidate'
 *
 */

//add_action( 'transition_post_status', 'hrb_unselect_pending_candidates', 10, 3 );
add_action( 'init', '_hrb_p2p_candidates_register' );
add_action( 'hrb_proposal_status_transition', 'hrb_update_proposal_candidate_status', 10, 3 );


### Hooks Callbacks

/**
 * Registers p2p connections for candidates. Relates users with posts of type 'project'.
 */
function _hrb_p2p_candidates_register() {

	// project candidates connection
	p2p_register_connection_type( array(
		'name' => HRB_P2P_CANDIDATES,
		'from' => HRB_PROJECTS_PTYPE,
		'to' => 'user',
	) );

}

/**
 * Wrapper triggered by a hook used to update a candidate proposal status.
 */
function hrb_update_proposal_candidate_status( $status, $old_status, $proposal ) {
	hrb_update_candidate_status( $proposal->get_post_id(), $proposal->get_user_id(), $status );
}


### Helper Functions

/**
 * Connects a candidate to a post.
 *
 * @uses do_action() Calls 'hrb_new_candidate'
 *
 */
function hrb_p2p_connect_candidate_to( $post_id, $user_id, $proposal, $meta = array() ) {

	$defaults = array(
		'proposal_id'	=> $proposal->get_id(),
		'timestamp'		=> current_time( 'mysql' ),
		'status'		=> 'pending',
	);
	$meta = wp_parse_args( $meta, $defaults );

	// create a new candidate relation to a post only there are no previous relations
	$p2p_id = hrb_p2p_get_candidate_p2p_id( $post_id, $user_id );

	if ( ! $p2p_id ) {

		$p2p = p2p_type( HRB_P2P_CANDIDATES )->connect( $post_id, $user_id, $meta );

		if ( ! $p2p ) {
			return false;
		}

	} else {

		$p2p = p2p_get_connection( $p2p_id );

		// reset the meta for a previously existing candidate
		p2p_update_meta( $p2p_id, 'status_timestamp', $meta['timestamp'] );
		p2p_update_meta( $p2p_id, 'status', $meta['status'] );

	}

	do_action( 'hrb_new_candidate', $p2p, $user_id, $post_id, $proposal );

	return $p2p;
}

/**
 * Retrieves the candidates list for a specific post.
 * By default, only considers candidates those who have a 'pending' status.
 */
function hrb_p2p_get_post_candidates( $post_id, $args = array() ) {

	$default = array(
		'connected_query'	=> array( 'post_status' => 'any' ),
		'connected_meta'	=> array( 'status' => array( 'pending' ) ),
		'suppress_filters'	=> false,
		'nopaging'			=> true
	);
	$args = wp_parse_args( $args, $default );

	$query = p2p_type( HRB_P2P_CANDIDATES )->get_connected( $post_id, $args );

	// assign all the candidate p2p meta fields to the 'data' property for easy access
	foreach( $query->results as $candidate ) {
		$candidate->data = _hrb_p2p_get_candidate( $candidate->data );
	}
	return $query;
}

/**
 * Retrieves a candidate by p2p_id.
 * Meaningful wrapper for the generic function that retrieves a p2p user with additional p2p meta.
 */
function _hrb_p2p_get_candidate( $p2p_id ) {
	return hrb_p2p_get_user_with_meta( $p2p_id );
}

/**
 * Retrieves a post candidate given a post and the user.
 */
function hrb_p2p_get_candidate( $post_id, $user_id ) {

	$p2p_id = hrb_p2p_get_candidate_p2p_id( $post_id, $user_id );

	if ( ! $p2p_id ) {
		return false;
	}

	return _hrb_p2p_get_candidate( $p2p_id );
}

/**
 * Retrieves a candidate p2p_id given the user and the post.
 */
function hrb_p2p_get_candidate_p2p_id( $post_id, $user_id ) {
	return p2p_type( HRB_P2P_CANDIDATES )->get_p2p_id( $post_id, $user_id );
}

/**
 * Updates a candidate status by updating the related p2p meta field.
 *
 * @uses do_action() Calls 'hrb_updated_candidate_status'
 *
 */
function hrb_update_candidate_status( $post_id, $user_id, $status ) {

	$p2p_id = hrb_p2p_get_candidate_p2p_id( $post_id, $user_id );

	$updated = p2p_update_meta( $p2p_id, 'status', $status );

	if ( $updated ) {
		$old_status = p2p_get_meta( $p2p_id, 'status', true );

		do_action( 'hrb_updated_candidate_status', $p2p_id, $status, $old_status );
	}

	return $updated;
}


### Agreement

/**
 * Anlyses the agreement decision between the employer and the proposal candidate and triggers callbacks to handle the decision.
 */
function hrb_maybe_agree_terms( $proposal, $acting_user, $new_decision ) {

	// get the updated proposal
	$proposal = hrb_get_proposal( $proposal, $refresh = true );

	$employer_decision = $proposal->_hrb_employer_decision;
	$candidate_decision = $proposal->_hrb_candidate_decision;

	if ( $candidate_decision == $employer_decision && HRB_TERMS_ACCEPT == $employer_decision ) {
		$workspace_id = hrb_proposal_shake_hands( $proposal, $acting_user );
		return $workspace_id;
	} else {
		hrb_proposal_no_agreement( $proposal, $acting_user, $new_decision );
	}
	return false;
}

/**
 * Users haven't yet reached an agreement. Altough being declined, negotations remain active until a user cancels the negotiation.
 *
 * @uses do_action() Calls 'hrb_no_agreement'
 *
 */
function hrb_proposal_no_agreement( $proposal, $user, $decision ) {
	do_action( 'hrb_no_agreement', $proposal, $user, $decision );
}

/**
 * Users agreed terms to start a project.
 *
 * Creates and connects a 'workspace' to the project.
 * Creates and connects the 'participant' to the new workspace.
 *
 * @uses do_action() Calls 'hrb_agreement_accepted'
 *
 */
function hrb_proposal_shake_hands( $proposal, $acting_user ) {

	$project_id = $proposal->project->ID;

	$candidate_id = $proposal->get_user_id();
	$employer_id = get_post_field( 'post_author', $project_id );

	// @todo - maybe allow selecting the workspace where candidate will be assigned - v.1.xx

	// create a new workspace for the project
	$workspace_id = hrb_new_workspace( $proposal->project );

	// could not create workspace
	if ( ! $workspace_id ) {
		return false;
	}

	// create a p2p relation betwen the workspace and the project
	hrb_p2p_connect_workspace_to( $project_id, $workspace_id );

	// add the owner to the participants list
	hrb_p2p_connect_participant_to( $workspace_id, $employer_id, array(
		'type' => 'employer',
		'agreement_timestamp' => current_time('mysql') )
	);

	// add candidate to participants list
	hrb_p2p_connect_participant_to( $workspace_id, $candidate_id, array(
		'proposal_id' => $proposal->get_id(),
		'development_terms' => $proposal->_hrb_development_terms,
		'agreement_timestamp' => current_time('mysql') )
	);

	// set the proposal status as accepted
	hrb_update_proposal_status( $proposal, HRB_PROPOSAL_STATUS_ACCEPTED );

	do_action( 'hrb_agreement_accepted', $proposal, $acting_user, $workspace_id );

	return $workspace_id;
}

/**
 * Users haven't reached an agreement and negotiatons end without success.
 *
 * @uses do_action() Calls 'hrb_agreement_canceled'
 *
 */
function hrb_agreement_cancel( $proposal, $user ) {

	hrb_update_proposal_status( $proposal, HRB_PROPOSAL_STATUS_DECLINED );

	hrb_update_post_status( $proposal->get_post_ID(), HRB_PROJECT_STATUS_CANCELED_TERMS );

	do_action( 'hrb_agreement_canceled', $proposal, $user );
}

/**
 * A proposal was selected as candidate.
 *
 * Creates and connects the proposal 'candidate' to the project.
 */
function hrb_proposal_selected( $proposal ) {

	$project_id = $proposal->project->ID;
	$candidate_id = $proposal->user_id;

	// add the proposal author as a new candidate to work on the project
	$p2p = hrb_p2p_connect_candidate_to( $project_id, $candidate_id, $proposal );

	if ( ! $p2p ) {
		return; // error
	}

	// set the project status to 'terms' discussion
	hrb_update_post_status( $project_id, HRB_PROJECT_STATUS_TERMS );

	// set the proposal status to 'selected'
	hrb_update_proposal_status( $proposal, HRB_PROPOSAL_STATUS_SELECTED );
}

/**
 * Updates a proposal agreement decision for the project or proposal authors. The decision data is stored in the proposal meta.
 *
 * @uses do_action() Calls 'hrb_updated_agreement_decision'
 *
 */
function hrb_update_agreement_decision( $proposal, $user, $decision, $notes = '' ) {

	if ( $user->ID == $proposal->get_user_id() ) {
		$user_relation = 'candidate';
	} else {
		$user_relation = 'employer';
	}

	appthemes_update_bid_meta( $proposal->get_id(), "_hrb_{$user_relation}_decision", $decision );
	appthemes_update_bid_meta( $proposal->get_id(), "_hrb_{$user_relation}_decision_timestamp", current_time('mysql') );

	if ( $notes ) {
		appthemes_update_bid_meta( $proposal->get_id(), "_hrb_{$user_relation}_notes", $notes );
	}

	do_action( 'hrb_updated_agreement_decision', $proposal, $user, $decision, $notes );
}


/**
 * Updates the proposal candidate development terms. The development terms are stored in the proposal meta.
 *
 * @uses do_action() Calls 'hrb_updated_proposal_dev_terms'
 *
 */
function hrb_update_proposal_dev_terms( $proposal, $terms ) {

	$curr_terms = $proposal->_hrb_development_terms;

	if ( $terms && $curr_terms != $terms ) {

		appthemes_update_bid_meta( $proposal->get_id(), '_hrb_development_terms', $terms );

		do_action( 'hrb_updated_proposal_dev_terms', $proposal, $terms );
	}

}

/**
 * Retrieves the custom meta fields related with an agreement decision. The decision data is stored in the proposal, in this fields.
 */
function hrb_get_proposal_agreement_meta( $field = '' ) {

	$agreement = array(
		'employer_decision',
		'employer_notes',
		'candidate_decision',
		'candidate_notes',
	);

	if ( $field && isset( $agreement[ $field ] ) ) {
		return $agreement[ $field ];
	}

	return $agreement;
}

/**
 * Retrieves the agreement decision verbiages.
 */
function hrb_get_agreement_decision_verbiage( $decision, $role = '' ) {

    if ( ! $decision && $role ) {

        if ( 'employer' == $role ) {
            $decision = 'selected';
		} else {
            $decision = 'deciding';
		}

    }

	$verbiages = array(
		HRB_TERMS_SELECT		=> __( 'Selected', APP_TD ),
		HRB_TERMS_PROPOSE		=> __( 'Proposed Terms', APP_TD ),
		HRB_TERMS_ACCEPT		=> __( 'Accepted', APP_TD ),
		HRB_TERMS_DECLINE		=> __( 'Declined', APP_TD ),
		HRB_TERMS_CANCEL		=> __( 'Canceled Negotiation', APP_TD ),
		HRB_TERMS_UNASSIGNED	=> __( 'Not Assigned', APP_TD ),
		'deciding'				=> __( 'Deciding', APP_TD ),
	);
	return hrb_get_verbiage_values( $verbiages, $decision );
}

/*
function hrb_unselect_pending_candidates( $new_status, $old_status, $post ) {

	if ( HRB_PROJECTS_PTYPE != $post->post_type || HRB_PROJECT_STATUS_TERMS == $new_status ) {
		return;
	}

	$args = array(
		'connected_meta' => array( 'status' => 'pending' ),
	);
	$candidates = hrb_p2p_get_post_candidates( $post->ID, $args );

	// @todo use constant name for status name

	foreach( $candidates->results as $candidate ) {
		hrb_update_candidate_status( $post->ID, $candidate->ID, HRB_TERMS_UNASSIGNED );
	}
}
*/