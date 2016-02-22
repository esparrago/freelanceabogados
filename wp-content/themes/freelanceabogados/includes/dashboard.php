<?php
/**
 * Dashboard related functions tipically used within the Dashboard.
 *
 * Also contains template tag functions for projects and proposals, used within the Dashboard.
 *
 */

add_action( 'hrb_before_dashboard_front', 'hrb_maybe_warn_no_public_email', 5 );
add_action( 'hrb_dashboard', 'hrb_maybe_warn_escrow_info_missing', 5 );


### Hooks Callbacks

/**
 * Inform users that they need to fill their shared email in their profile in order to apply to projects.
 */
function hrb_maybe_warn_no_public_email() {
	$user = wp_get_current_user();

	if ( empty( $user->hrb_email ) ) {
		$profile_link = html_link( appthemes_get_edit_profile_url(), __( 'Update Profile', APP_TD ) );

		if ( user_can( $user->ID, 'edit_projects' ) && user_can( $user->ID, 'edit_bids' ) ) {
			appthemes_display_notice( 'warning', sprintf( __( '<strong>Note:</strong> Before you accept or apply to a project you\'ll need to provide a public email. %s.', APP_TD ), $profile_link ) );
		} else {

			if ( user_can( $user->ID, 'edit_projects' ) ) {
				appthemes_display_notice( 'success', sprintf( __( 'Note: Before you agree on a project you\'ll need to provide a public email. %s.', APP_TD ), $profile_link ) );
			}

			if ( user_can( $user->ID, 'edit_bids' ) ) {
				appthemes_display_notice( 'success', sprintf( __( 'Note: Before applying to projects you\'ll need to provide a public email. %s.', APP_TD ), $profile_link ) );
			}

		}

	}

}

/**
 * Inform users that they need to fill escrow related info.
 */
function hrb_maybe_warn_escrow_info_missing() {
	$user = wp_get_current_user();

	if ( hrb_is_escrow_enabled() && current_user_can('edit_bids') && ! hrb_escrow_receiver_gateway_fields_valid( $user->ID ) ) {
		$profile_link = html_link( hrb_get_dashboard_url_for( 'payments', 'escrow' ), __( 'Fill Information', APP_TD ) );

		appthemes_display_notice( 'error',sprintf( __( "Note: Escrow information is missing. You cannot receive payments whitout that information.<br/>%s.", APP_TD ), $profile_link ) );
	}

}


### Helper Functions

/**
 * Retrieves the available dashboard pages and their info: title, permalink.
 *
 * @uses apply_filters() Calls 'hrb_dashboard_pages'
 *
 */
function hrb_dashboard_pages() {
	global $hrb_options;

	$permalinks = array(
		'dashboard' => array(
			'name' => __( 'Dashboard', APP_TD ),
			'permalink' => $hrb_options->dashboard_permalink,
		),
		'notifications' => array(
			'name' => __( 'Notifications', APP_TD ),
			'permalink' => $hrb_options->dashboard_notifications_permalink,
		),
		'projects' => array(
			'name' => __( 'Projects', APP_TD ),
			'permalink' => $hrb_options->dashboard_projects_permalink,
		),
		'reviews' => array(
			'name' => __( 'Reviews', APP_TD ),
			'permalink' => $hrb_options->dashboard_reviews_permalink,
		),
		'proposals' => array(
			'name' => __( 'Proposals', APP_TD ),
			'permalink' => $hrb_options->dashboard_proposals_permalink,
		),
		'favorites' => array(
			'name' => __( 'Favorites', APP_TD ),
			'permalink' => $hrb_options->dashboard_faves_permalink,
		),
		'payments' => array(
			'name' => __( 'Payments', APP_TD ),
			'permalink' => $hrb_options->dashboard_payments_permalink,
		),
		'workspace' => array(
			'name' => __( 'Workspace', APP_TD ),
			'permalink' => $hrb_options->dashboard_workspace_permalink,
		),
		'review' => array(
			'name' => __( 'Review', APP_TD ),
			'permalink' => $hrb_options->review_user_permalink,
		),
	);
	return apply_filters( 'hrb_dashboard_pages', $permalinks );
}

