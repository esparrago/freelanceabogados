<div class="section-head">
	<h1><?php _e( 'Order Summary', APP_TD ); ?></h1>
</div>

<div class="order-summary">

	<?php
		the_order_summary();

		$order = get_order();

		if ( hrb_is_escrow_enabled() && $order->is_escrow() ) {
			$service = 'escrow';
		} else {
			$service = 'instant';
		}
	?>

	<form class="custom main" action="<?php echo appthemes_get_step_url(); ?>" method="POST">
		<fieldset>
			<p><?php _e( 'Please select a method for processing your payment:', APP_TD ); ?></p>
			<div class="row">
				<div class="large-12 columns">
					<?php appthemes_list_gateway_dropdown( 'payment_gateway', $recurring = false, array( 'service' => $service, 'input_extra_args' => array( 'class' => 'required medium' ) ) ); ?>
				</div>
			</div>
		</fieldset>

		<?php do_action('hrb_order_form_plan_select'); ?>

		<fieldset>
			<?php if ( $previous_step = appthemes_get_previous_step() ) : ?>
				<input class="button secondary previous-step" previous-step-url="<?php echo esc_url( appthemes_get_step_url( $previous_step ) ); ?>" value="<?php echo esc_attr( __( '&#8592; Previous Step', APP_TD ) ); ?>" type="submit" />
			<?php endif; ?>

			<input class="button" type="submit" value="<?php echo esc_attr( $bt_step_text ); ?>" />
		</fieldset>
	</form>

</div>
