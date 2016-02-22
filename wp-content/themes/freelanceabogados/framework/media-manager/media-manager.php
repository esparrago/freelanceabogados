<?php
/**
 * Provides a frontend media manager using the built-in WordPress media uploader.
 *
 * @todo enforce allowed video|audio embeds
 * @todo find better way to disable default WP media views instead of using jQuery via the APP-ITEM flag to hide them
 * @package Framework\Media-Manager
 */

define( 'APP_MEDIA_MANAGER_VERSION', '1.0' );

define( 'APP_ATTACHMENT_FILE', 'file' );		// DEFAULT - meta type assigned to attachments
define( 'APP_ATTACHMENT_GALLERY', 'gallery' );  // suggested meta type for image attachments that are displayed as gallery images
define( 'APP_ATTACHMENT_EMBED', 'embed' );		// suggested meta type for embeds

add_action( 'parse_query', '_appthemes_media_query_var', 10 );

add_filter( 'admin_url', '_appthemes_media_query_arg', 10, 3 );
add_filter( 'map_meta_cap','_appthemes_media_capabilities', 15, 4 );

/**
 * Sets a query var to better identify the frontend/backend media managers and also
 * acts as helper for the media manager ajax calls.
 */
function _appthemes_media_query_var( $query ) {
	if ( ! is_admin() ) {
		$query->set( 'app_media_manager', 1 );
	}
	return $query;
}

/**
 * Sets a query arg that identifies the media manager ajax calls.
 * The query arg allows distinguishing media manager ajaxs calls and admin ajax calls
 */
function _appthemes_media_query_arg( $url, $path, $blog_id  ) {

	if ( get_query_var('app_media_manager') && 'admin-ajax.php' === basename( $url ) ) {
		$url = add_query_arg( array( 'app_media_manager' => 1 ), $url );
	}
	return $url;
}

/**
 * Retrieve the 'get_theme_support()' args.
 */
function appthemes_media_manager_get_args( $option = '' ) {

	if ( ! current_theme_supports( 'app-media-manager' ) ) {
		return array();
	}

	list( $args ) = get_theme_support( 'app-media-manager' );

	$defaults = array(
		'file_limit'  => -1,		// 0 = disable, -1 = no limit
		'embed_limit' => -1,		// 0 = disable, -1 = no limit
		'file_size'   => 1048577,	// limit file sizes to 1MB (in bytes), -1 = use WP default
		'mime_types'  => '',		// blank = any (accepts 'image', 'image/png', 'png, jpg', etc) (string|array)
		'delete_files'=> false,		// allow deleting uploaded files - false = do not allow, true = allow
	);

	$final = wp_parse_args( $args, $defaults );

	if ( empty( $option ) ) {
		return $final;
	} else if ( isset( $final[ $option ] ) ) {
		return $final[ $option ];
	} else {
		return false;
	}

}

class APP_Media_Manager {

	protected static $attach_ids_inputs = '_app_attach_ids_fields';
	protected static $embed_url_inputs = '_app_embed_urls_fields';

	protected static $default_filters;

