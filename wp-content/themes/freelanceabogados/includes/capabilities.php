<?php
/**
 * Hooks and functions that control and add/extend roles and capabilities.
 */

add_filter( 'map_meta_cap', '_hrb_bid_map_capabilities', 15, 4 );
add_filter( 'map_meta_cap', '_hrb_review_map_capabilities', 15, 4 );

add_filter( 'map_meta_cap', '_hrb_project_map_capabilities', 10, 4 );
add_filter( 'map_meta_cap', '_hrb_order_map_capabilities', 10, 4 );
add_filter( 'map_meta_cap', '_hrb_workspace_map_capabilities', 10, 4 );
add_filter( 'map_meta_cap', '_hrb_agreement_map_capabilities', 10, 4 );

add_filter( 'user_has_cap', '_hrb_has_proposals_cap', 10, 3 );
add_filter( 'user_has_cap', '_hrb_has_projects_cap', 10, 3 );


### Custom Roles & Capabilities

/**
 * Add custom roles: employer, freelancer and employer/freelancer.
 */
function hrb_roles( $role = '', $part = '' ) {

	$core_caps = array(
		'read' => true,
		'edit_posts' => false,
		'delete_posts' => false,
		'upload_media' => true,
		'embed_media' => true,
	);

	$roles = array(
		HRB_ROLE_FREELANCER => array(
			'label' => __( 'Freelancer', APP_TD ),
			'capabilities' => $core_caps,
		),
		HRB_ROLE_EMPLOYER => array(
			'label' => __( 'Employer', APP_TD ),
			'capabilities' => $core_caps,
		),
		HRB_ROLE_BOTH => array(
			'label' => __( 'Employer/Freelancer', APP_TD ),
			'capabilities' => $core_caps,
		),
	);

	if ( $role && isset( $roles[ $role ] ) ) {
		$roles = $roles[ $role ];
	}

	if ( $part && isset( $roles[ $part ] ) ) {
		$roles = $roles[ $part ];
	}

	return $roles;
}

/**
 * Retrieves custom capabilities for a role.
 */
function hrb_get_custom_caps( $role ) {

	$caps[HRB_ROLE_EMPLOYER] = array(
		'edit_projects',
		'delete_projects',
		'edit_published_projects',
	);

	$caps[HRB_ROLE_FREELANCER] = array(
		'edit_bids',
	);

	$caps[HRB_ROLE_BOTH] = array_merge( $caps[HRB_ROLE_EMPLOYER], $caps[HRB_ROLE_FREELANCER] );

	$caps['administrator'] = array_merge( $caps[HRB_ROLE_BOTH], array(
		'publish_projects',
		'edit_others_projects',
		'delete_published_projects',
		'delete_others_projects',
	) );

	if ( isset( $caps[$role] ) ) {
		return $caps[$role];
	}

	return array();
}

/**
 * Wrapper function used in hook to call the manage caps with 'add_cap' operation.
 */
function hrb_add_caps() {
	hrb_manage_caps('add_cap');
}

/**
 * Wrapper function used in hook to call the manage caps with 'remove_cap' operation.
 */
function hrb_remove_caps() {
	hrb_manage_caps('remove_cap');
}

/**
 * Assigns/removes custom capabilities to/from roles.
 */
function hrb_manage_caps( $operation ) {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles();
	}

	// add custom roles to global
	foreach( hrb_roles() as $role => $args ) {
		add_role( $role, $args['label'], $args['capabilities'] );
	}

	// add custom caps to admins
	foreach( $wp_roles->roles as $role => $details ) {
		foreach( hrb_get_custom_caps( $role ) as $cap ) {
			$wp_roles->$operation( $role, $cap );
		}
	}
}

/**
 * Assigns a role to a user.
 */
function hrb_assign_role_caps( $user_id, $role ) {
	wp_update_user( array( 'ID' => $user_id, 'role' => $role ) );
}


### Meta Capabilities

/**
 * Meta cababilities for projects.
 */
