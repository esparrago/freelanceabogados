<?php

add_action( 'admin_init', '_hrb_project_metaboxes' );
add_action( 'save_post', '_hrb_set_project_meta_defaults', 10, 2 );

/**
 * Add addons defaults to post meta on save
 */
function _hrb_set_project_meta_defaults( $post_id, $post ) {
	if ( HRB_PROJECTS_PTYPE !== $post->post_type ) {
		return;
	}

	if ( ! wp_is_post_revision( $post_id ) ) {
		hrb_set_project_addons( $post_id );
	}
}

function _hrb_project_metaboxes() {
	$remove_boxes = array( 'postexcerpt', 'revisionsdiv', 'postcustom', 'authordiv', HRB_PROJECTS_PTYPE . '_budgetdiv' );

	foreach ( $remove_boxes as $id ) {
		remove_meta_box( $id, HRB_PROJECTS_PTYPE, 'normal' );
	}
}

// Meta Boxes

class HRB_Project_Media extends APP_Media_Manager_Metabox {

	public function __construct( $id, $title, $post_type, $context = 'normal', $priority = 'default' ) {
		parent::__construct( $id, $title, $post_type, $context, $priority );
	}

	function display( $post ) {
		hrb_media_manager( $post->ID, array( 'id' => self::$id ) );
	}
}

class HRB_Project_Budget_Meta extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'project-budget', __( 'Budget', APP_TD ), HRB_PROJECTS_PTYPE, 'normal', 'high' );
	}

	public function form_fields(){

		return array(
			array(
				'title' => __( 'Type', APP_TD ),
				'type' => 'radio',
				'name' => '_hrb_budget_type',
				'choices' => array(
					'fixed' => __( 'Fixed Price', APP_TD ),
					'hourly' => __( 'Per Hour', APP_TD ),
				),
			),
			array(
				'title' => __( 'Currency', APP_TD ),
				'type' => 'select',
				'name' => '_hrb_budget_currency',
				'choices' =>  APP_Currencies::get_currency_string_array(),
			),
			array(
				'title' => __( 'Budget Price', APP_TD ),
				'type' => 'text',
				'name' => '_hrb_budget_price',
				'extra' => array(
					'size' => '3',
					'class' => 'required',
				),
				'desc' => html( 'span', array( 'class' => 'currency' ) ),
			),
			array(
				'title' => __( 'Mininum Hours Needed', APP_TD ),
				'type' => 'text',
				'name' => '_hrb_hourly_min_hours',
				'extra' => array(
					'size' => '3',
					'class' => 'budget-hourly',
					'style' => 'display: none;',
				),
				'row_extra' => array(
					'class' => 'budget-hourly',
					'style' => 'display: none;',
				),
			),
		);

	}

	public function validate_post_data( $data, $post_id = 0 ){

		$errors = new WP_Error();

		if ( empty( $data['_hrb_budget_price'] ) ){
			$errors->add( '_hrb_budget_price', __( 'The budget price cannot be empty.', APP_TD ) );
		}

		if ( ! is_numeric( $data['_hrb_budget_price'] ) ){
			$errors->add( '_hrb_budget_price', __( 'Invalid budget price.', APP_TD ) );
		}

        return $errors;
	}

	public function before_save( $data, $post_id ) {

		if ( empty( $data['_hrb_budget_price'] ) ) {
			$data['_hrb_budget_price'] = 0;
		}

		return $data;
	}

	function budget_list() {

		$budget_list = array();

		$budgets = get_terms( HRB_PROJECTS_CATEGORY, array( 'hide_empty' => false ) );
		foreach( $budgets as $budget ) {
			$budget_list[ $budget->slug ] = $budget->name;
		}

		$budget_list['custom'] = __( 'Custom', APP_TD );

		return $budget_list;
	}

}

class HRB_Project_Location_Meta extends APP_Meta_Box {

	public function __construct(){
		parent::__construct( 'project-location', __( 'Location Preferences', APP_TD ), HRB_PROJECTS_PTYPE, 'normal', 'high' );
	}

