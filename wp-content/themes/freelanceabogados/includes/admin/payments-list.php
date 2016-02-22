<?php
/**
 * Custom columns for payments related pages
 */

// only load additional order columns after the payments module is loaded
add_action( 'after_setup_theme', 'hrb_admin_order_columns', 1000 );

add_filter( 'manage_' . HRB_PRICE_PLAN_PTYPE . '_posts_columns', 'hrb_plans_manage_columns', 11 );
add_action( 'manage_' . HRB_PRICE_PLAN_PTYPE . '_posts_custom_column', 'hrb_plans_add_column_data', 10, 2 );
add_filter( 'manage_edit-' . HRB_PRICE_PLAN_PTYPE . '_sortable_columns', 'hrb_plans_columns_sort' );

add_filter( 'manage_' . HRB_PROPOSAL_PLAN_PTYPE . '_posts_columns', 'hrb_credit_plans_manage_columns', 11 );
add_action( 'manage_' . HRB_PROPOSAL_PLAN_PTYPE . '_posts_custom_column', 'hrb_plans_add_column_data', 10, 2 );
add_filter( 'manage_edit-' . HRB_PROPOSAL_PLAN_PTYPE . '_sortable_columns', 'hrb_plans_columns_sort' );

function hrb_admin_order_columns() {
    add_action( 'manage_' . APPTHEMES_ORDER_PTYPE . '_posts_custom_column', 'hrb_orders_add_column_data', 15, 2 );
    add_filter( 'manage_' . APPTHEMES_ORDER_PTYPE . '_posts_columns', 'hrb_orders_manage_columns', 15 );
    add_filter( 'manage_edit-' . APPTHEMES_ORDER_PTYPE . '_sortable_columns', 'hrb_orders_columns_sort' );
}

### Orders

function hrb_orders_manage_columns( $columns ) {

	foreach ( $columns as $key => $column ) {
		if ( 'order_author' == $key ) {
			$columns_reorder['plan_type'] = __( 'Type', APP_TD );
		}
		$columns_reorder[ $key ] = $column;

	}
	return $columns_reorder;
}

function hrb_orders_add_column_data( $column_index, $post_id ) {

	$order = appthemes_get_order( $post_id );

	switch ( $column_index ) {

		case 'plan_type':
			$order_data = hrb_get_order_plan_data( $order );

			if ( ! $order_data ) {
				if ( $order->is_escrow() ) {
					echo __( 'Escrow', APP_TD );
					return;
				}
				return;
            }

			extract( $order_data );

			$obj = get_post_type_object( $plan->post_type );
			echo $obj->labels->singular_name;
			break;
	}
}

function hrb_orders_columns_sort($columns) {
	$columns['plan_type'] = 'plan_type';
	return $columns;
}


### Plans

function hrb_plans_manage_columns( $columns ) {

	foreach ( $columns as $key => $column ) {

		if ( 'date' == $key ) {
			$columns_reorder['price'] = __( 'Price', APP_TD );
			$columns_reorder['relist'] = __( 'Relist', APP_TD );
            $columns_reorder['duration'] = __( 'Duration', APP_TD );
		}
		$columns_reorder[ $key ] = $column;

	}

	return $columns_reorder;
}

function hrb_credit_plans_manage_columns( $columns ) {

	foreach ( $columns as $key => $column ) {

		if ( 'date' == $key ) {
			$columns_reorder['price'] = __( 'Price', APP_TD );
            $columns_reorder['credits'] = __( 'Credits', APP_TD );
		}
		$columns_reorder[$key] = $column;

	}

	return $columns_reorder;
}

function hrb_plans_add_column_data( $column_index, $post_id ) {
	switch ( $column_index ) {

		case 'price' :
			$price = get_post_meta( $post_id, 'price', true );
			$price = ! (int) $price ? __( 'Free', APP_TD ) : appthemes_get_price( $price );
			echo $price;
			break;

		case 'relist':
			$price = get_post_meta( $post_id, 'relist_price', true );
			$price = ! (int) $price ? __( 'Free', APP_TD ) : appthemes_get_price( $price );
			echo $price;
			break;

        case 'credits' :
			$credits = get_post_meta( $post_id, 'credits', true );
			echo $credits;
			break;

		case 'duration' :
			$duration = get_post_meta( $post_id, 'duration', true );
			echo ! empty($duration) ? sprintf( _n( '%d day', '%d days', $duration ), $duration ) : '-';
			break;

	}
}

function hrb_plans_columns_sort($columns) {
	$columns['price'] = 'price';
    $columns['credits'] = 'credits';
	return $columns;
}