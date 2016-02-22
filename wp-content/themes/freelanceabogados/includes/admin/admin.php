<?php

add_filter( 'manage_post_posts_columns', 'hrb_posts_columns', 5 );
add_filter( 'admin_comment_types_dropdown' , '_hrb_clarification_comment_type' );

add_action( 'manage_posts_custom_column', '_hrb_posts_custom_columns', 5, 2 );

add_action( 'admin_enqueue_scripts', '_hrb_register_admin_scripts', 10 );
add_action( 'admin_enqueue_scripts', '_hrb_enqueue_admin_scripts', 11 );

add_action( 'load-post-new.php', '_hrb_disable_admin_project_creation' );
add_action( 'load-post.php', '_hrb_disable_admin_project_editing' );
add_action( 'admin_init', '_hrb_disable_admin_profile_edit' );

add_action( 'admin_head', '_hrb_maybe_disalow_publish' );
add_action( 'admin_head', '_hrb_maybe_disalow_inline_publishing' );
add_action( 'admin_notices', '_hrb_maybe_warn_editing' );

// Various tweaks
add_action( 'admin_menu', '_hrb_admin_menu_tweak' );
add_action( 'admin_print_styles', '_hrb_admin_styles' );

/**
 * Displays the 'clarification' custom comment type on the comment types dropdown.
 */
function _hrb_clarification_comment_type( $comment_types ) {

	$comment_types = $comment_types + array(
		HRB_CLARIFICATION_CTYPE => __( 'Clarification', APP_TD ),
	);

	return $comment_types;
}


/**
 * Adds thumbnail column to posts.
 */
function hrb_posts_columns( $defaults ){
    $defaults['post_thumbnail'] = __( 'Thumbnail', APP_TD );
    return $defaults;
}

/**
 * Outputs the post thumbnail.
 */
function _hrb_posts_custom_columns( $column_name, $id ){
	if ( 'post_thumbnail' == $column_name ) {
        echo the_post_thumbnail('thumbnail');
    }
}

/**
 * Register admin JS scripts.
 */
function _hrb_register_admin_scripts(){

	wp_register_script(
		'hrb-admin-settings',
		get_template_directory_uri() . '/includes/admin/scripts/settings.js',
		array('jquery'),
		HRB_VERSION,
		true
	);

	wp_register_script(
		'hrb-admin-project-edit',
		get_template_directory_uri() . '/includes/admin/scripts/project-edit.js',
		array('validate'),
		HRB_VERSION,
		true
	);

	wp_register_style(
		'hrb-jquery-ui',
		get_template_directory_uri() . '/styles/jqueryui/jquery-ui.css'
	);
}

/**
 * Enqueues admin JS scripts.
 */
function _hrb_enqueue_admin_scripts( $hook ) {
	global $post;

	### selective load

	// scripts/styles for settings page

	if ( strpos( $hook, 'page_app-settings' ) !== false ) {
		hrb_register_enqueue_styles( array( 'jquery-select2' ) );
		hrb_register_enqueue_scripts( array( 'hrb-admin-settings' ), $admin = true );
		hrb_register_enqueue_scripts( array( 'jquery-select2' ) );
		return;
	}

	// scripts/styles on admin post pages

	if ( empty( $post ) || HRB_PROJECTS_PTYPE != $post->post_type ) {
		return;
	}

	$pages = array( 'post.php', 'post-new.php' );
	if ( ! in_array( $hook, $pages ) ) {
		return;
	}

	hrb_register_enqueue_scripts( array( 'validate', 'hrb-admin-project-edit' ), $admin = true );

	wp_localize_script( 'hrb-admin-project-edit', 'HRB_admin_l18n', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'ajax_nonce' => wp_create_nonce( "listing-{$post->ID}" ),
		'user_admin' => current_user_can( 'manage_options' ),
		'project_type' => HRB_PROJECTS_PTYPE,
		'project_category' => HRB_PROJECTS_CATEGORY,
		'post_type' => ( isset( $post->post_type ) ? $post->post_type : '' ),
		'error_msg_empty' => __( 'Field cannot be empty.', APP_TD ),
		'geocomplete_options' => hrb_get_geocomplete_options(),
	) );

	hrb_maybe_enqueue_geo();
}

/**
 * Reorder admin menu.
 */
function _hrb_admin_menu_tweak() {
	global $menu;

	// move Posts below listings.
	$menu[7] = $menu[5];

	// move separator down
	$menu[5] = $menu[4];
	unset($menu[4]);
}

function _hrb_admin_styles() {
	appthemes_menu_sprite_css( array('#toplevel_page_app-dashboard') );
	?>
	<style>
		.inline-edit-project .inline-edit-group .alignleft {
			display: none;
		}
		.inline-edit-project .inline-edit-group .alignleft.inline-edit-status {
			display: block;
		}
	</style>
	<?php
}

/**
 * Redirect non-admins posting projects from the backend to the frontend post project page
 */
function _hrb_disable_admin_project_creation() {
	if ( current_user_can('edit_others_projects') ) {
		return;
	}

	if ( HRB_PROJECTS_PTYPE != @$_GET['post_type'] ) {
		return;
	}

	wp_redirect( get_the_hrb_project_create_url() );
	exit;
}

