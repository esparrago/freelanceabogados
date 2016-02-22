<?php
/**
 * Functions related with payments and purchases.
 */

if ( is_admin() ) {
	add_filter( 'the_title', '_hrb_pricing_modify_title', 10, 2 );
}
add_filter( 'parent_file', '_hrb_pricing_menu_edit_page_menu_workaround' );

add_action( 'init', '_hrb_register_pricing_plans_post_type', 10 );
add_action( 'admin_menu', '_hrb_pricing_add_menu', 11 );



### Hooks Callbacks

/**
 * Registers the projects pricing plans post type.
 */
function _hrb_register_pricing_plans_post_type() {

	$labels = array(
		'name' => __( 'Project Plans', APP_TD ),
		'singular_name' => __( 'Project Plan', APP_TD ),
		'add_new' => __( 'Add New', APP_TD ),
		'add_new_item' => __( 'Add New Plan', APP_TD ),
		'edit_item' => __( 'Edit Plan', APP_TD ),
		'new_item' => __( 'New Plan', APP_TD ),
		'view_item' => __( 'View Plan', APP_TD ),
		'search_items' => __( 'Search Plans', APP_TD ),
		'not_found' => __( 'No Plans found', APP_TD ),
		'not_found_in_trash' => __( 'No Plans found in Trash', APP_TD ),
		'parent_item_colon' => __( 'Parent Plan:', APP_TD ),
		'menu_name' => __( 'Project Plans', APP_TD ),
	);

	$args = array(
		'labels' => $labels,
		'hierarchical' => false,
		'supports' => array( 'page-attributes' ),
		'taxonomies' => array( HRB_PROJECTS_CATEGORY ),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => false,
	);

	register_post_type( HRB_PRICE_PLAN_PTYPE, $args );

}

/**
 * Adds the pricing plans items to the admin sidebar sub-menu.
 */
function _hrb_pricing_add_menu() {
	global $pagenow, $typenow;

	$ptypes = hrb_get_plan_types();

	foreach( $ptypes as $ptype ) {
		$ptype_obj = get_post_type_object( $ptype );

		add_submenu_page( 'app-payments', $ptype_obj->labels->name, $ptype_obj->labels->all_items, $ptype_obj->cap->edit_posts, "edit.php?post_type=$ptype" );

		if ( $pagenow == 'post-new.php' && $typenow == $ptype ) {
			add_submenu_page( 'app-payments', $ptype_obj->labels->new_item, $ptype_obj->labels->new_item, $ptype_obj->cap->edit_posts, "post-new.php?post_type=$ptype" );
		}
	}
}

/**
 * Makes sure the sub-menu retrieves the correct payments slug.
 */
function _hrb_pricing_menu_edit_page_menu_workaround( $parent_file ) {
	global $pagenow, $typenow;

	$ptypes = hrb_get_plan_types();

	foreach( $ptypes as $ptype ) {
		if ( $parent_file == "edit.php?post_type=$ptype" && ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) && $typenow == $ptype ) {
			return 'app-payments';
		}
	}
	return $parent_file;
}

/**
 * Retrieves the pricing plan title from plan meta instead of the post title.
 */
function _hrb_pricing_modify_title( $title, $post_id ) {

	$post = get_post( $post_id );

	if ( $post && ! in_array( $post->post_type, hrb_get_plan_types() ) ) {
		return $title;
	}

	return get_post_meta( $post_id, 'title', true );
}


### Main WP_Query for Plans

/**
 * Retrieves all plan types by querying the database using a WP_Query.
 */
function hrb_get_plans( $plan_types, $args = array() ) {

	if ( ! $plan_types ) {
		$plan_types = hrb_get_plan_types();
	}

	$defaults = array(
		'post_type' => $plan_types,
		'nopaging' => true,
		'post_status' => 'publish',
		'orderby' => 'menu_order'
	);
	$args = wp_parse_args( $args, $defaults );

	$plans = new WP_Query( $args );

	$plans_data = array();

	foreach( $plans->posts as $key => $post ) {
		$plans_data[ $key ] = hrb_get_plan_data( $post->ID );
		$plans_data[ $key ]['post'] = $post;
	}
	return $plans_data;
}

