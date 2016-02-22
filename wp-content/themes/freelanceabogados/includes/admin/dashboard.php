<?php

class HRB_Dashboard extends APP_Dashboard {

	public function __construct(){

		parent::__construct( array(
			'page_title' => __( 'HireBee Dashboard', APP_TD ),
			'menu_title' => __( 'Freelance Abogados', APP_TD ),
			'icon_url' => appthemes_locate_template_uri( 'images/admin-menu.png' ),
		) );

		add_filter( 'post_caluses', array( $this, 'filter_past_days' ), 10, 2 );

		$stats_icon = $this->box_icon( 'chart-bar.png' );
		$stats = array( 'stats', $stats_icon .  __( 'Snapshot', APP_TD ), 'normal' );
		array_unshift( $this->boxes, $stats );

	}

	public function stats_box(){

		$users = array();
		$users_stats = $this->get_user_counts();

		$users[ __( 'New Registrations Today', APP_TD ) ] = $users_stats['today'];
		$users[ __( 'New Registrations Yesterday', APP_TD ) ] = $users_stats['yesterday'];

		$users[ __( 'Total Users', APP_TD ) ] = array(
			'text' => $users_stats['total_users'],
			'url' => 'users.php'
		);

		$this->output_list( $users, '<ul style="float: right; width: 45%">' );

		$stats = array();

		$listings = $this->get_project_counts();
		$stats[ __( 'New Projects (24 hours)', APP_TD ) ] = $listings['new'];
		if( isset( $listings['pending'] ) ){
			$stats[ __( 'Pending Projects', APP_TD ) ] = array(
				'text' => $listings['pending'],
				'url' => sprintf( 'edit.php?post_type=%s&post_status=pending', HRB_PROJECTS_PTYPE ),
			);
		}

		$stats[ __( 'Total Projects', APP_TD ) ] = array(
			'text' => $listings['all'],
			'url' => sprintf( 'edit.php?post_type=%s', HRB_PROJECTS_PTYPE )
		);

		if( current_theme_supports( 'app-payments' ) ){
			$orders = $this->get_order_counts();
			$stats[ __( 'Revenue (7 days)', APP_TD ) ] = appthemes_get_price( $orders['revenue'] );
		}

		$this->output_list( $stats );

	}

	private function output_list( $array, $begin = '<ul>', $end = '</ul>', $echo = true ){

		$html = '';
		foreach( $array as $title => $value ){
			if( is_array( $value ) ){
				$html .= '<li>' . $title . ': <a href="' . $value['url'] . '">' . $value['text'] . '</a></li>';
			}else{
				$html .= '<li>' . $title . ': ' . $value . '</li>';
			}
		}

		if ( $echo ) {
			echo $begin . $html . $end;
		} else {
			return $begin . $html . $end;
		}

	}

	private function get_user_counts(){

		$users = (array) count_users();

		global $wpdb;
		$capabilities_meta = $wpdb->prefix . 'capabilities';
		$date_today = date( 'Y-m-d' );
		$date_yesterday = date( 'Y-m-d', strtotime('-1 days') );

		$users['today'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key = %s AND ($wpdb->usermeta.meta_value NOT LIKE %s) AND $wpdb->users.user_registered >= %s", $capabilities_meta, '%administrator%', $date_today ) );
		$users['yesterday'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key = %s AND ($wpdb->usermeta.meta_value NOT LIKE %s) AND $wpdb->users.user_registered BETWEEN %s AND %s", $capabilities_meta, '%administrator%', $date_yesterday, $date_today ) );

		return $users;
	}

	private function get_project_counts(){

		$listings = (array) wp_count_posts( HRB_PROJECTS_PTYPE );

		$all = 0;
		foreach( (array) $listings as $type => $count ){
			$all += $count;
		}
		$listings['all'] = $all;

		$yesterday_posts = new WP_Query( array(
			'past_days' => 7
		) );
		$listings['new'] = $yesterday_posts->post_count;

		return $listings;

	}

	private function get_order_counts(){

		$orders = (array) wp_count_posts( APPTHEMES_ORDER_PTYPE );

		$week_orders = new WP_Query( array(
			'post_type' => APPTHEMES_ORDER_PTYPE,
			'past_days' => 7,
		) );

		$revenue = 0;
		foreach( $week_orders->posts as $post ){
			$revenue += (float) get_post_meta( $post->ID, 'total_price', true );
		}

		$orders['revenue'] = $revenue;
		return $orders;

	}

	public function filter_past_days( $clauses, $wp_query ){
		global $wp_query;

		$past_days = (int) $wp_query->get( 'past_days' );
		if( $past_days ){
			$clauses['where'] .= ' AND post_data > \'' . date( 'Y-m-d', strtotime( '-' . $past_days .' days' ) ) . '\'';
		}

		return $clauses;

	}


}
