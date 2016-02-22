<?php
/**
 * Widgets related stuff.
 */

add_action( 'widgets_init', '_hrb_register_widgets' );

class HRB_APP_Widget_Recent_Posts extends APP_Widget_Recent_Posts {

	protected function form_fields() {
		$fields = parent::form_fields();

		foreach( $fields as &$field ) {

			if ( 'show_rating' == $field['name'] ) {
				$field['desc']	= __( 'Display Rating (requires "StarStruck" plugin for non-project types):', APP_TD );
			}
		}

		return $fields;
	}
}

/**
 * Registers all the custom Widgets.
 */
function _hrb_register_widgets() {

	new APP_Widget_Facebook( array(
			'name' => __( 'HireBee :: Facebook Widget', APP_TD ),
		)
	);

	new APP_Widget_125_Ads( array(
			'name' => __( 'HireBee :: 125x125 Ads', APP_TD ),
		)
	);

	new HRB_APP_Widget_Recent_Posts( array(
			'name' => __( 'HireBee :: Recent Projects', APP_TD ),
			'defaults' => array(
				'title' => __( 'Recent Projects', APP_TD ),
				'post_type' => HRB_PROJECTS_PTYPE,
				'template' => 'widget-recent-posts.php',
			),
		)
	);

	new APP_Widget_Social_Connect( array(
			'name' => __( 'HireBee :: Social Connect', APP_TD ),
			'defaults' => array(
				'social_networks' => array(
					'wordpress',
				),
				'exclude_mode' => true,
			)
		)
	);

	register_widget('HRB_Widget_Create_project_Button');
	register_widget('HRB_Widget_Saved_Filters');

	unregister_widget('WP_Widget_Search');
	unregister_widget('WP_Widget_Meta');
}

/**
 * Widget class for the 'Post a Project' button.
 */
class HRB_Widget_Create_project_Button extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'description' => __( 'A button for creating a new project', APP_TD )
		);

		parent::__construct( 'create_project_button', __( 'HireBee :: Post Project Button', APP_TD ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

        if ( is_user_logged_in() && ! current_user_can('edit_projects') ) {
            return;
        }

		$url = get_the_hrb_project_create_url();

		if ( is_tax( HRB_PROJECTS_CATEGORY ) ) {
			$url = add_query_arg( HRB_PROJECTS_CATEGORY, get_queried_object_id(), $url );
		}

		echo $before_widget;

		$args = array(
			'href' => $url,
			'class' => 'button expand',
		);
		echo html( 'a', $args, __( 'Nueva Consulta', APP_TD ) );

		echo $after_widget;
	}
}

/**
 * Widget class for the 'Saved Filters'.
 */
class HRB_Widget_Saved_Filters extends APP_Widget {

	public function __construct( $args = array() ) {

		add_filter( 'widget_title', array( $this, 'hide_from_user_listings' ), 10, 3 );

		$default_args = array(
			'id_base' => 'hrb_saved_filters',
			'name' => __( 'HireBee :: Saved Filters', APP_TD ),
			'defaults' => array(
				'title' => __( 'Saved Filters', APP_TD ),
			),
			'widget_ops' => array(
				'description' => __( 'Enables users to save and re-use searches and filters. Works only on a filter/search sidebar.', APP_TD ),
				'classname' => 'widget-saved-filters'
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		if ( ! is_hrb_project_saveable_filter() || ! is_user_logged_in() ) {
			return;
		}

		$saved_filters = hrb_get_user_saved_filters();
?>

		<?php if ( ! empty( $saved_filters ) ): ?>

			<form method="post" action="#" class="custom" id="load-saved-filter-form">

				<?php wp_nonce_field( 'hrb-save-filter' ); ?>

				<input type="hidden" name="action" value="load-saved-filter" />

				<select style="display:none;" id="saved-filter-slug" name="saved-filter-slug" class="medium">

					<?php
						foreach( $saved_filters as $key => $saved_filter ) {

							parse_str( $_SERVER['QUERY_STRING'], $query_string );

							$selected = $key;

							foreach( $saved_filter['params'] as $param_key => $value ) {
								if ( 'action' != $param_key && ( ! isset( $query_string[ $param_key] ) || $value != $query_string[ $param_key ] ) ) {
									$selected = '';
									break;
								}
							}

							$args = array(
								'value' => $key,
								'selected' => ( $selected && $key == $selected )
							);
							echo html( 'option', $args, $saved_filter['name'] );
						}
					?>
				</select>

				<?php if ( ! empty( $selected ) ): ?>

					<a id="edit-saved-filter" class="edit-saved-filter button" href="#"><?php echo __( 'Edit', APP_TD ); ?></a> <a id='delete-saved-filter' class='delete-saved-filter button secondary'><?php echo __( 'Delete', APP_TD ); ?></a>

				<?php endif; ?>
			</form>

		<?php endif; ?>

		<?php if ( empty( $selected ) ): ?>

			<a href="#"  id="open-save-filter" class="open-save-filter button" data-reveal-id="save-filter-modal"><?php echo __( 'Save Search', APP_TD ); ?></a>

		<?php endif; ?>

<?php
	}

	protected function form_fields() {
		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
		);
	}

	// hide the widget title on user archive pages - saved filters only work with projects
	function hide_from_user_listings( $title, $instance = '', $id = '' ) {
		if ( $this->id_base == $id && ( is_hrb_users_archive() || ! is_user_logged_in() ) ) {
			$title = '';
		}
		return $title;
	}

}
