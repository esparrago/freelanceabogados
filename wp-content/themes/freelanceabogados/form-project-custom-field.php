<div class="row">
	<div class="large-12 columns">
		<fieldset class="custom-fields">
			<legend><?php echo $legend; ?></legend>
			<?php foreach( $fields as $field ): ?>

				<div class="form-custom-field">
					<?php echo $field ?>
				</div>

			<?php endforeach; ?>
		</fieldset>
	</div>
</div>