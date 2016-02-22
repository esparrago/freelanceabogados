<?php
/**
 * Functions related with workspaces, participants, and their p2p relation.
 *
 * Some notes:
 * . After a proposal 'candidate' is selected to work on a project, he becames a 'participant'
 * . Participants are not directly connected to a project but instead, they are connected to a 'workspace' via a p2p relation
 *
 */

add_action( 'init', '_hrb_register_workspace_post_type', 11 );

add_action( 'init', '_hrb_p2p_workspaces_register', 11 );
add_action( 'init', '_hrb_p2p_participants_register', 12 );

add_action( 'deleted_post', '_hrb_clean_workspace' );
add_action( 'p2p_delete_connections','_hrb_p2p_delete_workspace_connections' );

add_action( 'hrb_agreement_accepted', 'hrb_maybe_activate_workspace', 10, 3 );

add_action( 'working_workspace', 'hrb_workspace_work_start' );


### Hooks Callbacks

/**
 * Register the workspaces post type.
 */
function _hrb_register_workspace_post_type() {
	global $hrb_options;

	$dashboard_permalink = $hrb_options->dashboard_permalink;
	$workspace_permalink = hrb_get_dashboard_permalinks( 'workspace' );

	$dash_workspace_permalink = $dashboard_permalink . '/' . $workspace_permalink;

	$labels = array(
		'name'			=> __( 'Workspaces', APP_TD ),
		'singular_name' => __( 'Workspace', APP_TD ),
		'add_new'		=> __( 'Add New', APP_TD ),
		'add_new_item'	=> __( 'Add New Workspace', APP_TD ),
		'edit_item'		=> __( 'Edit Workspace', APP_TD ),
		'new_item'		=> __( 'New Workspace', APP_TD ),
		'view_item'		=> __( 'View Workspace', APP_TD ),
		'search_items'	=> __( 'Search Workspaces', APP_TD ),
		'not_found'		=> __( 'No workspaces found', APP_TD ),
		'not_found_in_trash' => __( 'No workspaces found in Trash', APP_TD ),
		'parent_item_colon'	 => __( 'Parent Workspaces:', APP_TD ),
		'menu_name'		=> __( 'Worskpaces', APP_TD ),
	);

	$args = array(
		'labels'		=> $labels,
		'hierarchical'	=> false,
		'supports'		=> array( 'title', 'editor', 'author', 'comments' ),
		'public'		=> true,
		'publicly_queryable' => true,
		'query_var'		=> true,
		'rewrite'		=> array( 'slug' => $dash_workspace_permalink . '/workspace', 'with_front' => false ),
		'map_meta_cap'	=> true,
		'show_ui'		=> false,
	);

	register_post_type( HRB_WORKSPACE_PTYPE, $args );

	APP_Item_Registry::register( HRB_WORKSPACE_PTYPE, __( 'Workspace Activation', APP_TD ) );
}

/**
 * Registers p2p connections for 'workspaces'. Relates posts of type 'project' with posts of type 'workspace'.
 */
function _hrb_p2p_workspaces_register() {

	// project workspaces connection
	p2p_register_connection_type( array(
		'name' => HRB_P2P_WORKSPACES,
		'from' => HRB_PROJECTS_PTYPE,
		'to' => HRB_WORKSPACE_PTYPE,
	) );

}

/**
 * For better performance, post related workspaces ID's are stored in the post meta.
 * Makes sure the workspace ID's are synced by deleting the meta key/value if the related workspace ID is also deleted.
 */
function _hrb_clean_workspace( $workspace_id ) {

	$post = get_post( $workspace_id );

	if ( HRB_WORKSPACE_PTYPE != $post->post_type ) {
		return;
	}

	$project = hrb_p2p_get_workspace_post( $workspace_id, array( 'connected_query' => array( 'post_status' => 'trash' ) ) );

	delete_post_meta( $project->ID, '_hrb_workspace', $workspace_id );
}

/**
 * If a p2p connection between a workspace and a post is deleted, also deletes the workspace post.
 */
function _hrb_p2p_delete_workspace_connections( $p2p_ids ) {

	$p2p = p2p_get_connection( $p2p_ids );

	if ( HRB_P2P_WORKSPACES != $p2p->p2p_type ) {
		return;
	}

	wp_delete_post( $p2p->p2p_to, true );
}

