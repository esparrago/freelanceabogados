<?php
/**
 * Installation related functions
 */

add_action( 'appthemes_first_run', 'hrb_setup_settings' );
add_action( 'appthemes_first_run', 'hrb_setup_menu' );
add_action( 'appthemes_first_run', 'hrb_setup_content' );
add_action( 'appthemes_first_run', 'hrb_setup_widgets' );
add_action( 'appthemes_first_run', 'hrb_add_caps', 11 );
add_action( 'appthemes_first_run', 'hrb_setup_image_sizes' );

### Settings

function hrb_setup_settings() {

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', HRB_Home_Archive::get_id() );
	update_option( 'page_for_posts', HRB_Blog_Archive::get_id() );

	if ( !get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	flush_rewrite_rules();
}

### Menus

function hrb_setup_menu() {

	if ( is_nav_menu('header') ) {
		return;
	}

	$menu_id = wp_create_nav_menu( __( 'Header', APP_TD ) );

	if ( is_wp_error( $menu_id ) ) {
		return;
	}

	$page_ids = array(
		HRB_Project_Categories::get_id(),
		HRB_Project_Create::get_id(),
		-1,
		-1,
		HRB_How_Works_Page::get_id(),
		HRB_Blog_Archive::get_id(),
	);

	$page_ids = apply_filters( 'hrb_setup_menu_page_ids', $page_ids );

	foreach ( $page_ids as $key => $page_id ) {
		$page = get_post( $page_id );

		if ( ! $page ) {
			continue;
		}

		wp_update_nav_menu_item( $menu_id, 0, array(
			'menu-item-position'	=> $key,
			'menu-item-type'		=> 'post_type',
			'menu-item-object'		=> 'page',
			'menu-item-object-id' 	=> $page_id,
			'menu-item-title' 		=> esc_attr( $page->post_title ),
			'menu-item-url' 		=> esc_url( get_permalink( $page ) ),
			'menu-item-status' 		=> 'publish',
		) );
	}

	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-position'	=> 2,
		'menu-item-title' 		=> esc_attr( __( 'Find Work', APP_TD ) ),
		'menu-item-url' 		=> esc_url( get_post_type_archive_link( HRB_PROJECTS_PTYPE ) ),
		'menu-item-status' 		=> 'publish'
	) );

	wp_update_nav_menu_item( $menu_id, 0, array(
		'menu-item-position'	=> 3,
		'menu-item-title' 		=> esc_attr( __( 'Find Freelancers', APP_TD ) ),
		'menu-item-url' 		=> esc_url( get_the_hrb_users_base_url() ),
		'menu-item-status' 		=> 'publish'
	) );

	$locations = get_theme_mod( 'nav_menu_locations' );
	$locations['header'] = $menu_id;

	set_theme_mod( 'nav_menu_locations', $locations );
}

### Content

