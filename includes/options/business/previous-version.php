<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Options\Business;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class defining the "previous version" option.
 *
 *  
 */
class Previous_Version extends Version {
	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return 'previous_version'
	 */
	public static function key(): string {
		return 'previous_version';
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( "EasyWPTranslator's previous version.", 'easy-wp-translator' );
	}
}