	private function init_hooks() {
		add_action( 'appthemes_media_manager', array( __CLASS__, 'output_hidden_inputs' ), 10, 4 );
		add_action( 'ajax_query_attachments_args', array( __CLASS__, 'restrict_media_library' ), 5 );
		add_action( 'wp_ajax_app_manage_files', array( __CLASS__ , 'ajax_refresh_attachments' ) );

		add_action( 'wp_ajax_app_get_media_manager_options', array( __CLASS__, 'ajax_get_media_manager_options' ) );
		add_action( 'wp_ajax_nopriv_app_get_media_manager_options', array( __CLASS__, 'ajax_get_media_manager_options' ) );

		add_action( 'wp_ajax_app_delete_media_manager_transients', array( __CLASS__, 'ajax_delete_transients' ) );
		add_action( 'wp_ajax_nopriv_app_delete_media_manager_transients', array( __CLASS__, 'ajax_delete_transients' ) );

		add_action( 'add_attachment', array( __CLASS__, 'set_attachment_mm_id' ) );

		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'validate_upload_restrictions' ) );
	}

	function __construct() {
		$this->init_hooks();

		$params = appthemes_media_manager_get_args();

		extract( $params );

		self::$default_filters = $params;
	}

	/**
	 * Enqueues the JS scripts that output WP's media uploader.
	 */
	static function enqueue_media_manager( $localization = array() ) {

		wp_register_script(
			'app-media-manager',
			APP_FRAMEWORK_URI . '/media-manager/scripts/media-manager.js',
			array( 'jquery' ),
			APP_MEDIA_MANAGER_VERSION,
			true
		);

		wp_enqueue_style(
			'app-media-manager',
			APP_FRAMEWORK_URI . '/media-manager/style.css'
		);

		$defaults = array(
			'post_id' => 0,
			'post_id_field' => '',
			'ajaxurl' => admin_url( 'admin-ajax.php', 'relative' ),
			'ajax_nonce' => wp_create_nonce( 'app-media-manager' ),
			'files_limit_text' => __( 'Allowed files', APP_TD ),
			'files_type_text' => __( 'Allowed file types', APP_TD ),
		    'insert_media_title' => __( 'Insert Media', APP_TD ),
		    'embed_media_title' => __( 'Insert from URL', APP_TD ),
			'file_size_text' => __( 'Maximum upload file size', APP_TD ),
			'embed_limit_text' => __( 'Allowed embeds', APP_TD ),
			'clear_embeds_text' => __( 'Clear Embeds (clears any previously added embeds)', APP_TD ),
		'allowed_embeds_reached_text' => __( 'No more embeds allowed', APP_TD ),
		);
		$localization = wp_parse_args( $localization, $defaults );

		wp_localize_script( 'app-media-manager', 'app_uploader_i18n', $localization );

		wp_enqueue_script( 'app-media-manager' );

		wp_enqueue_media();
	}

	/**
	 * Outputs the media manager HTML markup.
	 *
	 * @uses do_action() Calls 'appthemes_media_manager'
	 *
	 */
	static function output_media_manager( $object_id = 0, $atts = array(), $filters = array() ) {

		// make sure we have a unique ID for each outputed file manager
		if ( empty( $atts['id'] ) ) {
			$attach_field_id = uniqid('id');
		} else {
			$attach_field_id = $atts['id'];
		}

		// parse the custom filters for the outputted media manager
		$filters = wp_parse_args( $filters, self::$default_filters );

		// allow using 'meta_type' or 'file_meta_type' as filter name
		if ( ! empty( $filters['meta_type'] ) ) {
			$filters['file_meta_type'] = $filters['meta_type'];
		}

		// media manager fieldset attributes
		$defaults = array(
			'id'             => $attach_field_id,
			'object'		 => 'post',
			'class'          => 'files',
			'title'          => '',
			'upload_text'    => __( 'Add Media', APP_TD ),
			'manage_text'    => __( 'Manage Media', APP_TD ),
			'no_media_text'  => __( 'No media added yet', APP_TD ),
			'attachment_ids' => '',
			'embed_urls'     => '',
			'attachment_params' => array(),
			'embed_params' => array(),
		);
		$atts = wp_parse_args( $atts, $defaults );

		if ( ! empty( $filters['mime_types'] ) ) {

			// extract, correct and flatten the mime types
			if ( ! is_array( $filters['mime_types'] ) ) {

				// keep the original required mime types to display to the user
				$filters['file_types'] = $filters['mime_types'];

				$mime_types = explode( ',', $filters['mime_types'] );
			} else {
				$mime_types = $filters['mime_types'];

				// keep the original required mime types to display to the user
				$filters['file_types'] = implode( ',', $filters['mime_types'] );
			}
			$mime_types = appthemes_get_mime_types_for( $mime_types );
			$filters['mime_types'] = implode( ',', $mime_types );
		}

		if ( empty( $atts['attachment_ids'] ) && $object_id ) {

			if ( 'post' == $atts['object'] ) {
				$attachment_ids = get_post_meta( $object_id, $attach_field_id, true );

				if ( ! empty( $attachment_ids ) ) {

					// check if the attachments stored in meta are still valid by querying the DB to retrieve all the valid ID's
					$args = array(
						'fields' => 'ids',
						'post__in' => $attachment_ids,
					);
					$atts['attachment_ids'] = self::get_post_attachments( $object_id, $args );

					// refresh the post meta
					if ( ! empty( $atts['attachment_ids'] ) ) {
						update_post_meta( $object_id, $attach_field_id, $atts['attachment_ids'] );
					}

				}

			} else {
				$atts['attachment_ids'] = get_user_meta( $object_id, $attach_field_id, true );
			}

		}

		// get all the embeds for the current post ID, if editing a post
		if ( empty( $atts['embed_urls'] ) && $object_id && 'post' == $atts['object'] ) {

			if ( 'post' == $atts['object'] ) {
				$embeds_attach_ids = get_post_meta( $object_id, $attach_field_id .'_embeds', true );

				if ( ! empty( $embeds_attach_ids ) ) {

					// check if the attachments stored in meta are still valid by querying the DB to retrieve all the valid ID's
					$args = array(
						'meta_value' => appthemes_get_mm_allowed_meta_types('embed'),
						'post__in' => $embeds_attach_ids,
					);

					$curr_embed_attachments = self::get_post_attachments( $object_id, $args );

					if ( ! empty( $curr_embed_attachments ) ) {
						$atts['embed_urls'] = wp_list_pluck( $curr_embed_attachments, 'guid' );
						$embeds_attach_ids = wp_list_pluck( $curr_embed_attachments, 'ID' );

						// refresh the post meta
						update_post_meta( $object_id,  $attach_field_id .'_embeds', array_keys( $embeds_attach_ids ) );
					}

				}

			} else {
				$embeds_attach_ids = get_user_meta( $object_id, $attach_field_id .'_embeds', true );
			}

		}

		$atts['button_text'] = ( ! empty( $atts['attachment_ids'] ) ? $atts['manage_text'] : $atts['upload_text']  );

		// look for a custom template before using the default one
		$located = locate_template( 'media-manager.php' );

		if ( ! $located ) {
			require APP_FRAMEWORK_DIR . '/media-manager/template/media-manager.php';
		} else {
			require $located;
		}

		$options = array(
			'attributes' => $atts,
			'filters' => $filters
		);

		update_option( "app_media_manager_{$attach_field_id}", $options );

		do_action( 'appthemes_media_manager', $attach_field_id, $atts['attachment_ids'], $atts['embed_urls'], $filters );
	}

	/**
	 * Process all posted inputs that contain attachment ID's that need to be assigned to a post or user.
	 */
	static function handle_media_upload( $object_id, $type = 'post', $fields = array(), $duplicate = false ) {

		$attach_ids_inputs = self::$attach_ids_inputs;
		$embed_url_inputs = self::$embed_url_inputs;

		if ( ! $fields ) {
			if ( isset( $_POST[ $attach_ids_inputs ] ) ) {
				$fields['attachs'] = $_POST[ $attach_ids_inputs ];
			}

			if ( isset( $_POST[ $embed_url_inputs ] ) ) {
				$fields['embeds'] = $_POST[ $embed_url_inputs ];
			}
		}

		if ( empty( $fields ) ) {
			return;
		}

		$attachs = array();

		// handle normal attachments
		foreach( (array) $fields['attachs'] as $field ) {
			$media = self::handle_media_field( $object_id, $field, $type, $duplicate );
			if ( ! empty( $media ) ) {
				$attachs = array_merge( $media, $attachs );
			}
		}

		// handle embed attachments
		foreach( (array) $fields['embeds'] as $field ) {
			$media = self::handle_embed_field( $object_id, $field, $type );
			if ( ! empty( $media ) ) {
				$attachs = array_merge( $media, $attachs );
			}
		}

		// clear previous attachments by checking if they are present on the updated attachments list
		if ( 'post' == $type && ! empty( $attachs ) ) {
			self::maybe_clear_old_attachments( $object_id, $attachs );
		}

	}

	/**
	 * Handles embeded media related posted data and retrieves an updated list of all the embed attachments for the current object.
	 *
	 * @uses do_action() Calls 'appthemes_handle_embed_field'
	 *
	 */
	private static function handle_embed_field( $object_id, $field, $type = 'post' ) {

		// user cleared the embeds
		if ( empty( $_POST[ $field ] ) ) {

			// delete the embed url's from the user/post meta
			if ( 'user' == $type  ) {
				delete_user_meta( $object_id, $field );
			} else {
				delete_post_meta( $object_id, $field );
			}

			$media = array();

		} else {

			$embeds = explode( ',', wp_strip_all_tags( $_POST[ $field ] ) );

			foreach( $embeds as $embed ) {

				$embed = trim( $embed );

				// try to get all the meta data from the embed URL to populate the attachment 'post_mime_type'
				// the 'post_mime_type' is stored in the following format: <mime type>/<provider-name>-iframe-embed ( e.g: video/youtube-iframe-embed, video/vimeo-iframe-embed, etc )
				// if the provider is not recognized by WordPress the 'post_mime_type' will default to <mime type>/iframe-embed ( e.g: video/iframe-embed )
				$oembed = self::get_oembed_object( $embed );

				$iframe_type = ( ! empty( $oembed->provider_name ) ? strtolower( $oembed->provider_name ) . '-' : '' ) . 'iframe-embed';
				$type = ( ! empty( $oembed->type ) ? $oembed->type : 'unknown' );
				$title = ( ! empty( $oembed->title ) ? $oembed->title : __( 'Unknown', APP_TD ) );

				$attachment = array(
					'post_title' => $title,
					'post_content' => $embed,
					'post_parent' => $object_id, // treating WP bug https://core.trac.wordpress.org/ticket/29646
					'guid' => $embed,
					'post_mime_type' => sprintf( '%1s/%2s', $type, $iframe_type ),
				);

				// assign the embed URL to the object as a normal file attachment
				$attach_id = wp_insert_attachment( $attachment, '', $object_id );

				if ( is_wp_error( $attach_id ) ) {
					continue;
				}

				$media[] = (int) $attach_id;

				if ( isset( $_POST[ $field . '_meta_type' ] ) && in_array( $_POST[ $field .'_meta_type' ], appthemes_get_mm_allowed_meta_types('embed') ) ) {
					$meta_type = $_POST[ $field .'_meta_type' ];
				} else {
					$meta_type = APP_ATTACHMENT_EMBED;
				}

				update_post_meta( $attach_id, '_app_attachment_type', $meta_type );
			}

			// store the embed url's on the user/post meta
			if ( 'user' == $type ) {
				update_user_meta( $object_id, $field, $media );
			} else {
				update_post_meta( $object_id, $field, $media );
			}

		}

		do_action( 'appthemes_handle_embed_field', $object_id, $field, $type );

		return $media;
	}

	/**
	 * Handles media related posted data and retrieves an updated list of all the attachments for the current object.
	 *
	 * @uses do_action() Calls 'appthemes_handle_media_field'
	 *
	 */
	private static function handle_media_field( $object_id, $field, $type = 'post', $leave_original = false ) {

		// user cleared the attachments
		if ( empty( $_POST[ $field ] ) ) {

			// delete the attachments from the user/post meta
			if ( 'user' == $type ) {
				delete_user_meta( $object_id, $field );
			} else {
				delete_post_meta( $object_id, $field );
			}

			$media = array();

		} else {

			$attachments = explode( ',', wp_strip_all_tags( $_POST[ $field ] ) );

			foreach( $attachments as $attachment_id ) {

				$attachment = get_post( $attachment_id );

				if ( $attachment->post_parent != $object_id && 'post' == $type ) {

					// keeps the original attachment untouched and instead creates a new one to be attached to the post
					if ( $leave_original ) {
						$attachment->ID = 0;
					}

					// treating WP bug https://core.trac.wordpress.org/ticket/29646
					$attachment->post_parent = $object_id;
					// update the attachment
					$attach_id = wp_insert_attachment( $attachment, '', $object_id );
					if ( is_wp_error( $attach_id ) ) {
						continue;
					}

				} else {
					$attach_id = $attachment_id;
				}

				if ( isset( $_POST[ $field .'_meta_type' ] ) && in_array( $_POST[ $field .'_meta_type' ], appthemes_get_mm_allowed_meta_types('file') ) ) {
					$meta_type = $_POST[ $field .'_meta_type' ];
				} else {
					$meta_type = APP_ATTACHMENT_FILE;
				}

				$media[] = (int) $attach_id;

				update_post_meta( $attach_id, '_app_attachment_type', $meta_type );
			}

			// store the attachments on the user/post meta
			if ( 'user' == $type ) {
				update_user_meta( $object_id, $field, $media );
			} else {
				update_post_meta( $object_id, $field, $media );
			}

		}

		do_action( 'appthemes_handle_media_field', $object_id, $field, $type );

		return $media;
	}

	/**
	 * Outputs the hidden inputs that act as helpers for the media manager JS.
	 */
	static function output_hidden_inputs( $attach_field_id, $attachment_ids, $embed_urls, $filters ) {

		$embeds_input = $attach_field_id . '_embeds';

		// input for the media manager unique nonce
		wp_nonce_field( "app_mm_nonce_{$attach_field_id}", "app_mm_nonce_{$attach_field_id}" );

		// input for the attachment ID's selected by the user in the media manager
		echo html( 'input', array( 'name' => $attach_field_id, 'type' => 'hidden', 'value' => implode( ',', (array) $attachment_ids ) ) );

		// input with all the field names that contain attachment ID's
		echo html( 'input', array( 'name' => self::$attach_ids_inputs.'[]','type' => 'hidden', 'value' => $attach_field_id ) );

		// input for the embed URL's selected by the user in the media manager
		echo html( 'input', array( 'name' => $embeds_input, 'type' => 'hidden', 'value' => implode( ',', (array) $embed_urls ) ) );

		// input with all the field names that contain embed URL's
		echo html( 'input', array( 'name' => self::$embed_url_inputs.'[]','type' => 'hidden', 'value' => $embeds_input ) );

		// input for normal attachments meta type
		if ( ! empty( $filters['file_meta_type'] ) ) {
			echo html( 'input', array( 'class' => $attach_field_id,	'type' => 'hidden',	'name' => $attach_field_id . '_meta_type', 'value' => $filters['file_meta_type'] ) );
		}

		// input for embed attachments meta type
		if ( ! empty( $filters['embed_meta_type'] ) ) {
			echo html( 'input', array( 'class' => $attach_field_id,	'type' => 'hidden',	'name' => $embeds_input . '_meta_type', 'value' => $filters['embed_meta_type'] ) );
		}

	}

	/**
	 * Refreshes the attachments/embed list based on the user selection.
	 */
	static function ajax_refresh_attachments() {

		if ( ! check_ajax_referer( 'app_mm_nonce_' . $_POST['mm_id'], 'mm_nonce' ) ) {
			die();
		}

		extract( $_POST );

		$attachment_ids = $embed_attach_ids = array();

		// retrieve the options for the current media manager
		$media_manager_options = appthemes_get_media_manager_options( $mm_id );

		if ( isset( $_POST['attachments'] ) ) {
			$attachment_ids = array_merge( $attachment_ids, $_POST['attachments'] );
			$attachment_ids = array_map( 'intval', $attachment_ids );
			$attachment_ids = array_unique( $attachment_ids );
		}

		if ( ! empty( $_POST['embed_urls'] ) ) {
			$posted_embed_urls = sanitize_text_field( $_POST['embed_urls'] );
			$embed_urls = explode( ',', $posted_embed_urls );
		}

		if ( ! empty( $attachment_ids ) ) {
			$attachments = appthemes_output_attachments( $attachment_ids, $media_manager_options['attributes']['attachment_params'], $echo = false );
			echo json_encode( array( 'output' => $attachments ) );
		}

		if ( ! empty( $embed_urls ) ) {
			$embeds = appthemes_output_embed_urls( $embed_urls, $media_manager_options['attributes']['embed_params'], $echo = false );
			echo json_encode( array( 'url' => $posted_embed_urls, 'output' => $embeds ) );
		}

		die();
	}

	/**
	 * Restrict media library to files uploaded by the current user with
	 * no parent or whose parent is the current post ID.
	 */
	static function restrict_media_library( $query ) {
		global $current_user, $wp_query;

		// make sure we're restricting the library only on the frontend media manager
		if ( empty( $_REQUEST['app_media_manager'] ) ) {
			return $query;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
		   $query['author'] = $current_user->ID;

		   if ( empty( $_REQUEST['post_id'] ) ) {
			   $query['post_parent'] = 0;
		   } else {
			   $query['post_parent'] = $_REQUEST['post_id'];
		   }

		}
		return $query;
	}

	/**
	 * Validates the files the current user is trying to upload by checking their mime types
	 * and the preset file limit.
	 */
	static function validate_upload_restrictions( $file ) {

		if ( empty( $_POST['app_mime_types'] ) && empty( $_POST['app_file_size'] ) && empty( $_POST['app_file_limit'] ) ) {
			return $file;
		}

		$mm_id = sanitize_text_field( $_POST['app_mm_id'] );

		$options = appthemes_get_media_manager_options( $mm_id );

		// secure mime types
		if ( ! empty( $_POST['app_mime_types'] ) ) {

			// check if the mime types limit where hacked
			if ( empty( $options['filters'] ) || $_POST['app_mime_types'] != $options['filters']['mime_types'] ) {
				$file['error'] = __( 'Sorry, allowed mime types do not seem to be valid.', APP_TD );
				return $file;
			}

			// can be 'mime_type/extension', 'extension' or 'mime_type'
			$allowed = explode( ',', $_POST['app_mime_types'] );

			$file_type = wp_check_filetype( $file['name'] );
			$mime_type = explode( '/', $file_type['type'] );

			$not_allowed = true;

			// check if extension and mime type are allowed
			if ( in_array( $mime_type[0], $allowed ) || in_array( $file_type['type'], $allowed ) || in_array( $file_type['ext'], $allowed ) ) {
				$not_allowed = false;
			}

			if ( $not_allowed ) {

				$allowed_mime_types = get_allowed_mime_types();

				// first pass to check if the mime type is allowed
				if ( ! in_array( $file['type'], $allowed_mime_types ) ) {

					// double check if the extension is invalid by looking at the allowed extensions keys
					foreach ( $allowed_mime_types as $ext_preg => $mime_match ) {
						$ext_preg = '!^(' . $ext_preg . ')$!i';
						if ( preg_match( $ext_preg, $file_type['ext'] ) ) {
							$not_allowed = false;
							break;
						}
					}

				}

				if ( $not_allowed ) {
					$file['error'] = __( 'Sorry, you cannot upload this file type for this field.', APP_TD );
					return $file;
				}

			}

		}

		// secure file size
		if ( ! empty( $_POST['app_file_size'] ) ) {

			// check if the file size limit was hacked
			if ( empty( $options['filters'] ) || $_POST['app_file_size'] != $options['filters']['file_size'] ) {
				$file['error'] = __( 'Sorry, the allowed file size does not seem to be valid.', APP_TD );
				return $file;
			}

			$file_size = sanitize_text_field( $_POST['app_file_size'] );

			if ( $file['size'] > $file_size ) {
				$file['error'] = __( 'Sorry, you cannot upload this file as it exceeds the size limitations for this field.', APP_TD );
				return $file;
			}

		}

		// secure file limit
		if ( ! empty( $_POST['app_file_limit'] ) ) {

			$args = array(
				'post_type' => 'attachment',
				'author' => get_current_user_id(),
				'post_parent' => ! empty( $_POST['post_id'] ) ? $_POST['post_id'] : 0,
				'nopaging' => true,
				'post_status' => 'any',
				// limit files considering the media manager parent ID since each available media manager on a form can have it's own file limits
				'meta_key' => '_app_media_manager_parent',
				'meta_value' => $mm_id,
			);

			$attachments = new WP_Query( $args );

			if ( $attachments->found_posts && $attachments->found_posts > $_POST['app_file_limit'] ) {
				$file['error'] = __( 'Sorry, you\'ve reached the file upload limit for this field.', APP_TD );
				return $file;
			}

		}

		return $file;
	}

	/**
	 * Get the attachments for a given object.
	 */
	static function get_post_attachments( $object_id, $args = array() ) {

		// get the current attached embeds
		$defaults = array(
			'post_parent' => $object_id,
			'meta_key' => '_app_attachment_type',
			'meta_value' =>  appthemes_get_mm_allowed_meta_types('file'),
		);
		$args = wp_parse_args( $args, $defaults );

		$curr_attachments = get_children( $args );

		return $curr_attachments;
	}

	/**
	 * Unassign or delete any previous attachments that are not present on the current attachment enqueue list.
	 */
	static function maybe_clear_old_attachments( $object_id, $attachments, $delete = false ) {

		$args = array(
			'meta_value' => appthemes_get_mm_allowed_meta_types(),
			'post__not_in' => $attachments,
		);
		$old_attachments = self::get_post_attachments( $object_id, $args );

		// unattach or delete
		foreach( $old_attachments as $old_attachment ) {

			$type = get_post_meta( $old_attachment->ID, '_app_attachment_type', true );

			// delete embeds by default since they cannot be re-attached again
			if ( in_array( $type, appthemes_get_mm_allowed_meta_types('embed') ) || $delete ) {
				wp_delete_attachment( $old_attachment->ID );
			} else {
				// unattach normal attachments to allow re-attaching them later
				$old_attachment->post_parent = 0;
				wp_insert_attachment( $old_attachment );
			}
		}

	}

   /**
    * Attempts to fetch an oembed object with metadata for a provided URL using oEmbed.
    */
   static function get_oembed_object( $url ) {
		require_once( ABSPATH . WPINC . '/class-oembed.php' );
		$oembed = _wp_oembed_get_object();

		$oembed_provider_url = $oembed->discover( $url );
		$oembed_object = $oembed->fetch( $oembed_provider_url, $url );

		return empty( $oembed_object ) ? false : $oembed_object;
   }

	/**
	 * Ajax callback to retrieves the db options for a specific media manager ID.
	 */
	static function ajax_get_media_manager_options() {

	   if ( empty( $_POST['mm_id'] ) ) {
		   die();
	   }

	   if ( ! check_ajax_referer( 'app_mm_nonce_' . $_POST['mm_id'], 'mm_nonce' ) ) {
		   die();
	   }

	   $mm_id = $_POST['mm_id'];

	   $options = appthemes_get_media_manager_options( $mm_id );

	   // set a transient for the opened media manager ID to help identify the current media manager when there's multiple mm's on same form
	   set_transient( 'app_media_manager_id', $mm_id, 60 * 60 * 5 ); // keep transient for 5 minutes

	   echo json_encode( $options );

	   die();
	}

	/**
	 * Delete any stored transients when media manager UI is closed.
	 */
	static function ajax_delete_transients() {
		delete_transient('app_media_manager_id');
		die();
	}

   /**
	* Assign a meta key containing the media manager parent ID AND a default attach type to each new media attachment added through the media manager.
	*/
   static function set_attachment_mm_id( $attach_id ) {

	   // get the active media manager ID
	   $mm_id = appthemes_get_active_media_manager();

	   if ( $mm_id ) {
		   update_post_meta( $attach_id, '_app_media_manager_parent', $mm_id );
		   update_post_meta( $attach_id, '_app_attachment_type', APP_ATTACHMENT_FILE );
	   }

   }

}

