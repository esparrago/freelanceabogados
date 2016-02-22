<?php
/**
 * Views for handling/displaying project related forms.
 *
 * Form-Views handle and process form data as well as user actions.
 *
 */

add_action( 'hrb_handle_update_project', 'hrb_set_project_addons', 10 );


/**
 * Loads and populates the project edit form and handles all the related post data.
 */
class HRB_Project_Form_Edit extends APP_Checkout_Step {

	function __construct(){
		$this->setup( 'edit-project', array(
			'register_to' => array( 'chk-edit-project' ),
	    ));
	}

	/**
	 * Loads/displays the project Edit template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_project_edit_template_vars'
	 *
	 */
	function display( $order, $checkout ){
		global $hrb_options;

		$project = $this->existing_project_to_edit();

		$title = get_the_title( $project->ID );
		$link = html_link( get_permalink( $project ), $title );

        $template_vars = array(
			'title'             => sprintf( __( 'Edit %s', APP_TD ), $link ),
			'project'           => $project,
			'hrb_options'		=> $hrb_options,
			'action'            => 'edit_project',
			'form_action'       => appthemes_get_step_url(),
			'bt_step_text'      => __( 'Save Changes', APP_TD ),
			'categories_locked' => ( ! hrb_categories_editable( $project->ID ) ),
		);
        $template_vars = apply_filters( 'hrb_project_edit_template_vars', $template_vars );

		appthemes_load_template( 'form-project.php', $template_vars );
	}

	/**
	 * Validates the rules for editing a project.
	 *
	 * @uses apply_filters() Calls 'hrb_project_edit_validate'
	 *
	 */
	function validate( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'edit_project' != $_POST['action'] ) {
			return false;
        }

		$project_id = (int) get_query_var('project_edit');

