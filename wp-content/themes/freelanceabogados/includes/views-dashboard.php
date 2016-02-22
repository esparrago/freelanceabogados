<?php
/**
 * Views for dashboard related pages.
 *
 * Views prepare and provide data to the page requested by the user.
 *
 */

/**
 * Base class for dashboard Views.
 */
class HRB_User_Dashboard extends APP_View {

	protected static $dashboard;
	protected static $dashboard_user;

	function init() {
		global $wp;

		$wp->add_query_var('dashboard');
		$wp->add_query_var('filter_posts_per_page');

		$this->add_rewrite_rules();
	}

	function condition() {
		return (bool) get_query_var('dashboard');
	}

	function parse_query( $wp_query ) {
		$wp_query->is_home = false;
		$wp_query->is_single = false;
		$wp_query->is_archive = false;

		$wp_query->set( 'hrb_apply_filter_reviews', true );
	}

	function get_pagination() {
		global $wp_query, $hrb_options;

		if ( ! $posts_per_page = $wp_query->get('filter_posts_per_page') ) {
			$posts_per_page = $hrb_options->projects_per_page;
		}

		$args = array(
			'posts_per_page' => $posts_per_page,
			'paged' => $wp_query->get('paged'),
		);

		$wp_query->set( 'hrb_filter_posts_per_page', $posts_per_page );
		$wp_query->set( 'posts_per_page', $posts_per_page );

		return $args;
	}

	private function add_rewrite_rules() {
		global $hrb_options;

		$dashboard_permalink = $hrb_options->dashboard_permalink;

		$all_permalinks = hrb_get_dashboard_permalinks();

		// ignore the workspace and reviews permalink as these use their own Views
		unset( $all_permalinks['workspace'] );
		unset( $all_permalinks['review'] );

		$dashboard_all_permalinks = implode( '?|', $all_permalinks );

		### Dashboard permalink

		appthemes_add_rewrite_rule( $dashboard_permalink . '/?$', array(
			'dashboard' => $dashboard_permalink,
		) );

		appthemes_add_rewrite_rule( $dashboard_permalink . '/page/([0-9]+)/?$', array(
			'dashboard' => $dashboard_permalink,
			'paged' => '$matches[1]',
		) );

		### Dashboard descendant permalinks

		appthemes_add_rewrite_rule( $dashboard_permalink . '/(' . $dashboard_all_permalinks . '?)/?$', array(
			'dashboard' => '$matches[1]',
		) );
		appthemes_add_rewrite_rule( $dashboard_permalink . '/(' . $dashboard_all_permalinks . '?)/?page/([0-9]+)/?$', array(
			'dashboard' => '$matches[1]',
			'paged' => '$matches[2]',
		) );

	}

	/**
	 * Retrieves the core vars used in all dashboard templates.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_template_vars'
	 *
	 */
	function template_vars() {

		$dashboard_user = wp_get_current_user();
		$dashboard_name = hrb_get_dashboard_name();
		$dashboard_type = hrb_get_dashboard_page();

		self::$dashboard_user = $dashboard_user;

		$template_vars = array(
			'dashboard_user'	=> $dashboard_user,
			'dashboard_type'	=> $dashboard_type,
			'dashboard_template'=> "dashboard-{$dashboard_type}.php",
			'dashboard_name'	=> $dashboard_name,
			'dashboard_title'	=> sprintf( __( "%s", APP_TD ), $dashboard_name ),
		);
		return apply_filters( 'hrb_dashboard_template_vars', $template_vars );
	}

	function template_include( $path ) {
		return locate_template('dashboard.php');
	}

	function template_redirect() {
		global $wp_query;

		$wp_query->is_404 = false;

		add_filter( 'wp_title', array( $this, 'title' ), 0 );
		add_filter( 'body_class', array( $this, 'body_class' ), 0 );
	}

	function body_class( $classes ) {
		$classes[] = 'hrb-dashboard';
		$classes[] = 'hrb-dashboard-' . self::$dashboard['dashboard_type'];

		return $classes;
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}

}

/**
 * Main/front dashboard View.
 */
