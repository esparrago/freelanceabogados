<?php

class HRB_Settings_Admin extends APP_Tabs_Page {

	protected $permalink_sections;
	protected $permalink_options;

	function setup() {
		$this->textdomain = APP_TD;

		$this->args = array(
			'page_title' => __( 'Freelance Abogados Settings', APP_TD ),
			'menu_title' => __( 'Settings', APP_TD ),
			'page_slug' => 'app-settings',
			'parent' => 'app-dashboard',
			'screen_icon' => 'options-general',
			'admin_action_priority' => 13,
		);

		add_action( 'admin_notices', array( $this, 'prune_projects' ) );
	}

	public function prune_projects(){
		if ( isset( $_GET['prune'] ) && $_GET['prune'] == 1 && isset( $_GET['tab'] ) && $_GET['tab'] == 'projects' ) {
			hrb_prune_expired_projects();
			echo scb_admin_notice( 'Expired projects have been pruned.' );
		}
	}

	function form_handler() {
		global $app_bidding_options, $app_reviews_options, $hrb_options;

		parent::form_handler();

		if ( empty( $_POST['action'] ) || ! $this->tabs->contains( $_POST['action'] ) ) {
			return;
		}

		### custom settings - settings outputted using 'render' and not in the 'fields' array, under 'init_tabs()'

		// @todo create hook in 'APP_Tabs_Page' to be able to pass custom fields, instead of using this hack

		$form_fields = array(
			array(
				'title' => '',
				'type' => 'text',
				'name' => 'allowed_currencies',
			),
		);

		$to_update = scbForms::validate_post_data( $form_fields, null, $this->options->get() );

		$this->options->update( $to_update );

		### settings from external modules

		if ( current_theme_supports('app-bidding') ) {
			$new_data['notify_new_bid'] = $hrb_options->notify_new_proposals;
			$app_bidding_options->update( $new_data );
		}

		if ( current_theme_supports('app-reviews') ) {
			$new_data['notify_new_review'] = $hrb_options->notify_new_reviews;
			$app_reviews_options->update( $new_data );
		}

	}

	protected function init_tabs() {

		$this->tabs->add( 'general', __( 'General', APP_TD ) );
		$this->tabs->add( 'categories', __( 'Categories', APP_TD ) );
		$this->tabs->add( 'notifications', __( 'Notifications', APP_TD ) );
		$this->tabs->add( 'projects', __( 'Projects', APP_TD ) );
		$this->tabs->add( 'proposals', __( 'Proposals', APP_TD ) );
		$this->tabs->add( 'users', __( 'Users', APP_TD ) );
		$this->tabs->add( 'geolocation', __( 'Geolocation', APP_TD ) );

		$this->tab_sections['general']['registration'] = array(
			'title' => __( 'Registration', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Share Roles Capabilities', APP_TD ),
					'desc' => __( 'Allow Employers to apply to projects and Freelancers to post projects.', APP_TD ),
					'type' => 'checkbox',
					'name' => 'share_roles_caps',
					'tip' => __( 'With this option enabled, when a user registers as \'Employer\' and applies to a project, his role will automatically change to \'Employer/Freelancer\'. The same applies to a \'Freelancer\' that posts a project.'
                            . ' If you disable this option, employers will be limited to posting projects and freelancers limited to applying to projects.', APP_TD ),
				),
				array(
					'title' => __( 'Registration Box Title', APP_TD ),
					'type' => 'text',
					'name' => 'registration_box_title',
					'tip' => __( 'The title displayed on the small registration box to the right of the login form.', APP_TD ),
				),
				array(
					'title' => __( 'Registration Box Text', APP_TD ),
					'type' => 'textarea',
					'name' => 'registration_box_text',
					'extra' => array(
						'rows' => 5,
						'cols' => 70,
					),
					'tip' => __( 'The title displayed on the small registration box to the right of the login form.', APP_TD ),
				),
            ),
        );

