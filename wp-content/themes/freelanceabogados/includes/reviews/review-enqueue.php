<?php
/**
 * Reviews scripts
 *
 * @package Components\Reviews
 */

/**
 * Note:
 * These functions register and enqueue the default scripts and styles for the Raty plugin
 * Each theme should manually hook into these functions as needed to avoid enqueing on every page
 */

/**
 * Registers and enqueues the default Raty JS scripts
 */
function appthemes_reviews_enqueue_scripts( $localization = '' ) {

	$url = appthemes_reviews_get_args( 'url'  );

	wp_register_script(
		'app-reviews-raty',
		$url . '/scripts/jquery.raty.min.js',
		array( 'jquery' ),
		APP_REVIEWS_VERSION,
		true
	);

	wp_register_script(
		'app-reviews-rating',
		$url . '/scripts/rating.js',
		array( 'app-reviews-raty' ),
		APP_REVIEWS_VERSION,
		true
	);

	$hint_list = array(
		__( 'bad', APP_TD ),
		__( 'poor', APP_TD ),
		__( 'regular', APP_TD ),
		__( 'good', APP_TD ),
		__( 'excellent', APP_TD )
	);

	$review_form = 'add-review-form';

	$defaults = array(
		'hint_list' => $hint_list,
		'image_path' => $url . '/images/',
		'review_form' => $review_form,
	);
	$localization = wp_parse_args( $localization, $defaults );

	wp_localize_script( 'app-reviews-rating', 'app_reviews_i18n', $localization );

	wp_enqueue_script( 'app-reviews-rating' );
}

/**
 * Registers and enqueue the default Raty CSS styles
 */
function appthemes_reviews_enqueue_styles() {

	wp_register_style(
		'reviews-raty',
		appthemes_reviews_get_args( 'url'  ) . '/style.css',
		null,
		APP_REVIEWS_VERSION
	);

	wp_enqueue_style( 'reviews-raty' );
}
