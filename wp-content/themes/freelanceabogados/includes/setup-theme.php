<?php
/**
 * Functions related with the base theme setup: menus, sidebars, styles, scripts, etc.
 */

add_action( 'after_setup_theme', '_hrb_setup_theme' );

add_action( 'wp_enqueue_scripts', '_hrb_enqueue_core_scripts' );
add_action( 'wp_enqueue_scripts', '_hrb_register_scripts' );

add_action( 'wp_enqueue_scripts', '_hrb_enqueue_core_styles' );
add_action( 'wp_enqueue_scripts', '_hrb_register_styles' );

add_action( 'wp_enqueue_scripts', '_hrb_load_fonts' );

add_action( 'login_enqueue_scripts', '_hrb_login_styling' );

add_filter( 'login_headerurl', '_hrb_login_logo_url' );
add_filter( 'login_headertitle', '_hrb_login_logo_url_title' );

add_filter( 'body_class', '_hrb_body_class' );
add_filter( 'appthemes_favicon', '_hrb_favicon' );

add_filter( 'wp_nav_menu_objects', '_hrb_disable_hierarchy_in_footer', 9, 2 );

add_action( 'wp_head', '_hrb_maybe_unhook_sharethis' );

### External Plugins Compatibility

// SocialConnect
if ( function_exists( 'sc_render_login_form_social_connect' ) ) {
	add_action( 'hrb_after_admin_bar_login_form', 'sc_render_login_form_social_connect' );
	add_action( 'app_login_pre_redirect', '_hrb_sc_connect_grab_login_redirect' );
}

// ShareThis
function _hrb_maybe_unhook_sharethis() {

	if ( ! function_exists('sharethis_button') ) {
		return;
	}

	remove_filter( 'the_content', 'st_add_widget' );
	remove_filter( 'the_excerpt', 'st_add_widget' );
}


### Hooks Callbacks

/**
 * Register menus, sidebars and other important theme related stuff.
 */
function _hrb_setup_theme() {
	global $hrb_options;

	// register menus
	register_nav_menu( 'header', __( 'Header Menu', APP_TD ) );
	register_nav_menu( 'footer', __( 'Footer Menu', APP_TD ) );

	// register sidebars
	hrb_register_sidebar( 'hrb-main', __( 'Main Sidebar', APP_TD ), __( 'The sidebar appearing on all pages except search, pages, and the single project page', APP_TD ) );
	hrb_register_sidebar( 'hrb-page', __( 'Pages Sidebar', APP_TD ), __( 'The sidebar for single pages', APP_TD ) );

	hrb_register_sidebar( 'hrb-create-project', __( 'Post Project Sidebar', APP_TD ), __( 'The sidebar for the post project page', APP_TD ) );
	hrb_register_sidebar( 'hrb-create-proposal', __( 'Post Proposal Sidebar', APP_TD ), __( 'The sidebar for the post proposal page', APP_TD ) );

	if ( hrb_is_escrow_enabled() ) {
		hrb_register_sidebar( 'hrb-transfer-funds', __( 'Transfer Funds Sidebar', APP_TD ), __( 'The sidebar for the funds transfer page', APP_TD ) );
	}

	hrb_register_sidebar( 'hrb-listings', __( 'Listings Sidebar', APP_TD ), __( 'The sidebar for the projects/freelancers listings', APP_TD ) );

	hrb_register_sidebar( 'hrb-single-project', __( 'Single Project Sidebar', APP_TD ), __( 'The sidebar for single project page', APP_TD ) );

	hrb_register_sidebar( 'hrb-project-ads', __( 'Projects Listings Ads', APP_TD ), __( 'An optional widget area to display ads on projects listing pages', APP_TD ) );
	hrb_register_sidebar( 'hrb-user-ads', __( 'Users Listings Ad', APP_TD ), __( 'An optional widget area to display ads on users listing pages', APP_TD ) );

	hrb_register_sidebar( 'hrb-profile', __( 'Profile Sidebar', APP_TD ), __( 'The sidebar for the user profile page', APP_TD ) );

	hrb_register_sidebar( 'hrb-activity-header', __( 'Dashboard Activity Header', APP_TD ), __( 'An optional widget area to display on the users activity dashboard header', APP_TD ), $type = 'header-dashboard' );
	hrb_register_sidebar( 'hrb-activity-footer', __( 'Dashboard Activity Footer', APP_TD ), __( 'An optional widget area to display on the users activity dashboard footer', APP_TD ), $type = 'header-dashboard' );

	hrb_register_sidebar( 'hrb-header-nav', __( 'Header Nav', APP_TD ), __( 'An optional widget area for widgets below the main navigation bar', APP_TD ), $type = 'header' );
	hrb_register_sidebar( 'hrb-header', __( 'Header', APP_TD ), __( 'An optional widget area for your site header', APP_TD ), $type = 'header' );

	hrb_register_sidebar( 'hrb-footer', __( 'Footer', APP_TD ), __( 'An optional widget area for your site footer (1 Column)', APP_TD ), $type = 'footer' );
	hrb_register_sidebar( 'hrb-footer2', __( 'Footer 2', APP_TD ), __( 'An optional widget area for your site footer (2 Columns)', APP_TD ), $type = 'footer' );
	hrb_register_sidebar( 'hrb-footer3', __( 'Footer 3', APP_TD ), __( 'An optional widget area for your site footer (3 Columns)', APP_TD ), $type = 'footer' );


	// misc
	add_theme_support('post-thumbnails');
	add_theme_support('automatic-feed-links');

	$defaults = array(
		'wp-head-callback' => 'hrb_custom_background_cb',
	);
	add_theme_support( 'custom-background', $defaults );

	// our theme handles the customer header in hrb_display_logo()
	$defaults = array(
		'default-image'          => '%s/images/logo.png',
		'width'                  => 400,
		'height'                 => 70,
		'flex-height'            => true,
		'flex-width'             => true,
		'default-text-color'     => '5b4c8c',
		'header-text'            => true,
		'uploads'                => true,
		'wp-head-callback'       => '',
		'admin-head-callback'    => '',
		'admin-preview-callback' => '',
	);
	add_theme_support( 'custom-header', $defaults );

	if ( $hrb_options->disable_admin_toolbar && ! current_user_can('administrator') && ! is_admin() ) {
	  show_admin_bar(false);
	}

}

