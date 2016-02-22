<?php
/**
 * Theme functions file
 *
 * DO NOT MODIFY THIS FILE!
 * Make a child theme instead: http://codex.wordpress.org/Child_Themes
 *
 * @package HireBee
 * @author AppThemes
 */

global $hrb_options;


// Versioning
define( 'APP_TD', 'hirebee' );
define( 'HRB_VERSION', '1.3.1' );


// Custom Roles
define( 'HRB_ROLE_FREELANCER', 'freelancer' );
define( 'HRB_ROLE_EMPLOYER', 'employer' );
define( 'HRB_ROLE_BOTH', 'employer_freelancer' );

// Post Types
define( 'HRB_PROJECTS_PTYPE', 'project' );
define( 'HRB_WORKSPACE_PTYPE', 'workspace' );
define( 'HRB_PRICE_PLAN_PTYPE', 'pricing-plan' );
define( 'HRB_PROPOSAL_PLAN_PTYPE', 'credit-plan' );

// "User Types"
define( 'HRB_FREELANCER_UTYPE', 'freelancer' );

// Comment Types
define( 'HRB_PROPOSAL_CTYPE', 'proposal' );
define( 'HRB_CLARIFICATION_CTYPE', 'clarification' );

// Taxonomies
define( 'HRB_PROJECTS_CATEGORY', 'project_category' );
define( 'HRB_PROJECTS_TAG', 'project_tag' );
define( 'HRB_PROJECTS_SKILLS', 'project_skill' );

// P2P Connection Names
define( 'HRB_P2P_CANDIDATES', 'project_candidates' );
define( 'HRB_P2P_WORKSPACES', 'project_workspaces' );
define( 'HRB_P2P_PARTICIPANTS', 'project_participant' );
define( 'HRB_P2P_PROJECTS_FAVORITES', 'project_favorite' );

// Attachment Types
define( 'HRB_ATTACHMENT_FILE', 'file' );
define( 'HRB_ATTACHMENT_GALLERY', 'gallery' );

// Posts Statuses
define( 'HRB_PROJECT_STATUS_TERMS', 'terms' );
define( 'HRB_PROJECT_STATUS_CANCELED_TERMS', 'canceled_terms' );
define( 'HRB_PROJECT_STATUS_WORKING', 'working' );
define( 'HRB_PROJECT_STATUS_CANCELED', 'canceled' );
define( 'HRB_PROJECT_STATUS_CLOSED_COMPLETED', 'closed_complete' );
define( 'HRB_PROJECT_STATUS_CLOSED_INCOMPLETE', 'closed_incomplete' );
define( 'HRB_PROJECT_STATUS_EXPIRED', 'expired' );
define( 'HRB_PROJECT_STATUS_WAITING_FUNDS', 'waiting_funds' );

// Post Meta Statuses :: Projects
define( 'HRB_PROJECT_META_STATUS_ARCHIVED', 'archived' );

// Post Meta Statuses :: Workspaces
define( 'HRB_WORK_STATUS_REVIEW', 'review' );
define( 'HRB_WORK_STATUS_WAITING', 'waiting' );
define( 'HRB_WORK_STATUS_WORKING', 'working' );
define( 'HRB_WORK_STATUS_COMPLETED', 'completed' );
define( 'HRB_WORK_STATUS_INCOMPLETE', 'incomplete' );

// Comment Meta Statuses :: Proposals
define( 'HRB_PROPOSAL_STATUS_ACTIVE', 'active' );
define( 'HRB_PROPOSAL_STATUS_PENDING', 'pending' );
define( 'HRB_PROPOSAL_STATUS_SELECTED', 'selected' );
define( 'HRB_PROPOSAL_STATUS_ACCEPTED', 'accepted' );
define( 'HRB_PROPOSAL_STATUS_DECLINED', 'declined' );
define( 'HRB_PROPOSAL_STATUS_CANCELED', 'canceled' );

// Post Meta States :: Agreement
define( 'HRB_TERMS_SELECT', 'selected' );
define( 'HRB_TERMS_PROPOSE', 'propose' );
define( 'HRB_TERMS_ACCEPT', 'accepted' );
define( 'HRB_TERMS_DECLINE', 'declined' );
define( 'HRB_TERMS_CANCEL', 'canceled' );
define( 'HRB_TERMS_DECIDING', 'deciding' );
define( 'HRB_TERMS_UNASSIGNED', 'not_assigned' );

// Addons Meta Keys
define( 'HRB_ITEM_REGULAR', '_hrb_regular' );
define( 'HRB_ITEM_FEATURED_HOME', '_hrb_featured-home' );
define( 'HRB_ITEM_FEATURED_CAT', '_hrb_featured-cat' );
define( 'HRB_ITEM_URGENT', '_hrb_urgent' );


### File Dependencies