	public function admin_enqueue_scripts() {
		hrb_maybe_enqueue_geo();
	}

	public function before_display( $form_data, $post ) {
		// placeholder for the project location
		echo html( 'div', array( 'class' => 'project-location' ) );
		echo html( 'p', array(), __( 'Enter the preferred location for Freelancers biding on this Project.', APP_TD ) );

		return $form_data;
	}


	public function form_fields(){

		return array(
			array(
				'title' => __( 'Location Preference', APP_TD ),
				'type' => 'select',
				'name' => '_hrb_location_type',
				'choices' => array(
					'local' => __( 'Local', APP_TD ),
					'remote' => __( 'Remote', APP_TD ),
				),
				'extra' => array (
					'id' => 'project-address-pref',
				)
			),
			array(
				'title' => __( 'Location', APP_TD ),
				'type' => 'text',
				'name' => '_hrb_location',
				'extra' => array (
					'id' => 'project-address',
				)
			),
		);

	}

	protected function validate_post_data( $post_data, $post_id ) {

		$hidden_keys = array();

		// others location fields / meta keys
		foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
			$meta_key = "_hrb_location_{$location_att}";
			$hidden_keys[] = $meta_key;
		}

		$post_data = array_merge( $post_data, $hidden_keys );

		parent::validate_post_data( $post_data, $post_id ) ;
	}

	public function before_save( $to_update, $post_id ) {

		// process and update the user skills meta
		if ( ! empty( $_POST['_hrb_location'] ) ) {

			$keys = array();

			// others location fields / meta keys
			foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
				$meta_key = "_hrb_location_{$location_att}";
				$keys[] = $meta_key;
			}

			$values = wp_array_slice_assoc( $_POST, $keys );
			$values = array_map( 'sanitize_text_field', $values );

			foreach( $keys as $key ) {
				$to_update[ $key ] = $values[ $key ];
			}

			// stores the main location atts on a master meta key
			$master_atts = hrb_get_geocomplete_master_attributes();

			$to_update['_hrb_location_master'] = $to_update['_hrb_location'];

			foreach( $master_atts as $att ) {
				if ( ! empty( $to_update[ "_hrb_location_{$att}" ] ) ) {
					$to_update['_hrb_location_master'] .= '|' . $to_update[ "_hrb_location_{$att}" ];
				}
			}

		}
		return $to_update;
	}

	// output additional HTML markup
	public function after_form( $post ) {

		$hidden_fields = '';

		foreach ( hrb_get_geocomplete_attributes() as $location_att ) {
			$meta_key = "_hrb_location_{$location_att}";
			$hidden_fields .= html( 'input', array( 'type' => 'hidden', 'name' => esc_attr( $meta_key ), 'data-geo' => esc_attr( $location_att ), 'value' => esc_attr( get_post_meta( $post->ID, $meta_key, true ) ) ) );
		}

		echo html( 'div', array( 'class' => 'custom-location' ), $hidden_fields );
	}

}

class HRB_Expire_Handler extends APP_Meta_Box {

