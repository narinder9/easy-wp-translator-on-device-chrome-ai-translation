<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Options\Business;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use WP_Error;
use EasyWPTranslator\Includes\Options\Primitive\Abstract_Boolean;
use EasyWPTranslator\Includes\Options\Options;



/**
 * Class defining the "Remove /language/ in pretty permalinks" boolean option.
 *
 *  
 */
class Rewrite extends Abstract_Boolean {
	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return 'rewrite'
	 */
	public static function key(): string {
		return 'rewrite';
	}

	/**
	 * Adds information to the site health info array.
	 *
	 *
	 * @param array   $info    The current site health information.
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function add_to_site_health_info( array $info, Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		if ( $this->get() ) {
			$value = '1: ' . sprintf(
				/* translators: %s is a URL slug: `/language/`. */
				__( 'Remove %s in pretty permalinks', 'easy-wp-translator' ),
				'`/language/`'
			);
		} else {
			$value = '0: ' . sprintf(
				/* translators: %s is a URL slug: `/language/`. */
				__( 'Keep %s in pretty permalinks', 'easy-wp-translator' ),
				'`/language/`'
			);
		}

		return $this->get_site_health_info( $info, $value, self::key() );
	}

	/**
	 * Returns the default value.
	 *
	 *  
	 *
	 * @return bool
	 */
	protected function get_default() {
		return true;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return sprintf(
			/* translators: %1$s is a URL slug: `/language/`. %2$s and %3$s are "true/false" values. */
			__( 'Remove %1$s in pretty permalinks: %2$s to remove, %3$s to keep.', 'easy-wp-translator' ),
			'`/language/`',
			'`true`',
			'`false`'
		);
	}
}
