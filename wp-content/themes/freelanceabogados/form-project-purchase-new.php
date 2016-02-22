<div class="section-head">
	  <h1><?php _e( 'Select a Plan', APP_TD ); ?></h1>
</div>

<form id="purchase-project" method="POST" class="custom main" action="<?php echo appthemes_get_step_url(); ?>">
	<fieldset>
		<?php if ( !empty( $plans ) ): ?>

			<?php foreach( $plans as $key => $plan ): ?>

					<?php
						$selected_addons = $sel_addons;

						if ( $plan['post']->ID != $sel_plan ) {
							$selected_addons = array();
						}
					?>

					<fieldset>
						<div class="plan">
							<div class="content">
								<div class="row">
									<div class="title large-12 columns">
										<h3><?php echo $plan['title'] . ( $is_relist ? ' ' . __( '(Relist)', APP_TD ) : '' ); ?></h3>
									</div>
								</div>
								<div class="row">
									<div class="description large-12 columns">
										<p class="description"><?php echo $plan['description']; ?></p>
									</div>
								</div>
								<div class="row">
									<div class="featured-options large-12 columns">
										<?php if ( ! hrb_addons_available( $plan, HRB_PROJECTS_PTYPE ) ) : ?>

											<h5><?php _e( 'Addons are not available for this price plan.', APP_TD ); ?></h5>

										<?php else: ?>

											<h5><?php _e( 'Please choose any additional options for your Project:', APP_TD ); ?></h5>

											<?php foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) : ?>
													<div class="featured-option <?php echo esc_attr( "plan_id-{$plan['post']->ID}" ) ?>">
														<?php if ( $project_id && hrb_project_has_addon( $project_id, $addon ) ): ?>
															<label><?php _hrb_output_purchased_addon( $addon, $plan['post']->ID, $project_id ); ?></label>
														<?php else: ?>
															<label><?php _hrb_output_purchaseable_addon( $addon, $plan['post']->ID, $selected_addons ); ?></label>
														<?php endif; ?>
													</div>
											<?php endforeach; ?>

										<?php endif; ?>
									</div>
								</div>
							</div>
							<div class="row">
								<div class="price-box large-12 columns">
									<input name="plan" type="radio" <?php echo $plan['post']->ID == $sel_plan ? 'checked="checked"' : ''; ?> value="<?php echo $plan['post']->ID; ?>" />
									<span class="price">

										<?php if ( $is_relist ): ?>

											<?php appthemes_display_price( $plan['relist_price'] ); ?>

										<?php else: ?>

											<?php appthemes_display_price( $plan['price'] ); ?>

										<?php endif; ?>

										<?php if( $plan['duration'] != 0 ){ ?>
											<?php printf( _n( 'for <br /> %s day', 'for %s days', $plan['duration'], APP_TD ), $plan['duration'] ); ?>
										<?php }else{ ?>
											<?php _e( 'Unlimited days', APP_TD ); ?>
										<?php } ?>
									</span>
								</div>
							</div>
						</div>
					</fieldset>

			<?php endforeach; ?>

		<?php else: ?>

			<em><?php _e( 'No Plans are currently available for this category. Please come back later.', APP_TD ); ?></em>

		<?php endif; ?>
	</fieldset>

	<?php do_action( 'hrb_project_form_purchase' ); ?>

	<fieldset>
		<?php do_action( 'appthemes_purchase_fields', HRB_PRICE_PLAN_PTYPE ); ?>

		<?php hrb_hidden_input_fields( array( 'action' => esc_attr('purchase-project') ) ); ?>

		<?php if ( $previous_step = appthemes_get_previous_step() ): ?>
			<input class="button secondary previous-step" previous-step-url="<?php echo esc_url( appthemes_get_step_url( $previous_step ) ); ?>" value="<?php echo esc_attr( '&#8592; Back', APP_TD ); ?>" type="submit" />
		<?php endif; ?>

		<?php if ( ! empty( $plans ) ): ?>
			<input class="button" type="submit" value="<?php echo esc_attr( $bt_step_text ); ?>" />
		<?php endif; ?>

	</fieldset>

</form>
