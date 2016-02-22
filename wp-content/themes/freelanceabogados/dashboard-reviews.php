<h2><i class="icon i-reviews"></i><?php echo __( 'Reviews', APP_TD  ); ?></h2>

	<div class="row">
		<div class="large-12 columns">

			<div class="dashboard-filters">
				<div class="row">

					<div class="large-12 small-12 columns dashboard-filter-sort">
						<div class="large-6 columns">
							<?php hrb_output_results_fdropdown( hrb_get_dashboard_url_for( 'reviews' ) ); ?>
						</div>

						<div class="large-6 columns">
							<?php hrb_output_sort_fdropdown(); ?>
						</div>
					</div>

					<div class="large-12 columns dashboard-filter-sort">
						<div class="large-12 columns">
							<?php hrb_output_review_relation_fdropdown( $reviews_no_filters, $attributes = array( 'name' => 'drop-filter-relation', 'label' => __( 'Relation', APP_TD ) ) ); ?>
						</div>
					</div>

				</div>
			</div>

			<?php if ( ! empty( $reviews ) ): ?>

				<div class="large-12 columns">
					<div class="row">
						<ul class="reviews">

							<?php foreach( $reviews as $review ): ?>

								<?php appthemes_load_template( 'content-review.php', array( 'review' => $review, 'reviewer' => get_userdata( $review->get_author_ID() ) ) ); ?>

							<?php endforeach; ?>

						</ul>
					</div>
				</div>

				<!-- pagination -->
				<?php
				if ( count( $reviews_no_filters ) > 1 ) :
					hrb_output_pagination( $reviews, array( 'total' => count( $reviews_no_filters ) ), hrb_get_dashboard_url_for( 'reviews' ) );
				endif;
				?>

		<?php else: ?>

				<h5 class="no-results"><?php echo __( 'You have no reviews at this time.', APP_TD ); ?></h5>

		<?php endif; ?>

	</div>
</div>
