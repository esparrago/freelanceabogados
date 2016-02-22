<?php
/**
 * Disputes related functions.
 *
 * @package Components\Disputes
 */

define( 'APP_DISPUTE_PTYPE', 'dispute' );

define( 'APP_DISPUTE_STATUS_PAY', 'dispute_paid' );
define( 'APP_DISPUTE_STATUS_REFUND', 'dispute_refunded' );

add_action( 'init', '_appthemes_register_dispute_related', 14 );

add_action( 'publish_to_dispute_paid', 'appthemes_end_dispute' );
add_action( 'publish_to_dispute_refunded', 'appthemes_end_dispute' );

add_action( 'appthemes_dispute_resolved', '_appthemes_dispute_close_comments' );

add_action( 'comment_post_redirect', '_appthemes_dispute_comment_redirect', 10, 2 );
add_action( 'wp_insert_comment', '_appthemes_insert_dispute_comment', 10, 2 );

add_filter( 'preprocess_comment', '_appthemes_dispute_preprocess_comment' );

add_filter( 'admin_comment_types_dropdown' , '_appthemes_dispute_dropdown_comment_type' );

### Hook callbacks

function _appthemes_register_dispute_related() {

	if ( ! appthemes_disputes_get_args( 'enable_disputes' ) ) {
		return;
	}

	_appthemes_register_dispute_post_type();
	_appthemes_register_disputes_statuses();
	_appthemes_p2p_disputes_register();
}

/**
 * Registers the dispute post type.
 */
function _appthemes_register_dispute_post_type() {

	$labels = array(
		'name'				=> __( 'Disputes', APP_TD ),
		'singular_name'		=> __( 'Dispute', APP_TD ),
		'add_new'			=> __( 'Add New', APP_TD ),
		'add_new_item'		=> __( 'Add New Dispute', APP_TD ),
		'edit_item'			=> __( 'Edit Dispute', APP_TD ),
		'new_item'			=> __( 'New Dispute', APP_TD ),
		'view_item'			=> __( 'View Dispute', APP_TD ),
		'search_items'		=> __( 'Search Disputes', APP_TD ),
		'not_found'			=> __( 'No disputes found', APP_TD ),
		'not_found_in_trash'=> __( 'No disputes found in Trash', APP_TD ),
		'parent_item_colon' => __( 'Parent Disputes:', APP_TD ),
		'menu_name'			=> __( 'Disputes', APP_TD ),
	);

	$args = array(
		'labels'			=> $labels,
		'hierarchical'		=> false,
		'supports'			=> array( 'title', 'editor', 'author' ),
		'public'			=> false,
		'publicly_queryable'=> false,
		'query_var'			=> false,
		'map_meta_cap'		=> true,
		'capabilities'		=> array(
			'create_posts' => 'open_dispute',
		  ),
		'menu_icon' => 'dashicons-shield',
		'show_ui' => true,
	);

	if ( appthemes_disputes_get_args('allow_comments') ) {
		$args['supports'][] = 'comments';
	}

	register_post_type( APP_DISPUTE_PTYPE, $args );
}

/**
 * Register disputes related payments statuses.
 */
function _appthemes_register_disputes_statuses() {

	register_post_status( APP_DISPUTE_STATUS_PAY, array(
		'public' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Paid <span class="count">(%s)</span>', 'Paid <span class="count">(%s)</span>', APP_TD ),
	));

	register_post_status( APP_DISPUTE_STATUS_REFUND, array(
		'public' => true,
		'show_in_admin_all_list' => true,
		'show_in_admin_status_list' => true,
		'label_count' => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', APP_TD ),
	));

}

/**
 * Registers p2p connections for 'disputes' connecting them to another custom post type.
 */
function _appthemes_p2p_disputes_register() {

	// project workspaces connection
	p2p_register_connection_type( array(
		'name' => appthemes_get_dispute_p2p_name(),
		'from' => appthemes_disputes_get_args('post_type'),
		'to' => APP_DISPUTE_PTYPE,
	) );

}

/**
 * Process comment disputes with a different custom type if post type is a dispute.
 */
function _appthemes_dispute_preprocess_comment( $commentdata ) {

	$post = get_post( $commentdata['comment_post_ID'] );

	if ( APP_DISPUTE_PTYPE != $post->post_type ) {
		return $commentdata;
	}

	// set the custom comment type
	$commentdata['comment_type'] = key( appthemes_disputes_get_args('comment_type') );

	return $commentdata;
}

