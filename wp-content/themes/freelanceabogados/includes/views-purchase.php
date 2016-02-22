<?php
/**
 * Views for purchase related pages.
 *
 * Views prepare and provide data to the page requested by the user.
 *
 * Notes:
 * . Contains HTML output helper functions used in purchase Views.
 *
 */

/**
 * Base class for purchase views.
 */
class HRB_Order extends APP_View {

	function init() {
		global $wp;

		$wp->add_query_var('bt_end');
		$wp->add_query_var('checkout');
	}

	function condition() {
		return (bool) get_query_var('checkout');
	}

	function parse_query( $wp_query ) {

		$checkout_type = get_query_var('checkout');

		// setup a new checkout after being redirected from a gateway page
		appthemes_setup_checkout( $checkout_type, $_SERVER['REQUEST_URI'] );

		$wp_query->set( 'checkout', get_query_var('checkout') );
	}

	function template_include( $template ) {

		$order = get_order();

		// point the progress bar to the final step if the order is complete.
		// by default, it shows the gateway process step (options/pay) when the order is processed by the gateway
		if ( $order && in_array( $order->get_status(), array( APPTHEMES_ORDER_PAID, APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ) {
			add_filter( 'appthemes_form_progress_current_step', array( $this, 'set_summary_step' ) );
		}

		return $template;
	}

	function set_summary_step( $step ) {
		return 'order-summary';
	}

}

/**
 * View for transfer escrow funds.
 */
class HRB_Escrow_Transfer extends APP_View_Page {

	function __construct() {
		parent::__construct( 'transfer-funds.php', __( 'Transfer Funds', APP_TD ), array( 'internal_use_only' => true ) );
	}

	static function get_id() {
		return parent::_get_id( __CLASS__ );
	}

	function template_include( $path ) {

		appthemes_require_login();

		appthemes_setup_checkout( 'chk-transfer-funds', get_permalink( self::get_id() ) );

		$checkout = appthemes_get_checkout();

		if ( ! $checkout->get_data('order_id') && ! empty( $_GET['oid'] ) ) {
			$order_id = (int) $_GET['oid'];
			$checkout->add_data( 'order_id', $order_id );
		}

		$order_id = $checkout->get_data('order_id');
		$order = appthemes_get_order( $order_id );

		if ( empty( $order ) || ! $order->get_items() ) {
			return locate_template( '404.php' );
		}

		$step_found = appthemes_process_checkout();

		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return $path;
	}

	function template_redirect() {
		global $wp_query;

		$wp_query->is_home = false;

		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function body_class($classes) {
		$classes[] = 'app-escrow-transfer';
		return $classes;
	}

	function title_parts( $parts ) {
		$checkout = appthemes_get_checkout();
		$order_id = $checkout->get_data('order_id');

		$order = appthemes_get_order( $order_id );

		$workspace = $order->get_item( HRB_WORKSPACE_PTYPE );

		if ( ! empty( $workspace ) ) {
			$parts[0] .=  sprintf( __( " for '%s'", APP_TD ), $workspace['post']->post_title );
		}

		return $parts;
	}
}

### Credits

/**
 * View for credits purchase.
 */
class HRB_Credits_Purchase extends APP_View_Page {

	function __construct() {
		parent::__construct( 'purchase-credits.php', __( 'Purchase Credits', APP_TD ), array( 'internal_use_only' => true ) );
	}

	static function get_id() {
		return parent::_get_id( __CLASS__ );
	}

	function template_include( $path ) {

		appthemes_require_login();

		appthemes_setup_checkout( 'chk-credits-purchase', get_permalink( self::get_id() ) );

		$step_found = appthemes_process_checkout();
		if ( ! $step_found ) {
			return locate_template( '404.php' );
		}

		return $path;
	}

	function template_redirect() {
		global $wp_query;

		$wp_query->is_home = false;

		add_filter( 'body_class', array( $this, 'body_class' ), 99 );
	}

	function body_class($classes) {
		$classes[] = 'app-credits-purchase';
		return $classes;
	}

}


### Helper Functions

/**
 * Retrieves the required vars from a given Order object, for use on an Order summary page.
 *
 * @uses apply_filters() Calls 'hrb_order_summary_template_vars'
 *
 */
function _hrb_get_order_summary_template_vars( $order = '', $checkout = '' ) {

	if ( ! $order ) {
		$order = get_order();
	}

	if ( ! $checkout ) {
		$checkout = appthemes_get_checkout();
	}

	if ( $checkout ) {
		$continue_to = $checkout->get_data('continue_to');
	}

	// get the purchased post permalink from the order if not available from the checkout object
	if ( empty( $continue_to ) ) {

		if ( $order_post = hrb_get_order_post( $order, HRB_PROJECTS_PTYPE ) ) {
			$post_type_obj = get_post_type_object( $order_post->post_type );

			$continue_to = array(
				'title' => $post_type_obj->labels->singular_name,
				'url' => get_permalink( $order_post->ID )
			);

			$continue_post = $order_post;

		} else {

			if ( $order->is_escrow() ) {

				$workspace = $order->get_item( HRB_WORKSPACE_PTYPE );
				$workspace_id = $workspace['post_id'];

				$continue_to = array(
					'title' => $workspace['post']->post_title,
					'url' => hrb_get_workspace_url( $workspace_id )
				);

				$continue_post = $workspace['post'];

			} else {
				$continue_to = array(
					'title' => __( 'Dashboard', APP_TD),
					'url' => hrb_get_dashboard_url_for('payments')
				);
			}

		}

	}

	// if the post is not published direct user to his dashboard
	if ( empty( $continue_post ) || ( ! empty( $continue_post ) && 'publish' != $continue_post->post_status ) ) {

		$continue_to = array(
			'title' => __( 'Dashboard', APP_TD),
			'url' => hrb_get_dashboard_url_for('projects')
		);

	}

	$template_vars = array(
		'order'			=> $order,
		'url'			=> $continue_to['url'],
		'bt_step_text'	=> sprintf( __( 'Continue to %s', APP_TD ), $continue_to['title'] ),
	);

	return apply_filters( 'hrb_order_summary_template_vars', $template_vars );
}


### Helper Functions

/**
 * Outputs the input option HTML and the related description for an addon that can be purchased.
 */
function _hrb_output_purchaseable_addon( $addon_id, $plan_id, $addons = array() ) {

	$plan = hrb_get_plan_data( $plan_id );
	$addon = hrb_get_addon_attributes( $addon_id );

	if ( ! empty( $plan[ $addon_id ] ) ) {

		_hrb_output_addon_option( $addon_id, true, true, $plan_id );

		if ( $addon['duration'] == 0 ){
			$string = __( ' %s is included in this plan for Unlimited days.', APP_TD );
			printf( $string, $addon['title'], $addon['price'] );
		} else {
			$string = _n( '%s is included in this plan for %s day.', '%s is included in this plan for %s days.', $plan[ $addon_id . '_duration' ], APP_TD );
			printf( $string, $addon['title'], $plan[ $addon_id . '_duration' ], $addon['price'] );
		}

	} elseif( ! hrb_addon_disabled( $addon_id ) ) {

		_hrb_output_addon_option( $addon_id, false, in_array( $addon_id, $addons ), $plan_id );

		if ( $addon['duration'] == 0 ) {
			$string = __( ' %s for Unlimited days for only %s more.', APP_TD );
			printf( $string, $addon['title'], $addon['price'] );
		} else {
			$string = __( ' %s for %d days for only %s more.', APP_TD );
			printf( $string, $addon['title'], $addon['duration'], $addon['price'] );
		}

	}

}

/**
 * Shows the field for an addon that has already been purchased.
 */
function _hrb_output_purchased_addon( $addon_id, $plan_id, $project_id ) {

	$addon = hrb_get_addon_attributes( $addon_id );

	_hrb_output_addon_option( $addon_id, true, true, $plan_id );

	$expiration_date = hrb_get_addon_expiration_date( $addon_id, $project_id );
	if ( 'Never' == $expiration_date ) {
		printf( __( ' %s for Unlimited days', APP_TD ), $addon['title'] );
	} else {
		printf( __( ' %s until %s', APP_TD ), $addon['title'], $expiration_date );
	}
	return;

}

/**
 * Outputs an addon input option HTML.
 */
function _hrb_output_addon_option( $addon, $enabled = false, $checked = false, $plan_id = '' ) {

	$name = $addon;
	if ( !empty( $plan_id ) ) {
		$name = $addon . '_' . $plan_id;
	}

	echo html( 'input', array(
		'name' => $name,
		'type' => 'checkbox',
		'disabled' => $enabled,
		'checked' => $checked,
	) );
}