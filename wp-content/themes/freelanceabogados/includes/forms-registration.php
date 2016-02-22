<?php
/**
 * Handles/processes registration forms data.
 */

add_action( 'user_register', '_hrb_user_register_role' );

/**
 * Assigns the selected role to the user being registered.
 */
function _hrb_user_register_role( $user_id ) {

	if ( empty( $_POST['role'] ) ) {
		return;
    }

	$valid_roles = array_keys( hrb_roles() );
	$role = $_POST['role'];

	// make sure we always get a valid role on registration
	if ( empty( $role ) || ! in_array( $role, $valid_roles ) ) {
		$role = HRB_ROLE_BOTH;
    }

	wp_update_user( array ( 'ID' => $user_id, 'role' => $role ) );
}