/*
 * Immediatelly activate or wait for funds on a given workspace after both parties agree on a project.
 *
 * @since 1.2
 */
function hrb_maybe_activate_workspace( $proposal, $acting_user, $workspace_id ) {

	if ( hrb_is_escrow_enabled() ) {
		// wait for funds
		hrb_update_project_work_status( $workspace_id, $proposal->get_post_ID(), HRB_PROJECT_STATUS_WAITING_FUNDS );
	} else {
		// activate immediately - start work
		hrb_activate_workspace( $workspace_id, $proposal->get_post_ID() );
	}

}

/**
 * Activates a given workspace by updating it's status to 'working'.
 *
 * @since 1.2
 */
function hrb_activate_workspace( $workspace_id, $post_id ) {
	hrb_update_project_work_status( $workspace_id, $post_id, HRB_PROJECT_STATUS_WORKING );
}

/**
 * Cancels a given workspace by updating it's status to 'canceled'.
 *
 * @since 1.2
 */
function hrb_cancel_workspace( $workspace_id, $post_id ) {
	hrb_update_project_work_status( $workspace_id, $post_id, HRB_PROJECT_STATUS_CANCELED );
}

/**
 * Updates the work status to 'working' on a given workspace.
 *
 * @since 1.2
 */
function hrb_workspace_work_start( $workspace_id ) {
	hrb_workspace_update_participants_work($workspace_id, HRB_WORK_STATUS_WORKING );
}

/**
 * Updates the work status for all participants on a given workspace.
 *
 * @since 1.2
 */
function hrb_workspace_update_participants_work( $workspace_id, $status ) {

	$participants = hrb_p2p_get_workspace_participants( $workspace_id )->results;
	foreach( $participants as $participant ) {
		hrb_p2p_update_participant_status( $workspace_id, $participant->ID, $status );
	}

}

### Helper Functions

/**
 * Creates and retrieves a new workspace tha relates to a specific post.
 */
function hrb_new_workspace( $post, $args = array() ) {

	$defaults = array(
		'post_type' => HRB_WORKSPACE_PTYPE,
		'post_status' => 'pending',
		'post_author' => $post->post_author,
		'post_title' => $post->post_title,
	);
	$args = wp_parse_args( $args, $defaults );

	$workspace_id = wp_insert_post( $args );

	if ( is_wp_error( $workspace_id ) ) {
		return false;
	}

	// generate and store a unique key on the workspace post meta
	$workspace_hash = substr( sha1( time() . mt_rand( 0, 1000 ) ), 0, 20 );

	update_post_meta( $workspace_id, '_hrb_workspace_hash', $workspace_hash );

	// adds the new workspace ID to the project meta to avoid querying the p2p DB
	add_post_meta( $post->ID, '_hrb_workspace', $workspace_id );

	return $workspace_id;
}

/**
 * Retrieves all cached workspaces for a specific post
 */
function hrb_get_cached_workspaces_for( $post_id ) {
	$workspaces = get_post_meta( $post_id, '_hrb_workspace' );
	return $workspaces;
}

/**
 * Create a new p2p relation between a post and a 'workspace' post type.
 *
 * @uses do_action() Calls 'hrb_new_workspace'
 *
 */
function hrb_p2p_connect_workspace_to( $post_id, $workspace_id, $meta = array() ) {

	$defaults = array(
		'timestamp' => current_time( 'mysql' ),
	);
	$meta = wp_parse_args( $meta, $defaults );

	$p2p = p2p_type( HRB_P2P_WORKSPACES )->connect( $post_id, $workspace_id, $meta );

	if ( ! $p2p ) {
		return false;
	}

	do_action( 'hrb_new_workspace', $p2p, $post_id, $workspace_id );

	return $p2p;
}

/**
 * Retrieves the post assigned to a specific workspace ID.
 */
function hrb_p2p_get_workspace_post( $workspace_id, $args = array() ) {

	$default = array(
		'connected_query' => array( 'post_status' => 'any' ),
		'suppress_filters' => false,
		'nopaging' => true
	);
	$args = wp_parse_args( $args, $default );

	$query = p2p_type( HRB_P2P_WORKSPACES )->get_connected( $workspace_id, $args );

	return reset( $query->posts );
}

