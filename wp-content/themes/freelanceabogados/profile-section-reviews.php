<div id="reviews">

	<?php if ( $reviews ): ?>

		<ul class="reviews">

			<?php foreach( $reviews as $review ): ?>
				  <?php appthemes_load_template( 'content-review.php', array( 'review' => $review, 'reviewer' => get_userdata( $review->get_author_ID() ) ) ); ?>
			<?php endforeach; ?>

		</ul>

	<?php else: ?>

		<h5 class="no-results"><?php echo __( 'No reviews yet.', APP_TD ); ?></h5>

	<?php endif; ?>

<div class="section-footer row">
</div><!-- end section-footer -->

</div><!-- end #reviews -->

