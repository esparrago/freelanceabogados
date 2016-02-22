<?php
/**
 * Functions related with the main custom post type: project.
 */

add_filter( 'preprocess_comment', '_hrb_process_clarification_comment' );
add_filter( 'pre_comment_approved',	'_hrb_clarification_auto_approve', 10, 2 );
add_filter( 'get_avatar_comment_types', '_hrb_enable_clarification_avatars' );
add_filter( 'comments_open', '_hrb_clarification_open', 10, 2 );

add_action( 'comment_form', '_hrb_output_clarification_ctype_field' );


### Hooks Callbacks

/**
 * Listen to submitted comments with the 'clarification' custom comment type.
 */
function _hrb_process_clarification_comment( $data ) {

	if ( ! isset( $_POST['comment_type'] ) || HRB_CLARIFICATION_CTYPE != $_POST['comment_type'] ) {
		return $data;
	}

	// set the custom comment type
	$data['comment_type'] = HRB_CLARIFICATION_CTYPE;

	return $data;
}

/**
 * Ignore WordPress discussion settings for this comment type and approve the clarification comments.
 */
function _hrb_clarification_auto_approve( $approved, $commentdata ) {

	if ( empty( $commentdata['comment_type'] ) || $commentdata['comment_type'] != HRB_CLARIFICATION_CTYPE ) {
		return $approved;
	}

	return true;
}


function _hrb_enable_clarification_avatars( $allowed_types ) {

	$allowed_types[] = HRB_CLARIFICATION_CTYPE;

	return $allowed_types;
}

/**
 * Outputs the hidden custom comment type field in the project comments form.
 */
function _hrb_output_clarification_ctype_field() {

	if ( ! is_singular( HRB_PROJECTS_PTYPE ) ) {
		return;
	}

	echo html( 'input', array( 'type' => 'hidden', 'name' => 'comment_type', 'value' => HRB_CLARIFICATION_CTYPE ) );
}

/**
 * Check if comments for projects should be opened.
 *
 * @since 1.3
 */
function _hrb_clarification_open( $open, $post_id = 0 ) {
	global $post;

	$post_id = $post_id ? $post_id : $post->ID;

	if ( HRB_PROJECTS_PTYPE != get_post_type( $post_id ) ) {
		return $open;
	}

	// clarification comments are only available for published projects
	if ( 'publish' != get_post_status( ! $post_id ) ) {
		return false;
	}

	return $open;
}


### Projects Base URL

/**
 * Contextually retrieves the base URL for projects listings.
 */
function get_the_hrb_projects_base_url() {
	global $wp_rewrite, $hrb_options;

	if ( is_tax( array( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_SKILLS ) ) ) {
		return get_term_link( get_queried_object() );
	}

	if ( $wp_rewrite->using_permalinks() ) {
		$url = $hrb_options->project_permalink;
		return home_url( user_trailingslashit( $url ) );
	} else {
		return get_post_type_archive_link( HRB_PROJECTS_PTYPE );
	}

}


### Main WP_Query for Projects

/**
 * Retrieves a WP_Query object with a list of projects, sorted by importance (featured on frontpage first), by default.
 */
function hrb_get_projects( $args = array() ) {
	global $hrb_options, $wp_query;

	$base = array(
		'post_type' => HRB_PROJECTS_PTYPE,
	);

	$defaults = array(
		'post_status'	 => array( 'publish' ),
		'posts_per_page' => $hrb_options->projects_per_page,
	);
	$args = wp_parse_args( $args, $defaults );

	return new WP_Query( array_merge( $base, $args ) );
}


### Project 'fake' Object

/**
 * Wrapper for 'get_post()' that retrieves an extended 'post' object with additonal relevant data wrapped as a 'project'.
 */
