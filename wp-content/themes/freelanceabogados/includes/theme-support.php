<?php
/**
 * Loads additional features to the theme via 'add_theme_support()'.
 */

add_theme_support( 'app-versions', array(
	'update_page' => 'admin.php?page=app-settings&firstrun=1',
	'current_version' => HRB_VERSION,
	'option_key' => 'freelance_version',
) );

add_theme_support( 'app-wrapping' );

add_theme_support( 'app-geo', array(
	'libraries' => array( 'geometry', 'places' ),
	'region' => $hrb_options->geo_region,
	'language' => $hrb_options->geo_language,
	'unit'	=> 'mi',
) );

add_theme_support( 'app-form-builder', array(
	'show_in_menu' => 'edit.php?post_type=' . HRB_PROJECTS_PTYPE
) );

add_theme_support( 'app-login', array(
	'login' => 'form-login.php',
	'register' => 'form-registration.php',
	'recover' => 'form-password-recovery.php',
	'reset' => 'form-password-reset.php',
) );

add_theme_support( 'app-payments', array(
	'items' => array_merge( hrb_project_addons(), hrb_proposal_addons() ),
	'items_post_types' => array( HRB_PROJECTS_PTYPE, HRB_WORKSPACE_PTYPE ),
	'options' => $hrb_options,
	'escrow' => true,
) );

add_theme_support( 'app-price-format', array(
	'currency_default' => $hrb_options->currency_code,
	'currency_identifier' => $hrb_options->currency_identifier,
	'currency_position' => $hrb_options->currency_position,
	'thousands_separator' => $hrb_options->thousands_separator,
	'decimal_separator' => $hrb_options->decimal_separator,
	'hide_decimals' => (bool) ( ! $hrb_options->decimal_separator ),
) );

add_theme_support( 'app-term-counts', array(
	'post_type' => array( HRB_PROJECTS_PTYPE ),
	'post_status' => array( 'publish' ),
	'taxonomy' => array( HRB_PROJECTS_CATEGORY ),
) );

add_theme_support( 'app-feed', array(
	'post_type' => HRB_PROJECTS_PTYPE,
	'blog_template' => 'index.php',
	'alternate_feed_url' => '',
) );

add_theme_support( 'app-bidding', array(
	'comment_type' => HRB_PROPOSAL_CTYPE,
	'post_type' => HRB_PROJECTS_PTYPE,
	'name' => __( 'Proposals', APP_TD ),
	'singular_name'	=> __( 'Proposal', APP_TD ),
	'admin_top_level_page' => 'none',
	'admin_sub_level_page' => '',
) );

add_theme_support( 'app-reviews', array(
	'post_type' => HRB_PROJECTS_PTYPE,
	'admin_top_level_page' => 'none',
	'admin_sub_level_page' => '',
) );

add_theme_support( 'app-notifications', array(
	'post_type' => HRB_PROJECTS_PTYPE,
	'admin_bar' => false,
	'admin_top_level_page' => 'none',
	'admin_sub_level_page' => '',
) );

add_theme_support( 'app-comment-counts' );

add_theme_support( 'app-media-manager' );

// Dynamic Checkout
require dirname( __FILE__ ) . '/checkout/load.php';

add_theme_support( 'app-form-progress', array(
	'checkout_types' => array(
		'chk-create-project' => array(
			'steps' => array(
				'create-project' => array( 'title' => __( 'Project Details', APP_TD ) ),
				'preview'		 => array( 'title' => __( 'Preview', APP_TD ) ),
				'select-plan'	 => array( 'title' => __( 'Options/Pay', APP_TD ) ),
				'thank_you'		 => array( 'title' => __( 'Thank You', APP_TD ) ),
				'order-summary'  => array( 'title' => __( 'Thank You', APP_TD ) ),
				'gateway-select' => array( 'map_to' => 'select-plan' ),
				'gateway-process' => array( 'map_to' => 'select-plan' ),
			),
		),
		'chk-renew-project' => array(
			'steps' => array(
				'renew-project' => array( 'title' => __( 'Project Details', APP_TD ) ),
                'preview'		 => array( 'title' => __( 'Preview', APP_TD ) ),
				'select-plan'	 => array( 'title' => __( 'Options/Pay', APP_TD ) ),
				'order-summary'  => array( 'title' => __( 'Thank You', APP_TD ) ),
				'gateway-select' => array( 'map_to' => 'select-plan' ),
				'gateway-process' => array( 'map_to' => 'select-plan' )
			),
		),
		'chk-credits-purchase' => array(
			'steps' => array(
				'select-plan' => array( 'title' => __( 'Select Plan', APP_TD ) ),
				'gateway-select' => array( 'title' => __( 'Pay', APP_TD ) ),
				'order-summary'  => array( 'title' => __( 'Thank You', APP_TD ) ),
				'gateway-process' => array( 'map_to' => 'gateway-select' )
			),
		),
	),
) );

// Disputes
add_theme_support( 'app-disputes', array(
	'post_type'				=> HRB_WORKSPACE_PTYPE,
	'verbiages_callback'	=> 'hrb_get_project_statuses_verbiages',
	'participants_callback' => 'hrb_get_dispute_participants',
	'allow_comments'		=> true,
	'enable_disputes'		=> $hrb_options->disputes['enabled'],
	'options'				=> $hrb_options,
	'labels' => array(
		'disputer'	=> __( 'Freelancer', APP_TD ),
		'disputee'	=> __( 'Employer', APP_TD ),
		'disputers'	=> __( 'Freelancers', APP_TD ),
		'disputees'	=> __( 'Employers', APP_TD ),
		'pay'		=> __( 'Pay Freelancer', APP_TD ),
		'refund'	=> __( 'Refund Employer', APP_TD ),
	)
) );