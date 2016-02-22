<?php
/**
 * Reviews classes
 *
 * @package Components\Reviews
 */

/**
 * Class for a review collection
 * 
 * Examples of review collections: user reviews, post reviews
 */
abstract class APP_Review_Collection {

	/**
	 * The reviews collection
	 * @var array
	 */
	public $reviews = array();

	/**
	 * Found reviews
	 * @var int
	 */
	public $found = 0;

	/**
	 * Total received reviews
	 * @var int
	 */
	protected $total_reviews = 0;

	/**
	 * Average received reviews rating
	 * @var int
	 */
	protected $avg_rating = 0;

	/**
	 * Relative reviews rating
	 * @var int
	 */
	protected $relative_rating = 3;

	/**
	 * The review collection meta
	 * @var array
	 */
	protected $meta = array(
		'updated' => '',
	);

	/**
	 * Sets up a review collection object
	 *
	 * @param array args (optional) WP_Query args to be used to fetch the review collection
	 */
	public function __construct( $args = array() ) {
		$this->reviews = $this->get_reviews( $args );

		$this->total_reviews = count( $this->reviews );
		$this->avg_rating = $this->calc_avg_rating();

		$this->relative_rating = $this->calc_relative_rating();
	}

	/**
	 * Abstract method to retrieve a review collection
	 */
	abstract protected function get_reviews( $args = array() );

	/**
	 * Updates the review collection metadata on the DB
	 */
	protected function save_meta() {}

	/**
	 * Retrieves the review collection metadata
	 *
	 * @return array The collection metadata
	 */
	public function get_meta() {
		return $this->meta;
	}

	/**
	 * Sets the metadata for the current review collection
	 *
	 * @param array $meta The metadata to be added to the collection
	 */
	function set_meta( $meta ) {
		$this->meta = wp_parse_args( $meta, $this->meta );
	}

	/**
	 * Adds a new unique received review to the review collection and updates aggregates
	 *
	 * @param object $review The review to be added
	 */
	public function add_review( $review ) {

		if ( empty( $this->reviews[ $review->get_id() ] ) ) {
			$this->total_reviews++;
		}

		$this->reviews[ $review->get_id() ] = $review;

		$this->update_avg_rating();
	}

	/**
	 * Deletes a review from the review collection and updates aggregates
	 *
	 * @param object $review The review to be added
	 *
	 */
	public function delete_review( $review ) {

		if ( isset( $this->reviews[ $review->get_id() ] ) ) {
			unset( $this->reviews[ $review->get_id() ] );
			$this->total_reviews--;
		}

		$this->update_avg_rating();
	}

	/**
	 * Updates the average rating for the current collection instance
	 */
	protected function update_avg_rating() {

		$ratings = self::get_ratings( $this->reviews );

		$this->avg_rating = self::calc_avg_rating( $ratings );
		$this->relative_rating = self::calc_relative_rating( $ratings );

		// save updated data
		$this->save_meta();
	}

	/**
	 * Retrieves the ratings list for the review collection
	 *
	 * @param array $reviews The reviews collection
	 * @return array The ratings list
	 */
	protected function get_ratings( $reviews ) {
		$ratings = array();
		foreach( $reviews as $review ) {
			$ratings[] = $review->get_rating();
		}
		return $ratings;
	}

	/**
	 * Calculates and retrieves the collection average rating
	 *
	 * @return float The averagate rating
	 */
	private function calc_avg_rating() {
		$ratings = self::get_ratings( $this->reviews );
		return self::_calc_avg_rating( $ratings );
	}

	/**
	 * Calculates and retrieves the collection relative rating
	 *
	 * @return float The relative rating
	 */
	private function calc_relative_rating() {
		$ratings = self::get_ratings( $this->reviews );
		return self::_calc_relative_rating( $ratings );
	}

