<?php
/**
 * Mirrors WordPress template tag functions (the_post(), the_content(), etc), used in the Loop .
 *
 * Altough some function might not be used in a WP Loop, they intend to work in the same way as to be self explanatory and retrieve data intuitively.
 *
 * Contains template tag functions for: freelancers
 *
 */

add_action( 'hrb_output_user_actions', 'hrb_output_user_actions' );


### Hooks Callbacks

/**
 * Outputs the formatted available actions for a user.
 */
function hrb_output_user_actions( $user ) {
	$user = get_the_hrb_userdata( $user );

	// edit profile
	the_hrb_user_edit_profile_link( $user );
}


### URL's

/**
 * Retrieves the user profile URL. Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_user_profile_url( $user = '' ) {
	global $wp_rewrite, $hrb_options;

	if ( ! $user ) {
		$user = get_userdata( get_the_author_meta( 'ID' ) );
	}

	if ( $wp_rewrite->using_permalinks() ) {
		$profile_permalink = $hrb_options->profile_permalink;
		return home_url( user_trailingslashit( "$profile_permalink/$user->user_nicename" ) );
	}

	return add_query_arg( array( 'profile_author' => $user->user_nicename ), home_url() );
}


### User Details / Meta

/**
 * Outputs the formatted user Bio.
 */
function the_hrb_user_member_since( $user = '', $before = '', $after = '' ) {
	$user = get_the_hrb_userdata( $user );

	$registered = mysql2date( get_option('date_format'), $user->user_registered );

	echo $before . $registered . $after;
}

/**
 * Outputs the formatted freelancer title. Optionally adds the featured CSS class if the listing is featured.
 */
function the_hrb_user_display_name( $user = '', $before = '', $after = '', $atts = array() ) {
	$user = get_the_hrb_userdata( $user );

	$defaults = array(
		'href'			=> esc_url( $user->profile_url ),
		'title'			=> esc_attr( $user->display_name ),
		'class'			=> 'user-title',
		'rel'			=> 'bookmark',
		'featured_tag'	=> 'span',
	);
	$atts = wp_parse_args( $atts, $defaults );

	$title = html( 'a', $atts, $atts['title'] );

	if ( ! empty( $atts['featured_tag'] ) && is_hrb_user_featured( $user ) ) {

		$attr_featured = array(
			'class' => hrb_user_featured_class( $user ),
		);

		$title = html( $atts['featured_tag'], $attr_featured, $atts['title'] );
	}

	echo $before . $title . $after;
}

/**
 * Retrieves the user display name.
 */
function get_the_hrb_user_display_name( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return $user->display_name;
}

/**
 * Retrieves the user Bio.
 */
function get_the_hrb_user_bio( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return apply_filters( 'the_content', $user->user_description, array( 'user' => $user ) );
}

/**
 * Outputs the formatted user Bio.
 */
function the_hrb_user_bio( $user = '', $before = '', $after = '' ) {
	echo $before . get_the_hrb_user_bio( $user ) . $after;
}

/**
 * Retrieves the clickable user gravatar.
 */
function get_the_hrb_user_gravatar( $user = '', $size = 45, $atts = array() ) {
	$user = get_the_hrb_userdata( $user );

	$defaults = array(
		'href' => esc_url( $user->profile_url ),
		'title' => $user->display_name,
		'rel' => 'bookmark'
	);
	$atts = wp_parse_args( $atts, $defaults );

	if ( ! empty( $user->gravatar ) ) {
		$gravatar = wp_get_attachment_image( $user->gravatar, array( $size, $size ) )	;
	} else {
		$gravatar = get_avatar( $user->ID, $size );
	}

	return html( 'a', $atts, $gravatar );
}

/**
 * Outputs the formatted user gravatar.
 */
function the_hrb_user_gravatar( $user = '', $size = 45, $before = '', $after = '', $atts = array() ) {
	echo $before . get_the_hrb_user_gravatar( $user, $size, $atts ) . $after;
}

/**
 * Outputs the formatted user bulk info: gravatar, display name, rating, success rate, by calling each of the related template functions.
 */
