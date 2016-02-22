<?php
	// footer can be setup to a maximum of 3 columns

	$sidebars = (int) is_active_sidebar('hrb-footer') + (int) is_active_sidebar('hrb-footer2') + (int) is_active_sidebar('hrb-footer3');

	if ( ! $sidebars ) { $sidebars = 1;	}

	$columns = 12 / $sidebars;
?>

<!-- Footer Widgets -->
<div id="footer">
	<div class="row widgets-footer">
		<div class="large-12 columns wrap">

			<div id="footer-widget1" class="f-widget <?php echo "large-{$columns}"; ?> columns">
				<?php dynamic_sidebar('hrb-footer'); ?>
			</div>

			<?php if ( is_active_sidebar('hrb-footer2') ) : ?>
				<div id="footer-widget2" class="f-widget <?php echo "large-{$columns}"; ?> columns">
					<?php dynamic_sidebar('hrb-footer2'); ?>
				</div>
			<?php endif; ?>

			<?php if ( is_active_sidebar('hrb-footer3') ) : ?>
				<div id="footer-widget3" class="f-widget <?php echo "large-{$columns}"; ?> columns">
					<?php dynamic_sidebar('hrb-footer3'); ?>
				</div>
			<?php endif; ?>


		</div>
	</div>

	<!-- End footer Widgets -->

	<!-- Footer -->
	<footer class="row footer">
		<div class="large-12 columns">

			<div id="theme-info" class="footer-info large-7 columns">
			</div>

			 <div id="footer-menu" class="footer-links large-5 columns">
			  <?php the_hrb_footer_menu(); ?>
			</div>

		</div>
	</footer>

</div><!-- end #footer -->