	public function js_define_expire_handler(){
		?>
		<script type="text/javascript">

			if ( typeof(callback) !== "function" ) {

				function createExpireHandler( enableBox, durationBox, startDateBox, startDateU, startDateDisplayBox, textBox, $ ){

					$(enableBox).change(function(){
						if( $(this).prop('checked') && $(startDateBox).val() == "" ){
							$(startDateDisplayBox).val( dateToString( new Date ) );
							$(startDateBox).val( dateToStdString( new Date ) );
							$(durationBox).val( '0' );
						} else {
							$(startDateBox).val( '' );
							$(startDateDisplayBox).val( '' );
							$(durationBox).val( '' );
							$(textBox).val( '' );
						}
					});

					var checker = function(){
						var string = "";
						if( enableBox === undefined ){
							string = get_expiration_time();
						}
						else if( $(enableBox).attr('checked') !== undefined ){
							string = get_expiration_time();
						}
						update(string);
					}

					var get_expiration_time = function(){

						var startDate = $(startDateU).val() * 1000;
						if( startDate == "" ){
							startDate = new Date().getTime();
						}

						var duration = $(durationBox).val();
						if ( duration == "" ){
							return "";
						}

						return getDateString( parseInt( duration, 10 ), startDate );
					}

					var getDateString = function ( duration, start_date){
						if( isNaN(duration) )
							return "";

						if( duration === 0 )
							return "<?php _e( 'Never', APP_TD ); ?>";

						var _duration = parseInt( duration ) * 24 * 60 * 60 * 1000;
						var _expire_time = parseInt( start_date ) + parseInt( _duration );
						var expireTime = new Date( _expire_time );

						return dateToString( expireTime );
					}

					var update = function( string ){
						if( string  != $(textBox).val() ){
							$(textBox).val( string );
						}
					}

					var dateToStdString = function( date ){
						return ( date.getMonth() + 1 )+ "/" + date.getDate() + "/" + date.getFullYear();
					}

					var dateToString = function( date ){
						<?php
							$date_format = get_option('date_format', 'm/d/Y');

							switch ( $date_format ) {
								case "d/m/Y":
								case "j/n/Y":
									$js_date_format = 'date.getDate() + "/" + ( date.getMonth() + 1 ) + "/" + date.getFullYear()';
								break;
								case "Y/m/d":
								case "Y/n/j":
									$js_date_format = 'date.getFullYear() + "/" + ( date.getMonth() + 1 ) + "/" + date.getDate()';
								break;
								case "m/d/Y":
								case "n/j/Y":
								default:
									$js_date_format = '( date.getMonth() + 1 )+ "/" + date.getDate() + "/" + date.getFullYear()';
								break;
							}
						?>
						return <?php echo $js_date_format; ?>;
					}

					setInterval( checker, 10 );
				}

			}
		</script>
		<?php

	}

}

class HRB_Project_Timeline_Meta extends HRB_Expire_Handler {

