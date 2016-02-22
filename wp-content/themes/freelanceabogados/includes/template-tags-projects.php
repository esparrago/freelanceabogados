<?php
/**
 * Mirrors WordPress template tag functions (the_post(), the_content(), etc), used in the Loop .
 *
 * Altough some function might not be used in a WP Loop, they intend to work in the same way as to be self explanatory and retrieve data intuitively.
 *
 * Contains template tag functions for: post, project (post type), workspace (post type)
 *
 */

// @todo add $atts() param to some function to allow customizing attributes
// @todo add $atts() param before the $before and $after params

add_action( 'hrb_output_project_actions', 'hrb_output_project_actions' );
add_filter( 'term_link', '_hrb_no_permalinks_term_link', 10, 3 );


### Hooks Callbacks

/**
 * Outputs the availavle template tag functions for a project.
 */
function hrb_output_project_actions( $post_id ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	// edit
	the_hrb_project_edit_link( $post_id );

	// relist/reopen
	the_hrb_project_relist_link( $post_id );
}

function _hrb_no_permalinks_term_link(  $link, $term, $taxonomy ) {
	global $wp_rewrite;

	if ( $wp_rewrite->using_permalinks() ) {
		return $link;
	}
	return add_query_arg( array( $taxonomy => $term->slug ), get_post_type_archive_link( HRB_PROJECTS_PTYPE ) );
}

### Actions / Permalinks

/**
 * Outputs the available actions for a listing (edit, delete, favorite, etc).
 *
 * @uses do_action() Calls 'hrb_output_project_actions'
 *
 */
function the_hrb_project_actions( $post_id = 0 ) {
	do_action( 'hrb_output_project_actions', $post_id );
}

/**
 * Retrieves the URL to call a dynamic action on a project.
 */
function get_the_hrb_project_action_url( $post_id = 0, $action ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$args = array(
		'action' => 'mp',
		'p_action' => $action,
		'project_id' => $post_id
	);
	return add_query_arg( $args, get_permalink( hrb_get_dashboard_url_for('projects') ) );
}

/**
 * Retrieves the permalink for creating a project. If a post ID is passed, resumes the project creation.
 * Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_project_create_url( $post_id = 0, $query_args = array() ) {
	global $wp_rewrite;

	if ( $wp_rewrite->using_permalinks() ) {
		return user_trailingslashit( get_permalink( HRB_Project_Create::get_id() ) ) . ( $post_id ? $post_id : '' );
	}

	$args = array();

	if ( $post_id ) {
		$args = array( 'project_id' => $post_id );
	}
	$args = wp_parse_args( $query_args, $args );

	return add_query_arg( $args, get_permalink( HRB_Project_Create::get_id() ) );
}

/**
 * Retrieves the URL for editing a project. Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_project_edit_url( $post_id = 0 ) {
	global $wp_rewrite, $hrb_options;

    $post_id = get_the_hrb_loop_id( $post_id );

	if ( $wp_rewrite->using_permalinks() ) {
		$project_permalink = $hrb_options->project_permalink;
		$permalink = $hrb_options->edit_project_permalink;

		return home_url( user_trailingslashit( "$project_permalink/$permalink/$post_id" ) );
	}

	return add_query_arg( array( 'project_edit' => $post_id, 'project_id' => $post_id ) , home_url() );
}

/**
 * Outputs the link for editing a project.
 */
function the_hrb_project_edit_link( $post_id = 0, $text = '', $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Edit Project', APP_TD );
	}

	echo html( 'a', array(
		'class' => 'button secondary expand',
		'href' => get_the_hrb_project_edit_url( $post_id ),
	), $before . $text . $after );
}

/**
 * Retrieves the URL for relisting a project. Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_project_relist_url( $post_id = 0 ) {
	global $wp_rewrite, $hrb_options;

    $post_id = get_the_hrb_loop_id( $post_id );

	if ( $wp_rewrite->using_permalinks() ) {
		$project_permalink = $hrb_options->project_permalink;
		$permalink = $hrb_options->renew_project_permalink;

		return home_url( user_trailingslashit( "$project_permalink/$permalink/$post_id" ) );
	}
	return add_query_arg( array( 'project_relist' => $post_id, 'project_id' => $post_id ) , home_url() );
}

/**
 * Outputs the link for relisting/reopening a project.
 */
function the_hrb_project_relist_link( $post_id = 0, $text = '', $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$can_relist = current_user_can( 'relist_post', $post_id );
	$can_reopen = current_user_can( 'reopen_post', $post_id );

	if ( ! $can_relist && ! $can_reopen ) {
		return;
	}

	if ( empty( $text ) ) {

		if ( $can_relist ) {
			$text = __( 'Relist Project', APP_TD );
			$link = get_the_hrb_project_relist_url( $post_id );
			$operation = __( 'Relist', APP_TD );
		} else {
			$text = __( 'Reopen Project', APP_TD );
			$link = get_the_hrb_project_action_url( $post_id, 'reopen' );
			$operation = __( 'Reopen', APP_TD );
		}

	}

	echo html( 'a', array(
		'class' => 'button secondary expand',
		'href' => $link,
		'onclick' => 'return confirm("' . sprintf( __( 'Are you sure you want to %s this project?', APP_TD ),  $operation ) . '");',
	), $before . $text . $after );
}

