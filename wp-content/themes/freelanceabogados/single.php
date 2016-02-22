<div id="main" class="large-8 columns">

	<?php appthemes_before_blog_loop(); ?>

	<?php while ( have_posts() ) : the_post(); ?>

		<?php appthemes_before_blog_post(); ?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

			<?php appthemes_before_blog_post_title(); ?>

			<div class="post-header cf">
				<div class="post-title">
					<h1 class="post-heading"><span class="left-hanger"><?php the_title(); ?></span></h1>
					<div class="author-post-link"><?php echo get_the_hrb_author_posts_link(); ?></div>
					<div class="category-post-list"><?php echo get_the_category_list(); ?></div>
				</div>

				<div class="post-date-box">
					<div><em><?php the_time( 'j' ); ?></em><span><?php the_time( 'M \'y' ); ?></span></div>
					<div class="box-comment-link"><i class="icon i-comment"></i><?php comments_popup_link( "0", "1", "%", "comment-count" ); ?></div>
				</div>
			</div>

			<?php appthemes_after_blog_post_title(); ?>

			<section class="overview cf">

				<?php appthemes_before_blog_post_content(); ?>

				<?php the_content(); ?>

				<?php appthemes_after_blog_post_content(); ?>

				<?php edit_post_link( __( 'Edit', APP_TD ), '<span class="edit-link">', '</span>' ); ?>
			</section>

			<?php if ( function_exists( 'sharethis_button' ) && $hrb_options->blog_post_sharethis ): ?>

				<section class="sharethis cf">
					<div class="sharethis"><?php sharethis_button(); ?></div>
				</section>

			<?php endif; ?>

			<section class="comments cf">

				<?php comments_template(); ?>

			</section>

		</article>

		<?php appthemes_after_blog_post(); ?>

	<?php endwhile; ?>

	<?php appthemes_after_blog_loop(); ?>

</div><!-- /#main -->

<div id="sidebar" class="large-4 columns">

	<div class="sidebar-widget-wrap cf">
		<?php get_sidebar( app_template_base() ); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->