/**
 * Retrieves the permalinks for a given page or for all the dashboard pages.
 */
function hrb_get_dashboard_permalinks( $page = '' ) {

	$permalinks = wp_list_pluck( hrb_dashboard_pages(), 'permalink' );

	if ( $page && isset( $permalinks[ $page ] ) ) {
		return $permalinks[ $page ];

	} elseif( ! $page ) {
		return $permalinks;
	}
	return false;
}

/**
 * Retrieves the dashboard fixed slug name corresponding to the requested permalink query var slug (dynamic as saved in permalinks).
 */
function hrb_get_dashboard_name() {

	$dashboard_type = hrb_get_dashboard_page();

	$names = wp_list_pluck( hrb_dashboard_pages(), 'name' );

	if ( ! isset( $names[ $dashboard_type ] ) ) {
		$name = __( 'Dashboard', APP_TD );
	} else {
		$name = $names[ $dashboard_type ];
	}
	return $name;
}

/**
 * Retrieves the dashboard page corresponding to the requested permalink query var slug (dynamic as saved in permalinks).
 */
function hrb_get_dashboard_page() {
	global $wp_query;

	$dash_page = $wp_query->get('dashboard');

	if ( ! $dash_page ) {
		return false;
	}

	$permalinks = array_flip( hrb_get_dashboard_permalinks() );

	// default to the front dashboard page if the requested page is not valid
	if ( ! isset( $permalinks[ $dash_page ] ) ) {
		$page = 'dashboard';
	} else {
		$page = $permalinks[ $dash_page ];
	}

	if ( 'dashboard' == $page ) {
		$page = 'front';
	}
	return $page;
}

/**
 * Retrieves the list of projects assigned to the current user. Assigned projects can be those authored by the user or awarded to him.
 */
function hrb_get_dashboard_projects( $args = array() ) {
	global $hrb_options;

	$dashboard_user = wp_get_current_user();

	$defaults = array(
		'paged' => get_query_var( 'paged' ),
		'posts_per_page' => get_query_var( 'filter_posts_per_page' ) ? get_query_var( 'filter_posts_per_page' ) : $hrb_options->projects_per_page,
		'post_status' => 'any'
	);
	$args = wp_parse_args( $args, $defaults );

	return hrb_p2p_get_participating_posts( $dashboard_user->ID, array( 'post_status' => $args['post_status'] ), $args );
}

/**
 * Retrieves the list of proposals for a given project, or all the proposals sent by the current user, if the project is not specified.
 */
function hrb_get_dashboard_proposals( $post_id = 0, $args = array() ) {
	$dashboard_user = wp_get_current_user();

	if ( $post_id ) {
		$proposals = hrb_get_proposals_by_post( $post_id, $args );
	} else {
		$proposals = hrb_get_proposals_by_user( $dashboard_user->ID, $args );
	}

	return $proposals;
}

/**
 * Retrieves the list of reviews for the current user.
 */
function hrb_get_dashboard_reviews( $args = array() ) {
	$dashboard_user = wp_get_current_user();

	if ( ! isset( $args['filter_review_relation'] ) ) {

		// retrieves authored and received reviews (runs through a specific 'comments_clauses' filter)
		$defaults = array(
			'user_id' => $dashboard_user->ID,
			'meta_key' => '_review_recipient_AND_AUTHORED_',	// flag to be replaced in the hooked 'comments_clauses' filter
			'meta_value' => $dashboard_user->ID,
		);
		$args = wp_parse_args( $args, $defaults );
	}
	return hrb_get_user_reviews( $dashboard_user->ID, $args );
}