function the_hrb_user_bulk_info( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	$defaults = array(
		'hide_if_author' => false, // don't use different name if avatar belongs to current logged user
		'show_gravatar' => array(
			'size' => 45,
			'before' => '<span class="user-gravatar">',
			'after' => '</span>',
			'atts' => array(),
		),
		'show_name' => array(
			'before' => '<span class="user-display-name">',
			'after' => '</span>',
			'atts' => array(),
		),
		'show_rating' => array(
			'before' => '<span class="user-rating">',
			'after' => '</span>',
			'atts' => array(),
		),
		'show_success_rate' => array(
			'before' => '<span class="user-success-rate">',
			'after' => '</span>',
			'atts' => array(),
		),
	);

	$args = wp_parse_args( $args, $defaults );

	foreach( $args as $key => $value ) {
		$args[ $key ] = wp_parse_args( $args[ $key ], $defaults[ $key ] );
	}

	if ( $args['hide_if_author'] && $user->ID == get_current_user_id() ) {
		return;
	}

	if ( ! empty( $args['show_gravatar'] ) ) {
		extract( $args['show_gravatar'] );
		the_hrb_user_gravatar( $user, $size, $before, $after, $atts );
	}

	if ( ! empty( $args['show_name'] ) ) {
		extract( $args['show_name'] );
		the_hrb_user_display_name( $user, $before, $after, $atts );
	}

	if ( ! empty( $args['show_rating'] ) ) {
		extract( $args['show_rating'] );
		the_hrb_user_rating( $user, '', $before, $after, $atts );
	}

	if ( ! empty( $args['show_success_rate'] ) ) {
		extract( $args['show_success_rate'] );
		the_hrb_user_success_rate( $user, $before, $after, $atts );
	}

}

/**
 * Retrieves the user contact details.
 */
function get_the_hrb_user_contact_info( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return $user->hrb_email;
}

/**
 * Oututs the formatted user contact details.
 */
function the_hrb_user_contact_info( $user = '', $before = '', $after = '' ) {
	$email = get_the_hrb_user_contact_info( $user );

	echo $before . make_clickable( $email ) . $after;
}

/**
 * Retrieves the user price rate.
 */
function get_the_hrb_user_rate( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	$rate = (float) $user->hrb_rate;
	$currency = $user->hrb_currency;

	return appthemes_get_price( $rate, $currency );
}

/**
 * Outputs the formatted user price rate.
 */
function the_hrb_user_rate( $user = '', $before = '', $after = '' ) {
	echo $before . sprintf( __( '<span>%s</span> per hour', APP_TD ), get_the_hrb_user_rate( $user ) ) . $after;
}

/**
 * Retrieves the user location.
 */
function get_the_hrb_user_location( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return $user->hrb_location ? $user->hrb_location : __( 'n/a', APP_TD );
}

/**
 * Outputs the formatted user location.
 */
function the_hrb_user_location( $user = '', $before = '', $after = '' ) {
	echo $before . get_the_hrb_user_location( $user ) . $after;
}

/**
 * Retrieves the user portfolio URL.
 */
function get_the_hrb_user_portfolio( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return $user->user_url;
}

/**
 * Outputs the formatted user portfolio URL.
 */
function the_hrb_user_portfolio( $user = '', $before = '', $after = '' ) {
	echo $before . html_link( get_the_hrb_user_portfolio( $user ), __( 'View Portfolio', APP_TD ) ) . $after;
}

/**
 * Retrieves the list of social networks registered by a user.
 */
function get_the_hrb_user_social_networks( $user = '' ) {

	if ( ! $user ) {
		$user = get_userdata( get_the_author_meta('ID') );
	}

	$social_networks = array();

	foreach( APP_Social_Networks::get_support() as $network_id ){
		$network = "hrb_{$network_id}";

		if ( $user->$network ) {
			$social_networks[ $network_id ] = $user->$network;
		}
	}
	return $social_networks;
}


### User -> Project(s)

/**
 * Retrieves the projects authored by a user.
 */
