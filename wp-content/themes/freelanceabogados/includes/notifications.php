<?php
/**
 * Notifications (dashboard/email) related functions.
 *
 * Some Notes:
 * . Emails are sent in HTML with content type header: Content-Type: text/html
 *
 */

add_action( 'appthemes_bid_approved', 'hrb_proposal_notify_parties', 10 );
add_action( 'appthemes_new_user_review', 'hrb_new_user_review_notify_parties', 10, 2 );

add_action( 'hrb_new_project', 'hrb_new_project_notify_admin', 15, 2 );
add_action( 'hrb_new_project', 'hrb_new_project_notify_author', 15, 2 );
add_action( 'hrb_new_candidate', 'hrb_new_candidate_notify', 10, 4 );

add_action( 'hrb_proposal_status_cancel', 'hrb_proposal_canceled_notify_parties' );

add_action( 'hrb_no_agreement', 'hrb_no_agreement_notify_parties', 10, 3 );
add_action( 'hrb_agreement_accepted', 'hrb_agreement_notify_parties', 10, 3 );
add_action( 'hrb_agreement_canceled', 'hrb_agreement_canceled_notify_parties', 10, 2 );

add_action( 'hrb_updated_development_terms', 'hrb_dev_terms_modified_notify_parties', 10, 2 );
add_action( 'hrb_updated_project_terms', 'hrb_terms_modified_notify_parties', 10, 3 );

add_action( 'hrb_updated_user_credits', 'hrb_credits_added_notify_user', 10, 3 );

add_action( 'hrb_transition_participant_status', 'hrb_work_status_notify_parties', 10, 4 );

add_action( 'transition_post_status', 'hrb_project_status_work_notify_parties', 10, 3 );
add_action( 'transition_post_status', 'hrb_maybe_notify_parties', 20, 3 );

add_action( 'hrb_new_plan_order', 'hrb_send_order_receipt' );

add_action( 'appthemes_transaction_completed', 'hrb_send_order_receipt_confirmation' );

add_action( 'appthemes_transaction_failed', 'hrb_order_canceled_notify_admin' );
add_action( 'appthemes_transaction_failed', 'hrb_order_canceled_notify_author' );

add_action( 'appthemes_escrow_completed', 'hrb_escrow_paid_notify', 15 );
add_action( 'appthemes_escrow_completed', 'hrb_escrow_paid_notify_admin', 15 );

add_action( 'appthemes_escrow_refunded', 'hrb_escrow_refund_notify', 15 );
add_action( 'appthemes_escrow_refunded', 'hrb_escrow_refund_notify_admin', 15 );

add_action( 'appthemes_transaction_paid', 'hrb_escrow_funds_available_notify', 15 );

add_action( 'waiting_funds_workspace', 'hrb_escrow_waiting_funds_notify', 15, 2 );

add_action( 'appthemes_escrow_refund_failed', 'hrb_refund_failed_notify_admin', 15, 2 );
add_action( 'appthemes_escrow_complete_failed', 'hrb_payment_failed_notify_admin', 15, 2 );

add_filter( 'wp_mail', 'hrb_append_signature' );


### Hooks Callbacks

/**
 * Send notifications on post status changes.
 */
function hrb_maybe_notify_parties( $new_status, $old_status, $post ) {

	if ( HRB_PROJECTS_PTYPE != $post->post_type ) {
		return;
	}

	// notify users when pending projects are published
	elseif ( 'publish' == $new_status && 'pending' == $old_status ) {
		hrb_project_approval_notify( $post );
	}

	// notify authors, candidates and participantes when projects expire or are canceled
	elseif ( in_array( $new_status, array( HRB_PROJECT_STATUS_EXPIRED, HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CANCELED_TERMS, 'publish' ) )  &&
			 in_array( $old_status, array( 'publish', HRB_PROJECT_STATUS_WORKING, HRB_PROJECT_STATUS_TERMS, HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE ) ) ) {
		hrb_status_change_notify_parties( $post, $new_status, $old_status );
	}

}


# New Projects

/**
 * Notify admins on new posted projects.
 * Email Only
 */
function hrb_new_project_notify_admin( $post_id, $order = '' ) {
	global $hrb_options;

	if ( ! $hrb_options->notify_new_projects ) {
		return;
	}

	$post = get_post( $post_id );

	switch( $post->post_status) {
		case 'pending':
			$subject = sprintf( __( "[%s] New Project waiting Moderation: %s", APP_TD ), get_bloginfo( 'name' ), $post->post_title );
			$content = html( 'p', sprintf(
				__( 'A new project is waiting moderation: %s', APP_TD ),
				html_link( get_permalink( $post ), $post->post_title ) ) );

			$content .= html( 'p', html_link(
				admin_url( 'edit.php?post_status=pending&post_type='.HRB_PROJECTS_PTYPE ),
				__( 'Review pending projects', APP_TD ) ) );
			break;

		default:
			if ( ! empty( $order ) && $order->get_total() > 0 ) {
				$subject = sprintf( __( "[%s] New Project Waiting Payment: %s", APP_TD ), get_bloginfo( 'name' ), $post->post_title );
				$content = _hrb_order_summary_email_body( $order, $to_admin = true );
			} else {
				$subject = sprintf( __( "[%s] New Project Published: %s", APP_TD ), get_bloginfo( 'name' ), $post->post_title );

				$content = html( 'p', sprintf(
					__( 'A new project was published: %s', APP_TD ),
					html_link( get_permalink( $post ), $post->post_title ) ) );

				$content .= html( 'p', html_link(
					admin_url( 'edit.php?post_status=publish&post_type='.HRB_PROJECTS_PTYPE ),
					__( 'View all published projects', APP_TD ) ) );
			}
			break;
	}

	appthemes_send_email( get_option( 'admin_email' ), $subject, $content );
}

/**
 * Notify author on his new posted project.
 * Notification + Email
 */
