<?php
/**
 * The template for displaying user listings
 */
?>

<div id="main" class="large-8 columns">
	<?php get_template_part( 'loop', HRB_FREELANCER_UTYPE ); ?>
</div><!-- end #main -->

<div id="sidebar" class="large-4 columns">
	<div class="sidebar-widget-wrap cf">
		<?php get_sidebar('archive'); ?>
	</div><!-- end .sidebar-widget-wrap -->
</div><!-- end #sidebar -->