class HRB_User_Dashboard_Main extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var('filter_notify_type');
	}

	function condition() {
		return ( 'front' == hrb_get_dashboard_page() );
	}

	/**
	 * Retrieves the required vars for the dashboard Main template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_main_template_vars'
	 *
	 */
	function template_vars() {

		// latest user activity
		$args = array(
			'limit' => 10,
		);
		$activity = appthemes_get_notifications( self::$dashboard_user->ID, $args );

		$template_vars = array(
			'activity' => $activity,
		);
		return apply_filters( 'hrb_dashboard_main_template_vars', $template_vars );
	}

}

/**
 * Dashboard notifications View.
 */
class HRB_User_Dashboard_Notifications extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var('filter_notify_type');
	}

	function condition() {
		return ( 'notifications' == hrb_get_dashboard_page() );
	}

	function get_filters() {
		global $wp_query;

		$filters = array();

		$type = $wp_query->get('filter_notify_type');

		if ( $type && 'default' != $type ) {
			$filters['type'] = $type;
			$wp_query->set( 'hrb_filter_notify_type', $type );
		}
		return $filters;
	}

	function get_sorting() {
		global $wp_query;

		$sorting = array();

		$orderby = $wp_query->get('orderby');

		switch( $orderby ) {
			case 'oldest':
				$sorting['order'] = 'ASC';
				break;
		}
		$wp_query->set( 'hrb_orderby', $orderby );

		return $sorting;
	}

	function get_pagination() {

		$pagination = parent::get_pagination();

		$args = array(
			'limit' => $pagination['posts_per_page'],
			'offset' => ( max( 1, get_query_var('paged') ) - 1 ) * $pagination['posts_per_page'],
		);

		return $args;
	}

	/**
	 * Retrieves the required vars for the dashboard Notifications template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_notifications_template_vars'
	 *
	 */
	function template_vars() {

		$filters = $this->get_filters();
		$sorting = $this->get_sorting();
		$paginate = $this->get_pagination();

		$args = array_merge( $filters, $sorting, $paginate );

		$notifications = appthemes_get_notifications( self::$dashboard_user->ID, $args );
		$notifications_no_filters = appthemes_get_notifications( self::$dashboard_user->ID );

		// automatically mark all notifications as read on load
		foreach( $notifications->results as $notification ) {
			appthemes_set_notification_status( $notification->id, 'read' );
		}

		$template_vars = array(
			'notifications_no_filters' => $notifications_no_filters,
			'notifications' => $notifications,
		);

		return apply_filters( 'hrb_dashboard_notifications_template_vars', $template_vars );
	}

	function template_redirect() {
		// enqeue required scripts
		hrb_register_enqueue_scripts( array('app-dashboard') );

		add_filter( 'wp_title', array( $this, 'title' ), 0 );
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}
}

/**
 * Dashboard projects View.
 */
class HRB_User_Dashboard_Projects extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var('filter_status');
		$wp->add_query_var('filter_relation');
	}

	function condition() {
		return (bool) ( 'projects' == hrb_get_dashboard_page() || 'favorites' == hrb_get_dashboard_page() );
	}

	function get_filters() {
		global $wp_query;

		$filters = array();

		$filters['meta_query'] = array(
			'relation' => 'OR',
			array(
				'key' => '_hrb_archived',
				'value' => self::$dashboard_user->ID,
				'compare' => '!=',
			),
			array(
				'key' => '_hrb_archived',
				'value' => 'DUMMY',
				'compare' => 'NOT EXISTS',
			),
		);

		if ( $post_status = $wp_query->get('filter_status') ) {

			switch( $post_status ) {
				case HRB_PROJECT_META_STATUS_ARCHIVED:

					$filters['meta_query'] = array(
						array(
							'key' => '_hrb_archived',
							'value' => self::$dashboard_user->ID
						),
					);
					break;

				case 'default':
					break;

				default:
					$filters['post_status'] = $post_status;
					break;
			}

			$wp_query->set( 'hrb_filter_status', $post_status );
		}

		if ( $role = $wp_query->get('filter_relation') ) {

			switch( $role ) {
				case 'employer':
					$filters['author'] = self::$dashboard_user->ID;
					//$filters['assigned_to'] = null;
					break;

				case 'worker':
					$workspace = hrb_p2p_get_participant_workspaces( self::$dashboard_user->ID, array( 'connected_meta' => array( 'type' => $role ) ) )->posts;
					$filters['connected_type'] = HRB_P2P_WORKSPACES;
					$filters['connected_items'] = $workspace;
					//$filters['assigned_to'] = null;

				default:
					break;
			}

			$wp_query->set( 'hrb_filter_relation', $role );
		}
		return $filters;
	}

	function get_sorting() {
		global $wp_query;

		$sorting = array();

		$orderby = $wp_query->get( 'orderby' );

		switch( $orderby ) {
			case 'oldest':
				$sorting['orderby'] = 'date';
				$sorting['order'] = 'ASC';
				break;
		}

		$wp_query->set( 'hrb_orderby', $orderby );

		return $sorting;
	}

	/**
	 * Retrieves the required vars for the dashboard Projects template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_projects_template_vars'
	 *
	 */
	function template_vars() {

		$filters = $this->get_filters();
		$sorting = $this->get_sorting();
		$paginate = parent::get_pagination();

		$args = array_merge( $filters, $sorting, $paginate );

		$template_vars = array(
			'projects_no_filters' => hrb_get_dashboard_projects( array( 'nopaging' => true ) ),
			'projects' => hrb_get_dashboard_projects( $args ),
			'favorites' => hrb_get_favorited_projects(),
		);

		return apply_filters( 'hrb_dashboard_projects_template_vars', $template_vars );
	}

	function template_redirect() {
		// enqeue required scripts
		hrb_register_enqueue_scripts( array( 'app-dashboard' ) );

		add_filter( 'wp_title', array( $this, 'title' ), 0 );
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}
}