function get_the_hrb_user_projects( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	$defaults = array(
		'nopaging' => true,
		'author' => $user->ID
	);
	$args = wp_parse_args( $args, $defaults );

	return hrb_get_projects( $args );
}

/**
 * Retrieves the projects where a user is participating as owner or worker (any status).
 */
function get_the_hrb_user_related_projects( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	$defaults = array(
		'post_status' => 'any',
	);
	$args = wp_parse_args( $args, $defaults );

	// the second param is used to filter the workspace so we keep only the post status and strip out all other args
	return hrb_p2p_get_participating_posts( $user->ID, array( 'post_status' => $args['post_status'] ), $args );
}

/**
 * Retrieves the projects where a user is participating as owner or worker - status is publish or working.
 */
function get_the_hrb_user_related_active_projects( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	$args['post_status'] = array( HRB_PROJECT_STATUS_WORKING, 'publish' );

	return get_the_hrb_user_related_projects( $user->ID, $args );
}

/**
 * Outputs the formatted stats count for projects assigned to a user.
 */
function the_hrb_user_related_active_projects_count( $user = '', $args = array(), $before = '', $after = '' ) {
	$projects = get_the_hrb_user_related_active_projects( $user, $args );

	$count = 0;

	if ( $projects ) {
		$count = $projects->post_count;
	}

	echo $before . $count . $after;
}

/**
 * Retrieves the projects where a user participated as owner or worker - status is closed_complete.
 */
function get_the_hrb_user_related_completed_projects( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	$args['post_status'] = HRB_PROJECT_STATUS_CLOSED_COMPLETED;

	return get_the_hrb_user_related_projects( $user->ID, $args );
}

/**
 * Outputs the formatted stats count for completed projects assigned to a user.
 */
function the_hrb_user_completed_projects_count( $user = '', $args = array(), $before = '', $after = '' ) {
	$projects = get_the_hrb_user_related_completed_projects( $user, $args );

	$count = 0;

	if ( $projects ) {
		$count = $projects->post_count;
	}

	echo $before . $count . $after;
}


### Reviews / Rating

/**
 * Outputs the formatted rating for a user.
 */
function the_hrb_user_rating( $user = '', $echo_no_rating = '', $before = '', $after = '', $atts = array() ) {
	$avg_rating = get_the_hrb_user_avg_rating( $user );

	ob_start();

	hrb_rating_html( $avg_rating, $echo_no_rating, $atts );

	$rating_html = ob_get_clean();

	echo $before . $rating_html . $after;
}

/**
 * Retrieves the success/completion rate for a user.
 */
function get_the_hrb_user_success_rate( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	return appthemes_get_user_success_rate( $user->ID, $args );
}

/**
 * Outputs the formatted success/completion rate for a user.
 */
function the_hrb_user_success_rate( $user = '', $before = '', $after = '', $atts = array() ) {
	$rate = get_the_hrb_user_success_rate( $user );

	if ( $rate < 0 ) {
		$rate = __( 'n/a', APP_TD );
	} else {
		$rate = sprintf( '%d%%', $rate );
	}
	echo $before . $rate . $after;
}

/**
 * Retrieves the average rating for a user.
 */
function get_the_hrb_user_avg_rating( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	return appthemes_get_user_avg_rating( $user->ID, $args );
}

/**
 * Outputs the average rating for a user.
 */
function the_hrb_user_avg_rating( $user = '', $args = array(), $before = '', $after = '' ) {
	echo $before . get_the_hrb_user_avg_rating( $user, $args ) . $after;
}

/**
 * Retrieves the total number of reviews a user has received.
 */
function get_the_hrb_user_total_reviews( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	return (int) appthemes_get_user_total_reviews( $user->ID, $args );
}

/**
 * Outputs the formatted total number of reviews a user has received.
 */
function the_hrb_user_total_reviews( $user = '', $args = array(), $before = '', $after = '' ) {
	echo $before . get_the_hrb_user_total_reviews( $user, $args ) . $after;
}

/**
 * Retrieves the total number of reviews authored by a user.
 */
