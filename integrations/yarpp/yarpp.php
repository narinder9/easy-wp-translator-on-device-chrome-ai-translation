<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\yarpp;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the compatibility with Yet Another Related Posts Plugin.
 *
 *  
 */
class EWT_Yarpp {
	/**
	 * Just makes YARPP aware of the language taxonomy ( after EasyWPTranslator registered it ).
	 *
	 *  
	 */
	public function init() {
		$GLOBALS['wp_taxonomies']['ewt_language']->yarpp_support = 1;
	}
}