/**
 * Queries the p2p DB to retrieve all the workspaces connected to a specific post ID.
 */
function hrb_p2p_get_post_workspaces( $post_id, $args = array() ) {

	$default = array(
		'suppress_filters' => false,
		'nopaging' => true,
		'connected_type' => HRB_P2P_WORKSPACES,
		'connected_items' => $post_id,
	);
	$args = wp_parse_args( $args, $default );

	return new WP_Query( $args );
}

/**
 * Retrieve the meta status notes for a workspace.
 */
function hrb_get_workspace_status_notes( $workspace_id ) {
	return get_post_meta( $workspace_id, '_hrb_status_notes', true );
}

/**
 * Retrieves the workspace cached hash (unique key).
 */
function hrb_get_workspace_hash( $workspace_id ) {
	return get_post_meta( $workspace_id, '_hrb_workspace_hash', true );
}


### Participants


# Hooks Callbacks

/**
 * Registers p2p connections for 'participants'. Relates users with posts of type 'workspace'.
 */
function _hrb_p2p_participants_register() {

	// project participants connection
	p2p_register_connection_type( array(
		'name' => HRB_P2P_PARTICIPANTS,
		'from' => HRB_WORKSPACE_PTYPE,
		'to' => 'user',
		'admin_box' => array(
			'show' => 'any',
			'context' => 'side'
		 )
	) );
}


### Helper Functions

/**
 * Connects a user to a workspace. Participants include employers and workers.
 * By default, connected participants are of type 'worker' and their status is 'working' (p2p meta).
 *
 * @uses do_action() Calls 'hrb_new_participant'
 *
 */
function hrb_p2p_connect_participant_to( $workspace_id, $user_id, $meta = array() ) {

	$defaults = array(
		'timestamp' => current_time( 'mysql' ),
		'type' => 'worker',
		'status' => HRB_WORK_STATUS_WAITING,
		'status_timestamp' => current_time( 'mysql' ),
	);
	$meta = wp_parse_args( $meta, $defaults );

	$p2p = p2p_type( HRB_P2P_PARTICIPANTS )->connect( $workspace_id, $user_id, $meta );

	if ( ! $p2p ) {
		return false;
	}

	do_action( 'hrb_new_participant', $p2p, $workspace_id, $user_id );

	return $p2p;
}

/**
 * Updates a participant status for a specific workspace ID.
 *
 * @uses do_action() Calls 'hrb_participant_{$status}'
 * @uses do_action() Calls 'hrb_transition_participant_status'
 *
 */
function hrb_p2p_update_participant_status( $workspace_id, $user_id, $status, $notes = '' ) {

	$p2p_id = hrb_p2p_get_participant_p2p_id( $workspace_id, $user_id );

	$updated = p2p_update_meta( $p2p_id, 'status', $status );

	if ( $updated ) {

		$old_status = p2p_get_meta( $p2p_id, 'status', true );

		p2p_update_meta( $p2p_id, 'status_timestamp', current_time( 'mysql' ) );

		if ( $notes ) {
			p2p_update_meta( $p2p_id, 'status_notes', $notes );
		}

		do_action( 'hrb_transition_participant_status', $status, $old_status, $workspace_id, $user_id );
	}

	return $updated;
}

/**
 * Retrieves the participants assigned to a specific workspace.
 */
function hrb_p2p_get_workspace_participants( $workspace_id, $args = array() ) {

	$default = array(
		'connected_type' => HRB_P2P_PARTICIPANTS,
		'connected_meta' => array( 'type' => array( 'worker' ) ),
		'connected_query' => array( 'post_status' => 'any' ),
		'suppress_filters' => false,
		'connected_items' => $workspace_id,
		'nopaging' => true,
	);
	$args = wp_parse_args( $args, $default );

	$query = new WP_User_Query( $args );

	if ( empty( $args['fields'] ) ) {

		// assign all the participant p2p meta fields to the 'data' property for easy access
		foreach( $query->results as $participant ) {
			$participant->data = _hrb_p2p_get_participant( $participant->data );
		}

	}
	return $query;
}

/**
 * Retrieves the participant status in a workspace.
 */
