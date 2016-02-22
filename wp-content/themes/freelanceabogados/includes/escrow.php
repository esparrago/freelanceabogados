<?php
/**
 * Functions related with escrow orders and their p2p relations with workspaces.
 */

add_action( 'admin_init', '_hrb_init_escrow_metaboxes', 15 );

add_action( 'appthemes_transaction_failed', 'hrb_cancel_escrow_order_workspace', 15 );
add_action( 'appthemes_transaction_refunded', 'hrb_cancel_escrow_order_workspace', 15 );

add_action( 'appthemes_transaction_paid', 'hrb_activate_escrow_order_workspace', 15 );

add_action( 'hrb_dashboard_project_workspace_actions', '_hrb_workspace_escrow_actions', 10, 3 );

// workspace status transition actions
add_action( 'waiting_funds_workspace', 'hrb_create_escrow_order', 10, 2 );

// canceled/closed as incomplete workspaces with work complete - disagreement
add_action( 'hrb_workspace_ended_disagreement_canceled', '_hrb_escrow_maybe_delay_refund' );
add_action( 'hrb_workspace_ended_disagreement_closed_incomplete', '_hrb_escrow_maybe_delay_refund' );

// ended workspaces with any status with work complete/incomplete - agreement
add_action( 'hrb_workspace_ended_agreement_canceled', 'hrb_workspace_refund_author' );
add_action( 'hrb_workspace_ended_agreement_closed_incomplete', 'hrb_workspace_refund_author' );
add_action( 'hrb_workspace_ended_agreement_closed_complete', 'hrb_workspace_pay_participants' );


### Hook callbacks

/**
 * Retrieve the URL for transferring funds.
 *
 * @since 1.2
 */
function hrb_get_workspace_transfer_funds_url( $workspace_id ) {

	$order = appthemes_get_order_connected_to( $workspace_id );

	if ( ! $order || APPTHEMES_ORDER_PENDING != $order->get_status() ) {
		return;
	}

	return add_query_arg( array( 'oid' => $order->get_id() ), hrb_get_transfer_funds_url() );
}

/**
 * Enqueues escrow related actions to the workspace list of user actions.
 *
 * @since 1.2
 */
function _hrb_workspace_escrow_actions( $actions, $workspace_id, $proposal ) {

	if ( ! hrb_is_escrow_enabled() ) {
		return $actions;
	}

	$order = appthemes_get_order_connected_to( $workspace_id );

	if ( ! $order || APPTHEMES_ORDER_PENDING != $order->get_status() ) {
		return $actions;
	}

	$workspace = get_post( $workspace_id );

	if ( get_current_user_id() != $workspace->post_author ) {
		return $actions;
	}

	$order = appthemes_get_order_connected_to( $workspace_id );

	if ( ! $order ) {
		return $actions;
	}

	$actions["transfer_funds"] = array(
		'title' => __( 'Transfer Funds', APP_TD ),
		'href' => hrb_get_workspace_transfer_funds_url( $workspace_id ),
	);

	$actions["cancel"] = array(
		'title' => __( 'Cancel', APP_TD ),
		'href' => hrb_get_the_order_cancel_url( $order ),
		'onclick' => 'return confirm("' . __( 'Workspace will be canceled and the related Order marked as failed. Are you sure?', APP_TD ) . '");',
	);

	return $actions;
}

/**
 * Creates a new escrow order for a newly created workspace.
 *
 * @since 1.2
 */