function hrb_new_project_notify_author( $post_id, $order = '' ) {

	$post = get_post( $post_id );

	$recipient = get_user_by( 'id', $post->post_author );

	$project_link = html_link( get_permalink( $post ), $post->post_title );

	$content = sprintf(
		__( 'Hello %2$s, %1$s
		your project %3$s was submitted with success.', APP_TD ), "\r\n\r\n", $recipient->display_name, $project_link
	);

	if ( ! empty( $order ) && $order->get_total() > 0 ) {

		$subject = sprintf( __( "Your project - %s - was submitted and is waiting payment", APP_TD ), $project_link );
		$content .= _hrb_order_summary_email_body( $order );
		$content .= "\r\n\r\n" . __( "The Order is waiting payment. You'll be notified once the payment clears.", APP_TD );

	} else {

		if ( 'pending' == $post->post_status ) {
			$subject = sprintf( __( "Your project - %s - was submitted and is waiting moderation", APP_TD ), $project_link );
			$content .= "\r\n\r\n" . __( "It's waiting moderation. You'll be notified once it is approved.", APP_TD );
		} else {
			$subject = sprintf( __( "Your project - %s - was submitted and is now live!", APP_TD ), $project_link );
			$content .= "\r\n\r\n" . __( "It is now live and publicly visible on our site.", APP_TD );
		}
	}

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject ),
			'project_id' => $post->ID,
			'action' => get_permalink( $post ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify project authors on project approval.
 * Notification + Email
 */
function hrb_project_approval_notify( $post ) {
	$recipient = get_user_by( 'id', $post->post_author );

	$project_link = html_link( get_permalink( $post ), $post->post_title );

	$subject_message = sprintf( __( "Your project - %s - has been approved!", APP_TD ), $project_link );

	$content = sprintf(
		__( 'Hello %2$s, %1$s
		your project %3$s, has been approved and is now live!', APP_TD ), "\r\n\r\n", $recipient->display_name, $project_link
	);

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $post->ID,
			'action' => get_permalink( $post ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify authors about expired projects.
 * Notification + Email
 */
function hrb_project_expired_notify( $post ) {
	$recipient = get_user_by( 'id', $post->post_author );

	$project_link = html_link( get_permalink( $post ), $post->post_title );
	$renew_link = html_link( get_the_hrb_project_relist_url( $post->ID ), __( 'Relist Project', APP_TD ) );

	$subject_message = sprintf( __( "Your project - %s - has expired", APP_TD ), $project_link );

	$content = sprintf(
		__( 'Hello %2$s, %1$s
		your project %3$s, has expired (it is not visible to the public anymore).%1$s %4$s', APP_TD ),
		"\r\n\r\n", $recipient->display_name, $project_link, $renew_link
	);

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $post->ID,
			'action' => get_permalink( $post ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify authors, candidates and participants on important project status changes.
 * Notification + Email
 */
function hrb_status_change_notify_parties( $post, $status, $old_status = '' ) {

	$recipient = get_user_by( 'id', $post->post_author );

	$project_link = html_link( get_permalink( $post ), $post->post_title );

	$notify_candidates = false;
	$notify_participants = false;

	switch( $status) {
		case HRB_PROJECT_STATUS_CANCELED:
			$status_desc = __( 'was canceled', APP_TD );
			$notify_candidates = $notify_participants = true;
			break;
		case HRB_PROJECT_STATUS_CANCELED_TERMS:
			$status_desc = __( 'is pending new candidate selection', APP_TD );
			break;
		case HRB_PROJECT_STATUS_EXPIRED:
			$status_desc = __( 'has expired', APP_TD );
			break;
		case 'publish':
			if ( 'publish' != $old_status ) {
				$status_desc = __( 'was reopened', APP_TD );
				$notify_candidates = true;
			} else {
				$status_desc = __( 'was updated', APP_TD );
				$notify_candidates = $notify_participants = true;
			}
			break;
		default:
			$status_desc = $status;
			$notify_candidates = $notify_participants = true;
	}

	if ( HRB_PROJECT_STATUS_EXPIRED == $status ) {

		// notify author using a separate function
		hrb_project_expired_notify( $post );

	} else {

		$subject_message = sprintf( __( 'Your project - %1$s - %2$s', APP_TD ), $project_link, $status_desc );

		$content = sprintf(
			__( 'Hello %2$s, %1$s
			your project %3$s, %4$s.', APP_TD ),
			"\r\n\r\n", $recipient->display_name, $project_link, $status_desc
		);

		$participant = array(
			'recipient' => $recipient->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $post->ID,
				'action' => get_permalink( $post ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

	### notify participants/candidates/proposal authors

	$project = hrb_get_project( $post->ID );

	$users = array();

	// check for participants or candidates and notify them on a per-status basis

	if ( $notify_participants ) {
		$participants = hrb_get_post_participants( $project->ID );

		if ( $participants ) {

			$users = $participants->results;
			if ( ! $users ) {
				$users = hrb_p2p_get_post_candidates( $project->ID );

				if ( ! empty( $users ) ) {
					$users = $users->results;
				}
			}

		}
	}

	// check for candidates

	if ( $notify_candidates ) {

		$proposals = hrb_get_proposals_by_post( $post->ID );

		foreach( $proposals['results'] as $proposal ) {
			$users[] = get_user_by( 'id', $proposal->get_user_id() );
		}

	}

	foreach( $users as $worker ) {

		$subject_message = sprintf( __( 'Project - %1$s - owned by \'%2$s\' %3$s', APP_TD ), $project_link, $recipient->display_name, $status_desc );

		$content = sprintf(
			__( 'Hello %2$s,%1$s
			Project %3$s, owned by \'%4$s\', %5$s.', APP_TD ),
			"\r\n\r\n", $worker->display_name, $project_link, $recipient->display_name, $status_desc
		);

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project->ID,
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}
}


# Orders

/**
 * Sends the Order receipt to the author by email only.
 * Email Only
 */
function hrb_send_order_receipt( $order ) {

	$recipient = get_user_by( 'id', $order->get_author() );

	$content = '';
	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );
	$content .= html( 'p', __( 'Receipt for your purchase:', APP_TD ) );
	$content .= _hrb_order_summary_email_body( $order );

	if ( $order->get_total() > 0 ) {
		$content .= html( 'p', __( 'Note: We will notify you after the payment clears.', APP_TD ) );
	}

	$subject = sprintf( __( '[%s] Receipt for Order #%d', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	appthemes_send_email( $recipient->user_email, $subject, $content );
}

/**
 * Sends the Order confirmation receipt to the author by email only.
 * Email Only
 */
function hrb_send_order_receipt_confirmation( $order ) {

	$recipient = get_user_by( 'id', $order->get_author() );

	$content = '';
	$content .= html( 'p', sprintf( __( 'Hello %s,', APP_TD ), $recipient->display_name ) );
	if ( $order->is_escrow() ) {
		$workspace = $order->get_item();
		$workspace_link = html_link( hrb_get_workspace_url( $workspace['post_id'] ), $workspace['post']->post_title );

		$content .= html( 'p', sprintf( __( 'This email confirms that funds held in escrow for work on %s were released:', APP_TD ), $workspace_link ) );
	} else {
		$content .= html( 'p', __( 'This email confirms that you have purchased the following:', APP_TD ) );
	}
	$content .= _hrb_order_summary_email_body( $order );

	$subject = sprintf( __( '[%s] Payment Confirmation for Order #%d', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	appthemes_send_email( $recipient->user_email, $subject, $content );
}

/**
 * Notify admins on canceled Orders.
 * Email Only
 */
function hrb_order_canceled_notify_admin( $order ) {

	$order_link = html_link( hrb_get_order_admin_url( $order->get_id() ), '#'.$order->get_id() );
	$subject = sprintf( __( '[%s] Order #%d was Canceled', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	$content = sprintf(
		__( "Hello,%s
		Order %s, has just been canceled.", APP_TD ), "\r\n\r\n", $order_link
	);

	$content .= _hrb_order_summary_email_body( $order );

	appthemes_send_email( get_option( 'admin_email' ), $subject, wpautop( $content ) );
}

/**
 * Notify owner on canceled Orders.
 * Notification + Email
 */
function hrb_order_canceled_notify_author( $order ) {

	$order_link = html_link( hrb_get_dashboard_url_for('payments'), '#'.$order->get_id() );
	$recipient = get_user_by( 'id', $order->get_author() );

	$subject = sprintf( __( 'Your Order %s was Canceled', APP_TD ), $order_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		your Order %3$s, as just been canceled.', APP_TD ), "\r\n\r\n", $recipient->display_name, $order_link
	);

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}


# Work Notifications

/**
 * Notify participants on project status changes.
 * Notification + Email
 *
 * @uses apply_filters() Calls 'hrb_project_status_change_notify_content'
 */
function hrb_project_status_work_notify_parties( $new_status, $old_status, $post ) {

	if ( HRB_PROJECTS_PTYPE != $post->post_type || HRB_PROJECT_STATUS_WORKING != $old_status ) {
		return;
	}

	if ( ! in_array( $new_status, array( HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CLOSED_COMPLETED, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE ) ) ) {
		return;
	}

	$employer = get_user_by( 'id', $post->post_author );

	$project_link = html_link( get_permalink( $post->ID ), $post->post_title );

	$status = hrb_get_project_statuses_verbiages( $new_status );

	### notify participants

	$participants = hrb_p2p_get_workspace_participants( $post->ID );
	if ( ! $participants ) {
		return;
	}

	$escrow_payment_text = '';

	if ( hrb_is_escrow_enabled() && in_array( $new_status, array( HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE ) ) ) {
		$escrow_payment_text = __( 'Since the project was not considered complete the project owner may be entitled to a full refund.', APP_TD );
	}

	foreach( $participants->results as $worker ) {
		// @todo use two vars for the url and the link for performance
		$workspace_link = html_link( hrb_get_workspace_url( $worker->p2p_from ), __( 'workspace', APP_TD ) );

		$subject_message = sprintf( __( "Project - %s - owned by '%s' was updated to '%s'", APP_TD ), $project_link, $employer->display_name, $status );

		$content = sprintf(
			__( 'Hello %2$s,%1$s
			User \'%3$s\' has just updated %4$s status to \'%5$s\'.%1$s' .
			'You can now review \'%3$s\' on the project %6$s.%1$s', APP_TD ), "\r\n\r\n", $worker->display_name, $employer->display_name, $project_link, $status, $workspace_link
		);

		if ( $escrow_payment_text ) {
			$content .= $escrow_payment_text . "\r\n\r\n";
		}

		$content = apply_filters( 'hrb_project_status_change_notify_content', $content, $new_status, $post, $worker->p2p_from, $worker );

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $post->ID,
				'action' => hrb_get_workspace_url( $worker->p2p_from ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

	### notify employer

	$escrow_payment_text = '';

	if ( hrb_is_escrow_enabled() && in_array( $new_status, array( HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE ) ) ) {
		$escrow_payment_text = sprintf( __( 'Since the project was not considered complete you %s entitled to a full refund.', APP_TD ), ( hrb_is_escrow_enabled() ? __( 'may be', APP_TD ) : __( 'are', APP_TD ) ) );
	}

	$workspaces = hrb_get_cached_workspaces_for( $post->ID );

	// check for multiple workspaces
	if ( count( $workspaces ) > 1 ) {

		$workspaces = hrb_p2p_get_post_workspaces( $post->ID, array( 'connected_query' => array( 'orderby' => 'ID' ) ) );
		$workspaces = $workspaces->posts;
		$workspace_id = $workspaces[0]->ID;

		$action_url = hrb_get_dashboard_url_for('projects');
		$workspace_link = html_link( $action_url, __( 'Dashboard', APP_TD ) );
	} else {
		$workspace_id = $workspaces[0];
		$action_url = hrb_get_workspace_url( $workspaces[0] );
		$workspace_link = html_link( $action_url, __( 'workspace', APP_TD ) );
	}

	$subject_message = sprintf( __( "The status for - %s - was updated to '%s'", APP_TD ), $project_link, $status );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		You\'ve updated %3$s status to \'%4$s\'.%1$s
		All participants can now review each other work on the project %5$s.%1$s', APP_TD ), "\r\n\r\n", $employer->display_name, $project_link, $status, $workspace_link
	);

	if ( $escrow_payment_text ) {
		$content .= $escrow_payment_text . "\r\n\r\n";
	}

	$content = apply_filters( 'hrb_project_status_change_notify_content', $content, $new_status, $post, $workspace_id, $employer );

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $post->ID,
			'action' => $action_url,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify participants on work status changes.
 * Notification + Email
 */
function hrb_work_status_notify_parties( $new_status, $old_status, $workspace_id, $participant_id ) {

	$project = hrb_p2p_get_workspace_post( $workspace_id );

	$worker = get_user_by( 'id', $participant_id );
	$employer = get_user_by( 'id', $project->post_author );

	$project_link = html_link( get_permalink( $project->ID ), $project->post_title );
	$workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), __( 'workspace', APP_TD ) );

	$work_status_link = html_link( hrb_get_workspace_url( $workspace_id ), __( 'work status', APP_TD ) );

	$status = hrb_get_participants_statuses_verbiages( $new_status );

	if ( in_array( $new_status, hrb_get_work_ended_statuses() ) ) {
		$work_ended = true;
	} else {
		$work_ended = false;
	}

	### notify employer

	$subject_message = sprintf( __( 'User %1$s, working on - %2$s - has updated his %3$s to \'%4$s\'', APP_TD ), $worker->display_name, $project_link, $work_status_link, $status );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		User \'%3$s\' has just updated his work status on %4$s, to \'%5$s\'.', APP_TD ),
		"\r\n\r\n", $employer->display_name, $worker->display_name, $project_link, $status
	);

	if ( $work_ended ) {
		$content .= "\r\n\r\n" . sprintf(
				__( "Please analyse his work and end the project accordingly. "
				. "You'll then be able to add your final review for user '%s'.", APP_TD ),
				$worker->display_name
		);
	}

	$content .= "\r\n\r\n" . sprintf( __( "Visit the project %s.", APP_TD ), $workspace_link );

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' =>  hrb_get_workspace_url( $workspace_id ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );


	### notify participant

	$subject_message = sprintf( __( 'Your work status on - %1$s - was updated to \'%2$s\'', APP_TD ), $project_link, $status );

	$content = "\r\n\r\n" . sprintf(
		__( 'Hello %1$s,%2$s
		Your work status on %3$s was updated to \'%4$s\'.', APP_TD ),
		$worker->display_name, "\r\n\r\n", $project_link, $status
	);

	if ( $work_ended ) {
		$content .= "\r\n\r\n" . sprintf(
				__( "The project owner will now analyse your work and end the project accordingly. "
				. "You'll then be able to add your final review for '%s'.", APP_TD ),
				$employer->display_name
		);
	}

	$content .= "\r\n\r\n" . sprintf( __( "Visit the project %s.", APP_TD ), $workspace_link );

	$participant = array(
		'recipient' => $worker->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' =>  hrb_get_workspace_url( $workspace_id ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

# Proposals

/**
 * Notify project and proposal author on canceled proposal.
 * Notification + Email
 */
function hrb_proposal_canceled_notify_parties( $proposal ) {

	$proposal = hrb_get_proposal( $proposal );

	if ( empty( $proposal ) ) {
		return;
	}

	$candidate = get_user_by( 'id', $proposal->user_id );
	$employer = get_user_by( 'id', $proposal->project->post_author );

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );


	### notify candidate

	$subject_message = sprintf( __( 'Proposal from %1$s on - %2$s - was canceled', APP_TD ), $candidate->display_name, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		User \'%3$s\' has canceled his proposal for %4$s.', APP_TD ),
		"\r\n\r\n", $employer->display_name, $candidate->display_name, $project_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );


	### notify sender

	$subject_message = sprintf( __( "Your proposal for - %s - was canceled", APP_TD ), $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		Your proposal for %3$s, was canceled.', APP_TD ),
		"\r\n\r\n", $candidate->display_name, $project_link
	);

	$participant = array(
		'recipient' => $candidate->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

}

# Agreement Discussion

/**
 * Notify employer and candidate on project assignment.
 * Notification + Email
 */
function hrb_agreement_notify_parties( $proposal, $user, $workspace_id ) {

	$proposal = hrb_get_proposal( $proposal );

	$candidate = get_user_by( 'id', $proposal->user_id );
	$employer = get_user_by( 'id', $proposal->project->post_author );

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );
	$workspace_url = hrb_get_workspace_url( $workspace_id );
	$workspace_link = html_link( $workspace_url, __( 'workspace', APP_TD ) );


	### notify candidate

	$subject_message = sprintf( __( 'Project - %1$s - from %2$s was assigned to you!', APP_TD ), $project_link, $employer->display_name );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		Congratulations! Project %3$s, from \'%4$s\', was assigned to you!%1$s %1$s
		A %5$s for the project is now available on your dashboard. From there you\'ll be able to contact \'%4$s\' and manage your work.', APP_TD ),
		"\r\n\r\n", $candidate->display_name, $project_link, $employer->display_name, $workspace_link
	);

	$participant = array(
		'recipient' => $candidate->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => $workspace_url,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );


	### notify sender

	$subject_message = sprintf( __( 'Your project - %1$s - was assigned to %2$s!', APP_TD ), $project_link, $candidate->display_name );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		Project %3$s, has just been assigned to \'%4$s\'.%1$s %1$s
		A %5$s for the project is now available on your dashboard. From there you\'ll be able to contact \'%4$s\' and manage your work.', APP_TD ),
		"\r\n\r\n", $employer->display_name, $project_link, $candidate->display_name, $workspace_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => $workspace_url,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );


	### notify proposal authors about project assignment

	$project =  $proposal->project;

	$users = array();

	$proposals = hrb_get_proposals_by_post( $project->ID );

	foreach( $proposals['results'] as $other_proposal ) {

		$user = get_user_by( 'id', $other_proposal->get_user_id() );
		if ( $user->ID != $proposal->get_user_id() ) {
			$users[] = $user;
		}
	}

	foreach( $users as $worker ) {

		$subject_message = sprintf( __( 'Project - %1$s - owned by \'%2$s\' was assigned to another user', APP_TD ), $project_link, $employer->display_name );

		$content = sprintf(
			__( 'Hello %2$s,%1$s
			Project %3$s, owned by \'%4$s\' was assigned to other user.', APP_TD ),
			"\r\n\r\n", $worker->display_name, $project_link, $employer->display_name
		);

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project->ID,
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}
}

/**
 * Notify employer and candidate on development terms change.
 * Notification + Email
 */
function hrb_dev_terms_modified_notify_parties( $p2p_id, $terms ) {

	$candidate = _hrb_p2p_get_candidate( $p2p_id );

	$proposal = hrb_get_proposal( $candidate->proposal_id );

	$employer = get_user_by( 'id', $proposal->project->post_author );

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );
	$terms_link = html_link( get_the_hrb_proposal_url( $proposal ), __( 'terms', APP_TD ) );

	### notify employer

	$subject_message = sprintf( __( 'Candidate \'%1$s\' updated his %2$s for - %3$s -', APP_TD ), $candidate->display_name, $terms_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		candidate \'%3$s\' has updated his %4$s for %5$s.', APP_TD ), "\r\n\r\n", $employer->display_name, $candidate->display_name, $terms_link, $project_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify candidate

	$subject_message = sprintf( __( 'You\'ve updated your %1$s for - %2$s -', APP_TD ), $terms_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you\'ve updated your %3$s for %4$s.', APP_TD ), "\r\n\r\n", $candidate->display_name, $terms_link, $project_link
	);

	$participant = array(
		'recipient' => $candidate->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify employer and candidate on project terms change.
 * Notification + Email
 */
function hrb_terms_modified_notify_parties( $project_id, $proposal, $terms ) {

	$project = hrb_get_project( $project_id );

	$employer = get_user_by( 'id', $project->post_author );

	$project_link = html_link( get_permalink( $project->ID ), $project->post_title );
	$terms_link = html_link( get_the_hrb_proposal_url( $proposal ), __( 'terms', APP_TD ) );

	### notify selected candidates

	$candidates = hrb_p2p_get_post_selected_candidates( $project_id );

	foreach( $candidates as $candidate ) {

		$subject_message = sprintf( __( 'User \'%1$s\' updated the %2$s for - %3$s -', APP_TD ), $employer->display_name, $terms_link, $project_link );

		$content = sprintf(
			__( 'Hello %2$s,%1$s
			user \'%3$s\' has updated the %4$s for %5$s.', APP_TD ), "\r\n\r\n", $candidate->display_name, $employer->display_name, $terms_link, $project_link
		);

		$participant = array(
			'recipient' => $candidate->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project_id,
				'action' => get_the_hrb_proposal_url( $proposal ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

	### notify employer

	$subject_message = sprintf( __( 'You\'ve updated the %1$s for - %2$s -', APP_TD ), $terms_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you\'ve updated your %3$s for %4$s.', APP_TD ), "\r\n\r\n", $employer->display_name, $terms_link, $project_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify user about credits added to balance.
 * Notification + Email
 */
function hrb_credits_added_notify_user( $user_id, $credits, $balance ) {

	if ( $credits <= 0 ) {
		return;
	}

	$recipient = get_user_by( 'id', $user_id );

	$dashboard_link = html_link( hrb_get_dashboard_url_for('payments'), __( 'account balance', APP_TD ) );
	$credits_text = sprintf( _n( '%d credit was', '%d credits were', $credits, APP_TD ), $credits );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		%3$s added to your %4$s.%1$s
		Current Balance: %5$d credit(s)', APP_TD ), "\r\n\r\n", $recipient->display_name, $credits_text, $dashboard_link, $balance
	) . "\r\n\r\n";

	$subject = sprintf( __( "Credits added to your %s", APP_TD ), $dashboard_link );

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject ),
			'action' => hrb_get_dashboard_url_for('payments'),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify employer and candidate on a failed agreement.
 * Notification + Email
 */
function hrb_no_agreement_notify_parties( $proposal, $sender, $decision ) {

	$proposal = hrb_get_proposal( $proposal );

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );
	$terms_link = html_link( get_the_hrb_proposal_url( $proposal ), __( 'terms', APP_TD ) );

	if ( $sender->ID == $proposal->project->post_author ) {
		// employer
		$recipient = get_user_by( 'id', $proposal->get_user_id() );
	} else {
		// candidate
		$recipient = get_user_by( 'id', $proposal->project->post_author );
	}

	if ( HRB_TERMS_PROPOSE == $decision ) {
		$decision = __( 'proposed new', APP_TD );
	} else {
		$decision = strtolower( hrb_get_agreement_decision_verbiage( $decision ) );
	}

	$decision = sprintf( '%1$s %2$s', $decision, $terms_link );


	### notify recipient

	$subject_message = sprintf( __( 'User \'%1$s\' %2$s for - %3$s -', APP_TD ), $sender->display_name, $decision, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		user %3$s %4$s for %5$s.', APP_TD ), "\r\n\r\n", $recipient->display_name, $sender->display_name, $decision, $project_link
	);

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify sender

	$subject_message = sprintf( __( 'You\'ve %1$s for - %2$s -', APP_TD ), $decision, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you\'ve %3$s for %4$s.', APP_TD ), "\r\n\r\n", $sender->display_name, $decision, $project_link
	);

	$participant = array(
		'recipient' => $sender->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify employer and candidate on a canceled agreement.
 * Notification + Email
 */
function hrb_agreement_canceled_notify_parties( $proposal, $sender ) {

	$proposal = hrb_get_proposal( $proposal );

	if ( $sender->ID == $proposal->project->post_author ) {
		// employer
		$recipient = get_user_by( 'id', $proposal->get_user_id() );
	} else {
		// candidate
		$recipient = get_user_by( 'id', $proposal->project->post_author );
	}

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );

	### notify recipient

	$subject_message = sprintf( __( 'User \'%1$s\' has canceled negotiations with you for - %2$s -', APP_TD ), $sender->display_name, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		user \'%3$s\' has canceled negotiations with you on %4$s.', APP_TD ),
		"\r\n\r\n", $recipient->display_name, $sender->display_name, $project_link
	);

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify sender

	$subject_message = sprintf( __( 'You\'ve canceled negotiations with \'%1$s\' on - %2$s -', APP_TD ), $recipient->display_name, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you\'ve canceled negotiations with \'%3$s\' on %4$s.', APP_TD ),
		"\r\n\r\n", $sender->display_name, $recipient->display_name, $project_link
	);

	$participant = array(
		'recipient' => $sender->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}


# Proposals

/**
 * Notify employer and candidate on new proposals.
 * Notification + Email
 */
function hrb_proposal_notify_parties( $proposal ) {

	### edited Proposals

	if ( strtotime($proposal->get_date() ) != $proposal->updated ) {
		hrb_edited_proposal_notify_parties( $proposal );
		return;
	}

	### new Proposals

	$proposal = hrb_get_proposal( $proposal );

	$candidate = get_user_by( 'id', $proposal->user_id );
	$employer = get_user_by( 'id', $proposal->project->post_author );

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );
	$proposal_link = html_link( get_the_hrb_proposal_url( $proposal ), __( 'proposal', APP_TD ) );

	### notify candidate

	$subject_message = sprintf( __( 'Your %1$s for - %2$s - was sent to \'%3$s\'', APP_TD ), $proposal_link, $project_link, $employer->display_name );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		your %3$s for %4$s, was sent to \'%5$s\'.', APP_TD ), "\r\n\r\n", $candidate->display_name, $proposal_link, $project_link, $employer->display_name
	);

	$participant = array(
		'recipient' => $candidate->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify employer

	$subject_message = sprintf( __( 'User \'%1$s\' has just sent you a %2$s for - %3$s -', APP_TD ), $candidate->display_name, $proposal_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		user %3$s has just sent you a %4$s for %5$s.', APP_TD ), "\r\n\r\n", $employer->display_name, $candidate->display_name, $proposal_link, $project_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'proposal', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify employer and candidate on edited proposals.
 * Notification + Email
 */
function hrb_edited_proposal_notify_parties( $proposal ) {

	$proposal = hrb_get_proposal( $proposal );

	$candidate = get_user_by( 'id', $proposal->user_id );
	$employer = get_user_by( 'id', $proposal->project->post_author );

	$project_link = html_link( get_permalink( $proposal->project->ID ), $proposal->project->post_title );
	$proposal_link = html_link( get_the_hrb_proposal_url( $proposal ), __( 'proposal', APP_TD ) );

	### notify candidate

	$subject_message = sprintf( __( 'Your %1$s for - %2$s - was updated succesfully.', APP_TD ), $proposal_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		your %3$s for %4$s, was updated succesfully and re-sent to \'%5$s\'.', APP_TD ),
		"\r\n\r\n", $candidate->display_name, $proposal_link, $project_link, $employer->display_name
	);

	$participant = array(
		'recipient' => $candidate->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify employer

	$subject_message = sprintf( __( 'User \'%1$s\' has updated his %2$s for - %3$s -', APP_TD ), $candidate->display_name, $proposal_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		user %3$s has just updated his %4$s for %5$s.', APP_TD ),
		"\r\n\r\n", $employer->display_name, $candidate->display_name, $proposal_link, $project_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->project->ID,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'proposal', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify employer and proposal author on a candidate selection.
 * Notification + Email
 */
function hrb_new_candidate_notify( $p2p_id, $user_id, $post_id, $proposal ) {

	$candidate = get_user_by( 'id', $user_id );
	$employer = get_user_by( 'id', $proposal->project->post_author );

	$project_link = html_link( get_permalink( $post_id ), $proposal->project->post_title );
	$terms_link = html_link( get_the_hrb_proposal_url( $proposal ), __( 'terms', APP_TD ) );

	### notify candidate

	$subject_message = sprintf( __( "Congratulations! You were selected as candidate for working on - %s -", APP_TD ), $project_link );
	$subject_message .= "\r\n" . sprintf( __( 'You must agree with \'%1$s\' %2$s before the project is officially assigned to you.', APP_TD ), $employer->display_name, $terms_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		Congratulations! User \'%3$s\' as selected you as a candidate for %4$s. %1$s
		You must agree with \'%3$s\' %5$s before the work is officially assigned to you.', APP_TD ),
		"\r\n\r\n", $candidate->display_name, $employer->display_name, $project_link, $terms_link
	);

	$participant = array(
		'recipient' => $candidate->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $post_id,
			'action' => get_the_hrb_proposal_url( $proposal ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify employer

	$subject_message = sprintf( __( 'You\'ve selected user %1$s as a candidate for working on - %2$s -', APP_TD ), $candidate->display_name, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you\'ve selected %3$s\'s as a candidate to work on %4$s.%1$s
		You must agree with each other %5$s before the work is assigned to him.', APP_TD ),
		"\r\n\r\n", $employer->display_name, $candidate->display_name, $project_link, $terms_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $post_id,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify users of insufficient credits when a proposal is being approved.
 * Notification + Email
 */
function hrb_insufficient_credits_notify( $user_id, $credits_required, $proposal ) {

	$recipient = get_user_by( 'id', $user_id );

	// make sure we're getting an hirebee 'proposal' object and not a 'bid' object
	$proposal = hrb_get_proposal( $proposal );

	$project_link = html_link( get_permalink( $proposal->get_post_ID() ), $proposal->project->post_title );
	$purchase_credits_link = html_link( hrb_get_credits_purchase_url(), __( 'purchase', APP_TD ) );

	$credits_balance = hrb_get_user_credits( $user_id );

	$subject_message = sprintf( __( "Insufficient credits to apply for - %s -", APP_TD ), $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you don\'t have sufficient credits to apply for project %3$s.%1$s
		Credits Balance: %4$d
		Credits Required: %5$d %1$s
		Please %6$s some credits and try again.', APP_TD ),
		"\r\n\r\n", $recipient->display_name, $project_link, $credits_balance, $credits_required, $purchase_credits_link
	);

	$participant = array(
		'recipient' => $recipient->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $proposal->get_post_ID(),
			'action' => get_permalink( $proposal->project ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

# Reviews

/**
 * Notify participants on new reviews.
 * Notification + Email
 */
function hrb_new_user_review_notify_parties( $review, $recipient_id ) {

	$reviewee = get_user_by( 'id', $recipient_id );
	$reviewer = get_user_by( 'id', $review->user_id );

	$project_id = $review->get_post_ID();

	$workspace_url = hrb_get_workspace_url_by( 'post_id', $project_id, array( $review->user_id, $recipient_id ) );

	$project_link = html_link( get_permalink( $project_id ), get_the_title( $project_id ) );
	$review_link = html_link( $workspace_url, __( 'review', APP_TD ) );

	### notify candidate

	$subject_message = sprintf( __( 'Your %1$s on - %2$s - was sent to \'%3$s\'', APP_TD ), $review_link, $project_link, $reviewee->display_name );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		your %3$s for %4$s was succesfully sent to \'%5$s\'.', APP_TD ),
		"\r\n\r\n", $reviewer->display_name, $review_link, $project_link, $reviewee->display_name
	);

	$participant = array(
		'recipient' => $reviewer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project_id,
			'action' => $review_link,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	### notify employer

	$subject_message = sprintf( __( 'User \'%1$s\' has just sent you a %2$s on - %3$s -', APP_TD ), $reviewer->display_name, $review_link, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		you\'ve received a new %3$s for %4$s from \'%5$s\'.', APP_TD ),
		"\r\n\r\n", $reviewee->display_name, $review_link, $project_link, $reviewer->display_name
	);

	$participant = array(
		'recipient' => $reviewee->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project_id,
			'action' => $review_link,
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'review', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

### Escrow

/**
 * Notify participants on an escrow release.
 * Notification + Email
 */
function hrb_escrow_paid_notify( $order ) {

	if ( ! $order->is_escrow() ) {
		return;
	}

	$workspace = $order->get_item();

	if ( empty( $workspace ) ) {
		return;
	}

	$workspace_id = $workspace['post_id'];

	$project = hrb_p2p_get_workspace_post( $workspace_id );

	$employer = get_user_by( 'id', $workspace['post']->post_author );

	$workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), $project->post_title );

	### notify employer

	$subject_message = sprintf( __( 'Work for - %1$s - has been paid', APP_TD ), $workspace_link );

	$content = sprintf(
		__( 'Hello %1$s,%2$s
		work for %3$s has been paid.%2$s
		Thanks for choosing us.', APP_TD ),
		$employer->display_name, "\r\n\r\n", $workspace_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' => hrb_get_workspace_url( $workspace_id ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'] );

	$participants = hrb_p2p_get_workspace_participants( $workspace_id );
	if ( ! $participants ) {
		return;
	}

	$receivers = $order->get_receivers();

	foreach( $participants->results as $worker ) {
		$subject_message = sprintf( __( 'You\'ve been paid for work on - %1$s -', APP_TD ), $workspace_link );

		$amount = $receivers[ $worker->ID ];

		$content = sprintf(
			__( 'Hello %1$s,%2$s
			you\'ve been paid %3$s for your work on %4$s.%2$s
			Thanks for choosing us.', APP_TD ),
			$worker->display_name, "\r\n\r\n", appthemes_get_price( $amount ), $workspace_link
		);

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project->ID,
				'action' => hrb_get_workspace_url( $workspace_id ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

}

/**
 * Notify admins on paid escrow Orders.
 * Email Only
 */
function hrb_escrow_paid_notify_admin( $order ) {

	$order_link = html_link( hrb_get_order_admin_url( $order->get_id() ), '#'.$order->get_id() );
	$subject = sprintf( __( '[%s] Funds held in escrow Order #%d were released', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	$details = appthemes_get_escrow_details( $order );

	if ( empty( $details['paymentInfoList']['paymentInfo'] ) ) {
		return;
	}

	$retained = 0;

	foreach( $details['paymentInfoList']['paymentInfo'] as $key => $payment_info ) {
		if ( 0 == $key ) continue;
		$retained += $payment_info['receiver']['amount'];
	}

	$content = sprintf(
		__( 'Hello,%1$s
		Funds held in Escrow Order %2$s, have been released to the secondary receivers.%1$s
		Retained: <strong>%3$s</strong>', APP_TD ), "\r\n\r\n", $order_link, appthemes_get_price( $order->get_total() - $retained, $order->get_currency() )
	);

	$content .= _hrb_order_summary_email_body( $order );

	appthemes_send_email( get_option( 'admin_email' ), $subject, wpautop( $content ) );
}

/**
 * Notify participants on workspace waiting funds.
 * Notification + Email
 */
function hrb_escrow_waiting_funds_notify( $workspace_id, $workspace ) {

	$project = hrb_p2p_get_workspace_post( $workspace_id );

	$employer = get_user_by( 'id', $workspace->post_author );

	$workspace_link = html_link( hrb_get_workspace_url( $workspace_id ),  $project->post_title );
	$transfer_funds_link = html_link( hrb_get_workspace_transfer_funds_url( $workspace_id ), __( 'Transfer funds now', APP_TD ) );

	### notify employer

	$subject_message = sprintf( __( 'Workspace for - %1$s - is waiting funds', APP_TD ), $workspace_link );

	$content = sprintf(
		__( 'Hello %1$s,%2$s
		the workspace for project %3$s is waiting for funds. Funds must be transferred to our escrow account before work can start.%2$s
		%4$s to activate the workspace.', APP_TD ),
		$employer->display_name, "\r\n\r\n", $workspace_link, $transfer_funds_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' => hrb_get_workspace_transfer_funds_url( $workspace_id ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	$participants = hrb_p2p_get_workspace_participants( $workspace_id );
	if ( ! $participants ) {
		return;
	}

	foreach( $participants->results as $worker ) {
		$subject_message = sprintf( __( 'Workspace for - %1$s - is waiting for funds', APP_TD ), $workspace_link );

		$content = sprintf(
			__( 'Hello %1$s,%2$s
			the workspace for project %3$s is waiting for funds so work can start.%2$s
			Please wait for a transfer confirmation before you start any work.', APP_TD ),
			$worker->display_name, "\r\n\r\n", $workspace_link
		);

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project->ID,
				'action' => hrb_get_workspace_url( $workspace_id ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

}

/**
 * Notify participants on funds available.
 * Notification + Email
 */
function hrb_escrow_funds_available_notify( $order ) {

	if ( ! $order->is_escrow() ) {
		return;
	}

	$workspace = $order->get_item();

	if ( empty( $workspace ) ) {
		return;
	}

	$workspace_id = $workspace['post_id'];

	$employer = get_user_by( 'id', $workspace['post']->post_author );

	$project = hrb_p2p_get_workspace_post( $workspace_id );
	$workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), $project->post_title );

	### notify employer

	$subject_message = sprintf( __( 'Funds for work on - %1$s - are available', APP_TD ), $workspace_link );

	$content = sprintf(
		__( 'Hello %1$s,%2$s
		your funds for %3$s were succefully transferred to our escrow account.%2$s
		Work can start!', APP_TD ),
		$employer->display_name, "\r\n\r\n", $workspace_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' => hrb_get_workspace_url( $workspace_id ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'] );

	$participants = hrb_p2p_get_workspace_participants( $workspace_id );
	if ( ! $participants ) {
		return;
	}

	$receivers = $order->get_receivers();

	foreach( $participants->results as $worker ) {
		$subject_message = sprintf( __( 'Funds for work on - %1$s - are available', APP_TD ), $workspace_link );

		$amount = $receivers[ $worker->ID ];

		$content = sprintf(
			__( 'Hello %1$s,%2$s
			funds for work on %3$s are now available.%2$s
			Work can start!', APP_TD ),
			$worker->display_name, "\r\n\r\n", $workspace_link
		);

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project->ID,
				'action' => hrb_get_workspace_url( $workspace_id ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

}

/**
 * Notify participants on refund request.
 * Notification + Email
 */
function hrb_escrow_refund_notify( $order ) {

	$workspace = $order->get_item();

	if ( empty( $workspace ) ) {
		return;
	}

	$workspace_id = $workspace['post_id'];

	$project = hrb_p2p_get_workspace_post( $workspace_id );

	$employer = get_user_by( 'id', $workspace['post']->post_author );

	$workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), $project->post_title );

	### notify employer

	$subject_message = sprintf( __( 'You were refunded for - %1$s -', APP_TD ), $workspace_link );

	$content = sprintf(
		__( 'Hello %1$s,%2$s
		as requested, you were refunded for for work on %3$s.%2$s
		Thanks for choosing us.', APP_TD ),
		$employer->display_name, "\r\n\r\n", $workspace_link
	);

	$participant = array(
		'recipient' => $employer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' => hrb_get_workspace_transfer_funds_url( $workspace_id ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );

	$participants = hrb_p2p_get_workspace_participants( $workspace_id );
	if ( ! $participants ) {
		return;
	}

	$receivers = $order->get_receivers();

	foreach( $participants->results as $worker ) {
		$subject_message = sprintf( __( 'A refund was issued for work on - %1$s -', APP_TD ), $workspace_link );

		$content = sprintf(
			__( 'Hello %1$s,%2$s
			work on %3$s was not completed. The project owner has been refunded.%2$s
			Unfortunately you\'ll not be paid for your work.', APP_TD ),
			$worker->display_name, "\r\n\r\n", $workspace_link
		);

		$participant = array(
			'recipient' => $worker->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'project_id' => $project->ID,
				'action' => hrb_get_workspace_url( $workspace_id ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

}

/**
 * Notify admins on succesfull escrow Orders refunds.
 * Email Only
 */
function hrb_escrow_refund_notify_admin( $order ) {

	$order_link = html_link( hrb_get_order_admin_url( $order->get_id() ), '#'.$order->get_id() );
	$subject = sprintf( __( '[%s] Funds held in Escrow Order #%d were refunded', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	$content = sprintf(
		__( "Hello,%s
		Funds held in Escrow Order %s, have been fully refunded.", APP_TD ), "\r\n\r\n", $order_link
	);

	$content .= _hrb_order_summary_email_body( $order );

	appthemes_send_email( get_option( 'admin_email' ), $subject, wpautop( $content ) );
}

/**
 * Notify admins on failed refunds.
 * Email Only
 */
function hrb_refund_failed_notify_admin( $order ) {

	$order_link = html_link( hrb_get_order_admin_url( $order->get_id() ), '#'.$order->get_id() );

	$subject = sprintf( __( '[%s] A refund request for Escrow Order #%d has failed', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	$content = sprintf(
		__( 'Hello,%1$s
		Refund for Escrow Order %2$s, has failed.%1$s
		More info can be found on the Order page.', APP_TD ), "\r\n\r\n", $order_link
	);

	$content .= _hrb_order_summary_email_body( $order );

	appthemes_send_email( get_option( 'admin_email' ), $subject, wpautop( $content ) );
}

/**
 * Notify admins on failed payments.
 * Email Only
 */
function hrb_payment_failed_notify_admin( $order ) {

	$order_link = html_link( hrb_get_order_admin_url( $order->get_id() ), '#'.$order->get_id() );
	$subject = sprintf( __( '[%s] A payment transfer for Escrow Order #%d has failed', APP_TD ), get_bloginfo( 'name' ), $order->get_id() );

	$content = sprintf(
		__( 'Hello,%1$s
		Payment for Escrow Order %2$s, has failed.%1$s
		More info can be found on the Order page.', APP_TD ), "\r\n\r\n", $order_link
	);

	$content .= _hrb_order_summary_email_body( $order );

	appthemes_send_email( get_option( 'admin_email' ), $subject, wpautop( $content ) );
}


### Verbiages

/**
 * Retrieves the notifications types verbiages or a single type verbiage.
 */
function hrb_get_notifications_verbiage( $type = '' ) {

	$verbiages = array(
		'notification'  => __( 'Notification', APP_TD ),
		'proposal'		=> __( 'Proposals', APP_TD ),
		'terms'			=> __( 'Terms', APP_TD ),
		'status'		=> __( 'Status', APP_TD ),
		'action'		=> __( 'Action Required', APP_TD ),
		'review'		=> __( 'Reviews', APP_TD )
	);

	return hrb_get_verbiage_values( $verbiages, $type );
}


### Helper Functions

/**
 * Outputs the email signature
 *
 * @uses do_action() Calls 'hrb_email_signature'
 *
 */
function hrb_email_signature( $headers ) {

	$signature  = sprintf( __( "Thanks,", APP_TD ) ) . "\r\n";
	$signature .= sprintf( __( "The %s team", APP_TD ), get_bloginfo('name') );

	// add line breaks considering the email content type
	if ( $headers ) {
		if ( is_array( $headers ) && isset( $headers['type'] ) ) {
			$headers = $headers['type'];
		}
		if ( false !== strpos( $headers, 'text/html' ) ) {
			$signature = wpautop( $signature );
		}
	} else {
		$signature = "\r\n" . $signature;
	}

	return apply_filters( 'hrb_email_signature', $signature, $headers );
}

/**
 * Prefix all emails that contain the blog name in the subject
 */
function hrb_append_signature( $email ) {

	$headers = array();

	if ( ! empty( $email['headers'] ) ) {
		$headers = $email['headers'];
	}

	if ( get_bloginfo( 'name' ) && false !== strpos( $email['subject'], get_bloginfo( 'name' ) ) ) {
		$email['message'] .= hrb_email_signature( $headers );

	}

	return $email;
}

/**
 * Group notification types.
 */
function hrb_group_notification_types( $notifications ) {

	$group['default'] = __( 'All', APP_TD );

	foreach( $notifications->results as $notification ) {
		$type = $notification->type;
		$group[ $type ] = hrb_get_notifications_verbiage( $type );
	}
	return $group;
}

/**
 * Retrieves the Order summary email body
 */
function _hrb_order_summary_email_body( $order, $to_admin = false ) {

	$ordered_post = '';

	$table = new APP_Order_Summary_Table( $order );

	$post = hrb_get_order_post( $order, HRB_PROJECTS_PTYPE );
	if ( $post ) {

		if ( $to_admin ) {
			$ordered_post = html_link( hrb_get_order_admin_url( $order->get_id() ), '#'.$order->get_id() );
		} else {
			$ordered_post = html( 'p' , html_link( get_permalink( $post ), $post->post_title ) );
		}

	}

	ob_start();

	$table->show();
	$table_output = ob_get_clean();

	$content = html( 'p', __( 'Order Summary:', APP_TD ) );
	$content .= $ordered_post;
	$content .= $table_output;

	return $content;
}