/**
 * Builds and retrieves the dashboard URL for a page/tab, considering the permalink settings.
 * Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function hrb_get_dashboard_url_for( $page = '', $tab = '' ) {
	global $wp_rewrite;

	$dashboard_permalink = hrb_get_dashboard_permalinks('dashboard');

	if ( $wp_rewrite->using_permalinks() ) {

		if ( $page ) {
			$dashboard_page_permalink = hrb_get_dashboard_permalinks( $page );
			$permalink = "$dashboard_permalink/$dashboard_page_permalink";
		} else {
			$permalink = "$dashboard_permalink";
		}

		$url = site_url( user_trailingslashit( $permalink ) );

	} else {

		if ( $page ) {
			$dashboard_page_permalink = hrb_get_dashboard_permalinks( $page );
			$url = add_query_arg( array( 'dashboard' => $dashboard_page_permalink ), site_url() );
		} else {
			$url = add_query_arg( array( 'dashboard' => $dashboard_permalink ), site_url() );
		}

	}

	if ( $tab ) {
		$url .= '#'.$tab;
	}
	return $url;
}


### Dashboard Sorting & Filtering

/**
 * Outputs a filter dropdown with all the unique sorting options.
 */
function hrb_output_sort_fdropdown( $base_link = '', $attributes = '' ) {

	$items = array(
		'default' => __( 'Newest', APP_TD ),
		'oldest' => __( 'Oldest', APP_TD ),
	);

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_generic_sort',
		'base_link' => $base_link,
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Outputs a filter dropdown with all the available page results options.
 */
function hrb_output_results_fdropdown( $base_link = '', $attributes = '' ) {

	$items = array(
		'10' => __( 'Show 10 results per page', APP_TD ),
		'20' => __( 'Show 20 results per page', APP_TD ),
		'30' => __( 'Show 30 results per page', APP_TD ),
		'-1' => __( 'Show All', APP_TD ),
	);

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_page_results',
		'active_prepend_label' => false,
		'query_var' => 'filter_posts_per_page',
		'base_link' => $base_link,
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Outputs a filter dropdow with all the unique statuses for a given list of posts.
 */
function hrb_output_statuses_fdropdown( $posts, $attributes = '' ) {
	$user_id = get_current_user_id();

	$active_statuses = hrb_get_projects_unique_statuses( $posts, $user_id );

	if ( ! empty( $posts->query_vars['post_type'] ) && APPTHEMES_ORDER_PTYPE == $posts->query_vars['post_type'] ) {
		$all_statuses = hrb_get_order_statuses_verbiages();
	} else {
		$all_statuses = hrb_get_project_statuses_verbiages();
	}

	$items = array();

	foreach( $all_statuses as $key => $verbiage ) {
		if ( in_array( $key, $active_statuses ) ) {
			$items[ $key ] = $verbiage;
		}
	}
	$items = array_merge( $items, array( 'default' => __( 'All', APP_TD ) ) );

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_posts_statuses',
		'label' => __( 'Filter By', APP_TD ),
		'query_var' => 'filter_status',
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Retrieves all the existing relations between a user and a project (employer/participant) given a WP_Query object.
 */
function hrb_get_assigned_projects_relation( $wp_query, $user_id = 0 ) {

	if ( empty( $wp_query->posts ) ) {
		return array();
	}

	$user_id = $user_id ? $user_id : get_current_user_id();

	$post_authors = array_unique( wp_list_pluck( $wp_query->posts, 'post_author' ) );

	$ocurrences = array_count_values( $post_authors );

	if ( isset( $ocurrences[ $user_id ] ) ) {

		$roles[] = array(
			'title' => __( 'Employer', APP_TD ),
			'value' => 'employer',
		);
	}

	// if the user has not authored any of the posts he's a participant
	if ( ! isset( $ocurrences[ $user_id ] ) || count( $post_authors ) != $ocurrences[ $user_id ] ) {
		$roles[] = array(
			'title' => __( 'Worker', APP_TD ),
			'value' => 'worker',
		);
	}

	return $roles;
}

/**
 * Outputs a filter dropdown with all the unique user relations with a given list of projects.
 */
function hrb_output_project_relation_fdropdown( $projects, $attributes = '' ) {

	$relations = hrb_get_assigned_projects_relation( $projects );

	$items = array();

	foreach( $relations as $relation ) {
		$items[ $relation['value' ] ] = $relation['title'];
	}
	$items = array_merge( $items, array( 'default' => __( 'All', APP_TD ) ) );

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_projects_relation',
		'label' => __( 'Filter By', APP_TD ),
		'query_var' => 'filter_relation',
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Outputs a filter dropdown with all the unique notification types for a given list of notifications.
 */
function hrb_output_notif_types_fdropdown( $notifications, $attributes = '' ) {

	$items = hrb_group_notification_types( $notifications );

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_notifications_types',
		'label' => __( 'Filter By', APP_TD ),
		'query_var' => 'filter_notify_type',
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Outputs a filter dropdown with all the unique statuses for a given list of proposals.
 */
function hrb_output_proposal_statuses_fdropdown( $proposals, $attributes = '' ) {

	$active_statuses = hrb_get_proposals_statuses( $proposals );
	$all_statuses = hrb_get_proposals_statuses_verbiages();

	$items = array();

	foreach( (array) $all_statuses as $key => $verbiage ) {
		if ( in_array( $key, $active_statuses ) ) {
			$items[ $key ] = $verbiage;
		}
	}
	$items = array_merge( $items, array( 'default' => __( 'All', APP_TD ) ) );

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_proposals_statuses',
		'label' => __( 'Filter By', APP_TD ),
		'query_var' => 'filter_status',
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Retrieves the user relation with a given list of reviews.
 */
function hrb_get_reviews_relation( $reviews, $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	$relations = array(
		'authored' => 0,
		'received' => 0,
	);

	foreach( $reviews as $review ) {

		if ( $user_id == $review->get_author_id() ) {
			$relations['authored']++;
		} else {
			$relations['received']++;
		}

	}
	return $relations;
}

/**
 * Outputs a filter dropdown with all the unique user relations for a given list of reviews.
 */
function hrb_output_review_relation_fdropdown( $reviews, $attributes = '' ) {

	$review_relations = hrb_get_reviews_relation( $reviews );

	$verbiage = array(
		'authored' => __( 'Given', APP_TD ),
		'received' => __( 'Received', APP_TD ),
	);

	$items = array();

	foreach( $review_relations as $relation => $count ) {
		if ( ! $count ) {
			continue;
		}
		$items[ $relation ] = $verbiage[ $relation ];
	}
	$items = array_merge( $items, array( 'all' => __( 'All', APP_TD ) ) );

	$def_dropdown_attributes = array(
		'class' => 'button dropdown secondary small',
	);
	$attributes = wp_parse_args( $attributes, $def_dropdown_attributes );

	$defaults = array(
		'id' => 'filter_reviews_relation',
		'label' => __( 'Filter By', APP_TD ),
		'query_var' => 'filter_review_relation',
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}


### Dashboard Project Actions

/**
 * Retrieves the context links for a given set of actions and project.
 *
 * @uses apply_filters() Calls 'hrb_dashboard_project_actions'
 *
 */
function _hrb_dashboard_project_actions_atts( $actions, $post ) {
	$dashboard_user = wp_get_current_user();

	$actions_attr = array();

	$atts = array(
		'edit' => array(
			'title' => __( 'Edit', APP_TD ),
			'href' => get_the_hrb_project_edit_url( $post->ID ),
		),
		'relist' => array(
			'title' => __( 'Relist', APP_TD ),
			'href' => get_the_hrb_project_relist_url( $post->ID ),
		),
		'reopen' => array(
			'title' => __( 'Re-Open', APP_TD ),
			'href' => get_the_hrb_project_action_url( $post->ID, 'reopen' ),
			'onclick' => 'return confirm("' . __( 'Are you sure you want to Re-Open this project?', APP_TD ) . '");',
		),
		'continue' => array(
			'title' => __( 'Continue', APP_TD ),
			'href' => get_the_hrb_project_create_url( $post->ID ),
		),
		'end' => array(
			'title' => __( 'End', APP_TD ),
			'href' => get_the_hrb_project_action_url( $post->ID, 'cancel' ),
			'onclick' => 'return confirm("' . __( 'Are you sure you want to end this project?', APP_TD ) . '");',
		),
		'cancel' => array(
			'title' => __( 'Cancel', APP_TD ),
			'href' => get_the_hrb_project_action_url( $post->ID, 'cancel' ),
			'onclick' => 'return confirm("' . __( 'Are you sure you want to cancel this project?', APP_TD ) . '");',
		),
		'trash' => array(
			'title' => __( 'Delete', APP_TD ),
			'href' => get_the_hrb_project_action_url( $post->ID, 'delete' ),
			'onclick' => 'return confirm("' . __( 'Are you sure you want to delete this project?', APP_TD ) . '");',
		),
		'archive' => array(
			'title' => __( 'Archive', APP_TD ),
			'href' => get_the_hrb_project_action_url( $post->ID, 'archive' ),
			'onclick' => 'return confirm("' . __( 'Are you sure you want to archive this project?', APP_TD ) . '");',
		),
		'apply' => array(
			'title' => __( 'Apply', APP_TD ),
			'href' => get_the_hrb_apply_to_url( $post->ID ),
		),
		'review' => array(
			'title' => __( 'Review', APP_TD ),
			'href' => hrb_get_workspace_url_by( 'post_id', $post->ID, $dashboard_user->ID ),
		),
		'view_order' => array(
			'title' => __( 'View Purchase', APP_TD ),
			'href' => hrb_get_dashboard_url_for( 'payments', 'purchases' ),
		),
	);

	if ( $dashboard_user->ID != $post->post_author ) {

		$proposals = hrb_get_proposals_by_user( $dashboard_user->ID, array( 'post_id' => $post->ID ) );
		if ( ! empty( $proposals['results'] ) ) {

			$proposal = reset( $proposals['results'] );

			$atts['view_proposal'] = array(
				'title' => __( 'View Proposal', APP_TD ),
				'href' => get_the_hrb_proposal_url( $proposal ),
			);
		}
	} else {

		$atts['view_proposal'] = array(
			'title' => __( 'View Proposals', APP_TD ),
			'href' => get_the_hrb_project_proposals_url( $post->ID ),
		);
	}

	// if project has order ignore all other actions
	if ( hrb_charge_listings() ) {

		if ( $order = appthemes_get_order_connected_to( $post->ID ) ) {

			if ( APPTHEMES_ORDER_PENDING == $order->get_status() ) {
				$order_actions_atts = get_the_hrb_order_actions( $order );

				$actions = array_keys( $order_actions_atts );
				$actions[] = 'edit';

				$atts = array_merge( $atts, $order_actions_atts );
			}

			$actions[] = 'view_order';

		}
	}

	foreach( $actions as $action ) {
		if ( empty( $atts[ $action ]['href'] ) ) {
			continue;
		}
		$atts[ $action ]['class'] = $action;
		$actions_attr[] = $atts[ $action ];
	}

	sort( $actions_attr );

	return apply_filters( 'hrb_dashboard_project_actions', $actions_attr, $post, $dashboard_user );
}

/**
 * Retrieves the active context actions for the current user and project.
 */
function get_the_hrb_dashb_project_actions( $post = '' ) {
	$post = $post ? $post : get_post( get_the_ID() );

	$dashboard_user = wp_get_current_user();

	$actions = array();

	if ( $dashboard_user->ID == $post->post_author ) {

		// user is the author

		if ( ! is_hrb_project_expired( $post->ID ) ) {
			$relist_reopen = 'reopen';
		} else {
			$relist_reopen = 'relist';
		}

		switch( $post->post_status ) {
			case 'publish':
				$actions = array( 'edit', 'end' );
				break;

			case 'pending':
				$actions = array( 'edit', 'cancel' );
				break;

			case 'draft':
				$actions = array( 'continue', 'trash' );
				break;

			case HRB_PROJECT_STATUS_CANCELED_TERMS:
				$actions = array( $relist_reopen, 'edit', 'cancel' );
				break;

			case HRB_PROJECT_STATUS_WORKING:
				$actions = array();
				break;

			case HRB_PROJECT_STATUS_CANCELED:
				$actions = array( $relist_reopen, 'archive' );
				break;

			case HRB_PROJECT_STATUS_EXPIRED:
				$actions = array( 'relist' );
				break;

			case HRB_PROJECT_STATUS_CLOSED_COMPLETED:
				$actions = array( 'archive' );
				break;

			case HRB_PROJECT_STATUS_CLOSED_INCOMPLETE:
				$actions = array( $relist_reopen, 'archive' );
				break;
		}

	} else {

		// user is assigned

		switch( $post->post_status ) {
			case HRB_PROJECT_STATUS_CLOSED_COMPLETED:
			case HRB_PROJECT_STATUS_CLOSED_INCOMPLETE:
				$actions = array( 'archive' );
				break;
		}

	}

	if ( appthemes_get_post_total_bids( $post->ID ) ) {
		array_unshift( $actions, 'view_proposal' );
	}


	### Remove actions based on the user capabilities

	if ( ! current_user_can( 'archive_post', $post->ID ) ) {
		$remove_actions = array( 'archive' );
		$actions = array_diff( $actions, $remove_actions );
	}

	if ( ! current_user_can( 'edit_post', $post->ID, 'edit' ) ) {
		$remove_actions = array( 'edit' );
		$actions = array_diff( $actions, $remove_actions );
	}

	if ( ! current_user_can( 'relist_post', $post->ID ) ) {
		$remove_actions = array( 'relist' );
		$actions = array_diff( $actions, $remove_actions );
	}

	if ( ! current_user_can( 'reopen_post', $post->ID ) ) {
		$remove_actions = array( 'reopen' );
		$actions = array_diff( $actions, $remove_actions );
	}

	return _hrb_dashboard_project_actions_atts( $actions, $post );
}

/**
 * Outputs the context dropdown with the active actions for the current user on a project.
 */
function the_hrb_dashboard_project_actions( $post = '', $text = '' ) {

	$actions = get_the_hrb_dashb_project_actions( $post );

	if ( empty( $actions ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Actions', APP_TD );
	}

	the_hrb_data_dropdown( $actions, array( 'data-dropdown' => "actions-{$post->ID}" ), $text );
}

/**
 * Retrieves the active context actions for the current user, project and proposal.
 */
function get_the_hrb_dashboard_project_work_actions( $post, $proposal = '' ) {
	$post = $post ? $post : get_post( get_the_ID() );

	$dashboard_user = wp_get_current_user();

	if ( $proposal ) {
		$users = array( $post->post_author, $proposal->get_user_id() );
	} else {
		$users = $dashboard_user->ID;
	}

	$recipient_id = $dashboard_user->ID == $post->post_author ? $proposal->get_user_id() : $post->post_author;

	$actions = array();

	$workspaces_ids = hrb_get_participants_workspace_for( $post->ID, $users );

	if ( ! empty( $workspaces_ids ) ) {

		foreach( $workspaces_ids as $workspace_id ) {

			$actions[ $workspace_id ] = array(
				'title' => __( 'View Workspace', APP_TD ),
				'href' => hrb_get_workspace_url( $workspace_id ),
			);

			if ( current_user_can( 'add_review', $post, $recipient_id, $workspace_id ) ) {
				$actions[ 'review_'.$workspace_id ] = array(
					'title' => __( 'Add Review', APP_TD ),
					'href' => hrb_get_workspace_url( $workspace_id ),
				);
			}
			$actions = apply_filters( 'hrb_dashboard_project_workspace_actions', $actions, $workspace_id, $proposal );
		}

	}
	return $actions;
}

function the_hrb_dashboard_project_work_actions( $post = '', $proposal ='', $text = '' ) {

	$actions = get_the_hrb_dashboard_project_work_actions( $post, $proposal );

	if ( empty( $actions ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Actions', APP_TD );
	}

	if ( ! empty( $proposal ) ) {
		$proposal_id = $proposal->get_id();
	} else {
		$proposal_id = 0;
	}

	the_hrb_data_dropdown( $actions, array( 'data-dropdown' => "work-actions-{$post->ID}-{$proposal_id}" ), $text );
}

### Dashboard Proposal Actions

/**
 * Retrieves the context links for a given set of actions and proposal.
 *
 * @uses apply_filters() Calls 'hrb_dashboard_proposal_actions'
 *
 */
function _hrb_dashboard_proposal_actions_atts( $actions, $proposal ) {
	$dashboard_user = wp_get_current_user();

	$actions_attr = array();

	$atts = array(
		'edit' => array(
			'title' => __( 'Edit', APP_TD ),
			'href' => get_the_hrb_proposal_edit_url( $proposal ),
		),
		'cancel' => array(
			'title' => __( 'Cancel', APP_TD ),
			'href' => get_the_hrb_proposal_action_url( $proposal->get_id(), 'cancel' ),
			'onclick' => 'return confirm("' . __( 'Are you sure you want to cancel this proposal? Proposal will be discarded.', APP_TD ) . '");',
		),
		'view_proposal' => array(
			'title' => __( 'View Proposal', APP_TD ),
			'href' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	foreach( $actions as $action ) {
		$actions_attr[] = $atts[ $action ];
	}

	$actions_attr = array_merge( $actions_attr, (array) get_the_hrb_dashboard_project_work_actions( $proposal->project, $proposal ) );

	return apply_filters( 'hrb_dashboard_proposal_actions', $actions_attr, $proposal, $dashboard_user );
}

/**
 * Retrieves the active context actions for the current user, project and proposal.
 */
function get_the_hrb_dashboard_proposal_actions( $proposal, $post = '' ) {
	$post = $post ? $post : get_post( get_the_ID() );

	$dashboard_user = wp_get_current_user();

	$actions = array();

	// user is the the author
	if ( $dashboard_user->ID != $post->post_author ) {

		switch( $post->post_status ) {
			case 'publish':
				$actions = array( 'edit', 'cancel' );
				break;
		}

	}

	if ( $proposal->is_approved() ) {
		$actions[] = 'view_proposal';
	}

	return _hrb_dashboard_proposal_actions_atts( $actions, $proposal );
}

/**
 * Outputs the context dropdown with the active actions for the current user on a proposal.
 */
function the_hrb_dashboard_proposal_actions( $proposal, $post = '', $text = '' ) {

	$actions = get_the_hrb_dashboard_proposal_actions( $proposal, $post );

	if ( empty( $actions ) ) {
		return false;
	}

	if ( empty( $text ) ) {
		$text = __( 'Actions', APP_TD );
	}

	the_hrb_data_dropdown( $actions, array( 'data-dropdown' => "actions-{$proposal->id}" ), $text );
}


function _hrb_dashboard_user_work_actions_atts( $actions, $workspace, $post = '', $recipient = '' ) {
	$dashboard_user = wp_get_current_user();

	$actions_attr = array();

	if ( ! current_user_can( 'edit_workspace', $workspace->ID ) ) {
		return $actions_attr;
	}

	if ( $recipient ) {
		$atts['review'] = array(
			'id' => "review-user-{$recipient->ID}",
			'title' => __( 'Review', APP_TD ),
			'class' => 'review-user',
			'href' =>  esc_url( get_the_hrb_review_user_url( $workspace->ID, $recipient ) ),
		);
	}

	if ( hrb_is_disputes_enabled() ) {

		$atts['dispute'] = array(
			'id' => "raise-dispute",
			'title' => __( 'Open Dispute', APP_TD ),
			'class' => 'raise-dispute',
			'href' =>  '#',
		);

	}

	foreach( $actions as $action ) {
		$actions_attr[] = $atts[ $action ];
	}

	return apply_filters( 'hrb_dashboard_user_work_actions', $actions_attr, $workspace, $dashboard_user );
}

function get_the_hrb_dashboard_user_work_actions( $workspace, $post = '', $recipient = '' ) {
	$post = $post ? $post : get_post( get_the_ID() );

	$actions = array();

	if ( $recipient && current_user_can( 'add_review', $post, $recipient->ID, $workspace->ID ) ) {
		$actions[] = 'review';
	}

	if ( current_user_can( 'open_dispute', $post, $workspace->ID ) ) {
		$actions[] = 'dispute';
	}

	return _hrb_dashboard_user_work_actions_atts( $actions, $workspace, $post, $recipient );
}

function the_hrb_dashboard_user_work_actions( $workspace, $post = '', $recipient = '', $text = '' ) {

	$actions = get_the_hrb_dashboard_user_work_actions( $workspace, $post, $recipient );

	if ( empty( $actions ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Actions', APP_TD );
	}

	the_hrb_data_dropdown( $actions, array( 'data-dropdown' => "actions-{$workspace->ID}-{$recipient->ID}" ), $text );
}