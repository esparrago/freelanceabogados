<?php
/**
 * Functions related with users. Users roles are filtered in order to retrieve only freelancers, employers or both.
 */

add_filter( 'comments_clauses', '_hrb_multiple_authors_sql', 10, 2 );

add_action( 'pre_user_query', '_hrb_multiple_roles_sql', 10 );
add_action( 'pre_user_query', '_hrb_users_compare_like_in_sql', 10 );
add_action( 'pre_user_query', '_hrb_update_user_query_args', 21 );


### Hooks Callbacks

/**
 * Query modifier to make a comment query retrieve the comments from an array of users.
 * Currently, WordPress's 'WP_Comment_Query()' only allows retrieving comments from a single user.
 */
function _hrb_multiple_authors_sql( $clauses, $wp_comment_query ) {

	// check for the query var that modifies the query
	if ( isset( $wp_comment_query->query_vars['app_user_id__in'] ) && isset( $wp_comment_query->query_vars['user_id'] ) ) {

		$user_ids = $wp_comment_query->query_vars['app_user_id__in'];

		if ( is_array( $user_ids ) ) {
			$clauses['where'] = preg_replace( '/user_id\s*=\s*0/i', 'user_id in (' . implode( ',', $user_ids ) . ')', $clauses['where'] );
		} else {
			$wp_comment_query->query_vars['user_id'] = (int) $user_ids;
		}

	}
	return $clauses;
}

/**
 * Query modifier to get retrieve users by single or multiple roles/capabilities.
 */
function _hrb_multiple_roles_sql( $user_query ) {
	global $wpdb;

	// look for the custom query vars modifiers
	if ( ! $user_query->get('user_roles') && ! $user_query->get('hrb_list_users') ) {
		return $user_query;
	}

	$roles = $user_query->get('user_roles');
	$role_criteria = '';

	$match = $user_query->get('role_match');

	// uses loose or exact role comparison
	if ( $match ) {
		$comparison = "CAST(usermeta.meta_value AS CHAR) LIKE '%\"__ROLE__\"%'";
	} else {
		$comparison = "CAST(usermeta.meta_value AS CHAR) LIKE '%__ROLE__%'";
	}

	if ( is_array( $roles ) ) {

		foreach( $roles as $role ) {

			if ( '' != $role_criteria ) {
				$role_criteria .= ' OR ';
			}
			$role_criteria .= str_replace( '__ROLE__', $role, $comparison );

		}

	} else {
		$role_criteria = str_replace( '__ROLE__', $roles, $comparison );
	}

	$caps_meta_key = $wpdb->prefix . 'capabilities';

	// make sure to always include only users who have the 'freelancer' capability without affecting additional meta queries
	$user_query->query_where .= " AND {$wpdb->users}.ID IN ( "
		. " SELECT ID FROM {$wpdb->users} AS users "
		. "INNER JOIN {$wpdb->usermeta} AS usermeta ON (users.ID = usermeta.user_id) "
		. "WHERE ( usermeta.meta_key = '{$caps_meta_key}' AND ( " . $role_criteria . " ) )"
		. ")";

}

/**
 * Modifies user queries to allow pattern matching of user meta given a list of strings
 *
 * @since 1.1
 *
 * @param string $user_query The user query
 */
function _hrb_users_compare_like_in_sql( $user_query ) {

	if ( ! $user_query->get('hrb_like_in_strings') ) {
		return $user_query;
	}

	$strings = $user_query->get('hrb_like_in_strings');

	// sanitize meta values
	$strings = array_map( 'like_escape', (array) $strings );
	$strings = esc_sql( $strings );

	$user_query->query_where = str_replace( "= '__LIKE_IN_PLACEHOLDER__'", sprintf( "REGEXP '%s'", implode( '|', $strings ) ), $user_query->query_where );
}


/**
 * 'WP_User_Query' does not affect the final query when 'query_vars()' are changed, like it does with 'WP_Query'.
 * This forces WP to recalculate/process new user query arguments (runs only once for each user query request).
 */
function _hrb_update_user_query_args( $user_query ) {

	// look for the custom query vars modifiers
	if ( $user_query->get('hrb_refresh_user_query') || ! $user_query->get('hrb_list_users') ) {
		return $user_query;
	}

	$user_query->set( 'hrb_refresh_user_query', true );
	$user_query->prepare_query();
}


### Users Base URL

/**
 * Contextually retrieves the base URL for user listings.
 */
function get_the_hrb_users_base_url() {
	global $wp_rewrite, $hrb_options;

	if ( is_tax( array( HRB_PROJECTS_SKILLS ) ) ) {
		return get_term_link( get_queried_object() );
	}

	if ( $wp_rewrite->using_permalinks() ) {
		$url = $hrb_options->user_permalink;
		return home_url( user_trailingslashit( $url ) );
	} else {
		return add_query_arg( array( 'archive_freelancer' => 1 ), home_url() );
	}

}


### Main WP_User_Queries for Users

/**
 * Retrieves a WP_User_Query object with all users who are freelancers.
 */
function hrb_get_freelancers( $params = array() ) {

	$params['user_roles'] = HRB_ROLE_FREELANCER;

	return hrb_get_users( $params );
}

