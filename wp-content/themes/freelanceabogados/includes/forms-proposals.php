<?php
/**
 * Views for handling proposal related forms.
 *
 * Form-Views handle and process form data as well as user actions.
 *
 */

// @todo enable proposal upgrades (v.1.x)

/**
 * Dynamic Checkout for Edited Proposals.
 */
class HRB_Proposal_Form_Edit extends APP_Checkout_Step {

	public function __construct(){
		add_filter( 'appthemes_handle_bid', array( __CLASS__, 'handle_proposal' ) );

		$this->setup( 'edit-proposal', array(
			'register_to' => array( 'chk-edit-proposal' ),
	    ));

	}

	/**
	 * Loads/displays the proposal Edit template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_proposal_edit_template_vars'
	 *
	 */
	public function display( $order, $checkout ){

        // retrieve the proposal being edited
		$proposal_id = (int) get_query_var('proposal_edit');
		$proposal = hrb_get_proposal( $proposal_id );

		// add the required credits to edit a proposal, to the proposal object
		$proposal->_hrb_credits_required = hrb_required_credits_to('edit_proposal');

        $template_vars = array(
			'title'             => __( 'Edit Proposal', APP_TD ),
			'proposal'          => $proposal,
			'featured_disabled' => 'disabled',
			'action'            => 'edit_proposal',
			'form_action'       => add_query_arg( array( 'proposal_edit' => $proposal_id ), appthemes_get_step_url()  ),
            'bt_step_text'		=> __( 'Save Changes', APP_TD ),
		);
        $template_vars = apply_filters( 'hrb_proposal_edit_template_vars', $template_vars );

		appthemes_load_template( 'form-proposal.php', $template_vars );
	}