/**
 * Dashboard proposals View.
 */
class HRB_User_Dashboard_Proposals extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var('filter_status');
	}

	function condition() {
		return (bool) ( 'proposals' == hrb_get_dashboard_page() );
	}

	function get_filters() {
		global $wp_query;

		$filters = array();

		$status = $wp_query->get('filter_status');

		if ( ! $status ) {
			return $filters;
		}

		switch( $status ) {
			case HRB_PROPOSAL_STATUS_ACTIVE:

				$filters['meta_query'] = array(
					array(
						'key' => '_hrb_status',
						'value' => '',
						'compare' => 'NOT EXISTS',
					),
				);
				break;

			case 'default':
				// show all
				break;

			default:
				$filters['meta_key'] = '_hrb_status';
				$filters['meta_value'] = $status;
				break;
		}

		$wp_query->set( 'hrb_filter_status', $status );

		return $filters;
	}

	function get_sorting() {
		global $wp_query;

		$sorting = array();

		$orderby = $wp_query->get('orderby');

		switch( $orderby ) {
			case 'oldest':
				$sorting['orderby'] = 'comment_date_gmt';
				$sorting['order'] = 'ASC';
				break;
			default:
				// sort by featured and date
				$sorting = array(
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
		}

		$wp_query->set( 'hrb_orderby', $orderby );

		return $sorting;
	}

	function get_pagination() {

		$pagination = parent::get_pagination();

		$args = array(
			'number' => $pagination['posts_per_page'],
			'offset' => ( max( 1, get_query_var('paged') ) - 1 ) * $pagination['posts_per_page'],
		);
		return $args;
	}

	/**
	 * Retrieves the required vars for the dashboard Proposals template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_proposals_template_vars'
	 *
	 */
	function template_vars() {

		$filters = $this->get_filters();
		$sorting = $this->get_sorting();
		$paginate = $this->get_pagination();

		$args = array_merge( $filters, $sorting, $paginate );

		$project_id = get_queried_object_id();

		// retrieve found bids
		$args['count'] = true;

		$proposals = hrb_get_dashboard_proposals( $project_id, $args );
		$proposals_no_filters = hrb_get_dashboard_proposals( $project_id );

		if ( HRB_PROJECTS_PTYPE != get_post_type( $project_id ) ) {
			$project_id = 0;
		}

		$template_vars = array(
			'proposals' => $proposals['results'],
			'proposals_no_filters' => $proposals_no_filters['results'],
			'proposals_found' => $proposals['found'],
			'project_id' => $project_id,
		);

		return apply_filters( 'hrb_dashboard_proposals_template_vars', $template_vars );
	}

	function template_redirect() {
		add_filter( 'wp_title', array( $this, 'title' ), 0 );
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}
}

/**
 * Dashboard payments View.
 */
