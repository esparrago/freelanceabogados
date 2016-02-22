<?php
/**
 * Disputes related functions.
 *
 * @package HireBee\Disputes
 */

add_action( 'init', '_hrb_schedule_unopened_disputes_refund' );
add_action( 'hrb_unopened_disputes_refund_or_notify', 'hrb_unopened_disputes_refund_or_notify' );

add_filter( 'map_meta_cap', '_hrb_dispute_map_capabilities', 15, 4 );

add_filter( 'appthemes_dispute_comment_redirect', '_hrb_dispute_discussion_redirect', 10, 2 );
add_filter( 'comment_reply_link', '_hrb_disable_dispute_comment_replies', 10, 3 );
add_action( 'appthemes_dispute_comment_insert', '_hrb_display_dispute_comment_notice' );
add_action( 'pre_comment_approved', '_hrb_dispute_comments_approve', 10, 2 );
add_action( 'wp_insert_comment', '_hrb_dispute_comment_notify_parties', 10, 2 );

add_filter( 'comment_notification_recipients', '_hrb_disputes_disable_default_comment_notification', 10, 2 );

add_action( 'hrb_workspace_ended_disagreement_canceled', '_hrb_maybe_set_workspace_dispute_timer', 15, 2 );
add_action( 'hrb_workspace_ended_disagreement_closed_incomplete', '_hrb_maybe_set_workspace_dispute_timer', 15, 2 );

add_action( 'appthemes_dispute_resolved', 'hrb_resolve_dispute', 10, 2 );
add_action( 'appthemes_dispute_resolved', 'hrb_opened_dispute_delete_meta', 15, 2 );

add_action( 'hrb_dispute_open_time_ended', 'hrb_workspace_refund_author' );

add_action( 'appthemes_dispute_opened', 'hrb_dispute_opened_add_meta', 10, 3 );
add_action( 'appthemes_dispute_opened', 'hrb_dispute_opened_notify_parties', 10, 3 );
add_action( 'appthemes_dispute_opened', 'hrb_dispute_opened_notify_admin' );

add_action( 'appthemes_dispute_paid', 'hrb_dispute_resolved_notify_parties', 10, 3 );
add_action( 'appthemes_dispute_refunded', 'hrb_dispute_resolved_notify_parties', 10, 3 );

add_filter( 'hrb_project_status_change_notify_content', '_hrb_project_status_change_inform_dispute', 10, 5 );


### Hooks Callbacks

/**
 * Schedules a project prune to unpublish expired projects.
 */
function _hrb_schedule_unopened_disputes_refund() {

	if ( ! hrb_is_disputes_enabled() ) {
		return;
	}

	if ( ! wp_next_scheduled( 'hrb_unopened_disputes_refund_or_notify' ) ) {
		wp_schedule_event( time(), 'daily', 'hrb_unopened_disputes_refund_or_notify' );
	}

}

/**
 * Retrieve the participants on a given dispute ID.
 *
 * @since 1.3
 *
 * @param int $dispute_id The dispute ID.
 * @return array A collection of participants user ID's.
 */
function hrb_get_dispute_participants( $dispute_id ) {

	$workspace = appthemes_get_dispute_p2p_post( $dispute_id );

	$args = array(
		'connected_meta' => array( 'type' => array( 'worker', 'employer' ) ),
	);

	$participants = hrb_p2p_get_workspace_participants( $workspace->ID, $args )->results;

	$participants = wp_list_pluck( $participants, 'ID' );

	return $participants;
}

/**
 * Wrapper for 'appthemes_raise_dispute()'.
 *
 * @since 1.3
 *
 * @param int $workspace_id The workspace ID to raise the dispute on.
 * @param string $disputer The disputing user ID.
 * @param string $reason (optional) The reason for the dispute.
 * @param array $args (optional) Additional args for the p2p query.
 * @return int|boolean The dispute p2p ID or False on error.
 */
function hrb_raise_dispute( $workspace_id, $disputer, $reason, $args = array() ) {

	$workspace = get_post( $workspace_id );
	$disputee = $workspace->post_author;

	return appthemes_raise_dispute( $workspace, $disputer, $disputee, $reason, $args );
}