/**
 * Display disputes custom comment type on the backend comments dropdown.
 */
function _appthemes_dispute_dropdown_comment_type( $comment_types ) {

	if ( ! appthemes_disputes_get_args('comment_type') ) {
		return $comment_types;
	}

	return $comment_types + appthemes_disputes_get_args('comment_type');
}

/**
 * Redirect the user after submitting a dispute comment.
 *
 * @uses apply_filters() Calls 'appthemes_dispute_comment_redirect'
 *
 * @param string $location The URL location to redirect the user when posting a dispute comment.
 * @param WP_Comment $comment The comment object.
 * @return string The redirect URL location.
 */
function _appthemes_dispute_comment_redirect( $location, $comment ) {

	if ( APP_DISPUTE_PTYPE != $comment->comment_type ) {
		return $location;
	}

	return apply_filters( 'appthemes_dispute_comment_redirect', $location, $comment );
}

/**
 * Fires a specific action whenever a dispute comment in inserted into the database.
 *
 * @uses do_action() Calls 'appthemes_dispute_comment_insert'
 *
 * @param int $id The comment ID.
 * @param WP_Comment The $comment object.
 */
function _appthemes_insert_dispute_comment( $id, $comment ) {

	if ( APP_DISPUTE_PTYPE != $comment->comment_type ) {
		return;
	}

	do_action( 'appthemes_dispute_comment_insert', $id, $comment );
}

### Helper Functions

/**
 * Builds the p2p connection name considering the connected post type.
 *
 * @return string The dispute p2p connection name.
 */
function appthemes_get_dispute_p2p_name() {
	return appthemes_disputes_get_args('post_type') . '_' . APP_DISPUTE_PTYPE;
}

/**
 * Creates a dispute and retrieves the new ID.
 *
 * @uses do_action() Calls 'appthemes_raise_dispute'
 *
 * @param WP_Post $post A post object to connect to a dispute.
 * @param string $disputer The disputing user ID.
 * @param string $disputee The recipient user ID of the dispute.
 * @param string $reason (optional) The reason for the dispute.
 * @param array $args (optional) Additional args for the p2p query.
 * @return int|boolean The dispute p2p ID or False on error.
 */
function appthemes_raise_dispute( $post, $disputer, $disputee, $reason, $args = array() ) {

	$defaults = array(
		'post_type'		=> APP_DISPUTE_PTYPE,
		'post_status'	=> 'publish',
		'post_author'	=> $disputer,
		'post_title'	=> $post->post_title,
		'post_content'	=> $reason,
	);
	$args = wp_parse_args( $args, $defaults );

	$dispute_id = wp_insert_post( $args );

	if ( is_wp_error( $dispute_id ) ) {
		return false;
	}

	$meta = array(
		'participants' => array( 'disputer' => $disputer, 'disputee' => $disputee ),
		'disputer' => $disputer,
		'disputee' => $disputee,
	);

	$p2p = _appthemes_p2p_connect_dispute_to( $dispute_id, $post->ID, $meta );

	// if we are not able to do a p2p connection remove the newly created dispute post
	if ( ! $p2p ) {
		wp_delete_post( $dispute_id, $force_delete = true );
		return false;
	}

	do_action( 'appthemes_dispute_opened', $dispute_id, $p2p, $post );

	return $dispute_id;
}

/**
 * Create a new p2p relation between a post type and a 'dispute'.
 */
function _appthemes_p2p_connect_dispute_to( $dispute_id, $post_id, $meta = array() ) {

	$defaults = array(
		'timestamp' => current_time( 'mysql' ),
	);
	$meta = wp_parse_args( $meta, $defaults );

	$p2p = p2p_type( appthemes_get_dispute_p2p_name() )->connect( $dispute_id, $post_id, $meta );

	return $p2p;
}

/**
 * Retrieves disputes for a given post ID.
 *
 * @param int $post_id The post ID to retrieve disputes from,
 * @param int $disputer (optional) The user ID opening the dispute.
 * @param int $disputee (optional) The user ID being targeted for the dispute.
 * @param array $args (optional) Additional args for the p2p query.
 * @return array A collection of WP_Post objects representing disputes.
 */