/**
 * Retrieves the permalink for applying to a project (post proposal). Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_apply_to_url( $post_id = 0 ) {
	global $wp_rewrite;

	$post_id = get_the_hrb_loop_id( $post_id );

	if ( $wp_rewrite->using_permalinks() ) {
		return get_permalink( HRB_Proposal_Create::get_id() ) . $post_id;
	}

	return add_query_arg( array( 'project_id' => $post_id ), get_permalink( HRB_Proposal_Create::get_id() ) );
}

/**
 * Outputs the formatted link to apply to a project or edit an existing proposal.
 */
function the_hrb_create_edit_proposal_link( $post_id = 0, $text = '', $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$proposal = appthemes_get_user_post_bid( get_current_user_id(), $post_id );

	if ( $proposal ) {
		the_hrb_proposal_edit_link( $proposal->get_id(), $text, $before, $after );
		return;
	}

	if ( ! current_user_can('edit_bids') || ! current_user_can( 'add_bid', $post_id ) ) {
		return;
	}

  	$url = get_the_hrb_apply_to_url( $post_id );

    if ( empty( $url ) ) {
       return;
	}

	if ( empty( $text ) ) {
		$text = __( 'Apply to Project', APP_TD );
	}

	echo html( 'a', array(
		'class' => 'button secondary expand',
		'href' => $url,
	), $before . $text . $after );

}

/**
 * Outputs the formatted proposals stats count for a project.
 */
function the_hrb_project_proposals_count( $post_id = 0, $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$proposals = appthemes_get_post_total_bids( $post_id );

	echo $before . sprintf( _n( '1 Proposal', '%d Proposals', $proposals, APP_TD ), $proposals ) . $after;
}

/**
 * Outputs the formatted proposals stats count link for a project.
 */
function the_hrb_project_proposals_count_link( $post_id = 0, $before = '', $after = '', $atts = array() ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$post = get_post( $post_id );

	$url = get_the_hrb_project_proposals_url( $post_id );

	$total = appthemes_get_post_total_bids( $post_id );

	$text = sprintf( '%d Proposals', $total, APP_TD );

	if ( ! $total ) {

		$text = html( 'span', array(
			'data-tooltip' => '',
			'title' => esc_attr( __( 'Proposals', APP_TD ) )
		), html( 'i', array( 'class' => 'icon i-proposals-count' ), '&nbsp;' ) .$text );

	} else {

	   $text = html( 'i', array( 'class' => 'icon i-proposals-count' ), '&nbsp;' ) . $text;

	}

	if ( ! $total ) {
		echo $text;
		return;
	}

	$defaults = array(
		'href' => $url,
		'class' => 'button small',
	);
	$atts = wp_parse_args( $atts, $defaults );

	if ( $post->post_author == get_current_user_id() ) {
		$link = html( 'a', $atts, $text );
	} else {
		$link = $text;
	}

	echo $before . $link . $after;
}

/**
 * Outputs the formatted link to view the list of proposals for a project.
 */
function the_hrb_proposals_view_link( $post_id = 0, $text = '', $before = '', $after = '', $min = 1, $atts = array() ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$post = get_post( $post_id );

	if ( $post->post_author != get_current_user_id() ) {
		return;
	}

	if ( ! appthemes_get_post_total_bids( $post_id ) >= $min ) {
		return;
	}

	if ( empty( $text ) ) {
		$text = __( 'View All Proposals', APP_TD );
	}

	$default = array(
		'class' => 'button secondary',
		'href' => get_the_hrb_project_proposals_url( $post_id ),
	);
	$atts = wp_parse_args( $atts, $default );

	echo html( 'a', $atts, $before . $text . $after );
}

/**
 * Retrieves the current URL with additional query variables.
 *
 * @param int     $post_id The post id to search in
 * @param string  $action The favorite action - valid options (add|delete)
 * @return string The favoriting URL
 */
function get_the_hrb_favorite_project_url( $post_id = 0, $action = 'add', $base_url = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$args = array (
		'favorite'   => $action,
		'post_id'    => $post_id,
		'ajax_nonce' => wp_create_nonce("favorite-{$post_id}"),
	);

	if ( $base_url ) {
		return add_query_arg( $args, $base_url );
	}
	return add_query_arg( $args );
}

/**
 * Retrieves the link to favorite a project.
 */
