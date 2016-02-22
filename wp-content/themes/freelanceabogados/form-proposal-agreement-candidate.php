<form id="proposal-agreement" name="proposal_agreement" method="post" class="custom" action="<?php echo esc_url( get_the_hrb_proposal_url( $proposal ) ); ?>">

	<div class="row">
		<div class="large-12 columns">

			<?php if ( ! empty( $proposal->_hrb_employer_decision ) ): ?>

					<h5><?php _e( 'Employer Decision', APP_TD ); ?></h5>

					<fieldset>
						<div class="employer-decision <?php echo esc_attr( $proposal->_hrb_employer_decision ); ?>"><?php echo hrb_get_agreement_decision_verbiage( $proposal->_hrb_employer_decision ); ?></div>
						<?php if ( $proposal->_hrb_employer_notes ): ?>
							<div class="employer-decision-notes"><?php echo $proposal->_hrb_employer_notes; ?></div>
						<?php endif; ?>
					</fieldset>

			<?php endif; ?>

			<?php if ( $proposal->selected ): ?>

					<h5><?php _e( 'Project Terms', APP_TD ); ?></h5>

					<fieldset>
						<div class="project-terms"><?php echo esc_textarea( $proposal->project->_hrb_project_terms ? $proposal->project->_hrb_project_terms : __( 'None', APP_TD ) ); ?></div>
					</fieldset>

			<?php endif; ?>

			<?php if ( $user_can_edit_agreement && $proposal->selected ): ?>

						<h5><?php _e( 'Your Decision?', APP_TD ); ?></h5>

						<?php if ( ! empty( $proposal->_hrb_candidate_decision ) ): ?>

							<fieldset>
								<legend><?php _e( 'Previous', APP_TD ); ?></legend>
								<div class="candidate-decision <?php echo esc_attr( $proposal->_hrb_candidate_decision ); ?>"><?php echo hrb_get_agreement_decision_verbiage( $proposal->_hrb_candidate_decision ); ?></div>
							</fieldset>

						<?php endif; ?>

						<fieldset>
							<?php if ( HRB_TERMS_ACCEPT != $proposal->_hrb_employer_decision ):  ?>
								<label><input type="radio" name="candidate_decision" class="required" <?php checked( $proposal->_hrb_candidate_decision, HRB_TERMS_PROPOSE ); ?> value="propose"> <?php esc_attr_e( 'Propose Terms', APP_TD ); ?></label>
							<?php endif; ?>
							<?php if ( HRB_TERMS_DECLINE != $proposal->_hrb_employer_decision ):  ?>
								<label><input type="radio" name="candidate_decision" class="required" <?php checked( $proposal->_hrb_candidate_decision, HRB_TERMS_ACCEPT ); ?> value="accepted"> <?php esc_attr_e( 'Accept', APP_TD ); ?></label>
							<?php endif; ?>
							<label><input type="radio" name="candidate_decision" class="required" <?php checked( $proposal->_hrb_candidate_decision, HRB_TERMS_DECLINE ); ?> value="declined"> <?php esc_attr_e( 'Decline', APP_TD ); ?></label>
							<p>
								<label id="candidate_delete"><input type="checkbox" name="self_candidate_delete">
									<span><?php echo __( 'Remove me as candidate', APP_TD ); ?></span>
									<span data-tooltip title="<?php echo __( 'Selecting this option cancels negotiations immediately and removes you as a candidate. <br/><br/><em>Note:</em> The proposal will remain active unless you withdraw it.', APP_TD ); ?>" class="more-info">
										<i class="icon fi-info"></i>
									</span>
								</label>
							</p>
							<fieldset>
								<legend><?php _e( 'Notes', APP_TD ); ?></legend>
								<textarea name="candidate_notes" placeholder="<?php echo esc_attr( __( 'Add any notes for the employer here', APP_TD ) ); ?>"><?php echo esc_textarea( $proposal->candidate_notes ); ?></textarea>
							</fieldset>

						</fieldset>

			<?php elseif( ! empty( $proposal->_hrb_candidate_decision ) ): ?>

					<h5><?php _e( 'Your Decision', APP_TD ); ?></h5>

					<fieldset>
						<div class="candidate-decision <?php echo esc_attr( $proposal->_hrb_candidate_decision ); ?>"><?php echo hrb_get_agreement_decision_verbiage( $proposal->_hrb_candidate_decision ); ?></div>
						<?php if ( $proposal->_hrb_candidate_notes ): ?>
							<div class="candidate-decision-notes"><?php echo $proposal->_hrb_candidate_notes; ?></div>
						<?php endif; ?>
					</fieldset>

			<?php elseif( $proposal->selected ): ?>

				<h4><?php _e( 'Waiting for decision', APP_TD ); ?></h4>

			<?php endif; ?>

			<?php if ( $proposal->selected ): ?>

					<?php if ( $user_can_edit_agreement_terms ): ?>

							<fieldset>
								<legend><?php _e( 'Your Terms', APP_TD ); ?></legend>
								<textarea name="proposal_terms" placeholder="<?php echo esc_attr( __( 'Use this field to specify additional terms the employer must agree to before you accept the Project', APP_TD ) ); ?>"><?php echo esc_textarea( $proposal->_hrb_development_terms ); ?></textarea>
							</fieldset>

					<?php elseif ( $proposal->_hrb_development_terms ): ?>

							<fieldset>
								<legend><?php _e( 'Terms', APP_TD ); ?></legend>
								<div class="proposal-terms"><?php echo esc_textarea( $proposal->_hrb_development_terms ); ?></div>
							</fieldset>

					<?php endif; ?>

			<?php endif; ?>

		</div>
	</div>

	<div class="row">
		<div class="large-12 columns">

			<a href="<?php echo esc_url( $return_url ); ?>" class="button secondary"><?php esc_attr_e( '&#8592; BACK', APP_TD ); ?></a>

			<?php if ( $user_can_edit_agreement && $proposal->selected ): ?>

				<input type="submit" id="proposal_agreement" name="proposal_agreement" class="button" value="<?php echo __( 'Submit for Approval', APP_TD ); ?>" onclick='return confirm("<?php echo __( 'Confirm your decision?', APP_TD ); ?>")' />

			<?php endif; ?>

			<?php the_hrb_proposal_edit_link( $proposal->get_id(), '', '', '', array( 'class' => 'button success right' ) ); ?>

			<?php the_hrb_proposal_cancel_link( $proposal->get_id(), '', '', '', array( 'class' => 'button secondary right') ); ?>

		</div>
	</div>

	<?php
		hrb_hidden_input_fields(
			array(
				'proposal_id'		=> esc_attr( $proposal->get_id() ),
				'user_relation'		=> 'candidate',
				'decision'			=>  esc_attr( $proposal->_hrb_employer_decision ),
				'action'			=> 'proposal_agreement',
			)
		);
	?>

</form>