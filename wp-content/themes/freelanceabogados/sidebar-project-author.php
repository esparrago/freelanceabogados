<div class="row single-posted-by-widget">
	<div class="large-4 small-4 columns posted-by-gravatar-widget">
		<?php the_hrb_user_gravatar( $user, $size = 100 ); ?>
	</div>
	<div class="large-8 small-8 columns posted-by-widget">
		<p><span><?php echo __( 'Posted by', APP_TD ); ?></span>
			<span class="meta name"><?php echo html_link( get_the_hrb_user_profile_url( $user ), $user->display_name ); ?></span>
			<span class="meta member"><?php echo __( 'Member Since:', APP_TD ); ?> <?php the_hrb_user_member_since( $user ); ?></span>
			<span class="meta location"><i class="icon i-user-location"></i> <?php the_hrb_user_location( $user ); ?></span>
			<span class="meta rating"><?php echo __( 'Rating:', APP_TD ); ?> <?php the_hrb_user_avg_rating( $user ); ?> (<?php echo sprintf( _n( '1 Review', '%d Reviews', $user_reviews, APP_TD ), $user_reviews ); ?>)</span>
		</p>
	</div>
</div>

<div class="single-by-widget-meta row">
	<div class="large-12 columns">
		<div class="row user-projects-stats">
			<div class="small-6 large-6 columns active-projects">
				<span>
					<i class="icon i-active-projects"></i><small><?php echo __( 'Active Projects: ', APP_TD ); ?></small>
					<?php echo the_hrb_user_related_active_projects_count( $user ); ?>
				</span>
			</div>
			<div class="small-6 large-6 columns completed-projects">
				<span>
					<i class="icon i-completed-projects"></i><small><?php echo __( 'Completed Projects: ', APP_TD ); ?></small>
					<?php echo the_hrb_user_completed_projects_count( $user );  ?>
				</span>
			</div>
		</div>
		<div class="row more-projects-link">
			<div class="columns">
				<?php echo html_link( get_the_hrb_user_profile_url( $user ), sprintf( __( 'See All %s\'s Projects', APP_TD ), $user->display_name ) ); ?>
			</div>
		</div>
	</div>
</div>