	/**
	 * Calculates and retrieves the collection average rating
	 *
	 * @param array $ratings The ratings list
	 * @return float The calculated average rating
	 */
	private function _calc_avg_rating( $ratings ) {

		if ( ! count( $ratings ) ) {
			return 0;
		}

		$num = array_sum( $ratings ) / count( $ratings );

		$ceil = ceil( $num );

		$half = $ceil - 0.5;

		if ( $num >= $half + 0.25 ) {
			return $ceil;
		} else if ( $num < $half - 0.25 ) {
			return floor( $num );
		} else {
			return $half;
		}
	}

	/**
	 * Calculates relative rating by adding artifical neutral value
	 *
	 * @uses apply_filters() Calls 'appthemes_review_relative_rating'
	 *
	 * @param array $ratings The ratings list
	 * @return float The calculated relative rating
	 */
	private function _calc_relative_rating( $ratings ) {

		if ( ! count( $ratings ) ) {
			return 0;
		}

		$rel_rating = ( array_sum( $ratings ) + appthemes_reviews_get_args( 'success_rate_min_val' ) ) / ( count( $ratings ) + 1 );

		return apply_filters( 'appthemes_review_relative_rating', $rel_rating, $ratings, $this );
	}

	/**
	 *  Set the total found reviews if the 'count' param is passed in WP_Comment_Query.
	 *  Executes an extra query to retrieve the count since WP_Comment_Query does not yet retrieve found rows like it does with WP_Query 'found_posts'
	 *  Due to WP_Comment_Query bug, the 'count' is not retrieved correctly so we've added a temporary fix that does the count by
	 *  executing the query with no limit params ('offset' and 'number')
	 *  https://core.trac.wordpress.org/ticket/23369
	 */
	protected function set_found_reviews( &$args ) {

		if ( isset( $args['count'] ) ) {

			unset( $args['count'] );
			$temp_args = $args;

			if ( isset( $temp_args['number'] ) ) {
				unset( $temp_args['number'] );
			}

			if ( isset( $temp_args['offset'] ) ) {
				unset( $temp_args['offset'] );
			}

			$this->found = count( APP_Review_Factory::get_reviews( $temp_args ) );
		}
		return $args;
	}

}

/**
 * Represents a user reviews collection
 */
class APP_User_Reviews extends APP_Review_Collection {

	/**
	 * WordPress user ID
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * User relation with review (recipient|author)
	 * @var string
	 */
	protected $relation = 0;

	/**
	 * Sets up a user review collection object
	 *
	 * @param int $user_id The user ID
	 * @param array args WP_Query args to be used to fetch the review collection
	 */
	public function __construct( $user_id, $args = array() ) {

		$defaults = array(
			'relation' => 'recipient'
		);
		$args = wp_parse_args( $args, $defaults );

		$this->user_id = $user_id;
		$this->relation = $args['relation'];

		$meta = get_comment_meta( $user_id, APP_REVIEWS_U_DATA_KEY, true );
		$this->meta = wp_parse_args( $meta, $this->meta );

		parent::__construct( $args );
	}

	/**
	 * Retrieve the user reviews collection
	 *
	 * @param type $args (optional) WP_Query args to be used to fetch the review collection
	 * @return array The reviews collection
	 */
	protected function get_reviews( $args = array() ) {
		$defaults = array(
			'meta_key'		=> APP_REVIEWS_C_RECIPIENT_KEY,
			'meta_value'	=> $this->user_id,
			//'post_status'	=> 'completed'
		);
		$args = wp_parse_args( $args, $defaults );

		$this->set_found_reviews( $args );

		return APP_Review_Factory::get_reviews( $args );
	}

	/**
	 * Retrieves the total number of received reviews
	 *
	 * @return int The total number of received reviews
	 */
	function get_total_reviews() {
		return $this->total_reviews;
	}

	/**
	 * Retrieves the average review rating for received reviews
	 *
	 * @return int The average rating for received reviews
	 */
	function get_avg_rating() {
		return $this->avg_rating;
	}

