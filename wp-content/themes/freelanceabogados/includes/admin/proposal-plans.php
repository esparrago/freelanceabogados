<?php

class HRB_Proposal_General_Box extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'proposal-pricing-details', __( 'Pricing Details', APP_TD ), HRB_PROPOSAL_PLAN_PTYPE, 'normal', 'high' );
	}

	public function before_form( $post ){
		?><style type="text/css">#notice{ display: none; }</style><?php
	}

	public function form_fields(){
		$plan_form =  array();

		$plan_form[] = array(
			'title' => __( 'Plan Name', APP_TD ),
			'type' => 'text',
			'name' => 'title',
		);

		$plan_form[] = array(
			'title' => __( 'Description', APP_TD ),
			'type' => 'textarea',
			'name' => 'description',
			'extra' => array(
				'style' => 'width: 25em;'
			)
		);

		$plan_form[] = array(
			'title' => __( 'Price', APP_TD ),
			'type' => 'text',
			'name' => 'price',
			'desc' => sprintf( __( '%s ( e.g: %s )' , APP_TD ), APP_Currencies::get_current_symbol(), '5.00' ),
			'extra' => array(
				'style' => 'width: 50px;'
			),
			'tip' => __( 'The price for this plan. Set to \'0\' for free plans. Use numbers and decimal separators only.', APP_TD ),
		);

		$plan_form[] = array(
			'title' => __( 'Credits', APP_TD ),
			'type' => 'text',
			'name' => 'credits',
			'desc' => sprintf( __( 'e.g: %s' , APP_TD ), '5' ),
			'extra' => array(
				'style' => 'width: 50px;'
			),
			'tip' => __( 'Credits allow freelancers to apply to projects and feature proposals.', APP_TD),
		);

		return $plan_form;
	}

	public function validate_post_data( $data, $post_id = 0 ){

		$errors = new WP_Error();

		if ( empty( $data['title'] ) ){
			$errors->add( 'title', '' );
		}

		if ( ! is_numeric( $data['price'] ) ){
			$errors->add( 'price', '' );
		}

		if ( ! is_numeric( $data['credits'] ) ){
			$errors->add( 'credits', '' );
		}

		return $errors;
	}

	public function before_save( $data, $post_id ){
		return $data;
	}

	public function post_updated_messages( $messages ) {
		$messages[ HRB_PRICE_PLAN_PTYPE ] = array(
		 	1 => __( 'Plan updated.', APP_TD ),
		 	4 => __( 'Plan updated.', APP_TD ),
		 	6 => __( 'Plan created.', APP_TD ),
		 	7 => __( 'Plan saved.', APP_TD ),
		 	9 => __( 'Plan scheduled.', APP_TD ),
			10 => __( 'Plan draft updated.', APP_TD ),
		);
		return $messages;
	}

}
