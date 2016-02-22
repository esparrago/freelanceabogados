<div id="projects">

		<div class="dashboard-filters">
			<div class="row">

				<div class="large-12 columns dashboard-filter-sort">
					<div class="large-6 columns">
						<?php hrb_output_results_fdropdown( hrb_get_dashboard_url_for('projects') ); ?>
					</div>

					<div class="large-6 columns">
						<?php hrb_output_sort_fdropdown(); ?>
					</div>
				</div>

				<div class="large-12 columns dashboard-filter-sort">
					<div class="large-6 columns">
						<?php hrb_output_statuses_fdropdown( $projects_no_filters, $attributes = array( 'name' => 'drop-filter-status', 'label' => __( 'Status', APP_TD ), 'base_link' => hrb_get_dashboard_url_for('projects') ) ); ?>
					</div>

					<div class="large-6 columns">
						<?php hrb_output_project_relation_fdropdown( $projects_no_filters, $attributes = array( 'name' => 'drop-filter-role', 'label' => __( 'Role', APP_TD ), 'base_link' => hrb_get_dashboard_url_for('projects') ) ); ?>
					</div>
				</div>

			</div>
		</div>

		<?php if ( ! empty( $projects ) && $projects->post_count > 0 ): ?>

			<?php while( $projects->have_posts() ) : $projects->the_post(); ?>

				<?php
					$project = hrb_get_project( $post );

					$notifications = appthemes_get_user_unread_notifications( $dashboard_user->ID, array( 'project_id' => get_the_ID() ) );

					$participants = hrb_get_post_participants( $project->ID );

					$addons = get_the_hrb_project_addons( get_the_ID() );
				?>

					<article class="listing">

						<div class="row project-wrapper">
							<div class="large-12 columns ">

								<div class="large-2 columns user-meta-info">

									<?php if ( $project->post_author == $dashboard_user->ID ) : ?>
										<span data-tooltip title="<?php echo esc_attr( __( 'Owned Project', APP_TD ) ); ?>" class="project-authored"><i class="icon i-authored-project"></i></span>
									<?php else: ?>
										<?php the_hrb_user_bulk_info( $project->post_author, array( 'show_gravatar' => array( 'size' => 55 ) ) ); ?>
									<?php endif; ?>

								</div>

								<div class="large-10 columns projects-section">
									<div class="row project-title-row">
										<div class="large-8 small-8 columns">
											<h2><?php the_hrb_project_title(); ?></h2>
										</div>
										<div class="large-4 small-4 columns project-meta-info">
											<span data-tooltip title="<?php echo esc_attr( __( 'Status', APP_TD ) ); ?>" class="label right project-status <?php echo esc_attr( get_the_hrb_project_or_workspace_status() ); ?>"><i class="icon i-status"></i> <?php the_hrb_project_or_workspace_status(); ?></span>
										</div>
									</div>
									<div class="row section-meta-info">
										<div class="<?php echo ( $addons ? 'large-4 small-4' : 'large-8 small-8' ); ?> columns project-date">
											<span data-tooltip title="<?php _e( 'Posted Date', APP_TD ); ?>" class="project-date"><i class="icon i-post-date"></i><?php the_hrb_project_posted_time_ago(); ?></span>
										</div>
										<div class="<?php echo ( $addons ? 'large-4 small-4 text-center' : 'large-4 small-4' ); ?> columns project-remain-days">
											<span data-tooltip title="<?php _e( 'Days until Expiration', APP_TD ); ?>" class="project-remain-days"><i class="icon i-remain-days"></i><?php the_hrb_project_remain_days(); ?></span>
										</div>
										<?php if ( $addons ): ?>
											<div class="large-4 small-4 columns project-meta-addons">
												<span class="project-addons inline-addons">
													<?php foreach( $addons as $key => $addon ): ?>
														<span data-tooltip title="<?php echo sprintf( __( '%s - active until %s', APP_TD ), $addon['label_2'], appthemes_display_date( $addon['expiration_date'], 'l jS \of F Y h:i:s A' ) ); ?>" class="inline-addon"><i class="<?php echo esc_attr( $addon['icon'] ); ?>"><?php echo ( 'featured-cat' == $addon['class_name'] ? __( 'c', APP_TD ) : '' ); ?></i></span>
													<?php endforeach; ?>
												</span>
											</div>
										<?php endif; ?>
									</div>
									<div class="row section-primary-info">
										<div class="large-4 small-4 columns dashboard-budget">
											<span data-tooltip title="<?php _e( 'Budget', APP_TD ); ?>" class="project-budget"><i class="icon i-budget-alt"></i><?php the_hrb_project_budget(); ?></span>
										</div>
										<div class="large-4 small-4 columns average-proposals">
											<span data-tooltip title="<?php _e( 'Avg. Proposals', APP_TD ); ?>" class="project-avg-bid"><i class="icon i-avg-proposals-alt"></i><?php echo appthemes_display_price( appthemes_get_post_avg_bid( get_the_ID() ) ); ?></span>
										</div>
										<div class="large-4 small-4 columns total-proposals <?php echo ( $dashboard_user->ID == $project->post_author ? ' clickable-prop-list' : '' ); ?>">
											<?php the_hrb_project_proposals_count_link(); ?>
										</div>

									</div>
									<div class="row section-secondary-info">
										<div class="large-7 small-2 columns">
											&nbsp;
										</div>
										<div class="large-2 small-6 columns project-notifications">
											<a href="<?php echo esc_url( hrb_get_dashboard_url_for( 'notifications' ) ); ?>"><span data-tooltip title="<?php echo esc_attr( __( 'Notifications', APP_TD ) ); ?>"><i class="icon i-notifications"></i><?php echo $notifications->found; ?></span></a>
										</div>
										<div class="large-3 small-4 columns projects-actions">
											<?php the_hrb_dashboard_project_actions( $post ); ?>
										</div>
									</div>

									<?php if ( $participants ): ?>

										<div class="row project-participants">
											<div class="large-12 columns">

													<?php foreach( $participants->results as $worker ): ?>

															<?php
																$workspaces_ids = hrb_get_participants_workspace_for( $post->ID, array( $project->post_author, $worker->ID, $dashboard_user->ID ) );
																if ( ! $workspaces_ids ) {
																	continue;
																}

																$proposals = hrb_get_proposals_by_user( $worker->ID, array( 'post_id' => $post->ID ) );
																if ( empty( $proposals['results'] ) ) {
																	continue;
																}

																$proposal = reset( $proposals['results'] );

																$dispute = '';

																if ( hrb_is_disputes_enabled() ) {
																	$dispute = appthemes_get_disputes_for( $workspaces_ids );
																	$dispute = reset( $dispute );
																}
															?>

															<div class="row project-participants-info">
																<div class="large-12 columns">
																	<span data-tooltip title="<?php echo esc_attr( __( 'Participant', APP_TD ) ); ?>">
																		<?php the_hrb_user_gravatar( $worker, 25 ); ?>

																		<?php if ( $dispute && 'publish' == $dispute->post_status ) : ?>
																				<div class="label dispute-status right"><i class="icon i-dispute"></i><?php echo __( 'Opened Dispute', APP_TD ); ?></div>
																		<?php endif; ?>

																		<?php
																			if ( $worker->ID != $dashboard_user->ID ) {
																				the_hrb_user_display_name( $worker );
																			} else {
																				echo __( 'You', APP_TD );
																			}
																		?>
																	</span>
																</div>
															</div>

															<div class="row project-participants-meta">
																<div class="large-5 columns work-status <?php echo esc_attr( $worker->status ); ?> <?php echo get_the_hrb_project_or_workspace_status(); ?>">
																	<span data-tooltip class="label <?php echo esc_attr( $worker->status ); ?>" title="<?php echo esc_attr( __( 'Work Status', APP_TD ) ); ?>"><i class="icon i-work-status"></i> <i class="icon i-status"></i> <?php echo hrb_get_participants_statuses_verbiages( $worker->status ); ?></span>
																</div>
																<div class="large-4 small-8 columns work-status-time">
																	<span data-tooltip title="<?php echo esc_attr( __( 'Last Status Update', APP_TD ) ); ?>"><i class="icon i-status-date"></i><?php the_hrb_posted_time_ago( strtotime( $worker->status_timestamp ) ); ?></span>
																</div>
																<div class="large-3 small-4 columns workspace-actions">
																	<?php the_hrb_dashboard_project_work_actions( $post, $proposal ); ?>
																</div>
															</div>

													<?php endforeach; ?>

											</div>
										</div>

									<?php endif;?>

								</div><!-- projects-section -->

							</div>
						</div>

					</article>


			<?php endwhile; ?>

		<?php else: ?>

			<?php if ( current_user_can( 'edit_projects') ): ?>

					<h5 class="no-results"><?php echo sprintf( __( 'No projects found. Click <a href="%s">here</a> to post a project now.', APP_TD ), esc_url( get_the_hrb_project_create_url() ) ); ?></h5>

			<?php else: ?>

					<h5 class="no-results"><?php echo __( 'No projects found.', APP_TD ); ?></h5>

			<?php endif; ?>

	<?php endif; ?>

	<!-- ad space -->
	<?php hrb_display_ad_sidebar( 'hrb-project-ads' ); ?>

	<!-- pagination -->
	<?php
	if ( ! empty( $projects ) && $projects->max_num_pages > 1 ) :
		hrb_output_pagination( $projects, '', hrb_get_dashboard_url_for( 'projects' ) );
	endif;
	?>

</div>