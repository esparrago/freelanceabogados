<?php
/**
 * Mirrors WordPress template tag functions (the_post(), the_content(), etc), used in the Loop .
 *
 * Altough some function might not be used in a WP Loop, they intend to work in the same way as to be self explanatory and retrieve data intuitively.
 *
 * Contains template tag functions for: transaction (post type)
 *
 */


### Actions

/**
 * Retrieves the URL for paying an existing pending order.
 */
function hrb_get_the_order_purchase_url( $order ) {
	$post = hrb_get_order_post( $order );

	if ( empty( $post ) ) {
		return;
	}

	if ( APPTHEMES_ORDER_PTYPE == $post->post_type ) {
		$url = hrb_get_credits_purchase_url();
	} else {
		$url = get_the_hrb_project_create_url( $post->ID );
	}

	return $url;
}

/**
 * Retrieves the URL for canceling an order.
 */
function hrb_get_the_order_cancel_url( $order ) {
	$args = array(
		'action' => 'mo',
		'p_action' => 'cancel_order',
		'order_id' => $order->get_id()
	);
	return add_query_arg( $args, hrb_get_dashboard_url_for( 'payments' ) );
}

/**
 * Retrieves the available list of actions for an order considering the order current status.
 */
function get_the_hrb_order_actions( $order ) {

	$actions = array();

	// @todo: use capabilities

	// if the order is not paid make it 'payable'
	if ( ! empty( $order ) && APPTHEMES_ORDER_PENDING == $order->get_status() ) {

		$order_data = hrb_get_order_plan_data( $order );

		if ( $order->is_escrow() ) {

			$item = $order->get_item();

			$actions['pay'] = array(
				'title' => __( 'Transfer Funds', APP_TD ),
				'href' => hrb_get_workspace_transfer_funds_url( $item['post_id'] ),
			);

		} else {

			extract( $order_data );

			// make it payable if applicable
			if ( ( HRB_PRICE_PLAN_PTYPE == $plan->post_type && hrb_charge_listings() ) || ( HRB_PROPOSAL_PLAN_PTYPE == $plan->post_type && hrb_credits_enabled() ) ) {

				$actions['pay'] = array(
					'title' => __( 'Pay', APP_TD ),
					'href' => hrb_get_the_order_purchase_url( $order ),
				);

			}

		}
	}

	if ( current_user_can( 'cancel_order', $order->get_id() ) ) {

		// if the order is pending/paid (escrow) make it 'canceable'
		if ( APPTHEMES_ORDER_PAID == $order->get_status() ) {

			$actions['cancel_order'] = array(
				'title' => __( 'Cancel (Get Refund)', APP_TD ),
				'href' => hrb_get_the_order_cancel_url( $order ),
				'onclick' => 'return confirm("' . __( 'This will issue a refund and work will not be paid.', APP_TD ) . '\r\n\r\n' . __( 'Are you sure you want to cancel?', APP_TD ) . '");',
			);

		} elseif ( APPTHEMES_ORDER_PENDING == $order->get_status() ) {

				$actions['cancel_order'] = array(
					'title' => __( 'Cancel', APP_TD ),
					'href' => hrb_get_the_order_cancel_url( $order ),
					'onclick' => 'return confirm("' . __( 'Are you sure you want to cancel?', APP_TD ) . '");',
			);

		}

	}

	return $actions;
}

/**
 * Outputs the formatted available list of actions for an order.
 */
function the_hrb_order_actions( $order, $text = '' ) {

	$actions = get_the_hrb_order_actions( $order );

	if ( empty( $actions ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Actions', APP_TD );
	}

	the_hrb_data_dropdown( $actions, array( 'data-dropdown' => "actions-{$order->get_id()}" ), $text );
}


### Other

/**
 * Outputs the formatted item title for an order.
 */
function the_hrb_order_item_title( $order, $before = '', $after = '' ) {

	$post = hrb_get_order_post( $order );
	if ( ! $post ) {
		return;
	}

	$atts = array(
		'class' => 'order-item-title',
	);

	if ( APPTHEMES_ORDER_PTYPE == $post->post_type ) {
		$html = html( 'span', $atts, __( 'Plan', APP_TD ) );
	} else {
		$atts['href'] = get_permalink( $post->ID );
		$html = html( 'a', $atts, $post->post_title );
	}

	echo $before . $html . $after;
}

/**
 * Outputs the formatted gateway description for an order.
 */
function the_hrb_order_gateway( $order, $before = '', $after = '' ) {
	$gateway_id = $order->get_gateway();

	if ( ! empty( $gateway_id ) ) {

		$gateway = APP_Gateway_Registry::get_gateway( $gateway_id );

		if ( $gateway ) {
			$gateway = $gateway->display_name('admin');
		} else {
			$gateway = __( 'Unknown', APP_TD );
		}

	} else {

		$gateway = __( 'Gateway N/A', APP_TD );

	}

	echo $before . $gateway . $after;
}

/**
 * Retrieves the current status nice name for an order.
 */
function the_hrb_order_status( $order ) {

	if ( APPTHEMES_ORDER_PENDING == $order->get_status() && ! $order->get_gateway() ) {
		echo __( 'Draft Payment', APP_TD );
	} else {
		echo hrb_get_order_statuses_verbiages( $order->get_status(), $order );
	}

}

/**
 * Retrieves the admin URL for a given order ID.
 *
 * @since 1.2
 */
function hrb_get_order_admin_url( $order_id ) {
	return add_query_arg( array( 'post' => $order_id, 'action' => 'edit' ), network_admin_url( 'post.php' ) );
}
