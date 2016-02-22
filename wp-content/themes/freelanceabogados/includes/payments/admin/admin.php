<?php
/**
 * Register Payments admin menu
 *
 * @package Components\Payments\Admin
 */

if( is_admin() ){
	add_action( 'admin_menu', 'appthemes_admin_menu_setup', 11 );
	add_action( 'admin_print_styles', 'appthemes_payments_menu_sprite' );
	add_action( 'admin_print_styles', 'appthemes_payments_icon' );
	add_action( 'init', 'appthemes_register_payments_settings', 12);
	add_action( 'parse_request', 'appthemes_admin_quick_find_post' );
}

/**
 * Get the full URL for an image
 *
 * @param string $name The basename of the image
 * @return string
 */
function appthemes_payments_image( $name ) {
	return appthemes_payments_get_args( 'images_url' ) . $name;
}

/**
 * Registers the payment settings page
 * @return void
 */
function appthemes_register_payments_settings(){
	new APP_Payments_Settings_Admin( APP_Gateway_Registry::get_options() );
}

/**
 * Adds the Orders Top Level Menu
 * @return void
 */
function appthemes_admin_menu_setup(){
	add_menu_page( __( 'Orders', APP_TD ), __( 'Payments', APP_TD ), 'edit_others_posts', 'app-payments', null, appthemes_payments_image( 'payments.png' ), 4 );
}

/**
 * Adds the Payments Menu Sprite to the CSS for admin pages
 * @return void
 */
function appthemes_payments_menu_sprite() {
	$sprite_url = appthemes_payments_image( 'payments.png' );

echo <<<EOB
<style type="text/css">

#toplevel_page_app-payments div.wp-menu-image {
	background-image: url('$sprite_url');
	background-position: -31px 7px !important;
	background-repeat: no-repeat;
}

#toplevel_page_app-payments div.wp-menu-image img {
	display: none;
}

#toplevel_page_app-payments:hover div.wp-menu-image,
#toplevel_page_app-payments.wp-has-current-submenu div.wp-menu-image {
	background-position: -1px 7px !important;
}
</style>
EOB;

}

/**
 * Adds the Payments Icon for certain pages
 * @return void
 */
function appthemes_payments_icon(){
	$url = appthemes_payments_image( 'payments-med.png' );
?>
<style type="text/css">
	.icon32-posts-pricing-plan,
	.icon32-posts-transaction {
		background-image: url('<?php echo $url; ?>');
		background-position: -5px -5px !important;
	}
</style>
<?php
}

function appthemes_admin_quick_find_post( $wp_query ){
	global $pagenow;

	if( 'edit.php' != $pagenow )
		return;

	if( empty( $wp_query->query_vars['s'] ) )
		return;

	$query = $wp_query->query_vars['s'];
	if( '#' != substr( $query, 0, 1 ) )
		return;

	$id = absint( substr( $query, 1 ) );
	if( ! $id ){
		$wp_query->query_vars['s'] = 'Bad ID';
	}

	$post = get_post( $id );
	if( $post ){
		$wp_query->query_vars['s'] = get_edit_post_link( $id );
		wp_redirect( 'post.php?action=edit&post=' . $id  );
		exit;
	}else{
		$wp_query->query_vars['s'] = 'Not Found';
	}


}
