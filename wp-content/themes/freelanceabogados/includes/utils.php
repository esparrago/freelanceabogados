<?php
/**
 * Utility functions and hook callbacks. These can go away at any time. Don't rely on them.
 */

// add a very low priority action to make sure any extra settings have been added to the permalinks global
add_action( 'admin_init', '_hrb_enable_permalink_settings', 999999 );

add_filter( 'excerpt_more', '_hrb_excerpt_more' );
add_filter( 'excerpt_length', '_hrb_excerpt_length' );

add_filter( 'the_excerpt', 'strip_tags' );

add_filter( 'breadcrumb_trail_items', '_hrb_breadcrumb', 10, 2 );
add_filter( 'comments_clauses', '_hrb_comments_sort_optional_cols', 10, 2 );


### Hooks Callbacks

// Temporary workaround for wordpress bug #9296 http://core.trac.wordpress.org/ticket/9296
// Although there is a hook in the options-permalink.php to insert custom settings,
// it does not actually save any custom setting which is added to that page.
function _hrb_enable_permalink_settings() {
	global $new_whitelist_options;

	// save hook for permalinks page
	if ( isset( $_POST['permalink_structure'] ) || isset( $_POST['category_base'] ) ) {
		check_admin_referer( 'update-permalink' );

		$option_page = 'permalink';

		$capability = 'manage_options';
		$capability = apply_filters( "option_page_capability_{$option_page}", $capability );

		if ( !current_user_can( $capability ) ) {
			wp_die( __( 'Cheatin&#8217; uh?', APP_TD ) );
		}

		// get extra permalink options
		$options = $new_whitelist_options[$option_page];

		if ( $options ) {
			foreach( $options as $option ) {
				$option = trim( $option );
				$value = null;
				if ( isset( $_POST[$option] ) ) {
					$value = $_POST[$option];
				}
				if ( !is_array( $value ) ) {
					$value = trim( $value );
				}
				$value = stripslashes_deep( $value );

				// get the old values to merge
				$db_option = get_option( $option );

				if ( is_array( $db_option ) ) {
					update_option( $option, array_merge( $db_option, $value ) );
				} else {
					update_option( $option, $value );
				}
			}
		}

		/**
		 *  Handle settings errors
		 */
		set_transient( 'settings_errors', get_settings_errors(), 30 );
	}
}


function _hrb_excerpt_more() {
	return '&hellip;';
}

function _hrb_excerpt_length() {
	return 55;
}

function _hrb_breadcrumb( $trail, $args ) {
	if ( is_hrb_users_archive() ) {
		$trail['trail_end'] = html_link( get_the_hrb_users_base_url(), __( 'Freelancers', APP_TD ) );
	}
	return $trail;
}

/**
 * Writes a message to /wp-content/debug.log if debugging is turned on.
 *
 * @param mixed $message What to output to the log file
 *
 * @return void
 */
function print_to_log( $message ) {

	$current_filter = 'current_filter = "' . current_filter() . "'";

    if ( true === WP_DEBUG ) {
        if ( is_array( $message ) || is_object( $message ) ) {
            $message = print_r( $message, true );
        }
		error_log( $message );
		error_log( $current_filter );
    }
}


/**
 * Query modifier to allow sorting listings by a custom optional meta key.
 *
 * @since 1.3.1
 *
 * @todo: To be removed after WP provides similar functionality.
 */
function _hrb_comments_sort_optional_cols( $clauses, $wp_comment_query ) {

	// check for the query var that modifies the query
	if ( ! empty( $wp_comment_query->query_vars['app_optional_orderby'] ) ) {

		$ordersby = $wp_comment_query->query_vars['app_optional_orderby'];
		if ( is_array( $ordersby ) ) {

			$orderby_sql = '';

			foreach( $ordersby as $orderby => $order ) {

				// check for an associatve or sequential array
				if ( is_int( $orderby ) ) {
					$orderby = $order;
				}

				if ( 'ASC' != strtoupper( $order ) && 'DESC' != strtoupper( $order ) ) {
					$order = $clauses['order'];
				}
				if ( strpos( $orderby, 'meta_value' ) !== FALSE ) {
					if ( 'meta_value_num' == $orderby ) {
						$orderby = 'meta_value+0';
					} else {
						$orderby = 'meta_value';
					}
					$orderby = "coalesce( IF( mt1.{$orderby} = 0, NULL, mt1.{$orderby} ) )";
				}
				$orderby_sql .= ( $orderby_sql ? ', ' : '' ) . "$orderby $order";
			}

		}

		$clauses['orderby'] = $orderby_sql;
		$clauses['order'] = '';
	}
	return $clauses;
}