/**
 * Outputs the media manager HTML markup.
 *
 * @param int $object_id (optional) The post ID/user ID that the media relates to
 * @param array $atts (optional) Input attributes to be passed to the media manager:
 * 			'id'			   => the input ID - name used as meta key to store the media data
 *			'object'		   => the object to assign the attachments: 'post'(default)|'user'
 *			'class'			   => the input CSS class
 *			'title'			   => the input title
 *			'upload_text'	   => the text to be displayed on the upload button when there are no uploads yet
 *			'manage_text'	   => the text to be displayed on the upload button when uploads already exist
 *			'no_media_text'	   => the placeholder text to be displayed while there are no uploads
 *			'attachment_ids'   => default attachment ID's to be listed (int|array),
 *			'embed_urls'	   => default embed URL's to be listed (string|array),
 *			'attachment_params => the parameters to pass to the function that outputs the attachments (array)
 * 			'embed_params      => the parameters to pass to the function that outputs the embeds (array)
 * @param array $filters (optional) Filters to be passed to the media manager:
 *			'file_limit'	 => file limit - 0 = disable, -1 = no limit (default)
 *			'file_size'		 => file size (in bytes) - default = 1048577 (~1MB)
  *			'file_meta_type' => APP_ATTACHMENT_FILE (default), APP_ATTACHMENT_GALLERY - hook into 'appthemes_mm_allowed_file_meta_types()' to add others
 *			'embed_limit'	 => embed limit - 0 = disable, -1 = no limit (default)
 *			'embed_meta_type'=> APP_ATTACHMENT_EMBED (default) - hook into 'appthemes_mm_allowed_embed_meta_types()' to add others
 *			'mime_types'	 => the mime types accepted (default is empty - accepts any mime type) (string|array)
 *			'delete_files'   => allow deleting uploaded files - false = do not allow (default), true = allow
 */
