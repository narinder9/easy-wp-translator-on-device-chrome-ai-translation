<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Services\Links;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Other\EWT_Language;



/**
 * Manages links related functions
 *
 *  
 */
class EWT_Links {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * Instance of a child class of EWT_Links_Model.
	 *
	 * @var EWT_Links_Model
	 */
	public $links_model;

	/**
	 * Current language (used to filter the content).
	 *
	 * @var EWT_Language|null
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		$this->links_model = &$easywptranslator->links_model;
		$this->model = &$easywptranslator->model;
		$this->options = &$easywptranslator->options;
	}

	/**
	 * Returns the home url in the requested language.
	 *
	 *  
	 *
	 * @param EWT_Language|string $language  The language.
	 * @param bool                $is_search Optional, whether we need the home url for a search form, defaults to false.
	 * @return string
	 */
	public function get_home_url( $language, $is_search = false ) {
		if ( ! $language instanceof EWT_Language ) {
			$language = $this->model->get_language( $language );
		}

		if ( empty( $language ) ) {
			return home_url( '/' );
		}

		return $is_search ? $language->get_search_url() : $language->get_home_url();
	}
}
