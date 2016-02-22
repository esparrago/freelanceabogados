jQuery(document).ready(function($) {

	$('#review-rating').raty({
		hintList: app_reviews_i18n.hint_list,
		path: app_reviews_i18n.image_path,
		scoreName: 'review_rating',
		starHalf: 'star-half-big.png',
		starOff: 'star-off-big.png',
		starOn: 'star-on-big.png',
		half: false,	// TODO: allow changing this values by param
		score: 0,		// TODO: allow changing this values by param
		click: function( score, evt ) {
			jQuery('#'+app_reviews_i18n.review_form).find('.rating-error').remove();
		}
	});

});