function appthemes_media_manager( $object_id = 0, $atts = array(), $filters = array() ) {
	APP_Media_Manager::output_media_manager( $object_id, $atts, $filters );
}

/**
 * Enqueues the JS scripts that output WP's media manager.
 *
 * @param array $localization (optional) The localization params to be passed to wp_localize_script()
 * 		'post_id'			=> the existing post ID, if editing a post, or 0 for new posts (required for edits if 'post_id_field' is empty)
 *		'post_id_field'		=> an input field name containing the current post ID (required for edits if 'post_id' is empty)
 *		'ajaxurl'			=> admin_url( 'admin-ajax.php', 'relative' ),
 *		'ajax_nonce'		=> wp_create_nonce('app-media-manager'),
 *		'files_limit_text'	=> the files limit text to be displayed on the upload view
 *		'files_type_text'	=> the allowed file types to be displayed on the upload view
 *		'insert_media_title'=> the insert media title to be displayed on the upload view
 *		'embed_media_title'	=> the embed media title to be displayed on the embed view
 *		'embed_limit_text'	=> the embed limit to be displayed on the embed view
 *		'clear_embeds_text' => the text for clearing the embeds to be displayed on the embed view
 *		'allowed_embeds_reached_text' => the allowed embeds warning to be displayed when users reach the max embeds allowed
 */