	/**
	 * Saves the review collection user metadata in the DB
	 */
	protected function save_meta() {
		$this->meta['updated'] = time();

		if ( 'author' == $this->relation ) {
			$total_key = APP_REVIEWS_U_TOTAL_AUTHORED_KEY;
			$avg_key = APP_REVIEWS_U_AVG_AUTHORED_KEY;
		} else  {
			$total_key = APP_REVIEWS_U_TOTAL_KEY;
			$avg_key = APP_REVIEWS_U_AVG_KEY;

			// relative ratings should only be set for recieved reviews
			update_user_option( $this->user_id, APP_REVIEWS_U_REL_KEY, $this->relative_rating );
		}

		// update reviews aggregates
		update_user_option( $this->user_id, $total_key, $this->total_reviews );
		update_user_option( $this->user_id, $avg_key, $this->avg_rating );


		// save all data in array
		update_user_option( $this->user_id, APP_REVIEWS_U_DATA_KEY, $this->meta );
	}

}

/**
 * Represents a post reviews collection
 */
class APP_Post_Reviews extends APP_Review_Collection {

	/**
	 * WordPress post ID
	 * @var int
	 */
	protected $post_id = 0;

	/**
	 * Sets up a post review collection object
	 *
	 * @param int $post_id  The post ID
	 * @param array args WP_Query args to be used to fetch the review collection
	 */
	public function __construct( $post_id, $args = array() ) {

		$this->post_id = $post_id;

		$meta = get_comment_meta( $post_id, APP_REVIEWS_P_DATA_KEY, true );
		$this->meta = wp_parse_args( $meta, $this->meta );

		parent::__construct( $args );
	}

	/**
	 * Retrieves the post ID related with the current review collection
	 *
	 * @return int The WordPress User ID
	 */
	public function get_post_ID() {
		return $this->post_id;
	}

	/**
	 * Retrieve the post reviews collection
	 *
	 * @param type $args (optional) WP_Query args to be used to fetch the review collection
	 * @return array The reviews collection
	 */
	protected function get_reviews( $args = array() ) {
		$defaults = array(
			'post_id' => $this->post_id,
			//'post_status' => 'completed'
		);
		$args = wp_parse_args( $args, $defaults );

		$this->set_found_reviews( $args );

		return APP_Review_Factory::get_reviews( $args );
	}

	/**
	 * Retrieves the total post reviews
	 *
	 * @return int The total post reviews
	 */
	public function get_total_reviews() {
		return $this->total_reviews;
	}

	/**
	 * Retrieve the post reviews average rating
	 *
	 * @return mixed The post average rating
	 */
	public function get_avg_rating() {
		return $this->avg_rating;
	}

	/**
	 * Saves the review collection post metadata in the DB
	 */
	protected function save_meta() {

		// save all data in array
		update_post_meta( $this->post_id, APP_REVIEWS_P_DATA_KEY, $this->meta );

		// also save rating & avg in separate meta for sorting queries
		update_post_meta( $this->post_id, APP_REVIEWS_P_TOTAL_KEY, $this->total_reviews );
		update_post_meta( $this->post_id, APP_REVIEWS_P_AVG_KEY, $this->avg_rating );
		update_post_meta( $this->post_id, APP_REVIEWS_P_REL_KEY, $this->relative_rating );
	}

}

/**
 * Represents a single review derived from a WordPress comment object
 */
class APP_Single_Review {

	/**
	 * Comment ID, defined by Wordpress when creating the Comment
	 * @var int
	 */
	protected $id = 0;

	/**
	 * The review type: user | post
	 * @var int
	 */
	protected $review_type = '';

	/**
	 * The ID for the user or post being reviewed
	 * @var int
	 */
	protected $recipient_id = 0;

	/**
	 * The review rating
	 * @var int
	 */
	protected $rating = 0;

	/**
	 * Extra metadata stored for each review
	 * @var array
	 */
	protected $meta = array(
		'updated' => '',
	);

	/**
	 * Additional metadata stored for each review
	 * @var array
	 */
	protected $public_meta = array();