function hrb_get_project( $post = '' ) {

	if ( ! $post ) {
		$project = get_queried_object();

		if ( HRB_PROJECTS_PTYPE != $project->post_type ) {
			return null;
		}

	} elseif ( is_a( $post, 'WP_Post' ) ) {
		$project = $post;
	} else {
		$project = get_post( (int) $post );
	}

	if ( ! $project ) {
		return null;
	}

	### Categories, skills & tags

	$terms = get_the_hrb_project_terms( $project->ID, HRB_PROJECTS_CATEGORY );

	foreach( $terms as $term ) {

		if ( $term->parent > 0 ) {
			$project->subcategories = $term->term_id;
			$project->subcategories_name = $term->name;
		} else {
			$project->categories = $term->term_id;
			$project->categories_name = $term->name;
		}
	}

	$project->skills = wp_list_pluck( get_the_hrb_project_terms( $project->ID, HRB_PROJECTS_SKILLS ), 'term_id' );
	$project->tags = implode( ',', wp_list_pluck( get_the_hrb_project_terms( $project->ID, HRB_PROJECTS_TAG ), 'name' ) );

	return $project;
}


### Verbiages

/**
 * Retrieves all the projects statuses verbiages or a single status verbiage.
 */
function hrb_get_project_statuses_verbiages( $status = '' ) {

	$verbiages = array(
		'publish'	=> __( 'Open for Proposals', APP_TD ),
		'draft'		=> __( 'Incomplete Draft', APP_TD ),
		'pending'	=> __( 'Pending Moderation', APP_TD ),
		HRB_PROJECT_STATUS_WAITING_FUNDS	=> __( 'Waiting Funds', APP_TD ),
		HRB_PROJECT_STATUS_TERMS			=> __( 'Discussing Agreement', APP_TD ),
		HRB_PROJECT_STATUS_CANCELED_TERMS	=> __( 'Agreement Canceled', APP_TD ),
		HRB_PROJECT_STATUS_WORKING			=> __( 'In Development', APP_TD ),
		HRB_PROJECT_STATUS_CANCELED			=> __( 'Canceled', APP_TD ),
		HRB_PROJECT_STATUS_CLOSED_COMPLETED	=> __( 'Completed', APP_TD ),
		HRB_PROJECT_STATUS_CLOSED_INCOMPLETE=> __( 'Incomplete', APP_TD ),
		HRB_PROJECT_STATUS_EXPIRED			=> __( 'Expired', APP_TD ),
		HRB_PROJECT_META_STATUS_ARCHIVED	=> __( 'Archived', APP_TD ),
	);

	return hrb_get_verbiage_values( $verbiages, $status );
}

/**
 * Retrieves all the unique projects statuses withtin a WP_Query posts result.
 */
function hrb_get_projects_unique_statuses( $wp_query, $user_id = 0 ) {

	if ( empty( $wp_query->posts ) ) {
		return array();
	}

	$statuses = array();

	foreach( $wp_query->posts as $post ) {

		if ( hrb_is_project_archived( $post->ID, $user_id ) ) {
			$statuses[] = HRB_PROJECT_META_STATUS_ARCHIVED;
		} else {
			$statuses[] = $post->post_status;
		}

	}

	return $statuses;
}


### Meta

/**
 * Retrieves the 'form field name'/'meta field name' pairs identifying the budget fields that need to be handled on the  project form.
 *
 * Key = form field name (as used in the project form)
 * Value = meta key name (as saved in post meta)
 *
 */
function hrb_get_project_form_budget_fields() {
	$fields = array (
		'budget_currency' => '_hrb_budget_currency',
		'budget_type'	  => '_hrb_budget_type',
		'budget_price'	  => '_hrb_budget_price',
		'hourly_min_hours'=> '_hrb_hourly_min_hours',
	);
	return $fields;
}

/**
 * Retrieves the 'form field name'/'meta field name' pairs identifying the location fields that need to be handled on the project form.
 *
 * Key = form field name (as used in the project form)
 * Value = prefixed meta key name (as saved in post meta)
 *
 */
