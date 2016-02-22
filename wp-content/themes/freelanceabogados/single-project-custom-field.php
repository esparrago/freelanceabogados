<?php foreach( $fields as $field ): ?>

	<?php if ( 'file' != $field['type'] ): ?>

		<fieldset class="project-custom-field">
			<legend><?php echo $field['desc']; ?></legend>
			<span class="custom-field-value"><?php echo $field['output']; ?></span>
		</fieldset>

	<?php else: ?>

		<?php echo $field['output']; ?>

	<?php endif; ?>

<?php endforeach; ?>
