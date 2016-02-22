<?php
/**
 * Views for projects related pages.
 *
 * Views prepare and provide data to the page requested by the user.
 *
 * Notes:
 * . Contains HTML output helper functions used in projects Views.
 *
 */

add_action( 'wp_ajax_hrb_output_subcategories', '_hrb_output_subcategories' );


/**
 * View for single projects.
 */
class HRB_Project_Single extends APP_View {

	function condition() {
		return is_singular( HRB_PROJECTS_PTYPE );
	}

	/**
	 * Retrieves the required vars for a Single project template.
	 *
	 * @uses apply_filters() Calls 'hrb_single_project_template_vars'
	 *
	 */
	function template_vars() {
		global $hrb_options;

		// query proposals and sort them by featured and date
		$params = array(
			'app_optional_orderby' => array( 'meta_value_num' => 'DESC', 'comment_date_gmt' => 'DESC' ),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'key' => '_hrb_featured',
					'compare' => 'EXISTS'
				),
				array(
					'key' => '_hrb_featured',
					'compare' => 'NOT EXISTS'
				)
			)
		);

		$project = get_queried_object();
		$proposals = hrb_get_proposals_by_post( $project->ID, $params );

		$project_author = get_user_by( 'id', $project->post_author );

		$vars = array(
			'hrb_options' => $hrb_options,
			'proposals' => $proposals['results'],
			'project_author' => $project_author,
			'project_author_reviews' => (int) get_the_hrb_user_total_reviews( $project_author ),
		);
		return apply_filters( 'hrb_single_project_template_vars', $vars );
	}

	function template_redirect() {
		global $hrb_options;

		// enqeue required scripts/styles

		if ( $hrb_options->projects_clarification ) {
			hrb_register_enqueue_scripts( 'comment-reply' );
		}
	}

	function notices() {

		parent::notices();

		$project = get_queried_object();
		$status = get_post_status( $project );

		if ( 'pending' == $status ) {

			appthemes_display_notice( 'success-pending', __( 'This project is currently pending and must be approved by an administrator.', APP_TD ) );

		} elseif ( 'publish' != $status ) {

			if ( HRB_PROJECT_STATUS_WORKING == $status  ) {
				appthemes_display_notice( 'in-development', __( "This project is in development.", APP_TD ) );
			} else {
				appthemes_display_notice( 'not-active', __( "This project is not active.", APP_TD ) );
			}

		} elseif ( is_hrb_project_expired( $project->ID ) ) {
			appthemes_display_notice( 'not-active', __( "This project has expired.", APP_TD ) );
		}
	}

	function title_parts( $parts ) {
		return array( __( 'Project Details', APP_TD ) );
	}

}

/**
 * View for creating/posting a project.
 */
class HRB_Project_Create extends APP_View_Page {

	function init() {
		global $wp;

		if ( ! self::get_id() ) {
            return;
        }

		$post = get_post( self::get_id() );
		$permalink = $post->post_name;

		appthemes_add_rewrite_rule( $permalink . '/(\d+)/?$', array(
			'pagename' => $permalink,
			'project_id' => '$matches[1]'
		) );

		$wp->add_query_var( 'project_id' );
	}

	function __construct() {
		parent::__construct( 'create-project.php', __( 'Post a Project', APP_TD ), array( 'internal_use_only' => true ) );
	}

	static function get_id() {
		return parent::_get_id( __CLASS__ );
	}