function appthemes_enqueue_media_manager( $localization = array() ) {
	APP_Media_Manager::enqueue_media_manager( $localization );
}

/**
 * Handles media related post data
 *
 * @param int $post_id The post ID to which the attachments will be assigned
 * @param array $fields (optional) The media fields that should be handled -
 * Expects the fields index type: 'attachs' or 'embeds' (e.g: $fields = array( 'attach' => array( 'field1', 'field2' ), 'embeds' => array( 'field1', 'field2' ) )
 * @param bool $duplicate (optional) Should the media files be duplicated, thus keeping the original file unattached
 * @return null|bool False if no media was processed, null otherwise
 */
function appthemes_handle_media_upload( $post_id, $fields = array(), $duplicate = false ) {
	APP_Media_Manager::handle_media_upload( $post_id, 'post', $fields, $duplicate );
}

/**
 * Handles media related user data
 *
 * @param int $user_id The user ID to which the attachments will be assigned
 * @param array $fields (optional) The media fields that should be handled
 * @return null|bool False if no media was processed, null otherwise
 */
function appthemes_handle_user_media_upload( $user_id, $fields = array() ) {
	APP_Media_Manager::handle_media_upload( $user_id, 'user', $fields );
}

/**
 * Outputs the HTML markup for a list of attachment ID's.
 *
 * @param array $attachment_ids The list of attachment ID's to output
 * @param array $params The params to be used to output the attachments
 *		'show_description' => displays the attachment description (default is TRUE),
 *		'show_image_thumbs' => displays the attachment thumb (default is TRUE - images only, displays an icon on other mime types),
 * @param bool $echo Should the attachments be echoed or returned (default is TRUE)
 */