function hrb_setup_content() {

	// Deliberately left untranslated

	$projects = get_posts( array(
		'post_type' => HRB_PROJECTS_PTYPE,
		'posts_per_page' => 1
	) );

	$cat = appthemes_maybe_insert_term( 'Software', HRB_PROJECTS_CATEGORY );

	if ( empty( $projects ) ) {

		$skills_p = appthemes_maybe_insert_term( 'PHP', HRB_PROJECTS_SKILLS );

		// Dummy Users

		$employer_id = username_exists( 'employer' );
		if ( !$employer_id ) {
			$employer_id = wp_insert_user( array(
				'role' => HRB_ROLE_EMPLOYER,
				'user_login' => 'employer',
				'display_name' => 'Employer',
				'user_pass' => wp_generate_password()
			) );

			update_user_meta( $employer_id, 'hrb_location', 'Lisboa, Portugal' );
			update_user_meta( $employer_id, 'hrb_rate', 40 );
			update_user_meta( $employer_id, 'hrb_currency', 'EUR' );
			update_user_meta( $employer_id, 'hrb_user_skills', $skills_p['term_id'] );
		}

		$freelancer_id = username_exists( 'freelancer' );
		if ( !$freelancer_id ) {
			$freelancer_id = wp_insert_user( array(
				'role' => HRB_ROLE_FREELANCER,
				'user_login' => 'freelancer',
				'display_name' => 'freelancer',
				'user_pass' => wp_generate_password()
			) );

			update_user_meta( $freelancer_id, 'hrb_location', 'San Francisco, USA' );
			update_user_meta( $freelancer_id, 'hrb_rate', 70 );
			update_user_meta( $freelancer_id, 'hrb_currency', 'USD' );
			update_user_meta( $freelancer_id, 'hrb_user_skills', $skills_p['term_id'] );
		}

		// Dummy Projects

		$project_id = wp_insert_post( array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'post_status' => 'publish',
			'post_author' => $employer_id,
			'post_title' => 'Responsive Design for Site',
			'post_content' => 'We are looking for a bold 1.4 page size ad for a local newspaper. This ad is very text heavy and will only leave a small amount of space for design work. Which has to tie into our current brand. Deadline for this job is 3pm MST June 28.',
			'tax_input' => array(
				HRB_PROJECTS_CATEGORY => (int) $cat['term_id'],
				HRB_PROJECTS_SKILLS => (int) $skills_p['term_id'],
			)
		) );

		update_post_meta( $project_id, '_hrb_duration', 5 );
		update_post_meta( $project_id, '_hrb_budget_type', 'fixed' );
		update_post_meta( $project_id, '_hrb_budget_currency', 'USD' );
		update_post_meta( $project_id, '_hrb_budget_price', '250' );

		$project_id = wp_insert_post( array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'post_status' => 'publish',
			'post_author' => $employer_id,
			'post_title' => 'Logo Design',
			'post_content' => 'We are looking for a bold 1.4 page size ad for a local newspaper. This ad is very text heavy and will only leave a small amount of space for design work. Which has to tie into our current brand. Deadline for this job is 3pm MST June 28.',
			'tax_input' => array(
				HRB_PROJECTS_CATEGORY => (int) $cat['term_id'],
				HRB_PROJECTS_SKILLS => (int) $skills_p['term_id'],
			)
		) );

		update_post_meta( $project_id, '_hrb_duration', 5 );
		update_post_meta( $project_id, '_hrb_budget_type', 'fixed' );
		update_post_meta( $project_id, '_hrb_budget_currency', 'USD' );
		update_post_meta( $project_id, '_hrb_budget_price', '150' );

		// Dummy Proposal
		$comment = array(
			'comment_post_ID' => $project_id,
			'user_id' => $freelancer_id,
			'comment_content' => "I'm the best person for this job.",
		);
		$bid = appthemes_make_bid( $comment, '500', 'USD' );

		// deliver in 5 days
		appthemes_update_bid_meta( $bid->get_id(), '_hrb_delivery', 5, true );

		appthemes_activate_bid( $bid->get_id() );

		$workspace_id = hrb_proposal_shake_hands( hrb_get_proposal( $bid ), get_user_by( 'id', $freelancer_id ) );

        hrb_update_post_status( $project_id, HRB_PROJECT_STATUS_CLOSED_COMPLETED );

		hrb_update_project_work_status( $workspace_id, $project_id, HRB_PROJECT_STATUS_CLOSED_COMPLETED );

		hrb_p2p_update_participant_status($workspace_id, $freelancer_id, HRB_WORK_STATUS_COMPLETED );

		// Dummy Reviews
		$comment = array(
			'comment_post_ID' => $project_id,
			'user_id' => $employer_id,
			'comment_content' => "Freelancer was nice but the quality was not top, as expected.",
		);
		$review = appthemes_create_user_review( $freelancer_id, $comment, 3 );

		appthemes_activate_review( $review->get_id() );

		$comment = array(
			'comment_type' => APP_REVIEWS_CTYPE,
			'comment_post_ID' => $project_id,
			'user_id' => $freelancer_id,
			'comment_content' => "Everything worked great with the Employer",
		);
		$review = appthemes_create_user_review( $employer_id, $comment, 5 );

		appthemes_activate_review( $review->get_id() );
	}

	$plans = get_posts( array(
		'post_type' => HRB_PRICE_PLAN_PTYPE,
		'posts_per_page' => 1
	) );

	if ( empty( $plans ) ) {

		$plan_id = wp_insert_post( array(
			'post_type' => HRB_PRICE_PLAN_PTYPE,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_title' => 'Basic',
			'post_content' => '',
			'tax_input' => array(
				HRB_PROJECTS_CATEGORY => array( $cat['term_id'] ),
			)
		) );

		$data = array(
			'title' => 'Basic',
			'description' => 'Get your project out there with our Basic plan. No frills, no fuss.',
			'duration' => 30,
			'price' => 15,
			'relist_price' => 5,
		);

		foreach ( $data as $key => $value ) {
			add_post_meta( $plan_id, $key, $value );
		}

	}

	$plans = get_posts( array(
		'post_type' => HRB_PROPOSAL_PLAN_PTYPE,
		'posts_per_page' => 1
	) );

	if ( empty( $plans ) ) {

		$plan_id = wp_insert_post( array(
			'post_type' => HRB_PROPOSAL_PLAN_PTYPE,
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'post_title' => '6 Pack',
			'post_content' => '',
		) );

		$data = array(
			'title' => '6 Pack',
			'description' => 'A pack with 6 credits. Get noticed faster by featuring your proposal.',
			'price' => 5,
			'credits' => 6,
		);

		foreach ( $data as $key => $value ) {
			add_post_meta( $plan_id, $key, $value );
		}

	}
}

/**
 * Auto setup widgets on sidebars
 */
