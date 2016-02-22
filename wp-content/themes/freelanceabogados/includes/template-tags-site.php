<?php
/**
 * Mirrors WordPress template tag functions (the_post(), the_content(), etc), used in the Loop.
 *
 * Altough some function might not be used in a WP Loop, they intend to work in the same way as to be self explanatory and retrieve data intuitively.
 *
 * Contains site generic functions and also frontend related functions mainly used to output HTML/CSS.
 *
 */

add_filter( 'appthemes_display_notice', 'hrb_display_notice', 10, 2 );

add_action( 'hrb_content_container_top', '_hrb_content_container_top' );

add_filter( 'wp_nav_menu_objects', '_hrb_maybe_remove_menu_items' );
add_action( 'wp_nav_menu_objects', '_hrb_no_permalinks_nav_menu_items' );

add_action( 'hrb_before_dashboard_front', '_hrb_output_dashboard_header_widget' );
add_action( 'hrb_after_dashboard_front', '_hrb_output_dashboard_footer_widget' );

add_action( 'appthemes_framework_loaded', '_hrb_remove_default_notices' );


### Hooks Callbacks

/**
 * Outputs the title HTML.
 */
function _hrb_content_container_top() {

	if ( hrb_is_blog() ) {
		the_hrb_page_title_header( __( 'Blog', APP_TD ) );
	} elseif ( is_hrb_titled_page() ) {
		the_hrb_page_title_header();
 	}

}

/**
 * Outputs the customized HTML markup for displaying notices.
 */
function hrb_display_notice( $class, $msgs ) {
?>
	<div data-alert class="notice <?php echo esc_attr( $class ); ?> alert-box radius">
		<?php foreach ( $msgs as $msg ): ?>
			<div><?php echo $msg; ?></div>
		<?php endforeach; ?>
		<a href="#" class="close">&times;</a>
	</div>
<?php
}

/**
 * Selectively remove navigation menu items using conditional checks.
 */
function _hrb_maybe_remove_menu_items( $items ) {

	if ( ! current_user_can('edit_projects') ) {
		$items_exclude[] = HRB_Project_Create::get_id();
	}

	if ( empty( $items_exclude ) ) {
		return $items;
	}

	foreach ( $items as $key => $item ) {
		if ( in_array( $item->object_id, $items_exclude ) ) {
			unset( $items[ $key ] );
		}
	}
	return $items;
}

/**
 * If permalinks are disabled, retrieve the no permalink URL version for projects and users archives.
 */
function _hrb_no_permalinks_nav_menu_items( $items ) {
	global $wp_rewrite;

	if ( $wp_rewrite->using_permalinks() ) {
		return $items;
	}

	$items[3]->url = esc_url( get_post_type_archive_link( HRB_PROJECTS_PTYPE ) );
	$items[4]->url = esc_url( get_the_hrb_users_base_url() );

	return $items;
}

/**
 * Outputs the dashboard header widget.
 */
function _hrb_output_dashboard_header_widget() {
	hrb_display_ad_sidebar( 'hrb-activity-header', $position = 'inside' );
}

/**
 * Outputs the dashboard footer widget.
 */
function _hrb_output_dashboard_footer_widget() {
	hrb_display_ad_sidebar( 'hrb-activity-footer', $position = 'inside' );
}

/**
 * Removes the default appthemes notice HTML markup by removing the hooking filter.
 */
function _hrb_remove_default_notices() {
	remove_filter( 'appthemes_display_notice', array( 'APP_Notices', 'outputter' ), 10 );
}


### Site URL's

/**
 * Retrieves the site registration URL.
 */
function get_the_hrb_site_registration_url() {
	$registration_url = add_query_arg( array( 'action' => 'register', 'redirect_to' => urlencode( hrb_get_dashboard_url_for() ) ), site_url('wp-login.php') );
	return $registration_url;
}

/**
 * Retrieves the site login URL.
 */
function get_the_hrb_site_login_url() {
	return site_url( 'wp-login.php', 'login' );
}

/**
 * Retrieves the site terms URL.
 */
function hrb_get_site_terms_url() {
	return get_permalink( HRB_Site_Terms_Page::get_id() );
}

### Blog

/**
 * Retrieves the blog page title.
 */
function get_the_hrb_blog_page_title() {
	return get_the_title( HRB_Blog_Archive::get_id() );
}

