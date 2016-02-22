<?php
/**
 * Functions that hook into WordPress to allow customizing the theme using the customizer.
 */

// @todo color chooser available after 1.x
add_action( 'customize_register', '_hrb_customize_color_scheme' );

add_action( 'customize_register', '_hrb_customize_listings' );
add_action( 'customize_register', '_hrb_customize_categories' );
add_action( 'customize_register', '_hrb_customize_header_nav' );


### Hooks Callbacks

/**
 * Displays the theme color choices in the customizer.
 */
function _hrb_customize_color_scheme( $wp_customize ){
	global $hrb_options;

	$wp_customize->add_setting( 'hrb_options[color]', array(
		'default' => $hrb_options->color,
		'type' => 'option'
	) );

	$wp_customize->add_control( 'hrb_color_scheme', array(
		'label'      => __( 'Color Scheme', APP_TD ),
		'section'    => 'colors',
		'settings'   => 'hrb_options[color]',
		'type'       => 'radio',
		'choices' => hrb_get_color_choices(),
	) );

}

/**
 * Displays the theme listing options in the customizer.
 */
function _hrb_customize_header_nav( $wp_customize ){
	global $hrb_options;

	$wp_customize->add_section( 'hrb_header', array(
		'title' => __( 'Header', APP_TD ),
		'description' => __( 'Control the visibility of the optional custom header.', APP_TD ),
		'priority' => 30
	));

	$wp_customize->add_setting( 'hrb_options[custom_header_vis]', array(
		'title' => __( 'Show teste ', APP_TD ),
		'default' => 'yes',
		'type' => 'option',
	));

	$wp_customize->add_control( 'hrb_extra_header', array(
		'label'      => __( 'Display Custom Header', APP_TD ),
		'section'    => 'hrb_header',
		'settings'   => 'hrb_options[custom_header_vis]',
		'type'       => 'radio',
		'choices' => array(
			'front' => __( 'Front Page Only', APP_TD ),
			'any' => __( 'Always', APP_TD ),
			'disable' => __( 'Disable', APP_TD ),
		),
	) );
}

/**
 * Displays the theme listing options in the customizer.
 */
function _hrb_customize_listings( $wp_customize ){
	global $hrb_options;

	$wp_customize->add_section( 'hrb_listings', array(
		'title' => __( 'Listings', APP_TD ),
		'priority' => 35
	));

	$wp_customize->add_setting( 'hrb_options[projects_per_page]', array(
		'default' => $hrb_options->projects_per_page,
		'type' => 'option'
	) );

	$wp_customize->add_setting( 'hrb_options[projects_frontpage]', array(
		'default' => $hrb_options->projects_frontpage,
		'type' => 'option'
	) );

	$wp_customize->add_setting( 'hrb_options[users_per_page]', array(
		'default' => $hrb_options->users_per_page,
		'type' => 'option'
	) );

	$wp_customize->add_setting( 'hrb_options[users_frontpage]', array(
		'default' => $hrb_options->users_frontpage,
		'type' => 'option'
	) );

	$wp_customize->add_control( 'hrb_projects_frontpage', array(
		'label'      => __( 'Projects Front Page', APP_TD ),
		'section'    => 'hrb_listings',
		'settings'   => 'hrb_options[projects_frontpage]',
		'type'       => 'text',
	) );

	$wp_customize->add_control( 'hrb_projects_per_page', array(
		'label'      => __( 'Projects Per Page', APP_TD ),
		'section'    => 'hrb_listings',
		'settings'   => 'hrb_options[projects_per_page]',
		'type'       => 'text',
	) );

	$wp_customize->add_control( 'hrb_users_frontpage', array(
		'label'      => __( 'Users Front Page', APP_TD ),
		'section'    => 'hrb_listings',
		'settings'   => 'hrb_options[users_frontpage]',
		'type'       => 'text',
	) );

	$wp_customize->add_control( 'hrb_users_per_page', array(
		'label'      => __( 'Users Per Page', APP_TD ),
		'section'    => 'hrb_listings',
		'settings'   => 'hrb_options[users_per_page]',
		'type'       => 'text',
	) );


}

/**
 *
 */