	/**
	 * Validates rules for loading the Create project template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_create_project'
	 *
	 */
	private function pre_load() {

		appthemes_require_login();

		$project_id = get_query_var('project_id');

		// if the project already exists, it's because user is resuming a draft project or navigating the form
		if ( ! empty( $project_id ) ) {
			$project = get_post( $project_id );

			if ( 'draft' !== $project->post_status ) {
				appthemes_add_notice( 'cannot-resume', __( 'You cannot edit that project.', APP_TD ) );
				return false;
			}
		}

		if ( ! current_user_can('edit_projects') ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not allowed to post projects.', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_create_project', APP_Notices::$notices );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function template_include( $path ) {

		if ( ! $this->pre_load() ) {
			wp_redirect( home_url() );
			exit;
		}

		appthemes_setup_checkout( 'chk-create-project', get_permalink( self::get_id() ) );

		$checkout = appthemes_get_checkout();

		$project_id = (int) get_query_var('project_id');
		if ( $project_id ) {
			$checkout->add_data( 'project', get_post( $project_id ) );
		}

		$step_found = appthemes_process_checkout();

		if ( ! $step_found ) {
			return locate_template('404.php');
		}

		return $path;
	}

	/*
	 * @uses apply_filters() Calls 'hrb_project_max_skills_selection'
	 */
	function template_redirect() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function enqueue_styles_scripts() {

		hrb_register_enqueue_styles( array( 'jquery-tagmanager', 'jquery-select2' ) );
		hrb_register_enqueue_scripts( array( 'jquery-tagmanager', 'validate', 'jquery-select2', 'app-project-edit' ) );

		// also enqueue geo scripts if supported
		hrb_maybe_enqueue_geo();

		appthemes_enqueue_media_manager( array( 'post_id_field' => 'ID' ) );

		$max_skills = hrb_get_allowed_skills_count();

		wp_localize_script( 'app-project-edit', 'app_project_edit_i18n', array(
			'skills_placeholder' => sprintf( __( 'Required skills for this project%s', APP_TD ), ( $max_skills > 0 ? ' ' . sprintf( __( '(%s maximum)', APP_TD ), $max_skills ) : '' ) ),
			'maximum_skills_selection' => $max_skills,
			'geocomplete_options' => hrb_get_geocomplete_options(),
		) );

	}

	function body_class( $classes ) {
		$classes[] = 'app-project-create';
		return $classes;
	}

}

/**
 * View for editing a project.
 */
class HRB_Project_Edit extends HRB_Project_Create {

	function init() {
		global $wp, $hrb_options;

		$project_permalink = $hrb_options->project_permalink;
		$edit_permalink = $hrb_options->edit_project_permalink;

		appthemes_add_rewrite_rule( $project_permalink . '/' . $edit_permalink . '/(\d+)/?$', array(
			'project_edit' => '$matches[1]',
			'project_id' => '$matches[1]'
		) );

		$wp->add_query_var( 'project_edit' );
	}

	function condition() {
		return (bool) get_query_var('project_edit');
	}