function get_the_hrb_project_favorite_link( $post_id = 0, $before = '', $after = '', $atts = array() ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	if ( ! current_user_can( 'favorite_post', $post_id ) ) {
		return html( 'p', '&nbsp;' );
	}

	$post_type = get_post_type( $post_id );

	if ( empty( $atts['base_url'] ) ) {
		$atts['base_url'] = '';
	}

	if ( ! is_hrb_faved_project( $post_id ) ) {

		$icon = html( 'i', array(
			'class' => "icon i-favorite fave-icon {$post_type}-fave",
		), '' );

		$defaults = array(
			'fave_text' => __( 'Favorite', APP_TD ),
			'class' => "button secondary expand {$post_type}-fave-link",
			'href' => get_the_hrb_favorite_project_url( $post_id, 'add', $atts['base_url']  ),
		);
		$atts = wp_parse_args( $atts, $defaults );

		$text = $atts['fave_text'];

	} else {

		$icon = html( 'i', array(
			'class' => "fave-icon {$post_type}-unfave",
		), '');

		$defaults = array(
			'unfave_text' => __( 'Un-Favorite', APP_TD ),
			'class' => "button secondary expand {$post_type}-unfave-link",
			'href' => get_the_hrb_favorite_project_url( $post_id, 'delete', $atts['base_url'] ),
		);
		$atts = wp_parse_args( $atts, $defaults );

		$text = $atts['unfave_text'];
	}

	unset( $atts['base_url'] );

	$button = html( 'a', $atts, $icon . ' ' . $before . $text . $after );

	return html( 'div', array( 'class' => 'favorites' ), $button );
}

/**
 * Outputs the link to favorite a project.
 */
function the_hrb_project_faves_link( $post_id = 0, $before = '', $after = '' ) {
	echo get_the_hrb_project_favorite_link( $post_id, $before, $after );
}

/**
 * Retrieves the URL to the proposals page for a project.
 */
function get_the_hrb_project_proposals_url( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	return add_query_arg( array( 'project_id' => $post_id ), hrb_get_dashboard_url_for('proposals') );
}

/**
 * Retrieves the URL for revieweing a user in a workspace. Retrieves permalink if in use, or URL with query vars, otherwise.
 */
function get_the_hrb_review_user_url( $workspace_id, $user = '' ) {
	global $hrb_options, $wp_rewrite;

	$user = get_the_hrb_userdata( $user );

	$workspace_permalink = untrailingslashit( get_permalink( $workspace_id ) );

	if ( $wp_rewrite->using_permalinks() ) {
		$review_permalink = $hrb_options->review_user_permalink;

		$review_url = "$workspace_permalink/$review_permalink/" . $user->user_nicename;

	} else {
		$review_url = add_query_arg( array( 'review_user' => $user->user_nicename ), get_permalink( $workspace_id ) );
	}

	$hash = hrb_get_workspace_hash( $workspace_id );

	return add_query_arg( array( 'hash' => $hash ), $review_url );
}

/**
 * Outputs the formatted URL for revieweing a user in a workspace.
 */
function the_hrb_review_user_link( $workspace_id, $user = '', $text = '', $before = '', $after = '', $atts = array() ) {
	$user = get_the_hrb_userdata( $user );

	if ( empty( $text ) ) {
		$text = __( 'Review', APP_TD );
	}

	$defaults = array(
		'id' => "review-user-{$user->ID}",
		'class' => 'button small review-hidden',
		'href' => esc_url( get_the_hrb_review_user_url( $workspace_id, $user ) ),
	);
	$atts = wp_parse_args( $atts, $defaults );

	echo $before . html( 'a', $atts, $text ) . $after;
}

/**
 * Retrieves the base URL for search refinements.
 */
function get_the_hrb_refine_search_base_url() {

	if ( ! get_query_var( 'st' ) && ! get_query_var('archive_freelancer') ) {
		return;
	}

	if ( HRB_FREELANCER_UTYPE == get_query_var( 'st' ) || get_query_var('archive_freelancer') ) {
		return get_the_hrb_users_base_url();
	} else {
		return get_the_hrb_projects_base_url();
	}

}


### Title / Excerpt

/**
 * Outputs the formatted title link for a listing. Optionally adds the featured CSS class if the listing is featured.
 */
function the_hrb_project_title( $post_id = 0, $before = '', $after = '', $atts = array() ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$title = get_the_title( $post_id );

	$post_type = get_post_type( $post_id );

	$defaults = array(
		'href'			=> esc_url( get_permalink( $post_id ) ),
		'title'			=> esc_attr( $title ),
		'class'			=> "{$post_type}-title",
		'rel'			=> 'bookmark',
		'featured_tag'	=> 'span',
	);
	$atts = wp_parse_args( $atts, $defaults );

	if ( 'pending' != get_post_status( $post_id ) ) {
		$title_link = html( 'a', $atts, $title );
	} else {
		unset( $atts['href'] );
		$title_link = html( 'span', $atts, $title );
	}

	if ( ! empty( $atts['featured_tag'] ) && is_hrb_project_featured( $post_id ) ) {

		$attr_featured = array(
			'class' => hrb_project_featured_class( $post_id ),
		);

		$title = html( $atts['featured_tag'], $attr_featured, $title );
	}

	echo $before . $title_link . $after;
}

/**
 * Outputs the formatted excerpt for a listing in the loop.
 */
function the_hrb_project_excerpt( $before = '', $after = '' ) {
	echo $before . get_the_excerpt(). $after;
}


### Author

/**
 * Outputs the formatted author link for a listing.
 */