        if ( ! current_user_can( 'edit_post', $project_id, 'edit' ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You cannot edit this project', APP_TD ) );
            return false;
		}

		wp_verify_nonce('hrb_post_project');

		APP_Notices::$notices = apply_filters( 'hrb_project_edit_validate', APP_Notices::$notices, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
		return true;
	}

	/**
	 * Handles Projects being edited and redirects the user to the updated Project page.
	 */
	function process( $order, $checkout ){

		if ( ! $this->validate( $order, $checkout ) ) {
			return;
		}

		$project = $this->update_project( $order, $checkout );
		if ( ! $project ) {
			// there are errors, return to current page
			return;
		}

		appthemes_add_notice( 'project-updated', __( 'Project was updated succesfully.', APP_TD ), 'success' );

		wp_redirect( get_permalink( $project->ID ) );
		exit;
	}

	/**
	 * Validates the posted fields on an Create/Edit project form.
	 *
	 * @uses apply_filters() Calls 'hrb_project_validate_fields'
	 *
	 */
	function validate_fields( $order, $checkout ){
        global $hrb_options;

		if ( empty( $_POST['post_title'] ) ) {
			appthemes_add_notice( 'no-title', __( 'No title was submitted.', APP_TD ) );
        }

		if ( empty( $_POST['budget_price'] ) || ! intval( $_POST['budget_price'] ) ) {
			appthemes_add_notice( 'invalid-price', __( 'Please insert a valid budget price.', APP_TD ) );
        }

		if ( ! hrb_charge_listings() && ( isset( $_POST['duration'] ) && $hrb_options->project_duration < (int) $_POST['duration'] ) ) {
			appthemes_add_notice( 'invalid-duration', sprintf( __( 'The project duration must be equal or inferior to %d.', APP_TD ), $hrb_options->project_duration ) );
        }

        if ( ! hrb_charge_listings() && ( ! isset( $_POST['duration'] ) && $hrb_options->project_duration > 0 ) ) {
			appthemes_add_notice( 'empty-duration', __( 'The project duration cannot be empty.', APP_TD ) );
        }

		$project_categories = _hrb_posted_terms( HRB_PROJECTS_CATEGORY );
		if ( ! $project_categories ) {
			appthemes_add_notice( 'wrong-cat', __( 'No category was submitted.', APP_TD ) );
        }

		if ( ! empty( $_REQUEST['ID'] ) ) {
			$project_id = (int) $_REQUEST['ID'];

			// make sure the categories were not changed after editing a purchased Project
			if ( ! hrb_categories_editable( $project_id ) ) {
				$terms = get_the_hrb_project_terms( $project_id, HRB_PROJECTS_CATEGORY );

				$diff_cats = hrb_parent_terms_diff( $terms, $project_categories );

				if ( ! empty( $diff_cats ) ) {
					appthemes_add_notice( 'hack-cat', __( 'Categories cannot be changed after purchase!', APP_TD ) );
				}
			}
		}

        APP_Notices::$notices = apply_filters( 'hrb_project_validate_fields', APP_Notices::$notices, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}
        return true;
	}

	/**
	 * Validates fields and processes the form data when creating/editing a project.
	 *
	 * @uses apply_filters() Calls 'hrb_handle_update_project'
	 *
	 */
	protected function update_project( $order, $checkout ){

        if ( ! $this->validate_fields( $order, $checkout ) ) {
            return false;
        }

		### update/insert post base data

		$args = wp_array_slice_assoc( $_POST, array( 'ID', 'post_title', 'post_content' ) );

		$args['post_type'] = HRB_PROJECTS_PTYPE;
		$args['post_name'] = hrb_make_unique( $args['post_title'], $args['ID'], $args['post_type'] );

		if ( empty( $_POST['ID'] ) || ! (bool) get_post( (int) $_POST['ID'] ) ) {

			$args['post_author'] = wp_get_current_user()->ID;
			$args['post_status'] = 'draft';

			$project_id = wp_insert_post( $args );

			$args['guid'] = get_permalink( $project_id );

		} else {
			$project_id = wp_update_post( $args );
		}

		hrb_update_post_guid( $project_id );


		### handle taxonomies

        $taxonomies = array( HRB_PROJECTS_CATEGORY, HRB_PROJECTS_SKILLS );

		$project_categories = array();
		foreach( $taxonomies as $taxonomy ) {

			$terms = _hrb_posted_terms( $taxonomy );

			if ( HRB_PROJECTS_CATEGORY == $taxonomy ) {
				$project_categories = $terms;
            }

			hrb_set_post_terms( $project_id, $terms, $taxonomy );
		}

		if ( ! empty( $_REQUEST[ 'hidden-'.HRB_PROJECTS_TAG ] ) ) {
			$project_tags = sanitize_text_field( $_REQUEST[ 'hidden-'.HRB_PROJECTS_TAG ] );
			wp_set_object_terms( $project_id, explode( ',', $project_tags ), HRB_PROJECTS_TAG );
		}


		### handle meta

		$fields = array();

		// retrieve the form fields that need to be handled in the project form to store as meta
		$project_meta = hrb_get_project_form_meta_fields();

		foreach ( $project_meta as $form_field => $meta_field ) {
			$field_value = sanitize_text_field( _hrb_posted_field_value( $form_field ) );

			update_post_meta( $project_id, $meta_field, $field_value );

			$fields[ $meta_field ] = $field_value;
		}

		### handle and set geolocation main meta

		if ( ! empty( $fields['_hrb_location'] ) ) {

			// stores the main location atts on a master meta key
			$master_atts = hrb_get_geocomplete_master_attributes();

			$fields['_hrb_location_master'] = $fields['_hrb_location'];

			foreach( $master_atts as $att ) {
				if ( ! empty( $fields[ "_hrb_location_{$att}" ] ) ) {
					$fields['_hrb_location_master'] .= '|' . $fields[ "_hrb_location_{$att}" ];
				}
			}

			update_post_meta( $project_id, '_hrb_location_master', $fields['_hrb_location_master'] );

		}

		### handle custom forms

		hrb_update_form_builder( $project_categories, $project_id, HRB_PROJECTS_CATEGORY );


		### handle uploaded files

		appthemes_handle_media_upload( $project_id );


		return apply_filters( 'hrb_handle_update_project', get_post( $project_id) );
	}

	function existing_project_to_edit() {
		global $wp_query;

		return hrb_get_project( $wp_query->get( 'project_edit' ) );
	}

}

/**
 * Loads the related form and handles all the data for the creation of new projects.
 * Descends from the related Edit View to allow editing the fields when going back from previous steps.
 */
class HRB_Project_Form_Create extends HRB_Project_Form_Edit {

