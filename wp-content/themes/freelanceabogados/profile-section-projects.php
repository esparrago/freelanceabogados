<div id="projects-<?php echo $relation; ?>">

  <?php if ( $projects->have_posts() ): ?>

	<!-- projects -->
	<div class="article-header row">
		<div class="article-title large-8 columns">
		  <h3><?php echo __( 'Active Projects', APP_TD ); ?></h3>
		</div>
	</div>

	<div class="row">
		<div class="large-12 columns">

			<?php while ( $projects->have_posts() ) : $projects->the_post(); ?>

				<?php appthemes_load_template( 'content-project-secondary.php', array( 'content' => 'profile' ) ); ?>

			<?php endwhile; ?>

		</div>
	</div>

	<div class="section-footer row">

		<!-- ad space -->
		<?php hrb_display_ad_sidebar( 'hrb-project-ads' ); ?>

		<!-- pagination -->
		<?php
			if ( $projects->max_num_pages > 1 ) :
				hrb_output_pagination( $projects,  array( 'paginate_projects' => 1 ), get_the_hrb_projects_base_url() );
			endif;
		?>

	</div><!-- end section-footer -->

  <?php else: ?>

		  <h5 class="no-results"><?php echo __( 'No active projects found.', APP_TD ); ?></h5>

  <?php endif; ?>

</div><!-- end #projects -->
