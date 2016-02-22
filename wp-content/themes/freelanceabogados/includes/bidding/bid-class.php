<?php
/**
 * Main classes for the bids objects.
 */


/**
* Class for a bid collection
*
* Examples of bid collections: user bids, post bids
*/
abstract class APP_Bid_Collection {

	/**
	 * The bids collection
	 * @var array
	 */
	public $bids = array();

	/**
	 * Found bids
	 * @var int
	 */
	public $found = 0;

	/**
	 * Total bids
	 * @var int
	 */
	protected $total_bids = 0;

	/**
	 * Average bids amount
	 * @var int
	 */
	protected $avg_amount = 0; // avg bids amount

	/**
	 * The bid collection meta
	 * @var array
	 */
	protected $meta = array(
		'updated' => '',
	);

	/**
	 * Sets up a bid collection object.
	 * @param array args The args to be used to fetch the bid collection
	 */
	public function __construct( $args = array() ) {
		$this->bids = $this->get_bids( $args );
		$this->total_bids = count( $this->bids );
		$this->avg_amount = $this->calc_avg_amount();
	}

	/**
	 * Retrieves a bid collection.
	 */
	abstract protected function get_bids( $args = array() );

	/**
	 * Updates the collection metadata on the DB.
	 */
	protected function save_meta() {}

	/**
	 * Retrieves the collection serialized metadata.
	 */
	public function get_meta() {
		return $this->meta;
	}

	/**
	 * Sets the metadata for the current collection instance.
	 */
	function set_meta( $meta ) {
		$this->meta = wp_parse_args( $meta, $this->meta );
	}

	/**
	 * Adds a new unique bid to the bids collection and updates aggregates.
	 */
	public function add_bid( $bid ) {

		if ( empty( $this->bids[ $bid->get_id() ] ) ) {
			$this->total_bids++;
		}

		$this->bids[ $bid->get_id() ] = $bid;

		$this->update_avg_amount();
	}

	/**
	 * Deletes a bid from the bid collection and updates aggregates..
	 */
	public function delete_bid( $bid ) {

		if ( isset( $this->bids[ $bid->get_id() ] ) ) {
			unset( $this->bids[ $bid->get_id() ] );
			$this->total_bids--;
		}

		$this->update_avg_amount();
	}

	/**
	 * Sets the average amount for the current collection instance.
	 */
	private function update_avg_amount() {

		$amounts = self::get_amounts( $this->bids );

		$this->avg_amount = self::calc_avg_amount( $amounts );

		// save updated data
		$this->save_meta();
	}

	/**
	 * Retrieves the bid collection list of bid amounts.
	 */
	protected function get_amounts( $bids ) {
		$amounts = array();
		foreach ( $bids as $bid ) {
			$amounts[] = $bid->get_amount();
		}
		return $amounts;
	}

	/**
	 * Calculates and retrieves the collection average amount.
	 */
	private function calc_avg_amount() {
		$amounts = self::get_amounts( $this->bids );
		return self::_calc_avg_amount( $amounts );
	}

	/**
	 * Calculates and retrieves the collection average amount.
	 */
	private function _calc_avg_amount( $amounts ) {

		if ( ! count( $amounts ) ) {
			return 0;
		}

		$num = array_sum( $amounts ) / count( $amounts );

		$ceil = ceil( $num );

		$half = $ceil - 0.5;

		if ( $num >= $half + 0.25 ) {
			return $ceil;
		} elseif ( $num < $half - 0.25 ) {
			return floor( $num );
		} else {
			return $half;
		}

	}

