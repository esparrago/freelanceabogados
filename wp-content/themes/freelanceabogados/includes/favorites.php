<?php
/**
 * Hooks and functions related with favoriting.
 */

add_action( 'init', '_hrb_p2p_register_favorites', 13 );


### Hooks Callbacks

/**
 * Registers p2p connections favorites. Relates users with posts of type 'project'.
 */
function _hrb_p2p_register_favorites() {

	$ajax_action = 'project_favorite';

	add_action( 'wp_ajax_' . $ajax_action, 'hrb_handle_ajax_favorites' );
	add_action( 'wp_ajax_nopriv_' . $ajax_action, 'hrb_handle_ajax_favorites' );

	p2p_register_connection_type( array(
		'name' => HRB_P2P_PROJECTS_FAVORITES,
		'from' => HRB_PROJECTS_PTYPE,
		'to' => 'user'
	) );

}


### Helper Functions

/**
 * Handle an ajax request for favoriting/un-favoriting a project.
 */
function hrb_handle_ajax_favorites() {

	if ( ! isset( $_POST['favorite'] ) && ! isset( $_POST['post_id'] ) && ! isset( $_POST['current_url'] ) ) {
		return;
	}

	if ( ! in_array( $_POST['favorite'], array( 'add', 'delete' ) ) ) {
		return;
	}

	$post_id = (int) $_POST['post_id'];

	check_ajax_referer( "favorite-{$post_id}" );

	$redirect = '';

	$status = 'success';

	if ( is_user_logged_in() ) {

		if ( 'add' == $_POST['favorite'] ) {
			$notice = __( "The project was added to your favorites.", APP_TD );
			$p2p = p2p_type( HRB_P2P_PROJECTS_FAVORITES )->connect( $post_id, get_current_user_id(), array( 'date' => current_time('mysql')) );
		} else {
			$notice = __( "The project was removed from your favorites.", APP_TD );
			$p2p = p2p_type( HRB_P2P_PROJECTS_FAVORITES )->disconnect( $post_id, get_current_user_id() );
		}

		if ( is_wp_error( $p2p ) ) {
			$status = 'error';
			$notice = sprintf( __( "Could not add '%s' to favorites at this time.", APP_TD ), get_the_title( $post_id ) );
		}

	} else {

		$redirect = esc_url( wp_sanitize_redirect( $_POST['current_url'] ) );
		$status = 'error';
		$notice = sprintf ( __( 'You must <a href="%1$s">login</a> to be able to favorite a project.', APP_TD ), wp_login_url( $redirect ) );

	}

	ob_start();

	appthemes_display_notice( $status, $notice );

	$notice = ob_get_clean();

	$result = array(
		'html' 	 	=> get_the_hrb_project_favorite_link( $post_id, '', '', array( 'base_url' => wp_sanitize_redirect( $_POST['current_url'] ) ) ),
		'status' 	=> $status,
		'notice' 	=> $notice,
		'redirect' 	=> $redirect,
	);
	die( json_encode( $result ) );
}

/**
 * Retrieves a list of favorited projects.
 */
function hrb_get_favorited_projects( $args = array() ) {
	global $hrb_options;

	$dashboard_user = wp_get_current_user();

	$defaults = array(
		'post_type'			=> HRB_PROJECTS_PTYPE,
		'paged'				=> get_query_var( 'paged' ),
		'posts_per_page'	=> $hrb_options->projects_per_page,
		'post_status'		=> array( 'publish' ),
		'connected_type'	=> HRB_P2P_PROJECTS_FAVORITES,
		'connected_items'	=> $dashboard_user->ID,
	);
	$args = wp_parse_args( $args, $defaults );

	return new WP_Query( $args );
}
