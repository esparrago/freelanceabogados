<?php
/**
 * Media related functions.
 */

add_action( 'appthemes_handle_media_field', 'hrb_maybe_set_thumbnail', 10, 3 );


### Hooks Callbacks

/**
 * When user is adding media attachments set the first media file as the thumbnail.
 *
 * @since 1.3
 */
function hrb_maybe_set_thumbnail( $object_id, $field, $type ) {

	$attachments = get_attached_media( 'image', $object_id );
	if ( empty( $attachments ) ) {
		return;
	}

	$media = (array) get_post_meta( $object_id, $field, true );

	$attachments = wp_list_pluck( $attachments, 'ID' );
	$media = array_intersect( $media, $attachments );

	// when attaching images set the first image as featured
	if ( ! empty( $media ) && 'post' == $type ) {
		$media = reset( $media );
		set_post_thumbnail( $object_id, $media );
	} else {
		delete_post_thumbnail( $object_id );
	}

}


### Helper Functions

/**
 * Outputs the media manager.
 */
function hrb_media_manager( $post_id, $atts, $params = array() ) {
	global $hrb_options;

	if ( ! current_theme_supports('app-media-manager') ) {
		return;
	}

	if ( ! $hrb_options->attachments ) {
		return;
	}

	$defaults = array(
		'mime_types' => $hrb_options->attachments_types,
		'file_limit' => $hrb_options->attachments_limit,
		'file_size' => ( $hrb_options->attachments_size * 1024 ),
		'embed_limit' => 0,
	);
	$params = wp_parse_args( $params, $defaults );

	appthemes_media_manager( $post_id, $atts, $params );
}

/**
 * Outputs the media manager for uploading a gravatar.
 */
function hrb_gravatar_media_manager( $user_id, $atts, $params = array() ) {

	if ( ! current_theme_supports('app-media-manager') ) {
		return;
	}

	$defaults = array(
		'mime_types' => 'image',
		'file_limit' => 1,
		'file_size' => 1024 * 1024,
		'embed_limit' => 0,
		'delete_files' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	$atts_defaults = array(
		'upload_text' => __( 'Avatar...', APP_TD ),
		'manage_text'    => __( 'Change...', APP_TD ),
		'no_media_text'  => '',
		'attachment_params' => array(
			'show_description' => false,
		)
	);
	$atts = wp_parse_args( $atts, $atts_defaults );

	$atts['object'] = 'user';

	appthemes_media_manager( $user_id, $atts, $params );
}