	function  parse_query( $wp_query ) {
		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $wp_query->get( 'project_id' ) )
		) );

		$wp_query->is_home = false;
	}

	function the_posts( $posts, $wp_query ) {
		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}
		return $posts;
	}

	/**
	 * Validates rules for loading the Edit project template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_edit_project'
	 *
	 */
	private function pre_load() {

		appthemes_require_login();

		$project_id = (int) get_query_var( 'project_edit' );

		if ( ! current_user_can( 'edit_post', $project_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You cannot edit this Project', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_edit_project', APP_Notices::$notices, $project_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function template_include( $path ) {

		if ( ! $this->pre_load() ) {
			wp_redirect( get_permalink() );
			exit;
		}

		$project_id = (int) get_query_var( 'project_edit' );

		// setup dynamic checkout for editing a project
		appthemes_setup_checkout( 'chk-edit-project', get_the_hrb_project_edit_url( $project_id ) );

		$found = appthemes_process_checkout( 'chk-edit-project' );
		if ( ! $found ) {
			return locate_template( '404.php' );
		}
		return locate_template( 'create-project.php' );
	}

	function title_parts( $parts ) {
		$project_id = (int) get_query_var( 'project_edit' );

		return array( sprintf( __( 'Edit Project :: %s', APP_TD ), get_the_title( $project_id ) ) );
	}

	function body_class( $classes ) {
		$classes[] = 'app-project-edit';
		return $classes;
	}

}

/**
 * View for relisting a project.
 */
class HRB_Project_Relist extends HRB_Project_Edit {

	function init() {
		global $wp, $hrb_options;

		$wp->add_query_var('project_relist');

		$project_permalink = $hrb_options->project_permalink;
		$renew_permalink = $hrb_options->renew_project_permalink;

		appthemes_add_rewrite_rule( $project_permalink . '/' . $renew_permalink . '/(\d+)/?$', array(
			'project_relist' => '$matches[1]',
			'project_id' => '$matches[1]'
		) );
	}

	function condition() {
		return (bool) get_query_var('project_relist');
	}

	/**
	 * Validates rules for loading the Relist project template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_relist_project'
	 *
	 */
	private function pre_load() {

		// skip validation on the following steps since the project status will change
		if ( ! empty( $_GET['step'] ) ) {
			return true;
		}

		appthemes_require_login();

		$project_id = (int) get_query_var('project_relist');

		if ( ! current_user_can( 'relist_post', $project_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You don\'t have permissions to relist this project.', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_relist_project', APP_Notices::$notices, $project_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
		return true;
	}

	function template_include( $path ) {

		if ( ! $this->pre_load() ) {
			wp_redirect( get_permalink() );
			exit;
		}

		$project_id = (int) get_query_var('project_relist');

		appthemes_setup_checkout( 'chk-renew-project', get_the_hrb_project_relist_url( $project_id ) );

		$checkout = appthemes_get_checkout();

		$checkout->add_data( 'project', get_post() );
		$checkout->add_data( 'relist', 1 );

		$step_found = appthemes_process_checkout( 'chk-renew-project' );

		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}
		return locate_template( 'create-project.php' );
	}

	function body_class( $classes ) {
		$classes[] = 'app-project-relist';
		return $classes;
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Relist Project :: %s', APP_TD ), get_the_title() ) );
	}

}

/**
 * View for projects archive listings.
 */
class HRB_Project_Archive extends APP_View {

	function condition() {
		return is_post_type_archive( HRB_PROJECTS_PTYPE ) && ! is_tax() && ! is_admin();
	}

	function parse_query( $wp_query ) {
		global $hrb_options;

		$wp_query->set( 'post_type', HRB_PROJECTS_PTYPE );
		$wp_query->set( 'post_status', 'publish' );
		$wp_query->set( 'posts_per_page', $hrb_options->projects_per_page );

		if ( '' == $wp_query->get( 'order' ) ) {
			$wp_query->set( 'order', 'asc' );
		}

		$orderby = $wp_query->get( 'orderby', 'default' );
		$wp_query->set( 'hrb_orderby', $orderby );

		switch( $orderby ) {
			case 'popularity':
				$wp_query->set( 'orderby', 'meta_value_num' );
				$wp_query->set( 'order', 'DESC' );
				$wp_query->set( 'meta_query', array(
					'relation' => 'OR',
					array(
						'key' => APP_BIDS_P_BIDS_KEY,
						'compare' => 'EXISTS'
					),
					array(
						'key' => APP_BIDS_P_BIDS_KEY,
						'value' => '',
						'compare' => 'NOT EXISTS'
					)
				) );
				break;
			case 'expiring':
				$wp_query->set( 'meta_key', '_hrb_duration' );
				$wp_query->set( 'meta_compare', '>' );
				$wp_query->set( 'meta_value', 0 );
				$wp_query->set( 'orderby', array( 'meta_value_num' => 'ASC', 'date' => 'ASC' ) );
				break;
			case 'budget':
				$wp_query->set( 'meta_key', '_hrb_budget_price' );
				$wp_query->set( 'orderby', 'meta_value_num' );
				$wp_query->set( 'order', 'desc' );
				break;
			case 'rand':
				$wp_query->set( 'orderby', 'rand' );
				break;
			case 'title':
				$wp_query->set( 'orderby', 'title' );
				break;
			case 'urgent':
				$wp_query->set( 'meta_key', HRB_ITEM_URGENT );
				$wp_query->set( 'meta_compare', '>' );
				$wp_query->set( 'meta_value', 0 );
				$wp_query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'date' => 'ASC' ) );
				break;
			case 'newest':
			default:
				// always default to urgent/date sorting if the current archive is not a taxonomy listing
				// and the project is not featured in a category
				if ( is_tax( HRB_PROJECTS_CATEGORY ) ) {
					$wp_query->set( 'meta_key', HRB_ITEM_FEATURED_CAT );
					$wp_query->set( 'orderby', array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ) );
				} else {
					$wp_query->set( 'orderby', 'date' );
					$wp_query->set( 'order', 'desc' );
				}
				break;
		}

		// taxonomy refine filter

		foreach( array( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_SKILLS ) as $taxonomy ) {

			if ( isset( $_GET['cat_' . $taxonomy] ) ) {

				$wp_query->set( 'tax_query', array(
					array(
						'taxonomy' => $taxonomy,
							'terms' => $_GET['cat_' . $taxonomy],
					)
				) );

			}
		}

		// refine location filter

		if ( ! empty( $_GET['search_location'] ) ) {

			// exclude featured if not included in the results
			if ( HRB_ITEM_FEATURED_HOME == $wp_query->get('meta_key') ) {
				$wp_query->set( 'meta_key', '' );
				$wp_query->set( 'meta_value', '' );
			}

			$locations = array_map( 'sanitize_text_field', $_GET['search_location'] );

			if ( in_array( 'remote', $locations ) ) {

				$meta_query[] = array(
					'key' => '_hrb_location_type',
					'compare' => 'IN',
					'value' => 'remote',
				);
			}

			$diff_locations = array_diff( $locations, array('remote') );

			if ( ! empty( $diff_locations ) ) {

				$meta_query['relation'] = 'OR';

				if ( 'country' != $hrb_options->project_refine_search ) {
					$meta_query[] = array(
						'key' => '_hrb_location_master',
						'value' => '__LIKE_IN_PLACEHOLDER__',
					);
					// do an array pattern matching comparison
					$wp_query->set( 'hrb_like_in_strings', $diff_locations );
				} else {
					$meta_query[] = array(
						'key' => '_hrb_location_country',
						'compare' => 'IN',
						'value' => $diff_locations,
					);
				}

			}
			$wp_query->set( 'meta_query', $meta_query );
		}

		$wp_query->is_home = false;
		$wp_query->is_archive = true;
	}

	/**
	 * Retrieves the required vars for a project Archive template.
	 *
	 * @uses apply_filters() Calls 'hrb_projects_archive_template_vars'
	 *
	 */
	function template_vars() {
		global $wp_query;

		$template_vars = array(
			'projects' => $wp_query,
		);
		return apply_filters( 'hrb_projects_archive_template_vars', $template_vars );
	}

	function title_parts( $parts ) {
		return array( __( 'Buscar consultas', APP_TD ) );
	}

}