function hrb_get_project_form_location_fields() {

	// main location fields / meta keys
	$fields = array(
		'location'	=> '_hrb_location',
		'location_type'	=> '_hrb_location_type'
	);

	// other location fields / meta keys
	foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
		$meta_key = "_hrb_location_{$location_att}";
		$fields[ $meta_key ] = $meta_key;
	}

	return $fields;
}

/**
 * Merges and retrieves all the 'form field name'/'meta field name' pairs identifying the fields that need to be handled on the project form.
 *
 * @uses apply_filters() Calls 'hrb_project_form_meta_fields'
 *
 */
function hrb_get_project_form_meta_fields(){
	$fields = array_merge(
		hrb_get_project_form_budget_fields(),
		hrb_get_project_form_location_fields(),
		array( 'duration' => '_hrb_duration' )
	);
	return apply_filters( 'hrb_project_form_meta_fields', $fields );
}

/**
 * Updates the development terms for a given project.
 *
 * @uses do_action() Calls 'hrb_updated_project_terms'
 *
 */
function hrb_update_project_dev_terms( $post_id, $proposal, $dev_terms ) {

	$curr_dev_terms = get_post_meta( $post_id, '_hrb_project_terms', true );

	if ( $dev_terms && $curr_dev_terms != $dev_terms ) {
		update_post_meta( $post_id, '_hrb_project_terms', $dev_terms );

		do_action( 'hrb_updated_project_terms', $post_id, $proposal, $dev_terms );
	}
}

/**
 * Updates the duration for a given project.
 */
function hrb_update_project_duration( $post_id, $duration = 0 ) {
    return update_post_meta( $post_id, '_hrb_duration', $duration );
}


### Conditionals

/**
 * Checks if projects need moderation.
 */
function hrb_moderate_projects() {
	global $hrb_options;
	return (bool) $hrb_options->moderate_projects;
}

/**
 * Checks if a given project is reviewable by a specific user.
 */
function hrb_is_project_reviweable_by( $user_id, $post_id, $workspace_ids = 0 ){

	if ( ! $workspace_ids ) {
		$workspace_ids = hrb_get_participants_workspace_for( $post_id, $user_id );
	}

	foreach( (array) $workspace_ids as $workspace_id ) {

		if ( ! is_hrb_workspace_open_for_reviews( $workspace_id ) ) {
			return false;
		}

		if ( ! hrb_is_work_ended( $workspace_id ) ) {
			return false;
		}

	}
	return true;
}

/**
 * Check if an user was already given a review on a project.
 */
function hrb_is_user_reviewed_on_project( $post_id, $recipient_id, $user_id ){

	// retrieve reviews with any status
	$args = array(
		'user_id' => $user_id,
		'post_id' => $post_id,
		'status' => '',
	);
	$reviews = appthemes_get_user_reviews( $recipient_id, $args );

	return (bool) ( ! empty( $reviews->reviews ) );
}

/**
 * Check if categories on a project are editable.
 */
function hrb_categories_editable( $post_id ) {

	$post = get_post( $post_id );

	if ( ! $post_id || 'draft' == $post->post_status || ! hrb_charge_listings() || is_hrb_relisting() ) {

		$order = hrb_get_pending_order_for( $post_id );
		if ( $order && $order->get_gateway() ) {
			return false;
		}
		return true;
	}
	return false;
}


### Helper Functions

/**
 * Retrieve the number of selectable skills.
 *
 * @since 1.3
 *
 * @uses apply_filters() Calls 'hrb_project_max_skills_selection'
 */
function hrb_get_allowed_skills_count() {
	global $hrb_options;

	return apply_filters( 'hrb_project_max_skills_selection', $hrb_options->projects_allowed_skills );
}

/**
 * Retrieves the currently selected candidates for a given project.
 *
 * @param int $post_id The project post ID.
 *
 * @since 1.3.1
 */
function hrb_p2p_get_post_selected_candidates( $post_id ) {
	$args = array(
		'connected_meta' => array( 'status' => array( HRB_PROPOSAL_STATUS_SELECTED ) ),
	);

	return hrb_p2p_get_post_candidates( $post_id, $args )->results;
}