function the_hrb_project_author( $post_id = 0, $before = '', $after = '' ) {
	$author_id = $post_id ? get_post_field( 'post_author', $post_id ) : 0;

	$author = get_userdata( get_the_author_meta( 'ID', $author_id ) );

	echo $before . sprintf( '<a href="%s" class="project-author">%s</a>', get_the_hrb_user_profile_url( $author ), $author->display_name ) . $after;
}


###  Date & Time

/**
 * Retrieves the human time ago for a proposal.
 */
function get_the_hrb_proposal_posted_time_ago( $proposal ) {
	return human_time_diff( strtotime( $proposal->get_date() ), current_time('timestamp') );
}

/**
 * Outputs the formatted human time ago for a proposal.
 */
function the_hrb_proposal_posted_time_ago( $proposal, $before = '', $after = '' ) {
	echo $before . sprintf( __( '%s ago', APP_TD ) , get_the_hrb_proposal_posted_time_ago( $proposal ) ) . $after ;
}

/**
 * Retrieves the human time ago for a listing.
 */
function get_the_hrb_project_posted_time_ago( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );
	return human_time_diff( get_post_time( 'U', $gmt = false, $post_id ), current_time('timestamp') );
}

/**
 * Retrieves the human time ago for a listing.
 */
function the_hrb_posted_time_ago( $time_start, $before = '', $after = '' ) {
	echo $before . sprintf( __( '%s ago', APP_TD ) , human_time_diff( $time_start, current_time('timestamp') ) ) . $after ;
}

/**
 * Outputs the formatted human time ago for a listing.
 */
function the_hrb_project_posted_time_ago( $post_id = 0, $before = '', $after = '' ) {
	echo $before . sprintf( __( '%s ago', APP_TD ) , get_the_hrb_project_posted_time_ago( $post_id ) ) . $after ;
}

/**
 * Retrieves the expiration date for a listing.
 */
function get_the_hrb_project_expire_date( $post_id = 0, $format = 'm/d/Y' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$duration = get_post_meta( $post_id, '_hrb_duration', true );

	if ( empty( $duration ) ) {
		return 0;
	}

	$post = get_post( $post_id );

	return hrb_get_formatted_expire_date( $post->post_date, $duration, $format );
}

/**
 * Calculates and retrieves the remaining days for a listing.
 */
function get_the_hrb_project_remain_days( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$expire_date = get_the_hrb_project_expire_date( $post_id, 'm/d/Y G:i:s' );

	if ( ! $expire_date ) {
		return '';
	}

	return appthemes_days_between_dates( $expire_date, current_time('mysql') );
}

/**
 * Outputs the formatted remaining days in listings
 */
function the_hrb_project_remain_days( $post_id = 0, $alt_output = false ) {

	$days = get_the_hrb_project_remain_days( $post_id );

	$expired = true;

	if ( 'publish' != get_post_status( $post_id ) ) {

		$days_left = ! $alt_output ? '&nbsp;' : '';
		$days_left_label = __( 'Not Available', APP_TD );

	} elseif ( '' === $days ) {

		$days_left = ! $alt_output ? '-' : '';
		$days_left_label = __( 'Endless', APP_TD );

	} elseif ( $days > 0 ) {

		$days_left = (int) $days;
		$days_left_label = _n( 'day', 'days', $days, APP_TD );

	} else {

		$expired = true;

		$days_left = __( 'Expired', APP_TD );
		$days_left_label = ! $alt_output ? __( 'n/a', APP_TD ) : '';
	}

	if ( $alt_output ) {
		$remain_days = sprintf( '%s %s', $days_left, $days_left_label );
	} else {
		$remain_days = sprintf( '<span class="days-left">%s</span> <span class="days-left-label">%s</span>', $days_left, $days_left_label );
	}

	echo $remain_days;
}

/**
 * Outputs the formatted posted date for a listing.
 */
function the_hrb_project_posted_date( $post_id = 0, $before = '', $after = '' ) {
	$date = $post_id ? get_post_field( 'post_date', $post_id ) : get_the_date();

	$posted_date = mysql2date( get_option('date_format'), $date );

	echo $before . $posted_date . $after;
}


### Meta

/**
 * Generates and outputs the custom form fields for a listing and loads the custom forms fields template.
 */
function the_hrb_project_custom_fields( $post_id = 0, $type = '', $include = true ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$post_type = get_post_type( $post_id );
	$cat_taxonomy = HRB_PROJECTS_CATEGORY;

	$cats = wp_get_object_terms( $post_id, $cat_taxonomy );
	if ( ! $cats ) {
		return;
	}

	$cats = wp_list_pluck( $cats, 'term_id' );

	$fields = array();

	$forms = hrb_get_custom_form( $cats, $cat_taxonomy );

	foreach( $forms as $form ) {
		$form_fields = APP_Form_Builder::get_fields( $form->ID );

		$fields = array();

		foreach( $form_fields as $field ) {

			if ( $type && ( ( $type != $field['type'] && $include ) || ( $type == $field['type'] && ! $include ) ) ) {
				continue;
			}

			if ( 'checkbox' == $field['type'] ) {
				$output = implode( ', ', get_post_meta( $post_id, $field['name'] ) );

			} else if ( 'file' == $field['type'] ) {

				$attachment_ids = get_post_meta( $post_id, $field['name'], true );
				$embed_urls = get_post_meta( $post_id, $field['name'].'_embeds', true );

					if ( $attachment_ids || $embed_urls ) {

						ob_start();

						if ( $attachment_ids ) {
							appthemes_output_attachments( $attachment_ids );
						}

						if ( $embed_urls) {
							appthemes_output_embed( $embed_urls );
						}
						$output = ob_get_clean();
					}

			} else {
				$output = get_post_meta( $post_id, $field['name'], true );
			}

			if ( ! empty( $output ) ) {
				$field['output'] = $output;
				$fields[] = $field;
			}
		}

		if ( ! empty( $fields ) ) {
			$fieldset = array(
				'class' => $form->post_name,
				'fields' => $fields,
			);
			appthemes_load_template( "single-{$post_type}-custom-field.php", $fieldset );
		}
	}
}

