<fieldset class="proposal">
	<div class="row">
		<div class="large-12 columns">
			<legend class="project-title"><span><?php the_hrb_project_title( $proposal->project->ID ); ?></span></legend>
		</div>
	</div>
	<div class="row">
		<div class="large-12 columns proposal-meta">
			<fieldset class="large-4 columns budget">
				<span class="project-budget"><i class="icon i-budget"></i><small><?php echo __( 'Budget:', APP_TD  ); ?></small> <?php the_hrb_project_budget( $proposal->project ); ?></span>
			</fieldset>
			<fieldset class="large-4 columns average">
				<span class="project-avg-bid"><i class="icon i-avg-proposals"></i><small><?php echo __( 'Avg. Budget:', APP_TD  ); ?></small><?php echo appthemes_display_price( appthemes_get_post_avg_bid( $proposal->project->ID ) ); ?></span>
			</fieldset>
			<fieldset class="large-4 columns total">
				<span class="project-total-bids"><i class="icon i-proposals-count"></i><small><?php echo __( 'Total Proposals:', APP_TD  ); ?></small> <?php echo appthemes_get_post_total_bids( $proposal->project->ID ); ?></span>
			</fieldset>
		</div>
	</div>
</fieldset>

<form id="create-proposal-form" class="proposal custom main" enctype="multipart/form-data" method="post" action="<?php echo esc_url( $form_action ); ?>">
	<fieldset class="proposal">
		<legend class="proposal-section"><?php _e( 'Proposal', APP_TD ); ?></legend>
		<div class="row">
			<div class="large-6 columns">
				<div class="row collapse">
					<div class="large-4 small-4 columns proposal-amount">
						<span class="prefix"><?php _e( 'Your Offer', APP_TD ); ?></span>
					</div>
					<div class="large-1 small-1 columns">
						<span class="prefix proposal-currency"><?php echo get_the_hrb_project_budget_currency( $proposal->project, 'symbol' ); ?></span>
					</div>
					<div class="large-7 small-7 columns">
						<input id="amount" name="amount" type="text" class="required" placeholder="<?php echo esc_attr( __( 'e.g: 40', APP_TD ) ); ?>" value="<?php echo esc_attr( $proposal->amount ); ?>"/>
					</div>
				</div>
			</div>
			<div class="large-6 columns">
				<div class="row collapse">
					<div class="large-4 small-4 columns proposal-amount">
						<span class="prefix delivery-type"><?php echo $proposal->label_delivery_type; ?></span>
					</div>
					<div class="large-6 small-6 columns">
						<input id="delivery" name="delivery" type="text" class="proposal-delivery required" placeholder="<?php echo esc_attr( __( 'e.g: 3', APP_TD ) ); ?>" value="<?php echo esc_attr( $proposal->_hrb_delivery ); ?>"/>
					</div>
					<div class="large-2 small-2 columns">
						<span class="postfix"><?php echo $proposal->label_delivery_unit; ?></span>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="large-12 columns">
				<label for="comment"><?php _e( 'Details', APP_TD ); ?></label>
				<textarea id="comment" name="comment" class="proposal-description required" placeholder="<?php echo esc_attr( __( 'Provide detailed information and explain why the project should be assigned to you', APP_TD ) ); ?>"><?php echo strip_tags( $proposal->comment_content ); ?></textarea>
			</div>
		</div>
		<div class="row">
			<div class="large-12 columns">
				<div class="row collapse featured-option">
					<input id="featured" name="featured" type="checkbox" style="display: none;" <?php echo esc_attr( $featured_disabled ); ?>>
					<span class="custom checkbox <?php echo esc_attr( $featured_disabled ); ?>"></span> <?php echo sprintf( __( 'Feature Proposal %s', APP_TD ), sprintf( _n( '(1 credit)', '(%d credits)', hrb_required_credits_to('feature_proposal'), APP_TD ), hrb_required_credits_to('feature_proposal') ) ); ?>
				</div>
			</div>
		</div>
	</fieldset>

	<?php do_action( 'hrb_proposal_custom_fields', $proposal ) ; ?>

	<?php do_action( 'hrb_proposal_form', $proposal ); ?>

	<?php if ( hrb_credit_plans_active() && $proposal->_hrb_credits_required ): ?>

		<div class="row credits-info">
			<div class="large-6 columns">
				<div class="row collapse">
					<div class="large-8 small-10 columns">
						<span class="prefix"><?php echo sprintf( __( 'Credit Balance %s', APP_TD ), html_link( hrb_get_credits_purchase_url(), __( '(get more)', APP_TD ) ) ); ?></span>
					</div>
					<div class="large-4 small-2 columns">
						<span class="prefix credits-balance"><?php echo hrb_get_user_credits(); ?></span>
					</div>
				</div>
			</div>
			<div class="large-6 columns">
				<div class="row collapse">
					<div class="large-8 small-10 columns">
						<span class="prefix"><?php echo __( 'Required Credits', APP_TD ); ?></span>
					</div>
					<div class="large-4 small-2 columns">
						<span class="prefix credits-required"><?php echo esc_attr( $proposal->_hrb_credits_required ); ?></span>
					</div>
				</div>
			</div>
		</div>

	<?php endif; ?>

	<fieldset class="submit">
		<?php do_action( 'hrb_proposal_form_fields' ); ?>

		<div class="row">
			<div class="large-12 columns">
				<div class="row collapse">
					<div class="large-12 columns">
						<p>
							<input id="accept_site_terms" name="accept_site_terms" type="checkbox" style="display: none;">
							<span class="custom checkbox"></span> <?php echo html( 'a', array( 'target' => '_new', 'href' => hrb_get_site_terms_url(), 'class' => 'site-terms-link' ), __( 'I agree with site terms', APP_TD ) ); ?>
						</p>
					</div>
				</div>
				<div class="row collapse">
					<div class="large-12 column">
						<input type="submit" class="cancel button secondary" value="<?php esc_attr_e( 'Cancel', APP_TD ); ?>" />
						<input type="submit" id="submit_proposal" class="agree button" disabled onclick='return confirm("<?php echo __( 'Your applying to this project. Proceed?', APP_TD ); ?>")' value="<?php echo esc_attr( $bt_step_text ); ?>" />
					</div>
					<div class="no-credits-warning" style="display: none">
						<span class="no-credits-message"><i class="icon fi-alert"></i> <?php echo sprintf( __( 'Not enough credits. Please <a href="%s">purchase more credits</a> to continue.', APP_TD ), hrb_get_credits_purchase_url() ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

		<?php
			wp_comment_form_unfiltered_html_nonce();

			hrb_hidden_input_fields(
				array(
					'action'				=> esc_attr( $action ),
					'credits_required'		=> esc_attr( $proposal->_hrb_credits_required ),
					'currency'				=> esc_attr( $proposal->project->_hrb_budget_currency ),
					'comment_ID'			=> esc_attr( $proposal->id ),
					'comment_post_ID'		=> esc_attr( $proposal->project->ID ),
					'comment_type'			=> esc_attr( appthemes_bidding_get_args('comment_type') ),
					'url_referer'			=> esc_url( $_SERVER['REQUEST_URI'] ),
				)
			);
		?>

</form>