function hrb_p2p_get_participant_status( $workspace_id, $user_id ) {

	$p2p_id = hrb_p2p_get_participant_p2p_id( $workspace_id, $user_id );

	if ( ! $p2p_id ) {
		return false;
	}
	return p2p_get_meta( $p2p_id, 'status', true );
}

/**
 * Checks if work on a specific workspace has ended (any status) for a single or list of participants.
 */
function hrb_is_work_ended( $workspace_id, $user_id = 0 ) {

	$work_ended_statuses = hrb_get_work_ended_statuses();

	// return earlier if the project has been canceled
	if ( get_post_status( $workspace_id ) == HRB_PROJECT_STATUS_CANCELED ) {
		return true;
	}

	if ( ! $user_id ) {

		$args = array(
			'connected_meta' => array(
				'type' => array( 'worker' ),
				'status' => $work_ended_statuses,
			),
		);

		$participants = hrb_p2p_get_workspace_participants( $workspace_id, $args );

		return (bool) ( $participants->results );

	} else {

		$work_status = hrb_p2p_get_participant_status( $workspace_id, $user_id );

		return ( in_array( $work_status, $work_ended_statuses ) );
	}
}

/**
 * Checks if work on a specific workspace is complete for a single or list of participants.
 *
 * @since 1.3
 *
 */
function hrb_is_work_complete( $workspace_id, $user_id = 0 ) {

	if ( ! $user_id ) {

		$args = array(
			'connected_meta' => array(
				'type' => array( 'worker' ),
				'status' => HRB_WORK_STATUS_COMPLETED,
			),
		);

		$participants = hrb_p2p_get_workspace_participants( $workspace_id, $args );

		return (bool) ( $participants->results );

	} else {

		$work_status = hrb_p2p_get_participant_status( $workspace_id, $user_id );

		return $work_status == HRB_WORK_STATUS_COMPLETED;
	}

}

/**
 * Retrieves a participant by p2p_id.
 * Meaningful wrapper for the generic function that retrieves a p2p user with additional p2p meta.
 */
function _hrb_p2p_get_participant( $p2p_id ) {
	return hrb_p2p_get_user_with_meta( $p2p_id );
}

/**
 * Retrieves additional participant data (p2p_meta) for a user/workspace.
 */
function hrb_p2p_get_participant( $workspace_id, $user_id ) {

	$p2p_id = hrb_p2p_get_participant_p2p_id( $workspace_id, $user_id );

	if ( ! $p2p_id ) {
		return false;
	}

	return _hrb_p2p_get_participant( $p2p_id );
}

/**
 * Retrieves the p2p_id for a participant/workspace relation.
 */
function hrb_p2p_get_participant_p2p_id( $workspace_id, $user_id ) {
	return p2p_type( HRB_P2P_PARTICIPANTS )->get_p2p_id( $workspace_id, $user_id );
}

/**
 * Retrieves the post workspaces from the post meta (default) or from the p2p DB, and retrieves the assigned participants.
 */
function hrb_get_post_participants( $post_id, $args = array() ) {

	$defaults = array(
		'cached' => false
	);
	$args = wp_parse_args( $args, $defaults );

	if ( ! empty( $args['cached'] ) && $args['cached'] ) {
		$workspaces_ids = hrb_get_cached_workspaces_for( $post_id );
	} else {
		$p2p_query = hrb_p2p_get_post_workspaces( $post_id, array( 'fields' => 'ids' ) );
		$workspaces_ids = $p2p_query->posts;
	}

	if ( empty( $workspaces_ids ) ) {
		return array();
	}

	return hrb_p2p_get_workspace_participants( $workspaces_ids, $args );
}

/**
 * Given a post and a single user or list of users, retrieves the assigned workspace(s).
 */
function hrb_get_participants_workspace_for( $post_id, $users, $args = array() ) {

	$args['connected_meta'] = array( 'worker', 'employer' );

	$p2p_participants = hrb_get_post_participants( $post_id, $args );

	if ( ! $p2p_participants ) {
		return array();
	}

	$participants_ws = $ws_ids = array();

	foreach( $p2p_participants->results as $participant ) {
		$participants_ws[ $participant->p2p_from ][] = $participant->p2p_to;
	}

	if ( count( $users ) == 1 ) {

		foreach( $participants_ws as $ws_id => $participant_ws ) {

			$intersect = array_intersect( $participant_ws, (array) $users );
			if ( ! empty( $intersect ) ) {
				$ws_ids[] = $ws_id;
			}

		}

	} else {

		foreach( $participants_ws as $ws_id => $participant_ws ) {

			$diff = array_diff( $users, $participant_ws );
			if ( empty( $diff ) ) {
				$ws_ids[] = $ws_id;
				break;
			}

		}

	}
	return $ws_ids;
}

