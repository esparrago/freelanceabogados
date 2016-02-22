<fieldset>
    <legend><?php _e( 'Work Details', APP_TD ) ?></legend>

        <?php foreach( $participants as $worker ): ?>

				<?php
					// skip if the worker is the participant
					if ( $participant->ID == $worker->ID ):
						continue;
					endif;

					$proposal = hrb_get_proposal( $worker->proposal_id );

					$can_edit = current_user_can( 'edit_workspace', get_the_ID() );
				?>

				<div class="participant-details">

					<div class="row work-user-meta">
						<div class="large-3 columns user-meta-info">
							<?php the_hrb_user_bulk_info( $worker, array( 'show_gravatar' => array( 'size' => 45 ) ) ); ?>
						</div>
						<div class="large-9 columns">

							<div class="user-contact-info">

								<div class="row user-contact">
									<div class="large-12 columns ">
										<span data-tooltip title="<?php echo __( 'Participant contact information', APP_TD );  ?>"><i class="icon i-contact"></i><?php the_hrb_user_contact_info( $worker ); ?>
									</div>
								</div>

								<div class="row user-location">
									<div class="large-12 columns ">
										<span data-tooltip title="<?php echo __( 'Participant location', APP_TD );  ?>"><i class="icon i-user-location"></i><?php the_hrb_user_location( $worker ); ?>
									</div>
								</div>

							</div>

							<div class="user-delivery-info">

								<div class="row">
									<div class="large-6 small-6 columns user-proposal-amount">
										<span data-tooltip title="<?php echo __( 'Price agreed for the project development', APP_TD );  ?>"><i class="icon i-budget-alt"></i><?php the_hrb_user_proposal_total_amount( $proposal ); ?></span>
									</div>
									<div class="large-6 small-6 columns user-proposal-delivery">
										<span data-tooltip title="<?php echo __( 'Delivery time', APP_TD );  ?>"><i class="icon i-days-deliver"></i><?php the_hrb_proposal_delivery_time( $proposal ); ?></span>
									</div>
								</div>

							</div>

							<div class="row work-meta">
								<div class="large-6 small-8 columns work-status <?php echo esc_attr( $worker->status ); ?> <?php echo esc_attr( $post->post_status ); ?>">
									<span class="label work-status"><i class="icon i-status"></i><?php echo hrb_get_participants_statuses_verbiages( $worker->status ); ?></span>
								</div>

								<?php
									ob_start();
									the_hrb_dashboard_user_work_actions( get_queried_object(), $project, $worker );
									$actions = ob_get_clean();
								?>

								<?php if ( $actions ): ?>

									<div class="large-4 small-4 columns participant-actions">
										<?php echo $actions; ?>
									</div>

								<?php endif; ?>

							</div>

						</div>
					</div>

					<div class="row form-review-fieldset review-user-<?php echo esc_attr( $worker->ID ); ?>">
						<fieldset>
							<?php appthemes_load_template( 'form-review.php', array( 'review_recipient' => $worker ) ); ?>
						</fieldset>
					</div>

				</div>

                <fieldset>
                    <legend><i class="icon i-notes"></i> <?php echo __( 'Notes', APP_TD ) ?></legend>
					<p class="worker-notes"><?php echo $worker->status_notes ? $worker->status_notes : __( 'None', APP_TD ); ?></p>
                </fieldset>

        <?php endforeach;?>

</fieldset>

<?php if ( HRB_PROJECT_STATUS_WORKING == $post->post_status ): ?>

        <?php appthemes_load_template( 'form-workspace-manage-employer.php', array( 'can_edit' => $can_edit ) ); ?>

<?php elseif ( HRB_PROJECT_STATUS_WORKING != $post->post_status && HRB_PROJECT_STATUS_WAITING_FUNDS != $post->post_status ): ?>

		<fieldset>
			<legend><i class="icon i-closed-date"></i> <?php echo __( 'Closed On', APP_TD ) ?></legend>
			<p class="worker-status-timestamp"><?php echo appthemes_display_date( $post->post_date ); ?></p>
		</fieldset>

<?php endif; ?>