<?php

class HRB_Pricing_General_Box extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'pricing-details', __( 'Pricing Details', APP_TD ), HRB_PRICE_PLAN_PTYPE, 'normal', 'high' );
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
			'desc' => sprintf( __( '%s ( e.g: %s )' , APP_TD ), APP_Currencies::get_current_symbol(), '15.00' ),
			'extra' => array(
				'style' => 'width: 50px;'
			),
			'tip' => __( 'The price for posting projects. Set to \'0\' to allow posting projects for free. Use numbers and decimal separators only.', APP_TD )
		);

		$plan_form[] = array(
			'title' => __( 'Relist Price', APP_TD ),
			'type' => 'text',
			'name' => 'relist_price',
			'desc' => sprintf( __( '%s ( e.g: %s )' , APP_TD ), APP_Currencies::get_current_symbol(), '5.00' ),
			'extra' => array(
				'style' => 'width: 50px;'
			),
			'tip' => __( 'The price for relisting projects. Set to \'0\' for free relistings. Use numbers and decimal separators only.', APP_TD )
		);

		$plan_form[] = array(
			'title' => __( 'Duration', APP_TD ),
			'type' => 'text',
			'name' => 'duration',
			'desc' => __( 'days ( 0 = Infinite )', APP_TD),
			'extra' => array(
				'style' => 'width: 50px;'
			),
			'tip' => __( 'The project duration until it expires.', APP_TD )
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

		if ( ! is_numeric( $data['duration'] ) ){
			$errors->add( 'duration', '' );
		}

		if ( $data['duration'] < 0 ) {
            $errors->add( 'duration', '' );
        }

        return $errors;
	}

	public function before_save( $data, $post_id ) {

        foreach( $data as $key => $value ) {

            if ( empty( $data[ $key ] ) ) {
                $data[ $key ] = 0;
            }

        }
		$data['duration'] = absint( $data['duration'] );

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


class HRB_Pricing_Addon_Box extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'pricing-addons', __( 'Featured Addons', APP_TD ), HRB_PRICE_PLAN_PTYPE, 'normal', 'high' );
	}

	public function form_fields(){

		$output = array();

		foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ){

			$enabled = array(
				'title' => APP_Item_Registry::get_title( $addon ),
				'type' => 'checkbox',
				'name' => $addon,
				'desc' => __( 'Included', APP_TD ),
			);

			$duration = array(
				'title' => __( 'Duration', APP_TD ),
				'type' => 'text',
				'name' => $addon . '_duration',
				'desc' => __( 'days', APP_TD ),
				'extra' => array(
					'size' => '3'
				),
			);

			$output[] = $enabled;
			$output[] = $duration;

		}

		return $output;

	}

	public function before_save( $data, $post_id ){

		foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ){

			if( !empty( $data[ $addon ] ) && empty( $data[ $addon . '_duration' ] ) ){
				$data[ $addon . '_duration' ] = get_post_meta( $post_id, 'duration', true );
			}

			$data[ $addon . '_duration' ] = absint( $data[ $addon . '_duration' ] );

		}

		return $data;
	}

	public function validate_post_data( $data, $post_id = 0 ){
		$errors = new WP_Error();

		$project_duration = (int) get_post_meta( $post_id, 'duration', true );
		foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ){

			if( !empty( $data[ $addon . '_duration' ] ) ){

				$addon_duration = $data[ $addon . '_duration' ];
				if ( ! is_numeric( $addon_duration ) )
					$errors->add( $addon . '_duration', '' );

				if ( (int)$addon_duration > $project_duration && $project_duration != 0 )
					$errors->add( $addon . '_duration', '' );

				if ( (int)$addon_duration < 0 )
					$errors->add( $addon . '_duration', '' );

			}

		}

		return $errors;
	}

	public function before_form( $post ){
		echo html( 'p', array(), __( 'You can include featured addons in a plan. These will be immediately added to the listing upon purchase. After they run out, the customer can then purchase regular featured addons.', APP_TD ) );
	}


	public function after_form( $post ){
		echo html( 'p', array('class' => 'howto'), __( 'Durations must be shorter than the listing duration.', APP_TD ) );
	}

}