class HRB_User_Dashboard_Payments extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var( 'filter_status' );
	}

	function condition() {
		return (bool) ( 'payments' == hrb_get_dashboard_page() );
	}

	function get_filters() {
		global $wp_query;

		$filters = array();

		if ( $wp_query->get( 'filter_status' ) ) {

			$post_status = $wp_query->get( 'filter_status' );
			$filters['post_status'] = $post_status;

			$wp_query->set( 'hrb_filter_status', $post_status );
		}

		return $filters;
	}

	function get_sorting() {
		global $wp_query;

		$sorting = array();

		$orderby = $wp_query->get( 'orderby' );

		switch( $orderby ) {
			case 'oldest':
				$sorting['orderby'] = 'date';
				$sorting['order'] = 'ASC';
				break;
		}

		$wp_query->set( 'hrb_orderby', $orderby );

		return $sorting;
	}

	/**
	 * Retrieves the required vars for the dashboard Payments template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_payments_template_vars'
	 *
	 */
	function template_vars() {

		$filters = $this->get_filters();
		$sorting = $this->get_sorting();
		$paginate = parent::get_pagination();

		$args = array_merge( $filters, $sorting, $paginate );

		$text = '';

		if ( hrb_required_credits_to('feature_proposal') ) {
			$text = __( 'feature', APP_TD );
		}

		if ( hrb_required_credits_to('send_proposal') ) {
			if ( $text ) {
				$text .= __( ' and ', APP_TD );
			}
			$text .= __( 'apply to', APP_TD );
		}

		$template_vars = array(
			'orders_no_filters' => hrb_get_orders_for_user( self::$dashboard_user->ID, array( 'nopaging' => true ) ),
			'orders' => hrb_get_orders_for_user( self::$dashboard_user->ID, $args ),
			'credits_for' => $text,
		);

		return apply_filters( 'hrb_dashboard_payments_template_vars', $template_vars );
	}

	function template_redirect() {
		add_filter( 'wp_title', array( $this, 'title' ), 0 );
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}
}

/**
 * Dashboard reviews View.
 */
class HRB_User_Dashboard_Reviews extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var( 'filter_review_relation' );
	}

	function condition() {
		return ( 'reviews' == hrb_get_dashboard_page() && !is_single() );
	}

	function parse_query( $wp_query ) {
		$wp_query->set( 'hrb_apply_filter_reviews', true );
	}

	function get_filters() {
		global $wp_query;

		$filters = array();

		$relation = $wp_query->get('filter_review_relation');

		if ( ! $relation ) {
			$relation = 'received';
		}

		if ( $relation && 'all' != $relation ) {
			$filters['filter_review_relation'] = $relation;
		}

		$wp_query->set( 'hrb_filter_review_relation', $relation );

		return $filters;
	}

	function get_sorting() {
		global $wp_query;

		$sorting = array();

		$orderby = $wp_query->get( 'orderby' );

		switch( $orderby ) {
			case 'oldest':
				$sorting['orderby'] = 'comment_date_gmt';
				$sorting['order'] = 'ASC';
				break;
		}

		$wp_query->set( 'hrb_orderby', $orderby );

		return $sorting;
	}

	/**
	 * Retrieves the required vars for the dashboard Reviews template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_reviews_template_vars'
	 *
	 */
	function template_vars() {

		$filters = $this->get_filters();
		$sorting = $this->get_sorting();
		$paginate = parent::get_pagination();

		$args = array_merge( $filters, $sorting, $paginate );

		$template_vars = array(
			'reviews_no_filters' => hrb_get_dashboard_reviews(),
			'reviews' => hrb_get_dashboard_reviews( $args ),
		);

		return apply_filters( 'hrb_dashboard_reviews_template_vars', $template_vars );
	}

	function template_redirect() {
		add_filter( 'wp_title', array( $this, 'title' ), 0 );
	}

	function title() {
		return __( 'Dashboard', APP_TD );
	}
}


### Agreement

/**
 * Dashboard agreement View.
 */
class HRB_User_Dashboard_Agreement extends HRB_User_Dashboard {

	protected static $proposal = '';

	function init() {
		global $wp;

		$wp->add_query_var( 'review_proposal' );
	}

	function condition() {
		return (bool) get_query_var( 'review_proposal' );
	}