/**
 * Retrieves a single or list of verbiage values from the participants statuses verbiages.
 *
 * @uses apply_filters() Calls 'hrb_participants_statuses_verbiages'
 *
 */
function hrb_get_participants_statuses_verbiages( $status = '' ) {

	$verbiages = array(
		'pending'					=> __( 'Pending', APP_TD ),
		HRB_WORK_STATUS_WAITING		=> __( 'Waiting', APP_TD ),
		HRB_WORK_STATUS_WORKING		=> __( 'Working', APP_TD ),
		HRB_WORK_STATUS_COMPLETED	=> __( 'Completed', APP_TD ),
		HRB_WORK_STATUS_INCOMPLETE	=> __( 'Incomplete', APP_TD ),
	);
	$verbiages = apply_filters( 'hrb_participants_statuses_verbiages', $verbiages, $status );

	return hrb_get_verbiage_values( $verbiages, $status );
}

/**
 * Retrieves the selectable statuses for the employer on a workspace.
 */
function hrb_get_employer_sel_statuses( $participant = '', $workspace_id = 0 ) {

	$statuses = hrb_get_project_work_ended_statuses();

	if ( $workspace_id && ! hrb_is_work_ended( $workspace_id ) ) {
		$statuses = array( HRB_PROJECT_STATUS_CANCELED );
	}
	return $statuses;
}

/**
 * Retrieves the selectable statuses for the worker on a workspace.
 */
function hrb_get_worker_sel_statuses( $participant = '', $workspace_id = 0 ) {

	$statuses = hrb_get_work_ended_statuses();

	return $statuses;
}

/**
 * Retrieves the 'worker' statuses that identify a work as ended.
 *
 * @uses apply_filters() Calls 'hrb_work_ended_statuses'
 *
 */
function hrb_get_work_ended_statuses() {

	$work_ended_statuses = array( HRB_WORK_STATUS_COMPLETED, HRB_WORK_STATUS_INCOMPLETE );

	return apply_filters( 'hrb_work_ended_statuses', $work_ended_statuses );
}

/**
 * Retrieves the statuses that identify a project work as ended.
 * These statuses mirror the 'project' but are applied to the 'workspace' post type.
 */
function hrb_get_project_work_ended_statuses() {

	// these statuses will be used to update the workspace project
	$ended_statuses = array( HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CLOSED_COMPLETED, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE );

	return apply_filters( 'hrb_get_project_work_ended_statuses', $ended_statuses );
}

/**
 * Retrieves user reviews for all participants in a post, or only from those who are participating on the post on a specific workspace.
 */
function hrb_get_post_reviews_for( $post_id, $workspace_id = 0, $args = array() ) {

	if ( ! isset( $args['participants'] ) ) {

		$args['connected_meta'] = array( 'type' => array( 'worker', 'employer' ) );
		$args['fields'] = 'ids';

		if ( empty( $workspace_id) ) {
			$participants = hrb_get_post_participants( $post_id, $args )->results;
		} else {
			$participants = hrb_p2p_get_workspace_participants( $workspace_id, $args )->results;
		}

	} else {
		$participants = $args['participants'];
	}

	$params = array(
		'user_id' => 0,
		'app_user_id__in' => $participants,
		'meta_query' => array(
			array(
				'key' => APP_REVIEWS_C_RECIPIENT_KEY,
				'value' => $participants,
			),
		),
	);

	return appthemes_get_post_reviews( $post_id, $params )->reviews;
}

/**
 * Retrieves a workspace given a review object, by looking at the review participants (reviewer/reviewee).
 */
function hrb_get_review_workspace( $review ) {

	$reviewer = $review->get_author_ID();
	$reviewee = $review->get_recipient_id();

	$participants = array( $reviewer, $reviewee );

	$workspace_id = hrb_get_participants_workspace_for( $review->get_post_ID(), $participants );

	return reset( $workspace_id );
}