	function __construct(){
		$this->setup( 'create-project', array(
			'register_to' => array( 'chk-create-project' ),
		) );
	}

	/**
	 * Loads/displays the project Create template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_project_create_template_vars'
	 *
	 */
	function display( $order, $checkout ){
		global $hrb_options;

		$project = $checkout->get_data('project');

		// if the project exists in the checkout data, it's because user is resuming a draft project or navigating the form
		if ( ! empty( $project->ID ) ) {
			$project = hrb_get_project( $project->ID );
		} else {
			$project = $this->default_project_to_edit();
		}

        $template_vars = array(
			'title'             => __( 'Add Project', APP_TD ),
			'hrb_options'    => $hrb_options,
			'project'           => $project,
			'categories_locked' => ( ! hrb_categories_editable( $project->ID ) ),
			'action'            => 'new_project',
			'form_action'       => appthemes_get_step_url(),
			'bt_step_text'      => __( 'Continue', APP_TD ),
		);
        $template_vars = apply_filters( 'hrb_project_create_template_vars', $template_vars );

		appthemes_load_template( 'form-project.php', $template_vars );
	}

	/**
	 * Validates the rules for creating a project.
	 *
	 * @uses apply_filters() Calls 'hrb_project_create_validate'
	 *
	 */
	function validate( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'new_project' != $_POST['action'] ) {
			return false;
		}

		if ( ! current_user_can('edit_projects') ) {
			appthemes_add_notice( 'cannot-create', __( 'You are not allowed to post projects.', APP_TD ) );
			return false;
		}

		wp_verify_nonce('hrb_post_project');

		APP_Notices::$notices = apply_filters( 'hrb_project_create_validate', APP_Notices::$notices, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	/**
	 * Handles new Projects being posted and redirects the user to the next step.
	 */
	function process( $order, $checkout ){

		if ( ! $this->validate( $order, $checkout ) ) {
			return;
		}

		$project = $this->update_project( $order, $checkout );
		if ( ! $project ) {
			// there are errors, return to current page
			return;
		}

		$checkout->add_data( 'project', $project );

		$this->finish_step();
	}

	/**
	 * Retrieve a new Project post object.
	 */
	function default_project_to_edit() {
        global $hrb_options;

		require ABSPATH . '/wp-admin/includes/post.php';

		$project = get_default_post_to_edit( HRB_PROJECTS_PTYPE );

        $project->_hrb_duration = $hrb_options->project_duration;
		$project->categories = '';
		$project->skills = '';

		foreach ( array( 'post_title', 'post_content' ) as $field ) {
			$project->$field = _hrb_posted_field_value( $field );
		}
		return $project;
	}

}

/**
 * Handle project relistings.
 */
class HRB_Project_Form_Relist extends HRB_Project_Form_Edit {

	function __construct(){
		$this->setup( 'renew-project', array(
			'register_to' => array( 'chk-renew-project' ),
		));
	}

	/**
	 * Loads/displays the project Relist template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_project_relist_template_vars'
	 *
	 */
	function display( $order, $checkout ) {
		global $hrb_options;

		the_post();

		$project = $this->existing_project_to_edit();

		$title = get_the_title( $project->ID );
		$link = html_link( get_permalink( $project ), $title );

        $template_vars = array(
			'title'             => sprintf( __( 'Relist %s', APP_TD ), $link ),
			'project'           => $project,
			'hrb_options'    => $hrb_options,
			'action'            => 'renew_project',
			'form_action'       => appthemes_get_step_url(),
			'bt_step_text'      => __( 'Continue', APP_TD ),
			'categories_locked' => false,
		);
        $template_vars = apply_filters( 'hrb_project_relist_template_vars', $template_vars );

		appthemes_load_template( 'form-project.php', $template_vars );
	}

	/**
	 * Validates the rules for relisting a project.
	 *
	 * @uses apply_filters() Calls 'hrb_validate_project_relist'
	 *
	 */
	function validate( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'renew_project' != $_POST['action'] ) {
			return false;
        }

        $project_id = (int) get_query_var('project_relist');

