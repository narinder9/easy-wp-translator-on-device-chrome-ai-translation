<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Options\Business;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Options\Primitive\Abstract_List;

/**
 * Class defining language switcher options list.
 *
 *  
 */
class Language_Switcher_Options extends Abstract_List {
	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return 'ewt_language_switcher_options'
	 */
	public static function key(): string {
		return 'ewt_language_switcher_options';
	}

	/**
	 * Returns the default value.
	 *
	 *  
	 *
	 * @return array
	 */
	protected function get_default() {
		return array( 'default' );
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'List of enabled language switcher types.', 'easy-wp-translator' );
	}
}