function appthemes_get_disputes_for( $post_id, $disputer = 0, $disputee = 0, $args = array() ) {

	$defaults = array(
		'post_status' => 'publish',
		'connected_direction' => 'from',
		'suppress_filters' => false,
		'nopaging' => true
	);

	if ( $disputer ) {
		$defaults['connected_meta'][] = array( 'disputer' =>  $disputer );
	}

	if ( $disputee ) {
		$defaults['connected_meta'][] = array( 'disputee' =>  $disputee );
	}

	$args = wp_parse_args( $args, $defaults );

	$p2p = p2p_type( appthemes_get_dispute_p2p_name() )->get_connected( $post_id, $args );

	return $p2p->posts;
}

/**
 * Retrieve the post connected to a given dispute ID.
 *
 * @param int $post_id The dispute post ID to retrieve the post from.
 * @param array $args (optional) Additional args for the p2p query.
 * @return WP_Post|null The post object if found, or null otherwise.
 */
function appthemes_get_dispute_p2p_post( $post_id, $args = array() ) {

	$defaults = array(
		'connected_query' => array( 'post_status' => 'any' ),
		'suppress_filters' => false,
		'nopaging' => true
	);
	$args = wp_parse_args( $args, $defaults );

	$posts = p2p_type( appthemes_get_dispute_p2p_name() )->get_connected( $post_id, $args );

	return reset( $posts->posts );
}


### Verbiages

/**
 * Retrieves the verbiages for a given post status.
 *
 * @param string $status The status to retrieve status labels.
 * @return type
 */
function appthemes_get_disputes_statuses_verbiages( $status = '' ) {

	$verbiages = array(
		'publish'			=> __( 'Opened for Review', APP_TD ),
		'dispute_paid'		=> __( 'Paid', APP_TD ),
		'dispute_refunded'	=> __( 'Refunded', APP_TD ),
	);

	return appthemes_get_verbiage_values( $verbiages, $status );
}

/**
 * Retrieves a single value or list of values from a list of given verbiages key/value pairs.
 *
 * @param array $verbiages The verbiages collection.
 * @param string $key (optional) The verbiage key to retrieve a specific verbiage.
 * @return array|null The requested verbiage or null if not found.
 */
function appthemes_get_verbiage_values( $verbiages, $key = '' ) {

	if ( $key && ! isset( $verbiages[ $key ] ) ) {
		return;
	}

	if ( $key && isset( $verbiages[ $key ] ) ) {
		return $verbiages[ $key ];
	}
	return $verbiages;
}

/**
 * Retrieves the verbiage for a given status using the dynamic callback set
 * in the 'verbiages_callback' param in 'add_theme_support()'.
 *
 * Defaults to the 'get_post_status_object' callback if the param is empty.
 *
 * @param string $status The status to retrieve the verbiage.
 * @return string The verbiage for the given status.
 */
function appthemes_get_disputes_p2p_status_verbiages( $status ) {

	$callback = appthemes_disputes_get_args('verbiages_callback');

	$result = call_user_func( $callback, $status );

	if ( 'get_post_status_object' == $callback && is_object( $result )  ) {
		return $result->label;
	}
	return $result;
}

/**
 * Retrieves a collection of participant ID's for a given post ID.
 *
 * @param int $post_id The dispute post ID to retrieve the participants from.
 * @return array A collection of the dispute participant ID's.
 */
function appthemes_get_dispute_participants( $post_id ) {
	$p2p = appthemes_get_dispute_p2p_post( $post_id );
	return p2p_get_meta( $p2p->p2p_id, 'participants', true );
}

/**
 * Triggers hook on ending disputes status change.
 *
 * @uses do_action() Calls 'appthemes_dispute_paid'
 * @uses do_action() Calls 'appthemes_dispute_refunded'
 * @uses do_action() Calls 'appthemes_dispute_resolved'
 *
 * @param WP_Post $post The dispute post object.
 */
function appthemes_end_dispute( $post ) {

	$status = $post->post_status;

	$p2p_post = appthemes_get_dispute_p2p_post( $post->ID );

	do_action( "appthemes_$status", $post, $p2p_post, $status );

	do_action( 'appthemes_dispute_resolved', $post, $p2p_post );
}

/**
 * Closes comments for a given dispute post.
 */
function _appthemes_dispute_close_comments( $post ) {

	$post = array(
		'ID' => $post->ID,
		'comment_status' => 'closed',
	);

	wp_update_post( $post );
}

