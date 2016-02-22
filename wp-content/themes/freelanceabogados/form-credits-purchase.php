<div class="section-head">
	<h1><?php _e( 'Select a Plan', APP_TD ); ?></h1>
</div>

<form id="purchase-credits" method="POST" class="custom main" action="<?php echo appthemes_get_step_url(); ?>">
	<fieldset>
		<?php if ( ! empty( $plans ) ): ?>

			<?php foreach( $plans as $key => $plan ): ?>

					<fieldset>
						<div class="plan">
							<div class="content">
								<div class="row">
									<div class="title large-12 columns">
										<h3><?php echo $plan['title']; ?></h3>
									</div>
								</div>
								<div class="row">
									<div class="description large-12 columns">
										<p class="description"><?php echo $plan['description']; ?></p>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="price-box large-12 columns">
									<input name="plan" type="radio" <?php echo ($key == 0) ? 'checked="checked"' : ''; ?> value="<?php echo $plan['post']->ID; ?>" />
									<span class="price">
										<?php echo sprintf( '%d %s / %s', $plan['credits'], _n( 'Credit', 'Credits', $plan['credits'] ), appthemes_get_price( $plan['price'] ) ); ?>
									</span>
								</div>
							</div>
						</div>
					</fieldset>

			<?php endforeach; ?>

		<?php else: ?>

			<em><?php _e( 'No Plans are currently available. Please try again later.', APP_TD ); ?></em>

		<?php endif; ?>
	</fieldset>

	<?php do_action( 'hrb_credits_form_purchase' ); ?>

	<fieldset>
		<?php do_action( 'appthemes_purchase_fields', HRB_PROPOSAL_PLAN_PTYPE ); ?>

		<input type="hidden" name="action" value="purchase-credits">

		<?php if ( $previous_step = appthemes_get_previous_step() ) : ?>
			<input class="button secondary previous-step" previous-step-url="<?php echo esc_url( appthemes_get_step_url( $previous_step ) ); ?>" value="<?php echo esc_attr( '&#8592; Back', APP_TD ); ?>" type="submit" />
		<?php endif; ?>

		<?php if ( !empty( $plans ) ){ ?>
			<input class="button" type="submit" value="<?php echo esc_attr( $bt_step_text ); ?>" />
		<?php } ?>

	</fieldset>

</form>