	/**
	 * Validates rules for loading an Agreement template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_agreement'
	 *
	 */
	protected function pre_load() {

		$project = hrb_get_project( get_queried_object() );
		$proposal_id = (int) get_query_var( 'review_proposal' );

		if ( empty( self::$dashboard_user->hrb_email ) ) {
			$profile_link = html_link( add_query_arg( array( 'redirect_url' => urlencode( $_SERVER['REQUEST_URI'] ) ), appthemes_get_edit_profile_url() ), __( 'Update Profile', APP_TD ) );
			appthemes_add_notice( 'no-public-email', sprintf( __( 'You must provide a public email before selecting a candidate. %s.', APP_TD ), $profile_link ) );
			return false;
		}

		if ( HRB_PROPOSAL_STATUS_CANCELED == hrb_get_proposal_status( $proposal_id) ) {
			appthemes_add_notice( 'not-available', __( 'The proposal that you\'re trying to view was canceled. It\'s not available anymore.', APP_TD ) );
		}

		if ( ! current_user_can( 'view_agreement', $proposal_id ) ) {
			appthemes_add_notice( 'no-view-agreement', __( 'You\'re not a candidate for this Project.', APP_TD ) );
		}

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_agreement', APP_Notices::$notices, $project );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the required vars for the dashboard Proposal Agreement template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_agreement_template_vars'
	 *
	 */
	function template_vars() {

		$proposal_id = (int) get_query_var('review_proposal');
		$proposal = hrb_get_proposal( $proposal_id );

		// retrieve the candidate proposal
		$candidate = hrb_p2p_get_candidate( $proposal->get_post_ID(), $proposal->get_user_id() );

		$user_relation = ( self::$dashboard_user->ID == $proposal->project->post_author ? 'employer' : 'candidate' );

		if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
			// store the URL from previous page
			$return_url = wp_sanitize_redirect( $_SERVER['HTTP_REFERER'] );
			$return_url = wp_validate_redirect( $return_url, $default = get_the_hrb_project_proposals_url( $proposal->project->ID ) );
		} else {
			$return_url = get_the_hrb_project_proposals_url( $proposal->project->ID );
		}

		$template_vars = array(
			'title' => html_link( get_permalink(), get_the_title() ),
			'proposal' => $proposal,
			'candidate' => $candidate,
			'user_relation' => $user_relation,
			'user_can_select_proposal' => current_user_can( 'select_proposal', $proposal ),
			'user_can_edit_agreement' => current_user_can( 'edit_agreement', $proposal ),
			'user_can_edit_agreement_terms' => current_user_can( 'edit_agreement_terms', $proposal ),
			'return_url' => $return_url ,
		);

		return apply_filters( 'hrb_dashboard_agreement_template_vars', $template_vars );
	}

	function template_include( $template ) {

		if ( ! $this->pre_load() ) {
			wp_redirect( hrb_get_dashboard_url_for( 'projects' ) );
			exit;
		}

		return locate_template( 'dashboard-agreement.php' );
	}

	function template_redirect() {
		// enqeue required scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function enqueue_styles_scripts() {

		hrb_register_enqueue_scripts( array( 'validate', 'app-proposal-agreement' ) );

		if ( hrb_is_escrow_enabled() ) {
			$proposal_id = (int) get_query_var('review_proposal');
			$proposal = hrb_get_proposal( $proposal_id );

			if ( get_current_user_id() == $proposal->get_user_id() ) {
				$agreement_note = sprintf( __( 'You have reached an agreement.%1$sAfter clicking \'OK\' the employer will be notified to transfer the necessary funds to our escrow account.%1$sContinue?', APP_TD ), "\r\n\r\n" );
			} else {
				$agreement_note = sprintf( __( 'You have reached an agreement.%1$sAfter clicking \'OK\' you will need to transfer the necessary funds to our escrow account so work can start.%1$sContinue?', APP_TD ), "\r\n\r\n" );
			}
		} else {
			$agreement_note = sprintf( __( 'You have reached an agreement.%1$sClicking \'OK\' will start the Project immediately.%1$sContinue?', APP_TD ), "\r\n\r\n" );
		}

		wp_localize_script( 'app-proposal-agreement', 'app_agreement_i18n', array(
			'submit_for_approval_text' => __( 'Submit for Approval', APP_TD ),
			'submit_agreement_text' => __( 'Accept / Start Project', APP_TD ),
			'agreement_note' => $agreement_note,
			'delete_candidate_employer' => __( 'Deselect this candidate (the candidate proposal will remain selectable)?', APP_TD ),
			'delete_candidate_self' => __( 'Decline this Project?', APP_TD ),
			'decline_agreement_text' => __( 'Decline', APP_TD ),
			'terms_accept' => HRB_TERMS_ACCEPT,
			'terms_decline' => HRB_TERMS_DECLINE,
			'terms_propose' => HRB_TERMS_PROPOSE,
		) );

	}

