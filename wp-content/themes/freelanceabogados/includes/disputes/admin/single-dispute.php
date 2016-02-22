<?php
/**
 * Disputes admin related functions and classes.
 *
 * @package Components\Disputes
 */

add_action( 'admin_init', '_appthemes_init_dispute_metabox', 15 );

add_action( 'admin_menu', '_appthemes_disputes_remove_meta_box' );
add_action( 'post_row_actions', '_appthemes_disputes_hide_quick_edit', 10, 2 );

/**
 * Displays a custom meta box on dispute pages showing additional information.
 */
function _appthemes_init_dispute_metabox() {
	new APP_Dispute_Moderate_Meta_Box;

	$callback = appthemes_disputes_get_args('participants_callback');

	if ( ! empty( $callback ) ) {
		new APP_Dispute_Participants_Meta_Box;
	}
	new APP_Dispute_Author_Meta_Box;
	new APP_Dispute_Internal_Notes_Meta_Box;
	new APP_Dispute_Official_Response_Meta_Box;
}

/**
 * Hide the quick edit link from disputes.
 *
 * @todo Temporary until better WP builtin solution: https://core.trac.wordpress.org/ticket/19343
 */
function _appthemes_disputes_hide_quick_edit( $actions, $post ) {
	if ( APP_DISPUTE_PTYPE == $post->post_type ) {
		unset( $actions['inline hide-if-no-js'] );
	}

  return $actions;
}

function _appthemes_disputes_remove_meta_box() {
	remove_meta_box( 'authordiv', APP_DISPUTE_PTYPE, 'normal' );
	remove_meta_box( 'submitdiv', APP_DISPUTE_PTYPE, 'side' );
}

/**
 * The dispute moderation meta box.
 */
class APP_Dispute_Moderate_Meta_Box extends APP_Meta_Box {

	/**
	 * Sets up the meta box with WordPress
	 */
	function __construct() {
		parent::__construct( 'dispute-moderate', __( 'Dispute', APP_TD ), APP_DISPUTE_PTYPE, 'side', 'high' );

		//add_action( "save_post_" . APP_DISPUTE_PTYPE, array( $this, 'save_post' ), 10, 3 );
		add_action( "wp_insert_post_data", array( $this, 'save_post_data' ), 10, 2 );
	}

	function condition() {
		return ( appthemes_disputes_get_args( 'enable_disputes' ) && isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == APP_DISPUTE_PTYPE );
	}

	/**
	 * Displays specific details for PayPal Adaptive escrow orders
	 *
	 * @param object $post WordPress Post object
	 */
	function display( $post ) {
		$p2p_post = appthemes_get_dispute_p2p_post( $post->id );
		$p2p_ptype = get_post_type_object( appthemes_disputes_get_args('post_type') );
?>
		<div id="misc-publishing-actions" >
			<div class="misc-pub-section misc-pub-post-status">
				<label for="post_status"><?php echo __( 'Status', APP_TD ); ?>:</label>
				<span id="post-status-display"><?php echo appthemes_get_disputes_statuses_verbiages( $post->post_status ); ?></span>
			</div>
			<div id="visibility" class="misc-pub-section misc-pub-visibility">
				<?php echo $p2p_ptype->labels->singular_name; ?>:
				<span id="post-status-display"><?php echo appthemes_get_disputes_p2p_status_verbiages( $p2p_post->post_status ); ?></span>
				<?php echo sprintf( '<a href="%1$s">%2$s</a>', get_permalink( $p2p_post->ID ), __( 'View', APP_TD ) ); ?>
			</div>
		</div>

<?php
		// provide dispute actions only when the dispute is opened (status = publish)
		if ( 'publish' != $post->post_status ) {
			return;
		}

		echo html( 'hr', '&nbsp;' );

		echo html( 'p', array(), __( 'Decision:', APP_TD ) );

		$labels = appthemes_disputes_get_args('labels');

		echo html( 'input', array(
			'type' => 'submit',
			'class' => 'button-primary',
			'value' => __( 'Accept', APP_TD ),
			'name' => APP_DISPUTE_STATUS_PAY,
			'style' => 'padding-left: 30px; padding-right: 30px; margin-right: 20px; margin-left: 15px;',
			'onclick' => "return confirm('" . __( 'Decide in favor of the '.$labels['disputer'].'? \r\n\r\n\\'.$labels['disputer'].' will be paid. Refund will be refused. \r\n\r\nConfirm?', APP_TD ) . "'); return false;",
		));

		echo html( 'input', array(
			'type' => 'submit',
			'class' => 'button',
			'value' => __( 'Reject', APP_TD ),
			'name' => APP_DISPUTE_STATUS_REFUND,
			'style' => 'padding-left: 30px; padding-right: 30px;',
			'onclick' => "return confirm('" . __( 'Decide in favor of the '.$labels['disputee'].'? \r\n\r\n\\'.$labels['disputee'].' will be refunded.\r\n\r\nConfirm?', APP_TD ) . "'); return false;",
		));

		html ( 'p', '&nbsp; ');

		$labels = appthemes_disputes_get_args('labels');
?>
		<table id="admin-escrow-order-details" style="width: 100%">
			<tbody>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
				<tr>
					<td><?php echo html( 'strong', __( 'Accept:', APP_TD ) ); ?></td>
					<td><?php echo $labels['pay']; ?></td>
				</tr>
				<tr>
					<td><?php echo html( 'strong', __( 'Reject:', APP_TD ) ); ?></td>
					<td><?php echo $labels['refund']; ?></td>
				</tr>
			</tbody>
		</table>
<?php
	}