/**
 * Enqueues core CSS styles to be used on all pages.
 */
function _hrb_enqueue_core_styles() {
	global $hrb_options;

	// always enqueue the core foundation files

	wp_enqueue_style(
		'hrb-normalize',
		get_template_directory_uri() . "/styles/core/normalize.css",
		array(),
		HRB_VERSION
	);

	wp_enqueue_style(
		'hrb-foundation',
		get_template_directory_uri() . "/styles/core/foundation.min.css",
		array(),
		HRB_VERSION
	);

	if ( is_child_theme() ) {
		return;
	}

	wp_enqueue_style(
		'hrb-color',
		get_template_directory_uri() . "/styles/$hrb_options->color.css",
		array(),
		HRB_VERSION
	);

}

/**
 * Enqueues core JS scripts to be used on all pages
 */
function _hrb_enqueue_core_scripts() {
    global $post, $hrb_options;

	if ( wp_is_mobile() ) {

		wp_enqueue_script(
			'zepto',
			get_template_directory_uri() . '/scripts/core/zepto.js',
			false,
			HRB_VERSION,
			true
		);

		$fnd_dependency = array( 'zepto' );

	} else {

		$fnd_dependency = array( 'jquery' );

	}

	wp_enqueue_script(
		'modernizr',
		get_template_directory_uri() . '/scripts/core/custom.modernizr.js',
		array( 'jquery' ),
		HRB_VERSION,
		true
	);

	wp_enqueue_script(
		'foundation',
		get_template_directory_uri() . '/scripts/core/foundation.min.js',
		$fnd_dependency,
		HRB_VERSION,
		true
	);

	wp_enqueue_script(
		'hrb-scripts',
		get_template_directory_uri() . '/scripts/scripts.js',
		array( 'jquery' ),
		HRB_VERSION,
		true
	);

    if ( ! empty( $post->ID ) ) {
        $nonce = $post->ID;
    } else {
        $nonce = 0;
    }

	wp_localize_script( 'hrb-scripts', 'hrb_i18n', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'ajax_nonce' => wp_create_nonce( "listing-{$nonce}" ),
        'ajaxloader' => html( 'img', array( 'class' => 'processing', 'src' => get_template_directory_uri() . '/images/processing.gif' ) ),
        'current_url' => scbUtil::get_current_url(),
		'dashboard' => hrb_get_dashboard_page(),
        'user_id' => get_current_user_id(),
		'file_upload_required' => __( 'Please upload some files.', APP_TD ),
		'loading_img' => '',
		'loading_msg' => '',
		'categories_menu' => ! wp_is_mobile() ? $hrb_options->categories_menu['show'] : '',
    ) );

}