	function title_parts( $parts ) {
		$proposal_id = (int) get_query_var('review_proposal');
		$proposal = hrb_get_proposal( $proposal_id );

		return array( sprintf( __( 'Proposal for "%s"', APP_TD ), $proposal->project->post_title ) );
	}

	function body_class( $classes ) {
		$classes[] = 'app-dashboard-agreement';
		return $classes;
	}

}


### Workspace

/**
 * Dashboard workspace View.
 */
class HRB_User_Dashboard_Workspace extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var('hash');

		$this->add_rewrite_rules();
	}

	function condition() {
		return ( 'workspace' == hrb_get_dashboard_page() && get_query_var('workspace') );
	}

	function parse_query( $wp_query ) {
		$wp_query->set( 'post_type', HRB_WORKSPACE_PTYPE );
		$wp_query->set( 'post__in', '' );
	}

	private function add_rewrite_rules() {
		global $hrb_options;

		$dashboard_permalink = $hrb_options->dashboard_permalink;
		$workspace_permalink = hrb_get_dashboard_permalinks( 'workspace' );

		$dash_workspace_permalink = $dashboard_permalink . '/' . $workspace_permalink;

		appthemes_add_rewrite_rule( $dash_workspace_permalink . '/([^/]+)/([^/]+)/?$', array(
			'dashboard' => $workspace_permalink,
			'workspace' => '$matches[2]',
		) );

	}

	/**
	 * Validates rules for loading a Workspace template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_workspace'
	 *
	 */
	protected function pre_load() {
		$workspace = get_queried_object();

		if ( ( ! get_query_var('hash') && ! current_user_can( 'review_workspace', $workspace->ID ) ) || ( get_query_var('hash') && hrb_get_workspace_hash( $workspace->ID ) != get_query_var('hash') ) ) {
			appthemes_add_notice( 'invalid-workspace', __( 'You\'re trying to access an invalid workspace.', APP_TD ) );
			return false;
		}

		if ( ! get_post( $workspace->ID ) ) {
			appthemes_add_notice( 'no-workspace', __( 'The workspace you tried to access is not valid or does not exist anymore.', APP_TD ) );
			hrb_clean_workspaces( $workspace->ID );
			return false;
		}

		if ( ! current_user_can( 'review_workspace', $workspace->ID ) && ! current_user_can( 'edit_workspace', $workspace->ID ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not participating on this Project.', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_workspace', APP_Notices::$notices, $workspace );
		if ( appthemes_get_notices() ) {
			return false;
		}

		if ( HRB_PROJECT_STATUS_WAITING_FUNDS == $workspace->post_status && hrb_workspace_is_pending_payment( $workspace->ID ) ) {
			appthemes_add_notice( 'waiting-funds', __( 'This workspace is waiting for funds. Work can only start after the funds have been transferred to the escrow account.', APP_TD ) );
			if ( self::$dashboard_user->ID == $workspace->post_author ) {
				appthemes_add_notice( 'transfer-funds', sprintf( __( '<a href="%s">Transfer funds now</a> to activate this workspace.', APP_TD ), hrb_get_workspace_transfer_funds_url( $workspace->ID ) ) );
			}
		}

		return true;
	}

	/**
	 * Retrieves the required vars for the dashboard Workspace template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_workspace_template_vars'
	 *
	 */
	function template_vars() {
		$workspace = get_queried_object();

		if ( current_user_can( 'manage_options', $workspace->ID )  ) {

			// add the reviewer to the participants list (if not added yet) to be able to view workspaces
			hrb_p2p_connect_participant_to( $workspace->ID, get_current_user_id(), array(
				'type' => 'reviewer',
				'status' => HRB_WORK_STATUS_REVIEW,
			) );

		}

		$p2p_workspace = hrb_p2p_get_workspace_post( $workspace->ID );

		$project = hrb_get_project( $p2p_workspace->p2p_from );

		$args = array(
			'connected_meta' => array( 'type' => array( 'worker' ) ),
		);

		$participants = hrb_p2p_get_workspace_participants( $workspace->ID, $args )->results;

		$employer = get_user_by( 'id', $project->post_author );

		$review_users = array_merge( wp_list_pluck( $participants, 'ID' ), array( $employer->ID ) );

		$order = appthemes_get_order_connected_to( get_queried_object_id() );

		$escrow_status = '';

		if ( $order && APPTHEMES_ORDER_COMPLETED == $order->get_status() ) {
			$escrow_status = __( 'PAID', APP_TD );
		} elseif ( $order && APPTHEMES_ORDER_REFUNDED == $order->get_status() ) {
			$escrow_status = __( 'REFUNDED', APP_TD );
		}

		$template_vars = array(
			'project' => $project,
			'reviews' => hrb_get_post_reviews_for( $project->ID, $workspace->ID, array( 'participants' => $review_users ) ),
			'employer' => $employer,
			'escrow_status' => $escrow_status,
			'participants' => $participants,
			'participant' => hrb_p2p_get_participant( $workspace->ID, self::$dashboard_user->ID ),
		);

		if ( hrb_is_disputes_enabled() ) {
			$disputes = appthemes_get_disputes_for( $workspace->ID, self::$dashboard_user->ID, 0, array( 'post_status' => 'any' ) );
			$template_vars['disputes'] = $disputes;
		}

		return apply_filters( 'hrb_dashboard_workspace_template_vars', $template_vars );
	}

	function template_include( $path ) {

		if ( ! $this->pre_load() && empty( $_GET['suberror'] ) ) {
			wp_redirect( hrb_get_dashboard_url_for('projects') );
			exit;
		}
		return parent::template_include( $path );
	}

	function template_redirect() {
		// enqeue required scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function enqueue_styles_scripts() {
		hrb_register_enqueue_scripts( array( 'validate', 'app-workspace' ) );

		// enqeue required styles/scripts
		appthemes_reviews_enqueue_styles();
		appthemes_reviews_enqueue_scripts();

		wp_localize_script( 'app-workspace', 'app_workspace_i18n', array(
			'escrow'						=> hrb_workspace_is_pending_payment(),
			'disputes_enabled'				=> hrb_is_disputes_enabled(),
			'work_complete'					=> hrb_is_work_complete( get_queried_object_id() ),
			'confirmation'					=> __( 'Are you sure?', APP_TD ),
			'confirmation_possible_dispute' => hrb_get_possible_dispute_notice( $js = true ),
			'confirmation_escrow_cancel'	=> __( 'This will issue a refund and work will not be paid.', APP_TD ),
			'confirmation_escrow_complete'  => __( 'The money held in escrow will be transferred to the participant.', APP_TD ),
		) );

	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Workspace :: %s', APP_TD ), get_the_title() ) );
	}

	function body_class( $classes ) {
		$classes[] = 'app-dashboard-workspace';
		return $classes;
	}

}

