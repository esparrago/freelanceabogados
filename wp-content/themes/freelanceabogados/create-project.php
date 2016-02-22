<?php
/**
 * Template Name: Create Project
 *
 * Also used as the edit project page
 *
 */
?>

<div class="row create-project purchase-plans">

	<div id="main" class="large-8 columns create-projects-section">
		<div class="form-wrapper">
			<?php appthemes_display_form_progress(); ?>
			<?php appthemes_display_checkout(); ?>
		</div>
	</div>

	<div id="sidebar" class="large-4 columns create-project-sidebar">
		<div class="sidebar-widget-wrap cf">
			<!-- dynamic sidebar -->
			<?php dynamic_sidebar('hrb-create-project'); ?>
		</div><!-- end .sidebar-widget-wrap -->
	</div><!-- end #sidebar -->

</div>