/**
 * Redirect non-admins editing projects from the backend to the frontend edit project page
 */
function _hrb_disable_admin_project_editing() {

	if ( current_user_can('edit_others_projects') ) {
		return;
	}

	if ( 'edit' != @$_GET['action'] ) {
		return;
	}

	$post_id = (int) @$_GET['post'];

	if ( HRB_PROJECTS_PTYPE != get_post_type( $post_id ) ) {
		return;
	}

	wp_redirect( get_the_hrb_project_edit_url( $post_id ) );
	exit;
}


/**
 * Redirect non-admins editing their profile from the backend to the frontend edit profile page
 */
function _hrb_disable_admin_profile_edit() {

	if ( ! defined('IS_PROFILE_PAGE') ) {
		return;
	}

	if ( current_user_can('edit_users') ) {
		return;
	}

	wp_redirect( appthemes_get_edit_profile_url() );
	exit;
}

/**
 * Checks if post can be edited in the backend.
 */
function hrb_disalow_post_admin_editing( $post_id ) {
	$post = get_post( $post_id );

	if ( ! is_admin() || $post->post_type != HRB_PROJECTS_PTYPE ) {
		return false;
	}

	$whitelisted = array_merge( array_keys( get_post_statuses() ), array( HRB_PROJECT_STATUS_EXPIRED, HRB_PROJECT_STATUS_CANCELED ) );

	return (bool) ( ! in_array( $post->post_status, $whitelisted ) );
}


/**
 * Warn users about editing posts considered closed.
 */
function _hrb_maybe_warn_editing() {
	global $pagenow, $post;

	if ( 'post.php' != $pagenow || ! hrb_disalow_post_admin_editing( $post->ID ) ) {
		return;
	}

	$msg = sprintf( __( "<strong>Warning:</strong> This project status is '<strong>%s</strong>'. Editing is not recommended!", APP_TD ), hrb_get_project_statuses_verbiages( $post->post_status ) );
	echo scb_admin_notice( $msg, 'error' );

}

/**
 * Hides the 'publish' button on posts considered closed.
 * Displays custom statuses on the 'publish' meta box.
 */
function _hrb_maybe_disalow_publish() {
	global $pagenow, $post;

	if ( 'post.php' != $pagenow || ! hrb_disalow_post_admin_editing( $post->ID ) ) {
		return;
	}

	$status = get_post_status_object( $post->post_status );
	$status_label = $status->label;

	$save_l18n = __( 'Save ', APP_TD );

?>

	<script>
		jQuery(document).ready(function($) {
			$('#post-status-display').html("<?php echo $status_label; ?>");

			$('#post_status').append('<option selected="selected" value="<?php echo $post->post_status; ?>"><?php echo $status_label; ?></option>');

			// remove the default 'save <status>' button
			$('#save-post').remove();

			// use a custom 'save <status>' button
			$('#save-action .spinner').before('<input type="submit" name="save" id="save-post-custom" value="<?php echo $save_l18n; ?>" class="button" style="float: left;" />');

			$('.save-post-status').on( 'click', function() {
				$('#save-post-custom').val( "<?php echo $save_l18n; ?>" + $('#post_status option:selected').text() );
				$('#post-status-select').slideUp('fast');
				$('#post-status-select').siblings('a.edit-post-status').show();
			});

			$('.save-post-status').trigger('click');

		});
	</script>

	<style>
		.edit-timestamp,
		.edit-visibility,
		#publishing-action { display: none; }
	</style>

<?php
}

/**
 * Hides the 'publish' button on posts considered closed in the inline editor.
 * Displays custom statuses on the 'publish' meta box in the inline editor.
 */
function _hrb_maybe_disalow_inline_publishing() {
	global $pagenow, $wp_post_statuses, $post;

	if ( 'edit.php' != $pagenow || ( $post && $post->post_type != HRB_PROJECTS_PTYPE ) ) {
		return;
	}

	$whitelisted = array_merge( array_keys( get_post_statuses() ), array( HRB_PROJECT_STATUS_EXPIRED ) );

	$custom_statuses_verbiages = wp_list_pluck( $wp_post_statuses, 'label' );
?>
	<script>
			jQuery(document).ready(function($) {

				// ** INLINE EDITOR METABOX **

				jQuery('a.editinline').on('click', function() {

					if ( typeof (this) == "object" ) {
						var post_id = inlineEditPost.getId(this)

						var context = $('#inline_' + post_id);
						var status = $( '._status', context ).text()

						var whitelisted = '<?php echo json_encode( $whitelisted ); ?>';
						var verbiages = '<?php echo json_encode( $custom_statuses_verbiages ); ?>';

						var parsed_verbiages = $.parseJSON(verbiages);

						context = $('#edit-'+post_id);

						if ( whitelisted.indexOf(status) < 0 ) {
							$('.inline-edit-status select[name=_status]').find('option[value=publish]').remove();
							$('.inline-edit-status select[name=_status]').append('<option selected="selected" value="'+status+'">'+parsed_verbiages[status]+'</option>');

						}

					}

				});

			});
		</script>

<?php

}