/**
 * Dashboard workspace user review View.
 */
class HRB_User_Dashboard_Workspace_Review extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var( 'review_user' );

		$this->add_rewrite_rules();
	}

	private function add_rewrite_rules() {
		global $hrb_options;

		$dashboard_permalink = $hrb_options->dashboard_permalink;
		$review_permalink = $hrb_options->review_user_permalink;

		$workspace_permalink = hrb_get_dashboard_permalinks('workspace');
		$dash_workspace_permalink = $dashboard_permalink . '/' . $workspace_permalink;

		appthemes_add_rewrite_rule( $dash_workspace_permalink . '/(.*?)/' . $review_permalink . '/(.*?)/?$', array(
			'dashboard' => $review_permalink,
			'project_id' => '$matches[1]',
			'review_user' => '$matches[2]',
		) );
	}

	function condition() {
		return ( 'review' == hrb_get_dashboard_page() && get_query_var( 'review_user' ) );
	}

	/**
	 * Validates rules for loading a Workspace User Review template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_workspace_review'
	 *
	 */
	protected function pre_load() {

		$project = hrb_get_project( get_queried_object() );
		$review_recipient = get_user_by( 'slug', get_query_var( 'review_user' ) );

		if ( ! current_user_can( 'edit_workspace', $project->ID ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not participating on this Project.', APP_TD ) );
			return false;
		}

		if ( ! current_user_can( 'add_review', $project->ID, $review_recipient->ID ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You cannot review this user.', APP_TD ) );
			return false;
		}

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_workspace_review', APP_Notices::$notices, $project, $review_recipient );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the required vars for the dashboard Workspace User Review template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_workspace_review_template_vars'
	 *
	 */
	function template_vars() {

		$template_vars = array(
			'project' => hrb_get_project( get_queried_object() ),
			'review_recipient' => get_user_by( 'slug', get_query_var( 'review_user' ) ),
		);
		return apply_filters( 'hrb_dashboard_workspace_review_template_vars', $template_vars );
	}

	function template_include( $path ) {

		if ( ! $this->pre_load() ) {
			wp_redirect( hrb_get_dashboard_url_for('reviews') );
			exit;
		}
		return parent::template_include( $path );
	}

	function template_redirect() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function enqueue_styles_scripts() {
		// enqeue required styles/scripts
		appthemes_reviews_enqueue_styles();
		appthemes_reviews_enqueue_scripts();
	}

	function title_parts( $parts ) {
		$user_slug = get_query_var( 'review_user' );
		$user = get_user_by( 'slug', $user_slug );

		return array( sprintf( __( 'Review  "%s"', APP_TD ), $user->display_name ) );
	}

	function body_class( $classes ) {
		$classes[] = 'app-dashboard-review';
		return $classes;
	}

}


/**
 * Dashboard workspace user review View.
 */
class HRB_User_Dashboard_Workspace_Dispute extends HRB_User_Dashboard_Workspace {

	function init() {
		global $wp;

		$wp->add_query_var( 'dispute' );
		$wp->add_query_var( 'suberror' );
	}

	function condition() {
		return ( get_query_var( 'dispute' ) );
	}

	/**
	 * Validates rules for loading a Workspace User Review template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_workspace_review'
	 *
	 */
	protected function pre_load() {

		$workspace = get_queried_object();

		APP_Notices::$notices = apply_filters( 'hrb_pre_load_workspace_dispute', APP_Notices::$notices, $workspace );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	/**
	 * Retrieves the required vars for the dashboard Workspace Dispute template.
	 *
	 * @uses apply_filters() Calls 'hrb_dashboard_workspace_review_template_vars'
	 *
	 */
	function template_vars() {

		$template_vars = array(
			'dispute_error' =>	get_query_var('dispute') && APP_Notices::$notices->get_error_code(),
		);
		return apply_filters( 'hrb_dashboard_workspace_dispute_template_vars', $template_vars );
	}

	function template_include( $path ) {

		$this->pre_load();

		return parent::template_include( $path );
	}

}


### Helper Classes for Views

/**
 * Security related checks for dashboard Views.
 */
class HRB_User_Dashboard_Secure extends HRB_User_Dashboard {

	function condition() {
		return parent::condition();
	}

	/**
	 * Validates the rules for displaying any dashboard page.
	 *
	 * @uses do_action() Calls 'hrb_dashboard_secure'
	 *
	 */
	function parse_query( $wp_query ) {

		// user must be logged in
		appthemes_auth_redirect_login();

		do_action( 'hrb_dashboard_secure', get_query_var('dashboard') );
	}

}

/**
 * Overrides WP_Query to make sure the dashboard queried post object is always the 'project' referenced in the 'project_id' query var, if present.
 */
class HRB_User_Dashboard_Single_Project extends HRB_User_Dashboard {

	function init() {
		global $wp;

		$wp->add_query_var('project_id');
	}

	function condition() {
		return parent::condition() && (int) get_query_var('project_id');
	}

	function parse_query( $wp_query ) {
		$project_id = (int) get_query_var('project_id');

		$project = get_post( $project_id );
		if ( ! $project ) {
			return;
		}

		// @todo check query vars usage

		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'post_status' => 'any',
			'post__in' => array( $project_id ),
		) );
	}

	function the_posts( $posts, $wp_query ) {
		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}
		return $posts;
	}

}
