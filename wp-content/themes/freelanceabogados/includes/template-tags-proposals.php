<?php
/**
 * Mirrors WordPress template tag functions (the_post(), the_content(), etc), used in the Loop.
 *
 * Altough these function are not used in a WP Loop, they intend to work in the same way as to be self explanatory and retrieve data intuitively.
 *
 * Contains template tag functions for: proposal (comment type)
 *
 */

/**
 * Retrieves the URL to call a dynamic action on a proposal.
 */
function get_the_hrb_proposal_action_url( $proposal_id, $action ) {

	$args = array(
		'action' => 'mb',
		'p_action' => $action,
		'proposal_id' => $proposal_id
	);

	return add_query_arg( $args, get_permalink( hrb_get_dashboard_url_for('proposals') ) );
}

/**
 * Retrieves the permalink for editing a proposal. Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_proposal_edit_url( $proposal ) {
	global $wp_rewrite, $hrb_options;

     $proposal = hrb_get_proposal( $proposal );
     if ( ! $proposal ) {
         return;
	 }

    $project_id = $proposal->get_post_ID();
    $proposal_id = $proposal->get_id();

	$page_url = get_permalink( HRB_Proposal_Edit::get_id() );

	if ( $wp_rewrite->using_permalinks() ) {
		$permalink = $hrb_options->edit_proposal_permalink;
		$page_url = untrailingslashit( $page_url );

		return "$page_url/$project_id/$permalink/$proposal_id";
	}

	$args = array(
		'project_id' => $project_id,
		'proposal_edit' => $proposal_id,
	);
	return add_query_arg( $args, $page_url);
}

/**
 * Outputs the formatted link to edit a proposal.
 */
function the_hrb_proposal_edit_link( $proposal_id, $text = '', $before = '', $after = '', $atts = array() ) {

	if ( !current_user_can( 'edit_bid', $proposal_id ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Edit Proposal', APP_TD );
	}

	$default = array(
		'class' => 'button success',
		'href' => get_the_hrb_proposal_edit_url( $proposal_id ),
	);
	$atts = wp_parse_args( $atts, $default );

	echo html( 'a', $atts, $before . $text . $after );
}

/**
 * Outputs the formatted link to edit a proposal.
 */
function the_hrb_proposal_cancel_link( $proposal_id, $text = '', $before = '', $after = '', $atts = array() ) {

	if ( !current_user_can( 'edit_bid', $proposal_id ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Withdraw Proposal', APP_TD );
	}

	$default = array(
		'class' => 'button secondary',
		'href' => get_the_hrb_proposal_action_url( $proposal_id, 'cancel' ),
		'onclick' => 'return confirm("' . __( 'Are you sure you want to withdraw this proposal? Proposal will be discarded.', APP_TD ) . '");',
	);
	$atts = wp_parse_args( $atts, $default );

	echo html( 'a', $atts, $before . $text . $after );
}

/**
 * Retrieves the URL for purchasing credits spendable in proposals.
 */
function hrb_get_credits_purchase_url() {
	return get_permalink( HRB_Credits_Purchase::get_id() );
}

/**
 * Retrieves the URL for vieweing a proposal.
 */
function get_the_hrb_proposal_url( $bid ) {

	$args = array(
		'review_proposal' => $bid->get_id(),
		'project_id' => $bid->get_post_id(),
	);
	return add_query_arg( $args, hrb_get_dashboard_url_for( 'projects' ) );
}

/**
 * Outputs the formatted delivery time for a proposal.
 */
function the_hrb_proposal_delivery_time( $proposal, $before = '', $after = '' ) {

	if ( 'days' == $proposal->label_delivery_type_slug ) {
		$text = sprintf( '%1$s %2$s %3$s', $proposal->label_delivery_type, $proposal->_hrb_delivery, _n( __( 'Day', APP_TD ), __( 'Days', APP_TD ), $proposal->_hrb_delivery ) );
	} else {
		$text = sprintf( '%1$s %2$s', $proposal->_hrb_delivery, $proposal->label_delivery_type );
	}

	echo $before . $text . $after;
}

/**
 * Outputs the formatted start time for a proposal.
 */
function the_participant_proposal_start_time( $proposal, $before = '', $after = '' ) {
	$start_date = $proposal->agreement_timestamp;

	echo $before . appthemes_display_date( $start_date ) . $after;
}

/**
 * Outputs the formatted development terms for a proposal.
 */
function the_participant_proposal_terms( $proposal, $before = '', $after = '' ) {

	$terms = $proposal->_hrb_development_terms;

	if ( ! $terms ) {
		$terms = __( 'None', APP_TD );
	}

	echo $before . $terms . $after;
}

/**
 * Outputs the formatted proposal amount.
 */
function the_hrb_proposal_amount( $proposal, $before = '', $after = '' ) {
	$project = hrb_get_project( $proposal->get_post_ID() );

	echo $before . sprintf( '%s %s', get_the_hrb_proposal_amount( $proposal ) , get_the_hrb_project_budget_type( $project ) ) . $after;
}

/**
 * Retrieves the proposal amount with the related currency.
 */
function get_the_hrb_proposal_amount( $proposal ) {
	return appthemes_display_price( $proposal->amount, $proposal->currency );
}

/**
 * Retrieves the total amount for a given proposal considering the budget type (per hour or fixed).
 */
function hrb_get_the_user_proposal_total_amount( $proposal ) {

	$amount = $proposal->get_amount();

	$project = hrb_get_project( $proposal->get_post_ID() );

	$budget_type = $project->_hrb_budget_type;

	if ( 'fixed' == $budget_type ) {
		return $amount;
	} else {
		return $amount * $proposal->_hrb_delivery;
	}

}

/**
 * Outputs the formatted proposal total amount.
 */
function the_hrb_user_proposal_total_amount( $proposal, $before = '', $after = '' ) {

	$project = hrb_get_project( $proposal->get_post_ID() );

	$budget_type = $project->_hrb_budget_type;

	if ( 'fixed' == $budget_type ) {
		$type = '';
	} else {
		$type = sprintf( ' <small>(%1$s * %2$sh)</small>', appthemes_get_price( $proposal->get_amount(), $proposal->currency ), $proposal->_hrb_delivery );
	}

	echo $before . sprintf( '%1$s %2$s', appthemes_display_price( hrb_get_the_user_proposal_total_amount( $proposal ), $proposal->currency ), $type ) . $after;
}