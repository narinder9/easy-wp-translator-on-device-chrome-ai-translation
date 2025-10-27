<?php
/**
 * @package EasyWPTranslator
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages copy and synchronization of terms and post metas
 *
 *  
 */
class EWT_Admin_Sync extends EWT_Sync {
	/**
	 * @var EWT_Admin_Links
	 */
	private $links;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		parent::__construct( $easywptranslator );

		$this->links = &$easywptranslator->links;

		add_filter( 'wp_insert_post_parent', array( $this, 'wp_insert_post_parent' ), 10, 3 );
		add_filter( 'wp_insert_post_data', array( $this, 'wp_insert_post_data' ) );
		add_filter( 'use_block_editor_for_post', array( $this, 'new_post_translation' ), 5000 ); // After content duplication.
	}

	/**
	 * Translates the post parent if it exists when using "Add new" (translation).
	 *
	 *  
	 *
	 * @param int   $post_parent Post parent ID.
	 * @param int   $post_id     Post ID, unused.
	 * @param array $postarr     Array of parsed post data.
	 * @return int
	 */
	public function wp_insert_post_parent( $post_parent, $post_id, $postarr ) {
		$context_data = $this->links->get_data_from_new_post_translation_request( $postarr['post_type'] ?? '' );

		if ( empty( $context_data ) ) {
			return $post_parent;
		}

		// Make sure not to impact media translations created at the same time.
		$parent_id = wp_get_post_parent_id( $context_data['from_post'] );

		if ( empty( $parent_id ) ) {
			return $post_parent;
		}

		$tr_parent = $this->model->post->get_translation( $parent_id, $context_data['new_lang'] );

		if ( empty( $tr_parent ) ) {
			return $post_parent;
		}

		return $tr_parent;
	}

	/**
	 * Copies menu order, comment, ping status and optionally the date when creating a new translation.
	 *
	 *  
	 *
	 * @param array $data An array of slashed post data.
	 * @return array
	 */
	public function wp_insert_post_data( $data ) {
		$context_data = $this->links->get_data_from_new_post_translation_request( $data['post_type'] ?? '' );

		if ( empty( $context_data ) ) {
			return $data;
		}

		foreach ( array( 'menu_order', 'comment_status', 'ping_status' ) as $property ) {
			$data[ $property ] = $context_data['from_post']->$property;
		}

		// Copy the date only if the synchronization is activated.
		if ( in_array( 'post_date', $this->options['sync'], true ) ) {
			$data['post_date']     = $context_data['from_post']->post_date;
			$data['post_date_gmt'] = $context_data['from_post']->post_date_gmt;
		}

		return $data;
	}

	/**
	 * Copies post metas and taxonomies when using "Add new" (translation).
	 *
	 *  
	 *   Use of use_block_editor_for_post filter instead of rest_api_init which is triggered too early in WP 5.8.
	 *
	 * @param bool $is_block_editor Whether the post can be edited or not.
	 * @return bool
	 */
	public function new_post_translation( $is_block_editor ) {
		global $post;
		static $done = array();

		if ( empty( $post ) ) {
			return $is_block_editor;
		}

		$context_data = $this->links->get_data_from_new_post_translation_request( $post->post_type );

		if ( empty( $context_data ) || ! empty( $done[ $context_data['from_post']->ID ] ) ) {
			return $is_block_editor;
		}

		$lang = $this->model->get_language( $context_data['new_lang'] );

		if ( empty( $lang ) ) {
			return $is_block_editor;
		}

		$done[ $context_data['from_post']->ID ] = true; // Avoid a second duplication in the block editor. Using an array only to allow multiple phpunit tests.

		$this->taxonomies->copy( $context_data['from_post']->ID, $post->ID, $lang->slug );
		$this->post_metas->copy( $context_data['from_post']->ID, $post->ID, $lang->slug );

		if ( is_sticky( $context_data['from_post']->ID ) ) {
			stick_post( $post->ID );
		}

		return $is_block_editor;
	}

	/**
	 * Get post fields to synchronize.
	 *
	 *  
	 *
	 * @param WP_Post $post Post object.
	 * @return array Fields to synchronize.
	 */
	protected function get_fields_to_sync( $post ) {
		global $wpdb;

		$postarr      = parent::get_fields_to_sync( $post );
		$context_data = $this->links->get_data_from_new_post_translation_request( $post->post_type );

		// For new drafts, save the date now otherwise it is overridden by WP.
		if ( ! empty( $context_data ) && in_array( 'post_date', $this->options['sync'], true ) ) {
			unset( $postarr['post_date'] );
			unset( $postarr['post_date_gmt'] );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB query is required here because WordPress core does not provide any efficient or native way to update post dates in bulk. This raw query is necessary for performance and compatibility.
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_date'     => $context_data['from_post']->post_date,
					'post_date_gmt' => $context_data['from_post']->post_date_gmt,
				),
				array( 'ID' => $post->ID )
			);
		}

		if ( isset( $GLOBALS['post_type'] ) ) {
			$post_type = $GLOBALS['post_type'];
		} elseif ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// 2nd case for quick edit.
			$post_type = sanitize_key( $_REQUEST['post_type'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// Make sure not to impact media translations when creating them at the same time as post
		if ( in_array( 'post_parent', $this->options['sync'], true ) && ( ! isset( $post_type ) || $post_type !== $post->post_type ) ) {
			unset( $postarr['post_parent'] );
		}

		return $postarr;
	}

	/**
	 * Synchronizes post fields in translations.
	 *
	 *  
	 *
	 * @param int     $post_id      Post id.
	 * @param WP_Post $post         Post object.
	 * @param int[]   $translations Post translations.
	 */
	public function ewt_save_post( $post_id, $post, $translations ) {
		parent::ewt_save_post( $post_id, $post, $translations );

		// Sticky posts
		if ( in_array( 'sticky_posts', $this->options['sync'] ) ) {
			$stickies = get_option( 'sticky_posts' );
			if ( isset( $_REQUEST['sticky'] ) && 'sticky' === $_REQUEST['sticky'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$stickies = array_merge( $stickies, array_values( $translations ) );
			} else {
				$stickies = array_diff( $stickies, array_values( $translations ) );
			}
			update_option( 'sticky_posts', array_unique( $stickies ) );
		}
	}


}
