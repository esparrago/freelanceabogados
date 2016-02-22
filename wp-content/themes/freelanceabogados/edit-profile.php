<?php
/*  Template Name: Edit Profile
 *
 * This template must be assigned to a page
 * in order for it to work correctly
 *
 */
?>
<div id="main" class="large-8 columns">

	<div class="edit-profile">

		<h2><?php _e('Update Your Profile', APP_TD) ?></h2>

		<div class="form-wrapper">

			<div class="row">
				<div class="large-12 columns">

					<form name="profile" id="profile-form" action="#" class="custom" method="post" enctype="multipart/form-data">

						<div class="row">
							<div class="large-7 small-0 columns">
								&nbsp;
							</div>
							<?php if ( $hrb_options->avatar_upload ): ?>
								<div class="large-2 small-2 columns upload-gravatar">
									<?php hrb_gravatar_media_manager( $current_user->ID, array( 'id' => '_app_gravatar' ) );  ?>
								</div>
							<?php endif; ?>
							<div class="large-3 small-3 columns user-meta-info right">
								<?php the_hrb_user_bulk_info( $current_user->ID, array( 'show_gravatar' => array( 'size' => 75 ) ) ); ?>
							</div>
						</div>

						<fieldset>

							<div class="row">
								<div class="large-6 columns form-field">
									<label><?php _e('First Name', APP_TD) ?></label>
									<input type="text" name="first_name" class="text regular-text" id="display_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" maxlength="100" />
								</div>
								<div class="large-6 columns form-field">
									<label><?php _e('Last Name', APP_TD) ?></label>
									<input type="text" name="last_name" class="text regular-text" id="display_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" maxlength="100" />
								</div>
							</div>

							<div class="row">
								<div class="large-6 columns form-field">
									<label><?php _e('Display Name', APP_TD) ?></label>
									<input type="text" name="display_name" class="text regular-text required" id="display_name" value="<?php echo esc_attr( $current_user->display_name ); ?>" maxlength="100" />
								</div>

								<div class="large-6 columns form-field">
									<label>
										<label><?php _e('Private Email', APP_TD) ?></label>
										<input type="text" name="email" class="text regular-text required" id="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" maxlength="100" />
									</label>
								</div>
							</div>

							<?php foreach ( wp_get_user_contact_methods( $current_user ) as $name => $desc ) : ?>

									<div class="form-field">
										<label for="<?php echo esc_attr( $name ); ?>"><?php echo apply_filters( "user_{$name}_label", $desc ); ?>:</label>
										<input type="text" name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $current_user->$name ); ?>" class="regular-text" />
									</div>

							<?php endforeach; ?>

							<div class="row">
								<div class="large-12 columns form-field">
									<label><?php _e('Website', APP_TD) ?></label>
									<input type="text" name="url" class="text regular-text" id="url" value="<?php echo esc_url( $current_user->user_url ); ?>" maxlength="100" />
								</div>
							</div>
							<div class="row">
								<div class="large-12 columns form-field">
									<label><?php _e('About Me', APP_TD); ?></label>
									<textarea name="description" class="text regular-text" id="description" rows="10" cols="50"><?php echo esc_attr( $current_user->description ); ?></textarea>
								</div>
							</div>

							<?php if ( $show_password_fields ) : ?>

							<div class="row">
								<div class="large-6 columns form-field">
									<label><?php _e('New Password', APP_TD); ?></label>
									<input type="password" name="pass1" class="text regular-text" id="pass1" maxlength="50" value="" />
									<span class="description"><?php _e('Leave this field blank unless you would like to change your password.', APP_TD); ?></span>
								</div>
								<div class="large-6 columns form-field">
									<label><?php _e('Password Again', APP_TD); ?></label>
									<input type="password" name="pass2" class="text regular-text" id="pass2" maxlength="50" value="" />
									<span class="description"><?php _e('Type your new password again.', APP_TD); ?></span>
								</div>
							</div>

							<div class="row pass-strenght-indicator">
								<div class="large-12 columns form-field">
									<span class=""><?php _e('Your password should be at least seven characters long.', APP_TD); ?></span>
									<p id="pass-strength-result"><?php _e('Strength indicator', APP_TD); ?></p>
								</div>
							</div>

							<?php endif; ?>

							<?php
								do_action( 'profile_personal_options', $current_user );
								do_action( 'show_user_profile', $current_user );
							?>

							<div class="form-field">
								<input type="submit" class="button" name="update_profile" value="<?php echo esc_attr( __( 'Update Profile', APP_TD ) ); ?>">
							</div>

						</fieldset>

						<?php wp_nonce_field( 'app-edit-profile' ); ?>

						<?php
							hrb_hidden_input_fields(
								array(
									'from'          => 'profile',
									'action'        => 'app-edit-profile',
									'checkuser_id'  => $user_ID,
									'user_id'       => $user_ID,
								 )
							);
						?>
					</form>

				</div>
			</div>
		</div>
	</div>

</div><!-- /#main -->

<?php appthemes_load_template( 'sidebar-dashboard.php' ); ?>