/**
 * Redirect the user back to the workspace after submitting a dispute comment.
 *
 * @since 1.3
 *
 * @param string $location The URL locatio to redirect the user when posting a dispute comment.
 * @param WP_Comment $comment The comment object.
 * @return string The redirect URL location.
 */
function _hrb_dispute_discussion_redirect( $location, $comment ) {

	$dispute_id = $comment->comment_post_ID;

	$workspace = appthemes_get_dispute_p2p_post( $dispute_id );

	$location = hrb_get_workspace_url( $workspace->ID ) . '#disputes';

	return $location;
}

/**
 * Display a notice after succesfully posting a dispute comment.
 *
 * @since 1.3
 *
 * @param int $id The dispute comment ID.
 */
function _hrb_display_dispute_comment_notice( $id ) {
	appthemes_add_notice( 'dispute-comment-success', __( 'Your comment was posted succefully!', APP_TD ), 'success' );
}

/**
 * Sets the time limit for a freelancer to open a dispute on a given workspace.
 *
 * @since 1.3
 *
 * @param int $id The workspace post ID.
 * @param object $post The workspace WP_Post object.
 */
function _hrb_maybe_set_workspace_dispute_timer( $id, $post ) {
	global $hrb_options;

	if ( ! hrb_is_disputes_enabled() ) {
		return;
	}

	$dispute_days = $hrb_options->disputes['max_days'];

	$dispute_end_date = date( 'Y-m-d H:i:s', strtotime( sprintf( '+%d day', $dispute_days ), time() ) );

	update_post_meta( $id, 'dispute_end_date', $dispute_end_date );

	$time = current_time('mysql');

	// notify freelancers when half the days have passed and right before the day the dispute expires

	$days_to_end = appthemes_days_between_dates( $dispute_end_date, $time, 0 );
	$days_half = (int) $days_to_end / 2;

	if ( $days_half > 1 ) {
		$notify_times[ date( 'Y-m-d H:i:s', strtotime( $dispute_end_date . sprintf( ' -%d days', $days_half ) ) ) ] = 0;
	}
	$notify_times[ date( 'Y-m-d H:i:s', strtotime( $dispute_end_date . ' -1 days' ) ) ] = 0;

	$notify_times = apply_filters( 'hrb_dispute_notify_times', $notify_times, $id, $post );

	if ( $notify_times ) {
		update_post_meta( $id, "dispute_end_notify_times", $notify_times );
	}
}

/**
 * Resolves an opened dispute by triggering payment to secondary receivers or refunds to employers.
 *
 * @since 1.3
 *
 * @param object $dispute The dispute WP_Post object.
 * @param object $workspace The dispute workspace WP_Post object.
 */
function hrb_resolve_dispute( $dispute, $workspace ) {

	if ( APP_DISPUTE_STATUS_PAY == $dispute->post_status ) {
		hrb_workspace_pay_participants( $workspace->ID );
	} else {
		hrb_workspace_refund_author( $workspace->ID );
	}

}

/**
 * Stores additional data on the workspace meta when a dispute is opened.
 *
 * @since 1.3
 *
 * @param int $dispute_id The dispute post ID.
 * @param object $p2p The dispute p2p object.
 * @param object $workspace The dispute workspace WP_Post object.
 */
function hrb_dispute_opened_add_meta( $dispute_id, $p2p, $workspace ) {

	$dispute = get_post( $dispute_id );

	$disputer = $dispute->post_author;

	add_post_meta( $workspace->ID, 'opened_dispute', $disputer );
}

/**
 * Deletes data from the workspace meta when a dispoute is resolved.
 *
 * @since 1.3
 *
 * @param object $dispute The dispute WP_Post object.
 * @param object $workspace The dispute worksapce WP_Post object.
 */
function hrb_opened_dispute_delete_meta( $dispute, $workspace ) {
	delete_post_meta( $workspace->ID, 'opened_dispute', $dispute->post_author );
}

/**
 * Disable replies on disputes comments.
 *
 * @since 1.3
 *
 */
