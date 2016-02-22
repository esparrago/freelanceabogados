<?php the_post(); ?>

<div id="main" class="large-8 columns">

	<!-- project -->
	<div id="project" <?php post_class( 'single-project' ); ?>>

		<div class="section-container project-leaves">
			<div class="project-header-wrapper">

				<div class="single-project-header row">

					<div class="budget-deadline large-6 small-7 columns cf">
						<div class="project-budget"  data-tooltip title="<?php echo __( 'Project Budget', APP_TD ); ?>">
							<span class="budget"><?php the_hrb_project_budget(); ?></span>
							<span class="budget-type"><?php echo the_hrb_project_budget_type(); ?></span>
						</div>

						<?php $remain_days = get_the_hrb_project_remain_days(); ?>

						<?php if ( '' !== $remain_days ): ?>
							<div class="<?php echo ( $remain_days < 0 ? 'project-expired' : '' ); ?>" data-tooltip title="<?php echo __( 'Time Until Expiration', APP_TD ); ?>">
								<div class="project-expires"><?php the_hrb_project_remain_days(); ?></div>
							</div>
						<?php endif; ?>
					</div>

					<div class="bid large-4 small-5 columns large-offset-2">
						<?php the_hrb_create_edit_proposal_link(); ?>
					</div>

				</div><!--end row -->

			</div><!--end wrapper -->

			<article class="project">

				<div class="article-header row collapse">

					<div class="large-3 small-5 columns">
						<span class="projects-starred"><?php the_hrb_project_faves_link(); ?></span>
					</div>

					<div class="large-4 small-6 columns add-ons">
						<?php the_hrb_project_addons(); ?>
					</div>

				</div><!-- end row -->

				<?php if ( function_exists('sharethis_button') && $hrb_options->listing_sharethis ): ?>

					<div class="row share-this">
						<div class="large-12 columns">
							<div><?php sharethis_button(); ?></div>
						</div>
					</div>

				<?php endif; ?>

				<div class="article-header row">
					<div class="large-9 small-9 columns article-title">

						<?php appthemes_before_post_title( HRB_PROJECTS_PTYPE ); ?>

						<h3><?php the_title(); ?></h3>

						<?php appthemes_after_post_title( HRB_PROJECTS_PTYPE ); ?>

					</div>

					<div class="large-3 columns single-project-meta-buttons">
						<?php the_hrb_project_actions(); ?>
					</div>

				</div><!-- end row -->

				<div class="row">
					<div class="large-12 columns">
						<div class="section-container project-branches">
							<div class="project-custom-fields">
								<?php the_hrb_project_custom_fields( get_the_ID(), 'file', $include = false ); ?>
							</div>

							<div class="project-files">
								<?php the_hrb_project_files( get_the_ID(), '<fieldset><legend>'.__( 'Attachments' ).'</legend>', '</fieldset>' ); ?>
							</div>
						</div>
					</div>
				</div>

			</article>

			<div class="single-project-meta-group">

				<div class="single-project-meta cf">
					<span class="project-location"><i class="icon i-project-location"></i> <?php the_hrb_project_location(); ?></span>
					<span class="project-cat"><i class="icon i-project-category"></i> <?php the_hrb_tax_terms( HRB_PROJECTS_CATEGORY ); ?></span>
					<span class="project-skills"><?php the_hrb_tax_terms( HRB_PROJECTS_SKILLS, 0, '', '<span class="label">','</span>' ); ?></span>
				</div>

				<?php if ( has_term( '', HRB_PROJECTS_TAG ) ): ?>

					<div class="single-project-meta-tags project-tags cf">
						<span><i class="icon i-tags"></i><?php the_hrb_tax_terms( HRB_PROJECTS_TAG ); ?></span>
					</div>

				<?php endif; ?>
			</div>

		</div><!-- end section-container -->


		<div class="section-container auto section-tabs project-trunk" data-section>

			<!-- dynamic content within tabs -->

			<section class="active">

				<p class="title"><a href="#details"><?php echo __( 'Details', APP_TD ); ?></a></p>

				<div class="content" data-section-content>

					<?php appthemes_load_template( 'single-project-section-details.php' ); ?>

				</div>

			</section>

			<section>

				<p class="title"><a href="#proposals"><?php echo sprintf( __( 'Proposals (%s)', APP_TD ), appthemes_get_post_total_bids( get_the_ID() ) ); ?></a></p>

				<div class="content" data-section-content>

					<?php appthemes_load_template( 'single-project-section-proposals.php', array( 'proposals' => $proposals ) ); ?>

				</div>

			</section>

			<?php if ( $hrb_options->projects_clarification ): ?>

			<section>

				<p class="title"><a href="#clarification"><?php echo sprintf( __( 'Clarification Board (%s)', APP_TD ), get_comments_number() ); ?></a></p>

				<div class="content" data-section-content>

					<?php appthemes_load_template( 'single-project-section-clarification.php' ); ?>

				</div>

			<?php endif; ?>

		</div>

	</div><!-- end #project -->

	<!-- ad space -->
	<?php hrb_display_ad_sidebar( 'hrb-single_project_ad_space' ); ?>

</div><!-- end #main -->

<div id="sidebar" class="large-4 columns">

	<div class="sidebar-widget-wrap cf">
		<!-- static sidebar -->
		<?php appthemes_load_template( 'sidebar-project-author.php', array( 'user' => $project_author, 'user_reviews' => $project_author_reviews ) ); ?>

		<!-- static sidebar -->
		<?php get_sidebar('project'); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->
