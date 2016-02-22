<div id="save-filter-modal" class="reveal-modal small">
	<form id="save-filter-form" class="custom" method="post" action="#">

		<!-- Custom Selects -->
		<fieldset>
			<legend><?php echo __( 'Save Filter', APP_TD ); ?></legend>
			<label><?php echo __( 'Name this Filter', APP_TD ); ?></label>
			<input type="text" name="saved-filter-name" class="required" placeholder="<?php echo __( 'Filter Name', APP_TD ); ?>" value="<?php echo esc_attr( $saved_filter['name'] ); ?>" />

			<?php // @todo future release ?>

			<div style='display: none'>
				<label for="email-digest"><?php echo __( 'Notify me', APP_TD ); ?></label>
				<select id="saved-filter-digest" name="saved-filter-digest" class="small">
				  <option value="daily" <?php selected ( 'daily' == $saved_filter['digest'] ); ?>><?php echo __( 'Daily', APP_TD ); ?></option>
				  <option value="weekly" <?php selected ( 'weekly' == $saved_filter['digest'] ); ?>><?php echo __( 'Weekly', APP_TD ); ?></option>
				</select>
			</div>

			<div id="save-filter-container" class="save-filter-container">
				<a class="button" id="save-filter"><span><?php echo __( 'Save', APP_TD ); ?></span></a>
				<a class="button secondary" id="cancel-save-filter"><span><?php echo __( 'Cancel', APP_TD ); ?></span></a>
			</div>
		</fieldset>

		<?php wp_nonce_field( 'hrb-save-filter' ); ?>

		<input type="hidden" name="action" value="edit-saved-filter" />
		<input type="hidden" name="saved-filter-slug" value="<?php echo esc_attr( $saved_filter['slug'] ); ?>" />

	</form>
	<a class="close-reveal-modal">&#215;</a>
</div>