	public function __construct(){
		parent::__construct( 'project-listing-duration', __( 'Timeline', APP_TD ), array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'context' => 'normal',
			'priority' => 'high'
		) );
	}

	public function admin_enqueue_scripts(){
		wp_enqueue_style( 'jquery-ui-datepicker', get_template_directory_uri() . '/styles/jqueryui/jquery-ui.css' );
		wp_enqueue_script('jquery-ui-datepicker');

		$this->js_define_expire_handler();
	}

	public function before_display( $form_data, $post ){

		$form_data['_blank_'.HRB_PROJECTS_PTYPE.'_start_date'] = $post->post_date;
		$form_data['_blank_js_'.HRB_PROJECTS_PTYPE.'_start_date'] = mysql2date( 'U', $post->post_date);

		return $form_data;

	}

	public function before_form( $post ){
		$mk_duration = '_hrb_duration';
		?>
		<script type="text/javascript">
			jQuery(function($){
				createExpireHandler( undefined, $("#<?php echo $mk_duration; ?>"), $("#_blank_<?php echo HRB_PROJECTS_PTYPE; ?>_start_date"), $("#_blank_js_<?php echo HRB_PROJECTS_PTYPE; ?>_start_date"), $(''), $("#_blank_expire_<?php echo HRB_PROJECTS_PTYPE; ?>"), $ );
				$("#_blank_<?php echo HRB_PROJECTS_PTYPE; ?>_start_date").parent().parent().parent().hide();
				$("#_blank_js_<?php echo HRB_PROJECTS_PTYPE; ?>_start_date").parent().parent().parent().hide();
			});
		</script>
		<p><?php _e( 'These settings allow you to override the defaults that have been applied to the listings based on the plan the owner chose. They will apply until the listing expires.', APP_TD ); ?></p>
		<?php

	}

	public function form_fields(){

		$output = array(
			 array(
				'title' => __( 'Post this Project for', APP_TD ),
				'type' => 'text',
				'name' => '_hrb_duration',
				'desc' => __( 'days', APP_TD ),
				'extra' => array(
					'size' => '3'
				),
			),
			array(
				'title' => __( 'Project Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_'.HRB_PROJECTS_PTYPE.'_start_date',
			),
			array(
				'title' => __( 'Project Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_js_'.HRB_PROJECTS_PTYPE.'_start_date',
			),
			array(
				'title' => __( 'Expires on', APP_TD ),
				'type' => 'text',
				'name' => '_blank',
				'extra' => array(
					'disabled' => 'disabled',
					'style' => 'background-color: #EEEEEF;',
					'id' => '_blank_expire_'.HRB_PROJECTS_PTYPE
				)
			)
		);

		return $output;

	}

	function before_save( $data, $post_id ){
		unset( $data['_blank_'.HRB_PROJECTS_PTYPE.'_start_date'] );
		unset( $data['_blank_js_'.HRB_PROJECTS_PTYPE.'_start_date'] );
		unset( $data['_blank'] );

		return $data;
	}
}

class HRB_Project_Promotional_Meta extends HRB_Expire_Handler {

	public function __construct(){
		parent::__construct( 'project-promotional', __( 'Promotion', APP_TD ), array(
			'post_type' => HRB_PROJECTS_PTYPE,
			'context' => 'normal',
			'priority' => 'high'
		) );
	}

	public function admin_enqueue_scripts(){
		if ( is_admin() ){
			wp_enqueue_style( 'jquery-ui-datepicker', get_template_directory_uri() . '/styles/jqueryui/jquery-ui.css' );
			wp_enqueue_script('jquery-ui-datepicker');

			$this->js_define_expire_handler();
		}
	}

	public function before_display( $form_data, $post ){
		$date_format = get_option('date_format');
		$date_format = str_ireplace('m', 'n', $date_format);
		$date_format = str_ireplace('d', 'j', $date_format);

		foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) {

			if( !empty( $form_data[$addon.'_start_date'] ) ) {
				$form_data['_blank_'.$addon.'_start_date'] = mysql2date( $date_format, $form_data[$addon.'_start_date']);
				$form_data['_blank_js_'.$addon.'_start_date'] = mysql2date( 'U', $form_data[$addon.'_start_date']);
				$form_data[$addon.'_start_date'] = mysql2date( 'm/d/Y', $form_data[$addon.'_start_date']);
			}

		}

		return $form_data;
	}

	public function before_form( $post ){
		$date_format = get_option('date_format', 'm/d/Y');

		switch ( $date_format ) {
			case "d/m/Y":
			case "j/n/Y":
				$ui_display_format = 'dd/mm/yy';
			break;
			case "Y/m/d":
			case "Y/n/j":
				$ui_display_format = 'yy/mm/dd';
			break;
			case "m/d/Y":
			case "n/j/Y":
			default:
				$ui_display_format = 'mm/dd/yy';
			break;
		}

		?>
		<script type="text/javascript">
			jQuery(function($){

				<?php foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ) { ?>

					createExpireHandler( $("#<?php echo $addon; ?>"), $("#<?php echo $addon; ?>_duration"), $("#<?php echo $addon; ?>_start_date"), $("#_blank_js_<?php echo $addon; ?>_start_date"), $("#_blank_<?php echo $addon; ?>_start_date"), $("#_blank_expire_<?php echo $addon; ?>"), $ );
					$( "#_blank_<?php echo $addon; ?>_start_date" ).datepicker({
						dateFormat: "<?php echo $ui_display_format; ?>",
						altField: "#featured-home_start_date",
						altFormat: "mm/dd/yy"
					});
					$("#<?php echo $addon; ?>_start_date").parent().parent().parent().hide();
					$("#_blank_js_<?php echo $addon; ?>_start_date").parent().parent().parent().hide();

				<?php } ?>

			});
		</script>
		<p><?php _e( 'These settings allow you to override the defaults that have been applied to the listings based on the plan the owner chose. They will apply until the listing expires.', APP_TD ); ?></p>
		<?php

	}

	public function form_fields(){

		$output = array();

		foreach( hrb_get_addons( HRB_PROJECTS_PTYPE ) as $addon ){

			$enabled = array(
				'title' => APP_Item_Registry::get_title( $addon ),
				'type' => 'checkbox',
				'name' => $addon,
				'desc' => __( 'Yes', APP_TD ),
				'extra' => array(
					'id' => $addon,
				),
				'row_extra' => array(
					'class' => 'promotional-title',
				),
			);

			$duration = array(
				'title' => __( 'Duration', APP_TD ),
				'desc' => __( 'days (0 = Infinite)', APP_TD ),
				'type' => 'text',
				'name' => $addon . '_duration',
				'extra' => array(
					'size' => '3'
				),
			);

			$start = array(
				'title' => __( 'Start Date', APP_TD ),
				'type' => 'text',
				'name' => $addon . '_start_date',
			);

			$start_display = array(
				'title' => __( 'Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_'.$addon . '_start_date',
			);

			$start_js = array(
				'title' => __( 'Start Date', APP_TD ),
				'type' => 'text',
				'name' => '_blank_js_'.$addon . '_start_date',
			);


			$expires = array(
				'title' => __( 'Expires on', APP_TD ),
				'type' => 'text',
				'name' => '_blank',
				'extra' => array(
					'disabled' => 'disabled',
					'style' => 'background-color: #EEEEEF;',
					'id' => '_blank_expire_' . $addon,
				)
			);

			$output = array_merge( $output, array( $enabled, $duration, $start, $start_display, $start_js, $expires ));

		}

		return $output;

	}

	function before_save( $data, $post_id ){
		global $hrb_options;

		unset( $data['_blank'] );

		foreach( hrb_get_addons(HRB_PROJECTS_PTYPE) as $addon ){

			unset( $data['_blank_'.$addon.'_start_date'] );
			unset( $data['_blank_js_'.$addon.'_start_date'] );

			if ( $data[$addon.'_start_date'] ){
				$data[$addon.'_start_date'] = date('Y-m-d H:i:s', strtotime( $data[$addon.'_start_date'] ) );
			}

			if ( $data[$addon] ){

				if ( $data[$addon.'_duration'] !== '0' && empty( $data[$addon.'_duration'] ) ){
					$data[$addon.'_duration'] = $hrb_options->addons[$addon]['duration'];
				}

				if ( empty( $data[$addon.'_start_date'] ) ){
					$data[$addon.'_start_date'] = current_time( 'mysql' );
				}

			}
		}

		return $data;
	}

}

