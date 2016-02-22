<div class="section-head">
	<h1><?php echo $title; ?></h1>
</div>

<form id="preview-project-form" class="custom main" method="post" action="<?php echo $form_action; ?>">

	<fieldset>
		<?php foreach( $preview_fields as $label => $value ): ?>

		<div class="row collapse field-preview">
			<div class="large-3 columns">
				<span><strong><?php echo $label; ?></strong></span>
			</div>
			<div class="large-9 columns preview-value">
				<span><?php echo $value; ?></span>
			</div>
		</div>

		<?php endforeach; ?>
	</fieldset>

	<?php do_action( 'hrb_project_form_preview', $project ); ?>

	<fieldset>
		<?php do_action( 'app_project_form_preview_fields', $project ); ?>

		<?php wp_nonce_field('hrb_post_project'); ?>

		<?php hrb_hidden_input_fields( array( 'action' => $action ) ); ?>

		<?php if ( $previous_step = appthemes_get_previous_step() ) : ?>
			<input class="button secondary previous-step" previous-step-url="<?php echo esc_url( appthemes_get_step_url( $previous_step ) ); ?>" value="<?php echo esc_attr( $bt_prev_step_text ); ?>" type="submit" />
		<?php endif; ?>

		<input class="button" type="submit" value="<?php echo esc_attr( $bt_step_text ); ?>" />
	</fieldset>
</form>

