<div id="projects">

	<!-- projects -->
	<div class="article-header row">

		<div class="article-title large-8 columns">

			<?php if ( is_tax() ): ?>

				<h3><?php echo sprintf( __( 'Browsing - %s', APP_TD ), single_term_title( '', false ) ); ?></h3>

			<?php elseif ( ! is_search() && ! is_archive() ): ?>

				<h3><?php echo __( 'Ultimas Consultas', APP_TD ); ?></h3>

			<?php endif; ?>

		</div>

		<?php if ( $projects->have_posts() ) : ?>

			<div class="large-4 columns project-dropdown">
				<?php the_hrb_projects_sort_dropdown( get_the_hrb_projects_base_url(), $attributes = array( 'id' => 'drop-projects-filter' ) ) ?>
			</div>

		<?php endif; ?>

	</div>

	<?php if ( $projects->have_posts() ) : ?>

		<?php if ( is_search() && get_query_var( 'ls' ) ) : ?>

			<article class="project">
				<h2><?php printf( __( 'Projects found for "%s"', APP_TD ), hrb_get_search_query_var( 'ls' ) ); ?></h2>
			</article>

		<?php endif; ?>

		<?php appthemes_before_loop( HRB_PROJECTS_PTYPE ); ?>

		<?php while( $projects->have_posts() ) : $projects->the_post(); ?>

			<?php appthemes_before_post( HRB_PROJECTS_PTYPE ); ?>

			<?php get_template_part( 'content', HRB_PROJECTS_PTYPE ); ?>

			<?php appthemes_after_post( HRB_PROJECTS_PTYPE ); ?>

		<?php endwhile; ?>

		<?php appthemes_after_loop( HRB_PROJECTS_PTYPE ); ?>

	<?php else : ?>

		<article class="project content-no-results">

			<h5 class="no-results">
				<?php if ( is_search() ) : ?>

					<?php printf( __( 'Sorry, no projects were found for "%s" %s.', APP_TD ), hrb_get_search_query_var( 'ls' ), ( get_query_var('st') ? __( 'with the specified filters', APP_TD ) : '' ) ); ?>

				<?php else: ?>

					<?php echo __( 'No hay consultas disponibles para ofertar, vuelve en un rato', APP_TD ); ?></h2>

				<?php endif; ?>
			</h5>

		</article>

	<?php endif; ?>


	<!-- ad space -->
	<?php hrb_display_ad_sidebar( 'hrb-project-ads' ); ?>

	<!-- pagination -->
	<?php
	if ( $projects->max_num_pages > 1 ) {
		hrb_output_pagination( $projects, array( 'paginate_projects' => true ), get_the_hrb_projects_base_url() );
	};
	?>

</div><!-- end #projects -->

<?php wp_reset_postdata(); ?>