### Verbiages

/**
 * Retrieves all the Orders statuses verbiages or a single status verbiage.
 */
function hrb_get_order_statuses_verbiages( $status = '', $order = '' ) {

	$verbiages = array(
		APPTHEMES_ORDER_PENDING => __( 'Pending Payment', APP_TD ),
		APPTHEMES_ORDER_PAID => __( 'Funds Available', APP_TD ),
		APPTHEMES_ORDER_FAILED => __( 'Payment Failed', APP_TD ),
		APPTHEMES_ORDER_REFUNDED => __( 'Refunded', APP_TD ),
		APPTHEMES_ORDER_COMPLETED => __( 'Pending Moderation / Paid', APP_TD ),
		APPTHEMES_ORDER_ACTIVATED => __( 'Activated', APP_TD ),
	);

	if ( ! $order ) {
		$verbiages[ APPTHEMES_ORDER_COMPLETED ] = __( 'Pending Moderation / Paid', APP_TD );
	} elseif ( $order->is_escrow() ){
		$verbiages[ APPTHEMES_ORDER_COMPLETED ] = __( 'Paid', APP_TD );
	}

	return hrb_get_verbiage_values( $verbiages, $status );
}


### Helper Functions

/**
 * Retrieves all the available plan types.
 */
function hrb_get_plan_types() {

	$types = array( HRB_PRICE_PLAN_PTYPE, HRB_PROPOSAL_PLAN_PTYPE );

	return apply_filters( 'hrb_plan_types', $types );
}

/**
 * Scans the items of an order and retrieves the purchased 'post', if any.
 *
 * Notes:
 * . Single plans purchase (e.g: credits plans) don't include a post in the items
 * . A purchased 'post' represents a project purchase
 *
 */
function hrb_get_order_post( $order, $post_type = '' ) {

	foreach( $order->get_items() as $item ) {
		if ( ( $post_type && $post_type == $item['post']->post_type ) || ! $post_type ) {
			return $item['post'];
		}
	}
	return false;
}

/**
 * Retrieves the plan data from a given Order.
 */
function hrb_get_order_plan_data( $order, $post_types = '' ) {

	// retrieve plan data from all plan types if the type was not specified
	if ( ! $post_types ) {
		$post_types = hrb_get_plan_types();
	}

	$plans = hrb_get_plans( $post_types );

	foreach( $plans as $key => $plan ) {

		if ( empty( $plan['post']->post_name ) ) {
			continue;
		}

		$plan_slug = $plan['post']->post_name;

		$items = $order->get_items( $plan_slug );

		if ( $items ) {
			return array(
				'post_id' => $items[0]['post_id'],
				'post' => $items[0]['post'],
				'plan' => $plan['post'],
				'plan_data' => $plan,
			);
		}
	}
	return false;
}

/**
 * Retrieves the Orders assigned to a User.
 */
function hrb_get_orders_for_user( $user_id, $args = array() ) {

	$defaults = array(
		'ignore_sticky_posts' => true,
		'author' => $user_id,
		'post_type' => APPTHEMES_ORDER_PTYPE,
		'no_paging' => true,
	);
	$args = wp_parse_args( $args, $defaults );

	return new WP_Query( $args );
}

/**
 * Retrieves all data for a given plan.
 */
function hrb_get_plan_data( $plan_id ) {

	$data = get_post_custom( $plan_id );

	$collapsed_data = array();

	foreach( $data as $key => $array ) {
		$collapsed_data[$key] = $array[0];
	}
	$collapsed_data['ID'] = $plan_id;

	// make sure we have valid price and relist price

	if ( empty( $collapsed_data['price'] ) ) {
		$collapsed_data['price'] = 0;
	}

	if ( empty( $collapsed_data['relist_price'] ) ) {
		$collapsed_data['relist_price'] = 0;
	}

	return $collapsed_data;
}

