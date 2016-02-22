<?php
/**
 * Disputes admin settings.
 *
 * @package Components\Disputes\Admin
 */

if ( is_admin() ) {
	add_action( 'init', '_appthemes_register_disputes_settings', 30 );
}

/**
 * Registers the payments escrow settings page.
 */
function _appthemes_register_disputes_settings() {
	new APP_Dispute_Settings_Admin( appthemes_disputes_get_args('options') );
}

/**
 * Defines the escrow settings administration panel.
 */
class APP_Dispute_Settings_Admin extends APP_Conditional_Tabs_Page {

	public function __construct( $options ) {

		$this->options = $options;

		parent::__construct( $options );
	}

	/**
	 * Sets up the page
	 * @return void
	 */
	public function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_slug'			 => 'app-escrow-settings',
			'parent'			 => 'app-payments-settings',
			'conditional_parent' => 'app-payments',
			'conditional_page'	 => 'app-payments-settings',
		);

	}

	public function conditional_create_page() {
		return false;
	}

	public function init_tabs() {

		$gateways = $this->tab_sections['escrow']['gateways'];

		$labels = appthemes_disputes_get_args('labels');

		// temporarily unset the gateways to re-order the settings
		unset( $this->tab_sections['escrow']['gateways'] );

		$this->tab_sections['escrow']['disputes'] = array(
			'title' => __( 'Disputes', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Enable Disputes', APP_TD ),
					'desc' => __( 'Yes', APP_TD ),
					'tip' => sprintf( __( 'Enabling this option allows %1$s to open disputes on projects that they\'ve completed but %2$s have canceled, or closed as incomplete.', APP_TD ), $labels['disputers'], $labels['disputees'] ) .
							html( 'p', '&nbsp;' ) .
							__( 'You\'ll be able to arbitrate disputes from the \'Disputes\' page that will be available on the admin left sidebar. Refund/Payments are put in stand-by until your final decision.', APP_TD ),
					'type' => 'checkbox',
					'name' => array( 'disputes', 'enabled' )
				),
				array(
					'title' => __( 'Availability', APP_TD ),
					'desc' => sprintf( __( 'days. Allow opening a dispute within this number of days after a project is closed/canceled by the %1$s.', APP_TD ), $labels['disputee'] ),
					'tip' => sprintf( __( 'The number of days granted to %1$s to be able to open disputes. If a dispute is not opened within this period, the %2$s will be automatically refunded.', APP_TD ), $labels['disputers'], $labels['disputee'] ),
					'type' => 'select',
					'name' => array( 'disputes', 'max_days' ),
					'choices' => range( 1, 10 ),
				),
			),
		);

		$this->tab_sections['escrow']['gateways'] = $gateways;
	}

}
