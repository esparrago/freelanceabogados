<?php
/**
 * Facebook like box widget
 *
 * @package Components\Widgets
 */
// facebook like box sidebar widget
class APP_Widget_Facebook extends APP_Widget {

	public function __construct( $args = array() ) {

		$default_args = array(
			'id_base' => 'appthemes_facebook',
			'name' => __( 'AppThemes Facebook Like Box', APP_TD ),
			'defaults' => array(
				'title' => __( 'Facebook Friends', APP_TD ),
				'fid' => '137589686255438',
				'connections' => '10',
				'width' => '310',
				'height' => '290'
			),
			'widget_ops' => array(
				'description' => __( 'This places a Facebook page Like Box in your sidebar to attract and gain Likes from visitors.', APP_TD ),
				'classname' => 'widget-facebook'
			),
			'control_options' => array(),

		);

		extract( $this->_array_merge_recursive( $default_args, $args ) );

		parent::__construct( $id_base, $name, $widget_ops, $control_options, $defaults );
	}

	public function content( $instance ) {
		$instance = array_merge( $this->defaults, (array) $instance );

		$title = $instance['title'];
		$fid = $instance['fid'];
		$connections = $instance['connections'];
		$width = $instance['width'];
		$height = $instance['height'];

		$likebox_url = add_query_arg( array(
			'id'     => urlencode( $fid ),
			'locale' => get_locale(),
			'stream' => 'false',
			'header' => 'true',
			'height' => $height,
			'width'  => $width,
			'connections' => $connections,
		), '//www.facebook.com/plugins/likebox.php' );

		?>
			<div class="pad10"></div>
			<iframe src="<?php echo esc_attr( $likebox_url ); ?>" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:<?php echo esc_attr( $width ); ?>px; height:<?php echo esc_attr( $height ); ?>px;" allowTransparency="true"></iframe>
		<?php
	}

	protected function form_fields() {
		return array(
			array(
				'type' => 'text',
				'name' => 'title',
				'desc' => __( 'Title:', APP_TD )
			),
			array(
				'type' => 'text',
				'name' => 'fid',
				'desc' => __( 'Facebook ID:', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'connections',
				'desc' => __( 'Connections:', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'width',
				'desc' => __( 'Width:', APP_TD ),
			),
			array(
				'type' => 'text',
				'name' => 'height',
				'desc' => __( 'Height:', APP_TD ),
			),
		);

	}
}