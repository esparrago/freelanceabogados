<?php

define( 'APP_FORMS_PTYPE', 'custom-form' );

add_action( 'init', array( 'APP_Form_Builder', 'register_post_type' ) );
add_action( 'save_post', array( 'APP_Form_Builder', 'save_fields' ) );
add_action( 'admin_print_styles', array( 'APP_Form_Builder', 'admin_print_styles' ) );


class APP_Form_Builder {

	public static function register_post_type() {
		$labels = array(
			'name'               => __( 'Custom Forms', APP_TD ),
			'singular_name'      => __( 'Custom Form', APP_TD ),
			'add_new'            => __( 'Add New', APP_TD ),
			'add_new_item'       => __( 'Add New Custom Form', APP_TD ),
			'edit_item'          => __( 'Edit Form', APP_TD ),
			'new_item'           => __( 'New Form', APP_TD ),
			'view_item'          => __( 'View Forms', APP_TD ),
			'search_items'       => __( 'Search Forms', APP_TD ),
			'not_found'          => __( 'No forms found', APP_TD ),
			'not_found_in_trash' => __( 'No forms found in Trash', APP_TD ),
			'menu_name'          => __( 'Custom Forms', APP_TD )
		);

		list( $args ) = get_theme_support( 'app-form-builder' );

		$ptype_args = array(
			'labels'               => $labels,
			'supports'             => array( 'title' ),
			'register_meta_box_cb' => array( __CLASS__, 'register_meta_box' ),
			'show_in_menu'         => $args['show_in_menu'],
			'menu_icon'            => appthemes_locate_template_uri( 'includes/custom-forms/admin-menu.png' ),
			'capability_type'      => 'page',
			'hierarchical'         => false,
			'show_ui'              => true,
			'show_in_nav_menus'    => false,
			'publicly_queryable'   => false,
			'exclude_from_search'  => true,
			'has_archive'          => false,
			'query_var'            => false,
			'can_export'           => true,
		);

		$ptype_args = apply_filters( 'appthemes_custom_forms_ptype_args', $ptype_args );

		register_post_type( APP_FORMS_PTYPE, $ptype_args );
	}

