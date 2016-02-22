<h2><i class="icon i-proposals-count"></i><?php echo __( 'Proposals', APP_TD  ); ?></h2>

<?php if ( $project_id ): ?>

		<fieldset class="proposal">
			<div class="row">
				<div class="large-12 columns">
					<legend class="project-title"><span><?php the_hrb_project_title(); ?></span></legend>
				</div>
			</div>
			<div class="row">
				<div class="large-12 columns proposal-meta">
					<fieldset class="large-4 columns budget">
						<span class="project-budget"><i class="icon i-budget"></i><small><?php echo __( 'Budget:', APP_TD  ); ?></small> <?php the_hrb_project_budget(); ?></span>
					</fieldset>
					<fieldset class="large-4 columns average">
						<span class="project-avg-bid"><i class="icon i-avg-proposals"></i><small><?php echo __( 'Avg. Proposals:', APP_TD  ); ?></small> <?php echo appthemes_display_price( appthemes_get_post_avg_bid( get_the_ID() ) ); ?></span>
					</fieldset>
					<fieldset class="large-4 columns total">
						<span class="project-total-bids"><i class="icon i-proposals-count"></i><small><?php echo __( 'Total Proposals:', APP_TD  ); ?></small> <?php echo appthemes_get_post_total_bids( get_the_ID() ); ?></span>
					</fieldset>
				</div>
			</div>
		</fieldset>

<?php endif; ?>

<div class="dashboard-filters">

	<div class="row">
		<div class="large-12 columns dashboard-filter-sort">
			<div class="large-6 columns">
				<?php hrb_output_results_fdropdown( hrb_get_dashboard_url_for('proposals') ); ?>
			</div>
			<div class="large-6 columns">
				<?php hrb_output_sort_fdropdown(); ?>
			</div>
		</div>

		<div class="large-12 columns dashboard-filter-sort">
			<div class="large-12 columns">
				<?php hrb_output_proposal_statuses_fdropdown( $proposals_no_filters, $attributes = array( 'name' => 'drop-filter-status', 'label' => __( 'Status', APP_TD ), 'base_link' => hrb_get_dashboard_url_for('proposals') ) ); ?>
			</div>
		</div>
	</div>

</div>

