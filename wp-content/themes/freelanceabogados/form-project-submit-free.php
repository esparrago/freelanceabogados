<div class="section-head">
	<h1><?php echo $title; ?></h1>
</div>

<div class="end-no-purchase">

	<fieldset>
		<?php if ( 'publish' == get_post_status( $project->ID ) ): ?>

			<?php echo __( 'Your project was submitted with success and is now live!', APP_TD ); ?>

		<?php else: ?>

			<?php echo __( 'Your project was submitted and is waiting moderation. ', APP_TD ); ?>

		<?php endif; ?>

		<?php do_action( 'app_project_form_end_free', $project ); ?>
	</fieldset>

	<fieldset>
		<input type="submit" class="button" value="<?php echo esc_attr( $bt_step_text ); ?>" onClick="location.href='<?php echo esc_url( $bt_url ); ?>';return false;">
	</fieldset>

</div>
