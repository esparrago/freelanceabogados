<?php
/**
 * Generic helper functions used throughout the theme.
 */

add_filter( 'get_meta_sql', '_hrb_compare_like_in_sql', 10 );
add_filter( 'comment_post_redirect', '_hrb_redirect_after_comment', 10, 2 );


/**
 * Overrides the default label input wrapping on custom fields and prepends the label to the input, instead
 */
class HRB_Field extends scbForms {

	static function label_input_from_meta( $field, $post_id ) {

		if ( in_array( $field['type'], array('checkbox', 'radio') ) && ! empty( $field['desc'] ) ) {
			$field['desc'] = html( 'label', array( 'for' => $field['name'] ), $field['desc'] );
		}

		$field_html = scbForms::input_from_meta( $field, $post_id );

		// hack to allow checkboxes and radio buttons to be set as required
		if ( in_array( $field['type'], array('checkbox', 'radio') ) && isset($field['extra']['class']) ) {
			$field_html = str_replace( '<input', '<input class="' . $field['extra']['class'] . '"', $field_html );
		} else {
			$field_html = str_replace( '</label>', '', $field_html );
			$field_html = str_replace( array( '<input', '<select' ) , array( '</label><input', '</label><select' ), $field_html );
		}

		return $field_html;
	}
}


### Hooks Callbacks

/**
 * Affect meta sql queries to allow pattern matching of project meta given a list of strings
 *
 * Works/triggered in WP_Query by looking for a previously set query var
 * Works/triggered in WP_User_Query by looking for a marker in the WHERE clause
 *
 * @since 1.1
 *
 * @param string $user_query The user query
 */
function _hrb_compare_like_in_sql( $clauses ) {

	// do an array pattern matching comparison if the query var '_hrb_like_in_strings' is set
	if ( get_query_var('hrb_like_in_strings') ) {
		$strings = get_query_var('hrb_like_in_strings');

		// sanitize meta values
		$strings = array_map( 'like_escape', (array) $strings );
		$strings = esc_sql( $strings );

		$clauses['where'] = str_replace( "= '__LIKE_IN_PLACEHOLDER__'", sprintf( "REGEXP '%s'", implode( '|', $strings ) ), $clauses['where'] );
	}
	return $clauses;
}

/**
 * Display message to user when a comment is submitted. Extracts the anchor name part from the URL.
 */
function _hrb_redirect_after_comment( $location, $comment ){

	if ( ! $comment->comment_type != 'comment' || is_admin() ) {
		return $location;
	}

	$parts = explode( '#', $location );

	appthemes_add_notice( 'comment-submit', __( 'Your message submited. Thank you!', APP_TD ), 'success' );

	return $parts[0];
}

### Other Functions

/**
 * Retrieves the given post ID, or the current loop post ID.
 */
function get_the_hrb_loop_id( $post_id = 0 ) {
	return $post_id ? $post_id : get_the_ID();
}

/**
 * Retrieves a single value or list of values from a list of given verbiages key/value pairs.
 */
function hrb_get_verbiage_values( $verbiages, $key = '' ) {

	if ( $key && ! isset( $verbiages[ $key ] ) ) {
		return __( 'Unknown', APP_TD );
	}

	if ( $key && isset( $verbiages[ $key ] ) ) {
		return $verbiages[ $key ];
	}
	return $verbiages;
}

/**
 * Calculates and retrieves a formatted expiration date given a start date and a duration.
 */
function hrb_get_formatted_expire_date( $start_date, $duration, $format = 'm/d/Y' ) {
	$expiration_date = date( $format, strtotime( $start_date . ' + ' . $duration . 'days' ) );

	return $expiration_date;
}

/**
 * Used in forms to hide fields.
 */
function hrb_hidden_input_fields( $input_data ) {
	foreach( (array) $input_data as $name => $value ) {
		_appthemes_form_serialize( $value, $name );
	}
}

/**
 * Retrieves a specific post data field value with an optional default value.
 */
function _hrb_posted_field_value( $field, $default = '' ) {
	return isset( $_POST[$field] ) ? stripslashes( $_POST[$field] ) : $default;
}

/**
 * Retrieves posted terms for a specific taxonomy.
 */
function _hrb_posted_terms( $taxonomy ) {
	$terms = false;

	$posted_tax = $taxonomy;
	if ( in_array( $taxonomy, array( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_SKILLS ) ) ) {
		$posted_tax = '_' . $taxonomy;
	}

	if ( ! empty( $_REQUEST[ $posted_tax ] ) ) {
		foreach( (array) $_REQUEST[ $posted_tax ] as $term ) {

			$term = sanitize_text_field( $term );
			if ( empty( $term ) ) {
				continue;
			}

			if ( ! is_numeric( $term ) ) {
				$field = 'name';
				$all_terms = explode( ',', $term );
			} else {
				$field = 'id';
				$all_terms = $term;
			}

			foreach( (array) $all_terms as $tax_term ) {
				$term_obj = get_term_by( $field, $tax_term, $taxonomy );
				if ( ! is_wp_error( $term_obj ) ) {
					$terms[] = $term_obj->term_id;
				}
			}

		}
	}
	return $terms;
}

/**
 * Assigns a list of taxonomy terms to a post.
 */
function hrb_set_post_terms( $post_id, $terms, $taxonomy ) {

	if ( empty( $terms ) ) {
		return;
	}

	$terms = array_map( 'intval', $terms );

	wp_set_object_terms( $post_id, $terms, $taxonomy );
}

