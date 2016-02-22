<div id="main" class="large-8 columns">
	<div class="row form-wrapper log-in">
	<div class="large-12 columns">

		<div class="row">
			<div class="large-6 columns login-box">
				<?php _e( 'Enter user details here', APP_TD ); ?>
				<?php require APP_FRAMEWORK_DIR . '/templates/form-login.php'; ?>
			</div>

			<?php if ( get_option('users_can_register') ): ?>

				<div class="large-6 columns register-box">
					<h5><?php echo $hrb_options->registration_box_title; ?></h5>
					<p class="registration-message"><?php echo $hrb_options->registration_box_text; ?></p>
					<?php wp_register( '<div class="button form-field" id="register-now">','</div>' ); ?>
				</div>

			<?php endif; ?>

		</div>
	</div>
</div>

</div>

<div id="sidebar" class="large-4 columns">

	<div class="sidebar-widget-wrap cf">
		<?php get_sidebar( app_template_base() ); ?>
	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->
