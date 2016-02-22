<div class="section-head">
	<h1><?php _e( 'Order Summary', APP_TD ); ?></h1>
</div>

<div class="order-summary">

	<fieldset>

		<?php the_order_summary(); ?>

		<div class="large-12 columns">

			<p><?php _e( 'Thank You!', APP_TD ); ?></p>

			<?php if ( ! in_array( $order->get_status(), array( APPTHEMES_ORDER_PAID, APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ):  ?>

				<?php if ( get_query_var('bt_end') ): ?>

						<p><?php echo __( 'Your Order is pending payment. After payment clears it will become available.', APP_TD ); ?>

				<?php else: ?>

						<p><?php echo __( 'Your Order is still being processed.', APP_TD ); ?>
						<p><?php echo sprintf( __( 'If it does not complete soon, please contact us and refer to your <strong>Order ID - #%d</strong>.', APP_TD ), $order->get_id() ); ?>

				<?php endif ?>

			<?php else: ?>

				<?php if ( $order->is_escrow() ): ?>

						<p><?php echo __( 'Funds were transferred succesfully.', APP_TD ); ?></p>
						<p><?php echo __( 'The workspace for this project is now active and work can start.', APP_TD ); ?></p>

				<?php else:  ?>

					<p><?php _e( 'Your Order has been completed!', APP_TD ); ?></p>

				<?php endif; ?>

			<?php endif; ?>
		</div>

		<?php do_action('hrb_order_summary'); ?>

	</fieldset>

	<fieldset>
		<input type="submit" class="button" value="<?php echo esc_attr( $bt_step_text ); ?>" onClick="location.href='<?php echo esc_url( $url ); ?>';return false;">
	</fieldset>

</div>