	public function save_post( $post_ID, $post, $update ) {}

	public function save_post_data( $data, $postarr ) {

		if ( empty( $data['post_type'] ) || APP_DISPUTE_PTYPE != $data['post_type'] )  {
			return $data;
		}

		if ( empty( $_POST[ APP_DISPUTE_STATUS_PAY ] ) && empty( $_POST[ APP_DISPUTE_STATUS_REFUND ] ) ) {
			return $data;
		}

		$data['post_status'] = ! empty( $_POST[ APP_DISPUTE_STATUS_REFUND ] ) ? APP_DISPUTE_STATUS_REFUND : APP_DISPUTE_STATUS_PAY;

		return $data;
	}

}

/**
 * The dispute participants list meta box.
 */
class APP_Dispute_Participants_Meta_Box extends APP_Meta_Box {

	/**
	 * Sets up the meta box with WordPress
	 */
	function __construct(){
		parent::__construct( 'disputes-details', __( 'Participants', APP_TD ), APP_DISPUTE_PTYPE, 'side', 'high' );
	}

	function condition() {
		return ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == APP_DISPUTE_PTYPE );
	}

	/**
	 * Displays specific details for PayPal Adaptive escrow orders
	 *
	 * @param object $post WordPress Post object
	 */

	function display( $post ) {
		$p2p_post = appthemes_get_dispute_p2p_post( $post->ID );
		$participants = appthemes_get_dispute_participants( $post->ID );
?>
		<table id="admin-dispute-details" style="width: 100%">
			<tbody>
				<?php foreach( $participants as $role => $user_id ) : ?>

					<?php $display_name = get_the_author_meta( 'display_name', $user_id ); ?>

					<tr>
						<td><?php echo sprintf( '<a href="%1$s">%2$s</a>', add_query_arg( 'user_id', $user_id, admin_url('user-edit.php') ), $display_name ) . ' ' . ( $user_id == $p2p_post->post_author ? __( '(Owner)', APP_TD ) : '' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
<?php
	}

}

/**
 * The dispute author meta box.
 */
class APP_Dispute_Author_Meta_Box extends APP_Meta_Box {

	/**
	 * Sets up the meta box with WordPress
	 */
	function __construct(){
		parent::__construct( 'dispute-author', __( 'Raised by', APP_TD ), APP_DISPUTE_PTYPE, 'side', 'low' );
	}

	function condition() {
		return ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) == APP_DISPUTE_PTYPE );
	}

	/**
	 * Displays specific details for PayPal Adaptive escrow orders
	 *
	 * @param object $post WordPress Post object
	 */
	function display( $post ) {
		echo html( 'h2', sprintf( '<a href="%1$s">%2$s</a>', add_query_arg( 'user_id', $post->post_author, admin_url('user-edit.php') ), get_the_author_meta( 'display_name', $post->post_author ) ) );
	}

}


/**
 * The dispute internal notes meta box.
 */
class APP_Dispute_Internal_Notes_Meta_Box extends APP_Meta_Box {

	private static $field;

	public function __construct() {
		self::$field = 'internal_notes';

		parent::__construct( 'dispute-internal-notes', __( 'Internal Notes', APP_TD ), APP_DISPUTE_PTYPE );
	}