/**
 * View for taxonomy listings.
 */
class HRB_Project_Taxonomy extends HRB_Project_Archive {

	function condition() {
		return is_tax( array( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_TAG, HRB_PROJECTS_SKILLS ) );
	}

	function parse_query_after( $wp_query ) {

		$orderby = get_hrb_query_var( 'orderby', false );

		if ( $orderby == 'default' || empty( $orderby ) ) {
			$wp_query->set( 'meta_key', HRB_ITEM_FEATURED_CAT );
		}
	}

}

/**
 * View for the project search listings.
 */
class HRB_Project_Search extends HRB_Project_Archive {

	function init() {
		global $wp;

		$wp->add_query_var( 'ls' );
		$wp->add_query_var( 'refine_ls' );
		$wp->add_query_var( 'st' );
	}

	function condition() {
		return ( ( isset( $_GET['ls'] ) || isset( $_GET['refine_ls'] ) ) && ( isset( $_GET['st'] ) && $_GET['st'] == HRB_PROJECTS_PTYPE ) );
	}

	function parse_query( $wp_query ) {

		// inherit parent base query vars
		parent::parse_query( $wp_query );

		if ( get_query_var( 'refine_ls' ) ) {
			$wp_query->set( 'ls', get_query_var( 'refine_ls' ) );
		}

		$wp_query->set( 'ls', trim( get_query_var( 'ls' ) ) );
		$wp_query->set( 's', get_query_var( 'ls' ) );

		$wp_query->set( 'hrb_ls', get_query_var( 'ls' ) );

		$wp_query->is_search = true;
	}

}

/**
 * View for saved filters listings.
 */
class HRB_Project_Saved_Filter extends HRB_Project_Archive {

	function init() {
		add_action( 'appthemes_after_footer', array( $this, 'display_saved_filter_form' ) );
		add_action( 'wp_ajax_hrb_render_saved_filter', array( $this, 'display_saved_filter_modal' ) );
	}

	function condition() {
		return is_hrb_project_saveable_filter();
	}

	function validate() {

		if ( empty( $_POST ) || !isset( $_POST['action'] ) || !in_array( $_POST['action'], array( 'load-saved-filter', 'edit-saved-filter', 'delete-saved-filter' ) ) ) {
			return false;
		}

		wp_verify_nonce('hrb-save-filter');

		return true;
	}

