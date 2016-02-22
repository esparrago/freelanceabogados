<?php

class APP_Bid_Admin {

	protected $options = '';

	function __construct( $options ) {

		$this->options = $options;

		add_filter( 'admin_comment_types_dropdown', array( $this, 'bid_comment_type' ) );
		add_action( 'init', array( $this, 'register_settings' ), 12 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'bids_add_menu' ), 11 );
	}

	/**
	 * Registers the settings page
	 * @return void
	 */
	function register_settings(){
		new APP_Bidding_Settings_Admin( $this->options );
	}

	function bid_comment_type($comment_types) {

		$comment_types = $comment_types + array(
			appthemes_get_bidding_ctype() => appthemes_bidding_get_args('name')
		);

		return $comment_types;
	}

	// save the checkbox value on the edit post screen under "Discussion"
	function save_post( $post_id, $post ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return;

		if ( ! isset ( $_POST['post_type'] ) )
			return;

		if ( ! current_user_can( 'edit_' . $_POST['post_type'], $post_id ) )
			return;

		$key = APP_BIDS_P_STATUS_KEY;

		// checkbox ticked
		if ( isset ( $_POST[ $key ] ) )
			return update_post_meta( $post_id, $key, $_POST[$key] );

		// checkbox unticked
		delete_post_meta( $post_id, $key );
	}

	function bids_add_menu(){
		$title = appthemes_bidding_get_args('name');
		add_submenu_page( 'edit.php?post_type='.HRB_PROJECTS_PTYPE, $title, $title, 'moderate_comments', 'edit-comments.php?comment_type='.appthemes_get_bidding_ctype() );
	}

} // end class
