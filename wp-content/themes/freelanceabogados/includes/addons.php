<?php
/**
 * Addon related functions.
 */

add_action( 'init', '_hrb_schedule_addon_prune' );
add_action( 'hrb_prune_expired_addons', 'hrb_prune_expired_addons' );

add_filter( 'posts_clauses', '_hrb_expired_addon_sql', 10, 2 );


### Hooks Callbacks

/**
 * Schedules a cron event to prune expired addons.
 */
function _hrb_schedule_addon_prune() {

	if ( ! wp_next_scheduled( 'hrb_prune_expired_addons' ) ) {
		wp_schedule_event( time(), 'hourly', 'hrb_prune_expired_addons' );
	}

}

/**
 * Prunes expired addons.
 */
function hrb_prune_expired_addons() {

	$type = HRB_PROJECTS_PTYPE;

	// prune addons related with 'project' custom types
	foreach( hrb_get_addons( $type ) as $addon ) {

		$expired_posts = new WP_Query( array(
			'post_type' => $type,
			'expired_addon' => $addon,
			'nopaging' => true,
		) );

		foreach ( $expired_posts->posts as $post ) {
			hrb_remove_addon( $post->ID, $addon );
		}

	}

}

/**
 * Looks for a specific query var to change the expired addons wp_query 'join' and 'where' clauses.
 */
function _hrb_expired_addon_sql( $clauses, $wp_query ) {

	if ( ! $addon = $wp_query->get('expired_addon') ) {
		return $clauses;
	}

	$clauses['join'] .= _hrb_get_expired_sql_join();
	$clauses['where'] = _hrb_get_expired_sql_where( $addon );

	return $clauses;
}

/**
 * Customizes an sql 'join' with addons post meta.
 *
 * Called in '_hrb_expired_addon_sql()'
 *
 */
function _hrb_get_expired_sql_join(){
	global $wpdb;

	$output = '';
	$output .= " INNER JOIN " . $wpdb->postmeta ." AS duration ON (" . $wpdb->posts .".ID = duration.post_id)";
	$output .= " INNER JOIN " . $wpdb->postmeta ." AS start ON (" . $wpdb->posts .".ID = start.post_id)";

	return $output;

}

/**
 * Customizes an sql 'where' to retrieve expired addons.
 *
 * Called in '_hrb_expired_addon_sql()'
 *
 */
function _hrb_get_expired_sql_where( $addon ){

	$where = 'AND (';
		$where .= 'duration.meta_key = \'' . $addon . '_duration\' AND ';
		$where .= 'start.meta_key = \'' . $addon . '_start_date\'';
		$where .= ' AND ';
		$where .= ' DATE_ADD( start.meta_value, INTERVAL duration.meta_value DAY ) < \'' . current_time( 'mysql' ) . '\'';
		$where .= ' AND duration.meta_value > 0 ';
	$where .= ") ";

	return $where;
}

/**
 * Retrieves all addons related with projects.
 *
 * @uses apply_filters() Calls 'hrb_project_addons'
 *
 */
function hrb_project_addons() {
    global $hrb_options;

    $addons = array(
        array(
            'type' => HRB_ITEM_FEATURED_HOME,
            'title' => __( 'Featured on Homepage', APP_TD ),
            'meta' => array(
                'price' => $hrb_options->addons[HRB_ITEM_FEATURED_HOME]['price'],
				'class_name' => 'featured-home',
				'icon' => 'icon i-featured',
                'label' => __( 'Featured', APP_TD ), // label for front-end display
				'label_2' => __( 'Featured Home', APP_TD ) // label for front-end display
            )
        ),
        array(
            'type' => HRB_ITEM_FEATURED_CAT,
            'title' => __( 'Featured on Category', APP_TD ),
            'meta' => array(
                'price' => $hrb_options->addons[HRB_ITEM_FEATURED_CAT]['price'],
				'class_name' => 'featured-cat',
				'icon' => 'icon i-featured',
                'label' => __( 'Featured', APP_TD ), // label for front-end display
				'label_2' => __( 'Featured Category', APP_TD ) // label for front-end display
            )
        ),
        array(
            'type' => HRB_ITEM_URGENT,
            'title' => __( 'Mark as Urgent', APP_TD ),
            'meta' => array(
                'price' => $hrb_options->addons[HRB_ITEM_URGENT]['price'],
				'class_name' => 'urgent fi',
				'icon' => 'icon fi-alert',
                'label' => __( 'Urgent', APP_TD ), // label for front-end display
				'label_2' => __( 'Urgent', APP_TD ) // label for front-end display
            )
        ),
    );

    return apply_filters( 'hrb_project_addons', $addons );
}

/**
 * Retrieves all addons related with proposals.
 *
 * @uses apply_filters() Calls 'hrb_proposal_addons'
 *
 */
function hrb_proposal_addons() {
    $addons = array();

    return apply_filters( 'hrb_proposal_addons', $addons );
}

