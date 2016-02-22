<?php

add_filter( 'manage_' . HRB_PROJECTS_PTYPE . '_posts_columns', 'hrb_project_manage_columns', 11 );
add_action( 'manage_' . HRB_PROJECTS_PTYPE . '_posts_custom_column', 'hrb_project_add_column_data', 10, 2 );
add_filter( 'manage_edit-' . HRB_PROJECTS_PTYPE . '_sortable_columns', 'hrb_project_columns_sort' );

add_filter( 'request', 'expire_column_orderby' );

add_action( 'save_post', 'hrb_project_bulk_save' );

function hrb_project_manage_columns( $columns ) {

	$comments = $columns['comments'];
	$date = $columns['date'];

	unset( $columns['date'] );
	unset( $columns['comments'] );

    $columns['bids'] = __( 'Proposals', APP_TD );
	$columns['status'] = __( 'Status', APP_TD );
	$columns['expire'] = __( 'Expire Date', APP_TD );
	$columns['comments'] = $comments;
	$columns['date'] = $date;

	return $columns;
}

function hrb_project_columns_sort( $columns ) {
    $columns['bids'] = 'bids';
	$columns['expire'] = 'expire';
	$columns['tax_project_category'] = HRB_PROJECTS_CATEGORY;
	return $columns;
}

function hrb_project_add_column_data( $column_index, $post_id ) {

	switch ( $column_index ) {

        case 'bids' :
            $bids = appthemes_get_post_bids( $post_id );
            echo $bids->get_total_bids();
            break;

        case 'expire' :
            $expiration_date = get_the_hrb_project_expire_date( $post_id );
            if ( $expiration_date ) {
                echo mysql2date( get_option('date_format'), $expiration_date );
				if ( is_hrb_project_expired( $post_id ) ) {
						echo html( 'p', array( 'class' => 'admin-expired' ), __( 'Expired', APP_TD ) );
				}
            } else {
                echo __( 'Endless', APP_TD );
            }
            break;

		case 'status':
			$post = get_post( $post_id );
			$status = get_post_status_object( $post->post_status );
			echo $status->label;
			break;
	}

}

function hrb_project_bulk_save( $post_id ) {

	if ( empty( $_REQUEST['post_type'] ) || HRB_PROJECTS_PTYPE !== $_REQUEST['post_type'] ) {
		return;
	}

	if ( !current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

}

function expire_column_orderby( $vars ) {
    if ( isset( $vars['orderby'] ) && 'expire' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key'	=> '_hrb_duration',
            'orderby'	=> 'meta_value_num'
        ) );
    }
	else if ( isset( $vars['orderby'] ) && 'bids' == $vars['orderby'] ) {
        $vars = array_merge( $vars, array(
            'meta_key' => APP_BIDS_P_BIDS_KEY,
            'orderby' => 'meta_value_num'
        ) );
    }
    return $vars;
}