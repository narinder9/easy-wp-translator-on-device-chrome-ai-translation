<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\aqua_resizer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the compatibility with Aqua Resizer when used in themes.
 *
 *  
 */
class EWT_Aqua_Resizer {
	/**
	 * Setups filters.
	 *
	 *  
	 */
	public function init() {
		add_filter( 'ewt_home_url_black_list', array( $this, 'home_url_black_list' ) );
	}

	/**
	 * Avoids filtering the home url for the function aq_resize().
	 *
	 *  
	 *
	 * @param array $arr Home url filter black list.
	 * @return array
	 */
	public function home_url_black_list( $arr ) {
		return array_merge( $arr, array( array( 'function' => 'aq_resize' ) ) );
	}
}
