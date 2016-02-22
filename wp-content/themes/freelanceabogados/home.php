<?php
/**
 * Template Name: Blog
 */
?>

<div id="main" class="large-8 columns">
	<div id="posts">
		<!-- posts -->

		<?php appthemes_before_blog_loop(); ?>

		<?php while ( have_posts() ) : the_post(); ?>

			<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

				<?php appthemes_before_blog_post_title(); ?>

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

				<?php appthemes_after_blog_post_title(); ?>

				<section class="overview cf">

					<?php appthemes_before_blog_post_content(); ?>

					<?php the_content( __('Read more', APP_TD ) ); ?>

					<?php appthemes_after_blog_post_content(); ?>

				</section>

			</article>

		<?php endwhile; ?>

		<?php appthemes_after_blog_loop(); ?>

		<?php if ( $wp_query->max_num_pages > 1 ) : ?>

			<nav class="pagination">
				<?php appthemes_pagenavi(); ?>
			</nav>

		<?php endif; ?>
	</div><!-- end #posts -->
</div>

<div id="sidebar" class="large-4 columns">

	<div class="sidebar-widget-wrap cf">
		<?php get_sidebar( app_template_base() ); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->