	/**
	 *  Set the total found bids if the 'count' param is passed in WP_Comment_Query.
	 *  Executes an extra query to retrieve the count since WP_Comment_Query does not yet retrieve found rows like it does with WP_Query 'found_posts'
	 *  Due to WP_Comment_Query bug, the 'count' is not retrieved correctly so we've added a temporary fix that does the count by
	 *  executing the query with no limit params ('offset' and 'number')
	 *  https://core.trac.wordpress.org/ticket/23369
	 */
	protected function set_found_bids( &$args ) {

		if ( isset( $args['count'] ) ) {

			unset( $args['count'] );
			$temp_args = $args;

			if ( isset( $temp_args['number'] ) ) {
				unset( $temp_args['number'] );
			}

			if ( isset( $temp_args['offset'] ) ) {
				unset( $temp_args['offset'] );
			}

			$this->found = count( APP_Bid_Factory::get_bids( $temp_args ) );
		}
		return $args;
	}

}

/**
 * Represents a user bids collection.
 */
class APP_User_Bid_Collection extends APP_Bid_Collection {

	/**
	 * WordPress user ID
	 * @var int
	 */
	protected $user_id = 0;

	/**
	 * Sets up a user bid collection object
	 *
	 * @param int $user_id The user ID
	 * @param array args The args to be used to fetch the bid collection
	 */
	public function __construct( $user_id, $args = array() ) {

		$this->user_id = $user_id;

		$meta = get_user_option( APP_BIDS_U_DATA_KEY, $user_id );
		$this->meta = wp_parse_args( $meta, $this->meta );

		parent::__construct( $args );
	}

	/**
	 * Updates all the collection metadata on the DB.
	 */
	protected function save_meta() {
		$this->meta['updated'] = time();

		// save all data in array
		update_user_option( $this->user_id, APP_BIDS_U_DATA_KEY, $this->meta );

		// also save amount & avg in separate meta for sorting queries
		update_user_option( $this->user_id, APP_BIDS_U_BIDS_KEY, $this->total_bids );
		update_user_option( $this->user_id, APP_BIDS_U_AMOUNT_AVG_KEY, $this->avg_amount );
	}

	/**
	 * Retrieves the user bids collection.
	 */
	protected function get_bids( $args = array() ) {
		$defaults = array(
			'user_id' => $this->user_id,
			//'post_status' => 'completed'
		);
		$args = wp_parse_args( $args, $defaults );

		$this->set_found_bids( $args );

		return APP_Bid_Factory::get_bids( $args );
	}


	/**
	 * Retrieves the user total bids.
	 *
	 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB
	 * @return int The user total bids
	 */
	public function get_total_bids( $cached = false ) {
		if ( $cached ) {
			$total = get_user_option( APP_BIDS_U_BIDS_KEY, $this->user_id );
		} else {
			$total = $this->total_bids;
		}
		return (int) $total;
	}

	/**
	 * Retrieve the user average bid amount.
	 *
	 * @param type $cached (optional) If set to TRUE will fetch the average from the current bid collection otherwise it will get the value stored in the DB
	 * @return mixed The user average bid amount
	 */
	public function get_avg_amount( $cached = false ) {
		if ( $cached ) {
			$avg = get_user_option( APP_BIDS_U_AMOUNT_AVG_KEY, $this->user_id );
		} else {
			$avg = $this->avg_amount;
		}
		return $avg;
	}

}

/**
 * Represents a post bids collection.
 */
class APP_Post_Bid_Collection extends APP_Bid_Collection {

	/**
	 * WordPress post ID
	 * @var int
	 */
	protected $post_id = 0;

	/**
	 * Sets up a post bid collection object.
	 * @param int $post_id The post ID
	 * @param array args The args to be used to fetch the bid collection
	 */
	public function __construct( $post_id, $args = array() ) {

		$this->post_id = $post_id;

		$meta = get_post_meta( $post_id, APP_BIDS_P_DATA_KEY, true );
		$this->meta = wp_parse_args( $meta, $this->meta );

		parent::__construct( $args );
	}