function _hrb_customize_categories( $wp_customize ){
	categories_options( 'categories_dir', __( 'Categories Page Options', APP_TD ), $wp_customize );
	categories_options( 'categories_menu', __( 'Categories Menu Item Options', APP_TD ), $wp_customize );

}

### Helper functions

/**
 * Display categories related options in the customizer.
 */
function categories_options( $prefix, $title, $wp_customize ) {
	global $hrb_options;

	$wp_customize->add_section( 'hrb_'.$prefix.'_categories', array(
		'title' => $title,
		'priority' => 999,
	));

	if ( 'categories_menu' == $prefix ) {

		$wp_customize->add_setting( 'hrb_options['.$prefix.'][show]', array(
			'default' => 'yes',
			'type' => 'option'
		) );

		$wp_customize->add_control( 'hrb_'.$prefix.'_show', array(
				'label'      => __( 'Categories Menu Behavior', APP_TD ),
				'section'    => 'hrb_'.$prefix.'_categories',
				'settings'   => 'hrb_options['.$prefix.'][show]',
				'type'       => 'select',
				'choices' => array(
					'always' => __( 'Always Visible', APP_TD ),
					'click' => __( 'Visible on Click', APP_TD ),
					'' => __( 'Hide', APP_TD ),
				),
				'default' =>  'click',
			) );
	}

	$wp_customize->add_setting( 'hrb_options['.$prefix.'][count]', array(
		'default' => $hrb_options->projects_per_page,
		'type' => 'option'
	) );

	$wp_customize->add_control( 'hrb_'.$prefix.'_count', array(
		'label'      => __( 'Count Listings in Category', APP_TD ),
		'section'    => 'hrb_'.$prefix.'_categories',
		'settings'   => 'hrb_options['.$prefix.'][count]',
		'type'       => 'checkbox',
	) );

	$wp_customize->add_setting( 'hrb_options['.$prefix.'][hide_empty]', array(
		'default' => '',
		'type' => 'option'
	) );

	$wp_customize->add_control( 'hrb_'.$prefix.'_hide_empty', array(
		'label'      => __( 'Hide Empty Categories', APP_TD ),
		'section'    => 'hrb_'.$prefix.'_categories',
		'settings'   => 'hrb_options['.$prefix.'][hide_empty]',
		'type'       => 'checkbox',
	) );

	$wp_customize->add_setting( 'hrb_options['.$prefix.'][depth]', array(
		'default' => 1,
		'type' => 'option'
	) );

	$wp_customize->add_control( 'hrb_'.$prefix.'_depth', array(
		'label'      => __( 'Category Depth', APP_TD ),
		'section'    => 'hrb_'.$prefix.'_categories',
		'settings'   => 'hrb_options['.$prefix.'][depth]',
		'type'       => 'select',
		'choices' => array(
			'999' => __( 'Show All', APP_TD ),
			'0' => '0',
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => '9',
			'10' => '10',
		),
	) );

	$wp_customize->add_setting( 'hrb_options['.$prefix.'][sub_num]', array(
		'default' => $hrb_options->projects_per_page,
		'type' => 'option'
	) );

	$wp_customize->add_control( 'hrb_'.$prefix.'_sub_num', array(
		'label'      => __( 'Number of Sub-Categories', APP_TD ),
		'section'    => 'hrb_'.$prefix.'_categories',
		'settings'   => 'hrb_options['.$prefix.'][sub_num]',
		'type'       => 'select',
		'choices' => array(
			'999' => __( 'Show All', APP_TD ),
			'0' => '0',
			'1' => '1',
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => '5',
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => '9',
			'10' => '10',
		),
	) );

}

/**
 * Retrieves the theme available color choices.
 *
 * @uses apply_filters() Calls 'hrb_color_choices'
 *
 */
function hrb_get_color_choices(){
	$color_choices = array(
		'modern' => __( 'Bee Modern (default)', APP_TD ),
		'green' => __( 'Bee Green', APP_TD ),
		'water' => __( 'Bee Water', APP_TD ),
		'urban' => __( 'Bee Urban', APP_TD ),
		'dark' => __( 'Bee Dark', APP_TD ),
	);
	return apply_filters( 'hrb_color_choices', $color_choices );
}
