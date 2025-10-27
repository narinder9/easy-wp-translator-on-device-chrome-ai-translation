<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Modules\sync;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Settings\Controllers\EWT_Settings_Module;



/**
 * Settings class for synchronization settings management
 *
 *  
 */
class EWT_Settings_Sync extends EWT_Settings_Module {
	/**
	 * Stores the display order priority.
	 *
	 * @var int
	 */
	public $priority = 50;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param object $easywptranslator The easywptranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		parent::__construct(
			$easywptranslator,
			array(
				'module'      => 'sync',
				'title'       => __( 'Synchronization', 'easy-wp-translator' ),
				'description' => __( 'The synchronization options allow to maintain exact same values (or translations in the case of taxonomies and page parent) of meta content between the translations of a post or page.', 'easy-wp-translator' ),
			)
		);
	}

	/**
	 * Deactivates the module
	 *
	 *  
	 */
	public function deactivate() {
		$this->options['sync'] = array();
	}


	/**
	 * Prepare the received data before saving.
	 *
	 *  
	 *
	 * @param array $options Raw values to save.
	 * @return array
	 */
	protected function prepare_raw_data( array $options ): array {
		// Take care to return only validated options.
		return array( 'sync' => empty( $options['sync'] ) ? array() : array_keys( $options['sync'], 1 ) );
	}

	/**
	 * Get the row actions.
	 *
	 *  
	 *
	 * @return string[] Row actions.
	 */
	protected function get_actions() {
		return empty( $this->options['sync'] ) ? array( 'configure' ) : array( 'configure', 'deactivate' );
	}

	/**
	 * Get the list of synchronization settings.
	 *
	 *  
	 *
	 * @return string[] Array synchronization options.
	 *
	 * @phpstan-return non-empty-array<non-falsy-string, string>
	 */
	public static function list_metas_to_sync() {
		return array(
			'taxonomies'        => __( 'Taxonomies', 'easy-wp-translator' ),
			'post_meta'         => __( 'Custom fields', 'easy-wp-translator' ),
			'comment_status'    => __( 'Comment status', 'easy-wp-translator' ),
			'ping_status'       => __( 'Ping status', 'easy-wp-translator' ),
			'sticky_posts'      => __( 'Sticky posts', 'easy-wp-translator' ),
			'post_date'         => __( 'Published date', 'easy-wp-translator' ),
			'post_format'       => __( 'Post format', 'easy-wp-translator' ),
			'post_parent'       => __( 'Page parent', 'easy-wp-translator' ),
			'_wp_page_template' => __( 'Page template', 'easy-wp-translator' ),
			'menu_order'        => __( 'Page order', 'easy-wp-translator' ),
			'_thumbnail_id'     => __( 'Featured image', 'easy-wp-translator' ),
		);
	}
}
