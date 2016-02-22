<div class="row single-bid">

	<div class="large-2 small-3 columns bid-terms">
		<?php if ( current_user_can( 'view_bid', $proposal->get_id() ) ): ?>
			<div class="bid"><span class="proposal-amount"><?php appthemes_display_price( $proposal->amount ); ?></span> <span class="proposal-delivery"><?php echo sprintf( __( 'in %s ', APP_TD ), get_the_hrb_project_budget_units( $proposal->project, $proposal->_hrb_delivery ) ); ?></span> </div>
		<?php else: ?>
			<div class="bid"><span class="proposal-amount"><?php echo 'N/A'; ?></span> <span class="proposal-delivery"><?php echo 'N/A'; ?></span> </div>
		<?php endif; ?>
		<div class="submit-time"><span><?php echo __( 'Submitted', APP_TD ); ?></span> <span class="proposal-posted-time"><?php the_hrb_proposal_posted_time_ago( $proposal ); ?></span></div>
	</div>

	<div class="large-8 small-9 columns">
		<p class="bidder-info"><?php the_hrb_user_gravatar( $proposal->user_id, 45 ); ?><span class="bidder-display-name"><?php the_hrb_user_display_name( $proposal->user_id ); ?></span> <span class="bidder-location"><i class=" icon i-user-location"></i> <?php the_hrb_user_location( $proposal->user_id ); ?></span></p>
        <?php if ( $proposal->_hrb_featured ): ?>
			<span class="add-ons">
				<span class="featured"> <?php echo __( 'Featured', APP_TD ); ?></span>
			</span>
        <?php endif; ?>
		<p class="bidder-description"><?php the_hrb_user_bio( $proposal->user_id ); ?></p>
		<p class="bidder-skills"><?php the_hrb_user_skills( $proposal->user_id, ' ', '<span class="label">', '</span>'  ); ?></p>
	</div>

	<div class="large-2 columns review-meta">
		<?php the_hrb_user_rating( $proposal->user_id ); ?>
		<span><?php echo $user_reviews = appthemes_get_user_total_reviews( $proposal->user_id ); ?> <?php echo _n( 'Review', 'Reviews', $user_reviews ); ?></span>
		<span><?php echo __( 'Success Rate', APP_TD );?> <?php the_hrb_user_success_rate( $proposal->user_id ); ?></span>
	</div>

</div><!-- end row -->

<?php if ( current_user_can( 'view_bid', $proposal->get_id() ) ): ?>

	<div class="row bidder-message">

		<div class="large-2 columns bidder-message-label">
			<i class="icon i-message"></i><strong><?php echo __( 'Message', APP_TD ); ?></strong>
		</div>

		<div class="large-10 columns bidder-message-content">
			<span><?php echo $proposal->comment_content; ?></span>
		</div>

	</div><!-- end row -->

<?php else: ?>

	<div class="row bidder-message">
		&nbsp;
	</div>

<?php endif; ?>