/**
 * Enqeue Google fonts.
 */
function _hrb_load_fonts() {
	wp_enqueue_style ( 'googleFonts', 'http://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,400,300,600|Titillium+Web:400,700' );
}

/**
 * Registers common backend/frontend JS scripts.
 */
function hrb_register_common_scripts() {

	wp_register_script(
		'hrb-user-edit',
		get_template_directory_uri() . '/scripts/user-edit.js',
		null,
		HRB_VERSION,
		true
	);

	wp_localize_script( 'hrb-user-edit', 'app_user_edit_i18n', array(
		'geocomplete_options' => hrb_get_geocomplete_options('user'),
	) );

}

/**
 * Registers CSS styles to be selectively enqueued later.
 */
function _hrb_register_styles() {

	wp_register_style(
		'jquery-tagmanager',
		get_template_directory_uri() . '/scripts/jquery/tagmanager/jquery.tagmanager.css',
		array(),
		HRB_VERSION
	);

	wp_register_style(
		'jquery-select2',
		get_template_directory_uri() . '/scripts/jquery/select2/select2.css',
		array(),
		HRB_VERSION
	);

}

/**
 * Registers JS scripts to be selectively enqueued later.
 */
function _hrb_register_scripts() {

	### 3d Party

	# Tag Manager http://welldonethings.com/tags/manager/v3

	wp_register_script(
		'jquery-tagmanager',
		get_template_directory_uri() . '/scripts/jquery/tagmanager/jquery.tagmanager.js',
		array('jquery'),
		HRB_VERSION,
		true
	);

	# http://ivaynberg.github.io/select2/

	wp_register_script(
		'jquery-select2',
		get_template_directory_uri() . '/scripts/jquery/select2/jquery.select2.min.js',
		array( 'jquery' ),
		HRB_VERSION,
		true
	);

	### Custom

	wp_register_script(
		'app-saved-filter',
		get_template_directory_uri() . '/scripts/saved-filters.js',
		array('jquery'),
		HRB_VERSION,
		true
	);

	wp_register_script(
		'app-project-edit',
		get_template_directory_uri() . '/scripts/project-edit.js',
		array('jquery'),
		HRB_VERSION,
		true
	);

	wp_register_script(
		'app-proposal-edit',
		get_template_directory_uri() . '/scripts/proposal-edit.js',
		array('jquery'),
		HRB_VERSION,
		true
	);

	wp_register_script(
		'app-dashboard',
		get_template_directory_uri() . '/scripts/dashboard.js',
		array('jquery' ),
		HRB_VERSION,
		true
	);

	wp_register_script(
		'app-workspace',
		get_template_directory_uri() . '/scripts/workspace.js',
		array('jquery' ),
		HRB_VERSION,
		true
	);

	wp_register_script(
		'app-proposal-agreement',
		get_template_directory_uri() . '/scripts/proposal-agreement.js',
		array('jquery'),
		HRB_VERSION,
		true
	);

}

/**
 * Outputs the login styling for the login page.
 */
function _hrb_login_styling() {
	$header_image = get_header_image();

	if ( ! empty( $header_image ) ) {
?>
		<style>
			body.login div#login h1 a {
				background-image: url('<?php header_image(); ?>');
				width: <?php echo HEADER_IMAGE_WIDTH; ?>;
				height: <?php echo HEADER_IMAGE_HEIGHT; ?>;
			}
		</style>
<?php
	}

}

/**
 * Retrieves a 'not-logged-in' CSS class to the be used in the page body if user is not logged in.
 */
function _hrb_body_class( $classes ) {

	if ( ! is_user_logged_in() ) {
		$classes[] = 'not-logged-in';
	}
	return $classes;
}

/**
 * Update the favicon with every theme updated.
 */
function _hrb_favicon( $favicon ) {
	return add_query_arg( array( 'ver'=> HRB_VERSION ), appthemes_locate_template_uri( 'images/favicon.ico' ) );
}

/**
 * Retrieves the URL to be used on the site logo.
 */
function _hrb_login_logo_url() {
	return home_url();
}

/**
 * Retrieves the description to be used with the logo.
 */
function _hrb_login_logo_url_title() {
	return get_bloginfo( 'description' );
}


 // @todo maybe remove
