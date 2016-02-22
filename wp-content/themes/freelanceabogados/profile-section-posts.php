<div id="posts">

  <!-- posts -->
  <div class="article-header row">

		<?php if ( $user_posts->have_posts() ): ?>

			<?php while ( $user_posts->have_posts() ) : $user_posts->the_post(); ?>

				<article class="post">
					 <div class="post-header cf">
						<div class="post-title">
							<h2 class="post-heading"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
							<div class="author-post-link"><?php echo get_the_hrb_author_posts_link(); ?></div>
							<div class="category-post-list"><?php echo get_the_category_list(); ?></div>
						</div>

						<div class="post-date-box">
							<div><em><?php the_time( 'j' ); ?></em><span><?php the_time( 'M \'y' ); ?></span></div>
							<div class="box-comment-link"><i class="icon i-comment"></i><?php comments_popup_link( "0", "1", "%", "comment-count" ); ?></div>
						</div>
					</div>
				</article>

			<?php endwhile; ?>

		<?php else: ?>

			<div class="large-12 columns">
				<h4><?php echo __( 'No posts found.', APP_TD ); ?></h4>
			</div>

		<?php endif; ?>

		<div class="section-footer row">

		  <!-- pagination -->
		  <?php
			  if ( $user_posts->max_num_pages > 1 ) {
				  hrb_output_pagination( $user_posts, array() );
			  }
		  ?>

		</div><!-- end section-footer -->

	</div>
</div><!-- end #posts -->
