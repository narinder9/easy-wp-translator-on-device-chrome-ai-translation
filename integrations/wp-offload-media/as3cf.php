<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\wp_offload_media;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A class to manage the integration with WP Offload Media Lite.
 * Version tested: 2.1.1
 *
 *  
 */
class EWT_AS3CF {
	/**
	 * Stores if a media is translated when it is deleted.
	 *
	 * @var bool[]
	 */
	private $is_media_translated = array();

	/**
	 * Initializes filters and actions.
	 *
	 *  
	 */
	public function init() {
		add_filter( 'ewt_copy_post_metas', array( $this, 'copy_post_metas' ) );
		add_action( 'delete_attachment', array( $this, 'check_translated_media' ), 5 ); // Before EasyWPTranslator deletes the translations information.
		add_action( 'delete_attachment', array( $this, 'prevent_file_deletion' ), 15 ); // Between EasyWPTranslator and WP Offload Media.
	}

	/**
	 * Synchronizes post metas
	 *
	 *  
	 *
	 * @param array $metas List of custom fields names.
	 * @return array
	 */
	public function copy_post_metas( $metas ) {
		$metas[] = 'amazonS3_info';
		$metas[] = 'as3cf_filesize_total';
		return $metas;
	}

	/**
	 * Checks if the deleted attachment was translated and stores the information.
	 *
	 *  
	 *
	 * @param int $post_id Id of the attachment being deleted.
	 */
	public function check_translated_media( $post_id ) {
		$this->is_media_translated[ $post_id ] = ( count( ewt_get_post_translations( $post_id ) ) > 1 );
	}

	/**
	 * Deletes the WP Offload Media information from the attachment being deleted.
	 * That way WP Offload Media won't delete the file stored in the cloud.
	 * Done after EasyWPTranslator has deleted the translations information, to avoid the synchronization of the deletion
	 * and of course before WP Offload Media deletes the file, normally at priority 20.
	 *
	 *  
	 *
	 * @param int $post_id Id of the attachment being deleted.
	 */
	public function prevent_file_deletion( $post_id ) {
		if ( ! empty( $this->is_media_translated[ $post_id ] ) ) {
			delete_post_meta( $post_id, 'amazonS3_info' );
			delete_post_meta( $post_id, 'as3cf_filesize_total' );
		}
	}
}
