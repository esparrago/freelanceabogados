
<h3><?php _e( 'Por favor completa la información para registrarte:', APP_TD ); ?></h3>

<div class="row">
	<div class="large-12 columns">

		<form action="<?php echo appthemes_get_registration_url( 'login_post' ); ?>" method="post" class="login-form register-form custom" name="registerform" id="login-form">
			<fieldset>
				<div class="row">
					<div class="large-6 columns form-field">
						<label><?php _e( 'Nombre Completo:', APP_TD ); ?></label>
						<input tabindex="1" type="text" class="text required" name="user_login" id="user_login" value="<?php if (isset($_POST['user_login'])) echo esc_attr(stripslashes($_POST['user_login'])); ?>" />
					</div>

					<div class="large-6 columns form-field">

					</div>

					<div class="large-6 columns form-field">
						<label><?php _e( 'Correo electrónico:', APP_TD ); ?></label>
						<input tabindex="2" type="text" class="text required email" name="user_email" id="user_email" value="<?php if (isset($_POST['user_email'])) echo esc_attr(stripslashes($_POST['user_email'])); ?>" />
					</div>
				</div>

				<div class="row">
					<div class="large-6 columns form-field">
						<label><?php _e( 'Contraseña:', APP_TD ); ?></label>
						<input tabindex="3" type="password" class="text required" name="pass1" id="pass1" value="" autocomplete="off" />
					</div>
					
					<div class="large-6 columns form-field" >
						<label><?php _e( 'Repite contraseña:', APP_TD ); ?></label>
						<input tabindex="4" type="password" class="text required" name="pass2" id="pass2" value="" autocomplete="off" />
					</div>
				</div>

				<div class="row">
					<div class="large-12 columns form-field">
						<div id="pass-strength-result" class="hide-if-no-js"><?php _e( 'Strength indicator', APP_TD ); ?></div>
						<p class="description indicator-hint"><?php _e( 'Consejo: La clave debe ser minimo de caracteres. para hacerla mas segura usa mayusculas, minusculas  y caracteres como ! " ? $ % ^ &amp; ).', APP_TD ); ?></p>
					</div>
				</div>

				<h4><?php echo __( 'Que eres', APP_TD ); ?></h4>

				<div class="row">
					<div class="large-4 small-12 columns form-field user-role-type">

						<select name="role">
							<option value="<?php echo esc_attr( HRB_ROLE_EMPLOYER ); ?>" selected="selected"><?php echo __( 'Cliente', APP_TD ); ?></option>
							<option value="<?php echo esc_attr( HRB_ROLE_FREELANCER ); ?>"><?php echo __( 'Abogado', APP_TD ); ?></option>
							<?php if ( $hrb_options->share_roles_caps ): ?>
								<option value="<?php echo esc_attr( HRB_ROLE_BOTH ); ?>"/> <?php echo __( 'Both', APP_TD ) ?></option>
							<?php endif; ?>
						</select>

					</div>
				</div>

				<?php do_action( 'register_form' ); ?>

				<div class="form-field">
					<?php echo HRB_Login_Registration::redirect_field(); ?>
					<input tabindex="30" type="submit" class="btn reg" id="register" name="register" value="<?php _e( 'Registrar', APP_TD ); ?>" />
				</div>

			</fieldset>

			<!-- autofocus the field -->
			<script type="text/javascript">try{document.getElementById('user_login').focus();}catch(e){}</script>
		</form>

	</div>
</div>