	/**
	 * Retrieves the post bids collection.
	 */
	protected function get_bids( $args = array() ) {
		$defaults = array(
			'post_id' 	=> $this->post_id,
			//'post_status' => 'completed'
		);
		$args = wp_parse_args( $args, $defaults );

		$this->set_found_bids( $args );

		return APP_Bid_Factory::get_bids( $args );
	}

	/**
	 * Retrieves the post total bids from the collection.
	 *
	 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB,
	 *								  defaults to the the total for the current bid collection
	 * @return int The post total bids
	 */
	public function get_total_bids( $cached = false ) {
		if ( $cached ) {
			$total = (int) get_post_meta( $this->post_id, APP_BIDS_P_BIDS_KEY, true );
		} else {
			$total = $this->total_bids;
		}
		return $total;
	}

	/**
	 * Retrieve the post average bid amount
	 *
	 * @param type $cached (optional) If set to TRUE will fetch the meta value stored in the DB,
	 *								  defaults to the the average for the current bid collection
	 * @return mixed The post average bid amount
	 */
	public function get_avg_amount( $cached = false ) {
		if ( $cached ) {
			$avg = get_post_meta( $this->post_id, APP_BIDS_P_AMOUNT_AVG_KEY, true );
		} else {
			$avg = $this->avg_amount;
		}
		return (int) $this->avg_amount;
	}

	/**
	 * * Updates all the collection metadata on the DB.
	 */
	function save_meta() {
		$this->meta['updated'] = time();

		// save all data in array
		update_post_meta( $this->post_id, APP_BIDS_P_DATA_KEY, $this->meta );

		// also save amount & avg in separate meta for sorting queries
		update_post_meta( $this->post_id, APP_BIDS_P_BIDS_KEY, $this->total_bids );
		update_post_meta( $this->post_id, APP_BIDS_P_AMOUNT_AVG_KEY, $this->avg_amount );
	}

}

/**
 * Main bid class that implements all methods and properties for a bid.
 */
class APP_Single_Bid {

	/**
	 * Comment ID, defined by Wordpress when creating the Comment
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Information on the bid amount
	 * @var array
	 */
	protected $amount = 0;

	/**
	 * Information on the bid currency
	 * @var array
	 */
	protected $currency = 'USD';

	/**
	 * Additional metadata stored for each bid
	 * @var array
	 */
	protected $meta = array(
		'updated' => '',
	);

	/**
	 * Additional metadata stored for each bid
	 * @var array
	 */
	protected $public_meta = array();

	/**
	 * Comment Object
	 * @var object
	 */
	protected $comment = '';


	/**
	 * Sets up the bid object.
	 * @param object comment The comment object inherited by the bid
	 */
	function __construct( $comment ) {

		$this->id = $comment->comment_ID;
		$this->comment = $comment;

		$meta = get_comment_meta( $this->id );

		if ( ! empty( $meta[APP_BIDS_C_DATA_KEY][0] ) ) {
			// bid serialized meta
			$this->meta = maybe_unserialize( $meta[APP_BIDS_C_DATA_KEY][0] );

			// unset the serialized meta keys from the public meta
			unset( $meta[ APP_BIDS_C_DATA_KEY] );
		}

		// flattens public meta value arrays
		array_walk( $meta, create_function('&$value', '$value = $value[0];') );

		// set the public meta
		$this->public_meta = $meta;

		if ( ! empty( $meta[APP_BIDS_C_AMOUNT_KEY] ) ) {
			$this->amount = floatval( $meta[APP_BIDS_C_AMOUNT_KEY] );
		}

		if ( ! empty( $meta[APP_BIDS_C_CURRENCY_KEY] ) ) {
			$this->currency = $meta[APP_BIDS_C_CURRENCY_KEY];
		}

	}

	### GETTERS

	/**
	 * Retrieve data from inaccessible properties.
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->get_data() ) ) {
			return $this->get_data( $name );
		}
		return null;
	}

    /**
	 * Retrieve data from empty properties.
	 */
    public function __isset( $name ) {
        $data = $this->get_data();
        return isset( $data[ $name ] );
    }

