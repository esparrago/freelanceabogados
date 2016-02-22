<?php

add_action( 'admin_enqueue_scripts', 'hrb_add_admin_scripts_user', 10 );

add_filter( 'manage_users_columns', 'hrb_manage_users_column' );
add_action( 'manage_users_custom_column', 'hrb_users_add_column_data', 10, 3 );
add_filter( 'manage_users_sortable_columns', 'hrb_users_columns_sort' );

add_action( 'pre_user_query', 'rating_column_orderby' );
add_action( 'pre_user_query', 'rate_column_orderby' );

/**
 * Function for updating the 'skills' taxonomy count.  What this does is update the count of a specific term
 * by the number of users that have been given the term.  We're not doing any checks for users specifically here.
 * We're just updating the count with no specifics for simplicity.
 *
 * See the _update_post_term_count() function in WordPress for more info.
 *
 * @param array $terms List of Term taxonomy IDs
 * @param object $taxonomy Current taxonomy object of terms
 */
function hrb_update_skills_terms_count( $terms, $taxonomy ) {
	global $wpdb;

	foreach ( (array) $terms as $term ) {

		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id = %d", $term ) );

		do_action( 'edit_term_taxonomy', $term, $taxonomy );

		$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );

		do_action( 'edited_term_taxonomy', $term, $taxonomy );
	}
}

function hrb_add_admin_scripts_user( $hook ) {

	// selective load
	$pages = array ( 'user-edit.php', 'profile.php' );
 	if ( ! in_array( $hook, $pages ) ) {
		return;
    }

	hrb_register_enqueue_scripts( 'hrb-user-edit', $admin = true );

	hrb_maybe_enqueue_geo();
}

/**
 * Adds extra columns to the users list
 */
function hrb_manage_users_column( $columns ) {
    $columns['posts'] = __( 'Projects', APP_TD );
    $columns['credits'] = __( 'Credits', APP_TD );
    $columns['rate'] = __( 'Hourly Rate', APP_TD );
	$columns['rating'] = __( 'Rating (% Success)', APP_TD );
	return $columns;
}

/**
 * Retrieve data for custom user columns
 */
function hrb_users_add_column_data( $value, $column_index, $user_id ) {
	switch ( $column_index ) {
        case 'credits':
            return hrb_get_user_credits( $user_id );
            break;
        case 'rating' :
            $rating = appthemes_get_user_avg_rating( $user_id );

            ob_start();
            the_hrb_user_success_rate( $user_id );
            $success_rate = ob_get_clean();

            if ( $rating ) {
                return sprintf( '%d/5 (%s)', $rating, $success_rate );
            } else {
                return __( 'Not Rated', APP_TD );
            }
            break;
        case 'rate' :
            $rate = get_the_hrb_user_rate( $user_id );
            return $rate;
            break;
	}

	return $value;
}

/**
 * Sortable user columns
 */
function hrb_users_columns_sort( $columns ) {
    $columns['rating'] = 'rating';
	$columns['rate'] = 'rate';
	return $columns;
}

/**
 * Allow sorting the rating column
 */
function rating_column_orderby( $vars ) {
	if ( isset( $vars->query_vars['orderby'] ) && 'rating' == $vars->query_vars['orderby'] ) {
        $vars->query_vars = array_merge( $vars->query_vars, array(
            'meta_key' => APP_REVIEWS_P_AVG_KEY,
            'orderby' => 'meta_value_num'
        ) );
    }
}

/**
 * Allow sorting the hourly rate column
 */
function rate_column_orderby( $vars ) {
	if ( isset( $vars->query_vars['orderby'] ) && 'rate' == $vars->query_vars['orderby'] ) {
		$vars->query_vars = array_merge( $vars->query_vars, array(
			'meta_key'	=> 'hrb_rate',
			'orderby'	=> 'meta_value_num'
		) );
	}
}