/**
 * Retrieves the pending order for a post, if exists.
 */
function hrb_get_pending_order_for( $post_id ) {

	$order = appthemes_get_order_connected_to( $post_id, array( 'post_status' => APPTHEMES_ORDER_PENDING ) );

	if ( empty( $order ) ) {
		return false;
	} else {
		return $order;
	}
}

/**
 * Retrieves the items summary for an Order.
 */
function get_the_hrb_order_summary( $order ) {

	$order_items = $addons = array();

	$items = $order->get_items();

	foreach( $items as $item ) {

		if ( ! APP_Item_Registry::is_registered( $item['type'] ) ) {
			$item_title = __( 'Unknown', APP_TD );
		} else {
			$item_title = APP_Item_Registry::get_title( $item['type'] );
		}

		$meta = array(
			'type' => $item['type'],
			'title' => $item_title,
			'price' => appthemes_get_price( $item['price'] ),
		);

		if ( hrb_item_is_addon_or_related( $item['type'] ) ) {
			$addons[] = $meta;
		} else {
			$order_items[0] = $meta;
		}

	}

	$order_items[0]['addons'] = $addons;

	return $order_items;
}

/**
 * Rerieves all the registered currencies.
 */
function hrb_get_currencies( $force_all = false ) {
	global $hrb_options;

	$currencies = array();

	if ( ! $force_all && ! empty(  $hrb_options->allowed_currencies ) ) {
		$allowed = array_flip( $hrb_options->allowed_currencies );
	}

	foreach ( APP_Currencies::get_currency_string_array() as $key => $name ) {

		// skip currency if not explicitily allowed
		if ( ! empty( $allowed ) && ! isset( $allowed[ $key]) ) {
			continue;
		}

		$currency = APP_Currencies::get_currency( $key );
		$currencies[ $key ] = array(
			'name' => $name,
			'symbol' => $currency['symbol'],
		);
	}
	return $currencies;
}

/**
 * Cancels the given Order.
 */
function hrb_cancel_order( $order ) {

	if ( APPTHEMES_ORDER_PAID == $order->get_status() ) {
		$workspace = $order->get_item();
		hrb_workspace_refund_author( $workspace['post_id'] );
	} else {
		$order->failed();
	}

	// @todo hook into 'appthemes_order_failed' hook

	$post = hrb_get_order_post( $order );

	// make sure previous relisted posts (expired) status are reset to 'expired' instead of staying 'pending'
	if ( $relisted = get_post_meta( $post->ID, '_hrb_relisted', true ) ) {

		if ( $relisted ) {
			hrb_update_post_status( $post->ID, HRB_PROJECT_STATUS_EXPIRED );
			delete_post_meta( $post->ID, '_hrb_relisted' );
		}
	}

}

/**
 * Checks in an Order is for a separate plan purchase (e.g: Credits Plans), with no 'purchased' posts.
 * In case of a separate purchase retrieves the plan data.
 */
function hrb_get_separate_plan_purchase( $order, $plan_post_type ) {

	// make sure we're dealing with a separate plan with no 'purchased' posts
	if ( ! hrb_get_order_post( $order, APPTHEMES_ORDER_PTYPE ) ) {
		return false;
	}

	$plan_data = hrb_get_order_plan_data( $order, $plan_post_type );

	// aditionaly check if the plan we are activating is the correct one
	if ( ! $plan_data  ) {
		return false;
	}

	return $plan_data;
}

/**
 * Outputs the redirect HTML.
 */
function hrb_js_redirect( $url ) {
	echo html( 'a', array( 'href' => $url, 'class' => 'redirect-text' ), __( 'Continue &#8594;', APP_TD ) );
	echo html( 'script', 'location.href="' . $url . '"' );
}


### Conditionals

/**
 * Checks if listings posting should be charged.
 */
function hrb_charge_listings() {
	global $hrb_options;
	return (bool) $hrb_options->project_charge;
}