/**
 * Retrieves a WP_User_Query object with all users who are employers.
 */
function hrb_get_employers( $params = array() ) {

	$params['user_roles'] = HRB_ROLE_EMPLOYER;

	return hrb_get_users( $params );
}

/**
 * Retrieves a WP_User_Query object with all users who are freelancers, employers or freelancers/employers.
 */
function hrb_get_users( $params = array() ) {
    global $hrb_options;

    $default = array(
		'hrb_list_users' => true,
		'user_roles'	=> array( HRB_ROLE_FREELANCER, HRB_ROLE_EMPLOYER ),
		'count_total'	=> true,
        'number'		=> (int) $hrb_options->users_per_page
    );
    $params = wp_parse_args( $params, $default );

    return new WP_User_Query( $params );
}

/**
 * Retrieves the reviews for an user. Defaults to given reviews but can be parametrized to retrieve authored reviews, or both.
 */
function hrb_get_user_reviews( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	if ( isset( $args['posts_per_page'] ) ) {
		$page = max( 1, get_query_var( 'paged' ) );
		$limit = $args['posts_per_page'];
		$offset = $limit * ( $page - 1 );
	} else {
		$limit = 0;
		$offset = 0;
	}

	$defaults = array(
		'number' => $limit,
		'offset' => $offset,
	);
	$args = wp_parse_args( $args, $defaults );

	if ( isset( $args['filter_review_relation'] ) && 'authored' == $args['filter_review_relation'] ) {
		$reviews = appthemes_get_user_authored_reviews( $user->ID, $args );
	} else {
		$reviews = appthemes_get_user_reviews( $user->ID, $args );
	}
	return $reviews->reviews;
}

/**
 * Retrieves posts for a given user.
 */
function hrb_get_user_posts( $user = '', $args = array() ) {
	$user = get_the_hrb_userdata( $user );

	$defaults = array(
		'author' => $user->ID,
		'post_status' => 'publish',
		'nopaging' => true,
	);
	$args = wp_parse_args( $args, $defaults );

	return new WP_Query( $args );
}


### Meta

/**
 * Retrieves a list of all the unique countries for all existing users.
 */
function hrb_get_users_locations( $user_type = '' ) {
	global $hrb_options;

    $params = array(
		'number' => 0,
		'hrb_ignore_filters' => true,
    );

	if ( $user_type ) {
		$params['user_roles'] = $user_type;
	}

    if ( ! $users = hrb_get_users( $params ) ) {
        return array();
    }

    $locations = array();

    foreach( $users->results as $user ) {

		$country = $user->hrb_location_country;

		if ( 'location' == $hrb_options->user_refine_search ) {

			// try to get the most relevant location
			$location = $user->hrb_location_locality;
			if ( ! $location ) {
				$location = $user->hrb_location_administrative_area_level_2;
				if ( ! $location ) {
					$location = $user->hrb_location_neighborhood;
					if ( ! $location ) {
						$location = $user->hrb_location_administrative_area_level_1;
					}
				}
			}

		} elseif ( 'postal_code' == $hrb_options->user_refine_search ) {
			$location = $user->hrb_location_postal_code;
		} else {
			$location = $country;
		}

		if ( ! $location ) {
			$location = $user->hrb_location;
		}

        if ( $location ) {

			if ( empty( $locations[ $location ] ) ) {
				$locations[ $location ] = sprintf( "%s%s", ( empty( $hrb_options->user_geo_country ) && $location != $country ? $country . ' :: ' : '' ), $location );
			}

        }
    }

	asort( $locations );

    return $locations;
}

/**
 * Retrieves the saved filters for a given user.
 */
function hrb_get_user_saved_filters( $user_id = 0 ) {
	$user_id = $user_id ? $user_id : get_current_user_id();

	$option = hrb_get_prefixed_user_option('_saved_filters');

	return get_user_meta( $user_id, $option, true );
}

### Helper Functions

/**
 * Enriches a p2p user data with all his related p2p meta.
 */
function hrb_p2p_get_user_with_meta( $data ) {

	if ( ! is_object( $data ) ) {
		$p2p = p2p_get_connection( (int) $data );
		$data = get_user_by( 'id', $p2p->p2p_to );
		$p2p_id = $p2p->p2p_id;
	} else {
		$p2p_id = $data->p2p_id;
	}

	// defaults

	$data->p2p_id = $p2p_id;
	$data->proposal_id = 0;
	$data->status_notes = '';

	$meta = p2p_get_meta( $p2p_id );

	foreach( (array) $meta as $field => $value ) {
		$data->$field = $value[0];
	}

	return $data;
}

/**
 * Retrieves a customized display name for the current user.
 */
function hrb_get_curr_user_desc( $user_id ) {

	if ( $user_id == get_current_user_id() ) {
		$name = __( 'You', APP_TD );
	} else {
		$name = get_userdata( $user_id )->display_name;
	}
	return $name;
}

/**
 * Retrieve the user option prefix considering multi-site.
 */
function hrb_get_prefixed_user_option( $option ) {
	global $wpdb;

	$prefix = $wpdb->get_blog_prefix();

	return $prefix . $option;
}