	/**
	 * Validates the rules for editing a proposal.
	 *
	 * @uses apply_filters() Calls 'hrb_proposal_edit_validate'
	 *
	 */
	function validate() {

		if ( ! isset( $_POST['action'] ) || 'edit_proposal' != $_POST['action'] ) {
			return false;
        }

		if ( ! $proposal_id = (int) get_query_var('proposal_edit') ) {
			return false;
        }

		if ( ! current_user_can( 'edit_bid', $proposal_id ) ) {
			appthemes_add_notice( 'cannot-edit-bid', __( 'You don\'t have permissions to edit this proposal', APP_TD ) );
			return false;
		}

		$credits_required = $this->calc_posted_proposal_required_credits( $_POST['action'] );

		if ( ! hrb_user_has_required_credits( $credits_required ) ) {
			appthemes_add_notice( 'no-credits', sprintf( __( 'Not enough credits to continue (%s credit(s) required). Please <a href="%s">purchase</a> some credits and try again.', APP_TD ), $credits_required, hrb_get_credits_purchase_url() ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_proposal_edit_validate', APP_Notices::$notices );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	public function process( $order, $checkout ) {

		if ( ! $this->validate() ) {
			return;
		}

        $comment_proposal = $this->update_proposal( $order, $checkout );
		if ( ! $comment_proposal ) {
			// there are errors, return to current page
			return;
		}

		// display notice to user
		appthemes_add_notice( 'proposal-edit-success', __( 'Your proposal was updated.', APP_TD ), 'success' );

		wp_redirect( get_permalink( $comment_proposal->comment_post_ID ) );
		exit;
	}

    function update_proposal( $order, $checkout ) {

        if ( ! $this->validate_fields( $order, $checkout ) ) {
            return false;
        }

        return self::comments_post();
    }

	/**
	 * Validates the posted fields on an Create/Edit proposal form.
	 *
	 * @uses apply_filters() Calls 'hrb_proposal_validate_fields'
	 *
	 */
	 function validate_fields( $order, $checkout ) {

		if ( empty( $_POST['comment'] ) ) {
			appthemes_add_notice( 'no-comment', __( 'Please describe your proposal.', APP_TD ) );
        }

		if ( empty( $_POST['amount'] ) || ! intval( $_POST['amount'] ) ) {
			appthemes_add_notice( 'no-amount', __( 'Please enter a valid proposal price.', APP_TD ) );
        }

		if ( empty( $_POST['delivery'] ) || ! intval( $_POST['delivery'] ) ) {
			appthemes_add_notice( 'no-delivery', __( 'Please enter a valid delivery value.', APP_TD ) );
        }

		if ( empty( $_POST['accept_site_terms'] ) ) {
			appthemes_add_notice( 'no-terms', __( 'You must accept the site terms.', APP_TD ) );
        }

        APP_Notices::$notices = apply_filters( 'hrb_proposal_validate_fields', APP_Notices::$notices, $order, $checkout );
        if ( APP_Notices::$notices->get_error_code() ) {
            return false;
        }
        return true;
	}

	/**
	 * Handle the proposal form data.
	 *
	 * @uses apply_filters() Calls 'hrb_proposal_handle_fields'
	 *
	 */
	static function handle_proposal( $data ) {

		if ( empty( $_POST['action'] ) || ! in_array( $_POST['action'], array( 'edit_proposal', 'new_proposal' ) ) ) {
			return false;
        }

		$amount = floatval( trim( $_POST['amount'] ) );
		$currency = $_POST['currency'];
		$action = $_POST['action'];

		// retrieve the form fields that need to be handled in the proposal form to later store as meta
		$fields = apply_filters( 'hrb_proposal_handle_fields', hrb_get_proposal_form_meta_fields() );

		foreach( $fields as $form_field => $meta_field ) {

			// sanitize the fields and retrieve them already prefixed to be stored in the proposal meta
			if ( isset( $_POST[ $form_field ] ) ) {
				$meta[ $meta_field ] = sanitize_text_field( $_POST[ $form_field ] );
			}

		}

		$data = array(
			'amount'	=> $amount,
			'currency'	=> $currency,
			'meta'		=> $meta,
		);
		return $data;
	}

	/**
	* Calculates the required credits for a proposal being submitted.
	*/
   function calc_posted_proposal_required_credits( $action ) {

	   $required_credits = 0;

	   switch ( $action ) {

		   case 'new-proposal':
			   $required_credits = hrb_required_credits_to('send_proposal');
			   break;

		   case 'edit-proposal':
			   $required_credits = hrb_required_credits_to('edit_proposal');
			   break;

	   }

	   if ( ! empty( $_POST['featured'] ) ) {
		   $required_credits += hrb_required_credits_to('feature_proposal');
	   }

	   return $required_credits;
   }

	/**
	  * Part of 'wp-comments-post.php'. Allows updating a custom comment type from the frontend since WP only allows comments editing from the backend.
	  * Instead of using 'wp-comment-post.php' in a form action, the form is handled by the theme by calling this function.
	  */
	static function comments_post() {

		$comment_post_ID = isset( $_POST['comment_post_ID'] ) ? (int) $_POST['comment_post_ID'] : 0;

		$comment_author = ( isset( $_POST['author'] ) ) ? trim( strip_tags( $_POST['author'] ) ) : null;
		$comment_author_email = ( isset( $_POST['email'] ) ) ? trim( $_POST['email'] ) : null;
		$comment_author_url = ( isset( $_POST['url'] ) ) ? trim( $_POST['url'] ) : null;
		$comment_content = ( isset( $_POST['comment'] ) ) ? trim( $_POST['comment'] ) : null;

		// If the user is logged in
		$user = wp_get_current_user();
		if ( $user->exists() ) {

			if ( empty( $user->display_name ) ) {
				$user->display_name = $user->user_login;
			}

			$user_ID = $user->ID;
			$comment_author = wp_slash( $user->display_name );
			$comment_author_email = wp_slash( $user->user_email );
			$comment_author_url = wp_slash( $user->user_url );

			if ( current_user_can( 'unfiltered_html' ) ) {
				if ( wp_create_nonce( 'unfiltered-html-comment_' . $comment_post_ID ) != $_POST['_wp_unfiltered_html_comment'] ) {
					kses_remove_filters(); // start with a clean slate
					kses_init_filters(); // set up the filters
				}
			}

		} else {
			// user is not logged ing
			return false;
		}

		$comment_type = '';

		$comment_parent = isset( $_POST['comment_parent'] ) ? absint( $_POST['comment_parent'] ) : 0;

		$commentdata = compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID' );

		$comment_id = isset( $_POST['comment_ID'] ) ? (int) $_POST['comment_ID'] : 0;

		if ( !$comment_id ) {
			$comment_id = wp_new_comment( $commentdata );
		} else {
			$commentdata['comment_ID'] = $comment_id;
			$comment = wp_update_comment( $commentdata );
		}

		$comment = get_comment( $comment_id );

		return $comment;
	}

}

/**
 * Dynamic Checkout for New proposals.
 */
class HRB_Proposal_Form_Create extends HRB_Proposal_Form_Edit {

	public function __construct(){
		$this->setup( 'create-proposal', array(
			'title' => __( 'Proposal Details', APP_TD ),
			'register_to' => array( 'chk-create-proposal' ),
		));
	}

	/**
	 * Loads/displays the proposal Create template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_proposal_create_template_vars'
	 *
	 */
	public function display( $order, $checkout ){

		$project_id = (int) get_query_var('project_id');
		$proposal = $this->default_proposal_to_edit( $project_id );

		// add the required credits to post a proposal, to the proposal object
		$proposal->_hrb_credits_required = hrb_required_credits_to('send_proposal');

        $template_vars = array(
			'title'             => __( 'Apply to Project', APP_TD ),
			'proposal'          => $proposal,
			'featured_disabled' => false,
			'action'            => 'new_proposal',
			'form_action'       => add_query_arg( array( 'project_id' => $project_id ), appthemes_get_step_url() ),
            'bt_step_text'		=> __( 'Submit', APP_TD ),
		);
        $template_vars = apply_filters( 'hrb_proposal_create_template_vars', $template_vars );

		appthemes_load_template( 'form-proposal.php', $template_vars );
	}

	/**
	 * Validates the rules for creating a proposal.
	 *
	 * @uses apply_filters() Calls 'hrb_proposal_create_validate'
	 *
	 */
	function validate() {

		if ( ! isset( $_POST['action'] ) || 'new_proposal' != $_POST['action'] ) {
			return false;
        }

		$project_id = (int) get_query_var('project_id');

		if ( ! current_user_can('edit_bids') ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not allowed to apply to projects.', APP_TD ) );
			return false;
		}

		if ( ! current_user_can( 'add_bid', $project_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You cannot apply to this project.', APP_TD ) );
			return false;
		}

		$credits_required = $this->calc_posted_proposal_required_credits( $_POST['action'] );

		if ( ! hrb_user_has_required_credits( $credits_required ) ) {
			appthemes_add_notice( 'no-credits', sprintf( __( 'Not enough credits to continue (%s credit(s) required). Please <a href="%s">purchase</a> some credits and try again.', APP_TD ), $credits_required, hrb_get_credits_purchase_url() ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_proposal_create_validate', APP_Notices::$notices );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	public function process( $order, $checkout ){

		if ( ! $this->validate() ) {
            // there are errors
			return;
        }

        $comment_proposal = $this->update_proposal( $order, $checkout );
		if ( ! $comment_proposal ) {
			// there are errors, return to current page
			return;
		}

		// get a 'proposal' instance from the comment object
		$proposal = hrb_get_proposal( $comment_proposal->comment_ID );

		// display notice to user
		if ( ! $proposal->is_approved() ) {
			appthemes_add_notice( 'proposal-sent', __( 'Your proposal was sent and is waiting moderation.', APP_TD ), 'success' );
		} else {
			appthemes_add_notice( 'proposal-sent', __( 'Your proposal was sent.', APP_TD ), 'success' );
		}

		wp_redirect( get_permalink( $proposal->get_post_ID() ) );
		exit;
	}

    /**
     * Retrieves a proposal with default properties to populate the new proposal form.
     */
	protected function default_proposal_to_edit( $project_id ) {

		$proposal = new stdClass();

		$proposal->id = 0;

        // assign the parent project
		$proposal->project = hrb_get_project( $project_id );

		$avg_bids = appthemes_get_post_avg_bid( $project_id );
		$avg_delivery = hrb_get_post_avg_proposal_delivery( $project_id );

		$defaults = array(
			// native 'bid' meta
			'amount'			=> $avg_bids ? $avg_bids : '',
			'comment_content'	=> '',
			// custom meta
			'_hrb_delivery'	=> $avg_delivery ? $avg_delivery : '',
		);

		foreach ( $defaults as $key => $default ) {
			$proposal->$key = _hrb_posted_field_value( $key, $default );
		}

		if ( $comment_content = _hrb_posted_field_value('comment') ) {
			$proposal->comment_content = $comment_content;
		}


		// assign budget related labels to the proposal, based on the project budget type
		$budget_labels = hrb_get_proposal_budget_labels( $proposal->project->_hrb_budget_type );

		foreach ( $budget_labels as $field => $label ) {
			$proposal->$field = $label;
		}

		return $proposal;
	}
}