	function parse_query( $wp_query ) {

		if ( ! $this->validate() ) {
			return;
		}

		$user_id = get_current_user_id();
		$saved_filters = hrb_get_user_saved_filters( $user_id );

		// look for a posted filter slug
		if ( ! empty( $_POST['saved-filter-slug'] ) ) {
			$slug = sanitize_text_field( $_POST['saved-filter-slug'] );
		}

		switch( $_POST['action'] ) {
			case 'delete-saved-filter':

				if ( empty( $slug) ) {
					return;
				}

				unset( $saved_filters[$slug] );
				$s_action = 'delete';
				break;

			case 'edit-saved-filter':

				parse_str( $_SERVER['QUERY_STRING'], $query_string );

				$name = sanitize_text_field( $_POST['saved-filter-name'] );
				$new_slug = sanitize_title( $name );
				if ( ! empty( $slug ) &&  $slug != $new_slug ) {
					unset( $saved_filters[ $slug ] );
				}

				$saved_filters[ $new_slug ] = array(
					'name' => $name ,
					'digest' => $_POST['saved-filter-digest'],
					'url' => $_SERVER['REQUEST_URI'],
					'params' => $query_string,
					'updated' => time(),
				);
				$s_action = 'save';

				break;

			default:

				// load saved filter
				if ( ! empty( $slug ) && ! empty( $saved_filters[ $slug ] ) ) {
					$s_search = $saved_filters[$slug];
					$url = $s_search['url'];
					$params = $s_search['params'];

					wp_redirect( add_query_arg( (array) $params, $url ) );
					exit;
				}

		}

		$option = hrb_get_prefixed_user_option('_saved_filters');

		// update the saved filters for the current user
		update_user_meta( $user_id, $option, $saved_filters );

		wp_redirect( add_query_arg( array( 'action' => $s_action ) ) );
		exit;
	}

	function display_saved_filter_form( $values = array(), $ajax = true ) {

		if ( ! $this->condition() && ! $ajax ) {
			return;
		}

		$defaults = array(
			'slug' => '',
			'name' => '',
			'digest' => '',
		);
		$data['saved_filter'] = wp_parse_args( $values, $defaults );

		appthemes_load_template( 'form-saved-filter.php', $data );
	}

	function display_saved_filter_modal() {
		$slug = sanitize_text_field( $_POST['search_slug'] );

		$saved_filters = hrb_get_user_saved_filters();

		$values = array();

		if ( ! empty( $saved_filters[ $slug ] ) ) {
			$values = array(
				'slug' => $slug,
				'name' => $saved_filters[$slug]['name'],
				'digest' => $saved_filters[$slug]['digest'],
			);
		}

		$this->display_saved_filter_form( $values, $ajax = true );
		die( 1 );
	}

	function template_redirect() {
		// enqueue required scripts/styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
	}

	function enqueue_styles_scripts() {
		hrb_register_enqueue_scripts( array( 'validate', 'app-saved-filter' ) );
	}

	function notices() {

		if ( isset( $_GET['action'] ) ) {

			if ( 'save' == $_GET['action'] ) {
				appthemes_display_notice( 'success', __( 'Filter Saved.', APP_TD ) );
			}

			elseif ( 'delete' == $_GET['action'] ) {
				appthemes_display_notice( 'success', __( 'Filter Deleted Successfully.', APP_TD ) );
			}
		}
	}

}

/**
 * Project Categories Page.
 * @todo: Incomplete
 */
class HRB_Project_Categories extends APP_View_Page {

	function __construct() {
		parent::__construct( 'categories-list-project.php', __( 'Categories', APP_TD ), array( 'internal_use_only' => true ) );
	}

	static function get_id() {
		return parent::_get_id( __CLASS__ );
	}

}


### Hooks Callbacks

/**
 * Ajax call for dynamically outputting sub-categories.
 */
function _hrb_output_subcategories() {

	$category = (int) $_POST['category'];
	$selected  = (int) $_POST['selected'];

	$args = array(
		'hide_empty' => false,
		'parent' => $category,
	);

	$html = html( 'option', array( 'value' => '' ), __( '- Select Sub-Category -', APP_TD ) );

	foreach( get_terms( HRB_PROJECTS_CATEGORY, $args ) as $sub_cat ) {

		$atts = array(
			'value' => $sub_cat->term_id,
		);

		if ( $selected == $sub_cat->term_id ) {
			$atts['selected'] = 'selected';
		}

		$html .= html( 'option', $atts, $sub_cat->name );
	}

	echo $html;
	die(1);
}