	/**
	 * WordPress comment object
	 * @var object
	 */
	protected $comment = '';

	/**
	 * Sets up a review object
	 *
	 * @param object comment The comment object that the review will inherit
	 */
	function __construct( $comment ) {

		$this->id = $comment->comment_ID;
		$this->comment = $comment;

		$meta = get_comment_meta( $this->id );

		if ( ! empty( $meta[APP_REVIEWS_C_DATA_KEY][0] ) ) {
			// review serialized meta
			$this->meta = maybe_unserialize( $meta[APP_REVIEWS_C_DATA_KEY][0] );

			// unset the serialized meta keys from the public meta
			unset( $meta[ APP_REVIEWS_C_DATA_KEY] );
		}

		// flattens public meta value arrays
		array_walk( $meta, create_function('&$value', '$value = $value[0];') );

		// set the public meta
		$this->public_meta = $meta;

		if ( ! empty( $meta[APP_REVIEWS_C_RECIPIENT_TYPE_KEY] ) ) {
			$this->review_type = $meta[APP_REVIEWS_C_RECIPIENT_TYPE_KEY];
		}

		if ( ! empty( $meta[APP_REVIEWS_C_RECIPIENT_KEY] ) ) {
			$this->recipient_id = $meta[APP_REVIEWS_C_RECIPIENT_KEY];
		}

		if ( ! empty( $meta[APP_REVIEWS_C_RATE_KEY] ) ) {
			$this->rating = $meta[APP_REVIEWS_C_RATE_KEY];
		}

	}

	### GETTERs

	/**
	 * Magic method to retrieve data from inaccessible properties
	 *
	 * @param property $name The property to get the value from
	 * @return mixed|null The property value or null if not found
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->get_data() ) ) {
			return $this->get_data( $name );
		}
		return null;
	}

	/**
	 * Retrieves the review ID
	 *
	 * @return int The review ID
	 */
	function get_id() {
		return $this->get_data( 'id' );
	}

	/**
	 * Retrieves the review type: user | post
	 *
	 * @return string The review type
	 */
	function get_type() {
		return $this->get_data('type');
	}

	/**
	 * Retrieves the review recipient ID
	 *
	 * @return int The review recipient ID
	 */
	function get_recipient_id() {
		return $this->get_data('recipient');
	}

	/**
	 * Retrieves the review post id
	 *
	 * @return int The review post id
	 */
	function get_post_ID() {
		return $this->get_data('comment_post_ID');
	}

	/**
	 * Retrieves the reviewer user id
	 *
	 * @return int The reviewer user id
	 */
	function get_author_ID() {
		return $this->get_data('user_id');
	}

	/**
	 * Retrieves the review rating
	 *
	 * @return int The review rating
	 */
	function get_rating() {
		return $this->get_data('rating');
	}

	/**
	 * Retrieves the review content
	 *
	 * @return string The review content
	 */
	function get_content() {
		return $this->get_data('comment_content');
	}

	/**
	 * Retrieves the review comment
	 *
	 * @return int The review comment
	 */
	function get_date() {
		return $this->get_data('comment_date');
	}

	/**
	 * Retrieves the review inherited comment object
	 *
	 * @return object The comment objet
	 */
	function get_comment() {
		return $this->comment;
	}

	/**
	 * Retrieves specific or all review metadata
	 *
	 * @param string $key (optional) The meta key to retrieve values from
	 * @param bool $single (optional) Retrieve a single value or an array of values (default) as in 'get_comment_meta()'
	 *
	 * @return mixed Returns a meta data array or a single value
	 */
	function get_meta( $key = '', $single = false ) {

		// merge public and base meta
		$all_meta = array_merge( $this->public_meta, $this->meta );

		if ( $key ) {

			if ( empty( $all_meta[ $key ] ) ) {

				if ( $single ) {
					return '';
				} else {
					return array();
				}

			}

			if ( $single ) {
				$meta = $all_meta[ $key ];
			} else  {
				$meta = array( $all_meta[ $key ] );
			}

		} else {

			// retrieves the meta values as an array of arrays as in WP 'get_metadata()'
			$meta = $all_meta;
			array_walk( $meta, create_function('&$value', '$value = array( $value );') );

		}
		return $meta;
	}

