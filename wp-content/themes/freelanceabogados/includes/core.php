<?php
/**
 * Contains core functions.
 */

// taxonomies need to be registered before the post type, in order for the rewrite rules to work
add_action( 'init', '_hrb_register_taxonomies', 8 );
add_action( 'init', '_hrb_register_post_types', 9 );

add_action( 'wp_login', '_hrb_redirect_after_login' );
add_action( 'app_login', '_hrb_redirect_after_login' );

add_action( 'appthemes_pagenavi_args', '_hrb_home_pagenavi_args' );


### Post Types & Taxonomies

/**
 * Register the main custom post types and statuses.
 */
function _hrb_register_post_types() {
	global $hrb_options;

	### Projects

	$labels = array(
		'name' => __( 'Projects', APP_TD ),
		'singular_name' => __( 'Project', APP_TD ),
		'add_new' => __( 'Add New', APP_TD ),
		'add_new_item' => __( 'Add New Project', APP_TD ),
		'edit_item' => __( 'Edit Project', APP_TD ),
		'new_item' => __( 'New Project', APP_TD ),
		'view_item' => __( 'View Project', APP_TD ),
		'search_items' => __( 'Search Projects', APP_TD ),
		'not_found' => __( 'No projects found', APP_TD ),
		'not_found_in_trash' => __( 'No projects found in Trash', APP_TD ),
		'parent_item_colon' => __( 'Parent Projects:', APP_TD ),
		'menu_name' => __( 'Projects', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,
		'supports' => array( 'title', 'editor', 'excerpt', 'author', 'comments' ),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 6,
		'show_in_nav_menus' => false,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'has_archive' => true,
		'query_var' => true,
		'can_export' => true,
		'rewrite' => array( 'slug' => $hrb_options->project_permalink, 'with_front' => false ),
		'capability_type' => HRB_PROJECTS_PTYPE,
		'map_meta_cap' => true,
		'menu_icon' => 'dashicons-portfolio'
	);

	if ( current_user_can( 'manage_options' ) ) {
		$args['supports'][] = 'custom-fields';
	}

	register_post_type( HRB_PROJECTS_PTYPE, $args );

	$statuses = array(
		HRB_PROJECT_STATUS_WAITING_FUNDS => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Waiting Funds', APP_TD ),
			'label_count' => _n_noop( 'Waiting Funds <span class="count">(%s)</span>', 'Waiting Funds <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_TERMS => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Discussing Terms', APP_TD ),
			'label_count' => _n_noop( 'Discussing Terms <span class="count">(%s)</span>', 'Discussing Terms <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_WORKING => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'In Development', APP_TD ),
			'label_count' => _n_noop( 'In Development <span class="count">(%s)</span>', 'In Development <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_CLOSED_COMPLETED => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Completed', APP_TD ),
			'label_count' => _n_noop( 'Closed Completed <span class="count">(%s)</span>', 'Closed Completed <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_CLOSED_INCOMPLETE => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Closed Incomplete', APP_TD ),
			'label_count' => _n_noop( 'Closed Incomplete <span class="count">(%s)</span>', 'Closed Incomplete <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_CANCELED => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Canceled', APP_TD ),
			'label_count' => _n_noop( 'Canceled <span class="count">(%s)</span>', 'Canceled <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_CANCELED_TERMS => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Canceled Terms', APP_TD ),
			'label_count' => _n_noop( 'Canceled Terms<span class="count">(%s)</span>', 'Canceled Terms <span class="count">(%s)</span>', APP_TD ),
		),
		HRB_PROJECT_STATUS_EXPIRED => array(
			'public' => true,
			'exclude_from_search' => false,
			'show_in_admin_all_list' => true,
			'show_in_admin_status_list' => true,
			'label' => __( 'Expired', APP_TD ),
			'label_count' => _n_noop( 'Expired <span class="count">(%s)</span>', 'Expired <span class="count">(%s)</span>', APP_TD ),
		),
	);

	foreach( $statuses as $status => $args ) {
		register_post_status( $status, $args );
	}

}

/**
 * Register custom taxonomies.
 */
function _hrb_register_taxonomies() {
	global $hrb_options;

	### Categories

	$labels = array(
		'name' => __( 'Project Categories', APP_TD ),
		'singular_name' => __( 'Project Category', APP_TD ),
		'search_items' => __( 'Search Project Categories', APP_TD ),
		'all_items' => __( 'All Project Categories', APP_TD ),
		'parent_item' => __( 'Parent Project Category', APP_TD ),
		'parent_item_colon' => __( 'Parent Project Category:', APP_TD ),
		'edit_item' => __( 'Edit Project Category', APP_TD ),
		'update_item' => __( 'Update Project Category', APP_TD ),
		'add_new_item' => __( 'Add New Project Category', APP_TD ),
		'new_item_name' => __( 'New Project Category Name', APP_TD ),
		'add_or_remove_items' => __( 'Add or remove project categories', APP_TD ),
		'menu_name' => __( 'Categories', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_ui' => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => false,
		'hierarchical' => true,
		'query_var' => true,
		'rewrite' => array(
			'slug' => $hrb_options->project_permalink . '/' . $hrb_options->project_cat_permalink,
			'with_front' => false
		),
	);

	register_taxonomy( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_PTYPE, $args );


	### Tags

	$labels = array(
		'name' => __( 'Project Tags', APP_TD ),
		'singular_name' => __( 'Project Tag', APP_TD ),
		'search_items' => __( 'Search Project Tags', APP_TD ),
		'popular_items' => __( 'Popular Project Tags', APP_TD ),
		'all_items' => __( 'All Project Tags', APP_TD ),
		'parent_item' => __( 'Parent Project Tag', APP_TD ),
		'parent_item_colon' => __( 'Parent Project Tag:', APP_TD ),
		'edit_item' => __( 'Edit Project Tag', APP_TD ),
		'update_item' => __( 'Update Project Tag', APP_TD ),
		'add_new_item' => __( 'Add New Project Tag', APP_TD ),
		'new_item_name' => __( 'New Project Tag Name', APP_TD ),
		'separate_items_with_commas' => __( 'Separate project tags with commas', APP_TD ),
		'add_or_remove_items' => __( 'Add or remove project tags', APP_TD ),
		'choose_from_most_used' => __( 'Choose from the most used project tags', APP_TD ),
		'menu_name' => __( 'Tags', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_in_nav_menus' => true,
		'show_ui' => true,
		'show_tagcloud' => true,
		'hierarchical' => false,
		'query_var' => true,
		'rewrite' => array(
			'slug' => $hrb_options->project_permalink . '/' . $hrb_options->project_tag_permalink,
			'with_front' => false
		),
	);

	register_taxonomy( HRB_PROJECTS_TAG, HRB_PROJECTS_PTYPE, $args );


	### Skills

	$labels = array(
		'name' => __( 'Skills', APP_TD ),
		'singular_name' => __( 'Skill', APP_TD ),
		'search_items' => __( 'Search Skills', APP_TD ),
		'all_items' => __( 'All Skills', APP_TD ),
		'parent_item' => __( 'Parent Skill', APP_TD ),
		'parent_item_colon' => __( 'Parent Skill:', APP_TD ),
		'edit_item' => __( 'Edit Skill', APP_TD ),
		'update_item' => __( 'Update Skill', APP_TD ),
		'add_new_item' => __( 'Add New Skill', APP_TD ),
		'new_item_name' => __( 'New Skill', APP_TD ),
		'add_or_remove_items' => __( 'Add or remove skills', APP_TD ),
		'menu_name' => __( 'Skills', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'public' => true,
		'show_ui' => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => false,
		'hierarchical' => true,
		'query_var' => true,
		'rewrite' => array(
			'slug' => $hrb_options->project_permalink . '/' . $hrb_options->project_skill_permalink,
			'with_front' => false
		),
	);

	register_taxonomy( HRB_PROJECTS_SKILLS, HRB_PROJECTS_PTYPE, $args );
}

/**
 * Redirects the user to the frontpage if the 'redirect_to' post data is not present.
 */
function _hrb_redirect_after_login() {
	if ( ! isset( $_REQUEST['redirect_to'] ) || home_url() == $_REQUEST['redirect_to'] ) {
		wp_redirect( hrb_get_dashboard_url_for() );
		exit();
	}
}

/**
 * Retrieve additional args to be used with PageNavi.
 */
function _hrb_home_pagenavi_args( $args ) {
	global $hrb_options;

	if ( is_archive() || is_hrb_users_archive() ) {
		return $args;
	}

	if ( ! empty( $args['paginate_projects'] ) ) {
		$listings_permalink = $hrb_options->project_permalink;
	} elseif ( !empty( $args['paginate_users'] ) ) {
		$listings_permalink = $hrb_options->user_permalink;
	}

	if ( !empty( $listings_permalink ) ) {
		$base = trailingslashit( home_url() );
		$args['base'] = str_replace( $base, $base . $listings_permalink . '/', $args['base'] );
	}
	return $args;
}
