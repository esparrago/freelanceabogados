<?php
/**
 * PayPal gateway
 *
 * @package Components\Payments\Gateways\PayPal
 */

require_once( dirname( __FILE__ ) . '/paypal-bridge.php' );
require_once( dirname( __FILE__ ) . '/paypal-notifier.php' );
require_once( dirname( __FILE__ ) . '/paypal-pdt.php' );
require_once( dirname( __FILE__ ) . '/paypal-ipn-listener.php' );
require_once( dirname( __FILE__ ) . '/paypal-form.php' );

/**
 * Payment Gateway to process PayPal Payments
 */
class APP_PayPal extends APP_Boomerang{

	/**
	 * API URLs to connect to
	 * @var array
	 */
	private static $urls = array(
		'https' => array(
			'sandbox' => 'https://www.sandbox.paypal.com/cgi-bin/webscr',
			'live' => 'https://www.paypal.com/cgi-bin/webscr'
		),
		'ssl' => array(
			'sandbox' => 'ssl://www.sandbox.paypal.com',
			'live' => 'ssl://www.paypal.com'
		)
	);

	/**
	 * Sets up the gateway
	 */
	public function __construct() {
		parent::__construct( 'paypal', array(
			'dropdown' => __( 'PayPal', APP_TD ),
			'admin' => __( 'PayPal', APP_TD ),
			'recurring' => true,
		) );

		add_action( 'init', array( $this, 'register') );
		$this->bridge = new APP_PayPal_Bridge;
	}

	public function register(){

		if( ! APP_Gateway_Registry::is_gateway_enabled( 'paypal' ) )
			return;

		$options = APP_Gateway_Registry::get_gateway_options( 'paypal' );
		if( !empty( $options['ipn_enabled'] ) )
			$this->listener = new APP_PayPal_IPN_Listener( array( 'APP_PayPal_Notifier', 'handle_response' ) );

	}

	/**
	 * Processes an Order Payment
	 * See APP_Gateway::process()
	 * @param  APP_Order $order   Order to process
	 * @param  array $options     User inputted options
	 * @return void
	 */
	public function process( $order, $options ) {

		if( !empty( $options['pdt_enabled'] ) ){

			if( APP_Paypal_PDT::can_be_handled() ){

				$transaction = APP_Paypal_PDT::get_transaction( $_GET['tx'], $options['pdt_key'], !empty( $options['sandbox_enabled'] ) );
				if( $transaction )
					$this->bridge->process_single( $transaction );
				else
					$this->fail_order( __( 'PayPal has responded to your transaction as invalid. Please contact site owner.', APP_TD ) );

			}

			else{
				$this->create_form( $order, $options );
			}

			return;
		}

		// Otherwise, validate regularly
		if( $this->is_returning() )
			$order->complete();
		else
			$this->create_form( $order, $options );

	}

	public function create_form( $order, $options ) {

		$return_url = $this->get_return_url( $order );
		$cancel_url = $this->get_cancel_url( $order );

		$values =  APP_PayPal_Form::create_form( $order, $options, $return_url, $cancel_url );

		if( !$values )
			return;

		list( $form, $fields ) = $values;
		$this->redirect( $form, $fields, __( 'You are now being redirected to PayPal.', APP_TD ) );
	}

	public static function get_request_url(){
		$options = APP_Gateway_Registry::get_gateway_options('paypal');
		return (  !empty( $options['sandbox_enabled'] ) ) ? self::$urls['https']['sandbox'] : self::$urls['https']['live'];
	}

	public static function get_ssl_url(){
		$options = APP_Gateway_Registry::get_gateway_options('paypal');
		return (  !empty( $options['sandbox_enabled'] ) ) ? self::$urls['ssl']['sandbox'] : self::$urls['ssl']['live'];
	}

	/**
	 * Returns an array for the administrative settings.
	 *
	 * See APP_Gateway::form()
	 *
	 * @uses apply_filters() Calls 'appthemes_paypal_settings_form'
	 *
	 * @return array scbForms style inputs
	 */
	public function form() {

		$general = array(
			'title' => __( 'General Information', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'PayPal Email', APP_TD ),
					'tip' => __( 'Enter your PayPal account email address. This is where your money gets sent.', APP_TD ),
					'type' => 'text',
					'name' => 'email_address',
					'extra' => array( 'size' => 50 )
				),
				array(
					'title' => __( 'Business/Premier Account', APP_TD ),
					'desc' => __( "This account is a Premier/Business Account", APP_TD ),
					'tip' => __( 'A premier/business account is required for allowing customers to process recurring payments via PayPal', APP_TD ),
					'type' => 'checkbox',
					'name' => 'business_account'
				),
				array(
					'title' => __( 'Sandbox Mode', APP_TD ),
					'desc' => sprintf( __( "You must have a <a target='_new' href='%s'>PayPal Sandbox</a> account setup before using this feature.", APP_TD ), 'http://developer.paypal.com/' ),
					'tip' => __( 'By default PayPal is set to live mode. If you would like to test and see if payments are being processed correctly, check this box to switch to sandbox mode.', APP_TD ),
					'type' => 'checkbox',
					'name' => 'sandbox_enabled'
				)
			)
		);

		$pdt = array(
			'title' => __( 'Payment Data Transfer (PDT)', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Enable PDT', APP_TD ),
					'desc' => sprintf( __( 'See our <a href="%s">tutorial</a> on enabling Payment Data Transfer.', APP_TD ), 'http://docs.appthemes.com/tutorials/enable-paypal-pdt-payment-data-transfer/' ),
					'type' => 'checkbox',
					'name' => 'pdt_enabled'
				),
				array(
					'title' => __( 'Identity Token', APP_TD ),
					'type' => 'text',
					'name' => 'pdt_key'
				),
			)
		);

		$ipn = array(
			'title' => __( 'Instant Payment Notification (IPN)', APP_TD ),
				'fields' => array(
					array(
						'title' => __( 'Enable IPN', APP_TD ),
						'tip'   => __( 'If any of your payment plans use recurring payments, this option must be checked. PayPal will be unable to communicate with your site otherwise.', APP_TD ),
						'type'  => 'checkbox',
						'name'  => 'ipn_enabled',
						'desc'  => sprintf( __( 'See our <a href="%s">tutorial</a> on enabling Instant Payment Notifications.', APP_TD ), 'http://docs.appthemes.com/tutorials/enable-paypal-ipn-instant-payment-notifications/' ),
					),
				)
		);

		$notifications = array(
			'title' => __( 'Notifications', APP_TD ),
				'fields' => array(
					array(
						'title' => __( 'Notify me when a payment is..', APP_TD ),
						'type' => 'checkbox',
						'name' => 'notifications',
						'values' => array(
							'completed' => __( 'Completed', APP_TD ),
							'reversed' => __( 'Reversed', APP_TD ),
							'denied' => __( 'Denied', APP_TD ),
							'failed' => __( 'Failed', APP_TD ),
							'voided' => __( 'Voided', APP_TD ),
						)
					),
				)
		);

		return apply_filters( 'appthemes_paypal_settings_form', array( 'general' => $general, 'pdt' => $pdt, 'ipn' => $ipn ) );
	}

	// PayPal processes recurring via IPN, therefore doesn't use this handler
	function process_recurring( $order, $options ){
		return;
	}

	function is_recurring(){
		$options = APP_Gateway_Registry::get_gateway_options( 'paypal' );
		return ! empty( $options['business_account'] );
	}

}
appthemes_register_gateway( 'APP_PayPal' );
