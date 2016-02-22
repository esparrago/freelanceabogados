<h2><i class="icon i-workspace"></i><?php echo __( 'Workspace', APP_TD  ); ?></h2>

	<fieldset class="proposal">
		<div class="row single-project-title">
			<div class="large-8 small-8 columns">
				<legend class="project-title"><span><?php the_hrb_project_title(); ?></span></legend>
			</div>
			<div class="large-4 small-4 columns">
				<span class="label right project-status <?php echo esc_attr( $post->post_status ); ?>"><i class="icon i-status"></i> <?php echo hrb_get_project_statuses_verbiages( $post->post_status ) . ( $escrow_status ? sprintf( ' | <strong>%1$s</strong>', $escrow_status  ) : '' ); ?></span>
			</div>
		</div>
	</fieldset>

	<div class="workspace-content">

		<div class="row">
			<div class="large-12 columns">
				<div class="row">
					<div class="large-12 columns">

						<?php do_action( 'hrb_before_workspace_project_details' ); ?>

						<fieldset>
							<legend><?php _e( 'Project Details', APP_TD ) ?></legend>
							<p class="project-description-"><?php echo $project->post_content; ?></p>
						</fieldset>

						<?php the_hrb_project_files( $project->ID, '<fieldset><legend>'.__( 'Files', APP_TD ).'</legend>', '</fieldset>' ); ?>

						<?php do_action( 'hrb_workspace_project_details' ); ?>

						<fieldset>
							<legend><i class="icon i-notes"></i> <?php echo __( 'Notes', APP_TD ) ?></legend>
							<p class="workspace-status-notes"><?php the_hrb_workspace_status_notes(); ?></p>
						</fieldset>

						<?php do_action( 'hrb_after_workspace_project_details' ); ?>

					</div>
				</div>
			</div>
		</div>

		<div class="row workspace-type-<?php echo esc_attr( $participant->type ); ?>">
			<div class="section-container project-trunk auto section-tabs" data-section>

			<section class="active">

				<p class="title" data-section-title="" style="left: 194px;"><a href="#manage"><?php echo __( 'Manage', APP_TD ); ?></a></p>

				<div class="content" data-section-content="">

					<?php appthemes_load_template( "dashboard-workspace-section-manage-".$participant->type.".php" ); ?>

				</div>

			</section>

			<section>

				<p class="title" data-section-title="" style="left: 194px;"><a href="#terms"><?php echo __( 'Agreed Terms', APP_TD ); ?></a></p>

				<div class="content" data-section-content="">

					<?php appthemes_load_template( 'dashboard-workspace-section-terms.php', array( 'participants' => ( $dashboard_user->ID == $project->post_author ? $participants : array( $participant ) ) ) ); ?>

				</div>

			</section>

			<?php if ( ! empty( $reviews ) ): ?>

				<section>
					<p class="title" data-section-title="" style="left: 194px;"><a href="#reviews"><?php echo __( 'Reviews', APP_TD ); ?></a></p>
					<div class="content" data-section-content="">

						<?php appthemes_load_template('dashboard-workspace-section-reviews.php'); ?>

					</div>
				</section>

			<?php endif; ?>

			<?php if ( ! empty( $disputes ) ): ?>

				<section>
					<p class="title" data-section-title="" style="left: 194px;"><a href="#disputes"><?php echo sprintf( __( 'Dispute %s', APP_TD ), html( 'small', '('.  ( 'publish' == $disputes[0]->post_status ? __( 'Opened', APP_TD ) : __( 'Resolved', APP_TD ) ) .')' ) ); ?></a></p>
					<div class="content" data-section-content="">

						<?php appthemes_load_template('dashboard-workspace-section-disputes.php'); ?>

					</div>
				</section>

			<?php endif; ?>

			</div>
		</div>

	</div>