/**
 * Retrieves a list of all the unique locations for all existing projects.
 */
function hrb_get_projects_locations() {
	global $hrb_options;

    $params = array(
		'meta_key' => '_hrb_location_type',
		'meta_compare' => 'EXISTS'
    );

    if ( ! $projects = hrb_get_projects( $params ) ) {
        return array();
    }

    $locations = array();

	$has_remote = false;

    foreach( $projects->posts as $post ) {

		$location = get_post_meta( $post->ID, '_hrb_location_type', true );

		if ( 'local' == $location ) {

			$country = get_post_meta( $post->ID, '_hrb_location_country', true );

			if ( 'location' == $hrb_options->project_refine_search ) {

				// try to get the most relevant location
				$location = get_post_meta( $post->ID, '_hrb_location_locality', true );
				if ( ! $location ) {
					$location = get_post_meta( $post->ID, '_hrb_location_administrative_area_level_2', true );
					if ( ! $location ) {
						$location = get_post_meta( $post->ID, '_hrb_location_neighborhood', true );
						if ( ! $location ) {
							$location = get_post_meta( $post->ID, '_hrb_location_administrative_area_level_1', true );
						}
					}
				}

			} elseif ( 'postal_code' == $hrb_options->project_refine_search ) {
				$location = get_post_meta( $post->ID, '_hrb_location_postal_code', true );
			} else {
				$location = $country;
			}

			if ( ! $location ) {
				// default to base location
				$location = get_post_meta( $post->ID, '_hrb_location', true );
			}

			$location_desc = sprintf( "%s%s", ( empty( $hrb_options->project_geo_country ) && $location != $country ? $country . ' :: ' : '' ), $location );

			if ( $location ) {

				if ( empty( $locations[ $location ] ) ) {
					$locations[ $location ] = $location_desc;
				}

			}

		} else {
			$has_remote = true;
		}

    }

	asort( $locations );

	if ( $has_remote ) {
		$locations['remote'] = __( 'Remote', APP_TD );
	}

    return $locations;
}

/**
 * Retrieves the location (post meta) for a listing.
 */
function get_the_hrb_project_location( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$address_pref = get_post_meta( $post_id, '_hrb_location_type', true );

	if ( 'remote' == $address_pref ) {
		return __( 'Remote', APP_TD );
	}

	$address = get_post_meta( $post_id, '_hrb_location', true );
	if ( ! $address ) {
		$address = __( 'N/A', APP_TD );
	}

	return $address;
}

/**
 * Outputs the formatted location (post meta) for a listing.
 */
function the_hrb_project_location( $post_id = 0, $before = '', $after = '' ) {
	$location = get_the_hrb_project_location( $post_id );

	echo $before . $location . $after;
}

/**
 * Retrieves the development terms (post meta) for a listing.
 */
function get_the_hrb_project_dev_terms( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	return get_post_meta( $post_id, '_hrb_project_terms', true );
}

/**
 * Outputs the formatted development terms (post meta) for a listing.
 */
function the_hrb_project_terms( $post_id = 0, $before = '', $after = '' ) {
	$terms = get_the_hrb_project_dev_terms( $post_id );

    if ( ! $terms ) {
        $terms = __( 'None', APP_TD );
    }

	echo $before . $terms . $after;
}

/**
 * Outputs the formatted project budget (post meta).
 */
function the_hrb_project_budget( $project = '',  $before = '', $after = '' ) {
	global $post;

	$project = $project ? $project : $post;

	$budget_currency = $project->_hrb_budget_currency;

	$budget_type = $project->_hrb_budget_type;
	$budget_price = $project->_hrb_budget_price;

	if ( empty( $budget_price ) ) {
		$budget_price = 0;
	}

	$f_budget_price = appthemes_get_price( $budget_price, $budget_currency );

	if ( 'fixed' == $budget_type ) {
		$budget = sprintf( '%s', $f_budget_price );
	} else {
		$hours = $project->_hrb_hourly_min_hours;
		$budget = sprintf( __( '%s <span class="budget-hours">(%d %s)</span>', APP_TD ), $f_budget_price, $hours, _n( 'hour', 'hours', $hours ) );
	}

	echo $before . $budget . $after;
}

/**
 * Outputs the formatted project budget type (post meta).
 */
