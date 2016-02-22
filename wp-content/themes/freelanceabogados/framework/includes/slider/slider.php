<?php
/**
 * Slider
 *
 * @package Framework\Slider
 */

add_action( 'init', '_appthemes_load_slider' );

function _appthemes_load_slider() {
	if ( ! current_theme_supports( 'app-slider' ) ) {
		return;
	}

	$args = appthemes_slider_get_args();

	if ( $args['enqueue_scripts'] ) {
		add_action( 'wp_enqueue_scripts', 'appthemes_slider_enqueue_scripts' );
	}

	if ( $args['enqueue_styles'] ) {
		add_action( 'wp_enqueue_scripts', 'appthemes_slider_enqueue_styles' );
	}

	appthemes_slider_init_image_size();
}

function appthemes_slider_enqueue_scripts( $script_uri = '' ) {
	if ( ! current_theme_supports( 'app-slider' ) ) {
		return;
	}

	$script_uri = ! empty( $script_uri ) ? $script_uri : APP_FRAMEWORK_URI . '/includes/slider/slider.js';
	wp_enqueue_script(
		'app-slider',
		$script_uri,
		array( 'jquery' ),
		'1.1'
	);
}

function appthemes_slider_enqueue_styles( $style_uri = '' ) {
	if ( ! current_theme_supports( 'app-slider' ) ) {
		return;
	}

	$style_uri = ! empty( $style_uri ) ? $style_uri : APP_FRAMEWORK_URI . '/includes/slider/slider.css';
	wp_enqueue_style(
		'app-slider-style',
		$style_uri,
		array(),
		'1.0'
	);
}

function appthemes_slider_init_image_size() {
	global $_wp_additional_image_sizes;

	if ( ! current_theme_supports( 'app-slider' ) ) {
		return;
	}

	$args = appthemes_slider_get_args();

	// check if we need to register new image size
	if ( isset( $_wp_additional_image_sizes[ $args['attachment_image_size'] ] ) ) {
		return;
	}

	$size = apply_filters( 'appthemes_slider_image_size', array( 'width' => $args['width'], 'height' => $args['height'] ) );

	add_image_size( $args['attachment_image_size'], $size['width'], $size['height'], true );
}

/**
 * Retrieve slider theme support options,
 * which can be overriden by the slider instance properties
 *
 */
function appthemes_slider_get_args() {
	global $content_width, $_wp_additional_image_sizes;

	if ( ! current_theme_supports( 'app-slider' ) ) {
		return array();
	}

	list( $args ) = get_theme_support( 'app-slider' );

	$defaults = array(
		'mime_groups'                   => array( 'image', 'video', 'video_iframe_embed' ),
		'image_mime_types'              => array( 'image/png', 'image/jpeg', 'image/gif' ),
		'video_mime_types'              => array( 'video/mp4' ),
		'video_iframe_embed_mime_types' => array( 'video/youtube-iframe-embed', 'video/vimeo-iframe-embed', 'video/iframe-embed' ),
		'id'                            => 'app-slider',
		'slider_class'                  => '',
		'video_embed_class'             => '',
		'attachment_image_size'         => 'app_slider',
		'image_a_attr'                  => array(),
		'enqueue_scripts'               => true,
		'enqueue_styles'                => true,
		'effect'                        => 'slide',
		'duration'                      => 300
	);

	$args = wp_parse_args( $args, $defaults );

	if ( ! isset( $args['width'] ) || ! isset( $args['height'] ) ) {
		if ( isset( $_wp_additional_image_sizes[ $args['attachment_image_size'] ] ) ) {
			// first check if image size is registered
			$args = wp_parse_args( $args, $_wp_additional_image_sizes[ $args['attachment_image_size'] ] );
		} else {
			// otherwise generate own values
			$content_width = intval( $content_width );
			if ( $content_width ) {
				// if content_width is set, we can use it for default slider size
				$args['width']  = $content_width;
				$args['height'] = intval( $content_width * 9 / 16 );
			} else {
				// otherwise we have to fallback to fixed values
				$args['width']  = 475;
				$args['height'] = 300;
			}
		}
	}

	return $args;
}

class APP_Slider {

	public $id;
	public $slider_class;

	public $mime_groups;
	public $image_mime_types;
	public $video_mime_types;
	public $embed_video_mime_types;

	public $width;
	public $height;
	public $effect;
	public $duration;

