<div class="row create-project purchase-plans">

	<div id="main" class="large-8 columns create-projects-section">
		<div class="form-wrapper checkout-process">
			<?php appthemes_display_form_progress(); ?>
			<?php appthemes_load_template( $template, $vars ); ?>
		</div>
	</div>

	<div id="sidebar" class="large-4 columns create-project-sidebar">
		<div class="sidebar-widget-wrap cf">
			<!-- dynamic sidebar -->
			<?php get_sidebar( $sidebar ); ?>
		</div><!-- end .sidebar-widget-wrap -->
	</div><!-- end #sidebar -->

</div>