function the_hrb_project_budget_type( $project = '', $before = '', $after = '' ) {
	$type = get_the_hrb_project_budget_type( $project, $before, $after );

	echo $before . $type . $after;
}

/**
 * Retrieves project budget type (post meta).
 */
function get_the_hrb_project_budget_type( $project = '', $before = '', $after = '' ) {
	global $post;

	$project = $project ? $project : $post;

	$budget_type = $project->_hrb_budget_type;

	if ( 'fixed' == $budget_type ) {
		$type = __( 'Fixed Price', APP_TD );
	} else {
		$type = __( 'Per Hour', APP_TD );
	}
	return $type;
}

/**
 * Retrieves project budget units (post meta).
 */
function get_the_hrb_project_budget_units( $project = '', $units = 1 ) {
	global $post;

	$project = $project ? $project : $post;

	$budget_type = $project->_hrb_budget_type;

	if ( 'fixed' == $budget_type ) {
		$unit_single = __( 'Day', APP_TD );
		$unit_plural = __( 'Days', APP_TD );
	} else {
		$unit_single = __( 'Hour', APP_TD );
		$unit_plural = __( 'Hours', APP_TD );
	}

	return sprintf( '%d %s', $units, _n( $unit_single, $unit_plural, $units, APP_TD ) );
}

/**
 * Retrieves the project budget currency (post meta).
 */
function get_the_hrb_project_budget_currency( $project = '', $part = 'symbol' ) {
	global $post;

	$project = $project ? $project : $post;

	$budget_currency = $project->_hrb_budget_currency;

	return APP_Currencies::get_currency( $budget_currency, $part );
}


### Taxonomies

/**
 * Retrieves the terms for a specific taxonomony for any object type (post, comment).
 */
function get_the_hrb_terms_list( $object_id = 0, $taxonomy = 'category', $before = '', $after = '' ) {
	$object_id = $object_id ? $object_id : get_the_ID();

	$terms = wp_get_object_terms( $object_id, $taxonomy );

	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	if ( empty( $terms ) ) {
		return array();
	}

	$term_links = array();

	foreach ( $terms as $term ) {
		$link = get_term_link( $term, $taxonomy );

		// always check if it's an error before continuing. get_term_link() can be finicky sometimes
		if ( is_wp_error( $link ) ) {
			continue;
		}

		$atts = array(
			'href' => esc_url( $link ),
			'rel' => 'tag',
		);
		$term_links[] = html( 'a', $atts, $before . $term->name . $after );
	}
	return $term_links;
}

/**
 * Outputs the formatted terms for a specific taxonomony for any object type (post, comment).
 */
function the_hrb_tax_terms( $taxonomy = 'category', $object_id = 0, $separator = ', ', $before = '', $after = '' ) {
	$term_links = get_the_hrb_terms_list( $object_id, $taxonomy, $before, $after );

	echo join( $separator, $term_links );
}

/**
 * Retrieves the terms for a specific listing taxonomy.
 */
function get_the_hrb_project_terms( $post_id = 0, $taxonomy = 'category' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$terms = get_the_terms( $post_id, $taxonomy );

	if ( ! $terms ) {
		return array();
	}

	return $terms;
}


### Statuses

/**
 * Retrieves the nice name status for a given project. Projects with Orders attached have different statuses verbiages.
 */
function get_the_hrb_project_status( $post_id = 0, $verbiages = true ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	if ( hrb_charge_listings() ) {

		$order = appthemes_get_order_connected_to( $post_id );

		if ( $order && APPTHEMES_ORDER_ACTIVATED != $order->get_status() ) {
			if ( $verbiages ) {
				return hrb_get_order_statuses_verbiages( $order->get_status() );
			} else {
				return $order->get_status();
			}

		}

	}
	$post = get_post( $post_id );

	if ( $verbiages ) {
		return hrb_get_project_statuses_verbiages( $post->post_status );
	} else {
		return $post->post_status;
	}
}

/**
 * Outputs the status for a project.
 */
function the_hrb_project_status( $post_id = 0, $before = '', $after = '' ) {
	echo $before . get_the_hrb_project_status( $post_id ) . $after;
}

function get_the_hrb_project_or_workspace_status( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$user_id = get_current_user_id();

	$post = get_post( $post_id );

	if ( $post->post_author == $user_id ) {
		return get_the_hrb_project_status( $post_id, $verbiages = false );
	} else {
		$workspace_id = hrb_get_participants_workspace_for( $post_id, $user_id );

		return get_post_status( reset( $workspace_id ) );
	}
}

function the_hrb_project_or_workspace_status( $post_id = 0, $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$user_id = get_current_user_id();

	$post = get_post( $post_id );

	if ( $post->post_author == $user_id ) {
		 the_hrb_project_status( $post_id, $before, $after );
	} else {
		$workspace_id = hrb_get_participants_workspace_for( $post_id, $user_id );

		the_hrb_workspace_status( reset( $workspace_id ), $before, $after  );
	}
}


### Conditional Template Tags

/**
 * Checks if a project is expired.
 */
