<div class="manage-project">
	<form id="manage_project" name="manage_project" method="post" class="custom" action="<?php echo esc_url( hrb_get_workspace_url( get_queried_object_id() ) ); ?>">

		<fieldset>
			<legend><?php _e( 'Actions', APP_TD ) ?></legend>

			<div class="row">
				<div class="large-8 columns">
					<div class="row collapse">
						<div class="large-5 small-5 columns">
							<span class="prefix"><?php echo __( 'Status', APP_TD ); ?></span>
						</div>
						<div class="large-7 small-7 columns">
							<select name="project_status" <?php disabled( ! $can_edit ); ?>>
								<?php foreach( get_the_hrb_participant_sel_statuses( $participant ) as $status ): ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $project->post_status == $status ); ?> ><?php echo hrb_get_project_statuses_verbiages( $status ); ?></option>
								<?php endforeach; ?>
							 </select>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<div class="large-12 columns">
					<textarea id="project_end_notes" name="project_end_notes" <?php disabled( ! $can_edit ); ?> placeholder="<?php echo esc_attr( __( 'Closing Notes', APP_TD ) ); ?>"></textarea>
				</div>
			</div>

			<?php if ( $can_edit ): ?>

				<div class="row">
					<div class="large-12 columns">
						<input type="submit" id="end_project" name="end_project" class="button" value="<?php echo __( 'End Project', APP_TD ); ?>"/>
					</div>
				</div>

			<?php endif; ?>

		</fieldset>

		<?php

			// nonce && hidden fields
			wp_nonce_field('hrb-manage-project');

			hrb_hidden_input_fields( array(
				'workspace_id'	=> get_queried_object_id(),
				'project_id'	=> esc_attr( $project->ID ),
				'action'		=> 'manage_project',
			) );
		?>

	</form>
</div>
