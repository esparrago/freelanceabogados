<div class="row">
	<div class="large-12 columns">
		<fieldset>
			<legend><?php _e( 'Project Terms', APP_TD ) ?></legend>

			<p><?php the_hrb_project_terms( $project->ID ); ?></p>

		</fieldset>

			<?php foreach( $participants as $participant ): ?>

				<?php if ( 'reviewer' == $participant->type ) continue; ?>

				<?php $proposal = hrb_get_proposal( $participant->proposal_id ); ?>

				<?php if ( ! $proposal ) : ?>
					<span class="label error"><?php echo sprintf( __( "Warning: Proposal for participant '%s' was not found. Please contact the site admin.", APP_TD ), $participant->display_name ); ?></span>
				<?php  continue; endif; ?>

						<fieldset>
							<legend><?php echo sprintf( __( '%s Terms', APP_TD ), ( $dashboard_user->ID == $participant->ID ?__( 'Your', APP_TD ) : __( 'Development', APP_TD  ) ) ); ?></legend>

							<?php if ( $dashboard_user->ID != $participant->ID ) : ?>

								<?php the_hrb_user_gravatar( $participant, 45 ); ?>
								<?php the_hrb_user_display_name( $participant ); ?>

							<?php endif; ?>

							<p><?php echo ( $participant->development_terms ? $participant->development_terms : __( 'None specified', APP_TD ) ) ; ?></p>
						</fieldset>

						<fieldset>
							<legend><?php _e( 'Amount', APP_TD ) ?></legend>
							<p><?php the_hrb_user_proposal_total_amount( $proposal ); ?></p>
						</fieldset>

						<fieldset>
							<legend><?php _e( 'Deliverables', APP_TD ) ?></legend>
							<p><?php the_hrb_proposal_delivery_time( $proposal ); ?></p>
						</fieldset>


				<div class="row agreement-date">
					<div class="large-12 columns">
						<span class="label-meta right"><?php echo __( 'On', APP_TD ); ?>
							<strong><?php echo appthemes_display_date( $participant->agreement_timestamp ); ?></strong>
						</span>
					</div>
				</div>

			<?php endforeach; ?>


	</div>
</div>