function _hrb_project_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		// native meta caps
		case 'delete_post':
		case 'edit_post':

			// keep the existing $caps untouched to use the base meta cap rules

			$post = get_post( $args[0] );

			// make sure the meta caps are only applied to custom post types and non admins
			if ( empty( $post ) || HRB_PROJECTS_PTYPE != $post->post_type || is_super_admin() || is_admin() ) {
				return $caps;
			}

			if ( 'edit_post' == $cap ) {

				// users can only edit posts with these statuses...
				if ( ! in_array( $post->post_status, array( 'publish', 'draft' ) ) ) {
					$caps[] = 'do_not_allow';
					break;
				}

			}

			break;

		//custom
		case 'cancel_post':
		case 'archive_post':
		case 'favorite_post':

			// give permission by default
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			// make sure the meta caps are only applied to custom post types and non admins
			if ( empty( $post ) ||  HRB_PROJECTS_PTYPE != $post->post_type || is_super_admin() || is_admin() ) {
				return $caps;
			}

			// users can only favorite published (active) posts
			if ( 'publish' != $post->post_status ) {
				$caps[] = 'do_not_allow';
				break;
			}

			break;

	}

	switch( $cap ) {

		case 'relist_post':
		case 'reopen_post':
			$post = get_post( $args[0] );

			if ( ! in_array( $post->post_status, array( HRB_PROJECT_STATUS_EXPIRED, HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CANCELED_TERMS, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE ) ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			$expired = is_hrb_project_expired( $post->ID );

		case 'cancel_post':
		case 'delete_post':
		case 'archive_post':
			$post = get_post( $args[0] );

			$caps = array( 'exist' );

			// User is not the owner of the post
			if ( $post->post_author != $user_id ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// don't allow if there are opened disputes on any workspace belonging to this project
			// don't allow if dispute opening period is active
			if ( hrb_is_disputes_enabled() ) {

				$workspaces = hrb_p2p_get_post_workspaces( $post->ID );

				$disputes = '';

				foreach( $workspaces->posts as $workspace ) {

					$disputes = appthemes_get_disputes_for( $workspace->ID );
					if ( ! empty( $disputes ) ) {
						break;
					}

					if ( hrb_is_dispute_period_active( $workspace->ID ) ) {
						$disputes = 1;
						break;
					}

				}

				if ( ! empty( $disputes ) ) {
					$caps[] = 'do_not_allow';
					break;
				}

			}

			switch( $cap ) {

				case 'relist_post':
					if ( ! $expired ) {
						$caps[] = 'do_not_allow';
					}
					break;

				case 'reopen_post':
					if ( $expired ) {
						$caps[] = 'do_not_allow';
					}
					break;

				case 'cancel_post':
					if ( 'pending' != $post->post_status ) {
						$caps[] = 'do_not_allow';
					}
					break;

				case 'delete_post':
					if ( 'draft' != $post->post_status ) {
						$caps[] = 'do_not_allow';
					}
					break;

				case 'archive_post':

					// check if not already archived
					if ( hrb_is_project_archived( $post->ID, $user_id ) ) {
						$caps[] = 'do_not_allow';
						break;
					}

					// check if work is considered completed
					if ( ! in_array( $post->post_status, hrb_get_project_work_ended_statuses() ) ) {
						$caps[] = 'do_not_allow';
					}
					break;
			}
			break;

		case 'favorite_post':
			$post = get_post( $args[0] );

			// visitors cannot favorite posts
			if ( ! is_user_logged_in() ) {
				$caps[] = 'do_not_allow';
			}
			break;

	}
	return $caps;
}

/**
 * Meta cababilities for project work.
 */
function _hrb_workspace_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		// users can't edit work if they are not participants on the project
		case 'edit_workspace':
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			$participant = hrb_p2p_get_participant( $post->ID, $user_id );

			// only post participants can edit work
			if ( ! $participant || ( 'employer' != $participant->type && 'worker' != $participant->type ) ) {
				$caps[] = 'do_not_allow';
			}
			break;

		case 'review_workspace':
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			$participant = hrb_p2p_get_participant( $post->ID, $user_id );

			if ( ! current_user_can('manage_options') && 'reviewer' != $participant->type ) {
				$caps[] = 'do_not_allow';
			}
			break;
	}
	return $caps;
}

/**
 * Meta cababilities for project agreement.
 */