function hrb_setup_widgets() {

	$sidebars_widgets = array(
		'hrb-main' => array(
			'create_project_button' => array(),
			'appthemes_recent_posts' => array( 'title' => __( 'Ultimas propuestas', APP_TD ), 'show_rating' => 1 ),
			'appthemes_facebook' => array(),
			'appthemes_125_ads' => array()
		),
		'hrb-listing-project' => array(
			'saved_filters' => array(),
		),
		'hrb-header-nav' => array(
			'text' => array(
				'text' => __( '
<div class="splash-example">
	<h2>Splash Screen Example</h2>

	This is an optional header area. It can be hidden, visible only on the frontpage or through out the site.

	The content is from a simple text widget. You can change it, or delete it in the Widgets settings section.

	<a href="post-a-project" class="button large">Post a Project Now</a>
</div>', APP_TD ),
				'filter' => 1,
			),
		),
		'hrb-project-ads' => array(
			'text' => array(
				'text' => '<a href="http://www.appthemes.com/?aid=4079" target="_blank"><img src="//www.appthemes.com/ads/at-468x60b.png" alt="Premium WordPress Apps" width="468" height="60"/></a>'
			),
		),
		'hrb-user-ads' => array(
			'text' => array(
				'text' => '<a href="http://www.appthemes.com/?aid=4079" target="_blank"><img src="//www.appthemes.com/ads/at-468x60c.gif" alt="Premium WordPress Apps" width="468" height="60"/></a>'
			),
		),
		'hrb-activity-header' => array(
			'text' => array(
				'title' => __( 'Optional Text Space', APP_TD ),
				'text' => __( 'You can use this space to display site news to your registered users.', APP_TD ),
			),
		),
		'hrb-activity-footer' => array(
			'text' => array(
				'title' => __( 'Optional Text Space', APP_TD ),
				'text' => __( 'Likewise, you can use this space to display additional news to your registered users.', APP_TD ),
			),
		),
		'hrb-create-project' => array(
			'text' => array(
				'title' => __( 'How To', APP_TD ),
				'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi dapibus, metus in fermentum consectetur, leo augue tempus est, sagittis rutrum est est nec mauris. Nullam non blandit tellus.

Cras tincidunt tortor et sem vestibulum congue. Aliquam scelerisque aliquet orci vel iaculis. Maecenas semper dictum velit, eget ultricies augue ultrices at.',
				'filter' => 1,
			),
		),
		'hrb-page' => array(
			'create_project_button' => array(),
			'text' => array(
				'title' => __( 'How To', APP_TD ),
				'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi dapibus, metus in fermentum consectetur, leo augue tempus est, sagittis rutrum est est nec mauris. Nullam non blandit tellus.

Cras tincidunt tortor et sem vestibulum congue. Aliquam scelerisque aliquet orci vel iaculis. Maecenas semper dictum velit, eget ultricies augue ultrices at.',
				'filter' => 1,
			),
		),
		'hrb-footer' => array(
			'text' => array(
				'title' => __( 'About HireBee', APP_TD ),
				'text' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi dapibus, metus in fermentum consectetur, leo augue tempus est, sagittis rutrum est est nec mauris. Nullam non blandit tellus.

Cras tincidunt tortor et sem vestibulum congue. Aliquam scelerisque aliquet orci vel iaculis. Maecenas semper dictum velit, eget ultricies augue ultrices at.',
				'filter' => 1
			),
		),
	);


	// only install widgets on first theme install

	if ( get_option('hrb_firstime') ) {
		return;
	}

	add_option( 'hrb_firstime', 1 );

	appthemes_install_widgets( $sidebars_widgets );

	// install the navigation menu in footer

	 $menu_name = 'header';

	if ( ( $locations = get_nav_menu_locations() ) && isset( $locations[ $menu_name ] ) ) {
		$menu = wp_get_nav_menu_object( $locations[ $menu_name ] );

		$menu_id = $menu->term_id;
	} else {
		$menu_id = 1;
	}

	appthemes_install_widget( 'nav_menu', 'hrb-footer2',
			array(
				'title' => 'Navigate',
				'nav_menu' => $menu_id,
				'filter' => 1
	) );

	// install the social connect widget

	appthemes_install_widget( 'appthemes_social_connect', 'hrb-footer3',
		array(
			'title' => 'Social Media' ,
			'use_tooltips' => 1,
			'social_facebook_inc' => 1,
			'social_facebook_url' => 'https://www.facebook.com/appthemes',
			'social_facebook_desc' => 'Become a Fan on Facebook',
			'social_twitter_inc' => 1,
			'social_twitter_url' => 'https://twitter.com/AppThemes',
			'social_twitter_desc' => 'Follow us on Twitter',
			'social_google-plus_inc' => 1,
			'social_google-plus_url' => 'https://plus.google.com/108097040296611426034',
			'social_google-plus_desc' => 'Follow us on Google+',
			'filter' => 1,
		)
	);
}

### Images

function hrb_setup_image_sizes( $sizes ) {
	update_option( 'thumbnail_size_w', 50 );
	update_option( 'thumbnail_size_h', 50 );
	update_option( 'thumbnail_crop', true );

	update_option( 'medium_size_w', 230 );
	update_option( 'medium_size_h', 230 );
}
