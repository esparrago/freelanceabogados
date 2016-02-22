<form id="proposal-agreement" name="proposal_agreement" method="post" class="custom" action="<?php echo esc_url( get_the_hrb_proposal_url( $proposal ) ); ?>">

	<div class="row">
		<div class="large-12 columns">

			<?php if ( ! empty( $proposal->_hrb_employer_decision ) ): ?>

				<h5><?php _e( 'Your Decision', APP_TD ); ?></h5>

				<fieldset>
					<div class="employer-decision <?php echo esc_attr( $proposal->_hrb_employer_decision ); ?>"><?php echo hrb_get_agreement_decision_verbiage( $proposal->_hrb_employer_decision ); ?></div>
					<?php if ( $proposal->_hrb_employer_notes ): ?>
						<div class="employer-decision-notes"><?php echo $proposal->_hrb_employer_notes; ?></div>
					<?php endif; ?>
				</fieldset>

			<?php endif; ?>

			<?php if ( ! $user_can_edit_agreement_terms && $proposal->project->_hrb_project_terms ): ?>

				<h5><?php _e( 'Your Project Terms', APP_TD ); ?></h5>

				<fieldset>
					<div class="project-terms"><?php echo esc_textarea( $proposal->project->_hrb_project_terms ); ?></div>
				</fieldset>

			<?php endif; ?>

			<?php if ( ! empty( $proposal->_hrb_candidate_decision ) ): ?>

					<h5><?php _e( 'Candidate Decision / Terms', APP_TD ); ?></h5>

					<fieldset>
						<div class="candidate-decision <?php echo esc_attr( $proposal->_hrb_candidate_decision ); ?>"><?php echo hrb_get_agreement_decision_verbiage( $proposal->_hrb_candidate_decision ); ?></div>
						<?php if ( $proposal->_hrb_candidate_notes ): ?>
							<div class="candidate-decision-notes"><?php echo $proposal->_hrb_candidate_notes; ?></div>
						<?php endif; ?>
					</fieldset>

			<?php elseif( $proposal->selected ): ?>

					<fieldset>
						<h4><?php _e( 'Waiting for decision', APP_TD ); ?></h4>
					</fieldset>

			<?php endif; ?>

			<?php if ( $proposal->selected ): ?>

					<?php if ( $proposal->_hrb_development_terms ): ?>

							<fieldset>
								<legend><?php _e( 'Terms', APP_TD ); ?></legend>
								<div class="proposal-terms"><?php echo esc_textarea( $proposal->_hrb_development_terms ); ?></div>
							</fieldset>

							<hr />

					<?php endif; ?>

					<?php if ( $user_can_edit_agreement && ! empty( $proposal->_hrb_candidate_decision ) ): ?>

							<h5><?php _e( 'Your decision?', APP_TD ); ?></h5>

							<?php if ( ! empty( $proposal->_hrb_employer_decision ) ): ?>

								<fieldset>
									<legend><?php _e( 'Previous', APP_TD ); ?></legend>
									<div class="employer-decision <?php echo esc_attr( $proposal->_hrb_employer_decision ); ?>"><?php echo hrb_get_agreement_decision_verbiage( $proposal->_hrb_employer_decision ); ?></div>
								</fieldset>

							<?php endif; ?>

							<fieldset>
								<?php if ( HRB_TERMS_ACCEPT != $proposal->_hrb_candidate_decision ):  ?>
									<label><input type="radio" name="employer_decision" class="required" <?php checked( $proposal->_hrb_employer_decision, HRB_TERMS_PROPOSE ); ?> value="propose"> <?php esc_attr_e( 'Propose Terms', APP_TD ); ?></label>
								<?php endif; ?>
								<?php if ( HRB_TERMS_DECLINE != $proposal->_hrb_candidate_decision ):  ?>
									<label><input type="radio" name="employer_decision" class="required" <?php checked( $proposal->_hrb_employer_decision, HRB_TERMS_ACCEPT ); ?> value="accepted"> <?php esc_attr_e( 'Accept', APP_TD ); ?></label>
								<?php endif; ?>
								<label><input type="radio" name="employer_decision" class="required" <?php checked( $proposal->_hrb_employer_decision, HRB_TERMS_DECLINE ); ?> value="declined"> <?php esc_attr_e( 'Decline', APP_TD ); ?></label>
								<p>
									<label id="candidate_delete"><input type="checkbox" name="employer_candidate_delete">
										<span><?php _e( 'Unselect candidate', APP_TD ); ?></span>
										<span data-tooltip title="<?php _e( 'Selecting this option cancels negotiations immediately and unselects the candidate. <br/><br/><em>Note:</em> The proposal will remain active unless the user withdraws the proposal.', APP_TD ); ?>" class="more-info">
											<i class="icon fi-info"></i>
										</span>
									</label>
								</p>
								<fieldset>
									<legend><?php _e( 'Notes', APP_TD ); ?></legend>
									<textarea name="employer_notes" placeholder="<?php echo esc_attr( __( 'Add any notes for the candidate here', APP_TD ) ); ?>"><?php echo esc_textarea( $proposal->_hrb_employer_notes ); ?></textarea>
								</fieldset>

							</fieldset>

					<?php elseif( ! empty( $proposal->_hrb_employer_decision ) ): ?>

							<fieldset>
								<legend><?php _e( 'Decision', APP_TD ); ?></legend>
								<p><?php echo $proposal->_hrb_employer_decision; ?></p>
								<p><?php echo $proposal->_hrb_employer_notes; ?></p>
							</fieldset>

					<?php endif; ?>

			<?php endif; ?>

			<?php if ( $user_can_edit_agreement_terms ): ?>

				<fieldset>
					<legend><?php _e( 'Your Terms for the Project', APP_TD ); ?></legend>
					<textarea name="project_terms" placeholder="<?php echo esc_attr( __( 'Use this field to specify additional terms the Candidate must accept before you award the Project', APP_TD ) ); ?>"><?php echo esc_textarea( get_the_hrb_project_dev_terms() ); ?></textarea>
				</fieldset>

			<?php endif; ?>

		</div>
	</div>

	<div class="row">
		<div class="large-12 columns">

			<a href="<?php echo esc_url( $return_url ); ?>" class="button secondary"><?php esc_attr_e( '&#8592; BACK', APP_TD ); ?></a>

			<?php if ( $user_can_edit_agreement ): ?>

				<?php if ( $user_can_select_proposal ): ?>

					<input type="submit" name="proposal_select" class="button" value="<?php esc_attr_e( 'Select Proposal', APP_TD ); ?>" onclick='return confirm("<?php echo __( 'Select this as the winning Proposal? Candidate will need to approve. Continue?', APP_TD ); ?>")' />

					<?php the_hrb_proposals_view_link(); ?>

				<?php elseif( $proposal->selected ): ?>

					<?php if ( ! $proposal->_hrb_candidate_decision ): ?>
							<input type="submit" name="employer_candidate_delete" onclick='return confirm("<?php echo __( "Are you sure you want to deselect this candidate?", APP_TD ); ?>")' class="button secondary" value="<?php echo __( 'Cancel Agreement', APP_TD ); ?>" />
					<?php endif; ?>

					<input type="submit" id="proposal_agreement" name="proposal_agreement" class="button" value="<?php echo __( 'Submit for Approval', APP_TD ); ?>" onclick='return confirm("<?php echo __( 'Confirm your decision?', APP_TD ); ?>")' />

				<?php endif; ?>

			<?php endif; ?>

		</div>
	</div>

	<?php
		hrb_hidden_input_fields(
			array(
				'proposal_id'	=> esc_attr( $proposal->get_id() ),
				'user_relation'	=> 'employer',
				'decision'		=>  esc_attr( $proposal->_hrb_candidate_decision ),
				'action'		=> 'proposal_agreement',
			)
		);
	?>

</form>