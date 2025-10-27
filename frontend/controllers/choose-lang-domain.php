<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Choose the language when the language is managed by different domains
 *
 *  
 */
class EWT_Choose_Lang_Domain extends EWT_Choose_Lang_Url {

	/**
	 * Don't set any language cookie
	 *
	 *  
	 *
	 * @return void
	 */
	public function maybe_setcookie() {}

	/**
	 * Don't redirect according to browser preferences
	 *
	 *  
	 *
	 * @return EWT_Language
	 */
	public function get_preferred_language() {
		return $this->model->get_language( $this->links_model->get_language_from_url() );
	}

	/**
	 * Adds query vars to query for home pages in all languages
	 *
	 *  
	 *
	 * @return void
	 */
	public function home_requested() {
		$this->set_curlang_in_query( $GLOBALS['wp_query'] );
		/** This action is documented in include/choose-lang.php */
		do_action( 'ewt_home_requested' );
	}
}