class HRB_Project_Publish_Moderation extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'project-publish-moderation', __( 'Moderation Queue', APP_TD ), HRB_PROJECTS_PTYPE, 'side', 'high' );
	}

	function condition() {
		return ( isset( $_GET['post'] ) && get_post_status( $_GET['post'] ) == 'pending' );
	}

	function display( $post ) {

		echo html( 'p', array(), __( 'You must approve this listing before it can be published.', APP_TD ) );

		echo html( 'input', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'value' => __( 'Accept', APP_TD ),
			'name' => 'publish',
			'style' => 'padding-left: 30px; padding-right: 30px; margin-right: 20px; margin-left: 15px;',
		));

		echo html( 'a', array(
			'class' => 'button',
			'style' => 'padding-left: 30px; padding-right: 30px;',
			'href' => get_delete_post_link($post->ID),
		), __( 'Reject', APP_TD ) );

		echo html( 'p', array(
				'class' => 'howto'
			), __( 'Rejecting a listing sends it to the trash.', APP_TD ) );

	}
}


class HRB_Project_Author_Meta extends APP_Meta_Box {

	public function __construct() {
		parent::__construct( 'listingauthordiv', __( 'Author', APP_TD ), HRB_PROJECTS_PTYPE, 'side', 'low' );
	}

	public function display( $post ) {
		global $user_ID;
		?>
		<label class="screen-reader-text" for="post_author_override"><?php _e( 'Author', APP_TD ); ?></label>
		<?php
		wp_dropdown_users( array(
			/* 'who' => 'authors', */
			'name' => 'post_author_override',
			'selected' => empty($post->ID) ? $user_ID : $post->post_author,
			'include_selected' => true
		) );
	}
}