	/**
	 * Returns the bid ID.
	 * @return int The bid ID
	 */
	function get_id() {
		return $this->get_data('id');
	}

	/**
	 * Returns the bid post ID.
	 * @return int The bid post id
	 */
	function get_post_ID() {
		return $this->get_data('comment_post_ID');
	}

	/**
	 * Returns the bid user ID.
	 * @return int The bid user id
	 */
	function get_user_id() {
		return $this->get_data('user_id');
	}

	/**
	 * Returns the bid rating.
	 * @return int The bid rating
	 */
	function get_amount() {
		return $this->get_data('amount');
	}

	/**
	 * Retrieve the bid currency.
	 */
	function get_currency() {
		return $this->get_data('currency');
	}

	/**
	 * Returns the bid content.
	 * @return string The bid content
	 */
	function get_content() {
		return $this->get_data('comment_content');
	}

	/**
	 * Returns the bid comment.
	 * @return int The bid comment
	 */
	function get_date() {
		return $this->get_data('comment_date');
	}

	/**
	 * Returns the bid inherited comment object.
	 * @return object The comment objet
	 */
	function get_comment() {
		return $this->comment;
	}

	/**
	 * Retrieves a single meta value or all the meta values for the bid.
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
	 * Retrieve specific or all comment bid data.
	 *
	 * @uses apply_filters() Calls 'comment_text'
	 *
	 */
	private function get_data( $part = '' ) {

		$core = array(
			'id'		=> $this->id,
			'amount'	=> $this->amount,
			'currency'	=> $this->currency,
		);
		$fields = array_merge( $core, (array) $this->comment, (array) $this->meta, (array) $this->public_meta );

		// apply WP comments content filtering
		$fields['comment_content'] = apply_filters( 'comment_text', $fields['comment_content'], $this->comment );

		if ( empty( $part ) ) {
			return $fields;
		} elseif ( isset( $fields[ $part ] ) ) {
			return $fields[ $part ];
		}
		return null;
	}

	### SETTERS

	/**
	 * Store currency in separate meta key to ease querying bids by currency.
	 */
	function set_currency( $code ) {
		$this->currency = $code;

		$this->update_meta( APP_BIDS_C_CURRENCY_KEY, $this->currency, true );
	}

	/**
	 * Set the bid amount.
	 */
	function set_amount( $amount, $currency = 'USD' ) {
		$this->amount = floatval( $amount );

		$this->set_currency( $currency );

		$this->update_meta( APP_BIDS_C_AMOUNT_KEY, $this->amount, true );
	}

	/**
	 * Sets/updates the main serialized meta key for the bid.
	 */
	function set_meta( $meta = array() ) {
		$this->meta['updated'] = time();

		if ( $meta ) {
			$this->meta = wp_parse_args( $meta, $this->meta );
		}

		update_comment_meta( $this->id, APP_BIDS_C_DATA_KEY, $this->meta );
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
	 * Removes a single meta value from the bid public metadata.
	 */
	function delete_meta( $meta_key, $meta_value = '' ) {
		return delete_comment_meta( $this->id, $meta_key, $meta_value );
	}

	/**
	 * Approves/disaproves a bid.
	 */
	function approve( $status = TRUE ) {
		$args = array(
			'comment_ID' => $this->get_id(),
			'comment_approved' => (int) $status,
		);
		return wp_update_comment( $args );
	}

	/**
	 * Cancels a bid.
	 */
	function cancel( $trash = false ) {
		$args = array(
			'comment_ID' => $this->get_id(),
			'comment_approved' => ( $trash ? 'trash' : 'hold' ),
		);
		return wp_update_comment( $args );
	}

	/**
	 * Returns the bid approvement status.
	 *
	 * @return bool	The bool state for the bid approvement status
	 */
	function is_approved() {
		return $this->get_data('comment_approved');
	}


} // end class
