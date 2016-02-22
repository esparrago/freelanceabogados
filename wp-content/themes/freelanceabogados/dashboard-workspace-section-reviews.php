<div class="row">
	<ul class="reviews">

		<?php foreach( $reviews as $review ): ?>

			<?php appthemes_load_template( 'content-review.php', array( 'review' => $review, 'reviewer' => get_userdata( $review->get_author_ID() ) ) ); ?>

		<?php endforeach; ?>

	</ul>
</div>