/**
 * Mirrors WP 'comments_template()' but make it more flexible to display comments on any post.
 *
 * @todo maybe move to framework
 *
 * @param string $file              Optional. The file to load. Default '/comments.php'.
 * @param bool   $separate_comments Optional. Whether to separate the comments by comment type.
 *                                  Default false.
 * @return null Returns null if no comments appear.
 */
function appthemes_comments_template( $comments_post, $file = '/comments.php', $separate_comments = false ) {
	global $wp_query, $post, $withcomments, $wpdb, $id, $comment, $user_login, $user_ID, $user_identity, $overridden_cpage;

	if ( empty( $post ) || 'open' != $post->comment_status ) {
		return;
	}

	// store the original '$post' global on a temp var
	$orig_post = $post;

	// temporarily override the '$post' global so the 'comments.php' works correctly for these comments post object
	$post = $comments_post;

	if ( empty($file) ) {
		$file = '/comments.php';
	}

	$req = get_option('require_name_email');

	/*
	 * Comment author information fetched from the comment cookies.
	 * Uuses wp_get_current_commenter().
	 */
	$commenter = wp_get_current_commenter();

	/*
	 * The name of the current comment author escaped for use in attributes.
	 * Escaped by sanitize_comment_cookies().
	 */
	$comment_author = $commenter['comment_author'];

	/*
	 * The email address of the current comment author escaped for use in attributes.
	 * Escaped by sanitize_comment_cookies().
	 */
	$comment_author_email = $commenter['comment_author_email'];

	/*
	 * The url of the current comment author escaped for use in attributes.
	 */
	$comment_author_url = esc_url($commenter['comment_author_url']);

	/** @todo Use API instead of SELECTs. */
	if ( $user_ID) {
		$comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND (comment_approved = '1' OR ( user_id = %d AND comment_approved = '0' ) )  ORDER BY comment_date_gmt", $post->ID, $user_ID));
	} else if ( empty($comment_author) ) {
		$comments = get_comments( array('post_id' => $post->ID, 'status' => 'approve', 'order' => 'ASC') );
	} else {
		$comments = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND ( comment_approved = '1' OR ( comment_author = %s AND comment_author_email = %s AND comment_approved = '0' ) ) ORDER BY comment_date_gmt", $post->ID, wp_specialchars_decode($comment_author,ENT_QUOTES), $comment_author_email));
	}

	/**
	 * Filter the comments array.
	 *
	 * @since 2.1.0
	 *
	 * @param array $comments Array of comments supplied to the comments template.
	 * @param int   $post_ID  Post ID.
	 */
	$wp_query->comments = apply_filters( 'comments_array', $comments, $post->ID );
	$comments = &$wp_query->comments;
	$wp_query->comment_count = count($wp_query->comments);
	update_comment_cache($wp_query->comments);

	if ( $separate_comments ) {
		$wp_query->comments_by_type = separate_comments($comments);
		$comments_by_type = &$wp_query->comments_by_type;
	}

	$overridden_cpage = false;
	if ( '' == get_query_var('cpage') && get_option('page_comments') ) {
		set_query_var( 'cpage', 'newest' == get_option('default_comments_page') ? get_comment_pages_count() : 1 );
		$overridden_cpage = true;
	}

	if ( ! defined('COMMENTS_TEMPLATE') ) {
		define('COMMENTS_TEMPLATE', true);
	}

	$theme_template = STYLESHEETPATH . $file;
	/**
	 * Filter the path to the theme template file used for the comments template.
	 *
	 * @since 1.5.1
	 *
	 * @param string $theme_template The path to the theme template file.
	 */
	$include = apply_filters( 'comments_template', $theme_template );

	if ( file_exists( $include ) ) {
		require( $include );
	} elseif ( file_exists( TEMPLATEPATH . $file ) ) {
		require( TEMPLATEPATH . $file );
	} else {
		require( ABSPATH . WPINC . '/theme-compat/comments.php');
	}

	// restore the '$post' to the original value
	$post = $orig_post;
}

/**
 * Retrieve the dispute decision meta.
 *
 * @param type $post_id The dispute post ID.
 * @return string The dispute decision.
 */
function appthemes_get_dispute_decision( $post_id ) {
	return get_post_meta( $post_id, 'official_response', true );
}