function hrb_create_escrow_order( $workspace_id, $workspace ) {

	if ( appthemes_get_order_connected_to( $workspace_id ) ) {
		return;
	}

	$participants = hrb_p2p_get_workspace_participants( $workspace_id )->results;
	if ( empty( $participants ) ) {
		return;
	}

	$amount = $total_amount = 0;

	$order = appthemes_new_escrow_order();

	// make sure the order is assigned to the workspace author
	$order->set_author( $workspace->post_author );

	foreach( $participants as $worker ) {
		$proposal = hrb_get_proposal( $worker->proposal_id );
		$amount = hrb_get_the_user_proposal_total_amount( $proposal );
		$total_amount += $amount;

		$order->add_receiver( $worker->ID, $amount );
	}

	$order->add_item( HRB_WORKSPACE_PTYPE, $total_amount, $workspace_id );
	$order->set_currency( $proposal->currency );
	$order->set_description( sprintf( __( "Funds Transfer for '%s'", APP_TD ), $workspace->post_title ) );
}

/**
 * Activates the workspace attached to a given order.
 *
 * @since 1.2
 */
function hrb_activate_escrow_order_workspace( $order ) {

	if ( ! $order->is_escrow() ) {
		return;
	}

	$item = $order->get_item();

	$project = hrb_p2p_get_workspace_post( $item['post_id'] );

	hrb_activate_workspace( $item['post_id'], $project->ID );
}

/**
 * Cancels the workspace attached to a given order.
 *
 * @since 1.2
 */
function hrb_cancel_escrow_order_workspace( $order ) {

	if ( ! $order->is_escrow() ) {
		return;
	}

	$item = $order->get_item();

	$project = hrb_p2p_get_workspace_post( $item['post_id'] );

	hrb_cancel_workspace( $item['post_id'], $project->ID );
}

/**
 * Retrieves the URL for purchasing credits spendable in proposals.
 *
 * @since 1.2
 */
function hrb_get_transfer_funds_url() {
	return get_permalink( HRB_Escrow_Transfer::get_id() );
}

### Helper functions

/**
 * Check if escrow is enabled by looking for active escrow gateways and checking if the main escrow setting is enabled.
 *
 * @since 1.2
 */
function hrb_is_escrow_enabled() {
	global $hrb_options;

	return $hrb_options->escrow['enabled'];
}

/**
 * Issue a refund on a given workspace if funds are currently held in escrow.
 *
 * @uses apply_filters() Calls 'hrb_workspace_refund_author'
 *
 * @since 1.2
 */
function hrb_workspace_refund_author( $workspace_id ) {

	$order = appthemes_get_order_connected_to( $workspace_id );

	if ( ! $order || ! $order->is_escrow() || APPTHEMES_ORDER_PAID != $order->get_status() ) {
		return;
	}

	$allow = apply_filters( 'hrb_workspace_refund_author', true, $workspace_id, $order );

	if ( $allow ) {
		$order->refunded();
	}
}

/**
 * 	Process refunds immediately or delay them if disputes are enabled.
 *
 * @uses apply_filters() Calls 'hrb_escrow_delay_refund'
 * 
 * @since 1.3
 */
function _hrb_escrow_maybe_delay_refund( $id ) {

	$delay_refund = apply_filters( 'hrb_escrow_delay_refund', hrb_is_disputes_enabled() );

	if ( ! $delay_refund ) {
		hrb_workspace_refund_author( $id );
	}
}

/**
 * Issue a payment for the receivers of funds held in escrow for a given workspace.
 *
 * @uses apply_filters() Calls 'hrb_workspace_pay_participants'
 *
 * @since 1.2
 */
function hrb_workspace_pay_participants( $workspace_id ) {

	$order = appthemes_get_order_connected_to( $workspace_id );

	if ( ! $order || ! $order->is_escrow() || APPTHEMES_ORDER_PAID != $order->get_status() ) {
		return;
	}

	$allow = apply_filters( 'hrb_workspace_pay_participants', true, $workspace_id, $order );

	if ( $allow ) {
		$order->complete();
	}

}

/**
 * Check if the participants of a given workspace have the necessary gateway fields filled out in order to be paid when work is complete.
 *
 * @since 1.2
 */
