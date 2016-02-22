<?php
/**
 * Contains the default values for the options global.
 */

$GLOBALS['hrb_options'] = new scbOptions( 'hrb_options', false, array(

    'share_roles_caps' => '',
	'registration_box_title' => 'Don\'t have an account?',
	'registration_box_text' => 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.',

	// appearance
	'color' => 'modern',
	'disable_admin_toolbar' => 'yes',
	'custom_header_vis' => 'front',

	'attachments' => '',
	'attachments_limit' => '3',
	'attachments_types' => 'pdf, doc, docx',
	'attachments_size' => '512',

	// social
	'linkedin_id' => '',
	'twitter_id' => '',
	'facebook_id' => '',
	'google_plus_id' => '',

	// pricing
	'currency_code' => 'USD',
	'currency_identifier' => 'symbol',
	'currency_position' => 'left',
	'thousands_separator' => ',',
	'decimal_separator' => '.',
	'tax_charge' => 0,

	// escrow
	'escrow' => array(
		'enabled' => false,
		'retain_amount' => '0',
		'retain_type' => 'flat',
	),

	// projects
	'project_price' => 0,
	'project_charge' => '',
	'moderate_projects' => '',
    'project_duration' => 30,
    'project_duration_editable' => '',

	'projects_frontpage' => 5,
	'projects_per_page' => 10,
	'projects_clarification' => 'yes',

	'projects_allowed_skills' => 3,

	 // projects :: budget
	'allowed_currencies' => '',
	'budget_types' => '',

	// projects :: location
	'location_types' => '',

	// proposals
	'proposals_quotes_hide' => 'no',
	'credits_given' => 5,
	'credits_apply' => 1,
	'credits_apply_edit' => 0,
	'credits_feature' => 3,
	'credits_offer' => 5,

	// users
	'users_frontpage' => 5,
	'users_per_page' => 10,
	'local_users' => '',
	'restrict_user_currencies' => '',

	'avatar_upload' => '',

	// geolocation
	'geo_language' => 'en',

	// geolocation :: users
	'user_geo_country' => '',
	'user_geo_type' => '',
	'user_refine_search' => 'country',

	// geolocation :: projects
	'project_geo_country' => '',
	'project_geo_type' => '',
	'project_refine_search' => 'country',

	// featured projects
	'addons' => array(
		HRB_ITEM_FEATURED_HOME => array(
			'enabled' => 'yes',
			'price' => 0,
			'duration' => 30,
		),

		HRB_ITEM_FEATURED_CAT => array(
			'enabled' => 'yes',
			'price' => 0,
			'duration' => 30,
		),
		HRB_ITEM_URGENT => array(
			'enabled' => 'yes',
			'price' => 0,
			'duration' => 30,
		),
	),

	// category options

	'categories_menu' => array(
		'show' => '',
		'count' => 0,
		'depth' => 3,
		'sub_num' => 3,
		'hide_empty' => false,
	),
	'categories_dir' => array(
		'count' => 0,
		'depth' => 3,
		'sub_num' => 3,
		'hide_empty' => false,
	),

	// permalinks
	'project_permalink' 		 	=> 'projects',
	'project_cat_permalink' 	 	=> 'category',
	'project_skill_permalink'		=> 'skill',
	'project_tag_permalink' 	 	=> 'tag',

	'starred_project_permalink'  	=> 'starred',
	'edit_project_permalink'  	 	=> 'edit',
	'renew_project_permalink'  	 	=> 'renew',
	'purchase_project_permalink' 	=> 'purchase',
	'review_user_permalink'			=> 'review',

	'edit_proposal_permalink'		=> 'edit',

	'user_permalink'				=> 'freelancers',

	'dashboard_permalink' 	 	 	=> 'dashboard',
	'dashboard_projects_permalink'	=> 'projects',
	'dashboard_reviews_permalink'	=> 'reviews',
	'dashboard_proposals_permalink'	=> 'proposals',
	'dashboard_faves_permalink'  	=> 'favorites',
	'dashboard_notifications_permalink'	=> 'notifications',
	'dashboard_workspace_permalink'	=> 'workspace',

	'dashboard_payments_permalink'  => 'payments',

	'profile_permalink'				=> 'profile',

	// gateways
	'gateways' => array(
		'enabled' => array()
	),

	// integration
	'listing_sharethis'		=> 0,
	'blog_post_sharethis'	=> 0,

	// bidding
	'fee_amount'	=> '5',
	'fee_type'		=> 'percent',

	// geo
	'geo_region'	=> 'en',
	'geo_language'	=> 'us',

	// notifications
	'notify_new_projects'	=> 'yes',
	'notify_new_proposals'	=> 'yes',
	'notify_new_reviews'	=> 'yes',

	// disputes
	'disputes' => array(
		'enabled' => false,
		'max_days' => 5,
	),

) );