/**
 * Updates a post 'guid'.
 */
function hrb_update_post_guid( $post_id ) {
	global $wpdb;
	$wpdb->update( $wpdb->posts, array( 'guid' => get_permalink( $post_id ) ), array( 'ID' => $post_id ) );
}

/**
 * Generates a unique post name given a set of parameters.
 */
function hrb_make_unique( $title, $ID, $post_type = '', $post_status = '', $post_parent = 0 ) {
	$post_name = sanitize_title( $title );

	$post_name = wp_unique_post_slug( $post_name, $ID, $post_status, $post_type, $post_parent );

	return $post_name;
}

/**
 * Mirrors WP 'get_query_var()' for custom query vars used in the theme.
 */
function get_hrb_query_var( $var, $fallback = true ) {
	if ( get_query_var( 'hrb_' . $var ) || ! $fallback ) {
		return get_query_var( 'hrb_' . $var );
	}
	return get_query_var( $var );
}

/**
 * Outputs a query var value given it's key name.
 */
function hrb_output_search_query_var( $qv ) {
	echo hrb_get_search_query_var( $qv );
}

/**
 * Retrieves a query var value given it's key name.
 */
function hrb_get_search_query_var( $qv ) {
	return stripslashes( esc_attr( trim( get_query_var( $qv ) ) ) );
}

/**
 * Given a terms object list and a list of term ids check if the object parent terms match the term id's.
 */
function hrb_parent_terms_diff( $terms1, $terms2_ids ) {

	$parents = array();

	foreach( $terms1 as $term ) {
		if ( ! $term->parent ) {
			$parents[] = $term->term_id;
		}
	}

	$diff_cats = array_diff( $parents, wp_list_pluck( $terms1, 'term_id' ) );

	return $diff_cats;
}


/**
 * Checks if a particular user has a role.
 * Returns true if a match was found.
 *
 * @param string $role Role name.
 * @param int $user_id (Optional) The ID of a user. Defaults to the current user.
 * @return bool
 */
function hrb_check_user_role( $role, $user_id = null ) {

    if ( is_numeric( $user_id ) ) {
		$user = get_userdata( $user_id );
	} else {
        $user = wp_get_current_user();
	}

    if ( empty( $user ) ) {
		return false;
	}
    return in_array( $role, (array) $user->roles );
}

### Geolocation

/**
 * Retrieves geocomplete options for the geocomplete JS plugin.
 *
 * @since 1.1
 *
 * @uses apply_filters() Calls 'hrb_geocomplete_options'
 *
 * @param string $type The target destination for the options: 'user' or post type
 * @return array An associative array with the options
 */
function hrb_get_geocomplete_options( $type = '' ) {
	global $hrb_options;

	if ( ! $type ) {
		$type = 'project';
	}

	$options = array();

	if ( $geo_types = $hrb_options->{"{$type}_geo_type"} ) {

		// Google Places Types - https://developers.google.com/places/documentation/supported_types?csw=1#table3
		switch( $geo_types ) {
			case 'cities':
			case 'regions':
				 $geo_types = sprintf( "(%s)", $geo_types );
				break;
		}
		$options['types'] = (array) $geo_types;

	}

	// Google Places Country Restrictions - https://developers.google.com/maps/documentation/javascript/reference#GeocoderComponentRestrictions
	if ( $geo_country = $hrb_options->{"{$type}_geo_country"} ) {
		$options['componentRestrictions']['country'] = (array) $geo_country;
	}

	return apply_filters( 'hrb_geocomplete_options', $options, $type );
}

/**
 * Retrieves the list of location attributes to use in with the geocomplete JS plugin.
 *
 * Note: each field is always  prefixed with 'location_'
 *
 * @since 1.1
 *
 * @uses apply_filters Calls 'hrb_geocomplete_location_atts'
 *
 * @return array A list with the the location attributes
 */
function hrb_get_geocomplete_attributes() {

	$attributes = array(
		'lat',
		'lng',
		'country',
		'country_short',
		'postal_code',
		'street_address',
		'route',
		'political',
		'administrative_area_level_1',
		'administrative_area_level_2',
		'administrative_area_level_3',
		'locality',
		'sublocality',
		'neighborhood',
		'viewport',
		'location',
		'formatted_address',
		'location_type',
		'bounds'
	);

	return apply_filters( 'hrb_geocomplete_location_atts', $attributes );
}

/**
 * Retrieves the list of the main location attributes used for location matching
 *
 * Note: each field is always prefixed with 'location_'
 *
 * @since 1.1
 *
 * @uses apply_filters Calls 'hrb_geocomplete_master_location_atts'
 *
 * @return array A list with the the location attributes
 */
function hrb_get_geocomplete_master_attributes() {

	$attributes = array(
		'formatted_address',
		'locality',
		'postal_code',
		'administrative_area_level_2',
		'administrative_area_level_1',
	);

	return apply_filters( 'hrb_geocomplete_master_location_atts', $attributes );
}

 ### DEBUG

//add_action( 'init', '_hr_debug', 9999 ); // un-comment to enable debug mode

/**
 * Enables debug mode.
 * Equivalent to seting WP_DEBUG to true in wp-config.php.
 */
function _hr_debug() {

	if ( ! isset( $_GET['debug'] ) || $_GET['debug'] != 1 ) {
		return;
	}

	error_reporting( E_ALL );

	ini_set( 'display_errors', 1 );
}