	public static function register_meta_box( $post ) {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ), 99 );
		add_meta_box( 'app-form-builder', __( 'Form Builder', APP_TD ), array( __CLASS__, 'meta_box' ), APP_FORMS_PTYPE, 'normal', 'core' );
	}

	public static function enqueue_scripts() {
		wp_enqueue_script( 'form-builder', get_template_directory_uri() . '/includes/custom-forms/form-builder.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'validate', 'validate-lang' ), '20110909' );
		wp_enqueue_script( 'form-builder-helper', get_template_directory_uri() . '/includes/custom-forms/form-builder-helper.js', array( 'jquery' ), '20110909' );
		wp_localize_script(
			'form-builder',
			'l10n',
			array(
				'save'               => __( 'Save', APP_TD ),
				'add_new_field'      => __( 'Add New Field...', APP_TD ),
				'text'               => __( 'Text Field', APP_TD ),
				'title'              => __( 'Title', APP_TD ),
				'textarea'           => __( 'Text Area', APP_TD ),
				'checkboxes'         => __( 'Checkboxes', APP_TD ),
				'radio'              => __( 'Radio', APP_TD ),
				'select'             => __( 'Select List', APP_TD ),
				'text_field'         => __( 'Text Field', APP_TD ),
				'file'				 => __('File Upload', APP_TD),
				'file_extensions'	 => __('Allowed Extensions', APP_TD),
				'file_tip'			 => __('(comma separated. i.e: pdf, doc)', APP_TD),
				'label'              => __( 'Label', APP_TD ),
				'textarea_field'     => __( 'Text Area Field', APP_TD ),
				'select_options'     => __( 'Select Options', APP_TD ),
				'add'                => __( 'Add', APP_TD ),
				'checkbox_group'     => __( 'Checkbox Group', APP_TD ),
				'remove_message'     => __( 'Are you sure you want to remove this element?', APP_TD ),
				'remove'             => __( 'Remove', APP_TD ),
				'radio_group'        => __( 'Radio Group', APP_TD ),
				'selections_message' => __( 'Allow Multiple Selections', APP_TD ),
				'hide'               => __( 'Hide', APP_TD ),
				'required'           => __( 'Required', APP_TD ),
				'show'               => __( 'Show', APP_TD ),
			)
		);

		wp_enqueue_style( 'form-builder', get_template_directory_uri() . '/includes/custom-forms/form-builder.css', array(), '20110909' );
		wp_enqueue_style( 'gh-buttons', get_template_directory_uri() . '/includes/custom-forms/gh-buttons.css', array( 'colors' ), '20110911' );

	}

	public static function meta_box( $post ) {
		if ( ! $form = get_post_meta( $post->ID, 'va_form', true ) ) {
			$form = array();
		}
?>
		<script type="text/javascript">
		jQuery( function($) {
			$('#app-form-builder-div').formbuilder();
			$(function() {
				$("#app-form-builder ul").sortable({ opacity: 0.6, cursor: 'move'});
			});
		});
		</script>

	<div id="app-form-builder-div">
		<input type="hidden" name="va_form" id="va_form" value='<?php echo esc_attr( json_encode( $form ) ); ?>' />
	</div>

<?php
	}

	public static function admin_print_styles() {
		appthemes_menu_sprite_css(
			array( '#adminmenu #menu-posts-' . APP_FORMS_PTYPE ),
			appthemes_locate_template_uri( 'includes/custom-forms/admin-menu.png' )
		);
	
	}

	static function make_id_unique( $id, $field_id, $fields, $unique_id = 1 ) {
		if ( empty( $id ) ) {
			$field_id = 'app_' . $unique_id;
		}

		if ( in_array( $field_id, $fields ) ) {

			if ( false === strpos( $field_id , '_' . $unique_id ) ) {
				$field_id .= '_' . $unique_id;
			} else {
				$unique_id_old = $unique_id;
				$unique_id++;
				$field_id = str_replace( '_' . $unique_id_old, '_' . $unique_id, $field_id );
			}

			return self::make_id_unique( $id, $field_id, $fields, $unique_id );
		}

		return $field_id;
	}

	public static function save_fields( $form_id ) {
		if ( ! isset( $_POST['va_form'] ) ) {
			return;
		}

		parse_str( $_POST['va_form'] );

		if ( ! is_array( $va_form ) ) {
			$va_form = array();
		}

		$unique_id = 1;

		$fields = array();
		foreach ( $va_form as &$field ) {

			if ( ! isset( $id[ $field['cssClass'] ] ) ) {
				$id[ $field['cssClass'] ] = 1;
			}

			if ( 'input_text' == $field['cssClass'] || 'textarea' == $field['cssClass'] || 'file' == $field['cssClass'] ) {
				$label = $field['values'];

				// sanitize file extensions
				if ( 'file' == $field['cssClass'] ) {
					$field['extensions'] = preg_replace( '/[^A-Za-z,]/', '', $field['extensions'] );
				}

			} else {
				$label = $field['title'];
			}

			if ( ! $label )	{
				$label = $field['cssClass'] . '_' . $id[ $field['cssClass'] ]++;
			}

			if ( empty( $field['id'] ) || 'undefined' == $field['id'] ) {
				$unique_id = strtolower( $label );
				$unique_id = str_replace( ' ', '-', $unique_id );
				$unique_id = preg_replace( '/[^a-z0-9_\-]/', '', $unique_id );
				$field['id'] = 'app_' . $unique_id;
			} else {
				$unique_id = substr( $field['id'], 4 );
			}

			// avoid duplicate field ids
			$field['id'] = self::make_id_unique( $unique_id, $field['id'], $fields );

			$fields[] = $field['id'];
		}

		update_post_meta( $form_id, 'va_form', $va_form );
	}

	static function get_fields( $form_id ) {
		$form = get_post_meta( $form_id, 'va_form', true );
		if ( ! $form ) {
			return array();
		}

		$fields = array();

		foreach ( $form as $field ) {
			$args = array(
				'name' => $field['id'],
				'type' => $field['cssClass'],
			);

			if ( $field['required'] == 'checked' ) {
				$args['extra'] = array( 'class' => 'required' );
			}

			if ( 'input_text' == $args['type'] ) {
				$args['type'] = 'text';
			}

			switch ( $args['type'] ) {
				case 'select':
				case 'radio':
				case 'checkbox':
					$args['desc'] = $field['title'];

					$values = array();
					$checked = array();

					foreach ( $field['values'] as $option ) {
						$values[] = $option['value'];

						if ( $option['baseline'] == 'checked' ) {
							$checked[] = $option['value'];
						}
					}

					$args['values'] = $values;

					if ( 'checkbox' == $args['type'] ) {
						$args['default'] = $checked;
					} elseif ( ! empty( $checked[0] ) ) {
						$args['default'] = $checked[0];
					}
					break;
				case 'file':
					$args['desc'] = $field['values'];
					$args['extensions'] = $field['extensions'];
					break;
				default:
					$args['desc'] = $field['values'];
			}

			$args['desc_pos'] = 'before';

			$fields[] = $args;
		}

		return $fields;
	}
}