function _hrb_agreement_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		case 'select_proposal':
		case 'view_agreement':
		case 'edit_agreement':
			$caps = array( 'exist' );

			if ( ! ( $proposal = hrb_get_proposal( $args[0] ) ) ) {
				$caps[] = 'do_not_allow';
				break;
			}
			break;

	}

	switch( $cap ) {

		case 'select_proposal':

			// don't allow if the project already has an active selected candidate
			if ( is_hrb_project_proposal_selectable( $proposal->get_post_ID() ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// don't allow if proposal is already selected
			if ( $proposal->selected ) {
				$caps[] = 'do_not_allow';
				break;
			}
			break;

		case 'view_agreement':

			// users can't edit agreement if they are not the proposal or project authors
			if ( $user_id != $proposal->get_user_id() && $user_id != $proposal->project->post_author ) {
				$caps[] = 'do_not_allow';
			}
			break;

		case 'edit_agreement':

			// check if the project status allows editing the agreement
			if ( ! is_hrb_project_open_for_agreement( $proposal->get_post_ID() ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// users can't edit agreement if they are not the proposal or project authors
			if ( $user_id != $proposal->get_user_id() && $user_id != $proposal->project->post_author ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// agreement cant be edited if the proposal author is already a participant in the project
			$workspaces = hrb_get_participants_workspace_for( $proposal->get_post_ID(), $proposal->get_user_id() );
			if ( ! empty( $workspaces ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			$user = get_user_by( 'id', $user_id );

			// users need to specify a public email address before editing an agreement
			if ( empty( $user->hrb_email ) ) {
				$caps[] = 'do_not_allow';
			}
			break;

		case 'edit_agreement_terms':
			$caps = array( 'exist' );

			if ( ! $proposal = hrb_get_proposal( $args[0] ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// check if the project status allows editing the agreement
			if ( ! is_hrb_project_open_for_agreement( $proposal->get_post_ID() ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// users can't edit agreement if they are not the proposal or project authors
			if ( $user_id != $proposal->get_user_id() && $user_id != $proposal->project->post_author ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// terms can only be edited while there is not a decision from the other party
			if ( $user_id == $proposal->project->post_author ) {

				if ( $proposal->_hrb_candidate_decision && HRB_TERMS_ACCEPT == $proposal->_hrb_candidate_decision ) {
					$caps[] = 'do_not_allow';
					break;
				}

			} else {

				 if ( $proposal->_hrb_employer_decision && HRB_TERMS_ACCEPT == $proposal->_hrb_employer_decision ) {
					$caps[] = 'do_not_allow';
					break;
				 }

			}

			// agreement cant be edited if the proposal author is already a participant in the project
			$workspaces = hrb_get_participants_workspace_for( $proposal->get_post_ID(), $proposal->get_user_id() );
			if ( ! empty( $workspaces ) ) {
				$caps[] = 'do_not_allow';
			}
			break;

	}
	return $caps;
}

/**
 * Meta cababilities for proposals.
 */
function _hrb_bid_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		case 'view_bid':
			global $hrb_options;

			$caps = array( 'exist' );

			if ( $hrb_options->proposals_quotes_hide ) {

				$bid = appthemes_get_bid( $args[0] );

				$post = get_post( $bid->get_post_ID() );

				// bids should be visible to the bid author or to the bidded project
				if ( $post->post_author != $user_id && $bid->user_id != $user_id ) {
					$caps[] = 'do_not_allow';
					break;
				}

			}
			break;

		case 'add_bid':
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			// Users can't apply to authored posts
			if ( $post->post_author == $user_id ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Users can't send proposals if post is not published
				if ( is_hrb_project_expired( $post->ID ) ) {
				$caps[] = 'do_not_allow';
			}
			break;

		case 'edit_bid':
			$caps = array( 'exist' );

			$bid = appthemes_get_bid( $args[0] );

			// users can only edit their own proposal
			if ( $bid->get_user_id() != $user_id ) {
				$caps[] = 'do_not_allow';
				break;
			}

			$proposal = hrb_get_proposal( $bid );

			// users can't edit selected proposals
			if ( $proposal->selected ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// Users can't edit proposals if the proposal is not approved
			if ( ! $bid->is_approved() ) {
				$caps[] = 'do_not_allow';
			}

			$post = get_post( $bid->get_post_ID() );

			// Users can't edit proposals if post is not published or is expired
			if ( 'publish' != $post->post_status || is_hrb_project_expired( $post->ID ) ) {
				$caps[] = 'do_not_allow';
			}
			break;
	}
	return $caps;
}

/**
 * Meta cababilities for reviews.
 */
function _hrb_review_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		case 'add_review':
			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			// check if current user can review a participating user
			if ( isset( $args[1] ) ) {
				$recipient_id = $args[1];

				// users can't review themselves
				if ( $recipient_id == $user_id ) {
					$caps[] = 'do_not_allow';
					break;
				}

				$recipient = get_user_by( 'id', $recipient_id );

				// users can't review a user that is already reviewed
				if ( hrb_is_user_reviewed_on_project( $post->ID, $recipient->ID, $user_id ) ) {
					$caps[] = 'do_not_allow';
					break;
				}
			}

			if ( isset( $args[2] ) ) {
				$workspace_id = $args[2];
			} else {
				$workspace_id = 0;
			}

			if ( $workspace_id && ! in_array( get_post_status( $workspace_id ), hrb_get_project_work_ended_statuses() ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// users can't review a user if they already reviewed it or if the project is not open for reviews
			if ( ! hrb_is_project_reviweable_by( $user_id, $post->ID, $workspace_id ) ) {
				$caps[] = 'do_not_allow';
			}

			// if an escrow order (with no funds) attached to a workspace was canceled do not allow since no work was done
			if ( $workspace_id && hrb_is_escrow_enabled() ) {
				$order = appthemes_get_order_connected_to( $workspace_id );
				if ( $order && $order->is_escrow() && APPTHEMES_ORDER_FAILED == $order->get_status() ) {
					$caps[] = 'do_not_allow';
				}
			}

			break;
	}
	return $caps;
}

/**
 * Meta cababilities for orders.
 *
 * @since 1.3
 */
function _hrb_order_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		// native meta caps
		case 'cancel_order':

			$caps = array( 'exist' );

			$post = get_post( $args[0] );

			// make sure the meta caps are only applied to custom post types and non admins
			if ( empty( $post ) || APPTHEMES_ORDER_PTYPE != $post->post_type || is_super_admin() || is_admin() ) {
				return $caps;
			}

			// users can only edit pending orders
			if ( ! in_array( $post->post_status, array( APPTHEMES_ORDER_PENDING, APPTHEMES_ORDER_PAID ) ) ) {
				$caps[] = 'do_not_allow';
				break;
			}
			break;
	}
	return $caps;
}


### Helper Cap Functions

/**
 * Limits posting projects based on the user role or 'share role capabilities' setting.
 *
 * Only 'employers' can post projects - 'edit_projects' cap.
 *
 * Exceptionally, if the the 'Share Roles Capabilities' option is checked 'freelancers' can post projects with the temp 'can_edit_projects' cap.
 *
 */
function _hrb_has_projects_cap( $allcaps, $cap, $args ) {
	global $hrb_options;

	// bail out if not checking for the 'edit_projects' cap or the temporary cap 'can_edit_projects
	if ( ! in_array( $args[0], array( 'edit_projects', 'can_edit_projects' ) ) ) {
		return $allcaps;
	}

	// use a dummy cap to check if the user has native cap to post projects
	if ( $args[0] == 'can_edit_projects' && ! isset( $allcaps['edit_projects'] ) ) {
		return $allcaps;
	 }

	// block posting projects from non employers unless explicitly set in admin setting
	else if ( ! isset( $allcaps['edit_projects'] ) && $hrb_options->share_roles_caps ) {
		$allcaps['edit_projects'] = true;
	}

	return $allcaps;
}

/**
 * Limits posting proposals based on the user role or 'share role capabilities' setting.
 *
 * Only 'freelancers' can apply to projects - 'edit_bids' cap.
 *
 * Exceptionally, if the the 'Share Roles Capabilities' option is checked 'employers' can apply to projects with the temp 'can_edit_bids' cap.
 *
 */
function _hrb_has_proposals_cap( $allcaps, $cap, $args ) {
	global $hrb_options;

	// bail out if we're not asking for a bid (proposal) cap or the temporary cap 'can_edit_bids
	if ( ! in_array( $args[0], array( 'add_bid', 'edit_bid', 'edit_bids', 'can_edit_bids' ) ) ) {
		return $allcaps;
	}

	// use a dummy cap to check if the user has native cap to post proposals
	 if ( $args[0] == 'can_edit_bids' && ! isset( $allcaps['edit_bids'] ) ) {
		return $allcaps;
	 }

	// block posting proposals from non freelancers unless explicitly set in admin setting
	else if ( ( ! isset( $allcaps['edit_bids'] ) && $hrb_options->share_roles_caps ) ) {
		$allcaps['edit_bids'] = true;
	}

	return $allcaps;
}