require dirname(__FILE__) . '/framework/load.php';								// Framework

if ( ! is_admin() ) {
	require dirname( __FILE__ ) . '/framework/admin/class-tabs-page.php';		// Framework :: Settings Tabs
}

// Sub-Modules
require dirname( __FILE__ ) . '/framework/admin/class-user-meta-box.php';		// User Profile
require dirname( __FILE__ ) . '/includes/payments/load.php';					// Payments
require dirname( __FILE__ ) . '/includes/reviews/load.php';						// Reviews
require dirname( __FILE__ ) . '/includes/bidding/load.php';						// Bidding
require dirname( __FILE__ ) . '/includes/notifications/load.php';				// Notifications
require dirname( __FILE__ ) . '/includes/checkout/form-progress/load.php';		// Form Progress
require dirname( __FILE__ ) . '/includes/widgets/load.php';						// Widgets
require dirname( __FILE__ ) . '/includes/geo/load.php';							// Geolocation
require dirname( __FILE__ ) . '/includes/custom-forms/form-builder.php';		// Custom Forms
require dirname( __FILE__ ) . '/includes/disputes/load.php';

// Main Files
require dirname( __FILE__ ) . '/includes/core.php';
require dirname( __FILE__ ) . '/includes/capabilities.php';
require dirname( __FILE__ ) . '/includes/setup-theme.php';
require dirname( __FILE__ ) . '/includes/customizer.php';
require dirname( __FILE__ ) . '/includes/users.php';
require dirname( __FILE__ ) . '/includes/projects.php';
require dirname( __FILE__ ) . '/includes/proposals.php';
require dirname( __FILE__ ) . '/includes/agreement.php';
require dirname( __FILE__ ) . '/includes/workspace.php';
require dirname( __FILE__ ) . '/includes/disputes.php';
require dirname( __FILE__ ) . '/includes/payments.php';
require dirname( __FILE__ ) . '/includes/credits.php';
require dirname( __FILE__ ) . '/includes/addons.php';
require dirname( __FILE__ ) . '/includes/loops.php';
require dirname( __FILE__ ) . '/includes/dashboard.php';
require dirname( __FILE__ ) . '/includes/custom-forms.php';
require dirname( __FILE__ ) . '/includes/reviews.php';
require dirname( __FILE__ ) . '/includes/status.php';
require dirname( __FILE__ ) . '/includes/activate.php';
require dirname( __FILE__ ) . '/includes/favorites.php';
require dirname( __FILE__ ) . '/includes/notifications.php';
require dirname( __FILE__ ) . '/includes/widgets.php';
require dirname( __FILE__ ) . '/includes/media.php';
require dirname( __FILE__ ) . '/includes/categories.php';
require dirname( __FILE__ ) . '/includes/options.php';
require dirname( __FILE__ ) . '/includes/helper.php';
require dirname( __FILE__ ) . '/includes/utils.php';

// Template Tags
require dirname( __FILE__ ) . '/includes/template-tags-site.php';
require dirname( __FILE__ ) . '/includes/template-tags-projects.php';
require dirname( __FILE__ ) . '/includes/template-tags-user.php';
require dirname( __FILE__ ) . '/includes/template-tags-proposals.php';
require dirname( __FILE__ ) . '/includes/template-tags-orders.php';

// Sub-Modules Registration via 'add_theme_support()'
require dirname( __FILE__ ) . '/includes/theme-support.php';

// Views
require dirname( __FILE__ ) . '/includes/views.php';
require dirname( __FILE__ ) . '/includes/views-purchase.php';
require dirname( __FILE__ ) . '/includes/views-projects.php';
require dirname( __FILE__ ) . '/includes/views-proposals.php';
require dirname( __FILE__ ) . '/includes/views-users.php';
require dirname( __FILE__ ) . '/includes/views-dashboard.php';

// Form Handling
require dirname( __FILE__ ) . '/includes/forms-registration.php';
require dirname( __FILE__ ) . '/includes/forms-projects.php';
require dirname( __FILE__ ) . '/includes/forms-proposals.php';
require dirname( __FILE__ ) . '/includes/forms-dashboard.php';
require dirname( __FILE__ ) . '/includes/forms-purchase.php';

// escrow
require dirname( __FILE__ ) . '/includes/escrow.php';

