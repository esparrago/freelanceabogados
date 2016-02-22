<?php
/**
 * Views for user related pages.
 *
 * Views prepare and provide data to the page requested by the user.
 */

add_action( 'wp_redirect', array( 'HRB_Edit_Profile', 'maybe_redirect_on_update' ) );

/**
 * View used for filtering and paginating users listings.
 */
class HRB_Users_Listings extends APP_View {

	function init() {
		add_action( 'pre_user_query', array( __CLASS__, 'filter_and_paginate_users' ), 10 );
	}

	function condition() {
		return true;
	}

	/**
	 * Apply filters and pagination to user listings.
	 */
	static function filter_and_paginate_users( $user_query ) {

		// sort/order users
		$orderby = $user_query->get('hrb_orderby');

		switch( $orderby ) {
			case 'avg_review':
				$meta_key = hrb_get_prefixed_user_option( APP_REVIEWS_U_AVG_KEY );

				$query_vars = array(
					'meta_key' => $meta_key,
					'orderby' => 'meta_value',
					'order' => 'desc',
					'meta_query' => array(
						'relation' => 'OR',
						array(
							'key' => $meta_key,
							'compare' => 'EXISTS'
						),
						array(
							'key' => $meta_key,
							'value' => '',
							'compare' => 'NOT EXISTS'
						)
					)
				);
				break;

			case 'newest':
				$query_vars = array(
					'orderby' => 'user_registered',
					'order' => 'desc',
				);
				break;

			case 'rate':
			case 'default':
			default:
				$query_vars = array(
					'meta_key' => hrb_get_prefixed_user_option( APP_REVIEWS_U_REL_KEY ),
					'orderby' => 'meta_value',
					'order' => 'desc',
				);
				break;

		}

		if ( $user_query->get('hrb_order') ) {
			$query_vars['order'] = $user_query->get('hrb_order');
		}

		$user_query->query_vars = array_merge( $user_query->query_vars, $query_vars );

		// add pagination
		if ( ! $user_query->get('offset') && get_query_var('paged') > 1 ) {
			$offset = $user_query->get( 'number' ) * ( get_query_var( 'paged' ) - 1 );
			$user_query->set( 'offset', $offset );
		}

	}

}

/**
 * View for the users archive page.
 */
class HRB_Users_Archive extends APP_View {

	function init() {
		$this->add_rewrite_rules();

		add_action( 'hrb_sidebar_refine_search_hidden', array( $this, 'add_st_query_var' ), 10 );
		add_action( 'pre_user_query', array( $this, 'pre_user_query' ), 12 );
	}

	function condition() {
		return (bool) get_query_var( 'archive_freelancer' );
	}

	private function add_rewrite_rules() {
		global $wp, $hrb_options;

		$wp->add_query_var('archive_freelancer');

		appthemes_add_rewrite_rule( trailingslashit( $hrb_options->user_permalink ) . '?$', array(
			'archive_freelancer' => 1,
			'st' => HRB_FREELANCER_UTYPE,
		) );

		appthemes_add_rewrite_rule( trailingslashit( $hrb_options->user_permalink ) . 'page/([0-9]{1,})/?$', array(
			'archive_freelancer' => 1,
			'st' => HRB_FREELANCER_UTYPE,
			'paged' => '$matches[1]',
		) );
	}

	function parse_query( $wp_query ) {
		$wp_query->is_home = false;
		$wp_query->is_404 = false;
		$wp_query->is_archive = true;
		$wp_query->set( 'is_hrb_archive_users', true );

		$orderby = $wp_query->get('orderby');
		$wp_query->set( 'hrb_orderby', $orderby );
	}

	/**
	 * Make sure we're passing the user post type to the filter form when displaying users archives
	 */
	function add_st_query_var() {
		if ( $this->condition() && ! isset( $_REQUEST['st'] ) ) {
			_appthemes_form_serialize( HRB_FREELANCER_UTYPE, 'st' );
		}
	}

	/*
	 * Alter the user query to apply filters
	 */
	function pre_user_query( $user_query ) {

		if ( ! $this->condition() || $user_query->get('hrb_ignore_filters') ) {
			return;
		}

		$this->filter_users_by_tax( $user_query );
		$this->filter_users_by_country( $user_query );
	}