function hrb_escrow_receivers_gateway_fields_valid( $workspace_id, $gateway_id = '' ) {

	$participants = hrb_p2p_get_workspace_participants( $workspace_id );
	$participants = $participants->results;

	$values = array();

	foreach( $participants as $participant ) {
		$values = array_merge( $values, hrb_escrow_receiver_gateway_fields_valid( $participant->ID, $gateway_id ) );
	}
	return $values;
}

/**
 * Check if a specific user has the necessary gateway fields filled out in order to be paid when work is complete.
 *
 * @since 1.2
 */
function hrb_escrow_receiver_gateway_fields_valid( $user_id, $gateway_id = '' ) {

	$fields = APP_Escrow_Settings_Form::get_fields( $gateway_id );

	$values = array();

	foreach( $fields as $field ) {
		$value = get_user_option( $field['name'], $user_id );
		if ( $value ) {
			$values[ $field['name'] ] = $value;
		}
	}

	return $values;
}

/**
 * Checks if a given workspace is pending funds before work can start.
 *
 * @since 1.2
 */
function hrb_workspace_is_pending_payment( $workspace_id = 0 ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	$order = appthemes_get_order_connected_to( $workspace_id );

	if ( ! $order || APPTHEMES_ORDER_PAID != $order->get_status() ) {
		return false;
	}
	return true;
}


### Admin Metaboxes

/**
 * Displays a custom meta box on escrow order pages showing additional information.
 *
 * @since 1.2
 */
function _hrb_init_escrow_metaboxes() {

	/**
	 * Provides an additional meta box with escrow details to escrow orders.
	 *
	 * @since 1.2.0
	 *
	 * @package Components\Payments\Escrow\PayPal
	 */
	class HRB_Escrow_Order_Meta_Box extends APP_Meta_Box {

		/**
		 * Sets up the meta box with WordPress
		 */
		function __construct(){

			if ( ! isset( $_GET['post'] ) ) {
				return;
			}

			$order = appthemes_get_order( (int) $_GET['post'] );

			if ( ! $order || ! $order->is_escrow() ) {
				return;
			}

			parent::__construct( 'escrow-details', __( 'Work / Participants', APP_TD ), APPTHEMES_ORDER_PTYPE, 'side' );
		}

		/**
		 * Displays specific details for PayPal Adaptive escrow orders
		 *
		 * @param object $post WordPress Post object
		 */
		function display( $post ) {

			$order = appthemes_get_order( $post->ID );
			$workspace = $order->get_item( HRB_WORKSPACE_PTYPE );

			$args = array(
				'connected_meta' => array( 'type' => array( 'worker', 'employer', 'reviewer' ) ),
			);

			$participants = hrb_p2p_get_workspace_participants( $workspace['post_id'], $args )->results;

			if ( empty( $participants ) ) {
				echo __(  'N/A', APP_TD );
				return;
			}
	?>
			<table id="admin-escrow-order-details">
				<tbody>
					<tr>
						<th><?php _e( 'Status', APP_TD ); ?>: </th>
						<td><?php echo hrb_get_project_statuses_verbiages( $workspace['post']->post_status ); ?> </td>
					</tr>
					<tr><td colspan="2">&nbsp;</td></tr>
					<?php foreach( $participants as $participant ) : ?>
						<tr>
							 <?php if ( $participant->ID == $post->post_author ): ?>
								<th><?php _e( 'Owner', APP_TD ); ?>: </th>
							<?php else: ?>
								<th><?php echo ( 'reviewer' == $participant->type ? __( 'Reviewer', APP_TD ) : __( 'Participant', APP_TD ) ); ?>: </th>
							<?php endif; ?>
							<td class="participant"><?php echo sprintf( '<a href="%1$s">%2$s</a>', get_the_hrb_user_profile_url( $participant ), $participant->display_name ); ?></td>

						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<div class="clear"></div>
	<?php
		}

	}

	new HRB_Escrow_Order_Meta_Box;
}

