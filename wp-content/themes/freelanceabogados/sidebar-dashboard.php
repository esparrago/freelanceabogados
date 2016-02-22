<!-- begin dashboard sidebar -->
<div id="sidebar" class="large-4 columns dashboard-side">

	<div class="sidebar-widget-wrap cf">

		<?php appthemes_before_sidebar_widgets( 'dashboard' ); ?>

		<h3><?php echo sprintf( __( 'Welcome %s', APP_TD ), $dashboard_user->display_name ); ?></h3>

		<div class="dashboard-user-meta cf">
			<div class="user-gravatar"><?php the_hrb_user_gravatar( $dashboard_user->ID, 75 ); ?></div>
			<div class="user-name"><?php echo $dashboard_user->first_name . ' ' . $dashboard_user->last_name; ?></div>
			<div class="edit-profile"><?php echo html_link( appthemes_get_edit_profile_url(), __( 'Edit Profile', APP_TD ) ); ?></div>
		</div>

		<?php if ( current_user_can('edit_projects') ): ?>
			<div class="add-project cf"><span>+</span><?php echo html_link( get_the_hrb_project_create_url(), __( 'Add a Project', APP_TD ) ); ?></div>
		<?php endif; ?>

		<aside id="dashboard-side-nav">
			<div class="section-head">
				<h3><?php _e( 'Dashboard', APP_TD ); ?></h3>
			</div>

			<ul class="dashboard-links">
				<li class="notifications <?php hrb_dashboard_current_page_class('dashboard'); ?>">
					<a href="<?php echo esc_url( hrb_get_dashboard_url_for() ); ?>"><?php echo __( 'Recent', APP_TD ); ?></a><span>&nbsp;</span>
				</li>
				<li class="notifications <?php hrb_dashboard_current_page_class('notifications'); ?>">
					<a href="<?php echo esc_url( hrb_get_dashboard_url_for('notifications') ); ?>"><?php echo __( 'Notifications', APP_TD ); ?><span><?php echo sprintf( ' %d', appthemes_get_user_total_unread_notifications( $dashboard_user->ID ) ); ?></span></a>
				</li>
				<li class="projects <?php hrb_dashboard_current_page_class('projects'); ?>">
					<a href="<?php echo esc_url( hrb_get_dashboard_url_for('projects') ); ?>"><?php echo __( 'Projects', APP_TD ); ?></a><span>&nbsp;</span>
				</li>
				<?php if ( current_user_can('edit_bids') && appthemes_get_user_total_bids( $dashboard_user->ID ) > 0  ): ?>
					<li class="proposals <?php hrb_dashboard_current_page_class('proposals'); ?>">
						<a href="<?php echo esc_url( hrb_get_dashboard_url_for('proposals') ); ?>"><?php echo __( 'Proposals', APP_TD ); ?></a><span>&nbsp;</span>
					</li>
				<?php endif; ?>
				<?php if ( hrb_get_dashboard_reviews() ) : ?>
					<li class="reviews <?php hrb_dashboard_current_page_class('reviews'); ?>">
						<a href="<?php echo esc_url( hrb_get_dashboard_url_for('reviews') ); ?>"><?php echo __( 'Reviews', APP_TD ); ?></a><span>&nbsp;</span>
					</li>
				<?php endif; ?>
				<li class="payments <?php hrb_dashboard_current_page_class('payments'); ?>">
					<a href="<?php echo esc_url( hrb_get_dashboard_url_for('payments') ); ?>"><?php echo __( 'Payments', APP_TD ); ?></a><span>&nbsp;</span>
				</li>
			</ul>
		</aside>

		<aside id="dashboard-acct-stats">

		 <div class="section-head">
		   <h3><?php echo __( 'Stats', APP_TD ); ?></h3>
		 </div>

		 <ul class="dashboard-stats">
		   <li class="rating">
			   <?php echo __( 'Rating', APP_TD ); ?>
			   <span><?php the_hrb_user_rating( $dashboard_user, __( 'n/a', APP_TD ) ); ?></span>
		   </li>
		   <li class="projects-current">
			   <?php echo __( 'Active Projects', APP_TD ); ?>
			   <span><?php the_hrb_user_related_active_projects_count( $dashboard_user ); ?></span>
		   </li>
		   <li class="projects-complete">
			   <?php echo __( 'Projects Completed', APP_TD ); ?>
			   <span><?php the_hrb_user_completed_projects_count( $dashboard_user ); ?></span>
		   </li>
		   <li class="reviews-received">
			   <?php echo __( 'Reviews Received', APP_TD ); ?>
			   <span><?php the_hrb_user_total_reviews( $dashboard_user ); ?></span>
		   </li>
		   <li class="reviews-given">
			   <?php echo __( 'Reviews Given', APP_TD ); ?>
			   <span><?php the_hrb_user_total_authored_reviews( $dashboard_user ); ?></span>
		   </li>
		   <li class="reviews-success_rate">
			   <?php echo __( 'Success Rate', APP_TD ); ?>
			   <span><?php the_hrb_user_success_rate( $dashboard_user ); ?></span>
		   </li>
		 </ul>

		</aside>

		<aside id="dashboard-acct-info">
		  <div class="section-head">
			<h3><?php echo __( 'Account Info', APP_TD ); ?></h3>
		  </div>

		  <ul class="links">
			  <li><?php echo __( 'Email shared in projects:', APP_TD ); ?></li>
			  <?php the_hrb_user_contact_info( $dashboard_user->ID, '<li>', '</li>' ); ?>
		  </ul>

		</aside>

		<?php appthemes_after_sidebar_widgets( 'dashboard' ); ?>

	</div><!-- end .sidebar-widget-wrap -->

</div><!-- end #sidebar -->
