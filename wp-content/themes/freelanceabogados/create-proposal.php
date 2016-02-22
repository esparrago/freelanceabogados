<?php
/**
 * Template Name: Create Proposal
 *
 * Also used as the edit proposal page
 *
 */
?>

<div id="main" class="large-8 columns">
	<?php appthemes_display_checkout(); ?>
</div>

<div id="sidebar" class="large-4 columns create-proposal-sidebar">

	<div class="sidebar-widget-wrap cf">
		<!-- static sidebar -->
		<?php appthemes_load_template( 'sidebar-project-author.php', array( 'user' => $project_author, 'user_reviews' => $project_author_reviews ) ); ?>

		<!-- dynamic sidebar -->
		<?php dynamic_sidebar('hrb-create-proposal'); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->