<?php if ( ! empty( $proposals ) ): ?>

	<?php foreach( $proposals as $proposal ): ?>

		<?php
			$project = $proposal->project;
			$notifications = appthemes_get_user_unread_notifications( $dashboard_user->ID, array( 'project_id' => $project->ID ) );

			$user_id = ( $project->post_author == $dashboard_user->ID ? $proposal->user_id : $project->post_author );
		?>

			<article class="listing">

				<div class="row">
					<div class="large-12 columns">

						<div class="large-2 column user-meta-info">
							<?php the_hrb_user_bulk_info( $user_id, array( 'show_gravatar' => array( 'size' => 55 ) ) ); ?>
						</div>

						<div class="large-10 columns projects-section">

							<?php if ( ! $project_id ): ?>

								<div class="row project-title-row">
									<div class="large-8 small-8 columns">
										<span data-tooltip title="<?php echo esc_attr( __( 'Project Title', APP_TD ) ); ?>">
											<h2><?php the_hrb_project_title( $project->ID ); ?></h2>
										</span>
									</div>
									<div class="large-4 small-4 columns project-meta-info">
										<span data-tooltip title="<?php echo esc_attr( __( 'Status', APP_TD ) ); ?>" class="label right project-status <?php echo esc_attr( get_the_hrb_project_or_workspace_status( $project->ID) ); ?>"><i class="icon i-status"></i> <?php the_hrb_project_or_workspace_status( $project->ID ); ?></span>
									</div>
								</div>

							<?php endif; ?>

							<div class="row section-meta-info <?php echo ( $project_id ? 'border-top' : '' ); ?>">
								<div class="large-6 small-6 columns project-date">
									<span data-tooltip title="<?php _e( 'Posted Date', APP_TD ); ?>"><i class="icon i-post-date"></i><?php the_hrb_project_posted_time_ago( $project->ID ); ?></span>
								</div>
								<div class="large-6 small-6 columns project-remain-days">
									<span data-tooltip title="<?php _e( 'Days until Expiration', APP_TD ); ?>"><i class="icon i-remain-days"></i><?php the_hrb_project_remain_days( $project->ID ); ?></span>
								</div>
							</div>

							<div class="row section-primary-info">
								<div class="<?php echo $proposal->_hrb_featured ? 'large-3 small-4' : 'large-4 small-4'; ?> columns proposal-amount">
									<span data-tooltip title="<?php echo __( 'Proposal Amount', APP_TD ); ?>"><i class="icon i-budget-alt"></i> <?php echo appthemes_display_price( $proposal->amount ); ?></span>
								</div>
								<div class="<?php echo $proposal->_hrb_featured ? 'large-4 small-4' : 'large-4 small-4'; ?> columns proposal-delivery-date">
									<span data-tooltip title="<?php echo __( 'Days for Delivery', APP_TD ); ?>"><i class="icon i-days-deliver"></i> <?php echo $proposal->_hrb_delivery . ' ' . $proposal->label_delivery_unit; ?></span>
								</div>
								<div class="<?php echo $proposal->_hrb_featured ? 'large-3 small-3' : 'large-4 small-4'; ?> columns proposal-date">
									<span data-tooltip title="<?php echo __( 'Proposal Date', APP_TD ); ?>"><i class="icon i-proposal-date"></i> <?php the_hrb_proposal_posted_time_ago( $proposal ); ?></span>
								</div>
								<?php if ( $proposal->_hrb_featured ): ?>
									<div class="large-2 small-1 columns">
										<span class="project-addons inline-addons">
											<span data-tooltip title="<?php echo __( 'Featured', APP_TD ); ?>" class="inline-addon"><i class="icon i-featured"></i></span>
										</span>
									</div>
								<?php endif; ?>
							</div>
							<?php if ( ! $project_id ): ?>
									<div class="row section-primary-info">
										<div class="large-12 columns total-proposals <?php echo ( $dashboard_user->ID == $project->post_author ? ' clickable-prop-list' : '' ); ?>">
											<?php the_hrb_project_proposals_count_link( $project->ID ); ?>
										</div>
									</div>
							<?php endif; ?>

							<div class="row">
								<div class="large-12 columns dashboard-proposal-description">
									<p><?php echo sanitize_text_field( $proposal->comment_content ); ?></p>
								</div>
							</div>

							<div class="row section-secondary-info">
								<div class="large-7 columns proposal-status <?php echo esc_attr( hrb_get_proposal_status( $proposal) ); ?>">
									<span data-tooltip class="label proposal-status <?php echo esc_attr( hrb_get_proposal_status( $proposal) ); ?>" title="<?php echo esc_attr( __( 'Status', APP_TD ) ); ?>"><i class="icon i-work-status"></i><i class="icon i-status"></i> <?php echo hrb_get_proposals_statuses_verbiages( hrb_get_proposal_status( $proposal) ); ?></span>
								</div>

								<div class="large-2 small-8 columns project-notifications">
									<a href="<?php echo esc_url( hrb_get_dashboard_url_for( 'notifications' ) ); ?>"><span data-tooltip title="<?php echo esc_attr( __( 'Notifications', APP_TD ) ); ?>"><i class="icon i-notifications"></i><?php echo $notifications->found; ?></span></a>
								</div>
								<div class="large-3 small-4 columns proposals-actions">
									<?php the_hrb_dashboard_proposal_actions( $proposal, get_post( $project->ID ) ); ?>
								</div>
							</div>

						</div><!-- projects-section -->

					</div>
				</div>

			</article>

	<?php endforeach; ?>

	<!-- pagination -->
	<?php
	if ( $proposals_found >= 1 ) :
		hrb_output_pagination( $proposals, array( 'total' => $proposals_found ), hrb_get_dashboard_url_for('proposals') );
	endif;
	?>

<?php else: ?>

		<h5 class="no-results"><?php echo __( 'No proposals found.', APP_TD ); ?></h5>

<?php endif; ?>
