<div class="section-head">
	<h1><?php _e( 'Details', APP_TD ); ?></h1>
</div>

<form id="create-project-form" class="custom main" enctype="multipart/form-data" method="post" action="<?php echo esc_url( $form_action ); ?>">

	<fieldset>
		<legend><?php _e( 'Essential info', APP_TD ); ?></legend>
		<div class="row">
			<div class="large-12 columns">
				<label for="post_title"><?php _e( 'What do you need?', APP_TD ); ?></label>
				<input name="post_title" tabindex="1" type="text" placeholder="<?php echo esc_attr__( 'e.g: I need a Web Developer to develop a plugin', APP_TD ); ?>" value="<?php echo esc_attr( $project->post_title ); ?>" class="required" />
			</div>
		</div>
		<div class="row">
			<div class="large-12 columns">
				<label for="post_content"><?php _e( 'Project Details', APP_TD ); ?></label>
				<textarea name="post_content" tabindex="2" placeholder="<?php echo esc_attr__( 'Provide a detailed description of what you need to get done', APP_TD ); ?>" class="required"><?php echo esc_textarea( $project->post_content ); ?></textarea>
			</div>
		</div>
	</fieldset>

	<fieldset>
		<legend><?php _e( 'Categories & Skills', APP_TD ); ?></legend>
		<div class="row">
			<div class="large-6 columns category-dropdown">
				<div class="row">
					<div class="large-12 columns">
						<label for="category"><?php echo __( 'Category', APP_TD ); ?></label>
							<?php
								$args = array(
									'id' => 'category',
									'name' => '_'.HRB_PROJECTS_CATEGORY.'[]',
									'taxonomy' => HRB_PROJECTS_CATEGORY,
									'hide_empty' => false,
									'hierarchical' => true,
									'depth' => 1,
									'selected' => $project->categories,
									'class' => 'category-dropdown required' . ( $categories_locked ? ' locked' : '' ) ,
									'show_option_all' => __( '- Select Category -', APP_TD ),
									'tab_index' => 3
								);
								wp_dropdown_categories( $args );
							?>

							<?php if ( $categories_locked ): ?>
									<input name="<?php echo '_'.HRB_PROJECTS_CATEGORY.'[]'; ?>" type="hidden" value="<?php echo esc_attr( $project->categories ); ?>">
							<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="large-6 columns sub-category-dropdown">
				<div class="row">
					<div class="large-12 columns">
						<label for="sub_category"><?php echo __( 'Sub-Category', APP_TD ); ?></label>
						<select id="sub_category" name="<?php echo esc_attr( '_'.HRB_PROJECTS_CATEGORY ); ?>[]" tabindex="4" class="subcategory-dropdown" pre-selected="<?php echo esc_attr( $project->subcategories ); ?>" >
							<option value=""><?php echo __( '- Select Sub-Category -', APP_TD ); ?></option>
						</select>
					</div>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="large-12 columns">
			<?php if ( hrb_charge_listings() ): ?>
				<p class="important-note"><?php echo __( '<strong>Note:</strong> Categories are locked after purchase', APP_TD ); ?></p>
			<?php endif; ?>
			</div>
		</div>

		<?php if ( hrb_get_allowed_skills_count() ): ?>

			<div class="row">
				<div class="large-12 columns">
					<div class="row">
						<div class="large-12 columns">
							<label for="skills"><?php echo __( 'Skills', APP_TD ); ?></label>
							<?php
								$args = array(
									'id' => 'skills',
									'name' => '_'.HRB_PROJECTS_SKILLS.'[]',
									'taxonomy' => HRB_PROJECTS_SKILLS,
									'hide_empty' => false,
									'hierarchical' => true,
									'selected' => $project->skills,
									'walker' => new HRB_OptGroup_Category_Walker,
									'depth' => 5,
									'echo' => false,
									'tab_index' => 5
								);
								$dropdown = wp_dropdown_categories( $args );

								// make this a multiple dropdown
								echo str_replace( '<select ', '<select multiple="multiple"', $dropdown );
							?>
						</div>
					</div>
				</div>
			</div>

		<?php endif; ?>

		<div class="row">
			<div class="large-12 columns">
				<div class="row">
					<div class="large-12 columns">
						<label for="tags"><?php echo __( 'Tags', APP_TD ); ?></label>
						<span class="tags-tags"></span>
						<input id="tags" name="<?php echo esc_attr( HRB_PROJECTS_TAG ); ?>" tabindex="6" type="text" class="tm-input tm-tag" placeholder="<?php echo esc_attr__( 'Add some tags for this project. e.g: mobile, web (comma separated)', APP_TD ); ?>" value="<?php echo esc_attr( $project->tags ); ?>">
					</div>
				</div>
			</div>
		</div>
	</fieldset>

	<?php do_action( 'hrb_project_custom_fields', $project ); ?>

	<fieldset>
		<legend><?php _e( 'Budget', APP_TD ); ?></legend>
		<div class="row">
			<div class="large-4 columns">
				<select id="budget_type" name="budget_type" tabindex="10">
					<?php if ( ! $hrb_options->budget_types || 'fixed' == $hrb_options->budget_types ): ?>
						<option value="fixed" <?php selected( $project->_hrb_budget_type, 'fixed' ); ?>><?php echo __( 'Fixed Price', APP_TD ); ?></option>
					<?php endif; ?>
					<?php if ( ! $hrb_options->budget_types || 'hourly' == $hrb_options->budget_types ): ?>
						<option value="hourly" <?php selected( $project->_hrb_budget_type, 'hourly' ); ?>><?php echo __( 'Per Hour', APP_TD ); ?></option>
					<?php endif; ?>
				</select>
			</div>
			<div class="large-8 columns">
				<div class="row collapse">
					<div class="large-5 columns">
						<span class="prefix"><?php _e( 'Currency', APP_TD ); ?></span>
					</div>
					<div class="large-7 columns budget-currency">
						<select id="budget_currency" name="budget_currency" tabindex="11">
							<?php foreach( hrb_get_currencies() as $key => $currency ): ?>
							<option currency-symbol="<?php echo $currency['symbol'] ?>" value="<?php echo esc_attr( $key ); ?>" <?php selected( $project->_hrb_budget_currency ? $project->_hrb_budget_currency : APP_Currencies::get_current_currency('code'), $key ); ?>><?php echo $currency['name']; ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div>
		</div>

		<hr/>

		<div class="row">
			<div class="large-6 columns">
				<div class="row collapse">
					<div class="large-6 small-6 columns budget-price">
						<span class="prefix"><?php _e( 'Price', APP_TD ); ?></span>
					</div>
					<div class="large-1 small-1 columns">
						<span class="prefix selected-currency center">$</span>
					</div>
					<div class="large-5 small-5 columns">
						<input id="budget_price" name="budget_price" tabindex="12" type="text" class="required" placeholder="<?php echo esc_attr__( 'e.g: 40', APP_TD ); ?>" value="<?php echo esc_attr( $project->_hrb_budget_price ); ?>"/>
					</div>
				</div>
			</div>
			<div class="large-6 columns">
				<div class="row collapse budget-min-hours">
					<div class="large-6 small-6 columns">
						<span class="prefix"><?php _e( 'Min. Hours', APP_TD ); ?></span>
					</div>
					<div class="large-6 small-6 columns">
						<input id="hourly_min_hours" name="hourly_min_hours" tabindex="13" type="text" class="required" placeholder="<?php echo esc_attr__( 'e.g: 8', APP_TD ); ?>" value="<?php echo esc_attr( $project->_hrb_hourly_min_hours ); ?>"/>
					</div>
				</div>
			</div>
		</div>
	</fieldset>

    <?php if ( ! hrb_charge_listings() ): ?>

        <fieldset>
            <legend><?php _e( 'Duration', APP_TD ); ?></legend>
            <div class="row">
                <div class="large-8 columns">
                    <div class="row collapse">
                        <div class="large-6 columns">
                            <span class="prefix"><?php echo __( 'Post this Project for', APP_TD ); ?></span>
                        </div>
                        <div class="large-3 columns">
                            <input id="duration" name="duration" tabindex="14" type="text" <?php echo ( ! $hrb_options->project_duration_editable ? 'readonly' : '' ); ?> class="required" placeholder="<?php echo esc_attr__( 'e.g: 30', APP_TD ); ?>" value="<?php echo esc_attr( $project->_hrb_duration ? $project->_hrb_duration : $hrb_options->project_duration ); ?>" />
                        </div>
                        <div class="large-3 columns">
                            <span class="postfix"><?php echo __( 'Days', APP_TD ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ( $hrb_options->project_duration ): ?>
            <div class="row">
                <div class="large-8 columns">
                    <div class="row">
                        <div class="large-8 columns">
                            <label><?php echo sprintf( __( 'Maximum days allowed is %1$d %2$s', APP_TD ), $hrb_options->project_duration, ( ! $hrb_options->project_duration_editable ? __( '(not editable)', APP_TD ) : '' ) ); ?></label>
                        </div>
                    </div>
            </div>
            <?php endif; ?>
        </fieldset>

    <?php endif; ?>

	<fieldset id="optional-fields">
		<legend><?php _e( 'Other', APP_TD ); ?></legend>

		<?php if ( ! $hrb_options->local_users ): ?>

			<div class="row">
				<div class="large-12 columns">
					<div class="row collapse">
						<div class="large-2 small-4 columns">
							<span class="prefix"><?php _e( 'Location', APP_TD ); ?></span>
						</div>
						<div class="large-3 small-4 columns location-type">
							<select id="location_type" name="location_type" tabindex="16">
								<?php if ( ! $hrb_options->location_types || 'remote' == $hrb_options->location_types ): ?>
									<option value="remote" <?php selected( $project->_hrb_location_type, 'remote' ); ?>><?php echo __( 'Remote', APP_TD ); ?></option>
								<?php endif; ?>
								<?php if ( ! $hrb_options->location_types || 'local' == $hrb_options->location_types ): ?>
									<option value="local" <?php selected( $project->_hrb_location_type, 'local' ); ?>><?php echo __( 'Local', APP_TD ); ?></option>
								<?php endif; ?>
							</select>
						</div>
						<div class="large-7 columns custom-location">
							<input type="text" id="location" name="location" tabindex="17" data-geo="formatted_address" placeholder="<?php echo esc_attr__( 'e.g: New York', APP_TD ); ?>" class="required" value="<?php echo esc_attr( $project->_hrb_location ); ?>" />
							<?php
								foreach ( hrb_get_geocomplete_attributes() as $location_att ) :
									$meta_key = "_hrb_location_{$location_att}";
							?>
									<input type="hidden" id="<?php echo esc_attr( $meta_key ); ?>" name="<?php echo esc_attr( $meta_key ); ?>" data-geo="<?php echo esc_attr( $location_att ); ?>" value="<?php echo esc_attr( $project->$meta_key ); ?>" />
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>

		<?php endif; ?>

		<div class="row">
			<div class="large-12 columns">
				<?php hrb_media_manager( $project->ID, array( 'id' => '_app_media', 'title' => __( 'Files', APP_TD ) ) );  ?>
			</div>
		</div>

	</fieldset>

	<?php do_action( 'hrb_project_form', $project ); ?>

	<fieldset>
		<?php do_action( 'hrb_project_form_fields', $project ); ?>

		<?php wp_nonce_field('hrb_post_project'); ?>

		<?php
			hrb_hidden_input_fields(
				array(
					'ID'	=> esc_attr( $project->ID ),
					'action'=> esc_attr( $action ),
				)
			);
		?>

		<input tabindex="20" type="submit" class="button" value="<?php echo esc_attr( $bt_step_text ); ?>" />
	</fieldset>
</form>