	function __construct( $args = array() ) {
		$defaults = appthemes_slider_get_args();

		$args = wp_parse_args( $args, $defaults );

		$args['slider_class'] = explode( ' ', $args['slider_class'] );
		$args['slider_class'][] = 'app-slider';
		$args['slider_class'] = implode( ' ', $args['slider_class'] );

		foreach ( $args as $arg_k => $arg_v ) {
			$this->{$arg_k} = $arg_v;
		}
	}

	function get_attachments( $post_id = 0 ) {
		$post_id = $post_id ? $post_id : get_the_ID();

		$posts = get_posts( array(
			'post_parent' => $post_id,
			'post_status' => 'inherit',
			'post_type'   => 'attachment',
			'nopaging'    => true,
			'orderby'     => 'menu_order',
			'order'       => 'asc'
		) );

		return $posts;
	}

	function display() {
		$attachments = $this->get_attachments();

		$attachments_html = '';
		$id = 0;
		foreach ( $attachments as $attachment ) {
			$mime_display = $this->display_mime_type( $attachment );
			if ( empty( $mime_display ) ) {
				continue;
			}

			if ( $attachment->post_excerpt ) {
				$mime_display .= html( 'figcaption', array( 'class' => 'attachment-caption' ), $attachment->post_excerpt );
			}

			$attachments_html .= html( 'figure', array( 'class' => 'attachment attachment_' . $id ), $mime_display );
			$id++;
		}

		$attachments_container = html( 'div', array( 'class' => 'attachments'), $attachments_html );

		$arrow_left = html( 'div', array( 'class' => 'left-arrow' ) );
		$arrow_right = html( 'div', array( 'class' => 'right-arrow' ) );

		$slider = html( 'div', array( 'id' => $this->id, 'class' => $this->slider_class ), $arrow_left, $arrow_right, $attachments_container );
		$slider .= $this->scripts();
		return $slider;
	}

	function display_mime_type( $attachment ) {

		$mime_type = $attachment->post_mime_type;
		foreach ( $this->mime_groups as $mime_group ) {
			$html = apply_filters( 'appthemes_slider_attachment_mime_group-' . $mime_group, null, $attachment );
			if ( ! is_null( $html ) ) {
				return $html;
			}

			if ( in_array( $mime_type, $this->{ $mime_group . '_mime_types' } ) ) {
				$method = 'display_' . $mime_group;
				$html = $this->$method( $attachment );
				return $html;
			}
		}

		$html = apply_filters( 'appthemes_slider_attachment_mime_type_unknown', '', $attachment );
		return $html;
	}

	function display_image( $attachment ) {
		return html( 'a', $this->image_a_attr + array(
			'href' => wp_get_attachment_url( $attachment->ID ),
			'title' => trim( strip_tags( get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) ) )
		), wp_get_attachment_image( $attachment->ID, $this->attachment_image_size ) );
	}

	function display_video( $attachment ) {
		$args = array(
			'mp4' => $attachment->guid,
			'width' => $this->width,
			'height' => $this->height,
		);

		// Opera 12 (Presto, pre-Chromium) fails to load ogv properly
		// when combined with ME.js. Works fine in Opera 15.
		// Don't serve ogv to Opera 12 to avoid complete brokeness.
		if ( $GLOBALS['is_opera'] ) {
			unset( $args['ogv'] );
		}

		return wp_video_shortcode( $args );
	}

	function display_video_iframe_embed( $attachment ) {
		$src = set_url_scheme( $attachment->guid );
		$embed = wp_oembed_get( $src, array( 'width' => $this->width, 'height' => $this->height ) );

		if ( ! $embed ) {
			$embed = html( 'iframe webkitallowfullscreen mozallowfullscreen allowfullscreen ', array(
				'src' => $src,
				'frameborder' => 0,
				'style' => 'display:block;border:0;',
				'height' => $this->height,
				'width' => $this->width,
			) );
		}

		return html( 'div', array(
			'class' => 'slider-video-iframe slider-video-iframe-' . esc_attr( $attachment->post_mime_type ) . ' ' . $this->video_embed_class
		), $embed );
	}

	function scripts() {
		ob_start();
	?>
		<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery('#<?php echo esc_js( $this->id ); ?>').appthemes_slider({
				height   : <?php echo esc_js( $this->height ) ?>,
				width    : <?php echo esc_js( $this->width ) ?>,
				duration : <?php echo esc_js( $this->duration ) ?>,
				effect   : "<?php echo esc_js( $this->effect ) ?>"
			});
		});
		</script>
	<?php
		return ob_get_clean();
	}
}