// Admin
if ( is_admin() ) {

	require dirname( __FILE__ ) . '/includes/admin/install.php';
	require dirname( __FILE__ ) . '/includes/admin/dashboard.php';
	require dirname( __FILE__ ) . '/includes/admin/settings.php';
	require dirname( __FILE__ ) . '/includes/admin/admin.php';
	require dirname( __FILE__ ) . '/includes/admin/users.php';
	require dirname( __FILE__ ) . '/includes/admin/project-plans.php';
	require dirname( __FILE__ ) . '/includes/admin/proposal-plans.php';
	require dirname( __FILE__ ) . '/includes/admin/project-single.php';
	require dirname( __FILE__ ) . '/includes/admin/project-list.php';
    require dirname( __FILE__ ) . '/includes/admin/payments-list.php';
	require dirname( __FILE__ ) . '/includes/admin/addons.php';

	// Init Admin Views/Metaboxes
	$hrb_settings_admin = new HRB_Settings_Admin( $hrb_options );
	add_action( 'admin_init', array( $hrb_settings_admin, 'init_integrated_options' ), 10 );


	### Classes Instantiation

	// Admin Dashboard & System Info
	new HRB_Dashboard;
	new APP_System_Info( array( 'admin_action_priority' => 99 ) );

	// Meta Boxes :: Pricing Plans
	new HRB_Pricing_General_Box;
	new HRB_Pricing_Addon_Box;

	// Meta Boxes :: Proposal
	new HRB_Proposal_General_Box;

	// Meta Boxes :: Projects
	//new HRB_Project_Attachments;
	new HRB_Project_Media( '_app_media', __( 'Attachments', APP_TD ), HRB_PROJECTS_PTYPE, 'normal', 'high' );
	new HRB_Project_Budget_Meta;
	new HRB_Project_Timeline_Meta;
	new HRB_Project_Location_Meta;
	new HRB_Project_Promotional_Meta;
	new HRB_Project_Publish_Moderation;
	new HRB_Project_Author_Meta;

}

### Classes Instantiation

// Meta Boxes :: User Profile
new HRB_Edit_Profile_Social_Meta_Box;
new HRB_Edit_Profile_Extra_Meta_Box;
new HRB_Edit_Profile_Account_Meta_Box;

// Views :: Login/Registration
new HRB_Login_Registration;

// Views :: Static Pages
new HRB_Home_Archive;
new HRB_Blog_Archive;
new HRB_Blog_Single;
new HRB_How_Works_Page;
new HRB_Site_Terms_Page;

// Views :: User Profile
new APP_User_Profile;
new HRB_User_Profile;
new HRB_Edit_Profile;

// Views :: Users
new HRB_Users_Listings;
new HRB_Users_Archive;
new HRB_Users_Search;

// Views :: Projects
new HRB_Project_Single;
new HRB_Project_Archive;
new HRB_Project_Search;
new HRB_Project_Saved_Filter;
new HRB_Project_Taxonomy;
new HRB_Project_Categories;
new HRB_Project_Create;
new HRB_Project_Edit;
new HRB_Project_Relist;

// Views :: Projects :: Form Handling/Processing
new HRB_Project_Form_Create;
new HRB_Project_Form_Edit;
new HRB_Project_Form_Relist;
new HRB_Project_Form_Preview;
new HRB_Project_Form_Submit_Free;

// Views :: Proposals
new HRB_Proposal_Create;
new HRB_Proposal_Edit;

// Views :: Proposals :: Form Handling/Processing
new HRB_Proposal_Form_Edit;
new HRB_Proposal_Form_Create;

// Views :: Workspace
new HRB_Workspace_Form_Review;
new HRB_Workspace_Form_Manage;

if ( current_theme_supports( 'app-disputes' ) ) {
	new HRB_Workspace_Form_Dispute;
}

// Views :: Purchases
new HRB_Credits_Purchase;
new HRB_Select_Credits_Plan_New;
new HRB_Project_Form_Relist_Select_Plan;

// Views :: Escrow
new HRB_Escrow_Transfer;

// Views :: Purchases :: Form Handling/Processing
new HRB_Order;
new HRB_Select_Plan_New;
new HRB_Gateway_Select;
new HRB_Gateway_Process;
new HRB_Order_Summary;

// Views :: Dashboard
new HRB_User_Dashboard_Secure;
new HRB_User_Dashboard_Single_Project;
new HRB_User_Dashboard;
new HRB_User_Dashboard_Main;
new HRB_User_Dashboard_Notifications;
new HRB_User_Dashboard_Projects;
new HRB_User_Dashboard_Proposals;
new HRB_User_Dashboard_Reviews;
new HRB_User_Dashboard_Payments;
new HRB_User_Dashboard_Agreement;
new HRB_User_Dashboard_Workspace;
new HRB_User_Dashboard_Workspace_Review;

if ( current_theme_supports( 'app-disputes' ) ) {
	new HRB_User_Dashboard_Workspace_Dispute;
}

// Views :: Dashboard :: Form Handling/Processing
new HRB_User_Dashboard_Form_Notifications;
new HRB_User_Dashboard_Form_Projects;
new HRB_User_Dashboard_Form_Proposals;
new HRB_User_Dashboard_Form_Payments;
new HRB_User_Dashboard_Form_Agreement;

APP_Mail_From::init();


### Init

appthemes_init();