	/**
	 * Filter users by taxonomy terms
	 */
	function filter_users_by_tax( $user_query ) {

		$terms = array();

		$allowed_taxonomies = array( HRB_PROJECTS_SKILLS );

		foreach( $allowed_taxonomies as $taxonomy ) {
			if ( isset( $_GET["cat_{$taxonomy}"] ) ) {
				$terms = array_map( 'sanitize_text_field', $_GET["cat_{$taxonomy}"] );
			}
		}

		if ( empty( $terms ) ) {
			return;
		}

		$query_vars = array(
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'key'		=> 'hrb_user_skills',
					'value'		=> $terms,
					'compare'	=> 'IN',
					'type'		=> 'NUMERIC',
				),
			),
		);
		$user_query->query_vars = array_merge( $user_query->query_vars, $query_vars );

		// make sure we return distinct users, since user skills are stored in rows
		$user_query->query_fields = 'DISTINCT ' . $user_query->query_fields;
	}

	/**
	 * Filter users by country
	 */
	function filter_users_by_country( $user_query ) {
		global $hrb_options;

		// skip if there's no filters set or the location meta query as been already set
		if ( ! isset( $_GET['search_location'] ) || ! empty( $user_query->query_vars['hrb_location_meta_query']) ) {
			return;
		}

		$locations = array_map( 'sanitize_text_field', $_GET['search_location'] );

		if ( 'country' != $hrb_options->user_refine_search ) {
			$meta_query = array(
				'key' => 'hrb_location_master',
				'value' => '__LIKE_IN_PLACEHOLDER__',
			);

			// do an array pattern matching comparison
			$user_query->query_vars['hrb_like_in_strings'] = $locations;
		} else {
			$meta_query = array(
				'key' => 'hrb_location_country',
				'compare' => 'IN',
				'value' => $locations,
			);
		}

		$user_query->query_vars['hrb_location_meta_query'] = 1;

		$user_query->query_vars['meta_query'][] = $meta_query;
	}

	/**
	 * Retrieves the required vars for a Users archive template.
	 *
	 * @uses apply_filters() Calls 'hrb_users_archive_template_vars'
	 *
	 */
	function template_vars() {
		global $wp_query;

		$order = $wp_query->get('order');
		$query_vars['hrb_order'] = $order;

		$orderby = $wp_query->get('orderby');
		$query_vars['hrb_orderby'] = $orderby;

		$template_vars = array(
			'users' => hrb_get_freelancers( $query_vars ),
		);

		return apply_filters( 'hrb_users_archive_template_vars', $template_vars );
	}

	function template_redirect() {
		// make sure 'wp_title' works with SEO plugins like YOAST SEO by skipping any customizations since the users archive is not a post type archive
		// but is instead a custom listing that retrieves users - YOAST SEO would return only the site name for the user listings title
		remove_filter( 'wp_title', 'appthemes_title_tag', 9 );
		add_filter( 'wp_title', 'appthemes_title_tag', 99 );
	}

	function template_include( $template ) {
		return locate_template( 'archive-' . HRB_FREELANCER_UTYPE . '.php' );
	}

	function title_parts( $parts ) {
		return array( __( 'Buscar abogados', APP_TD ) );
	}

}

/**
 * View for the users search page.
 */
class HRB_Users_Search extends HRB_Users_Archive {

	function init() {
		global $wp;

		add_action( 'pre_user_query', array( $this, 'search_freelancers' ), 20 );

		$wp->add_query_var( 'ls' );
		$wp->add_query_var( 'refine_ls' );
		$wp->add_query_var( 'st' );
	}

	function condition() {
		return ( ( isset( $_GET['ls'] ) || isset( $_GET['refine_ls'] ) )&& ( isset( $_GET['st'] ) && $_GET['st'] == HRB_FREELANCER_UTYPE ) );
	}

	function parse_query( $wp_query ) {

		if ( get_query_var( 'refine_ls' ) ) {
			$wp_query->set( 'ls', get_query_var( 'refine_ls' ) );
		}

		$wp_query->set( 'ls', trim( get_query_var( 'ls' ) ) );
		$wp_query->set( 's', get_query_var( 'ls' ) );

		$wp_query->set( 'hrb_st', get_query_var( 'st' ) );
		$wp_query->set( 'hrb_ls', get_query_var( 'ls' ) );
	}

