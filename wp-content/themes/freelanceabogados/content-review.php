<?php $workspace_id = hrb_get_review_workspace( $review ); ?>

<li class="group">
	<?php the_hrb_user_gravatar( $reviewer->ID ); ?>
	<div>
		<h2><a href="<?php echo get_permalink( $review->get_post_ID() ); ?>" rel="bookmark"><?php echo get_the_title( $review->get_post_ID() ); ?></a> <span class="project-status <?php echo esc_attr( get_post_status( $workspace_id ) ); ?>"><span class="label"><?php the_hrb_workspace_status( $workspace_id ); ?></span></span></h2>
	  <h5>
		  <?php printf( __( 'Reviewed on %s by %s.' , APP_TD ),  mysql2date( get_option('date_format'), $review->get_date() ), html_link( get_the_hrb_user_profile_url( $reviewer ), $reviewer->user_login ) ); ?>
		  <span class="review-rating"><?php hrb_rating_html( $review->get_rating() ); ?></span>
	  </h5>

	  <p><?php echo $review->get_content(); ?></p>

	  <?php if ( ! get_query_var('dashboard') || 'workspace' != hrb_get_dashboard_page() ): ?>

			<?php the_hrb_workspace_link( $workspace_id, '', '', '', array( 'class' => 'button tiny workspace secondary' ) ); ?>

	  <?php endif; ?>
	</div>
</li>