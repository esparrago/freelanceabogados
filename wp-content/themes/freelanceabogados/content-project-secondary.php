<article id="post-<?php the_ID(); ?>" class="project project-secondary">
	<div class="row">

		<div class="large-2 small-6 columns user-meta-info project-img">

			  <?php if ( 'profile' == $content ): ?>
				<span class="project-authored"><i class="icon i-project"></i></span>
			  <?php else: ?>
					<?php the_hrb_user_bulk_info( get_the_author_meta('ID'), array( 'show_gravatar' => array( 'size' => 70 ) ) ); ?>
			  <?php endif; ?>

		</div>

		<div class="large-2 small-4 columns project-price-action budget-deadline">

			<div class="project-budget">
				<div class="budget"><?php the_hrb_project_budget(); ?></div>
				<div class="budget-type"><?php the_hrb_project_budget_type(); ?></div>
			</div>

			<div class="project-actions">
				<?php the_hrb_project_faves_link(); ?>
			</div>

		</div>

		<div class="large-8 small-12 columns projects-section">

			<div class="row project-title-row">
				<div class="large-10 columns project-title"><h2><?php the_hrb_project_title(); ?></h2></div>
			</div>

			<div class="row section-meta-info">
				<div class="large-6 small-6 columns project-date">
					<span data-tooltip title="<?php _e( 'Posted Date', APP_TD ); ?>"><i class="icon i-post-date"></i><?php the_hrb_project_posted_time_ago(); ?></span>
				</div>
				<div class="large-6 small-6 columns project-remain-days">
					<span data-tooltip title="<?php _e( 'Days until Expiration', APP_TD ); ?>"><i class="icon i-remain-days"></i><?php the_hrb_project_remain_days( get_the_ID(), true ); ?></span>
				</div>
			</div>

			<div class="row project-author-meta">

				<?php if ( 'profile' != $content ): ?>
					<div class="<?php echo ( 'profile' != $content ? 'large-4 small-4' : 'large-8 small-8' ); ?> columns project-author-by">
						<span class=""><i class="icon i-author"></i><?php the_hrb_project_author( get_the_ID(), __( 'by ', APP_TD ) ); ?></span>
					</div>
				<?php endif; ?>

				<div class="<?php echo ( 'profile' != $content ? 'large-4 small-4' : 'large-8 small-8' ); ?> columns project-in-location">
					<span data-tooltip title="<?php _e( 'Project Location', APP_TD ); ?>"><i class="icon i-project-location"></i><?php the_hrb_project_location(); ?></span>
				</div>

				<div class="large-4 small-4 columns project-num-proposals">
					<span><i class="icon i-proposals-count"></i><?php the_hrb_project_proposals_count(); ?></span>
				</div>
			</div>

			<div class="row">
				<div class="project-description large-12 columns"><?php the_content(); ?></div>
			</div>

			<!-- project meta taxonomy-->

			<div class="row project-meta-below-desc cf">
				<div class="large-6 small-6 columns project-cat">
					<i class="icon i-project-category"></i><?php the_hrb_tax_terms( HRB_PROJECTS_CATEGORY ); ?>
				</div>

				<div class="large-6 small-6 columns left project-skills">
					<?php the_hrb_tax_terms( HRB_PROJECTS_SKILLS, get_the_ID(), '', '<span class="label">', '</span>' ); ?>
				</div>
			</div><!-- end row -->

			<?php if ( has_term( '', HRB_PROJECTS_TAG ) ): ?>

				<div class="row project-meta-below-desc-tags cf">
					<div class="project-tags large-12 columns">
						<i class="icon i-tags"></i><?php the_hrb_tax_terms( HRB_PROJECTS_TAG ); ?>
					</div>
				</div>

			<?php endif; ?>


		</div><!-- end columns -->

	</div><!-- end row -->
</article>