/**
 * Modified to return the link instead of displaying it.
 * Taken from http://codex.wordpress.org/Template_Tags/the_author_posts_link
 *
 * @uses apply_filters() Calls 'hrb_author_posts_link'
 *
 */
function get_the_hrb_author_posts_link() {
	global $authordata;

	if ( ! is_object( $authordata ) ) {
		return false;
	}

	$link = sprintf(
		'<a href="%1$s" title="%2$s" rel="author">%3$s</a>',
		get_author_posts_url( $authordata->ID, $authordata->user_nicename ),
		esc_attr( sprintf( __( 'Posts by %s', APP_TD ), get_the_author() ) ),
		get_the_author()
	);
	return apply_filters( 'hrb_author_posts_link', $link );
}

### Site Content / Output

/**
 * Outputs the title header for most of the pages.
 */
function the_hrb_page_title_header( $title = '' ) {

	if ( ! $title ) {
		if ( is_singular( HRB_PROJECTS_PTYPE ) ) {
			$title = __( 'Project Details', APP_TD );
		} else {
			$title = wp_title( $sep = '', $display = false );
		}
	}

	appthemes_before_page_title();
?>
	<div class="content-header">
		<div class="row">
			<h3><?php echo $title; ?></h3>
		</div>
	</div>
<?php
	appthemes_after_page_title();
}

/**
 * Outputs the site header logo applying any attributes from the WordPress site customizer.
 *
 * @uses apply_filters() Calls 'hrb_header_logo'
 *
 */
function the_hrb_logo() {

	$url = get_header_image();

	if ( $url === false ) {

		$header_image = '';
		$text_indent = '0';

	} elseif( $url != '' ) {

		$header_image = $url;
		$text_indent = '-9999px';

	} else {

		$header_image = get_template_directory_uri().'/images/logo.png';
		$text_indent = '-9999px';

	}
?>
	<h1 id="site-title">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="custom-header-image" style="height:<?php echo get_custom_header()->height; ?>px;width:<?php echo get_custom_header()->width; ?>px;background: transparent url('<?php echo $header_image; ?>') no-repeat 0 0;color:#<?php header_textcolor(); ?>;text-indent: <?php echo $text_indent; ?>"><?php bloginfo( 'title' ); ?></a>
	</h1>
<?php
}

### Navigation / Menus

/**
 * Outputs the main navigation menu below the site title.
 */
function the_hrb_nav_menu() {

	wp_nav_menu( array(
		'menu_id'		 => 'navigation',
		'theme_location' => 'header',
		'container_class'=> 'center',
		'items_wrap'	 => '<ul id="%1$s">%3$s</ul>',
		'fallback_cb'	 => false
	) );

}

/**
 * Outputs the footer navigation menu.
 */
function the_hrb_footer_menu() {
	wp_nav_menu( array(
		'menu_class'	=> 'inline-list right',
		'theme_location'=> 'footer',
		'fallback_cb'	=> false,
	) );
}

/**
 * Outputs the social networks navigation links
 *
 * @uses apply_filters() Calls 'hrb_social_nav_links'
 *
 */
function the_hrb_social_nav_links() {
	global $hrb_options;

	$social_links = array(

		'twitter' => array(
			'name' => __( 'Twitter', APP_TD ),
			'class' => 'icon fi-social-twitter',
		),
		'linkedin' => array(
			'name' => __( 'LinkedIn', APP_TD ),
			'class' => 'icon fi-social-linkedin',
		),
		'facebook' => array(
			'name' => __( 'Facebook', APP_TD ),
			'class' => 'icon fi-social-facebook',
		),
		'google-plus' => array(
			'name' => __( 'Google Plus', APP_TD ),
			'class' => 'icon fi-social-google-plus',
			'setting_key' => 'google_plus_id'
		),
	);
	$social_links = apply_filters( 'hrb_social_nav_links', $social_links );

	foreach( $social_links as $key => $social_link ) {

		if ( ! empty( $social_link['setting_key'] ) ) {
			$setting_key = $social_link['setting_key'];
		} else {
			$setting_key = "{$key}_id";
		}

		if ( empty( $social_link['url'] ) ) {
			$social_link['url'] = APP_Social_Networks::get_url( $key, $hrb_options->$setting_key );
		}

		if ( empty( $social_link['url'] ) || $social_link['url'] == APP_Social_Networks::get_url( $key ) ) {
			continue;
		}

		_hrb_output_social_nav_html( $key, $social_link );

	}
}

