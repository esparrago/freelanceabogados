<?php
/**
 * Provides a media manager metabox with WordPress's native media UI.
 */
class APP_Media_Manager_Metabox extends APP_Meta_Box {

	static $id;

	public function __construct( $id, $title, $post_type, $context = 'normal', $priority = 'default' ) {

		if ( ! current_theme_supports( 'app-media-manager' ) ) {
			return;
		}

		self::$id = $id;

		parent::__construct( "$id-metabox", $title, $post_type, $context, $priority );
	}

	public function admin_enqueue_scripts() {
		global $post;

		appthemes_enqueue_media_manager( array( 'post_id' => $post->ID ) );
	}

	function display( $post ) {
		appthemes_media_manager( $post->ID, array( 'id' => self::$id ) );
	}

	protected function save( $post_id ) {
		parent::save( $post_id );

		appthemes_handle_media_upload( $post_id );
	}

}
