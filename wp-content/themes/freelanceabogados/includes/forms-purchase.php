<?php
/**
 * Views for handling purchase related forms.
 *
 * Form-Views handle and process form data as well as user actions.
 *
 */


### Plans Selection

/**
 * Select plan View.
 */
class HRB_Select_Plan_New extends APP_Checkout_Step {

	public function __construct(){

		if ( ! hrb_charge_listings() ) {
			return;
		}

		$this->setup( 'select-plan', array(
			'register_to' => array(
				'chk-create-project' => array( 'after' => 'preview' ),
				'chk-renew-project' => array( 'after' => 'preview' ),
			),
		));
	}

	/**
	 * Loads/displays the Project Plan Select template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_select_plan_template_vars'
	 *
	 */
	public function display( $order, $checkout ){
		global $hrb_options;

		$project_id = 0;

		$project = $checkout->get_data('project');

		$args['tax_query'] = array(
			array(
				'taxonomy' => HRB_PROJECTS_CATEGORY,
				'field' => 'id',
				'terms' => wp_get_object_terms( $project->ID, HRB_PROJECTS_CATEGORY, array( 'fields' => 'ids' ) ),
			)
		);

		$plans = hrb_get_plans( HRB_PRICE_PLAN_PTYPE, $args );

		// look for selected addons for the current checkout
		$addons = (array) $checkout->get_data('addons');
		$plan = (array) $checkout->get_data('plan');
		$is_relist = (bool) $checkout->get_data('relist');

		if ( ! empty( $project ) ) {

			$project_id = $project->ID;

			$order = hrb_get_pending_order_for( $project_id );
			if ( $order ) {
				$checkout->add_data( 'order_id', $order->get_id() );
			}

		}

        $template_vars = array(
			'hrb_options'		=> $hrb_options,
			'project_id'		=> $project_id,
			'plans'				=> $plans,
			'sel_plan'			=> reset( $plan ),	// selected plans
			'sel_addons'		=> $addons,			// selected addons
			'is_relist'			=> $is_relist,
			'bt_step_text'		=> __( 'Continue', APP_TD ),
		);
        $template_vars = apply_filters( 'hrb_select_plan_template_vars', $template_vars, HRB_PRICE_PLAN_PTYPE );

		appthemes_load_template( 'form-project-purchase-new.php', $template_vars );
	}