	/**
	 * Retrieves specific or all comment review data
	 *
	 * @uses apply_filters() Calls 'comment_text'
	 *
	 * @param string $part (optional) Field part to retrieve
	 * @return mixed A single value or a data list
	 */
	private function get_data( $part = '' ) {

		$basic = array(
			'id'		=> $this->id,
			'type'		=> $this->review_type,
			'recipient' => $this->recipient_id,
			'rating'	=> $this->rating,
		);
		$fields = array_merge( $basic, (array) $this->comment, (array) $this->meta, (array) $this->public_meta );

		// apply WP comments content filtering
		$fields['comment_content'] = apply_filters( 'comment_text', $fields['comment_content'], $this->comment );

		if ( empty( $part ) ) {
			return $fields;
		} elseif ( isset( $fields[ $part ] ) ) {
			return $fields[ $part ];
		}
		return null;
	}

	### SETTERs

	/**
	 * Sets the review type
	 *
	 * @param string The review type: user | post
	 */
	public function set_type( $type ) {
		$this->review_type = $type;

		$this->update_meta( APP_REVIEWS_C_RECIPIENT_TYPE_KEY, $this->review_type, true );
	}

	/**
	 * Sets the review recipient ID
	 *
	 * @param int The review recipient ID
	 */
	public function set_recipient( $id ) {
		$this->recipient_id = $id;

		$this->update_meta( APP_REVIEWS_C_RECIPIENT_KEY, $this->recipient_id, true );
	}

	/**
	 * Sets the review rating
	 *
	 * @param string The rating
	 */
	public function set_rating( $rating ) {
		$this->rating = floatval( $rating );

		$this->update_meta( APP_REVIEWS_C_RATE_KEY, $this->rating, true );
	}

	/**
	 * Sets the review meta
	 *
	 * @param array The meta key/value pairs
	 */
	function set_meta( $meta = array() ) {
		$this->meta['updated'] = time();

		if ( $meta ) {
			$this->meta = wp_parse_args( $meta, $this->meta );
		}

		update_comment_meta( $this->id, APP_REVIEWS_C_DATA_KEY, $this->meta );
	}

	/**
	 * Updates/adds values to the main serialized meta key (default) or in a separate meta key.
	 */
	function update_meta( $meta_key, $meta_value = '', $public = false ) {

		// if the meta value is public, stores it on it owns meta key
		if ( $public ) {
			update_comment_meta( $this->id, $meta_key, $meta_value );
		} else {
			// othwerwise add the meta key/value pair to the serialized meta key
			$this->meta = wp_parse_args( array( $meta_key => $meta_value ), $this->meta );
		}

		$this->set_meta();
	}

	/**
	 * Removes a single meta value from the review public metadata.
	 */
	function delete_meta( $meta_key, $meta_value = '' ) {
		return delete_comment_meta( $this->id, $meta_key, $meta_value );
	}

	/**
	 * Approves/disaproves a review.
	 */
	public function approve( $status = TRUE ) {
		$args = array(
			'comment_ID' => $this->get_id(),
			'comment_approved' => (int) $status,
		);
		wp_update_comment( $args );
	}

	/**
	 * Cancels a review.
	 */
	function cancel( $trash = false ) {
		$args = array(
			'comment_ID' => $this->get_id(),
			'comment_approved' => ( $trash ? 'trash' : 'hold' ),
		);
		return wp_update_comment( $args );
	}

	### OTHER

	/**
	 * Returns the review approvement status
	 *
	 * @return bool The bool state for the review approvement status
	 */
	function is_approved() {
		return $this->get_data('comment_approved');
	}

}
