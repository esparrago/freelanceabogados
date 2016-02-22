<div class="row create-project purchase-plans">

	<div id="main" class="large-8 columns create-projects-section">
		<div class="form-wrapper checkout-process">

		<?php appthemes_display_form_progress(); ?>

		<?php
			$success = process_the_order();

			$order = get_order();

			$is_returning = isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], $order->get_gateway() );

			// redirect the user if the order completes
			if ( $success && ( in_array( $order->get_status(), array( APPTHEMES_ORDER_PAID, APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) || ( APPTHEMES_ORDER_PENDING == $order->get_status() && $is_returning ) ) ) {

				$redirect_to = get_post_meta( $order->get_id(), 'complete_url', true );

				if ( ! empty( $redirect_to ) ) {
					hrb_js_redirect( $redirect_to );
				}

			} elseif( APPTHEMES_ORDER_PENDING == $order->get_status() && $success !== NULL && ! $success ) {

				$text = html( 'p', __( 'There was a problem processing your order. Please try again later.', APP_TD ) );
				$text .= html( 'p', sprintf( __( 'If the problem persists, contact the site owner and refer your <strong>Order ID: %d</strong>', APP_TD ), $order->get_id() ) );

				// output sanitized link for previous page
				$location = wp_sanitize_redirect( $_SERVER['HTTP_REFERER'] );
				$location = wp_validate_redirect( $location, admin_url() );

				$text .= html( 'a', array( 'href' => $location ), __( '&#8592; Return', APP_TD ) );
				echo html( 'span', array( 'class' => 'redirect-text' ), $text );
			}

		?>

		</div>

	</div>

	<div id="sidebar" class="large-4 columns create-project-sidebar">
		<div class="sidebar-widget-wrap cf">
			<!-- dynamic sidebar -->
			<?php
				if ( $order->is_escrow() ) {
					$sidebar = 'hrb-transfer-funds' ;
				} else {
					$sidebar = 'hrb-create-project' ;
				}
			?>
			<?php dynamic_sidebar( $sidebar ); ?>
		</div><!-- end .sidebar-widget-wrap -->
	</div><!-- end #sidebar -->

</div>

