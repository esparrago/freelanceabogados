<?php
	$vars = _hrb_get_order_summary_template_vars( $order );

	// when user clicks 'continue' show the order summary instead of the bank details again
	if ( get_query_var('bt_end') ) {
		appthemes_load_template( 'order-summary-content.php', $vars );
		return;
	}
?>

<div class="section-head">
	<h1><?php _e( 'Bank Transfer', APP_TD ); ?></h1>
</div>

<form id="bank-transfer" class="custom">

	<?php if ( ! empty( $hrb_options->gateways['bank-transfer']['message'] ) ): ?>

		<fieldset>
			<div class="featured-head"><h3><?php _e( 'Instructions', APP_TD ); ?></h3></div>

			<div class="content">
				<p><?php echo apply_filters( 'the_content', $hrb_options->gateways['bank-transfer']['message'] ); ?></p>
			</div>
		</fieldset>

	<?php endif; ?>

	<?php do_action('hrb_order_form_bank_transfer'); ?>

	<fieldset>
		<div class="featured-head">
			<h3><?php _e( 'Order Information', APP_TD ); ?></h3>
		</div>

		<div class="content">

			<div class="row collapse field-preview">
				<div class="large-2 columns">
					<span><strong><?php _e( 'Order ID:', APP_TD ); ?></strong></span>
				</div>
				<div class="large-10 columns">
					<span><?php echo $order->get_id(); ?></span>
				</div>
			</div>
			<div class="row collapse field-preview">
				<div class="large-2 columns">
					<span><strong><?php _e( 'Order Total:', APP_TD ); ?></strong></span>
				</div>
				<div class="large-10 columns">
					<span><?php echo appthemes_get_price( $order->get_total(), $order->get_currency() ); ?></span>
				</div>
			</div>

			<div class="row collapse field-preview">
				<div class="large-12 columns">
					<p><?php echo sprintf( __( 'For questions or problems, please contact us directly at <a href="mailto:%s">%s</a>', APP_TD ), get_option('admin_email'), get_option('admin_email') ); ?></p>
				</div>
			</div>

		</div>
	</fieldset>

	<fieldset>
		<!--<input type="submit" class="button" value="<?php echo esc_attr( $vars['bt_step_text'] ); ?>" onClick="location.href='<?php echo esc_url( $vars['url'] ); ?>';return false;">-->
		<input type="submit" class="button" value="<?php _e( 'Continue', APP_TD ); ?>"  onClick="location.href='<?php echo esc_attr( add_query_arg( array( 'bt_end' => 1 ), appthemes_get_step_url( 'order-summary' ) ) ); ?>';return false;">
	</fieldset>
</form>