function _hrb_disable_dispute_comment_replies( $link, $args, $comment ) {

	if ( ! hrb_is_disputes_enabled() || APP_DISPUTE_PTYPE != $comment->comment_type ) {
		return $link;
	}

	return '';
}

/**
 * Hook into the project workspace status change notification to inform freelancers on disputes if they don't agree with employer status.
 *
 * @since 1.3
 */
function _hrb_project_status_change_inform_dispute( $content, $new_status, $post, $workspace_id, $user ) {
	global $hrb_options;

	if ( ! hrb_is_disputes_enabled() || ! hrb_is_workspace_status_disagreement( $workspace_id, $new_status ) ) {
		return $content;
	}

	$workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), __( 'workspace', APP_TD ) );

	$days_left = $hrb_options->disputes['max_days'];

	if ( $post->post_author == $user->ID ) {
		$content .= sprintf( __( 'If the user working on the project does not agree with your project end decision he will be able to open a dispute within the next %2$d %3$s.', APP_TD ), $workspace_link, $days_left, _n( 'day', 'days', $days_left, APP_TD ) );
	} else {
		$content .= sprintf( __( 'If you don\'t agree with the decision you can open a dispute in the project %1$s within the next %2$d %3$s.', APP_TD ), $workspace_link, $days_left, _n( 'day', 'days', $days_left, APP_TD ) );
	}

	return $content;
}


### Cron

/**
 * Look for closed workspaces with unopened disputes.
 *
 * @since 1.3
 *
 * @uses do_action() Calls 'hrb_workspace_dispute_period_ended'
 * @uses do_action() Calls 'hrb_workspace_dispute_period_ending'
 *
 */
function hrb_unopened_disputes_refund_or_notify() {

	$workspace_unopened_disputes = new WP_Query( array(
		'post_type'		=> HRB_WORKSPACE_PTYPE,
		'post_status'	=> array( HRB_PROJECT_STATUS_CANCELED, HRB_PROJECT_STATUS_CLOSED_INCOMPLETE ),
		'meta_query'	=> array(
			'relation' => 'AND',
			array(
				'key'		=> 'dispute_end_date',
				'value'		=> current_time('mysql'),
				'compare'	=> '>',
				'type'		=> 'datetime',
			),
			array(
				'key'		=> 'opened_dispute',
				'value'		=> 'dummy',
				'compare'	=> 'NOT EXISTS',
			),
		),
		'nopaging' => true,
	) );

	$time = current_time('mysql');

	foreach( $workspace_unopened_disputes->posts as $workspace ) {

		$end_time = get_post_meta( $workspace->ID, 'dispute_end_date', true );

		### fire a hook if dispute for the current workspace has expired

		if ( $end_time < $time ) {
			hrb_dispute_period_ended_notify_parties( $workspace, $end_time );

			do_action( 'hrb_workspace_dispute_period_ended', $workspace->ID, $end_time );
		} else {

			### fire a hook each time a pre-set dispute deadline notification time is reached

			$notify_times = get_post_meta( $workspace->ID, 'dispute_end_notify_times', true );

			foreach ( (array) $notify_times as $notify_time => $notified ) {
				// skip if notifications for this time period were already sent
				if ( $notified ) continue;

				if ( $notify_time < $time ) {
					hrb_dispute_period_ending_notify_parties( $workspace, $end_time, $notify_time, $notify_times );

					do_action( 'hrb_workspace_dispute_period_ending', $workspace, $end_time, $notify_time, $notify_times );
				}

			}

		}

	}

}


### Capabilities

/**
 * Meta cababilities for disputes.
 */
