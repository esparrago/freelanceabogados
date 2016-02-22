<div <?php post_class( 'recent-box' ); ?>>

	<?php if ( $instance['show_thumbnail'] ): ?>

		<div class="recent-box-thumb">

			<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" >

				<?php if ( has_post_thumbnail() ) : ?>

					<?php the_post_thumbnail( 'recent-posts-widget' ); ?>

				<?php endif; ?>

			</a>

		</div><!-- end recent-box-thumb -->

	<?php endif; ?>

	<div class="recent-box-content">

		<div class="recent-box-info">
			<div class="row recent-box-title">
				<div class="large-12 small-12 columns">
					<h4 <?php if ( $instance['show_rating'] ) echo ' class="recent-box-rating"'; ?>><a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php echo esc_attr( get_the_title() ? get_the_title() : get_the_ID() ); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?></a></h4>
				</div>
			</div>


			<?php if ( HRB_PROJECTS_PTYPE == $post->post_type ): ?>

				<?php if ( $instance['show_rating'] ) : ?>
					<div class="fr row recent-box-author">
						<div class="large-9 small-9 columns">
							<i class="icon i-author"></i> <?php echo __( 'by', APP_TD ); ?>
							<span class="recent-author"><?php the_hrb_user_display_name( $post->post_author ); ?></span>
							<span class="recent-rating">&nbsp;<?php the_hrb_user_rating( $post->post_author ); ?></span>
						</div>
						<div class="large-3 small-3 columns total-proposals">
							<span data-tooltip title="<?php echo __( 'Proposals', APP_TD ); ?>" class="right"><i class="icon i-proposals-count"></i> <?php echo appthemes_get_post_total_bids( $post->ID ); ?></span>
						</div>
					</div>
				<?php endif; ?>

			<?php else: ?>

				<?php if ( $instance['show_rating'] && defined( 'STARSTRUCK_KEY' ) ) : ?>
					<div class="fr">
						<?php echo starstruck_mini_ratings( $instance[ 'post_type' ] ); ?>
					</div>
				<?php endif; ?>

			<?php endif; ?>

		</div>

		<div class="recent-box-excerpt">

			<?php the_excerpt(); ?>

			<?php if ( $instance['show_readmore'] ) : ?>
				<div class="button-new"><i><a href="<?php the_permalink(); ?>"><?php _e( 'Read More', APP_TD );?></a></i></div>
			<?php endif; ?>

		</div>

		<div class="project-meta-below-desc cf-sidebar cf">
			<div class="project-location"><i class="icon i-project-location"></i> <?php the_hrb_project_location(); ?></div>
			<div class="project-cat"><i class="icon i-project-category"></i> <?php the_hrb_tax_terms( HRB_PROJECTS_CATEGORY ); ?></div>
		</div>

	</div>

	<?php if ( $instance['show_date'] ) : ?>
		<span class="recent-post-date"><?php echo get_the_date(); ?></span>
	<?php endif; ?>

</div><!-- end recent-box -->