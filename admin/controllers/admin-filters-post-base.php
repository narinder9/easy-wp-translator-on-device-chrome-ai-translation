<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Some common code for EWT_Admin_Filters_Post and EWT_Admin_Filters_Media
 *
 *  
 */
abstract class EWT_Admin_Filters_Post_Base {
	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * @var EWT_Admin_Links
	 */
	public $links;

	/**
	 * Language selected in the admin language filter.
	 *
	 * @var EWT_Language|null
	 */
	public $filter_lang;

	/**
	 * Preferred language to assign to new contents.
	 *
	 * @var EWT_Language|null
	 */
	public $pref_lang;

	/**
	 * Constructor: setups filters and actions
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		$this->links = &$easywptranslator->links;
		$this->model = &$easywptranslator->model;
		$this->pref_lang = &$easywptranslator->pref_lang;
	}

	/**
	 * Save translations from the languages metabox.
	 *
	 *  
	 *
	 * @param int   $post_id Post id of the post being saved.
	 * @param int[] $arr     An array with language codes as key and post id as value.
	 * @return int[] The array of translated post ids.
	 */
	protected function save_translations( $post_id, $arr ) {
		// Security check as 'wp_insert_post' can be called from outside WP admin.
		check_admin_referer( 'ewt_language', '_ewt_nonce' );

		$translations = $this->model->post->save_translations( $post_id, $arr );
		return $translations;
	}
}