	function search_freelancers( $wp_user_query ) {

		if ( ! $this->condition() || ! $wp_user_query->get('hrb_list_users') || $wp_user_query->get('hrb_ignore_filters') ) {
			return;
		}
		$wp_user_query->set( 'search', '*' . get_query_var( 'ls' ) . '*' );
	}

	function template_redirect() {
		global $wp_query;

		// inherit parent base query vars
		parent::parse_query( $wp_query );

		$wp_query->is_search = true;
	}

}

/**
 * View for the user profile page.
 */
class HRB_User_Profile extends APP_View {

	protected static $user;

	function init() {
		global $wp;

		$wp->add_query_var( 'profile_author' );

		$this->add_rewrite_rules();
	}

	private function add_rewrite_rules() {
		global $hrb_options;

		$permalink = $hrb_options->profile_permalink;

		appthemes_add_rewrite_rule( $permalink . '/(.*?)/?$', array(
			'profile_author' => '$matches[1]'
		) );

		appthemes_add_rewrite_rule( 'author/(.*?)/?$', array(
			'profile_author' => '$matches[1]'
		) );
	}

	function condition() {
		return (bool) get_query_var( 'profile_author' );
	}

	function parse_query( $wp_query ) {

		appthemes_require_login();

		$user_id = $wp_query->get( 'profile_author' );

		if ( intval( $user_id ) ) {
			self::$user = get_userdata( $user_id );
		} else {
			self::$user = get_user_by( 'slug', $user_id );
		}

		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'profile_author' => self::$user,
		) );

		$wp_query->is_home = false;
	}

	/**
	 * Retrieves the required vars for a User Profile template.
	 *
	 * @uses apply_filters() Calls 'hrb_user_profile_template_vars'
	 *
	 */
	function template_vars() {

		$projects_owned = get_the_hrb_user_projects( self::$user );
		$projects_participant = hrb_p2p_get_participating_posts( self::$user->ID, array( 'connected_meta' => array( 'type' => array( 'worker' ) ), 'post_status' => HRB_PROJECT_STATUS_WORKING ) );
		$reviews = hrb_get_user_reviews( self::$user->ID );
		$posts = hrb_get_user_posts( self::$user );

		$template_vars = array(
			'projects_owned' => $projects_owned,
			'projects_participant' => $projects_participant,
			'reviews' => $reviews,
			'user_posts' => $posts,
		);

		return apply_filters( 'hrb_user_profile_template_vars', $template_vars );
	}

	function template_include( $path ) {
		appthemes_add_template_var( $this->template_vars() );

		return locate_template( 'profile.php' );
	}

	function title_parts( $parts ) {
		return array( sprintf( __( '%s Profile', APP_TD ), self::$user->display_name ) );
	}

	function body_class( $classes ) {
		$classes[] = 'app-profile';
		return $classes;
	}

}

/**
 * Edit Profile page.
 */
class HRB_Edit_Profile extends APP_User_Profile {

	private static $_this;

	function __construct() {
		self::$_this = $this;

		parent::__construct();

		// avoid duplicating the update action
		add_action( 'user_profile_update_errors', array( $this, 'update_errors' ) );
		remove_action( 'init', array( self::$_this, 'update' ) );

		add_action( 'personal_options_update', 'appthemes_handle_user_media_upload' );
	}

    function condition() {
        return parent::get_id();
    }

	/**
	 * Retrieves the required vars for the Edit profile template.
	 *
	 * @uses apply_filters() Calls 'hrb_edit_profile_template_vars'
	 * @uses apply_filters() Calls 'show_password_fields'
	 *
	 */
    function template_vars() {
        $template_vars = array(
          'current_user' => wp_get_current_user(), // grabs the user info and puts into vars
           'show_password_fields' => apply_filters( 'show_password_fields', true ),
			'dashboard_user' => wp_get_current_user(),
        );
        return apply_filters( 'hrb_edit_profile_template_vars' , $template_vars );
    }

	function template_redirect() {
        // WordPress Template Administration API - required to use wp_terms_checklist()
        require_once(ABSPATH . 'wp-admin/includes/template.php');

		// enqueue required scripts/styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
	}

