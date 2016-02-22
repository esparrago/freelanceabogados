<?php
/**
 * Views for proposal related pages.
 *
 * Views prepare and provide data to the page requested by the user.
 *
 */

/**
 * View for creating/posting a proposal.
 */
class HRB_Proposal_Create extends APP_View_Page {

	static protected $permalink;

	function __construct() {
		parent::__construct( 'create-proposal.php', __( 'Apply to Project', APP_TD ), array( 'internal_use_only' => true ) );
	}

	function init() {
		global $wp;

        if ( ! self::get_id() ) {
            return;
        }

		$post = get_post( self::get_id() );
		self::$permalink = $post->post_name;

		appthemes_add_rewrite_rule( self::$permalink . '/(\d+)/?$', array(
			'pagename' => self::$permalink,
			'project_id' => '$matches[1]'
		) );

		$wp->add_query_var('project_id');
		$wp->add_query_var('proposal_id');
	}

	static function get_id() {
		return parent::_get_id( __CLASS__ );
	}

	/**
	 * Validates rules for loading the Create proposal template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_create_proposal'
	 *
	 */
	private function pre_load( $project_id ) {
		global $current_user;

		appthemes_require_login();

		if ( empty( $current_user->hrb_email ) ) {
			$profile_link = html_link( add_query_arg( array( 'redirect_url' => urlencode( $_SERVER['REQUEST_URI'] ) ), appthemes_get_edit_profile_url() ), __( 'Update Profile', APP_TD ) );
			appthemes_add_notice( 'no-public-email', sprintf( __( 'You must provide a public email before applying to a project. %s.', APP_TD ), $profile_link ) );
            return false;
		}

		if ( ! current_user_can('edit_bids') ) {
			appthemes_add_notice( 'no-permissions', __( 'You are not allowed to apply to projects.', APP_TD ) );
			return false;
		}

		if ( ! current_user_can( 'add_bid', $project_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You cannot apply to this project', APP_TD ) );
            return false;
		}

		if ( ! hrb_user_has_credits_to('send_proposal') ) {
			appthemes_add_notice( 'no-credits', sprintf( __( 'Not enough credits to continue (%s credit(s) required). Please <a href="%s">purchase</a> some credits and try again.', APP_TD ), hrb_required_credits_to('send_proposal'), hrb_get_credits_purchase_url() ) );
			return false;
		}

        APP_Notices::$notices = apply_filters( 'hrb_pre_load_create_proposal', APP_Notices::$notices, $project_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
        return true;
	}

	function template_include( $path ) {

		// return earlier if user not using permalinks and is editing the proposal
		if ( get_query_var('proposal_edit') ) {
			return $path;
		}

		$project_id = get_query_var('project_id');

        if ( ! $this->pre_load( $project_id ) ) {
            wp_redirect( get_permalink( $project_id ) );
			exit;
        }

		appthemes_setup_checkout( 'chk-create-proposal', get_permalink( self::get_id() ) );

		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return $path;
	}

	function template_vars() {

		$project_id = (int) get_query_var('project_id');
		$project = get_post( $project_id );

		$project_author = get_user_by( 'id', $project->post_author );

		$template_vars = array(
			'project_author' => $project_author,
			'project_author_reviews' => (int) get_the_hrb_user_total_reviews( $project_author ),
		);
		return $template_vars;
	}

	function template_redirect() {
		// enqueue required scripts/styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function enqueue_styles_scripts() {

		hrb_register_enqueue_styles( array( 'jquery-tagmanager', 'jquery-select2' ) );
		hrb_register_enqueue_scripts( array( 'jquery-tagmanager', 'jquery-select2', 'validate', 'app-proposal-edit' ) );

		wp_localize_script( 'app-proposal-edit', 'hrb_proposal_i18n', array(
			'credits_balance' => hrb_get_user_credits(),
			'send_proposal_req_c'	=> hrb_required_credits_to('send_proposal'),
			'feature_proposal_req_c' => hrb_required_credits_to('feature_proposal'),
		) );

	}

	function body_class($classes) {
		$classes[] = 'app-proposal-create';
		return $classes;
	}

}

/**
 * View for editing a proposal.
 */
class HRB_Proposal_Edit extends HRB_Proposal_Create {


	function init() {
		global $wp, $hrb_options;

		$edit_permalink = $hrb_options->edit_proposal_permalink;

		appthemes_add_rewrite_rule( self::$permalink . '/(\d+)/' . $edit_permalink . '/(\d+)/?$', array(
            'pagename' => self::$permalink,
            'project_id' => '$matches[1]',
			'proposal_edit' => '$matches[2]',
		) );

		$wp->add_query_var('project_slug');
		$wp->add_query_var('proposal_edit');
	}

	function condition() {
		return (bool) get_query_var('proposal_edit');
	}

	function parse_query( $wp_query ) {

        $project_id = $wp_query->get('project_id');

		$wp_query->query_vars = array_merge( $wp_query->query_vars, array(
			'post_type'		=> HRB_PROJECTS_PTYPE,
			'post_status'	=> 'any',
			'post__in'		=> array( $project_id ),
		) );
	}

	function the_posts( $posts, $wp_query ) {
		if ( ! empty( $posts ) ) {
			$wp_query->queried_object = reset( $posts );
			$wp_query->queried_object_id = $wp_query->queried_object->ID;
		}
		return $posts;
	}

	/**
	 * Validates rules for loading the Edit proposal template.
	 *
	 * @uses apply_filters() Calls 'hrb_pre_load_edit_proposal'
	 *
	 */
	private function pre_load( $proposal_id ) {

		appthemes_require_login();

		if ( ! current_user_can( 'edit_bid', $proposal_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You don\'t have permissions to edit that Proposal', APP_TD ) );
            return false;
		}

		if ( ! hrb_user_has_credits_to('edit_proposal') ) {
			appthemes_add_notice( 'no-credits', sprintf( __( 'Not enough credits to continue (%s credit(s) required). Please <a href="%s">purchase</a> some credits and try again.', APP_TD ), hrb_required_credits_to('edit_proposal'), hrb_get_credits_purchase_url() ) );
			return false;
		}

        APP_Notices::$notices = apply_filters( 'hrb_pre_load_edit_proposal', APP_Notices::$notices, $proposal_id );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
        return true;
	}

	function template_include( $path ) {

        $proposal_id = (int) get_query_var( 'proposal_edit' );

        if ( ! $this->pre_load( $proposal_id ) ) {
			wp_redirect( get_permalink( get_query_var('project_id') ) );
			exit;
        }

		// setup dynamic checkout for editing a project
		appthemes_setup_checkout( 'chk-edit-proposal', get_the_hrb_proposal_edit_url( $proposal_id ) );

		$found = appthemes_process_checkout( 'chk-edit-proposal' );
		if ( !$found ){
			return locate_template( '404.php' );
		}
		return locate_template( 'create-proposal.php' );
	}

	function title_parts( $parts ) {
		return array( sprintf( __( 'Edit Proposal for "%s"', APP_TD ), get_the_title( get_query_var('project_id') ) ) );
	}

	function body_class($classes) {
		$classes[] = 'app-proposal-edit';
		return $classes;
	}
}