/**
 * Retrieves the workspaces assigned to a user.
 */
function hrb_p2p_get_participant_workspaces( $user_id, $args = array() ) {

	$defaults = array(
		'connected_meta' => array( 'type' => array( 'worker', 'employer' ) ),
		'connected_query' => array( 'post_status' => 'any' ),
		'suppress_filters' => false,
		'nopaging' => true
	);
	$args = wp_parse_args( $args, $defaults );

	$workspaces = p2p_type( HRB_P2P_PARTICIPANTS )->get_connected( $user_id, $args );

	return $workspaces;
}

/**
 * Retrieves the posts where the user is participating as 'employer' or 'worker'.
 *
 * @todo: optimize to use fewer queries
 */
function hrb_p2p_get_participating_posts( $user_id, $args = array(), $post_args = array() ) {

	$post_ids = array();

	// get participanting projects - workspace exists

	if ( empty( $args['connected_meta']['type'] ) || in_array( 'worker', $args['connected_meta']['type'] ) ) {

		$workspaces = hrb_p2p_get_participant_workspaces( $user_id, $args );

		$post_ids = array();

		foreach( $workspaces->posts as $workspace ) {
			$post = hrb_p2p_get_workspace_post( $workspace->ID );
			if ( $post ) {
				$post_ids[ $post->ID ] = $workspace->post_status;
			}
		}

	}

	if ( empty( $args['connected_meta']['type'] ) || in_array( 'employer', $args['connected_meta']['type'] ) ) {

		// get authored projects

		$params = array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'posts_per_page' => -1,
			'author' => $user_id,
			'post_status' => 'any'
		);

		if ( ! empty( $post_args['post_status'] ) ) {
			$params['post_status'] = $post_args['post_status'];
		}

		$authored = new WP_Query( $params );

		foreach( $authored->posts as $authored_post ) {
			if ( ! isset( $post_ids[ $authored_post->ID ] ) ) {
				$post_ids[ $authored_post->ID ] = $authored_post->post_status;
			}
		}

	}

	if ( empty( $post_ids ) ) {
		return false;
	}

	// mix all together

	// ignore the post status for the final query since we're filtering by post ID
	unset( $post_args['post_status'] );

	$defaults = array(
		'post_type' => HRB_PROJECTS_PTYPE,
		'post__in' => array_keys( $post_ids ),
		'post_status' => 'any',
		'nopaging' => true,
	);
	$post_args = wp_parse_args( $post_args, $defaults ) ;

	$projects = new WP_Query( $post_args );

	// for workers hack the project status so that the WP_Query object retreives the workspace status for the post, where available
	foreach( $projects->posts as &$project ) {
		if ( isset( $post_ids[ $project->ID ] ) && $project->post_author != $user_id ) {
			$project->post_status = $post_ids[ $project->ID ];
		}
	}

	return $projects;
}

/**
 * Builds and retrieves the workspace URL given the workspace ID or a project ID.
 */
function hrb_get_workspace_url_by( $field = 'workspace_id', $object_id, $user_id = 0 ) {
	global $wp_rewrite;

	// retrieve the workspace ID from a post ID, if specifically requested
	if ( 'post_id' == $field && $user_id ) {
		$workspaces_ids = hrb_get_participants_workspace_for( $object_id, $user_id );

		if ( ! $workspaces_ids || count( $workspaces_ids ) > 1 ) {
			return;
		}
		$object_id = reset( $workspaces_ids );
	}

	if ( ! $object_id ) {
		return;
	}

	### retrieve the URL considering if permalinks are active

	$workspace_permalink = get_permalink( $object_id );

	if ( ! $wp_rewrite->using_permalinks() ) {
		$workspace_permalink = add_query_arg( array( 'dashboard'  => 'workspace' ), $workspace_permalink );
	}

	### append the unique workspace hash to the final URL

	$hash = hrb_get_workspace_hash( $object_id );

	$url = add_query_arg( array( 'hash' => $hash ), $workspace_permalink );

	return $url;
}

/**
 * Builds and returns the dashboard workspace URL for a specific project.
 */
function hrb_get_workspace_url( $workspace_id ) {
	return hrb_get_workspace_url_by( 'workspace_id', $workspace_id );
}
