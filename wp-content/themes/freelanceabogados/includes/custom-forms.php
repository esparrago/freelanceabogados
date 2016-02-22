<?php
/**
 * Functions related with custom forms.
 */

add_action( 'init', '_hrb_register_custom_forms_post_type', 11 );

add_action( 'wp_ajax_app-render-project-form', '_hrb_forms_ajax_render_project_form' );

add_action( 'hrb_project_custom_fields', 'hrb_output_custom_fields_placeholder' );
add_filter( 'hrb_preview_custom_fields', 'hrb_preview_custom_fields', 10, 4 );

add_filter( 'parent_file', '_hrb_forms_tax_menu_fix' );


### Hooks Callbacks

/**
 * Registers the custom form post type by assign it to a taxonomy.
 */
function _hrb_register_custom_forms_post_type() {
	register_taxonomy_for_object_type( HRB_PROJECTS_CATEGORY, APP_FORMS_PTYPE );
}

/**
 * Position custom forms menu item right after 'Add New'.
 */
function _hrb_forms_tax_menu_fix( $parent_file ) {
	global $submenu;

	if ( ! isset( $submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE] ) ) {
		return $parent_file;
	}

	$idx = -1;
	foreach( $submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE] as $k => $submenu_item ) {

		// find the 'Add New' item position
		if ( 'post-new.php?post_type='.HRB_PROJECTS_PTYPE == $submenu_item[2] ) {
			$idx = $k + 1;
		}

		// find the 'Custom Forms' item position
		if ( 'edit.php?post_type=custom-form' == $submenu_item[2] ) {
			$custom_forms = $submenu_item;

			// swap with an existing item if set
			if ( isset( $submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE][ $idx ] ) ) {
				$submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE][ $k ] = $submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE][ $k ];
			}

			unset( $submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE][ $k ] );
			$submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE][ $idx ] = $submenu_item;
		}
	}

	// re-sort the menu
	ksort( $submenu['edit.php?post_type='.HRB_PROJECTS_PTYPE] );

	return $parent_file;
}

/**
 * Ajax call to render/display the custom form.
 */
function _hrb_forms_ajax_render_project_form() {

	if ( empty( $_POST['category'] ) ) {
		die;
	}

	$cat = $_POST['category'];
	$listing_id = (int) $_POST['listing_id'];

	hrb_render_form( $cat, HRB_PROJECTS_CATEGORY, $listing_id );
	die;
}

/**
 * Displays a custom form.
 *
 * @uses apply_filters() Calls 'hrb_render_form_fieldset'
 *
 */
function hrb_render_form( $categories, $taxonomy, $post_id = 0 ) {

	if ( empty( $categories) ) {
		return;
	}

	foreach ( (array) $categories as $category ) {

		$forms = hrb_get_custom_form( $category, $taxonomy );
		if ( empty( $forms ) ) {
			continue;
		}

		foreach( $forms as $form ) {
			$form_fields = APP_Form_Builder::get_fields( $form->ID );

			$fields = array();

			foreach( $form_fields as $field ) {

				if ( 'file' == $field['type'] ) {
					if ( empty( $field['extra']['class'] ) ) {
						$field['extra']['class'] = '';
					}
					$class = 'file-upload ' . $field['extra']['class'];

					ob_start();
					echo '<div class="'.$class.'">';
					appthemes_media_manager( $post_id, array( 'id' => $field['name'], 'title' => $field['desc'] ), array( 'mime_types' => $field['extensions'] ) );
					echo '</div>';
					$output = ob_get_clean();
					$fields[] = $output;
				} else {
					$fields[] = HRB_Field::label_input_from_meta( $field, $post_id );
				}

			}

			if ( ! empty( $fields ) ) {

				$fieldset  = array(
					'legend' => $form->post_title,
					'slug' => $form->post_slug,
					'fields' => $fields,
				);

				$fieldset = apply_filters( 'hrb_render_form_fieldset', $fieldset, $post_id );

				appthemes_load_template( 'form-project-custom-field.php', $fieldset );
			}
		}
	}

}

/**
 * Retrieves a custom form by terms and taxonomy.
 */
function hrb_get_custom_form( $terms, $taxonomy ) {
	$args =	array(
			'post_type' => APP_FORMS_PTYPE,
			'tax_query' => array(
				array(
					'taxonomy' => $taxonomy,
					'terms' => (array) $terms,
					'field' => 'id',
					'include_children' => false
				)
			),
			'post_status' => 'publish',
			'numberposts' => 2
	);
	$forms = get_posts( $args );

	if ( empty( $forms ) ) {
		return array();
	}

	return $forms;
}

/**
 * Retrieves the fields for a custom form by terms and taxonomy.
 */
function hrb_get_custom_fields( $terms, $taxonomy ) {

	if ( ! $terms ) {
		return;
	}

	$fields = array();

	foreach( (array) $terms as $term ) {
		$forms = hrb_get_custom_form( $term, $taxonomy );
		if ( empty( $forms ) ) {
			continue;
		}
		foreach( $forms as $form ) {
			$form_fields = APP_Form_Builder::get_fields( $form->ID );

			foreach ( $form_fields as $field ) {
				$fields[ $field['name'] ] = $field;
			}
		}
	}
	return $fields;
}

/**
 * Handles and updates the fields in a custom form.
 */
function hrb_update_form_builder( $terms, $post_id, $taxonomy ) {

	$fields = hrb_get_custom_fields( $terms, $taxonomy );

	if ( empty( $fields) ) {
		return;
	}

	$to_update = scbForms::validate_post_data( $fields );

	scbForms::update_meta( $fields, $to_update, $post_id );
}

/**
 * Outputs the custom fields tag placeholder.
 */
function hrb_output_custom_fields_placeholder( $post ) {

	if ( ! current_theme_supports('app-form-builder') ) {
		return;
	}

	$post = get_post( $post );

	if ( $post ) {
		$post_type = $post->post_type;
	} else {
		$post_type = HRB_PROJECTS_PTYPE;
	}

	// output the placeholder for the custom fields
	echo html( 'div', array( 'id' => "{$post_type}-form-custom-fields" ) );
}

/**
 * Retrieves custom fields label/value pairs to be used for previewing a listing.
 */
function hrb_preview_custom_fields( $fields, $post_id, $listing_cat, $taxonomy ) {

	if ( ! current_theme_supports('app-form-builder') ) {
		return;
	}

	$meta = get_post_custom( $post_id );

	$custom_fields = hrb_get_custom_fields( $listing_cat, $taxonomy );

	if ( empty( $custom_fields) ) {
		return array();
	}

	$output = '';

	foreach( $custom_fields as $name => $field ) {

		if ( ! empty( $meta[ $field['name'] ][0] ) ) {

			if ( 'file' == $field['type'] ) {
				$attachment_ids = maybe_unserialize( $meta[ $field['name'] ][0] );
				$output = appthemes_output_attachments( $attachment_ids, null, $output = false );
				$fields[ $field['desc'] ] = $output;
			} else {
				$fields[ $field['desc'] ] = $meta[ $field['name'] ][0];
			}

		}
	}

	return $fields;
}
