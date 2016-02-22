<article id="freelancer-<?php echo $user->ID; ?>" <?php hrb_user_class( HRB_FREELANCER_UTYPE, $user ); ?>>
	<div class="row">

		<div class="fr-img large-3 small-3 small-centered large-uncentered columns">
			<?php the_hrb_user_gravatar( $user, 175 ); ?>
			<div class="review-meta">
				<?php the_hrb_user_rating( $user, __( 'Sin calificación', APP_TD ) ); ?>
			</div>
		</div>

		<div class="large-9 columns">
			<h2 class="freelancer-header">
				<?php the_hrb_user_display_name( $user ); ?>
				<?php if ( $user->hrb_location ): ?>
					<span class="freelancer-loc"><i class="icon i-user-location"></i><?php the_hrb_user_location( $user ); ?></span>
				<?php endif; ?>
			</h2>

			<!-- freelancer meta above desc-->
			<div class="freelancer-meta cf">
				<div class="freelancer-rate"><?php the_hrb_user_rate( $user ); ?></div>
				<div class="freelancer-success"><?php the_hrb_user_success_rate( $user ); ?></div>
				<div class="freelancer-portfolio">
					<?php if ( $user->user_url ): ?>
						<?php the_hrb_user_portfolio( $user ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<!-- freelancer desc-->
			<div class="freelancer-description"><?php the_hrb_user_bio( $user ); ?></div>

		</div><!-- end 9-columns -->

	</div><!-- end row -->

	<div class="row">
		<div class="large-12 columns">
			<div class="user-skills"><?php the_hrb_user_skills( $user, ' ', '<span class="label">', '</span>' ); ?></div>
		</div><!-- end 12-columns -->
	</div><!-- end row -->
</article>