/**
 * Outputs a social navigation link HTML. Expects an array with: url, class, name.
 *
 * @uses apply_filters() Calls 'hrb_social_nav_html'
 *
 */
function _hrb_output_social_nav_html( $network_id, $social_atts ) {

	$title = APP_Social_Networks::get_title( $network_id );

	if ( 'rss' == $network_id ) {
		$title = __( 'RSS Feed', APP_TD );
	}

	ob_start();
?>
	<li>
		<a data-tooltip target="_blank" title="<?php echo esc_attr( $title ); ?>" href="<?php echo esc_url( $social_atts['url'] ); ?>">
			<i class="<?php echo esc_attr( $social_atts['class'] ); ?>"></i>
			<span><?php echo $social_atts['name']; ?></span>
		</a>
	</li>
<?php
	$social_link_html = ob_get_clean();

	echo apply_filters( 'hrb_social_nav_html', $social_link_html, $network_id, $social_atts );
}

/**
 * Retrieves the user main navigation links.
 *
 * @uses apply_filters() Calls 'hrb_user_nav_links'
 *
 */
function get_the_hrb_user_nav_links() {
	global $current_user;

	if ( ! is_user_logged_in() ) {

		$user_links = array(
			'login' => array(
				'name' => __( 'Login', APP_TD ),
				'url' => get_the_hrb_site_login_url(),
				'class' => 'icon i-login',
			),
			'register' => array(
				'name' => __( 'Registrar', APP_TD ),
				'url' => get_the_hrb_site_registration_url(),
				'class' => 'icon i-register',
			),
		);

	} else {

		get_currentuserinfo();

		ob_start();

		the_hrb_user_rating( $current_user );

		$the_user_rating = ob_get_clean();

		$user_links = array(
			/*'favorites' => array(
				'name' => __( 'Favorites', APP_TD ),
				'url' => hrb_get_dashboard_url_for( 'projects', 'favorites' ),
				'class' => 'icon i-favorites',
			),*/
			'notifications' => array(
				'name' => __( 'Notifications', APP_TD ) . sprintf( ' <span class="inbox">%d</span>', appthemes_get_user_total_unread_notifications( $current_user->ID ) ),
				'url' => hrb_get_dashboard_url_for('notifications'),
				'class' => '',
			),
			'rating' => array(
				'name' => $the_user_rating,
				'url' => get_the_hrb_user_profile_url( $current_user ),
				'class' => '',
				'title' => __( 'Rating', APP_TD ),
			),
			'user' => array(
				'name' => sprintf( __( 'Hi, %s', APP_TD ), $current_user->display_name ),
				'url' => hrb_get_dashboard_url_for(),
				'align' => 'left',
				'class' => 'icon i-dashboard',
				'title' => __( 'Dashboard', APP_TD ),
			),
		);
	}

	// hide registration link if not enabled
	if ( ! get_option( 'users_can_register' ) ) {
		unset( $user_links['register'] );
	}

	return apply_filters( 'hrb_user_nav_links', $user_links );
}

/**
 * Outputs the user main navigation links.
 */
function the_hrb_user_nav_links() {

	$user_links = get_the_hrb_user_nav_links();

	$defaults = array(
		'align' => '',
		'class' => '',
		'name' => 'item',
	);

	foreach( $user_links as $key => $user_link ) {

		$user_link = wp_parse_args( $user_link, $defaults );

		_hrb_output_user_nav_html( $user_link, $key );

	};

	if ( is_user_logged_in() ) {

		$logout_html = html( 'i', array( 'class' => 'logout icon i-logout'), '&nbsp;' ) . __(  'Logout', APP_TD );

		echo html( 'li', html( 'a', array( 'href' => wp_logout_url() ), $logout_html ) );
	}

}

/**
 * Outputs a user navigation link HTML. Expects an array with: url, class, name.
 *
 * @uses apply_filters() Calls 'hrb_user_nav_html'
 *
 */
