<div class="manage-project">
	<form id="manage_project" name="manage_project" method="post" class="custom" action="<?php echo esc_url( hrb_get_workspace_url( get_queried_object_id() ) ); ?>">

		<fieldset>

			<?php if ( HRB_PROJECT_STATUS_WORKING == $post->post_status ) : ?>

				<legend><?php _e( 'Actions', APP_TD ) ?></legend>

				<div class="row">
					<div class="large-8 columns">
						<div class="row collapse">
							<div class="large-5 small-5 columns">
								<span class="prefix"><?php echo __( 'Status', APP_TD ); ?></span>
							</div>
							<div class="large-7 small-7 columns">
								<select name="work_status">
									<?php foreach( get_the_hrb_participant_sel_statuses( $participant ) as $status ): ?>
										<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $status == $participant->status ); ?> ><?php echo hrb_get_participants_statuses_verbiages( $status ); ?></option>
									<?php endforeach; ?>
								 </select>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="large-12 columns">
						<textarea id="work_end_notes" name="work_end_notes" placeholder="<?php echo esc_attr( __( 'Closing notes', APP_TD ) ); ?>"><?php echo $participant->status_notes; ?></textarea>
					</div>
				</div>
				<div class="row">
					<div class="large-12 columns">
						<input type="submit" id="end_work" name="end_work" class="button" onclick="return confirm('<?php echo __( "Are you sure?", APP_TD ); ?>'); return false;" value="<?php echo ( ! $participant->status ? __( 'End Work', APP_TD ) : __( 'Update', APP_TD ) ); ?>" />
					</div>
				</div>

			<?php else: ?>

				<legend><i class="icon i-notes"></i> <?php _e( 'Notes', APP_TD ) ?></legend>
				<p class="participant-notes"><?php echo $participant->status_notes ? $participant->status_notes : __( 'None', APP_TD ); ?></p>

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