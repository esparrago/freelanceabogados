<?php
/**
 * Template Name: Purchase Credits
 */
?>

<div id="main" class="large-8 columns order-checkout purchase-plans">
	<div class="form-wrapper">
		<?php appthemes_display_form_progress(); ?>

		<?php appthemes_display_checkout(); ?>
	</div>
</div>

<div id="sidebar" class="large-4 columns">

	<div class="sidebar-widget-wrap cf">
		<!-- dynamic sidebar -->
		<?php dynamic_sidebar('hrb-create-proposal'); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->