	/**
	 * Validates the rules for selecting a project plan.
	 *
	 * @uses apply_filters() Calls 'hrb_select_plan_validate'
	 *
	 */
	function validate( $order, $checkout ) {

		if ( !isset( $_POST['action'] ) || 'purchase-project' != $_POST['action'] ) {
			return false;
        }

		if ( ! current_user_can('edit_projects') ) {
            appthemes_add_notice( 'cannot-edit-projects', __( 'You don\'t have permissions to edit projects', APP_TD ) );
			return false;
        }

		APP_Notices::$notices = apply_filters( 'hrb_select_plan_validate', APP_Notices::$notices, HRB_PRICE_PLAN_PTYPE, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
		return true;
	}

	public function process( $order, $checkout ) {

		if ( ! $this->validate( $order, $checkout ) ) {
			return;
        }

        $plan = $this->update_plan( $order, $checkout );
		if ( ! $plan ) {
            // there are errors, return to current page
			return;
		}

		do_action( 'appthemes_create_order', $order, HRB_PRICE_PLAN_PTYPE );

		$this->finish_step();
    }

	/**
	 * Validates the posted fields on an plan select form.
	 *
	 * @uses apply_filters() Calls 'appthemes_validate_purchase_fields'
	 *
	 */
	 function validate_fields( $order, $checkout, $plan_type ) {

		if ( empty( $_POST['plan'] ) ){
			appthemes_add_notice( 'no-plan', __( 'Please choose a plan.', APP_TD ) );
			return false;
		}

        APP_Notices::$notices = apply_filters( 'appthemes_validate_purchase_fields', APP_Notices::$notices, $plan_type, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
        return true;
	}

    function update_plan( $order, $checkout ) {

        if ( ! $this->validate_fields( $order, $checkout, HRB_PRICE_PLAN_PTYPE ) ) {
            return false;
        }

		$plan_id = $this->get_posted_plan();
		if ( ! $plan_id ) {
			return false;
		}

		$project = $checkout->get_data('project');
		$addons = $this->get_posted_addons( $plan_id );

		$checkout->add_data( 'plan', $plan_id );
		$checkout->add_data( 'addons', $addons );

		$post_type_obj = get_post_type_object( $project->post_type );

		$checkout->add_data( 'continue_to', array(
			'title' => $post_type_obj->labels->singular_name,
			'url' => get_permalink( $project->ID )
		) );

		if ( hrb_charge_listings() ) {

			$this->clear_order_coupons( $order );

			$this->add_plan_to_order( $order, $checkout, $plan_id, $project->ID );
			$this->add_addons_to_order( $order, $project->ID, $plan_id, $addons );

			$this->set_order_description( $order, $checkout, $project->ID );
		}

        return true;
	}

	/**
	 * Retrieves the posted Plan, if selected
	 * @return int/boolean The selected plan ID or false, if no plan was selected
	 */
	protected function get_posted_plan(){

		$plan = get_post( (int) $_POST['plan'] );
		if ( ! $plan ){
			appthemes_add_notice( 'invalid-plan', __( 'The plan you chose no longer exists.', APP_TD ) );
			return false;
		}

		return $plan->ID ;
	}

	/**
	 * Retrieves selected/included addons
	 */
	protected function get_posted_addons( $plan_id ) {
		$addons = array();

		$plan_data = hrb_get_plan_data( $plan_id );

		foreach( (array) hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {

			// make sure we include purchased addons as well as addons offered by the plan
			if ( ! empty( $_POST[ $addon . '_' . (int) $_POST['plan'] ] ) || ! empty( $plan_data[ $addon ] ) ) {
				$addons[] = $addon;
			}

		}
		return $addons;
	}


	/**
	 * Assigns a plan to the Order
	 */
	function add_plan_to_order( $order, $checkout, $plan_id, $post_id = 0 ){

		// clear previous plan if available
		$this->clear_order_plan( $order );

		$plan = get_post( $plan_id );
		$plan_data = hrb_get_plan_data( $plan_id );

		$item = $order->get_item( $plan->post_name );
		if ( $item ) {
			return;
		}

		if ( $checkout->get_data('relist') ) {
			$price = $plan_data['relist_price'];
		} else {
			$price = $plan_data['price'];
		}

		$order->add_item( $plan->post_name, $price, $post_id );
	}

	/**
	 * Assigns a list of addons to the Order
	 */
	function add_addons_to_order( $order, $project_id, $plan_id, $addons ){

		// clear previous addons if available
		$this->clear_order_addons( $order );

		foreach( $addons as $addon_id ) {

			if ( hrb_project_has_addon( $project_id, $addon_id ) ) {
				continue;
			}

			$price = $this->calc_addon_price( $plan_id, $addon_id );

			$order->add_item( $addon_id, $price, $project_id );
		}

	}

	/**
	 * Sets the Order description that will later be displayed on the payment gateway page.
	 */
	function set_order_description( $order, $checkout, $post_id = 0 ) {
		$order_summary = '';

		if ( $post_id ) {
			$order_summary .= get_the_title( $post_id ) . ' :: ';
		}
		$order_summary .= $this->get_order_summary_content( $order, $checkout );

		$order->set_description( $order_summary );
	}

	/**
	 * Clears all addons from an Order to avoid duplicate items when a user updates the addons selection
	 */
	protected function clear_order_addons( $order ) {

		foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {
			$order->remove_item( $addon );
		}

	}

	/**
	 * Clears all plans from an Order to avoid duplicate items when a user updates the selection
	 */
	protected function clear_order_plan( $order, $plan_types = '' ) {

		foreach( hrb_get_plans( $plan_types ) as $plan ) {
			$order->remove_item( $plan['post']->post_name );
		}

	}

	/**
	 * Clears any coupons from the order.
	 */
	protected function clear_order_coupons( $order ) {
		if ( ! defined('APPTHEMES_COUPON_PTYPE') ) {
			return;
		}
		$order->remove_item( APPTHEMES_COUPON_PTYPE );
	}

	function get_order_summary_content( $order, $checkout ) {

		$order_items = $relist = '';

		if ( $checkout->get_data('relist') ) {
			$relist = ' ' . __( '(Relist)', APP_TD );
		}

		$items = $order->get_items();

		$order_plan = hrb_get_order_plan_data( $order );
		$plan_name = $order_plan['plan']->post_name;

		foreach( $items as $item ) {

			if ( ! APP_Item_Registry::is_registered( $item['type'] ) ) {
				$item_title = __( 'Unknown', APP_TD );
			} else {
				$item_title = APP_Item_Registry::get_title( $item['type'] );
			}

			if ( $item['type'] == $plan_name ) {
				$item_title .= $relist;
			}

			$item_html = ( $order_items ? ' / ' . $item_title : $item_title );

			$order_items .= $item_html;
		}

		if ( ! $order_items  ) {
			$order_items = '-';
		}

		return $order_items;
	}

	/**
	 * Calculate and retrieve a the addon price considering if it's included in a plan or purchased separately
	 */
	function calc_addon_price( $plan_id, $addon ) {

		$price = -1;

		$plan_data = hrb_get_plan_data( $plan_id );

		// make sure we don't charge the addon price it it's included in the plan
		if ( ! empty( $plan_data[ $addon ] ) ) {
			$price = 0;
		} elseif ( ! empty( $_POST[ $addon . '_' . $plan_id ] ) ) {
			$price = APP_Item_Registry::get_meta( $addon, 'price' );
		}
		return $price;
	}

}

/**
 * Select plan View for projects relisting.
 */
class HRB_Project_Form_Relist_Select_Plan extends HRB_Select_Plan_New {

	public function __construct(){

		parent::__construct( 'renew-project-plan', array(
			'register_to' => array(
				'renew-project' => array(
					'after' => 'chk-renew-project'
				),
		 	)
		) );

	}

}

/**
 * Select plan View for credits purchase.
 */
class HRB_Select_Credits_Plan_New extends HRB_Select_Plan_New {

	public function __construct(){

		if ( ! hrb_credit_plans_active() ) {
			return;
		}

		$this->setup( 'select-plan', array(
			'register_to' => array(
				'chk-credits-purchase',
			),
		));
	}

	/**
	 * Loads/displays the Credits Plan Select template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_select_plan_template_vars'
	 *
	 */
	public function display( $order, $checkout ) {
		global $hrb_options;

		$args = array(
			'meta_query' => array(
				array(
					'key' => 'credits',
					'value' => 0,
					'compare' => '>'
				),
			),
		);

		$plans = hrb_get_plans( HRB_PROPOSAL_PLAN_PTYPE, $args );

		// look for selected addons for the current checkout
		$addons = (array) $checkout->get_data('addons');

        $template_vars = array(
			'plans' => $plans,
			'addons' => $addons,
			'hrb_options' => $hrb_options,
			'bt_step_text' => __( 'Continue', APP_TD ),
		);
        $template_vars = apply_filters( 'hrb_select_plan_template_vars', $template_vars, HRB_PROPOSAL_PLAN_PTYPE );

		appthemes_load_template( 'form-credits-purchase.php', $template_vars );
	}

	/**
	 * Validates the rules for selecting a credits plan.
	 *
	 * @uses apply_filters() Calls 'hrb_select_plan_validate'
	 *
	 */
	function validate( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'purchase-credits' != $_POST['action'] ) {
			return false;
		}

		if ( ! current_user_can('edit_bids') ) {
            appthemes_add_notice( 'cannot-purchase-credits', __( 'You don\'t have permissions to purchase credits.', APP_TD ) );
			return false;
        }

		APP_Notices::$notices = apply_filters( 'hrb_select_plan_validate', APP_Notices::$notices, HRB_PROPOSAL_PLAN_PTYPE, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
		return true;
	}

	public function process( $order, $checkout ) {

		if ( ! $this->validate( $order, $checkout ) ) {
			return;
        }

        $plan = $this->update_plan( $order, $checkout );

		if ( ! $plan ) {
            // there are errors, return to current page
			return;
		}

		do_action( 'appthemes_create_order', $order, HRB_PROPOSAL_PLAN_PTYPE );

		$this->finish_step();
    }

    function update_plan( $order, $checkout ) {

        if ( ! $this->validate_fields( $order, $checkout, HRB_PROPOSAL_PLAN_PTYPE ) ) {
            return false;
        }

		$plan_id = $this->get_posted_plan();

		if ( ! $plan_id ) {
			return false;
		}

		$checkout->add_data( 'plan', $plan_id );

		$checkout->add_data( 'continue_to', array(
			'title' => __( 'Dashboard', APP_TD),
			'url' => hrb_get_dashboard_url_for('payments')
		) );

		if ( hrb_credit_plans_active() ) {
			$this->add_plan_to_order( $order, $checkout, $plan_id );

			$this->set_order_description( $order, $checkout );
		}
        return true;
	}

}


### Gateways and Order Processing

/**
 * Gateway select View.
 */
class HRB_Gateway_Select extends APP_Checkout_Step {

	public function __construct(){

		if ( hrb_charge_listings() ) {

			$register_to = array (
				'chk-create-project',
				'chk-renew-project',
			);

		}

		if ( hrb_credits_enabled() ) {
			$register_to[] = 'chk-credits-purchase';

		}

		if ( hrb_is_escrow_enabled() ) {
			$register_to[] = 'chk-transfer-funds';
		}

		if ( ! empty( $register_to ) ) {

			parent::__construct( 'gateway-select', array(
				'register_to' => $register_to,
			) );

		}
	}

	/**
	 * Loads/displays the Gateway Select template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_gateway_select_template_vars'
	 *
	 */
	public function display( $order, $checkout ) {

		if ( $order->get_total() > 0 ) {
			$bt_step_text = __( 'Pay', APP_TD );
		} else {
			$bt_step_text = __( 'Continue', APP_TD );
		}

        $template_vars = array(
			'bt_step_text' => $bt_step_text,
		);
        $template_vars = apply_filters( 'hrb_gateway_select_template_vars', $template_vars );

		appthemes_add_template_var( array( 'app_order' => $order ) );

		appthemes_load_template( 'order-select.php', $template_vars );

	}

	public function process( $order, $checkout ) {

		if ( $order->get_total() == 0 ) {
			$order->complete();
			$this->finish_step();
		}

		if ( ! empty( $_POST['payment_gateway'] ) ) {

			$is_valid = $order->set_gateway( $_POST['payment_gateway'] );

			if ( ! $is_valid ) {
				appthemes_add_notice( 'invalid-gateway', __( 'Invalid Gateway!', APP_TD ) );
				return false;
			}

			if ( $order->is_escrow() ) {

				$item = $order->get_item();

				// check if the receivers have the gateway fields filled out
				$gateway_values = hrb_escrow_receivers_gateway_fields_valid( $item['post_id'], $order->get_gateway() );
				if ( empty( $gateway_values ) ) {
					appthemes_add_notice( 'receiver-fields-missing', __( 'Receiver is missing required information for escrow transfer.<br/>Please try again later.', APP_TD )  );

					do_action( 'hrb_escrow_receivers_info_missing', $order, $checkout );

					return false;
				}
			}

			$this->finish_step();
		}

	}

}

/**
 * Processes a gateway - redirects user to the selected gateway.
 */
class HRB_Gateway_Process extends APP_Checkout_Step {

	public function __construct() {

		if ( hrb_charge_listings() ) {

			$register_to = array(
				'chk-create-project' => array(
					'after' => 'gateway-select',
				),
				'chk-renew-project' => array(
					'after' => 'gateway-select',
				),
			);

		}

		if ( hrb_credits_enabled() ) {

			$register_to['chk-credits-purchase'] = array(
				'after' => 'gateway-select',
			);

		}

		if ( hrb_is_escrow_enabled() ) {

			$register_to['chk-transfer-funds'] = array(
				'after' => 'gateway-select',
			);

		}

		if ( ! empty( $register_to ) ) {

			parent::__construct( 'gateway-process', array(
				'register_to' => $register_to,
			) );

		}

		add_filter( 'appthemes_order_return_url', array( $this, 'filter_return_url' ) );
	}

	/**
	 * Handle new projects being purchased and redirects the user to the next step.
	 *
	 * @uses do_action() Calls 'hrb_new_plan_order'
	 * @uses do_action() Calls 'hrb_new_project'
	 *
	 */
	public function process( $order, $checkout ) {

		// if the order was already processed skip processing it again
		if ( ! $checkout->get_data('processed') ) {

			$post = hrb_get_order_post( $order, HRB_PROJECTS_PTYPE );

			// reset status to 'pending' on relistings
			if ( $checkout->get_data('relist') && $post ) {

				$args = array(
					'ID' => $post->ID,
					'post_date' => date('Y-m-d H:i:s'),
					'post_status' => 'pending',
				);
				$result = wp_update_post( $args );

				if ( is_wp_error( $result ) ) {
					appthemes_add_notice( 'status-update', __( 'There was a problem processing the gateway. Please try again later.', APP_TD ) );
					return;
				}

			}

			if ( hrb_get_order_plan_data( $order ) ) {
				do_action( 'hrb_new_plan_order', $order, $checkout );
			}

			// check for a project purchase
			if ( $post && $post->ID ) {

				$project = $checkout->get_data('project');

				do_action( 'hrb_new_project', $project->ID, $order, $checkout );
			}

			$checkout->add_data( 'processed', true );
		}

		if ( $order->get_total() == 0 || in_array( $order->get_status(), array( APPTHEMES_ORDER_PAID, APPTHEMES_ORDER_REFUNDED, APPTHEMES_ORDER_COMPLETED, APPTHEMES_ORDER_ACTIVATED ) ) ) {
			$this->finish_step();
		} else {
			update_post_meta( $order->get_id(), 'redirect_to', appthemes_get_step_url( $checkout->get_next_step('gateway-process') ) );
			update_post_meta( $order->get_id(), 'complete_url', appthemes_get_step_url('order-summary') );
			update_post_meta( $order->get_id(), 'cancel_url', appthemes_get_step_url('gateway-select') );
			wp_redirect( $order->get_return_url() );
			exit;
		}

	}

	/**
	 * Modifies the Order return URL by adding the current step ID.
	 */
	public function filter_return_url( $url ) {
		$checkout = appthemes_get_checkout();
		if ( $checkout ) {
			$url = add_query_arg( array( 'step' => $this->step_id, 'checkout' => $checkout->get_checkout_type() ), $url );
		}
		return $url;
	}

}

/**
 * Order summary View.
 */
class HRB_Order_Summary extends APP_Checkout_Step {

	public function __construct(){

		if ( hrb_charge_listings() ) {

			$register_to = array(
				'chk-create-project' => array(
					'after' => 'gateway-process',
				),
				'chk-renew-project' => array(
					'after' => 'gateway-process'
				),
			);

		}

		if ( hrb_credits_enabled() ) {

			$register_to['chk-credits-purchase'] = array(
				'after' => 'gateway-process'
			);

		}

		if ( hrb_is_escrow_enabled() ) {

			$register_to['chk-transfer-funds'] = array(
				'after' => 'gateway-process'
			);

		}

		if ( ! empty( $register_to ) ) {

			parent::__construct( 'order-summary', array(
				'register_to' => $register_to,
			) );

		}

	}

	/**
	 * Loads/displays the Order Summary template form with all the required vars.
	 */
	public function display( $order, $checkout ) {
		appthemes_add_template_var( array( 'app_order' => $order ) );
		appthemes_load_template( 'order-summary-content.php', _hrb_get_order_summary_template_vars( $order, $checkout ) );
	}

}