		if ( ! current_user_can( 'relist_post', $project_id ) ) {
			appthemes_add_notice( 'no-permissions', __( 'You don\'t have permissions to relist this project.', APP_TD ) );
			wp_redirect( get_permalink( $project_id ) );
			exit;
		}

		wp_verify_nonce('hrb_post_project');

		APP_Notices::$notices = apply_filters( 'hrb_validate_project_relist', APP_Notices::$notices, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	function process( $order, $checkout ){

		if ( ! $this->validate( $order, $checkout ) ) {
			return;
        }

		$project = $this->update_project( $order, $checkout );
		if ( ! $project ) {
			// there are errors, return to current page
			return;
		}

		// set a flag in the project meta so we know it was expired and is being relisted
		update_post_meta( $project->ID, '_hrb_relisted', 1 );

		$checkout->add_data( 'project', $project );

		$this->finish_step();
	}

}

/**
  * Loads the related form and handles all the related data.
  */
class HRB_Project_Form_Preview extends APP_Checkout_Step {

	function __construct(){
		$this->setup( 'preview', array(
			'register_to' => array(
				'chk-create-project' => array( 'after' => 'create-project' ),
				'chk-renew-project' => array( 'after' => 'renew-project' ),
			),
		));
	}

	/**
	 * Loads/displays the project Preview template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_project_preview_template_vars'
	 *
	 */
	function display( $order, $checkout ){
		global $hrb_options;

		$project = hrb_get_project( $checkout->get_data('project') );

		$project->meta = get_post_custom( $project->ID );

		if ( ! hrb_charge_listings() ) {
			$bt_step_text = __( 'Confirm & Submit', APP_TD );
		} else {
			$bt_step_text = __( 'Continue', APP_TD );
		}

		$preview_fields = $this->get_preview_fields( $project );

        $template_vars = array(
			'title'             => __( 'Preview', APP_TD ),
			'project'           => $project,
			'preview_fields'    => $preview_fields,
			'hrb_options'       => $hrb_options,
            'action'            => 'preview_project',
			'form_action'       => appthemes_get_step_url(),
			'bt_step_text'      => $bt_step_text,
			'bt_prev_step_text' => __( '&#8592; Edit', APP_TD ),
			'app_order'			=> $order,
		);
        $template_vars = apply_filters( 'hrb_project_preview_template_vars', $template_vars );

		appthemes_load_template( 'form-project-preview.php', $template_vars );

	}

