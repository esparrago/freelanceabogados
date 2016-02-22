<?php
/**
 * Functions related with posts status changes.
 */

add_action( 'init', '_hrb_schedule_project_prune' );
add_action( 'hrb_prune_expired_projects', 'hrb_prune_expired_projects' );

add_action( 'transition_post_status', '_hrb_maybe_update_project_start_date', 10, 3 );
add_action( 'hrb_new_project', '_hrb_maybe_publish_free_project', 10, 2 );

add_filter( 'posts_clauses', '_hrb_expired_project_sql', 10, 2 );


### Hooks Callbacks

/**
 * Schedules a project prune to unpublish expired projects.
 */
function _hrb_schedule_project_prune() {

	if ( ! wp_next_scheduled( 'hrb_prune_expired_projects' ) ) {
		wp_schedule_event( time(), 'hourly', 'hrb_prune_expired_projects' );
	}

}

/**
 * Immediatelly publishes a project if moderation is disabled. Updates it to 'pending' otherwise.
 */
function _hrb_maybe_publish_free_project( $post_id, $order = '' ) {

	// skip earlier if the post was purchased
	if ( ! empty( $order ) ) {
		return;
	}

	if ( hrb_moderate_projects() ) {
		$status = 'pending';
	} else {
		$status = 'publish';
	}

	hrb_update_post_status( $post_id, $status );

	return (bool) ( 'publish' == $status );
}

/**
 * Updates a project start date when the status transitions from a pre-set list of non-published statuses to 'publish'.
 */
function _hrb_maybe_update_project_start_date( $new_status, $old_status, $post ) {

	if ( HRB_PROJECTS_PTYPE != $post->post_type || 'publish' != $new_status ) {
		return;
	}

	// notify auhtors, candidates and participantes  when projects expire or are canceled
	elseif ( in_array( $old_status, array( 'pending', 'draft', 'expired' ) ) ) {
		hrb_update_project_start_date( $post );
	}

}

/**
 * Query modifier for easier retrieval of expired projects.
 */
function _hrb_expired_project_sql( $clauses, $wp_query ) {
	global $wpdb;

	if ( $wp_query->get( 'expired_listings' ) ) {
		$clauses['join'] .= " INNER JOIN " . $wpdb->postmeta . " AS exp1 ON (" . $wpdb->posts . ".ID = exp1.post_id)";

		$clauses['where'] .= " AND ( exp1.meta_key = '_hrb_duration' AND DATE_ADD(post_date, INTERVAL exp1.meta_value DAY) < '" . current_time( 'mysql' ) . "' AND exp1.meta_value > 0 )";
	}
	return $clauses;
}


## Helper Functions

/**
 * Wrapper for 'wp_update_post()' to update a post status.
 */
function hrb_update_post_status( $post_id, $new_status ) {

	return wp_update_post( array(
		'ID'			=> $post_id,
		'post_status'	=> $new_status
	) );

}

/**
 * Updates the works meta status for a project/workspace.
 */
function hrb_update_project_work_status( $workspace_id, $project_id, $status, $notes = '' ) {

	// updates the project status
	$updated = hrb_update_post_status( $project_id, $status );

	if ( $updated ) {

		// updates the workspace status
		$success = hrb_update_post_status( $workspace_id, $status );

		if ( $success ) {

			// check for an end status
			if ( in_array( $status, hrb_get_project_work_ended_statuses() ) ) {

				// if final workspace status differs from the work status fire a specific disagreement hook
				if ( hrb_is_workspace_status_disagreement( $workspace_id, $status ) ) {
					do_action( 'hrb_workspace_ended_disagreement_' . $status, $workspace_id, $project_id, $status );
				} else {
					do_action( 'hrb_workspace_ended_agreement_' . $status , $workspace_id, $project_id, $status );
				}

			}

		}

		if ( $notes ) {
			update_post_meta( $workspace_id, '_hrb_status_notes', $notes );
		}

	}
	return $updated;
}

/**
 * Checks if the new status for a given workspace ID differs from the participant(s) work status.
 *
 * @since 1.3
 *
 * @param int $workspace_id The workspace ID.
 * @param string $status The new workspace status.
 * @param int $participant_id (optional) The participant ID to compare the statuses.
 * @return boolean True if there's a disagreement, False otherwise.
 */
function hrb_is_workspace_status_disagreement( $workspace_id, $status, $participant_id = 0 ) {
	return HRB_PROJECT_STATUS_CLOSED_COMPLETED != $status && hrb_is_work_complete( $workspace_id, $participant_id );
}

/**
 * Searches and removes expired projects by setting their status to 'expired'.
 */
function hrb_prune_expired_projects() {

	$expired_posts = new WP_Query( array(
		'post_type' => HRB_PROJECTS_PTYPE,
		'post_status' => 'publish',
		'expired_listings' => true,
		'nopaging' => true,
	) );

	foreach( $expired_posts->posts as $post ) {
		hrb_update_post_status( $post->ID, HRB_PROJECT_STATUS_EXPIRED );
	}

}

/**
 * Cancels a project by setting the status to 'canceled'.
 */
function hrb_cancel_project( $post_id ) {
	return hrb_update_post_status( $post_id, HRB_PROJECT_STATUS_CANCELED );
}

/**
 * Reopens a project by setting the status to 'publish'.
 */
function hrb_reopen_project( $post_id ) {
	return hrb_update_post_status( $post_id, 'publish' );
}

/**
 * Archives a project by adding an 'archived' meta field to the post meta.
 * The meta field stores the user who archived the project.
 * An additional field with the archived date is also stored.
 */
function hrb_archive_project( $post_id, $user_id ) {
	add_post_meta( $post_id, '_hrb_archived', $user_id );
	add_post_meta( $post_id, '_hrb_archived_date'.'-'.$user_id, current_time('mysql') );
}

/**
 * Trashes or deletes a project.
 */
function hrb_delete_project( $post_id, $force_delete = false ) {
	if ( $force_delete ) {
		return wp_delete_post( $post_id, $force_delete );
	}
	return hrb_update_post_status( $post_id, 'trash' );
}

/**
 * Updates the start date for a project.
 */
function hrb_update_project_start_date( $post, $timestamp = '' ) {

	if ( $post->post_type == HRB_PROJECTS_PTYPE ) {

		if ( ! $timestamp ) {
			$timestamp = current_time('mysql');
		}

		wp_update_post( array(
			'ID' => $post->ID,
			'post_date' => $timestamp,
		) );

	}

}

/**
 * Checks if a given project was archived by a specific user or current logged user.
 */
function hrb_is_project_archived( $post_id, $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	$archived = get_post_meta( $post_id, '_hrb_archived' );

	if ( ( ! empty( $archived ) && ! $user_id ) || ( $user_id && in_array( $user_id , $archived ) ) ) {
		return true;
	}
	return false;
}