function appthemes_output_attachments( $attachment_ids, $params = array(), $echo = true ) {

	$defaults = array(
		'show_description' => true,
		'show_image_thumbs' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	extract( $params );

	if ( empty( $attachment_ids ) ) {
		return;
	}

	$attachments = '';

	if ( ! $echo ) {
		ob_start();
	}

	foreach( (array) $attachment_ids as $attachment_id ) {
		appthemes_output_attachment( $attachment_id, $show_description, $show_image_thumbs );
	}

	if ( ! $echo ) {
		$attachments .= ob_get_clean();
	}

	if ( ! empty( $attachments ) ) {
		return $attachments;
	}

}

/**
 * Outputs the HTML markup for a specific attachment ID.
 *
 * @param int $attachment_id The attachment ID
 * @param bool $show_description (optional) Should the attachment description be displayed?
 * @param bool $show_image_thumbs (optional) Should images be prepended with thumbs? (defaults to mime type icons)
 * @return string The HTML markup
 */
function appthemes_output_attachment( $attachment_id, $show_description = true, $show_image_thumbs = true ) {

	$file = appthemes_get_attachment_meta( $attachment_id, $show_description );

	$title = $show_description ? $file['title'] : '';

	$link = html( 'a', array(
		'href' => $file['url'],
		'title' => $file['title'],
		'alt' => $file['alt'],
		'target' => '_blank',
	), $title );

	$mime_type = explode( '/', $file['mime_type'] );

	if ( $show_description ) {
		$attachment = get_post( $attachment_id );
		$file = array_merge( $file, array(
			'caption' => $attachment->post_excerpt,
			'description' => $attachment->post_content,
		) );
		$link .= html( 'p', array( 'class' =>  'file-description' ), $file['description'] );
	}

	if ( 'image' == $mime_type[0] && $show_image_thumbs ) {
		$thumb = wp_get_attachment_image( $attachment_id, 'thumb' );

		echo html( 'div', $thumb . ' ' . $link );
		return;
	}

	echo html( 'div', array(
		'class' => 'file-extension ' . appthemes_get_mime_type_icon_class( $file['mime_type'] ),
	), $link );
}

/**
 * Outputs embed attachments or the HTML markup for a single embed or list of embed attachments.
 *
 * @param int|array $attachment_ids A single embed attachment ID or list of embeds attachment ID's
 * @param array $params (optional)
 *              'embed' => true (default)|false - should the URL be automatically embed or simply outputed?
 */
function appthemes_output_embeds( $attachment_ids, $params = array(), $echo = true ) {

	$defaults = array(
		'embed' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	extract( $params );

	$embeds = '';

	if ( ! $echo ) {
		ob_start();
	}

	foreach( (array) $attachment_ids as $attach_id ) {

		$attachment = get_post( $attach_id );

		if ( empty( $attachment) ) {
			continue;
		}

		$url = trim( $attachment->guid );

		echo html( 'br', '&nbsp;' );

		echo appthemes_get_oembed_url( $url );

		if ( ! $echo ) {
			$embeds .= ob_get_clean();
		}

	}

	if ( ! empty( $embeds ) ) {
		return $embeds;
	}
}

/**
 * Outputs an embeded URL or the HTML markup for a single URL or list of URL's.
 *
 * @param string|array $urls A single URL or list of URL's
 * @param array $params (optional)
 *		'embed' => true (default)|false - should the URL be automatically embed or simply outputed?
 */
function appthemes_output_embed_urls( $urls, $params = array(), $echo = true ) {

	$defaults = array(
		'embed' => true,
	);
	$params = wp_parse_args( $params, $defaults );

	extract( $params );

	if ( empty( $urls ) ) {
		return;
	}

	$embeds = '';

	if ( ! $echo ) {
		ob_start();
	}

	foreach( (array) $urls as $url ) {
		$url = trim( $url );

		echo html( 'br', '&nbsp;' );

		echo appthemes_get_oembed_url( $url );

		if ( ! $echo ) {
			$embeds .= ob_get_clean();
		}
	}

	if ( ! empty( $embeds ) ) {
		return $embeds;
	}
}

/**
 * Attempts to fetch an embed HTML for a provided URL using oEmbed.
 *
 * @param type $url The embed URL
 * @param type $embed (optional) Should the URL be returned as a static URL or attempts to fetch the embed HTML for a provided URL using oEmbed
 * @return string The oEmbed URL on success or the URL passed in the first parameter on failure
 */
function appthemes_get_oembed_url( $url, $embed = true ) {

	if ( $embed ) {

		$oembed = wp_oembed_get( $url );
		if ( $oembed ) {
			$output = $oembed;
		} else {
			$output = $url;
		}

	} else {
		$output = $url;
	}

	return $output;
}

/**
 * Attempts to fetch an oembed object with metadata for a provided URL using oEmbed.
 *
 * @param string $url The URL to fetch the data from.
 * @return bool|string False on failure or the oembed object on success.
 */
function appthemes_get_ombed_object( $url ) {
	return APP_Media_Manager::get_oembed_object( $url );
}

/**
 * Queries the database for media manager attachments.
 * Uses the meta key '_app_attachment_type' to filter the available attachment types: gallery | file | embed
 *
 * @param int $post_id	The listing ID
 * @param array $filters (optional) Params to be used to filter the attachments query
 */
function appthemes_get_post_attachments( $post_id, $filters = array() ) {

	if ( ! $post_id ) {
		return array();
	}

	$defaults = array(
		'file_limit' => -1,
		'meta_type'	 => APP_ATTACHMENT_FILE,
		'mime_types' => '',
	);
	$filters = wp_parse_args( $filters, $defaults );

	extract( $filters );

	return get_posts( array(
		'post_type' 		=> 'attachment',
		'post_status' 		=> 'inherit',
		'post_parent' 		=> $post_id,
		'posts_per_page' 	=> $file_limit,
		'post_mime_type'	=> $mime_types,
		'orderby' 			=> 'menu_order',
		'order' 			=> 'asc',
		'meta_key'			=> '_app_attachment_type',
		'meta_value'		=> $meta_type,
		'fields'			=> 'ids',
	) );
}

/**
 * Collects and returns the meta info for a specific attachment ID.
 *
 * Meta retrieved: title, alt, url, mime type, file size
 *
 * @param int $attachment_id  The attachment ID
 * @return array Retrieves the attachment meta
 */
function appthemes_get_attachment_meta( $attachment_id ) {
	$filename = wp_get_attachment_url( $attachment_id );

	$title = trim( strip_tags( get_the_title( $attachment_id ) ) );
	$size = size_format( filesize( get_attached_file( $attachment_id ) ), 2 );
	$basename = basename( $filename );

	$meta = array (
		'title'     => ( ! $title ? $basename : $title ),
		'alt'       => get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'url'       => $filename,
		'mime_type' => get_post_mime_type( $attachment_id ),
		'size'      => $size,
	);
	return $meta;
}

/**
 * Retrieves the CSS class that should be used for a specific mime type icon.
 *
 * @uses apply_filters() Calls 'appthemes_mime_type_icon'
 *
 * @param string $mime_type
 * @return string The mime type icon CSS class
 */
function appthemes_get_mime_type_icon_class( $mime_type ) {

	if ( ! $mime_type ) {
		$mime_type = 'generic';
	}

	$file_ext_ico = array(
		'pdf'          => 'file-pdf',
		'msword'       => 'file-word',
		'vnd.ms-excel' => 'file-excel',
		'csv'          => 'file-excel',
		'image'        => 'file-image',
		'video'        => 'file-video',
		'audio'        => 'file-audio',
		'other'        => 'file-other',
	);

	$mime_type = explode( '/' , $mime_type );

	if ( is_array( $mime_type ) ) {
		// simplify the mime match for image types by using the 'image' part (i.e: image/png, image/jpg, etc)
		if ( in_array( $mime_type[0], array( 'video', 'audio', 'image' ) ) ) {
			$mime_type = $mime_type[0];
		} else {
			$mime_type = $mime_type[1];
		}

	}

	if ( ! isset( $file_ext_ico[ $mime_type ] ) ) {
		$mime_type = 'other';
	}
	return apply_filters( 'appthemes_mime_type_icon', $file_ext_ico[ $mime_type ], $mime_type );
}

/**
 * Compares full/partial mime types or file extensions and tries to retrieve a list of related mime types.
 *
 * examples:
 * 'image'	=> 'image/png', 'image/gif', etc
 * 'pdf'	=> 'application/pdf'
 *
 * @param mixed $mime_types_ext The full/partial mime type or file extension to search
 * @return array The list of mime types if found, or an empty array
 */
function appthemes_get_mime_types_for( $mime_types_ext ) {

	$normalized_mime_types = array();

	$all_mime_types = wp_get_mime_types();

	// sanitize the file extensions/mime types
	$mime_types_ext = array_map( 'trim', (array) $mime_types_ext );
	$mime_types_ext = preg_replace( "/[^a-z\/]/i", '', $mime_types_ext );

	foreach( $mime_types_ext as $mime_type_ext ) {

		if ( isset( $all_mime_types[ $mime_type_ext ] ) ) {
			$normalized_mime_types[] = $all_mime_types[ $mime_type_ext ];
		} elseif( in_array( $mime_type_ext, $all_mime_types ) ) {
			$normalized_mime_types[] = $mime_type_ext;
		} else {

			// try to get the full mime type from extension (e.g.: png, .jpg, etc ) or mime type parts (e.g.: image, application)
			foreach ( $all_mime_types as $exts => $mime ) {
				$mime_parts = explode( '/', $mime );

				if ( preg_match( "!({$exts})$|({$mime_parts[0]})!i", $mime_type_ext ) ) {
					$normalized_mime_types[] = $mime;
				}
			}
		}
	}
	return $normalized_mime_types;
}

/**
 * Retrieves all the attributes and filters set for a specific media manager ID.
 *
 * @param string $mm_id The media manager ID to retrieve the options from.
 * @return array An associative array with all the options for the media manager.
 */
function appthemes_get_media_manager_options( $mm_id ) {
	return get_option( "app_media_manager_{$mm_id}" );
}

/**
 * Retrieves the currently active (opened) media manager ID.
 *
 * @return string The media manager ID.
 */
function appthemes_get_active_media_manager() {
	return get_transient('app_media_manager_id');
}

/**
 * Retrieves allowed attachments meta types.
 *
 * @uses apply_filters() Calls 'appthemes_mm_allowed_meta_types'
 * @uses apply_filters() Calls 'appthemes_mm_allowed_file_meta_types'
 * @uses apply_filters() Calls 'appthemes_mm_allowed_embed_meta_types'
 *
 * @param string $type The attachment type: 'file' or 'embed', or all types, if empty
 */
function appthemes_get_mm_allowed_meta_types( $type = '' ) {

	$meta_types = array(
		'file' => array( APP_ATTACHMENT_FILE, APP_ATTACHMENT_GALLERY ),
		'embed' => array( APP_ATTACHMENT_EMBED ),
	);

	if ( empty( $type ) ) {
		$meta_types = array_merge( $meta_types['file'], $meta_types['embed'] );
	} elseif ( empty( $meta_types[ $type ] ) ) {
		$meta_types = $meta_types['file'];
	} else {
		$meta_types = $meta_types[ $type ];
		$type = '_' . $type;
	}

	return apply_filters( "appthemes_mm_allowed{$type}_meta_types", $meta_types );
}

### Hooks Callbacks

/**
 * Meta cababilities for uploading files.
 *
 * Users need the 'upload_media' cap to be able to upload files
 * Users need the 'delete_post' cap to be able to delete files
 */
function _appthemes_media_capabilities( $caps, $cap, $user_id, $args ) {

	switch( $cap ) {
		case 'upload_files':
			if ( user_can( $user_id, 'upload_media' ) && ! empty( $_REQUEST['app_media_manager'] ) ) {
				$caps = array( 'exist' );
			}
			break;
		case 'delete_post';
			$post = get_post( $args[0] );
			// allow users to delete their uploaded files
			if ( $user_id == $post->post_author && 'attachment' == $post->post_type && ! empty( $_REQUEST['app_media_manager'] ) ) {

				$mm_id = appthemes_get_active_media_manager();
				if ( $mm_id ) {
					$mm_options = appthemes_get_media_manager_options( $mm_id );
					// check if the active media manage allows deleting uploaded files (only own uploaded files can be deleted)
					if ( ! empty( $mm_options['filters']['delete_files'] ) ) {
						$caps = array( 'exist' );
					}
				}
			}
			break;
	}
	return $caps;
}