	function enqueue_styles_scripts() {

		// enqeue required styles/scripts
		hrb_register_enqueue_scripts( array( 'hrb-user-edit', 'user-profile', 'validate' ) );

		appthemes_enqueue_media_manager( array( 'user_id' => 'gravar_user_id' ) );

        // also enqueue geo scripts if supported
		hrb_maybe_enqueue_geo();

		wp_localize_script( 'user-profile', 'pwsL10n', array(
			'empty'	=> __( 'Strength indicator', APP_TD ),
			'short' => __('Very weak', APP_TD ),
            'bad' => __('Weak', APP_TD ),
            'good' => __('Medium', APP_TD ),
            'strong' => __('Strong', APP_TD ),
            'mismatch' => __('Mismatch', APP_TD ),
		) );

	}

	static function maybe_redirect_on_update( $location ) {
		if ( ! is_admin() && ! empty( $_POST['update_profile'] ) && ! empty( $_GET['redirect_url'] ) ) {
			return $_GET['redirect_url'];
		}
		return $location;
	}

	public function update_errors( $errors ) {

		foreach ( $errors->errors as $key => $error ) {
			appthemes_add_notice( $key, $error[0] );
		}

	}

}



### Extended User Meta Box Classes

/**
 * Extends the user meta box class to provide additional 'freelancer' specific fields in the user profile.
 */
class HRB_Edit_Profile_Extra_Meta_Box extends APP_User_Meta_Box {

	protected function condition() {
		return HRB_Edit_Profile::get_id();
	}

	public function __construct(){

		$args = array(
			'templates' => array('edit-profile.php'),
		);

		parent::__construct( 'app_profile_details', __( 'Additional Details', APP_TD ), $args );
	}

	/**
	 * Retrieves the additional fields that will be displayed in the View/Edit Profile template.
	 *
	 * @uses apply_filters() Calls 'hrb_profile_base_fields'
	 *
	 */
	public function form_fields() {
		global $hrb_options;

		$all = ! $hrb_options->restrict_user_currencies;

		foreach ( hrb_get_currencies( $all ) as $key => $currency ) {
			$currencies[ $key ] = $currency['name'];
		}

		$fields = array(
			array(
				'title' => __( 'Your Currency', APP_TD ),
				'type'  => 'select',
				'name'  => 'hrb_currency',
				'choices' => $currencies,
			),
			array(
				'title' => __( 'Rate per Hour', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_rate',
				'extra' => array ( 'size' => 3, 'class' => 'short-field' ),
				'desc'  => __( 'p/ Hour', APP_TD ),
			),
			array(
				'title' => __( 'Skills', APP_TD ),
				'type'  => 'custom',
				// name used for easier handling since terms are stored in 'hrb_user_skills'
				'name'  => 'tax_input['.HRB_PROJECTS_SKILLS.']',
				'render'  => array( $this, 'render_skills' ),
			),
		);

		if ( ! $hrb_options->local_users ) {

			$location = array(
				'title' => __( 'Location', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_location',
				'desc'  => __( 'Your Location', APP_TD ),
				'extra' => array( 'id' => 'location', 'data-geo' => 'formatted_address', 'class' => 'regular-text' ),
			);
			array_unshift( $fields , $location );

		}
		return apply_filters( 'hrb_profile_base_fields', $fields );
	}

	function render_skills() {

		$sel_terms = get_user_meta( $this->user_id, 'hrb_user_skills' );

		$atts = array(
			'selected_cats' => $sel_terms,
			'taxonomy'		=> HRB_PROJECTS_SKILLS,
			'checked_ontop' => false,
			'walker'		=> new HRB_Multi_Category_Walker( array( 'disabled' => false ) ),
		);
		ob_start();

		wp_terms_checklist( 0, $atts );

		return ob_get_clean();
	}

	protected function validate_fields_data( $to_update, $form_fields, $user_id ) {

		$hidden_keys = array();

		// others location fields / meta keys
		foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
			$meta_key = "hrb_location_{$location_att}";
			$hidden_keys[] = $meta_key;
		}

		$form_fields = array_merge( $form_fields, $hidden_keys );

		parent::validate_fields_data( $hidden_keys, $form_fields, $user_id) ;
	}

	public function before_save( $to_update, $user_id ) {

		// process and update the user skills meta
		if ( ! empty( $_POST['tax_input'] ) ) {

			$skills = $_POST['tax_input'][ HRB_PROJECTS_SKILLS ];
			$skills = array_map( 'intval', $skills );

			delete_user_meta( $user_id, 'hrb_user_skills' );

			foreach( $skills as $skill ) {
				add_user_meta( $user_id, 'hrb_user_skills', $skill );
			}

		}

		// process and update the user skills meta
		if ( ! empty( $_POST['hrb_location'] ) ) {

			$keys = array();

			// others location fields / meta keys
			foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
				$meta_key = "hrb_location_{$location_att}";
				$keys[] = $meta_key;
			}

			$values = wp_array_slice_assoc( $_POST, $keys );
			$values = array_map( 'sanitize_text_field', $values );

			foreach( $keys as $key ) {
				$to_update[ $key ] = $values[ $key ];
			}

			// stores the main location atts on a master meta key
			$master_atts = hrb_get_geocomplete_master_attributes();

			$to_update['hrb_location_master'] = $to_update['hrb_location'];

			foreach( $master_atts as $att ) {
				if ( ! empty( $to_update[ "hrb_location_{$att}" ] ) ) {
					$to_update['hrb_location_master'] .= '|' . $to_update[ "hrb_location_{$att}" ];
				}
			}

		}
		return $to_update;
	}