function is_hrb_project_expired( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

    $status = get_post_status( $post_id );

    if ( HRB_PROJECT_STATUS_EXPIRED == $status ) {
        return true;
    }

	$expiration_date = get_the_hrb_project_expire_date( $post_id, $format = 'm/d/Y G:i:s' );

	if ( ! $expiration_date ) {
		return false;
	}

	return (bool) ( strtotime( $expiration_date ) < time() );
}

/**
 * Checks if a project is open for agreement.
 *
 * @uses apply_filters() Calls 'hrb_open_agreement_statuses'
 *
 */
function is_hrb_project_open_for_agreement( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$statuses = array( 'publish', HRB_PROJECT_STATUS_TERMS, HRB_PROJECT_STATUS_CANCELED_TERMS );

	$open_agreement_statuses = apply_filters( 'hrb_open_agreement_statuses', $statuses );

	return in_array( get_post_status( $post_id ), $open_agreement_statuses );
}

/**
 * Checks if a project is open for agreement.
 *
 * @uses apply_filters() Calls 'hrb_proposal_open_select_statuses'
 *
 * @since 1.3.1
 */
function is_hrb_project_proposal_selectable( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$statuses = array( HRB_PROJECT_STATUS_TERMS );

	$open_agreement_statuses = apply_filters( 'hrb_proposal_open_select_statuses', $statuses );

	return in_array( get_post_status( $post_id ), $open_agreement_statuses );
}


### Attachments / Embeds

/**
 * Retrieves the list of attachments ID's for a project.
 */
function get_the_hrb_project_attachments( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	// @todo maybe use constant name for field name

	$field_name = '_app_media';

	$attachment_ids = get_post_meta( $post_id, $field_name, true );

	return $attachment_ids;
}

/**
 * Retrieves the embeds for a project.
 */
function get_the_hrb_project_embeds( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$field_name = '_app_media_embeds';

	$embed_urls = get_post_meta( $post_id, $field_name, true );

	return $embed_urls;
}

/**
 * Outputs the formatted attachments/embeds list.
 */
function the_hrb_project_files( $post_id = 0, $before = '', $after = '' ) {

	// output the files from static inputs

	$attachment_ids = get_the_hrb_project_attachments( $post_id );
	$embed_urls = get_the_hrb_project_embeds( $post_id );

	ob_start();

	if ( $attachment_ids || $embed_urls ) {

		if ( $attachment_ids ) {
			appthemes_output_attachments( $attachment_ids );
		}
		if ( $embed_urls) {
			appthemes_output_embed( $embed_urls );
		}
	}

	// output files from custom input fields
	the_hrb_project_custom_fields( $post_id, 'file' );

	$output = ob_get_clean();

	if ( $output ) {
		echo $before . $output . $after;
	}
}


### Workspace Template Tags ( Project -> Workspace )

### Meta

/**
 * Retrieves the meta status for the current workspace (post meta). The workspace meta statuses are updated with the projects statuses.
 *
function get_the_hrb_workspace_status( $workspace_id = 0 ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	return get_post_status( $workspace_id );
}*/

/**
 * Outputs the formatted meta status for the current workspace (post meta).
 */
function the_hrb_workspace_status( $workspace_id = 0, $before = '', $after = '' ) {

	$status = get_post_status( $workspace_id );

	echo $before . hrb_get_project_statuses_verbiages( $status ) . $after;
}

/**
 * Retrieves the meta status notes for the current workspace (post meta).
 */
function get_the_hrb_workspace_status_notes( $workspace_id = 0 ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	$status = hrb_get_workspace_status_notes( $workspace_id );

	if ( ! $status ) {
		return __( 'None', APP_TD );
	}
	return $status;
}

/**
 * Outputs the formatted meta status notes for the current workspace (post meta).
 */
function the_hrb_workspace_status_notes( $workspace_id = 0, $before = '', $after = '' ) {
	echo $before . get_the_hrb_workspace_status_notes( $workspace_id ) . $after;
}

/**
 * Retrieves the selectable status for a workspace participant (worker/employer).
 *
 * @uses apply_filters() Calls 'hrb_participant_sel_statuses'
 */
function get_the_hrb_participant_sel_statuses( $participant, $workspace_id = 0 ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	if ( 'worker' == $participant->type ) {
		$statuses = hrb_get_worker_sel_statuses( $participant, $workspace_id );
	} else {
		$statuses = hrb_get_employer_sel_statuses( $participant, $workspace_id );
	}

	return apply_filters( 'hrb_participant_sel_statuses', $statuses, $participant, $workspace_id );
}

/**
 * Retrieves the workspace(s) link(s) given a list of workspace ID's.
 */
function get_the_hrb_workspace_link( $workspace_id = 0 ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	$workspaces = array();

	foreach( (array) $workspace_id as $ws_id ) {

		$workspace = get_post( $ws_id );

		$workspaces[] = array(
			'title' => $workspace->post_title,
			'href' => hrb_get_workspace_url( $workspace->ID ),
		);
	}
	return $workspaces;
}

/**
 * Outputs the formatted workspace(s) link(s) for a project.
 */