function _hrb_output_user_nav_html( $user_link, $key ) {
	$user = wp_get_current_user();

	ob_start();
?>

	<?php if ( ! empty( $user_link['name'] )):  ?>

		<li class="<?php echo esc_attr( 'hrb-' . $key ); ?>">
			<a <?php echo ( ! empty( $user_link['title'] ) ? sprintf( 'data-tooltip title="%s"', $user_link['title'] ) : '' ); ?> href="<?php echo esc_url( $user_link['url'] ); ?>"><?php echo ( $user_link['align']? $user_link['name'] . ' ' : '' ); ?>
			<i class="<?php echo esc_attr( $user_link['class'] ); ?>"> </i>
			<?php echo ( 'left' != $user_link['align'] ? $user_link['name'] : '' ); ?></a>
		</li>

	<?php endif; ?>

	<?php if ( 'rating' == $key ): ?>

		<li>
			<?php
				echo get_the_hrb_user_gravatar( wp_get_current_user(), 35, array(
						'href' => esc_url( get_the_hrb_user_profile_url( $user ) ),
						'data-tooltip' => 'data-tooltip' ,
						'title' => __( 'Profile', APP_TD ) ) );
			?>
		</li>

	<?php endif; ?>

<?php
	$user_link_html = ob_get_clean();

	echo apply_filters( 'hrb_user_nav_html', $user_link_html, $user_link, $key );
}


### CSS Classes

/**
 * Mirrors 'post_class()' function to output a CSS class for a user.
 */
function hrb_user_class( $class, $user = '' ) {
	// separates classes with a single space
	echo 'class="' . esc_attr( join( ' ', get_the_hrb_user_class( $class, $user ) ) ) . '"';
}

/**
 * Mirrors 'get_post_class()' mirror function to retrieve the CSS class for a user.
 *
 * @uses apply_filters() Calls 'hrb_user_class'
 *
 */
function get_the_hrb_user_class( $class, $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return apply_filters( 'hrb_user_class', (array) $class, $user );
}

/**
 * Retrieves the CSS class to be applied to a featured user.
 *
 * @uses apply_filters() Calls 'hrb_user_featured_class'
 *
 */
function hrb_user_featured_class( $user = '' ) {
	$user = get_the_hrb_userdata( $user );

	return esc_attr( apply_filters( 'hrb_user_featured_class', 'featured', $user ) );
}

/**
 * Retrieves the CSS class to be applied to a featured project.
 *
 * @uses apply_filters() Calls 'hrb_project_featured_class
 *
 */
function hrb_project_featured_class( $post_id = 0 ) {
	return esc_attr( apply_filters( 'hrb_project_featured_class', 'featured', $post_id ) );
}

/**
 * Outputs the CSS class to be used for the current dashboard menu item, in the sidebar.
 *
 * @uses apply_filters() Calls 'hrb_dashboard_current_page_class'
 *
 */
function hrb_dashboard_current_page_class( $page ) {

	$class = '';

	if ( $page == hrb_get_dashboard_page() || ( 'dashboard' == $page && 'front' == hrb_get_dashboard_page() ) )  {
		$class = 'current';
	}

	echo esc_attr( apply_filters( 'hrb_dashboard_current_page_class', $class ) );
}


### Ads / widgets

/**
 * Outputs the a sidebar ad or widget by loading the related template.
 */
function hrb_display_ad_sidebar( $sidebar_id, $position = 'listing' ) {
	appthemes_load_template( 'widget-space.php', array( 'sidebar_id' => $sidebar_id, 'position' => $position ) );
}


### Searching & Filtering UI

/**
 * Outputs the search bar dropdown for: projects, freelancers.
 */
