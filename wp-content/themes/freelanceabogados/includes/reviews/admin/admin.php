<?php
/**
 * Registers the Reviews Settings Administration Panel
 *
 * @package Components\Reviews\Admin\Settings
 */
class APP_Review_Admin {

	protected $options = '';

	public function __construct( $options ) {

		$this->options = $options;

		add_filter( 'admin_comment_types_dropdown' , array( $this, 'review_comment_type' ) );
		add_action( 'init', array( $this, 'register_settings' ), 12 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'reviews_add_menu' ), 11 );
		add_action( 'post_comment_status_meta_box-options',array( $this, 'reviews_closed_cb' ) );
	}

	/**
	 * Registers the settings page
	 * @return void
	 */
	function register_settings(){
		new APP_Reviews_Settings_Admin( $this->options );
	}

	public function review_comment_type($comment_types) {

		$comment_types = $comment_types + array(
			APP_REVIEWS_CTYPE => __('Reviews', APP_TD)
		);

		return $comment_types;
	}

	// save the checkbox value on the edit post screen under "Discussion"
	public function save_post( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! isset ( $_POST['post_type'] ) )
			return;

		if ( ! current_user_can( 'edit_' . $_POST['post_type'], $post_id ) )
			return;

		$key = APP_REVIEWS_P_STATUS_KEY;

		// checkbox ticked
		if ( isset ( $_POST[ $key ] ) )
			return update_post_meta( $post_id, $key, $_POST[$key] );

		// checkbox unticked
		delete_post_meta( $post_id, $key );
	}

	public function reviews_add_menu(){
		$post_type = appthemes_reviews_get_args( 'post_type' );
		add_submenu_page( 'edit.php?post_type='. $post_type , __( 'Reviews', APP_TD ), __( 'Reviews', APP_TD ), 'moderate_comments', 'edit-comments.php?comment_type='.APP_REVIEWS_CTYPE );
	}

	// add a checkbox on the edit post screen under "Discussion"
	public function reviews_closed_cb( $post ) {
	}

} // end class