/**
 * Retrieve all addons or a specific type.
 *
 * @param string $type The list of addons to rettrieve: project | proposal
 * @return array List of addons for the specificied type or all addons
 */
function hrb_get_addons( $type = '' ) {

    $all_addons = array(
        HRB_PROJECTS_PTYPE => wp_list_pluck( hrb_project_addons(), 'type' ),
        HRB_PROPOSAL_CTYPE => wp_list_pluck( hrb_proposal_addons(), 'type' ),
    );

    if ( ! $type ) {
		$addons = $all_addons;
	} else {
		$addons = $all_addons[ $type ];
	}

    return $addons;
}

/**
 * Assigns an addon with a start date and duration to a post by adding the related meta keys/values to the post meta.
 */
function hrb_add_addon( $post_id, $addon, $duration ) {
    update_post_meta( $post_id, $addon, true );
    update_post_meta( $post_id, $addon . '_start_date', current_time( 'mysql' ) );
    update_post_meta( $post_id, $addon . '_duration', $duration );
}

/**
 * Removes an addon with a start date and duration to a post by removing the related meta keys/values from the post meta.
 */
function hrb_remove_addon( $post_id, $addon ){
	update_post_meta( $post_id, $addon, '' );
	update_post_meta( $post_id, $addon .'_start_date', '' );
	update_post_meta( $post_id, $addon .'_duration', '' );
}

/**
 * Checks if a listing has a specific addon assigned.
 *
 * @param int $post_id The post ID
 * @param string|array $search_addons The addons list to check
 * @return boolean Whether the addon is assigned or not
 */
function hrb_project_has_addon( $post_id, $search_addons ) {

    $addons = get_the_hrb_project_addons( $post_id );

    foreach( (array) $search_addons as $key ) {
        if ( isset( $addons[ $key ] ) ) {
            return true;
		}
    }
    return false;
}

/**
 * Retrieves all available addons with default values.
 *
 * @uses apply_filters() Calls 'hrb_project_addon_defaults'
 */
function _hrb_project_addon_defaults( $post_type = '' ) {

    foreach( (array) hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {
        $defaults[ $addon ] = 0;
    }
    return apply_filters( 'hrb_project_addon_defaults', $defaults );
}

/**
 * Assigns default addons to a listing.
 */
function hrb_set_project_addons( $post ) {

    if ( empty( $post ) ) {
        return false;
	}

	if ( isset( $post->ID ) ) {
        $post_id = $post->ID;
	} elseif( is_numeric( $post ) ) {
		$post_id = $post;
	} else {
		return false;
	}

	$post_type = get_post_type( $post_id );

    foreach( _hrb_project_addon_defaults( $post_type ) as $k => $v ) {
		add_post_meta( $post_id, $k, $v, true );
    }
    return $post;
}

/**
 * Retrieves the key/value pairs for a specific addon.
 */
function hrb_get_addon_attributes( $addon ) {
    global $hrb_options;

    return array(
        'title' => APP_Item_Registry::get_title( $addon ),
        'price' => appthemes_get_price( APP_Item_Registry::get_meta( $addon, 'price' ) ),
        'duration' => $hrb_options->addons[$addon]['duration']
    );
}

/**
 * Retrieves TRUE if an addon is disabled, FALSE otherwise.
 */
function hrb_addon_disabled( $addon ) {
    global $hrb_options;
    return empty( $hrb_options->addons[ $addon ]['enabled'] );
}

/**
 * Retrieves TRUE if there are addons avaialble for selection (included or optional), FALSE otherwise.
 */
function hrb_addons_available( $plan, $type = '' ) {

	$addons = array();

    foreach( hrb_get_addons( $type ) as $addon ) {
        if ( ! empty( $plan[ $addon ] ) || ! hrb_addon_disabled( $addon ) ) {
            $addons[] = $addon;
		}
    }
    return ! empty( $addons );
}

/**
 * Retrieves an addon expiration date for a listing.
 * // @todo might not be necessary
 */
function hrb_get_addon_expiration_date( $addon, $post_id = 0 ) {
    $post_id = !empty( $post_id ) ? $post_id : get_the_ID();

    $start_date = get_post_meta( $post_id, $addon . '_start_date', true );

    $duration = get_post_meta( $post_id, $addon . '_duration', true );

    if ( ! $start_date || ! $duration ) {
        return __( 'Never', APP_TD );
    }

    return hrb_get_formatted_expire_date( $start_date, $duration );
}

/**
 * Checks if an item is an addon an retrieves the boolean comparison. Tipically used within a Order.
 */
function hrb_item_is_addon_or_related( $item_type, $post_type = '' ) {

    foreach( hrb_get_addons( $post_type ) as $addons ) {
        foreach( (array) $addons as $addon ) {
            if ( $item_type == $addon ) {
                return true;
			}
        }
    }

	if ( '_regional-tax' == $item_type || ( defined('APPTHEMES_COUPON_PTYPE') && APPTHEMES_COUPON_PTYPE == $item_type ) ) {
		return true;
	}

    return false;
}