function the_hrb_search_dropdown( $attributes = '' ) {

	$items = array(
		HRB_PROJECTS_PTYPE => __( 'Consultas', APP_TD ),
		HRB_FREELANCER_UTYPE => __( 'Abogados', APP_TD )
	);

	$defaults = array(
		'id'					=> 'filter_search_types',
		'active_prepend_label'	=> false,
		'disable_links'			=> true,
		'hide_selected'			=> false,
		'query_var'				=> 'st',
		'dropdown_attributes'	=> array(
			'class' => 'button dropdown',
		),
		'f_dropdown_attributes' => array(
			'data-dropdown-content' => '',
		),
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Outputs the sorting dropdown for projects.
 */
function the_hrb_projects_sort_dropdown( $base_link = '', $attributes = '' ) {

	$items = array(
		'default'	=> __( 'Newest', APP_TD ),
		'urgent'	=> __( 'Urgent', APP_TD ),
		'popularity'=> __( 'Popularity', APP_TD ),
		'expiring'	=> __( 'Ending Soon', APP_TD ),
		'budget'	=> __( 'Budget', APP_TD ),
		'title'		=> __( 'Alphabetical', APP_TD ),
		'rand'		=> __( 'Random', APP_TD ),
	);

	if ( is_archive() ) {
		$base_link = $_SERVER['REQUEST_URI'];
	}

	$defaults = array(
		'id' => 'filter_projects_sort',
		'base_link' => $base_link,
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Outputs the sorting dropdown for freelancers.
 */
function the_hrb_users_sort_dropdown( $base_link = '', $attributes = '' ) {

	$items = array(
		'newest'  => __( 'Newest', APP_TD ),
		'default' => __( 'Success Rate', APP_TD ),
	);

	if ( is_hrb_users_archive() ) {
		$base_link = $_SERVER['REQUEST_URI'];
	}

	$defaults = array(
		'id' => 'filter_users_sort',
		'base_link' => $base_link,
		'dropdown_attributes' => $attributes
	);
	$options = wp_parse_args( $attributes, $defaults );

	the_hrb_filter_dropdown( $options, $items );
}

/**
 * Builds and outputs a filter dropdown with the given options and items.
 *
 * @uses apply_filters() Calls 'hrb_fdropdown_options'
 * @uses apply_filters() Calls 'hrb_fdropdown_items'
 *
 */
function the_hrb_filter_dropdown( $options, $items ) {
	global $wp_query;

	if ( empty( $items ) ) {
		return;
	}

	// parses the dropdown options

	$defaults = array(
		'id'			=> 'filter',		// should contain an unique ID to help identify the filter type on the 'apply_filters()' hook, below
		'name'			=> 'drop-sort-' . uniqid(),
		'label'			=> __( 'Sort By', APP_TD ),
		'base_link'		=> '',
		'query_var'		=> 'orderby',		// the query var to retrieve the active item from
		'hide_selected'	=> true,			// hide selected item from dropdown list
		'auto_select'	=> true,			// auto select active item
		'active_prepend_label'	=> true,	// prepend label to active item
		'disable_links'			=> false,	// should items redirect the page on click
		'dropdown_attributes'	=> '',		// dropdown header atts
		'f_dropdown_attributes' => '',		// child dropdown atts
	);
	$options = apply_filters( 'hrb_fdropdown_options', wp_parse_args( $options, $defaults ) );

	// make sure to pass an unique options 'id' to be able to change the items
	$items = apply_filters( 'hrb_fdropdown_items', $items, $options['id'] );

	// parses the dropdown atributes and outputs the markup

	$defaults = array(
		'href' => '#',
		'data-dropdown' => $options['name'],
		'class' => 'button dropdown small',
	);
	$dropdown_atts = wp_parse_args( $options['dropdown_attributes'], $defaults );

	if ( empty( $items['default'] ) ) {
		$default = array_keys( $items );
		$default = $default[0];
	} else {
		$default = 'default';
	}

	$current = get_hrb_query_var( $options['query_var'], false );

	if ( ( ! $current || ! isset( $items[ $current ] ) ) ) {
		$current = $default;
	}

	if ( $current && isset( $items[ $current ] ) && $options['auto_select'] ) {
		if ( $options['active_prepend_label'] && ! empty( $options['label'] ) ) {
			$selected = $options['label'] . ' :: ' . $items[ $current ];
		} else {
			$selected = $items[ $current ];
		}
		echo html( 'a', $dropdown_atts, $selected );
	}

	// dropdown <ul> markup

	$defaults = array(
		'id' => $options['name'],
		'class' => 'f-dropdown',
	);
	$f_dropdown_atts = wp_parse_args( $options['f_dropdown_attributes'], $defaults );

	$li = '';
	foreach( $items as $value => $title ) {

		if ( $value == $current && $options['hide_selected'] ) {
			continue;
		}

		if ( empty( $options['disable_links'] ) ) {

			$query_args = $wp_query->query;

			// unset paging when selecting a new filter
			if ( get_query_var('paged') ) {
				unset( $query_args['paged'] );
			}

			$options['base_link'] = add_query_arg( $query_args, $options['base_link'] );

			if ( ! empty( $options['base_link'] ) ) {
				$href = add_query_arg( $options['query_var'], $value, $options['base_link'] );
			} else {
				$href = add_query_arg( $options['query_var'], $value );
			}
		} else {
			$href = '#';
		}

		$args = array(
			'href' => $href,
			'data-value' => $value,
		);
		$link = html( 'a', $args, $title );

		$li .= html( 'li', $link );
	}
	echo html( 'ul', $f_dropdown_atts, $li );
}

/**
 * Outputs a data dropdown given a set of items and attributes.
 *
 * @uses apply_filters() Calls 'hrb_output_dropdown'
 *
 */
function the_hrb_data_dropdown( $items, $atts = array(), $text = '' ) {

	if ( ! $text ) {
		$text = __( 'Select Option', APP_TD );
	}

	$defaults = array(
		'title'	=> '',
		'href' => "#",
		'data-dropdown' => 'dropdown',
		'class' => 'button small dropdown',
	);
	$atts = wp_parse_args( $atts, $defaults );

	$dropdown = html( 'a', $atts, $text );

	$li = '';

	foreach( $items as $item => $attr ) {
		$a = html( 'a', $attr, $attr['title'] );
		$li .= html( 'li', $a );
	}

	$dropdown .= html( 'ul', array( 'id' => $atts['data-dropdown'], 'class' => 'f-dropdown' ), $li );

	echo apply_filters( 'hrb_output_dropdown', $dropdown, $items, $atts, $text );
}

/**
 * Outputs the refinement UI based on the given name and choices.
 */
function the_hrb_refine_checkbox_ui( $name, $choices ) {

	$options['choices'] = $choices;
	$options['checked'] = array();

	foreach( $choices as $choice => $desc ) {

		if ( isset( $_REQUEST[ $name ] ) && in_array( $choice, $_REQUEST[ $name ] ) ) {
			$options['checked'][] = $choice;
		}
	}

	$input = scbForms::input( array(
		'type' => 'checkbox',
		'name' => $name,
		'values' => $options['choices'],
		'checked' => $options['checked'],
	), $options );

	echo html( 'ul', array( 'class' => 'parent cf' ), $input );
}

/**
 * Outputs the refinements UI for a given taxonomy.
 *
 * @uses apply_filters() Calls 'hrb_refine_{$taxonomy}_ui'
 *
 */
function the_hrb_refine_category_ui( $taxonomy ) {

	$options = array(
		'taxonomy' => $taxonomy,
		'request_var' => "cat_$taxonomy",
	);
	$options = apply_filters( "hrb_refine_{$taxonomy}_ui", $options );

	ob_start();

	wp_terms_checklist( 0, array(
		'taxonomy' => $options['taxonomy'],
		'selected_cats' => isset( $_GET[ $options['request_var'] ] ) ? $_GET[ $options['request_var'] ] : array(),
		'checked_ontop' => false,
	) );
	$output = ob_get_clean();

	$output = str_replace( 'tax_input[' . $options['taxonomy'] . ']', $options['request_var'], $output );
	$output = str_replace( 'disabled=\'disabled\'', '', $output );

	echo html( 'ul', array( 'class' => 'parent cf' ), $output );
}

/**
 * Outputs the UI options for refining the 'location' on a listing.
 */
function the_hrb_refine_location_ui( $user_type = '', $type = '' ) {
	if ( 'users' == $type ) {
		$choices = hrb_get_users_locations( $user_type );
	} else {
		$choices = hrb_get_projects_locations();
	}

	if ( ! empty( $choices ) ) {
		the_hrb_refine_checkbox_ui( 'search_location', $choices );
	}
}

/**
 * Outputs the UI options for refining 'skills' on a listing.
 */
function the_hrb_refine_skills_ui() {
	the_hrb_refine_category_ui( HRB_PROJECTS_SKILLS );
}


### Categories

/**
 * Retrieves the projects categories list.
 *
 * @uses apply_filters() Calls 'hrb_project_categories_list'
 *
 */
function get_the_hrb_project_categories_list( $options = '' ) {
	global $hrb_options;

	if ( ! $options ) {
		$options = 'categories_dir';
	}

	$options = $hrb_options->$options;

	$args = array(
		'menu_cols'			=> 1,
		'menu_depth'		=> $options['depth'],
		'menu_sub_num'		=> $options['sub_num'],
		'cat_parent_count'	=> $options['count'],
		'cat_child_count'	=> $options['count'],
		'cat_hide_empty'	=> $options['hide_empty'],
		'cat_nocatstext'	=> true,
		'taxonomy'			=> HRB_PROJECTS_CATEGORY,
	);
	$args = apply_filters( 'hrb_project_categories_list', $args );

	return appthemes_categories_list( $args, array( 'pad_counts' => false ) );
}

/**
 * Outputs the projects categories list.
 */
function the_hrb_project_categories_list( $options = '' ) {
	echo get_the_hrb_project_categories_list( $options );
}


### Pagination

/**
 * Outputs pagination by loading the pagination template with pagination related vars.
 */
function hrb_output_pagination( $wp_query_object, $pagination_args, $base_url = '', $url_params = '' ) {
	global $wp, $wp_query, $hrb_options;

	// special case for paginating user results
	if ( isset( $pagination_args['paginate_users'] ) && is_a( $wp_query_object, 'WP_User_Query' ) ) {

		$current = max( 1, $wp_query->get( 'paged' ) );
		$total = (int) ( $hrb_options->users_per_page ? ceil( ( $wp_query_object->total_users / $hrb_options->users_per_page ) ) : 0 );

		$wp_query_object = array(
			'paginate_users' => true,
			'current' => $current,
			'total' => $total,
		);
		$pagination_args = wp_parse_args( $pagination_args, $wp_query_object );
	}

	$pagination_args['base_url'] = ( $base_url ? $base_url : $wp->request );
	$pagination_args['add_args'] = $url_params ? ( '#' == $url_params[0] ? array( 'a' => $url_params ) : '' ) : $url_params;

	$vars = array(
		'wp_query_object' => $wp_query_object,
		'pagination_args' => $pagination_args,
	);

	appthemes_load_template( 'pagination.php', $vars );
}

/**
 * Outputs the page numbering.
 */
function the_hrb_pagination( $wp_query, $args = array() ) {

	$defaults = array(
		'type'		=> 'array',
		'current'	=> max( 1, get_query_var( 'paged' ) ),
		'prev_next' => false,
	);
	$args = wp_parse_args( $args, $defaults );

	$query_var = 'paged';

	if ( is_array( $wp_query ) ) {

		if ( empty( $args['paginate_users'] ) && get_query_var( 'posts_per_page' ) ) {
			$args['total'] = ceil( $args['total'] / get_query_var( 'posts_per_page' ) );
		}
		$wp_query['total'] = $args['total'];
		$wp_query['current'] = get_query_var( $query_var );
	}

	$pages = appthemes_pagenavi( $wp_query, $query_var, $args );

	if ( ! $pages ) {
		return false;
	}

	foreach( $pages as $page ):
		$curr_page = (int) strip_tags( $page );
		$current = ( ( $curr_page == get_query_var( $query_var ) && $curr_page > 0 ) || ( ! get_query_var( $query_var ) && 1 == $curr_page ) );

		if ( $current ) {
			$page = html( 'a', $page );
		}
?>
		<li class="<?php echo ( $current ? 'current' : '' ); ?>"><?php echo $page; ?></li>
<?php
	endforeach;
}

### Reviews / Ratings

/**
 * Outputs the stars rating HTML given the rating.
 *
 * @uses apply_filters() Calls 'hrb_rating_html'
 *
 */
function hrb_rating_html( $rating, $no_rating_text = null, $atts = array() ) {

	if ( is_null( $no_rating_text ) ) {
		$no_rating_text = __( 'Unrated', APP_TD );
	}

	ob_start();
?>

	<?php if ( (int) $rating > 0 ) : ?>

		<i>
			<span class="stars-cont"></span>
			<span class="stars stars-<?php echo esc_attr( $rating ); ?>">
			<?php for( $i = 1; $i <= $rating; $i++ ): ?>
					<i class="icon i-review-symbol"></i>
			<?php endfor; ?>
			</span>
		</i>

	<?php elseif ( $no_rating_text ): ?>

		<span class="no-rating"><?php echo $no_rating_text; ?></span>

	<?php endif; ?>

<?php
	$ratings_html = ob_get_clean();

	echo apply_filters( 'hrb_rating_html',trim( $ratings_html ), $rating, $no_rating_text );
}


### Conditionals


/**
 * Checks if user is relisting a project.
 */
function is_hrb_relisting() {
	return (bool) get_query_var('project_relist');
}

/**
 * Checks if the current page should show a header title.
 */
function is_hrb_titled_page() {

	$page_id = (int) get_query_var('page_id');

	return ! is_home() && $page_id != HRB_Project_Categories::get_id();
}

function hrb_is_blog() {
	global $wp_query;

	return is_singular( 'post' ) || $wp_query->is_posts_page;
 }
