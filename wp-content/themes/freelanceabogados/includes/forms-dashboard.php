<?php
/**
 * Views for handling dashboard related forms.
 *
 * Form-Views handle and process form data as well as user actions.
 *
 */

/**
 * Handles projects actions in the user dashboard.
 */
class HRB_User_Dashboard_Form_Projects extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var('action');
		$wp->add_query_var('p_action');
	}

	function condition() {
		return (bool) ( get_query_var('p_action') && get_query_var('project_id') && 'mp' == get_query_var('action') );
	}

	/**
	 * Validates the rules on an user action over a project in the dashboard.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_projects_validate'
	 *
	 */
	function validate( $action, $post_id ) {

		APP_Notices::$notices = apply_filters( 'hrb_dashboard_project_validate', APP_Notices::$notices, $action, $post_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function parse_query( $wp_query ) {

		$action = get_query_var('p_action');
		$project_id = (int) get_query_var('project_id');

		if ( ! $this->validate( $action, $project_id ) ) {
			return;
        }

        if ( ! $this->process( $action, $project_id ) ) {
            // there are errors
            return;
        }

    }

    function process( $action, $project_id ) {

		$result = null;

		switch ( $action ) {
			case 'cancel':
				$result = hrb_cancel_project( $project_id );
                $action_v = __( 'Canceled', APP_TD );
				break;

			case 'delete':
				$result = hrb_delete_project( $project_id );
                $action_v = __( 'Deleted', APP_TD );
				break;

			case 'reopen';
				$result = hrb_reopen_project( $project_id );
                $action_v = __( 'Reopened', APP_TD );
				break;

			case 'archive';
				$user_id = get_current_user_id();
				$result = hrb_archive_project( $project_id, $user_id );
                $action_v = __( 'Archived', APP_TD );
				break;
		}

        if ( is_wp_error( $result ) ) {
            appthemes_add_notice( 'project-action-error', sprintf( __( 'Could not %s project. Please try again later.', APP_TD ), ucfirst ( $action_v ) ) );
            return false;
        } else {
            appthemes_add_notice( 'project-action-success', sprintf( __( 'Project was %s.', APP_TD ), ucfirst ( $action_v ) ), 'success' );
        }
        return true;
	}

}

/**
 * Handles proposals actions in the user dashboard.
 */
class HRB_User_Dashboard_Form_Proposals extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var('proposal_id');
		$wp->add_query_var('p_action');
	}

	function condition() {
		return (bool) ( get_query_var('p_action') && get_query_var('proposal_id')  && 'mb' == get_query_var('action') );
	}

	/**
	 * Validates the rules on an user action over a proposal in the dashboard.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_proposal_validate'
	 *
	 */
	function validate( $action, $proposal_id ) {

		if ( ! current_user_can( 'edit_bid', $proposal_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You don\'t have permissions to update this proposal.', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_dashboard_proposal_validate', APP_Notices::$notices, $action, $proposal_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function parse_query( $wp_query ) {

		$proposal_id = (int) get_query_var('proposal_id');
		$action = get_query_var('p_action');

		if ( ! $this->validate( $action, $proposal_id ) ) {
			return;
        }

        if ( ! $this->process( $action, $proposal_id ) ) {
            // there are errors
            return;
        }

		// redirect the user to the dashboard
		if ( 'cancel' == $action ) {
			wp_redirect( hrb_get_dashboard_url_for('proposals') );
			exit;
		}
    }

    function process( $action, $proposal_id ) {

        $result = null;
		switch ( $action ) {
			case 'cancel':
				$result = hrb_cancel_proposal( $proposal_id );
                $action_v = __( 'Canceled', APP_TD );
				break;
		}

		if ( $result !== null ) {

			if ( ! $result ) {
				appthemes_add_notice( 'proposal-action-error', sprintf( __( 'Could not %s proposal. Please try again later.', APP_TD ), ucfirst ( $action_v ) ) );
                return false;
            } else {
				appthemes_add_notice( 'proposal-action-success', sprintf( __( 'Proposal was %s.', APP_TD ), ucfirst ( $action_v ) ), 'success' );
            }

		}
        return true;
	}


}

/**
 * Handles order actions in the user dashboard.
 */
class HRB_User_Dashboard_Form_Payments extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var('order_id');
		$wp->add_query_var('p_action');
	}

	function condition() {
		return (bool) ( get_query_var('p_action') && get_query_var('order_id')  && 'mo' == get_query_var('action') );
	}

	/**
	 * Validates the rules on an user action over an Order in the dashboard.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_order_validate'
	 *
	 */
	function validate( $action, $order ) {

   		if ( ! $order ) {
			appthemes_add_notice( 'invalid-order', __( 'Invalid Order!', APP_TD ) );
			return false;
		}

		if ( ! current_user_can( 'cancel_order', $order->get_id() ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You don\'t have permissions to cancel this Order.', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_dashboard_order_validate', APP_Notices::$notices, $action, $order );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function parse_query( $wp_query ) {

		$order_id = (int) get_query_var('order_id');
		$action = get_query_var('p_action');

        $order = appthemes_get_order( $order_id );

		if ( ! $this->validate( $action, $order ) ) {
			return;
        }

        if ( ! $this->process( $action, $order ) ) {
            // there are errors
			return;
        }

    }

    function process( $action, $order ) {
		$success = null;
		switch ( $action ) {

			case 'cancel_order':
				hrb_cancel_order( $order );

                $success = false;
				if ( APPTHEMES_ORDER_FAILED == $order->get_status() ) {
					appthemes_add_notice( 'order-canceled', __( 'The Order was sucessfully canceled.', APP_TD ), 'success' );
					$success = true;
				} elseif ( APPTHEMES_ORDER_REFUNDED == $order->get_status() ) {
					appthemes_add_notice( 'order-refunded', __( 'The Order was sucessfully canceled and refunded.', APP_TD ), 'success' );
					$success = true;
				}
				break;

		}

		if ( $success !== null && ! $success  ) {
			appthemes_add_notice( 'unknown-error', __( 'There was an error updating the Order status. Please try again later.', APP_TD ) );
            return false;
        }
        return true;
	}

}

/**
 * Handle the notifications form in the user dashboard.
 */
class HRB_User_Dashboard_Form_Notifications extends APP_View {

	function condition() {
		return ( ! empty( $_POST['action'] ) && 'manage_notifications' == $_POST['action'] );
	}

	/**
	 * Validates the rules on a user action over a Notification in the dashboard.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_notification_validate'
	 *
	 */
	function validate() {

		if ( empty( $_POST['bulk_delete'] ) || empty( $_POST['notification_id'] ) ) {
			return false;
        }

		wp_verify_nonce('hrb_manage_notifications');

		APP_Notices::$notices = apply_filters( 'hrb_dashboard_notification_validate', APP_Notices::$notices );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	// handle the notifications form
	function parse_query( $wp_query ) {

		if ( ! $this->validate() ) {
			return;
        }

        if ( ! $this->process() ) {
            // there are errors
			return;
        }
    }

    function process() {

        $notifications_ids = array_map( 'appthemes_filter', $_POST['notification_id'] );

		$success = true;
		foreach( $notifications_ids as $notification_id ) {
			$success = appthemes_delete_notification( $notification_id );
		}

		if ( $success ) {
			appthemes_add_notice( 'delete-success', __( 'Selected notifications were deleted', APP_TD ), 'success' );
        } else {
			appthemes_add_notice( 'delete-error', __( 'Some notifications could not be deleted.', APP_TD ) );
        }

        return true;
	}

}

/**
 * Handle Project/Proposal agreement form.
 */
class HRB_User_Dashboard_Form_Agreement extends APP_View {

	function condition() {
		return (bool) ( ! empty( $_POST['action'] ) && 'proposal_agreement' == $_POST['action'] && ! empty( $_POST['proposal_id'] ) );
	}

	/**
	 * Validates the rules on a user action over a Proposal Agreement in the dashboard.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_agreement_validate'
	 *
	 */
	function validate( $proposal ) {

		if ( empty( $_POST['user_relation'] ) || ! in_array( $_POST['user_relation'], array( 'employer', 'candidate' ) ) ) {
			appthemes_add_notice( 'invalid-data', __( 'Invalid form data. Please try again.', APP_TD ) );
			return false;
		}

		if ( ! current_user_can( 'edit_agreement', $proposal->get_id() ) ) {
			appthemes_add_notice( 'no-edit-agreement', __( 'The agreement for this proposal is not editable.', APP_TD ) );
			return false;
		}

		wp_verify_nonce('hrb-proposal-agreement');

		APP_Notices::$notices = apply_filters( 'hrb_dashboard_agreement_validate', APP_Notices::$notices, $proposal );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	// handle the agreement form
	function parse_query( $wp_query ) {

		$proposal_id = (int) $_POST['proposal_id'];
		$proposal = hrb_get_proposal( $proposal_id );

		if ( ! $this->validate( $proposal ) ) {
			return;
        }

        if ( ! $this->process( $proposal ) ) {
            // there are errors
            return;
        }

		if ( 'candidate' == $_POST['user_relation'] ) {
			$redirect_to = 'proposals';
		} else {
			$redirect_to = 'projects';
		}

        wp_redirect( hrb_get_dashboard_url_for( $redirect_to ) );
        exit;
	}

	/**
	 * Validates the posted fields in a dashboard proposal Agreement form.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_agreement_validate_fields'
	 *
	 */
	function validate_fields( $proposal, $user, $user_relation ){

		APP_Notices::$notices = apply_filters( 'hrb_dashboard_agreement_validate_fields', APP_Notices::$notices, $proposal, $user, $user_relation );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
        return true;
    }

    function process( $proposal ) {

		$user_relation = $_POST['user_relation'];
		$user = wp_get_current_user();

        if ( ! $this->validate_fields( $proposal, $user, $user_relation ) ) {
            return false;
        }

        // employer selected a proposal
        if ( ! empty( $_POST['proposal_select'] ) ) {

            hrb_proposal_selected( $proposal );

            $candidate = get_userdata( $proposal->user_id );

            appthemes_add_notice( 'proposal-selected-1',  __( "Congratulations! You've just selected a candidate for your project.", APP_TD ), 'success' );
			appthemes_add_notice( 'proposal-selected-2', sprintf( __( "The project will only be officially assigned when you and '%s' accept working with each other.", APP_TD ), $candidate->display_name ), 'success' );

        }

        // handle negotiation and trigger actions if parties reach agreement
        $this->handle_negotiation( $proposal, $user_relation, $user );

        return true;
    }

	function handle_negotiation( $proposal, $user_relation, $user ) {
        call_user_func( array( $this, "negotiate_{$user_relation}" ), $proposal, $user );
		do_action( "hrb_proposal_negotiate_{$user_relation}", $proposal, $user );
	}

	/**
	 * Handles the negotiation data related with the employer.
	 */
	function negotiate_employer( $proposal, $user ) {

		$project_id = $proposal->get_post_ID();

		$data = wp_array_slice_assoc( $_POST, array( 'project_terms', 'employer_decision', 'employer_notes', 'employer_candidate_delete' ) );

		$sanitized = array_map( 'sanitize_text_field', $data );
		$sanitized = array_map( 'trim', $sanitized );

		extract( $sanitized );

		if ( ! empty( $project_terms ) ) {
			// store the project terms in post meta
			hrb_update_project_dev_terms( $project_id, $proposal, $project_terms );
		}

		// employer declined the agreement and want delete
		if ( ! empty( $employer_candidate_delete ) ) {
			$employer_decision = HRB_TERMS_CANCEL;
		}

		if ( ! empty( $employer_decision ) ) {

			if ( empty( $employer_notes ) ) {
				$employer_notes = '';
			}

			// update the proposal agreement related meta
			hrb_update_agreement_decision( $proposal, $user, $employer_decision, $employer_notes );

            // employer declined the agreement and want delete
            if ( HRB_TERMS_CANCEL == $employer_decision ) {

				hrb_agreement_cancel( $proposal, $user );

                appthemes_add_notice( 'proposal-canceled-employer', __( 'Candidate removed.', APP_TD ), 'success' );

            } else {

				$workspace_id = hrb_maybe_agree_terms( $proposal, $user, $employer_decision );
				$candidate_name = get_the_author_meta( 'display_name', $proposal->get_user_id() );

                if ( $workspace_id ) {

                    $workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), __( 'workspace', APP_TD ) );

                    appthemes_add_notice( 'agreement-1', sprintf( __( "Congratulations! Project '%s' has just been assigned to '%s'!", APP_TD ), $proposal->project->post_title, $candidate_name ), 'success' );
                    appthemes_add_notice( 'agreement-2', sprintf( __( "A new %s is now available on your Dashboard. From there you'll have access to the '%s' contact information and manage your Project.", APP_TD ), $workspace_link, $candidate_name ), 'success' );

					if ( hrb_is_escrow_enabled() ) {
						appthemes_add_notice( 'waiting-funds', __( '<strong>Important:</strong> You must first transfer funds to our escrow account. Work can only start after the funds have been transferred.', APP_TD ) );
						appthemes_add_notice( 'transfer-funds', sprintf( __( '<a href="%s">Transfer funds now</a> to activate the workspace.', APP_TD ), hrb_get_workspace_transfer_funds_url( $workspace_id ) ) );
					}

                } else {

					appthemes_add_notice( 'no-agreement', sprintf( __( "Your decision was sent to '%s'.", APP_TD ), $candidate_name ), 'success' );
                }

            }

		}

	}

	/**
	 * Handles the negotiation data related with the candidate.
	 */
	function negotiate_candidate( $proposal, $user ) {

		$project_id = $proposal->get_post_ID();

		$data = wp_array_slice_assoc( $_POST, array( 'proposal_terms', 'candidate_decision', 'candidate_notes', 'self_candidate_delete' ) );

		$sanitized = array_map( 'sanitize_text_field', $data );
		$sanitized = array_map( 'trim', $sanitized );

		extract( $sanitized );

		if ( ! empty( $proposal_terms ) ) {
			//hrb_update_candidate_terms( $p2p_id, $proposal_terms );
			hrb_update_proposal_dev_terms( $proposal, $proposal_terms );
		}

		// candidate declined the agreement and want delete
		if ( ! empty( $self_candidate_delete ) ) {
			$candidate_decision = HRB_TERMS_CANCEL;
		}

		if ( ! empty( $candidate_decision ) ) {

			if ( empty( $candidate_notes) ) {
				$candidate_notes = '';
			}

			// update the proposal agreement related meta
			hrb_update_agreement_decision( $proposal, $user, $candidate_decision, $candidate_notes );

            // candidate declined the agreement and want delete
            if ( HRB_TERMS_CANCEL == $candidate_decision ) {

				hrb_agreement_cancel( $proposal, $user );

                appthemes_add_notice( 'proposal-canceled-candidate', __( 'You are no longer a candidate for this Project.', APP_TD ), 'success' );

            } else {

				$workspace_id = hrb_maybe_agree_terms( $proposal, $user, $candidate_decision );
				$employer_name = get_the_author_meta( 'display_name', $proposal->project->post_author );

                if ( $workspace_id ) {

                    $workspace_link = html_link( hrb_get_workspace_url( $workspace_id ), __( 'workspace', APP_TD ) );

                    appthemes_add_notice( 'agreement-1', sprintf( __( "Congratulations! Project '%s', owned by '%s', has been assigned to you!", APP_TD ),  $proposal->project->post_title, $employer_name ), 'success' );
                    appthemes_add_notice( 'agreement-2', sprintf( __( "A new %s is now available on your Dashboard. From there you'll have access to the '%s' contact information and all the Project details.", APP_TD ), $workspace_link, $employer_name ), 'success' );

                } else {

                    appthemes_add_notice( 'no-agreement', sprintf( __( "Your decision was sent to '%s'.", APP_TD ), $employer_name ), 'success' );

                }

            }

		}

	}

}

/**
 * Handles the workspace form.
 */
class HRB_Workspace_Form_Manage extends APP_View {

	function condition() {
		return ( isset( $_POST['action'] ) && 'manage_project' == $_POST['action'] );
	}

	/**
	 * Validates the rules on an user action over a project within a dashboard Workspace.
	 *
	 * @uses apply_filters() Calls 'hrb_workspace_manage_validate'
	 *
	 */
	function validate( $workspace_id, $project_id, $user_id ) {

		if ( empty( $_POST['work_status'] ) && empty( $_POST['project_status'] ) ) {
			return false;
        }

		if ( HRB_PROJECT_STATUS_WORKING != get_post_status( $project_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'This project is not open for work.', APP_TD ) );
            return false;
        }

		if ( ! current_user_can( 'edit_workspace', $workspace_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not participating on this Project.', APP_TD ) );
            return false;
		}

		wp_verify_nonce('hrb-manage-project');

		APP_Notices::$notices = apply_filters( 'hrb_workspace_manage_validate', APP_Notices::$notices, $workspace_id, $project_id, $user_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function parse_query( $wp_query ) {

		$project_id = (int) $_POST['project_id'];
		$workspace_id = (int) $_POST['workspace_id'];
		$user_id = get_current_user_id();

		if ( ! $this->validate( $workspace_id, $project_id, $user_id ) ) {
			return;
        }

		if ( ! $this->process( $workspace_id, $project_id, $user_id ) ) {
            // there are errors
			return;
        }

    }

    function process( $workspace_id, $project_id, $user_id ) {
		global $hrb_options;

		$notes = '';

		if ( ! empty( $_POST['work_end_notes'] ) ) {
			$notes = $_POST['work_end_notes'];
		}

		elseif ( ! empty( $_POST['project_end_notes'] ) ) {
			$notes = $_POST['project_end_notes'];
		}

		$notes = sanitize_text_field( $notes );

		// participant
		if ( ! empty( $_POST['work_status'] ) ) {

			$work_status = sanitize_text_field( $_POST['work_status'] );

			hrb_p2p_update_participant_status( $workspace_id, $user_id, $work_status, $notes );

			appthemes_add_notice( 'work-status', sprintf( __( 'Your work status was updated to: %s', APP_TD ), hrb_get_participants_statuses_verbiages( $work_status ) ), 'success' );

		// employer
		} elseif ( ! empty( $_POST['project_status'] ) && in_array( $_POST['project_status'], hrb_get_project_work_ended_statuses() ) ) {

			$project_status = sanitize_text_field( $_POST['project_status'] );

			$updated = hrb_update_project_work_status( $workspace_id, $project_id, $project_status, $notes );

			if ( $updated ) {

				appthemes_add_notice( 'project-status', sprintf( __( 'The project status was updated to: %s', APP_TD ), hrb_get_project_statuses_verbiages( $project_status ) ), 'success' );

				if ( hrb_is_disputes_enabled() && hrb_is_workspace_status_disagreement( $workspace_id, $project_status ) ) {
					appthemes_add_notice( 'dispute-notice', hrb_get_possible_dispute_notice(), 'info' );
				}

			}

		}
		return true;
	}

}

/**
 * Handles the review form.
 */
class HRB_Workspace_Form_Review extends APP_View {

	function init() {
		add_filter( 'appthemes_review_post_redirect', array( __CLASS__, 'redirect' ), 10, 2 );
        add_filter( 'appthemes_handle_review', array( __CLASS__, 'handle_review' ) );
	}

	function condition() {
		return ( isset( $_POST['action'] ) && 'review_user' == $_POST['action'] && ! empty( $_POST['review_recipient_ID'] ) );
	}

	/**
	 * Validates the rules on an user action over a user review within a dashboard Workspace.
	 *
	 * @uses apply_filters() Calls 'hrb_workspace_user_review_validate'
	 *
	 */
	function validate( $workspace_id, $project_id ) {

		$recipient_id = (int) $_POST['review_recipient_ID'];

		if ( ! current_user_can( 'add_review', $project_id, $recipient_id, $workspace_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You cannot review this user.', APP_TD ) );
            return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_workspace_user_review_validate', APP_Notices::$notices, $workspace_id, $project_id, $recipient_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function parse_query( $wp_query ) {

		$project_id = (int) $_POST['comment_post_ID'];
		$workspace_id = (int) $_POST['workspace_id'];

		if ( ! $this->validate( $workspace_id, $project_id ) ) {
			return;
        }

		if ( ! $this->process( $project_id, $workspace_id ) ) {
            // there are errors
			return;
        }

        appthemes_add_notice( 'review-posted', __( 'Your review was posted succesfully!', APP_TD ), 'success' );

        wp_redirect( hrb_get_workspace_url( $workspace_id ) );
        exit;
	}

    function process( $project_id, $workspace_id ) {

        if ( ! $this->validate_fields( $workspace_id, $project_id ) ) {
            return false;
        }

		$comment = HRB_Proposal_Form_Edit::comments_post();
		if ( ! $comment ) {
			// there are errors, return to current page
			return false;
		}

        return true;
    }

	/**
	 * Validates the posted fields for a user Review form within a dashboard Workspace.
	 *
	 * @uses apply_filters() Calls 'hrb_workspace_user_review_validate_fields'
	 *
	 */
   static function validate_fields( $workspace_id, $project_id ) {

		$rating = ( isset( $_POST['review_rating'] ) ) ? trim( $_POST['review_rating'] ) : null;
		$content = ( isset( $_POST['comment'] ) ) ? trim( $_POST['comment'] ) : null;

		if ( ! $rating ) {
			appthemes_add_notice( 'no-rating', __( 'Please choose a star rating.', APP_TD ) );
		}

		if ( ! $content ) {
			appthemes_add_notice(  'no-content', __( 'Please write a review.', APP_TD ) );
		}

		 APP_Notices::$notices = apply_filters( "hrb_workspace_user_review_validate_fields", APP_Notices::$notices, $workspace_id, $project_id );
		 if ( APP_Notices::$notices->get_error_code() ) {
			 return false;
		 }
		 return true;
   }

	static function handle_review( $data ) {

		if ( ! isset( $_POST['action'] ) || 'review_user' != $_POST['action'] ) {
			return;
		}

		$rating = floatval( trim( $_POST['review_rating'] ) );
		$reviewee = (int) $_POST['review_recipient_ID'];

		$data = array(
			'rating' => $rating,
			'user_id' => $reviewee,
		);

		return $data;
	}

	static function redirect( $location, $review ) {
		appthemes_add_notice( 'review-submited', __( 'Review submitted.', APP_TD ), 'success' );

		return hrb_get_workspace_url_by( 'post_id', $review->comment_post_ID, $review->get_author_ID() );
	}

}

/**
 * Handles the dispute form.
 */
class HRB_Workspace_Form_Dispute extends APP_View {

	function condition() {
		return ( isset( $_POST['action'] ) && 'open_dispute' == $_POST['action'] );
	}

	/**
	 * Validates the rules on an user action over a user review within a dashboard Workspace.
	 *
	 * @uses apply_filters() Calls 'hrb_workspace_raise_dispute_validate'
	 *
	 */
	function validate( $workspace_id, $project_id ) {

		if ( ! current_user_can( 'open_dispute', $project_id, $workspace_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not allowed to open a dispute on this workspace.', APP_TD ) );
            return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_workspace_raise_dispute_validate', APP_Notices::$notices, $workspace_id, $project_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function parse_query( $wp_query ) {

		$project_id = (int) $_POST['project_id'];
		$workspace_id = (int) $_POST['workspace_id'];

		if ( ! $this->validate( $workspace_id, $project_id ) ) {
			return;
        }

		if ( ! $this->process( $workspace_id ) ) {
            // there are errors
			return;
        }

        appthemes_add_notice( 'dispute-opened-i', __( 'The dispute was opened succesfully!', APP_TD ), 'success' );
		appthemes_add_notice( 'dispute-opened-ii', __( 'A new communication channel for discussing the dispute is now available.', APP_TD ), 'success' );

        wp_redirect( hrb_get_workspace_url( $workspace_id ) );
        exit;
	}

    function process( $workspace_id ) {

        if ( ! $this->validate_fields( $workspace_id ) ) {
            return false;
        }

		$reason = esc_textarea( trim( $_POST['reason'] ) );

		$dispute = hrb_raise_dispute( $workspace_id, get_current_user_id(), $reason );
		if ( ! $dispute ) {
			// there are errors, return to current page
			appthemes_add_notice( 'error-opening-dispute', __( 'Could not open a dispute at this time. Please try again later. If the problem persists please contact us.', APP_TD ) );
			return false;
		}

        return true;
    }

	/**
	 * Validates the posted fields for a user Review form within a dashboard Workspace.
	 *
	 * @uses apply_filters() Calls 'hrb_workspace_raise_dispute_validate_fields'
	 *
	 */
   static function validate_fields( $workspace_id ) {

		$reason = ( isset( $_POST['reason'] ) ) ? trim( $_POST['reason'] ) : null;

		if ( ! $reason ) {
			appthemes_add_notice( 'no-reason', __( 'Please explain your reason for opening the dispute.', APP_TD ) );
		}

		 APP_Notices::$notices = apply_filters( "hrb_workspace_raise_dispute_validate_fields", APP_Notices::$notices, $workspace_id );
		 if ( APP_Notices::$notices->get_error_code() ) {
			 return false;
		 }
		 return true;
   }

}