function _hrb_dispute_map_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {

		case 'open_dispute':
			$caps = array( 'exist' );

			// disputes are only available if escrow is enabled
			if ( ! hrb_is_escrow_enabled() || ! hrb_is_disputes_enabled() ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// disputes can't be raised from admin panel
			if ( is_admin() ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// check for a workspace ID
			if ( isset( $args[1] ) ) {
				$workspace_id = $args[1];
			} else {
				$caps[] = 'do_not_allow';
				break;
			}

			$participant = hrb_p2p_get_participant( $workspace_id, $user_id );

			// only a 'worker' can open disputes
			if ( ! $participant || ( 'worker' != $participant->type ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// don't allow opening disputes if work was not completed or employer closed project as completed
			if ( HRB_WORK_STATUS_COMPLETED != $participant->status || HRB_WORK_STATUS_COMPLETED == $participant->status && HRB_PROJECT_STATUS_CLOSED_COMPLETED == get_post_status( $workspace_id ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// don't allow opening disputes if already opened for this user/workspace
			$disputes = appthemes_get_disputes_for( $workspace_id, $user_id, 0, array( 'post_status' => 'any' ) );
			if ( ! empty( $disputes ) ) {
				$caps[] = 'do_not_allow';
				break;
			}

			// don't allow raising disputes if period for opening disputes is over
			$end_time = get_post_meta( $workspace_id, 'dispute_end_date', true );
			if ( $end_time < current_time('mysql') ) {
				$caps[] = 'do_not_allow';
				break;
			}

			break;

	}
	return $caps;
}


### Notifications

/**
 * Notify participants on a new dispute.
 *
 * @since 1.3
 */
function hrb_dispute_opened_notify_parties( $dispute_id, $p2p, $workspace ) {

	$dispute = get_post( $dispute_id );

	$disputer = get_user_by( 'id', $dispute->post_author );
	$disputee = get_user_by( 'id', $workspace->post_author );

	$project = hrb_p2p_get_workspace_post( $workspace->ID );

	$project_link = html_link( get_permalink( $project->ID ), $project->post_title );
	$workspace_link = html_link( hrb_get_workspace_url( $workspace->ID ), __( 'workspace', APP_TD ) );
	$dispute_link = html_link( hrb_get_workspace_url( $workspace->ID ) . '#disputes', __( 'communication channel', APP_TD ) );

	$note =  "\r\n\r\n" . sprintf(
		__( 'A new %2$s is now opened for both participants and our team to be able to discuss the decision. We will aim to make a resolution decision on behalf of both parties.%1$s
		If a mutual resolution is agreed between both parties meanwhile, please inform us and we will close the dispute in line with the mutual agreement.', APP_TD ),
		"\r\n\r\n", $dispute_link
	);

	### notify disputee

	$subject_message = sprintf( __( "User %s opened a dispute on - %s -", APP_TD ), $disputer->display_name, $project_link );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		User \'%3$s\' does not agree with your decision on %4$s and has opened a dispute.', APP_TD ),
		"\r\n\r\n", $disputee->display_name, $disputer->display_name, $project_link
	) . $note;

	$content .= "\r\n\r\n" . sprintf( __( "Visit the project %s.", APP_TD ), $workspace_link );

	$participant = array(
		'recipient' => $disputee->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' =>  hrb_get_workspace_url( $workspace->ID ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );


	### notify disputer

	$subject_message = sprintf( __( "Your dispute on - %s - is now opened", APP_TD ), $project_link );

	$content = "\r\n\r\n" . sprintf(
		__( 'Hello %2$s,%1$s
		Your dispute on %3$s is now opened.', APP_TD ),
		"\r\n\r\n", $disputer->display_name, $project_link
	) . $note;

	$content .= "\r\n\r\n" . sprintf( __( "Visit the project %s.", APP_TD ), $workspace_link );

	$participant = array(
		'recipient' => $disputer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' =>  hrb_get_workspace_url( $workspace->ID ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify admins on opened disputes.
 * Email Only
 */
function hrb_dispute_opened_notify_admin( $dispute_id ) {

	$dispute = get_post( $dispute_id );

	$disputer = get_user_by( 'id', $dispute->post_author );

	$dispute_link = html_link( add_query_arg( array( 'post' => $dispute_id, 'action' => 'edit' ), admin_url('post.php') ), __( 'dispute.', APP_TD ) );
	$disputes_link = html_link( add_query_arg( array( 'post_type' => APP_DISPUTE_PTYPE, 'post_status' => 'publish' ), admin_url('edit.php') ), __( 'View all opened disputes.', APP_TD ) );

	$subject = sprintf( __( '[%s] A new dispute was opened', APP_TD ), get_bloginfo( 'name' ) );

	$content = sprintf(
		__( 'Hello,%1$s
		A new %2$s was opened by \'%3$s\'.%1$s%1$s
		Please review it and decide accordingly.%1$s%1$s
		%4$s', APP_TD ), "\r\n\r\n", $dispute_link, $disputer->display_name, $disputes_link
	);

	appthemes_send_email( get_option( 'admin_email' ), $subject, wpautop( $content ) );
}

/**
 * Notify participants on a dispute resolution.
 *
 * @since 1.3
 */
function hrb_dispute_resolved_notify_parties( $dispute, $p2p_post, $status ) {

	$workspace = appthemes_get_dispute_p2p_post( $dispute->ID );

	$disputer = get_user_by( 'id', $dispute->post_author );
	$disputee = get_user_by( 'id', $workspace->post_author );

	$project = hrb_p2p_get_workspace_post( $workspace->ID );

	$project_link = html_link( get_permalink( $project->ID ), $project->post_title );
	$workspace_link = html_link( hrb_get_workspace_url( $workspace->ID ), __( 'workspace', APP_TD ) );


	### notify disputee

	$dispute_decision = APP_DISPUTE_STATUS_REFUND == $status ? __( 'in your favor', APP_TD ) : sprintf( __( 'in favor of \'%s\'', APP_TD ), $disputer->display_name );

	// get the official response from the form posted data since the meta value is not available at the time the notification is triggered
	$official_response = _hrb_get_posted_dispute_meta_value( $dispute->ID, array( 'name' => 'official_response', 'type' => 'textarea' ) );

	$subject_message = sprintf( __( "Dispute on - %s - has been resolved %s", APP_TD ), $project_link, $dispute_decision );

	$content = sprintf(
		__( 'Hello %2$s,%1$s
		After careful review of the dispute on %3$s, raised by \'%4$s\', we decided %5$s. Following is our official response:%1$s
		<em>%6$s</em>.', APP_TD ),
		"\r\n\r\n", $disputee->display_name, $project_link, $disputer->display_name, $dispute_decision, $official_response
	);

	$content .= "\r\n\r\n" . sprintf( __( "Visit the project %s.", APP_TD ), $workspace_link );

	$participant = array(
		'recipient' => $disputee->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' =>  hrb_get_workspace_url( $workspace->ID ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );


	### notify disputer

	$dispute_decision = APP_DISPUTE_STATUS_PAY == $status ? __( 'in your favor', APP_TD ) : sprintf( __( 'in favor of \'%s\'', APP_TD ), $disputee->display_name );

	$subject_message = sprintf( __( "Your dispute on - %s - has been resolved %s", APP_TD ), $project_link, $dispute_decision );

	$content = "\r\n\r\n" . sprintf(
		__( 'Hello %2$s,%1$s
		After careful review of your dispute on %3$s, we decided %4$s. Following is our official response:%1$s
		<em>%5$s</em>.', APP_TD ),
		"\r\n\r\n", $disputer->display_name, $project_link, $dispute_decision, $official_response
	);

	$content .= "\r\n\r\n" . sprintf( __( "Visit the project %s.", APP_TD ), $workspace_link );

	$participant = array(
		'recipient' => $disputer->ID,
		'message' => $subject_message,
		'send_mail' => array(
			'content' => wpautop( $content ),
		),
		'meta' => array(
			'subject' => wp_strip_all_tags( $subject_message ),
			'project_id' => $project->ID,
			'action' =>  hrb_get_workspace_url( $workspace->ID ),
		),
	);

	appthemes_send_notification( $participant['recipient'], $participant['message'], 'notification', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
}

/**
 * Notify participants on the deadline for opening disputes.
 *
 * @since 1.3
 */
function hrb_dispute_period_ending_notify_parties( $workspace, $end_time, $notify_time, $notify_times ) {

	$participants = hrb_p2p_get_workspace_participants( $workspace->ID );
	if ( ! $participants ) {
		return;
	}

	$project = hrb_p2p_get_workspace_post( $workspace->ID );

	$workspace_link = html_link( hrb_get_workspace_url( $workspace->ID ), $workspace->post_title );

	$days_left = appthemes_days_between_dates( $end_time, $notify_time );

	foreach( $participants->results as $worker ) {
		$subject_message = sprintf( __( '%1$d %2$s left for opening a dispute on - %3$s -', APP_TD ), $days_left, _n( 'Day', 'Days', $days_left, APP_TD ), $workspace_link );

		$content = sprintf(
			__( 'Hello %1$s,%2$s
			just to inform you that the deadline for opening disputes on %3$s ends in %4$d %5$s.%2$s
			If you fail to open a dispute during this time the employer will be fully refunded and you will not get paid.', APP_TD ),
			$worker->display_name, "\r\n\r\n", $workspace_link, $days_left, _n( 'Day', 'Days', $days_left, APP_TD )
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
				'action' => hrb_get_workspace_url( $workspace->ID ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

	// update the notify times meta

	$notify_times[ $notify_time ] = 1;

	update_post_meta( $workspace->ID, 'dispute_end_notify_times', $notify_times );
}

/**
 * Notify participants that the deadline for opening disputes has expired.
 *
 * @since 1.3
 */
function hrb_dispute_period_ended_notify_parties( $workspace, $end_time ) {

	$participants = hrb_p2p_get_workspace_participants( $workspace->ID );
	if ( ! $participants ) {
		return;
	}

	$project = hrb_p2p_get_workspace_post( $workspace->ID );

	$workspace_link = html_link( hrb_get_workspace_url( $workspace->ID ), $workspace->post_title );

	foreach( $participants->results as $worker ) {
		$subject_message = sprintf( __( 'The deadline for opening disputes - %3$s - as ended', APP_TD ), $workspace_link );

		$content = sprintf(
			__( 'Hello %1$s,%2$s
			just to inform you that the deadline for opening disputes on %3$s as ended.%2$s
			The employer will be fully refunded for this project.', APP_TD ),
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
				'action' => hrb_get_workspace_url( $workspace->ID ),
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

}

/**
 * Auto approve dispute comments.
 *
 * @since 1.3
 */
function _hrb_dispute_comments_approve( $approved, $commentdata ) {

	if ( ! hrb_is_disputes_enabled() || empty( $commentdata['comment_type'] ) || $commentdata['comment_type'] != appthemes_disputes_get_args('comment_type') ) {
		return $approved;
	}
	return true;
}

/**
 * Skip default WP comment notifications for disputes.
 *
 * @since 1.3
 */
function _hrb_disputes_disable_default_comment_notification( $emails, $comment_id ) {

	$comment = get_comment( $comment_id );

	if ( key( appthemes_disputes_get_args('comment_type') ) != $comment->comment_type ) {
		return $emails;
	}

	return array();
}

/**
 * Notify pariticipants on new dispute comments.
 *
 * @todo move to 'disputes' module
 *
 * @since 1.3
 */
function _hrb_dispute_comment_notify_parties( $id, $comment ) {

	if ( ! hrb_is_disputes_enabled() ) {
		return;
	}

	if ( key( appthemes_disputes_get_args('comment_type') ) != $comment->comment_type ) {
		return;
	}

	$dispute = get_post( $comment->comment_post_ID );
	$workspace = appthemes_get_dispute_p2p_post( $comment->comment_post_ID );

	$dispute_admin_link = html_link( add_query_arg( array( 'post' => $dispute->ID, 'action' => 'edit' ), admin_url('post.php') ), __( 'dispute.', APP_TD ) );
	$dispute_link = html_link( hrb_get_workspace_url( $workspace->ID ) . '#disputes', $dispute->post_title );

	$author = get_user_by( 'id', $comment->user_id );

	$participants = hrb_p2p_get_workspace_participants( $workspace->ID, array( 'connected_meta' => array( 'type' => array( 'employer', 'worker' ) ) ) );
	$participants = $participants->results;

	// notify admin
	$participants[] = get_user_by( 'id', 1 );

	### notify participants and admin

	foreach( $participants as $participant ) {

		if ( $participant->ID == $author->ID ) continue;

		$subject_message = sprintf( __( 'New comment from \'%1$s\' on dispute - %2$s -', APP_TD ), $author->display_name, $dispute_link );

		$content = sprintf(
			__( 'Hello %2$s,%1$s
			There\'s a new comment from \'%3$s\' on %4$s:%1$s
			%5$s', APP_TD ), "\r\n\r\n", $participant->display_name, $author->display_name, ( user_can( $participant, 'admin_options' ) ? $dispute_admin_link : $dispute_link ), $comment->comment_content
		);

		$participant = array(
			'recipient' => $participant->ID,
			'message' => $subject_message,
			'send_mail' => array(
				'content' => wpautop( $content ),
			),
			'meta' => array(
				'subject' => wp_strip_all_tags( $subject_message ),
				'action' => hrb_get_workspace_url( $workspace->ID ) . '#disputes',
			),
		);

		appthemes_send_notification( $participant['recipient'], $participant['message'], 'action', $participant['meta'], array( 'send_mail' => $participant['send_mail'] ) );
	}

}


### Helper functions

/**
 * Check if disputes are enabled by checking if the main dispute setting is enabled and theme supports it.
 *
 * @since 1.3
 */
function hrb_is_disputes_enabled() {
	global $hrb_options;

	return hrb_is_escrow_enabled() && current_theme_supports('app-disputes') && $hrb_options->disputes['enabled'] && $hrb_options->disputes['max_days'];
}

### Template Tag Functions

/**
 * Outputs the dispute decision.
 *
 * @since 1.3
 *
 * @param int $post_id The dispute post ID.
 * @param string $before Text to be prepended to the decision.
 * @param string $after Text to be appeended to the decision.
 */
function the_hrb_dispute_decision( $post_id = 0, $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	echo $before . appthemes_get_dispute_decision( $post_id ) . $after;
}

/**
 * Retrieves a notice informing users about the possibility of a dispute being opened.
 *
 * @since 1.3
 *
 * @return string The notice text.
 */
function hrb_get_possible_dispute_notice( $js = false ) {
	global $hrb_options;

	$break = "\r\n";

	$notice =  sprintf( __( 'Since you haven\'t considered this project as completed even though the work has been completed, the participant is allowed to open a dispute in the next %d days.', APP_TD ), $hrb_options->disputes['max_days'] ) .
				$break . $break .  __( 'If a dispute is opened we will aim to make a resolution decision on behalf of both parties.', APP_TD ) .
				 __( ' If the dispute is resolved in your favor you\'ll be refunded, otherwise the participant will be paid in full for his work.', APP_TD );

	if ( ! $js ) {
		$notice = wpautop( $notice );
	}

	return $notice;
}

/**
 * Checks if the period for opening a dispute on a given workspace is active.
 *
 * @since 1.3
 *
 * @param int $workspace_id The workspace post ID.
 * @return bool True if period is active, False othwerise.
 */
function hrb_is_dispute_period_active( $workspace_id ) {

	$time = current_time('mysql');

	$dispute_end_date = get_post_meta( $workspace_id, 'dispute_end_date', true );
	$days_to_end = appthemes_days_between_dates( $dispute_end_date, $time, 0 );

	return $days_to_end > 0;
}

/**
 * Handles and sanitizes the value for a given meta field on a dispute being processed.
 * Sanitizes the value using the metaboxes validation methods.
 *
 * @since 1.3
 */
function _hrb_get_posted_dispute_meta_value( $post_id, $field ) {

	if ( ! isset( $_POST['action'] ) || $_POST['action'] != 'editpost'|| empty( $field['name'] ) ) {
		return;
	}

	if ( $post_id != $_POST['post_ID'] ) {
		return;
	}

	if ( get_post_type( $post_id) != APP_DISPUTE_PTYPE ) {
		return;
	}

	$value = scbForms::validate_post_data( array( $field ) );

	return $value[ $field['name'] ];
}