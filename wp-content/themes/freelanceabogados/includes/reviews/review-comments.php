<?php
/**
 * Review comments class
 *
 * Abstract helper class that hooks into WP comments to trigger actions or filters on a specific custom comment type
 *
 * @package Components\Reviews
 */
class APP_Review_Comments {

	/**
	 * Comment Type, the custom comment type to use
	 * @var string
	 */
	private static $comment_type = '';

	/**
	 * The comment slug to use on the comment link
	 * @var string
	 */
	private static $slug = '';

	/**
	 * Should the comment be auto approved when created
	 * @var boolean
	 */
	private static $auto_approve = true;

	/**
	 * Sets up the extended comments class
	 *
	 * @param string comment_type The custom comment type
	 * @param boolean auto_approve Should the comment type be auto approved
	 */
	public static function init( $comment_type, $auto_approve = true ) {

		if ( ! $comment_type ) {
			trigger_error( 'No custom comment type defined.', E_USER_WARNING );
		}

		// slug for the review links
		self::$slug = preg_replace( "[^A-Za-z]", "", $comment_type );

		self::$comment_type = $comment_type;

		self::$auto_approve = $auto_approve;

		add_filter( 'preprocess_comment',	array( __CLASS__, 'process_comment' ) );
		add_filter( 'get_comment_link',		array( __CLASS__, 'set_comment_link' ), 10, 3 );
		add_filter( 'comments_array',		array( __CLASS__, 'intercept_comment_query' ), 10, 2 );
		add_filter( 'pre_comment_approved',	array( __CLASS__, 'auto_approve' ), 10, 2 );
		add_filter( 'appthemes_ctypes_count_exclude', array( __CLASS__, 'exclude_from_comment_count' ) );
	}

	/**
	 * Process the comment once submitted
	 */
	public static function process_comment( $data ) {

		if ( ! isset( $_POST['comment_type'] ) || self::$comment_type != $_POST['comment_type'] ) {
			return $data;
		}

		// set the custom comment type
		$data['comment_type'] = self::$comment_type;

		return $data;
	}

	/**
	 * Replace the default comment hash link with the custom comment type
	 *
	 * @uses apply_filters() Calls 'appthemes_review_link'
	 *
	 */
	public static function set_comment_link( $link, $comment, $args ) {

		if ( $comment->comment_type != self::$comment_type ) {
			return $link;
		}

		$link = preg_replace( '/#comment-([\d]+)/', sprintf( '#%s-$1', self::$slug ), $link );

		return apply_filters( 'appthemes_review_link', $link, $comment, $args );
	}

	/**
	 * Strip out the custom comment type from the comment front-end loopin case theme doesn't filter via wp_list_comments( array('type' => 'comment') )
	 */
	public static function intercept_comment_query( $comments, $post_id ) {

		foreach( $comments as $key => $comment ) {
			if ( $comment->comment_type == self::$comment_type ) {
				unset( $comments[$key] );
			}
		}

		// reset array keys
		return array_values( $comments );
	}

	/**
	 * Ignore WordPress discussion settings for this comment type and approve the comment based on the 'auto_aprove' property
	 */
	public static function auto_approve( $approved, $commentdata ) {

		if ( empty( $commentdata['comment_type'] ) || $commentdata['comment_type'] != self::$comment_type ) {
			return $approved;
		}

		if ( self::$auto_approve ) {
			$approved = self::$auto_approve;
		}

		return $approved;
	}

	/**
	 * Strip out custom comment type from the comment counters
	 */
	public static function exclude_from_comment_count( $exclude_types ) {
		$exclude_types[] = self::$comment_type;
		return $exclude_types;
	}

}