	/**
	 * Validates the rules for previewing a project.
	 *
	 * @uses apply_filters() Calls 'hrb_project_preview_validate'
	 *
	 */
	function validate( $order, $checkout ) {

		if ( ! isset( $_POST['action'] ) || 'preview_project' != $_POST['action'] ) {
			return false;
		}

		wp_verify_nonce('hrb_post_project');

		APP_Notices::$notices = apply_filters( 'hrb_project_preview_validate', APP_Notices::$notices, $order, $checkout );
		if ( APP_Notices::$notices->get_error_code() ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle new projects being previewed and redirects the user to the next step.
	 *
	 * @uses do_action() Calls 'hrb_new_project'
	 *
	 */
	function process( $order, $checkout ){

		// if errors occured or the order was already processed skip processing it
		if ( ! $this->validate( $order, $checkout ) ||  $checkout->get_data('processed') ) {
			return;
		}

		if ( ! hrb_charge_listings() ) {
			$project = $checkout->get_data('project');

			// @todo test new/relisted projects
			$checkout->add_data( 'processed', true );

			do_action( 'hrb_new_project', $project->ID );
		}

		$this->finish_step();
	}

	/**
	 * Retrieves the fields that should be visible on the preview page.
	 *
	 * @uses apply_filters() Calls 'hrb_preview_custom_fields'
	 * @uses apply_filters() Calls 'hrb_preview_fields'
	 *
	 */
	function get_preview_fields( $project ) {

		$cats_link = get_term_link( $project->categories, HRB_PROJECTS_CATEGORY );
		$cats = html_link( $cats_link, $project->categories_name );

		if ( $project->subcategories ) {
			$subcats_link = get_term_link( $project->subcategories, HRB_PROJECTS_CATEGORY );
			$subcats = ' > ' . html_link( $subcats_link, $project->subcategories_name );
		} else {
			$subcats = '';
		}

		$skills = get_the_hrb_terms_list( $project->ID, HRB_PROJECTS_SKILLS, '<span class="label">', '</span>' );
		$tags = get_the_hrb_terms_list( $project->ID, HRB_PROJECTS_TAG, '<span class="label">', '</span>' );

		$files = appthemes_output_attachments( $project->_app_media, null, $output = false );

		$fields = array(
			__( 'Title', APP_TD ) => $project->post_title,
			__( 'Description', APP_TD ) => apply_filters('the_content', $project->post_content ),
			__( 'Category', APP_TD ) => sprintf( '%s%s', $cats, $subcats ),
			__( 'Skills', APP_TD ) => join( ' ', $skills ),
			__( 'Tags', APP_TD ) => join( ' ', $tags ),
		);

		if ( ! hrb_charge_listings() ) {
			$fields[ __( 'Duration', APP_TD ) ] = sprintf( _n( '%d day', '%d days', $project->_hrb_duration, APP_TD ), $project->_hrb_duration );
		}

		$merged_cats = array_merge( (array) $project->categories, (array) $project->subcategories );

		$custom_form_fields = apply_filters( 'hrb_preview_custom_fields', array(), $project->ID, $merged_cats, HRB_PROJECTS_CATEGORY );

		if ( 'fixed' == $project->_hrb_budget_type ) {
			$budget_text =  __( 'Fixed Price', APP_TD );
		} else {
			$budget_text = sprintf( __( 'Per Hour / %s', APP_TD ), sprintf( _n( '1 hour', '%d hours', $project->_hrb_hourly_min_hours ), $project->_hrb_hourly_min_hours ) );
		}

		$other_fields = array(
			__( 'Budget', APP_TD ) => sprintf( '%s (%s)', appthemes_get_price( $project->_hrb_budget_price, $project->_hrb_budget_currency ), $budget_text ),
			__( 'Location', APP_TD ) => ( 'remote' != $project->_hrb_location_type  ? $project->_hrb_location : __( 'Remote', APP_TD ) ),
			__( 'Files', APP_TD ) => $files,
		);

		$fields = array_merge( $fields, $custom_form_fields, $other_fields );

		foreach( $fields as $key => $value ) {

			if ( empty( $value ) ) {
				$fields[ $key ] = '-';
			}

		}

		return apply_filters( 'hrb_preview_fields', $fields );
	}

}

/**
  * View for the final project submit step when charging is disabled.
  */
class HRB_Project_Form_Submit_Free extends APP_Checkout_Step {

	function __construct(){
		global $hrb_options;

		if ( hrb_charge_listings() )
			return;

		$this->setup( 'thank_you', array(
			'register_to' => array(
				'chk-create-project' => array( 'after' => 'preview' ),
			),
		));
	}

	/**
	 * Loads/displays the project End Step template form with all the required vars.
	 *
	 * @uses apply_filters() Calls 'hrb_project_submit_free_template_vars'
	 *
	 */
	function display( $order, $checkout ) {
		global $hrb_options;

		$project = $checkout->get_data( 'project' );
		$post_type_obj = get_post_type_object( get_post_type( $project ) );

		$can_publish = current_user_can('publish_posts');

		$template_vars = array(
			'title'			=> __( 'Thank You!', APP_TD ),
			'project'		=> $project,
			'hrb_options'	=> $hrb_options,
			'form_action'	=> appthemes_get_step_url(),
			'bt_url'		=> $can_publish ? get_permalink( $project->ID ) : hrb_get_dashboard_url_for('projects'),
			'bt_step_text'  => sprintf( __( 'Continue to %s', APP_TD ), $can_publish ? $post_type_obj->labels->singular_name : __( 'Dashboard', APP_TD ) ),
			'app_order'		=> $order,
		);
		$template_vars = apply_filters( 'hrb_project_submit_free_template_vars', $template_vars );

		appthemes_load_template( 'form-project-submit-free.php', $template_vars );
	}

	function process( $order, $checkout ){

        $project = $checkout->get_data('project');
        if ( $project ) {
            hrb_activate_free_project( $project );
        }

	}

}