	public function admin_enqueue_scripts() {
		// add tinyMCE editor styles
		add_editor_style( '/styles/editor-style.css' );
	}

	function display( $post ) {
		echo html( 'p', array(
				'class' => ''
			), __( 'Does not appear publicly. Used for internal communication only.', APP_TD ) );

		wp_editor(
			get_post_meta( $post->ID, self::$field, true ),
			self::$field,
			array(
				'media_buttons' => false,
				'textarea_name' => self::$field,
				'textarea_rows' => 10,
				'tabindex' => '2',
				'editor_css' => '',
				'editor_class' => '',
				'teeny' => false,
				'tinymce' => true,
				'quicktags' => array( 'buttons' => 'strong,em,link,block,ul,ol,li,code' )
			)
		);
	}

	public function before_save( $post_data, $post_id ) {
		if ( isset( $_POST[ self::$field ] ) ) {
			$post_data[ self::$field ] = $_POST[ self::$field ];
		}

		return $post_data;
	}

}


/**
 * The dispute official response meta box.
 */
class APP_Dispute_Official_Response_Meta_Box extends APP_Meta_Box {

	private static $field;

	public function __construct() {
		self::$field = 'official_response';

		parent::__construct( 'dispute-official-response', __( 'Official Response (required)', APP_TD ), APP_DISPUTE_PTYPE );

		add_action( 'admin_footer', array( $this, 'validate_empty_fields' ) );
	}

	public function admin_enqueue_scripts() {
		// add tinyMCE editor styles
		add_editor_style( '/styles/editor-style.css' );
		wp_enqueue_script( 'validate' );
	}

	function display( $post ) {
		echo html( 'p', array(
				'class' => ''
			), __( 'Displayed publicly to the participants. Should be filled used when making the final decision.', APP_TD ) );

		wp_editor(
			get_post_meta( $post->ID, self::$field, true ),
			self::$field,
			array(
				'media_buttons' => false,
				'textarea_name' => self::$field,
				'textarea_rows' => 10,
				'tabindex' => '2',
				'editor_css' => '',
				'editor_class' => 'required',
				'teeny' => false,
				'tinymce' => true,
				'quicktags' => array( 'buttons' => 'strong,em,link,block,ul,ol,li,code' )
			)
		);

	}

	protected function before_save( $post_data, $post_id ) {
		if ( isset( $_POST[ self::$field ] ) ) {
			$post_data[ self::$field ] = $_POST[ self::$field ];
		}
		return $post_data;
	}

	public function validate_empty_fields() {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) || APP_DISPUTE_PTYPE != get_post_type() ) {
			return;
		}
?>
		<script>
			jQuery(document).ready(function($) {

				var validator = $("#post").validate({
					ignore: '',
					errorClass: 'error',
					errorElement: 'div',
					rules: {
					  official_response: {
						required: function() {
							var valid = field_is_valid( $('#official_response'), $('#official_response_ifr') );
							return ! valid;
						},
					  }
					},
					// scroll screen on hidden empty fields
					invalidHandler: function( form, validator ) {
						var errors = validator.numberOfInvalids();

						if ( errors && $("#" + $(validator.errorList[0].element).attr("id")+'_ifr').length ) {

							$('html, body').animate({
								scrollTop: $("#" + $(validator.errorList[0].element).attr("id")+'_ifr').offset().top
							}, 0);

						} else if( errors ) {

							$('html, body').animate({
								scrollTop: $("#" + $(validator.errorList[0].element).attr("id")).offset().top
							}, 0);

						}
					}
				});

				$("#post input[type=submit]").click( function( e ) {
					var valid = field_is_valid( $('#official_response'), $('#official_response_ifr') );

					// refresh the hidden input field and trigger validate()
					if ( ! valid ) {
						$('#official_response').val('');
						validator.form();
					}

				});

				function field_is_valid( field, field_ifr ) {

					// help jquery validator check if the field is empty depending on the current editor choice...
					// ...visual editor
					if ( ! field.is(":visible") ) {
						var content = field_ifr.contents().find('body').html();
					} else {
						// ...text editor
						var content = field.val();
					}

					// remove tags and spaces to look for valid content
					content = content.replace( /&nbsp;|(<([^>]+)>)|\s/ig, "" );

					return ! ( undefined === content || ! content.length || content.indexOf('data-mce-bogus') >= 0 );
				}

			});
		</script>
<?php
	}

}