		$this->tab_sections['general']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Theme Customizer', APP_TD ),
					'desc' => sprintf( __( '<a href="%s">Customize HireBee</a> design and settings and see the results real-time without opening or refreshing a new browser window.' , APP_TD), 'customize.php' ),
					'type' => 'text',
					'name' => '_blank',
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'Use the WordPress Theme Customizer to try out different design options and other Freelancer settings.', APP_TD ),
				),

				array(
					'title' => __( 'Theme Color', APP_TD ),
					'type' => 'select',
					'name' => 'color',
					'values' => hrb_get_color_choices(),
					'tip' => __( 'Choose the overall theme color.', APP_TD ),
				),

				array(
					'title' => __( 'Header Image', APP_TD ),
					'desc' => sprintf( __( 'Set Your Header Image in the <a href="%s">Header</a> settings.', APP_TD ),
						 'themes.php?page=custom-header' ),
					'type' => 'text',
					'name' => '_blank',
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'This is where you can upload/manage your logo that appears in your site\'s header along with settings to control the text below the logo.', APP_TD ),
				),
				array(
					'title' => __( 'Disable Admin Toolbar', APP_TD ),
					'desc' => __( 'Disable the admin toolbar to all non-admin users.', APP_TD ),
					'type' => 'checkbox',
					'name' => 'disable_admin_toolbar',
					'tip' => sprintf( __( 'If you uncheck this option, the toolbar will be displayed only to users with the <a href="%s">Show Toolbar when viewing site</a> option, checked.', APP_TD ), admin_url('profile.php') ),
				),
			),
		);

		$this->tab_sections['general']['social'] = array(
			'title' => __( 'Social', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'LinkedIn Profile ID', APP_TD ),
					'type' => 'text',
					'name' => 'linkedin_id',
					'tip' => APP_Social_Networks::get_tip('linkedin')
							. '<br/><br/>' . __( 'A social icon for this social network will be displayed on the top navigation bar.', APP_TD ),
				),
				array(
					'title' => __( 'Twitter ID', APP_TD ),
					'type' => 'text',
					'name' => 'twitter_id',
					'tip' => APP_Social_Networks::get_tip('twitter')
							. '<br/><br/>' . __( 'A social icon for this social network will be displayed on the top navigation bar.', APP_TD ),
				),
				array(
					'title' => __( 'Facebook Page ID', APP_TD ),
					'type' => 'text',
					'name' => 'facebook_id',
					'tip' => APP_Social_Networks::get_tip('facebook')
							. '<br/><br/>' . __( 'A social icon for this social network will be displayed on the top navigation bar.', APP_TD ),
				),
				array(
					'title' => __( 'Google Plus Page ID', APP_TD ),
					'type' => 'text',
					'name' => 'google_plus_id',
					'tip' => APP_Social_Networks::get_tip('google-plus')
							. '<br/><br/>' . __( 'A social icon for this social network will be displayed on the top navigation bar.', APP_TD ),
				),
			),
		);

		$this->tab_sections['general']['permalinks'] = array(
			'title' => __( 'Permalinks', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Manage', APP_TD ),
					'desc' => sprintf( __( 'Manage <a href="%s">HireBee Permalinks</a>.', APP_TD ), 'options-permalink.php' ),
					'type' => 'text',
					'name' => '_blank',
					'extra' => array(
						'style' => 'display: none;',
					),
					'tip' => __( 'Manage Freelancer\'s permalinks settings for jobs, job categories, job tags, dashboard pages, etc.', APP_TD )
				),
			),
		);

		$this->tab_sections['general']['integration'] = array(
			'title' => __( 'Integration', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Display ShareThis on Projects', APP_TD ),
					'type' => 'checkbox',
					'desc' => __( 'Yes', APP_TD ),
					'name' => 'listing_sharethis',
					'extra' => ( ! function_exists ( 'sharethis_button' ) ? array ( 'disabled' => 'disabled' ) : '' ),
					'tip' => sprintf( __( 'If you have the <a href="%1$s" target="_blank">ShareThis</a> plugin instaled, enabling this option will display ShareThis icons in the single project page.', APP_TD ) , 'http://wordpress.org/extend/plugins/share-this/' ),
				),
				array(
					'title' => __( 'Display ShareThis on Blog Posts', APP_TD ),
					'type' => 'checkbox',
					'desc' => __( 'Yes', APP_TD ),
					'name' => 'blog_post_sharethis',
					'extra' => ( ! function_exists ( 'sharethis_button' ) ? array ( 'disabled' => 'disabled' ) : '' ),
					'tip' => sprintf( __( 'If you have the <a href="%1$s" target="_blank">ShareThis</a> plugin instaled, enabling this option will display ShareThis icons on single posts.', APP_TD ) , 'http://wordpress.org/extend/plugins/share-this/' ),
				),
			),
		);

		$this->tab_sections['projects']['moderation'] = array(
			'title' => __( 'Moderation', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Moderate Projects', APP_TD ),
					'type' => 'checkbox',
					'name' => 'moderate_projects',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Do you want to moderate new projects before they are displayed live?', APP_TD ),
				),
			)
		);

		$this->tab_sections['projects']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Projects on Front Page', APP_TD ),
					'type' => 'text',
					'name' => 'projects_frontpage',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'How many projects do you want shown on your front page? Leave blank to hide projects from front page.', APP_TD ),
				),
				array(
					'title' => __( 'Projects Per Page', APP_TD ),
					'type' => 'text',
					'name' => 'projects_per_page',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'How many projects per page do you want shown?', APP_TD ),
				),
				array(
					'title' => __( 'Enable Clarification Section', APP_TD ),
					'type' => 'checkbox',
					'desc' => __( 'Yes', APP_TD ),
					'name' => 'projects_clarification',
					'tip' => __( 'Enabling this option allows freelancers to post public questions on project pages.', APP_TD ),
				),
			),
		);

		$this->tab_sections['projects']['general'] = array(
			'title' => __( 'Skills', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Number of Skills', APP_TD ),
					'name' => 'projects_allowed_skills',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'Number of selectable skills allowed. Leave empty to disable skills in projects.', APP_TD ),
				),
			),
		);

		$this->tab_sections['projects']['budget'] = array(
			'title' => __( 'Budget', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Currencies', APP_TD ),
					'name' => '_blank',
					'type'	=> 'custom',
					'render'=> array( $this, 'output_limit_currency_option' ),
					'tip' => sprintf( __( 'Select allowed currencies for budget prices. If left blank, all currencies in <a href="%s">Payments Settings</a> will be selectable.', APP_TD ), 'admin.php?page=app-payments-settings'),
				),
				array(
					'title' => '',
					'type' => 'checkbox',
					'name' => 'restrict_user_currencies',
					'desc' => __( 'Also restrict user rates to these currencies.', APP_TD ),
					'tip' => __( 'Check this option to restrict user rates currencies to budget currencies. Leave unchecked to allow user rates on any currency.', APP_TD ),
				),
				array(
					'title' => __( 'Types', APP_TD ),
					'type' => 'select',
					'name' => 'budget_types',
					'tip' => __( 'Choose the budget types that should be selectable by employers when posting projects.', APP_TD ),
					'choices' => array(
						'fixed'	=> __( 'Fixed Price', APP_TD ),
						'hourly' => __( 'Per Hour', APP_TD ),
						''  => __( 'Fixed Price + Per Hour', APP_TD ),
					),
				),
			),
		);

		$this->tab_sections['projects']['location'] = array(
			'title' => __( 'Location', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Types', APP_TD ),
					'type' => 'select',
					'name' => 'location_types',
					'tip' => __( 'Choose the location types that should be selectable by employers when posting projects (<em>Remote</em> - remote work allowed; <em>Local</em> - employer must provide a location for the project).', APP_TD ),
					'choices' => array(
						'remote'	=> __( 'Remote', APP_TD ),
						'local' => __( 'Local', APP_TD ),
						''  => __( 'Remote + Local', APP_TD ),
					),
				),
			),
		);

		$this->tab_sections['projects']['attachments'] = array(
			'title' => __( 'Attachments', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'File Uploading', APP_TD ),
					'name' => 'attachments',
					'type' => 'checkbox',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Check this option to allow employers to upload files when posting projects.', APP_TD ),
				),
				array(
					'title' => __( 'File Limit', APP_TD ),
					'name' => 'attachments_limit',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'Number of files allowed.', APP_TD ),
				),
				array(
					'title' => __( 'File Types', APP_TD ),
					'name' => 'attachments_types',
					'type' => 'text',
					'desc' => __( 'Comma separated list of file types (e.g: pdf, doc).', APP_TD ),
					'tip' => __( 'The file types allowed. Leave empty to allow any file type.', APP_TD ),
				),
				array(
					'title' => __( 'File Size', APP_TD ),
					'name' => 'attachments_size',
					'type' => 'text',
					'extra' => array( 'size' => 4 ),
					'desc' => __( 'KB', APP_TD ),
					'tip' => __( 'The maximum file size allowed in kilobites (1 MB = 1024 kb).', APP_TD ),
				),
			)
		);

		$this->tab_sections['projects']['duration'] = array(
			'title' => __( 'Duration (Free Projects Only)', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Projects Duration', APP_TD ),
					'type' => 'text',
					'name' => 'project_duration',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'The projects duration to be applied to new projects.', APP_TD ),
				),
				array(
					'title' => __( 'Editable Duration', APP_TD ),
					'name' => 'project_duration_editable',
					'type' => 'checkbox',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Check this option to allow employers to specify their own job duration. The project duration set above will be used as the maximum limit.', APP_TD ),
				),
             ),
         );

		$this->tab_sections['projects']['pricing'] = array(
			'title' => __( 'Pricing', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Charge for Projects/Addons', APP_TD ),
					'name' => 'project_charge',
					'type' => 'checkbox',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => sprintf( __( 'Do you want to charge for posting and/or featuring a project on your site? You can manage your <a href="%s">Payments Settings</a> in the Payments Menu.', APP_TD ), 'admin.php?page=app-payments-settings'),
				),
				array(
					'title' => __( 'Project Plans', APP_TD ),
					'name' => '_blank',
					'type' => '',
					'desc' => sprintf( __( 'Set your <a href="%s">Project Plans</a> in the Payments Menu.', APP_TD ), 'edit.php?post_type='.HRB_PRICE_PLAN_PTYPE ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'Manage your Project Plans, which are packages of pricing and feature options that are offered.', APP_TD ),
				),
				array(
					'title' => __( 'Payments Settings', APP_TD ),
					'name' => '_blank',
					'type' => '',
					'desc' => sprintf( __( 'Set your default <a href="%s">Featured Pricing</a> and Payment Gateway settings in the Payments Menu.', APP_TD ), 'admin.php?page=app-payments-settings#featured-pricing' ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'Manage default Payments Settings including featured pricing and duration, enable/disable available payment gateways, and manage individual payment gateway\'s settings.', APP_TD ),
				),
			),
		);

		$this->tab_sections['users']['appearance'] = array(
			'title' => __( 'Appearance', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Users on Front Page', APP_TD ),
					'type' => 'text',
					'name' => 'users_frontpage',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'How many freelancers do you want shown on your front page? Leave blank to hide freelancers from front page.', APP_TD ),
				),
				array(
					'title' => __( 'Users Per Page', APP_TD ),
					'type' => 'text',
					'name' => 'users_per_page',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'How many freelancers per page do you want shown?', APP_TD ),
				),
			)
		);

		$this->tab_sections['users']['avatar'] = array(
			'title' => __( 'Avatar', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Avatar Upload', APP_TD ),
					'type' => 'checkbox',
					'name' => 'avatar_upload',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Enable this option to allow users to upload custom avatars.', APP_TD ),
				),
			),
		);

		$this->tab_sections['users']['location'] = array(
			'title' => __( 'Location', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Remote Work Only', APP_TD ),
					'type' => 'checkbox',
					'name' => 'local_users',
					'desc' => __( 'Check this option to restrict your site to remote working. The location field will not be available on user profiles or when submitting projects.', APP_TD ),
					'tip' => __( 'Enable this option if your site is targeted to remote working. Otherwise, leave unchecked and the location field will be available in the users profile and when submitting projects.', APP_TD ),
				),
			),
		);

		$this->tab_sections['proposals']['visibility'] = array(
			'title' => __( 'Visibility', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Hide Quotes', APP_TD ),
					'name' => 'proposals_quotes_hide',
					'type' => 'checkbox',
					'desc' => __( 'Check to hide proposals quotes (amount/message) from other users. Only project authors will be able to see the full list of quotes for their projects.', APP_TD ),
				),
			),
		);

		$this->tab_sections['proposals']['credits'] = array(
			'title' => __( 'Credits Usage', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Free Credits', APP_TD ),
					'name' => 'credits_given',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'desc' => __( 'Set how many credits are given to each registered user (0 = None)', APP_TD ),
				),
				array(
					'title' => __( 'Place Proposal', APP_TD ),
					'name' => 'credits_apply',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'desc' => __( 'Set how many credits are needed to place a proposal (0 = Free)', APP_TD ),
				),
				array(
					'title' => __( 'Edit Proposal', APP_TD ),
					'name' => 'credits_apply_edit',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'desc' => __( 'Set how many credits are needed to edit a proposal (0 = Free)', APP_TD ),
				),
				array(
					'title' => __( 'Feature Proposal', APP_TD ),
					'name' => 'credits_feature',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'desc' => __( 'Set how many credits are needed to feature a proposal (0 = Free)', APP_TD ),
				),
				array(
					'title' => __( 'Free Monthly Credits', APP_TD ),
					'name' => 'credits_offer',
					'type' => 'text',
					'extra' => array( 'size' => 2 ),
					'desc' => __( 'Set how many credits are offered to each user on the 1st day of each month ( 0 = No Offers )', APP_TD ),
				),
			),
		);

		$this->tab_sections['proposals']['pricing'] = array(
			'title' => __( 'Pricing', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Credit Plans', APP_TD ),
					'name' => '_blank',
					'type' => '',
					'desc' => sprintf( __( 'Set your <a href="%s">Credit Plans</a> in the Payments Menu.', APP_TD ), 'edit.php?post_type='.HRB_PROPOSAL_PLAN_PTYPE ),
					'extra' => array(
						'style' => 'display: none;'
					),
					'tip' => __( 'Manage your Credits Plans which are packages of bundled credits.', APP_TD ),
				),
			),
		);

		$this->tab_sections['categories']['category_menu_options'] = array(
			'title' => __( 'Categories Menu Item Options', APP_TD ),
			'fields' => $this->categories_options( 'categories_menu' ),
		);

		$this->tab_sections['categories']['category_dir_options'] = array(
			'title' => __( 'Categories Page Options', APP_TD ),
			'fields' => $this->categories_options( 'categories_dir' )
		);

		$this->tab_sections['notifications']['notification'] = array(
			'title' => __( 'Notifications', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'New Projects', APP_TD ),
					'type' => 'checkbox',
					'name' => 'notify_new_projects',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Notify admins when new projects are posted', APP_TD ),
				),
				array(
					'title' => __( 'New Proposals', APP_TD ),
					'type' => 'checkbox',
					'name' => 'notify_new_proposals',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Notify admins when new proposals are posted', APP_TD ),
				),
				array(
					'title' => __( 'New Reviews', APP_TD ),
					'type' => 'checkbox',
					'name' => 'notify_new_reviews',
					'desc' => __( 'Yes', APP_TD ),
					'tip' => __( 'Notify admins when new reviews are posted', APP_TD ),
				),
			)
		);

		$this->tab_sections['geolocation']['language'] = array(
			'title' => __( 'Language', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Language', APP_TD ),
					'desc' => sprintf( __( 'Find your two-letter language code <a href="%s" target="_blank">here</a>.', APP_TD ), 'https://spreadsheets.google.com/pub?key=p9pdwsai2hDMsLkXsoM05KQ&gid=1' ),
					'type' => 'text',
					'name' => 'geo_language',
					'extra' => array( 'size' => 2 ),
					'tip' => __( 'The language in which to return geolocation results. If language is not supplied, the geocoder will attempt to use the native language of the domain from which the request is sent wherever possible.', APP_TD )
				),
			),
		);

		$this->tab_sections['geolocation']['projects'] = array(
			'title' => __( 'Projects', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Country Biasing', APP_TD ),
					'type' => 'text',
					'name' => 'project_geo_country',
					'extra' => array( 'size' => 2 ),
					'desc' => sprintf( __( 'Restrict geocomplete locations to a specific country code. Find your two-letter ccTLD country code <a href="%s" target="_blank">here</a>.', APP_TD ), 'http://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Country_code_top-level_domains' ),
					'tip' => __( "Only locations within the specified country will be retrieved. Leave blank to allow locations from any country.", APP_TD )
				),
				array(
					'title' => __( 'Location Types', APP_TD ),
					'type' => 'select',
					'name' => 'project_geo_type',
					'choices' => array(
						'' => __( 'All', APP_TD ) ,
						'geocode' => __( 'Geocode', APP_TD ) ,
						'cities' => __( 'Cities', APP_TD ),
						'regions' => __( 'Regions', APP_TD ),
					),
					'desc' => sprintf( __( 'You may restrict location results in the geocomplete request to be of a certain type. Geolocation uses <a href="%s">Google Places Types</a> to restrict results.', APP_TD ), 'https://developers.google.com/places/documentation/supported_types?csw=1#table3' ),
					'tip' => sprintf( __( 'You can read more about each type <a href="%s">here</a>. If no restrictions are specified, the geolocation will retrieve all location types.', APP_TD ), 'https://developers.google.com/places/documentation/supported_types?csw=1#table3' ),
				),
				array(
					'title' => __( 'Refine Search *', APP_TD ),
					'type' => 'select',
					'name' => 'project_refine_search',
					'choices' => array(
						'country' => __( 'Country', APP_TD ) ,
						'location' => __( 'Location', APP_TD ),
						'postal_code' => __( 'Postal Code', APP_TD ),
					),
					'desc' => __( 'Select the most appropriate filter for the location refine search considering the location type you\'ve chosen above.', APP_TD ),
					'tip' => __( 'Choose how users should filter locations when searching/browsing projects. Note that postal codes might not always be available on all locations. In these cases, the typed location will be displayed, instead.', APP_TD ),
				),
			),
		);

		$this->tab_sections['geolocation']['users'] = array(
			'title' => __( 'Users', APP_TD ),
			'fields' => array(
				array(
					'title' => __( 'Country Biasing', APP_TD ),
					'type' => 'text',
					'name' => 'user_geo_country',
					'extra' => array( 'size' => 2 ),
					'desc' => sprintf( __( 'Restrict geocomplete locations to a specific country code. Find your two-letter ccTLD country code <a href="%s" target="_blank">here</a>.', APP_TD ), 'http://en.wikipedia.org/wiki/List_of_Internet_top-level_domains#Country_code_top-level_domains' ),
					'tip' => __( "Only locations within the specified country will be retrieved. Leave blank to allow locations from any country.", APP_TD )
				),
				array(
					'title' => __( 'Location Types', APP_TD ),
					'type' => 'select',
					'name' => 'user_geo_type',
					'choices' => array(
						'' => __('All', APP_TD ) ,
						'geocode' => __( 'Geocode', APP_TD ) ,
						'cities' => __( 'Cities', APP_TD ),
						'regions' => __( 'Regions', APP_TD ),
					),
					'desc' => sprintf( __( 'You may restrict location results in the geocomplete request to be of a certain type. Geolocation uses <a href="%s">Google Places Types</a> to restrict results.', APP_TD ), 'https://developers.google.com/places/documentation/supported_types?csw=1#table3' ),
					'tip' => sprintf( __( 'You can read more about each type <a href="%s">here</a>. If no restrictions are specified, the geolocation will retrieve all location types.', APP_TD ), 'https://developers.google.com/places/documentation/supported_types?csw=1#table3' ),
				),
				array(
					'title' => __( 'Refine Search *', APP_TD ),
					'type' => 'select',
					'name' => 'user_refine_search',
					'choices' => array(
						'country' => __( 'Country', APP_TD ) ,
						'location' => __( 'Location', APP_TD ),
						'postal_code' => __( 'Postal Code', APP_TD ),
					),
					'desc' => __( 'Select the most appropriate filter for the location refine search considering the location type you\'ve chosen above.', APP_TD ),
					'tip' => __( 'Choose how users should filter locations when searching/browsing projects. Note that postal codes might not always be available on all locations. In these cases, the typed location will be displayed, instead.', APP_TD ),
				),
			),
		);

		$this->tab_sections['geolocation']['notes'] = array(
			'title' => '',
			'fields' => array(
				array(
					'title' => __( '* Notes', APP_TD ),
					'type' => 'text',
					'name' => '_blank',
					'extra' => array(
						'style' => 'display: none;'
					),
					'desc' => __( 'Please note that geolocation results may not always retrieve all the meta data for a location, like the postal code or some administrative area levels. Since this meta '
							. 'data is used to display the geolocation refine filters, HireBee will try to use the most relevant data available or default to the typed location if none is available.', APP_TD ),
				),
			),
		);
	}

	private function categories_options( $prefix ) {
		$fields = array(
			array(
				'title' => __( 'Show Category Count', APP_TD ),
				'type' => 'checkbox',
				'name' => array( $prefix, 'count' ),
				'desc' => __( 'Yes', APP_TD ),
				'tip' => __( 'Display the quantity of posts in that category next to the category name?', APP_TD ),
			),
			array(
				'title' => __( 'Hide Empty Sub-Categories', APP_TD ),
				'type' => 'checkbox',
				'name' => array( $prefix, 'hide_empty' ),
				'desc' => __( 'Yes', APP_TD ),
				'tip' => __( 'If a category has no projects, should it be hidden?', APP_TD ),
			),
			array(
				'title' => __( 'Category Depth', APP_TD ),
				'type' => 'select',
				'name' => array( $prefix, 'depth' ),
				'values' => array(
					'999' => __( 'Show All', APP_TD ),
					'0' => '0',
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
					'9' => '9',
					'10' => '10',
				),
				'tip' => __( 'How many levels deep should the category tree traverse?', APP_TD ),
			),
			array(
				'title' => __( 'Number of Sub-Categories', APP_TD ),
				'type' => 'select',
				'name' => array( $prefix, 'sub_num' ),
				'values' => array(
					'999' => __( 'Show All', APP_TD ),
					'0' => '0',
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
					'7' => '7',
					'8' => '8',
					'9' => '9',
					'10' => '10',
				),
				'tip' => __( 'How many sub-categories of each parent category should be shown?', APP_TD ),
			),
		);

		if ( 'categories_menu' == $prefix ) {
			array_unshift( $fields, array(
					'title' => __( 'Show Categories Menu', APP_TD ),
					'type' => 'select',
					'name' => array( $prefix, 'show' ),
					'tip' => __( 'Display the categories list menu? If disabled, all related options will be ignored.', APP_TD ),
					'choices' => array(
						'always' => __( 'Always Visible', APP_TD ),
						'click' => __( 'Visible on Click', APP_TD ),
						'' => __( 'Hide', APP_TD ),
					),
			) );
		}
		return $fields;
	}

	/**
	 * Output the currency multiple select option.
	 */
	function output_limit_currency_option() {
		global $hrb_options;

		$args = array(
			'id' => 'allowed_currencies',
			'name' => 'allowed_currencies[]',
			'placeholder' => __( 'Choose one or multiple currencies. Leave empty to allow all.', APP_TD ),
			'multiple' => 'multiple',
		);

		$opts = '';
		foreach( APP_Currencies::get_currency_string_array() as $key => $value ) {
			$atts = array( 'value' => $key, 'title' => $value );
			if ( in_array( $key, (array) $hrb_options->allowed_currencies ) ) {
				$atts['selected'] = 'selected';
			}
			$opts .= html( 'option', $atts, $value );
		}

		return html( 'select', $args, $opts );
	}

	function init_integrated_options() {
		// display additional section on the permalinks page
		$this->permalink_sections();
	}

	function permalink_sections() {

		$option_page = 'permalink';
		$new_section = 'hrb_options';	// store permalink options on global 'hrb_options'

		$this->permalink_sections = array(
			'projects' 		=> __( 'Projects Custom Post Type & Taxonomy URLs', APP_TD ),
			'actions' 		=> __( 'Projects Other URLs', APP_TD ),
			'users' 	=> __( 'Freelancers Custom Post Type & Taxonomy URLs', APP_TD ),
			'dashboard' 	=> __( 'Dashboard URLs', APP_TD ),
			'profile'		=> __( 'Profile URL', APP_TD ),
		);

		$this->permalink_options['projects'] = array (
			'project_permalink' 	  		=> __('Projects Base URL',APP_TD),
			'project_cat_permalink'	  		=> __('Project Categories Base URL',APP_TD),
			'project_skill_permalink'	  	=> __('Project Skills Base URL',APP_TD),
			'project_tag_permalink'   		=> __('Project Tags Base URL',APP_TD),
		);

		$this->permalink_options['users'] = array (
			'user_permalink' 	  		=> __('Users Base URL',APP_TD),
		);

		$this->permalink_options['actions'] = array (
			'starred_project_permalink'  	=> __('Starred Projects Base URL',APP_TD),
			'edit_project_permalink'  		=> __('Edit Project Base URL',APP_TD),
			'renew_project_permalink'  		=> __('Renew Project Base URL',APP_TD),
			'purchase_project_permalink'	=> __('Purchase Project Base URL',APP_TD),
			'review_user_permalink'			=> __('Review User Base URL',APP_TD),

			'edit_proposal_permalink'  		=> __('Edit Proposal Base URL',APP_TD),
		);

		$this->permalink_options['dashboard'] = array (
			'dashboard_permalink'  		 	=> __('Dashboard Base URL',APP_TD),
			'dashboard_projects_permalink' 	=> __('Dashboard Projects Base URL',APP_TD),
			'dashboard_reviews_permalink' 	=> __('Dashboard Reviews Base URL',APP_TD),
			'dashboard_proposals_permalink' => __('Dashboard Proposals Base URL',APP_TD),
			'dashboard_payments_permalink'	=> __('Dashboard Payments Base URL',APP_TD),
			'dashboard_workspace_permalink' => __('Dashboard Workspace Base URL',APP_TD),
			'dashboard_notifications_permalink' => __('Dashboard notifications Base URL',APP_TD),
		);


		$this->permalink_options['profile'] = array (
			'profile_permalink'  		 	=> __('Profile Base URL',APP_TD),
		);

		register_setting(
			$option_page,
			$new_section,
			array( $this, 'permalink_options_validate')
		);

		foreach ( $this->permalink_sections as $section => $title ) {

			add_settings_section(
				$section,
				$title,
				'__return_false',
				$option_page
			);

			foreach ( $this->permalink_options[ $section ] as $id => $title ) {

				add_settings_field(
					$new_section.'_'.$id,
					$title,
					array( $this, 'permalink_section_add_option'), 	// callback to output the new options
					$option_page,						   			// options page
					$section,						   				// section
					array( 'id' => $id )							// callback args [ database option, option id ]
				);

			}

		}
	}

	function permalink_section_add_option( $option ) {
		global $hrb_options;

		echo scbForms::input( array(
			'type'  => 'text',
			'name'  => 'hrb_options['.$option['id'].']',
			'extra' => array( 'size' => 53 ),
			'value'	=> $hrb_options->$option['id']
		) );

	}

	// validate/sanitize permalinks
	function permalink_options_validate( $input ) {
		global $hrb_options;

		$error_html_id = '';

		foreach ( $this->permalink_sections as $section => $title ) {

			foreach ( $this->permalink_options[$section] as $key => $value ) {

				if ( empty($input[$key]) ) {
					$error_html_id = $key;
					// set option to previous value
					$input[$key] = $hrb_options->$key;
				} else {
					if ( !is_array($input[$key]) ) $input[$key] = trim($input[$key]);
					$input[$key] = stripslashes_deep($input[$key]);
				}

			}
		}

		if ( $error_html_id ) {

			add_settings_error(
				'hrb_options',
				$error_html_id,
				__('Freelancer custom post types and taxonomy URLs cannot be empty. Empty options will default to previous value.', APP_TD),
				'error'
			);

		}
		return $input;
	}

}
