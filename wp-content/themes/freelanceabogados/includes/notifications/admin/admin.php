<?php
/**
 * Registers Notifications Settings Administration Panel
 *
 * @package Components\Notifications\Admin
 */
class APP_Notifications_Admin {

	protected $options = '';

	public function __construct( $options ) {

		$this->options = $options;

		add_action( 'init', array( $this, 'register_settings' ), 12 );
	}

	/**
	 * Registers the settings page
	 * @return void
	 */
	function register_settings(){
		new APP_Notifications_Settings_Admin( $this->options );
	}


} // end class

