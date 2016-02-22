<form id="add-review-form" class="review-user review-user-<?php echo esc_attr( $review_recipient->ID ); ?>" action="<?php echo esc_url( get_the_hrb_review_user_url( get_queried_object_id(), $review_recipient ) ); ?>" method="post">

	<div class="row">
		<div class="large-12 columns">
			<label><?php _e( 'Rating', APP_TD ); ?></label>
			<div id="review-rating"></div>
		</div>
	</div>

	<br/>

	<div class="row">
		<div class="large-12 columns">
			<label><?php _e( 'Review', APP_TD ); ?></label>
			<textarea name="comment" id="review_body" class="required"></textarea>
		</div>
	</div>

	<input type="submit" class="button small right" value="<?php esc_attr_e( 'Submit Review', APP_TD ); ?>" onclick="return confirm('<?php echo __( 'Submit Review?', APP_TD ) ?>'); return false;"/>

	<?php
		wp_comment_form_unfiltered_html_nonce();

		hrb_hidden_input_fields(
			array(
				'action'				=> 'review_user',
				'comment_post_ID'		=> esc_attr( $project->ID ),
				'comment_type'			=> esc_attr( APP_REVIEWS_CTYPE ),
				'review_recipient_ID'	=> esc_attr( $review_recipient->ID ),
				'workspace_id'			=> get_queried_object_id(),
				'url_referer'			=> esc_url( $_SERVER['REQUEST_URI'] ),
			)
		);
	?>
</form>