function _hrb_disable_hierarchy_in_footer( $items, $args ) {

	if ( 'footer' != $args->theme_location ) {
		return $items;
	}

	foreach ( $items as &$item ) {
		if ( $item->menu_item_parent > 0 ) {
			$item = false;
		}
	}

	return array_filter( $items );
}


### Helper functions

/**
 * Retrieves custom attributes for a sidebar type.
 */
function hrb_get_sidebar_attributes( $type ) {

	$class = array(
		'before_title' => '<div class="section-head"><h3>',
		'after_title' => '</h3></div>',
	);

	switch ( $type ) {
		case 'header':
			$class['before_widget'] = '<div class="header-widget cf"><div id="%1$s" class="widget large-12 columns widget-content %2$s">';
			$class['after_widget'] = "</div></div>";
			break;

		case 'header-dashboard':
			$class['before_widget'] = '<div class="header-widget cf"><div id="%1$s" class="widget large-12 columns widget-content dashboard-header %2$s">';
			$class['after_widget'] = "</div></div>";
			break;

		case 'footer':
			$class['before_widget'] = '<div id="%1$s" class="widget large-12 centered columns %2$s">';
			$class['after_widget'] = "</div>";
			break;

		default:
			$class['before_widget'] = '<div id="%1$s" class="widget large-12 columns %2$s"><div class="panel">';
			$class['after_widget'] = "</div></div>";
			break;
	}

	return apply_filters( 'hrb_sidebar_attributes', $class, $type  );
}

/**
 * Wrapper function used to register sidebars with common custom attributes.
 */
function hrb_register_sidebar( $id, $name, $description = '', $type = 'default' ) {

	$base = array(
		'id' => $id,
		'name' => $name,
		'description' => $description,
	);
	$args = array_merge( $base, hrb_get_sidebar_attributes( $type ) );

	register_sidebar( $args );
}

/**
 * Default callback to output a background image/color.
 *
 * //@todo check if needed on final design
 */
function hrb_custom_background_cb() {

	$background = get_background_image();
	$color = get_background_color();

	if ( ! $background && ! $color ) {
		return;
	}

	$style = $color ? "background-color: #$color;" : '';

	if ( $background ) {

		$image = " background-image: url('$background');";
		$repeat = get_theme_mod( 'background_repeat', 'repeat' );

		if ( ! in_array( $repeat, array( 'no-repeat', 'repeat-x', 'repeat-y', 'repeat' ) ) ) {
			$repeat = 'repeat';
		}

		$repeat = " background-repeat: $repeat;";
		$position = get_theme_mod( 'background_position_x', 'left' );

		if ( ! in_array( $position, array( 'center', 'right', 'left' ) ) ) {
			$position = 'left';
		}

		$position = " background-position: top $position;";
		$attachment = get_theme_mod( 'background_attachment', 'scroll' );
		if ( ! in_array( $attachment, array( 'fixed', 'scroll' ) ) ) {
			$attachment = 'scroll';
		}

		$attachment = " background-attachment: $attachment;";
		$style .= $image . $repeat . $position . $attachment;

	} else if ( ! $background && $color ) {
		$style .= " background-image: none; ";
	}
?>
	<style type="text/css">
		body.custom-background { <?php echo trim( $style ); ?> }
	</style>
<?php
}


/**
 * Register scripts and enqueue them as needed.
 */
function hrb_register_enqueue_scripts( $scripts, $admin_only = false ) {

	// register common backend/frontend scripts
	hrb_register_common_scripts();

	if ( ! $admin_only ) {
		_hrb_register_scripts();
    }

	wp_enqueue_script( $scripts );
}

/**
 * Register styles and enqueue them as needed.
 */
function hrb_register_enqueue_styles( $styles ) {
	_hrb_register_styles();
	wp_enqueue_style( $styles );
}

/**
 * Enqueues the geo complete scripts if requested.
 */
function hrb_maybe_enqueue_geo( $callback = '' ) {

	if ( ! current_theme_supports( 'app-geo' ) ) {
		return;
	}

	appthemes_enqueue_geo_scripts( $callback );

	// enqueued later
	wp_enqueue_script(
		'app-geocomplete', get_template_directory_uri() . '/scripts/jquery/jquery.geocomplete.min.js', array( 'jquery', 'google-maps-api' ), HRB_VERSION, true
	);
}


### External Plugins

/**
 * Social Connect plugin compatibility.
 */
function _hrb_sc_connect_grab_login_redirect() {
	if ( ! empty( $_POST['action'] ) && 'social_connect' == $_POST['action'] ) {
		return false;
	}

	return true;
}