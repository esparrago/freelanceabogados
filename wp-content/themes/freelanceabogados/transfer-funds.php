<?php
/**
 * Template Name: Transfer Funds
 */
?>

<div id="main" class="large-8 columns order-checkout transfer-funds">
	<div class="form-wrapper">
		<?php appthemes_display_checkout(); ?>
	</div>
</div>

<div id="sidebar" class="large-4 columns">

	<div class="sidebar-widget-wrap cf">
		<!-- dynamic sidebar -->
		<?php dynamic_sidebar('hrb-transfer-funds'); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->