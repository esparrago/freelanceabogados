<?php
/**
 * Functions related with the main frontend loops for users and projects.
 */

add_action( 'hrb_front_loops', '_hrb_loop_projects', 10 );
add_action( 'hrb_front_loops', '_hrb_loop_users', 11 );


### Hooks Callbacks

/**
 * Queries and paginates the projects and loads the related loop template.
 */
function _hrb_loop_projects() {
	global $hrb_options;

	if ( ! $hrb_options->projects_frontpage ) {
		return;
    }

	$args = array(
		'posts_per_page' => (int) $hrb_options->projects_frontpage,
		'meta_key'	=> HRB_ITEM_FEATURED_HOME,
		'orderby' => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
	);

	$template_vars = array(
		'projects' => hrb_get_projects( $args ),
	);

	appthemes_load_template( 'loop-'.HRB_PROJECTS_PTYPE.'.php', $template_vars );

}

/**
  * Queries and paginates the users and loads the related loop template.
 */
function _hrb_loop_users() {
	global $hrb_options;

	if ( ! $hrb_options->users_frontpage ) {
		return;
    }

    $params = array(
        'number' => (int) $hrb_options->users_frontpage ,
        'hrb_orderby' => 'rate',
    );

	$template_vars = array(
		'users' => hrb_get_freelancers( $params ),
   );
   appthemes_load_template( 'loop-'.HRB_FREELANCER_UTYPE.'.php', $template_vars );
}


/**
 * Provides a dynamic hook to append content before a post type section.
 *
 * @uses do_action() Calls 'hrb_before_{$type}{$section}'
 *
 */
function hrb_before_post_section( $type = 'post', $section = 'content' ) {
	if ( $type ) $type .= '_';
	do_action( "hrb_before_{$type}{$section}" );
}

/**
 * Provides a dynamic hook to append content after a post type section.
 *
 * @uses do_action() Calls 'hrb_after_{$type}{$section}'
 *
 */
function hrb_after_post_section( $type = 'post', $section = 'content' ) {
	if ( $type ) $type .= '_';
	do_action( "hrb_after_{$type}{$section}" );
}

/**
  * Provides a dynamic hook to append content before a user section.
 *
 * @uses do_action() Calls 'hrb_before_user'
 */
function hrb_before_user( $user_id ) {
	do_action( 'hrb_before_user', $user_id );
}

/**
  * Provides a dynamic hook to append content after a user section.
 *
 * @uses do_action() Calls 'hrb_after_user'
 */
function hrb_after_user( $user_id ) {
	do_action( 'hrb_after_user', $user_id );
}