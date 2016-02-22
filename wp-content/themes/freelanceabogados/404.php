<div id="main" class="large-8 columns">
	<article class="fourzerofour">
		<div class="section-head">
			<h1 class="post-heading"><?php _e( 'Sorry, Page Not Found', APP_TD ); ?></h1>
		</div>

		<p><?php _e( "The page or listing you are trying to reach no longer exists or has expired.", APP_TD ); ?></p>
	</article>
</div><!-- /#main -->

<div id="sidebar" class="large-4 columns">
	<div class="sidebar-widget-wrap cf">

	<!-- dynamic sidebar -->
	<?php get_sidebar( app_template_base() ); ?>

	</div><!-- end .sidebar-widget-wrap -->
</div><!-- end #sidebar -->