	// output additional HTML markup
	public function after_form( $user ) {

		foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
			$meta_key = "hrb_location_{$location_att}";
			echo html( 'input', array( 'type' => 'hidden', 'name' => esc_attr( $meta_key ), 'data-geo' => esc_attr( $location_att ), 'value' => esc_attr( get_user_meta( $user->ID, $meta_key, true ) ) ) );
		}

	}

}

/**
 * Extends the user meta box class to provide additional Social related fields in the user profile.
 */
class HRB_Edit_Profile_Social_Meta_Box extends APP_User_Meta_Box {

	// Additional checks before registering the metabox
	protected function condition() {
		return HRB_Edit_Profile::get_id();
	}

	public function __construct(){

		$args = array(
			'templates' => array('edit-profile.php'),
		);

		parent::__construct( 'app_profile_social', __( 'Social Details', APP_TD ), $args );
	}

	/**
	 * Retrieves the additional social fields that will be displayed in the View/Edit Profile template.
	 *
	 * @uses apply_filters() Calls 'hrb_profile_social_fields'
	 *
	 */
	public function form_fields() {

		$fields = array(
			array(
				'title' => __( 'Public Email', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_email',
				'extra' => array(
					'class' => 'required important-field regular-text',
				),
				'desc'  => __( 'Your public email (shared only with project participants)', APP_TD ),
			),
			array(
				'title' => __( 'LinkedIn URL', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_linkedin',
				'desc'  => __( 'Your LinkedIn ID', APP_TD ),
			),
			array(
				'title' => __( 'Twittter ', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_twitter',
				'desc'  => __( 'Your Twitter URL', APP_TD ),
			),
			array(
				'title' => __( 'Facebook ', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_facebook',
				'desc'  => __( 'Your Facebook URL', APP_TD ),
			),
		);
		return apply_filters( 'hrb_profile_social_fields', $fields );
	}
}

/**
 * Extends the user meta box class to provide additional Account related fields in the user profile.
 */
class HRB_Edit_Profile_Account_Meta_Box extends APP_User_Meta_Box {

	// Additional checks before registering the metabox
	protected function condition() {
		return current_user_can('manage_options') && HRB_Edit_Profile::get_id();
	}

	public function __construct(){

		$args = array(
			'templates' => array('edit-profile.php'),
		);

		parent::__construct( 'app_profile_account', __( 'Account', APP_TD ), $args );
	}

	public function display( $user ) {

		// only display credits balance to freelancers
		if ( ! user_can( $user->ID, 'edit_bids' ) ) {
			return;
		}
		parent::display( $user );

	}

	/**
	 * Retrieves the additional account fields that will be displayed in the View/Edit Profile template.
	 *
	 * @uses apply_filters() Calls 'hrb_profile_account_fields'
	 *
	 */
	public function form_fields() {
		$fields = array(
			array(
				'title' => __( 'Balance', APP_TD ),
				'type'  => 'text',
				'name'  => 'hrb_credits',
				'desc'  => __( 'Credits', APP_TD ),
				'extra' => array( 'size' => '5', 'class' => 'short-field' ),
			),
		);
		return apply_filters( 'hrb_profile_account_fields', $fields );
	}
}