function get_the_hrb_user_total_authored_reviews( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return (int) appthemes_get_user_authored_total_reviews( $user->ID );
}

/**
 * Outputs the formatted total number of reviews authored by a user.
 */
function the_hrb_user_total_authored_reviews( $user = '', $args = array(), $before = '', $after = '' ) {
	echo $before . get_the_hrb_user_total_authored_reviews( $user, $args ) . $after;
}


/**
 * Retrieves the clickable list of skills terms, for a user.
 */
function get_the_hrb_user_skills( $user = '', $before = '', $after = '', $atts = array() ) {
	$user = get_the_hrb_userdata( $user );

	// we take the skills array directly from the user meta instead of the user object because the object only return single values
	$term_ids = get_user_meta( $user->ID, 'hrb_user_skills' );

	if ( empty( $term_ids) ) {
		return array();
	}

	$terms = get_terms( HRB_PROJECTS_SKILLS, array( 'include' => array_values( $term_ids ), 'hide_empty' => false ) );

	$term_links = array();

	foreach ( $terms as $term ) {
		// always check if it's an error before continuing. get_term_link() can be finicky sometimes
		$link = get_term_link( $term, HRB_PROJECTS_SKILLS );
		if ( is_wp_error( $link ) ) {
			continue;
		}

		$a_atts = array(
			'href' => esc_url( $link ),
			'rel' => 'tag',
		);
		$term_links[] = html( 'a', $a_atts, $before . $term->name . $after );
	}
	return $term_links;
}

/**
 * Outputs the formatted clickable list of skills for a user.
 */
function the_hrb_user_skills( $user = '', $separator = ' ', $before = '', $after = '' ) {
	$term_links = get_the_hrb_user_skills( $user, $before, $after );

	echo join( $separator, $term_links );
}

/**
 * Outputs the formatted link for editing a user profile.
 */
function the_hrb_user_edit_profile_link( $user = '', $text = '', $before = '', $after = '' ) {
	$user = get_the_hrb_userdata( $user );

	if ( ! current_user_can( 'edit_user', $user->ID ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Edit Profile', APP_TD );
	}

	$link = html( 'a', array(
		'class' => 'button secondary profile-edit',
		'href' => appthemes_get_edit_profile_url(),
	), $text );

	echo $before . $link . $after;
}

/**
 * Outputs the available actions for a user (edit profile, etc).
 *
 * @uses do_action() Calls 'hrb_output_user_actions'
 *
 */
function the_hrb_user_actions( $user = '' ) {
	do_action( 'hrb_output_user_actions', $user );
}


### Conditional Tags

 /**
  * Checks if a user is featured by checking the addons stored in his user meta.
  */
function is_hrb_user_featured( $user = '', $addons = array() ) {
	$user = get_the_hrb_userdata( $user );

	// @todo maybe allow featuring users in the future

	if ( empty( $addons ) ) {
		$addons = array();
	}

	foreach( $addons as $addon ){
		//$featured = get_user_meta( $user->ID, $addon, true );
		$featured = $user->$addon;
		if ( ! empty( $featured ) ) {
			return true;
		}
	}
	return false;
}


## Helper functions

/**
 * Retrieves the data for a given user or for the loop post author.
 */
function get_the_hrb_userdata( $user = '' ) {

	if ( ! is_a( $user, 'WP_User' ) ) {
		$user_id = $user ? $user : get_the_author_meta('ID');
		$user = get_userdata( $user_id );
	}

	if ( ! empty( $user->_app_gravatar[0] ) ) {
		$user->gravatar = $user->_app_gravatar[0];
	}

	$user->profile_url = get_the_hrb_user_profile_url( $user );

	$user->social_networks = get_the_hrb_user_social_networks( $user );

	return $user;
}


### Conditionals

/**
 * Checks if user is viewing a users archive page.
 */
function is_hrb_users_archive() {
	return (bool) get_query_var('is_hrb_archive_users');
}

/**
 * Checks if user is viewing a users search page.
 */
function is_hrb_users_search() {
	return (bool) get_query_var('is_hrb_archive_users') && is_search();
}