function the_hrb_project_workspace_link( $post_id = 0, $text = '', $before = '', $after = '' ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$user_id = get_current_user_id();

	$workspaces_ids = hrb_get_participants_workspace_for( $post_id, $user_id );

	if ( empty( $workspaces_ids ) ) {
		return;
	}

	if ( ! $text ) {
		 $text = __( 'Workspaces', APP_TD );
	}

	$workspaces = get_the_hrb_workspace_link( $workspaces_ids );

	if ( count( $workspaces ) > 1  ) {
		the_hrb_data_dropdown( $workspaces, array( 'data-dropdown' => "workspaces-{$post_id}" ), $before . $text . $after );
	} else {
		the_hrb_workspace_link( $workspaces_ids[0], $text, $before, $after );
	}
}

/**
 * Outputs the formatted workspace link for a single workspace.
 */
function the_hrb_workspace_link( $workspace_id = 0, $text = '', $before = '', $after = '', $atts = array() ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	// must be workspace participant
	if ( ! current_user_can( 'edit_workspace', $workspace_id ) ) {
		return;
	}

	if ( ! $text ) {
		 $text = __( 'Visit Workspace ...', APP_TD );
	}

	$defaults = array(
		'class' => 'button tiny secondary',
		'href' => hrb_get_workspace_url( $workspace_id ),
	);
	$atts = wp_parse_args( $atts, $defaults );

	echo html( 'a', $atts, $before . $text . $after );
}


### Addons

/**
 * Retrieves the addons list and their meta values for a project.
 */
function get_the_hrb_project_addons( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

    $post_type = get_post_type( $post_id );

	if ( 'page' == $post_type ) {
		$post_type = HRB_PROJECTS_PTYPE;
	}

    $fields = array( 'price', 'duration', 'start_date' );

    $addons = array();

    foreach( hrb_get_addons( $post_type ) as $addon ) {
        $value = get_post_meta( $post_id, $addon, true );
        if ( ! empty( $value ) ) {
            $addons[ $addon ]['title'] = APP_Item_Registry::get_title( $addon );
			$addons[ $addon ]['class_name'] = APP_Item_Registry::get_meta( $addon, 'class_name' );
			$addons[ $addon ]['icon'] = APP_Item_Registry::get_meta( $addon, 'icon' );
			$addons[ $addon ]['label'] = APP_Item_Registry::get_meta( $addon, 'label' );
			$addons[ $addon ]['label_2'] = APP_Item_Registry::get_meta( $addon, 'label_2' );

            foreach( $fields as $field ) {
                $addons[ $addon ][ $field ] = get_post_meta( $post_id, $addon . '_' . $field, true );
            }

			if ( ! $addons[ $addon ]['start_date'] || ! $addons[ $addon ]['duration'] ) {
				$addons[ $addon ]['expiration_date']  = __( 'Never', APP_TD );
			} else {
				$addons[ $addon ]['expiration_date'] = hrb_get_formatted_expire_date( $addons[ $addon ]['start_date'], $addons[ $addon ]['duration'] );
			}

        }
    }
    return $addons;
}

/**
 * Outputs the addons related to a project.
 */
function the_hrb_project_addons( $post_id = 0, $before = '', $after = '' ) {

    $addons = get_the_hrb_project_addons( $post_id );

	$echo_addons = '';

    foreach( $addons as $key => $addon ) {
        $echo_addons .= '<span class="' . $addon['class_name'] . '">' . $addon['label'] . '</span>';
    }

	echo $before . $echo_addons . $after;
}


### Conditional Tags

/**
 * Checks if a workspace is available to receive user reviews.
 */
function is_hrb_workspace_open_for_reviews( $workspace_id = 0 ) {
	$workspace_id = get_the_hrb_loop_id( $workspace_id );

	$end_statuses = hrb_get_project_work_ended_statuses();

	$status = get_post_status( $workspace_id );

	return (bool) in_array( $status, $end_statuses );
}

/**
 * Checks if a post is featured by checking the post meta and the current user page context ( is_home() or is_tax() ).
 */
function is_hrb_project_featured( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	if ( is_home() && hrb_project_has_addon( $post_id, HRB_ITEM_FEATURED_HOME ) ) {
		return true;
    }

	if ( is_tax( get_post_taxonomies( $post_id ) ) && hrb_project_has_addon( $post_id, HRB_ITEM_FEATURED_CAT ) ) {
		return true;
    }

	return false;
}

/**
 * Check if a specific project is already favorited
 */
function is_hrb_faved_project( $post_id = 0 ) {
	$post_id = get_the_hrb_loop_id( $post_id );

	$count = p2p_get_connections( HRB_P2P_PROJECTS_FAVORITES, array (
		'direction' => 'from',
		'from' 		=> $post_id,
		'to' 		=> get_current_user_id(),
		'fields' 	=> 'count'
	) );

	return (bool) $count;
}

/**
 * Check if a saveable filter can be applied to the current page.
 */
function is_hrb_project_saveable_filter() {
	return ! is_hrb_users_archive() && ( is_archive( HRB_PROJECTS_PTYPE ) || is_search() || is_tax( HRB_PROJECTS_CATEGORY ) || is_tax( HRB_PROJECTS_SKILLS ) );
}



