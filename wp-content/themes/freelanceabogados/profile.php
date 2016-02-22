<div id="main" class="large-8 columns user-profile">

	<div class="row profile">

		<div class="fr-img large-3 small-3 small-centered large-uncentered columns">
			<?php the_hrb_user_gravatar( $profile_author->ID, 175 ); ?>
			<div class="review-meta">
				<?php the_hrb_user_rating( $profile_author->ID, __( 'Sin calificación', APP_TD ) ); ?>
			</div>
		</div>

		<div class="large-9 columns user-info">

			<h2 class="user-name"><?php the_hrb_user_display_name( $profile_author->ID ); ?>
				<?php if ( get_the_hrb_user_location( $profile_author->ID ) ): ?>
					<span data-tooltip title="<?php echo esc_attr( __( 'User Location', APP_TD ) ); ?>" class="location"><i class="icon i-user-location"></i><?php the_hrb_user_location( $profile_author->ID ); ?></span>
				<?php endif; ?>
			</h2>

			<!-- freelancer meta above desc-->
			<div class="freelancer-meta cf">
				<div class="freelancer-rate"><?php the_hrb_user_rate( $profile_author ); ?></div>
				<div class="freelancer-success"><?php the_hrb_user_success_rate( $profile_author ); ?></div>
				<div class="freelancer-portfolio">
					<?php if ( $profile_author->user_url ): ?>
						<?php the_hrb_user_portfolio( $profile_author ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="user-actions">
				<?php the_hrb_user_actions( $profile_author->ID ); ?>
			</div>

			<div class="user-description"><?php the_hrb_user_bio( $profile_author ); ?></div>

			<div class="user-social-networks">

				<?php if ( ! empty( $profile_author->user_url ) ):  ?>
						<a data-tooltip title="<?php echo esc_attr( __( 'Website', APP_TD ) ); ?>" href="<?php echo esc_url( $profile_author->user_url ); ?>"><i class="icon i-website"></i></a>
				<?php endif; ?>

				<?php foreach( get_the_hrb_user_social_networks( $profile_author ) as $network_id => $value ): ?>
						<a data-tooltip target="_blank" title="<?php echo esc_attr( APP_Social_Networks::get_title( $network_id) ); ?>" href="<?php echo esc_url( APP_Social_Networks::get_url( $network_id, $value ) ); ?>"><i class="icon fi-social-<?php echo esc_attr( $network_id ); ?>"></i></a>
				<?php endforeach; ?>

			</div>


	  </div>

	</div><!-- end row -->

	<div class="row">
		<div class="large-12 columns skills">
			<div data-tooltip title="<?php echo esc_attr( __( 'The user skills', APP_TD ) ); ?>" class="user-skills"><?php the_hrb_user_skills( $profile_author, ' ', '<span class="label">', '</span>' ); ?></div>
		</div><!-- end 12-columns -->
	</div><!-- end row -->

	<div class="user-header-meta row">
	  <div class="meta-rating large-4 columns large-uncentered success-rate">
		  <i class="icon i-success-rate"></i><small class="label-meta"><?php echo __( 'Success Rate:', APP_TD ); ?></small> <strong><?php the_hrb_user_success_rate( $profile_author ); ?></strong>
	  </div>
	  <div class="meta-current large-4 columns large-uncentered active-projects">
		  <i class="icon i-active-projects"></i><small class="label-meta"><?php echo __( 'Active Projects:', APP_TD ); ?></small> <strong><?php the_hrb_user_related_active_projects_count( $profile_author ); ?></strong>
	  </div>
	  <div class="meta-completed large-4 columns large-uncentered completed-projects">
		<i class="icon i-completed-projects"></i><small class="label-meta"><?php echo __( 'Projects Completed:', APP_TD ); ?></small> <strong><?php the_hrb_user_completed_projects_count( $profile_author ); ?></strong>
	  </div>
	</div><!-- end row -->

	<div class="user-content-tabs row">
	  <div class="section-container auto section-tabs" data-section>

		<!-- dynamic content within tabs -->

		<?php if ( $projects_owned && $projects_owned->have_posts() ): ?>

			<section class="services-current <?php echo empty( $active ) ? $active = 'active' : ''; ?>">

				<p class="title" data-section-title><a href="#projects-employer"><?php echo __( 'Owned Projects', APP_TD ) ?></a></p>

				<div class="content" data-section-content>

					<?php appthemes_load_template( 'profile-section-projects.php', array( 'projects' => $projects_owned, 'relation' => 'employer', ) ); ?>

				</div>

			</section>

		<?php endif; ?>

		<?php if ( $projects_participant && $projects_participant->have_posts() ) : ?>

				<section class="services-current <?php echo empty( $active ) ? $active = 'active' : ''; ?>">

					<p class="title" data-section-title><a href="#projects-worker"><?php echo __( 'Awarded Projects', APP_TD ) ?></a></p>

					<div class="content" data-section-content>

						<?php appthemes_load_template( 'profile-section-projects.php', array( 'projects' => $projects_participant, 'relation' => 'worker' ) ); ?>

					</div>

				</section>

		<?php endif; ?>

		<section class="services-current <?php echo empty( $active ) ? $active = 'active' : ''; ?>">

			<p class="title" data-section-title><a href="#reviews"><?php echo __( 'Reviews', APP_TD ); ?></a></p>

			<div class="content" data-section-content>

				<?php appthemes_load_template( 'profile-section-reviews.php', array( 'reviews' => $reviews ) ); ?>

			</div>

		</section>

		<?php if ( $user_posts->have_posts() ) : ?>

				<section class="services-current <?php echo empty( $active ) ? $active = 'active' : ''; ?>">

					<p class="title" data-section-title><a href="#posts"><?php echo __( 'Posts', APP_TD ); ?></a></p>

					<div class="content" data-section-content>

						<?php appthemes_load_template( 'profile-section-posts.php', array( 'user_posts' => $user_posts ) ); ?>

					</div>

				</section>

		<?php endif; ?>

		<?php do_action( 'hrb_profile_tabs', $profile_author, $active ); ?>

	  </div>
	</div><!-- end row -->


</div><!-- end main -->

<div id="sidebar" class="large-4 columns">

	<div class="row">
		<?php get_sidebar('profile'); ?>
	</div><!-- end row -->

</div><!-- end #sidebar -->
