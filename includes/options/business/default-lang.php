<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Options\Business;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Models\Languages;
use EasyWPTranslator\Includes\Options\Options;
use EasyWPTranslator\Includes\Options\Primitive\Abstract_String;
use WP_Error;



/**
 * Class defining language slug string option.
 *
 *  
 */
class Default_Lang extends Abstract_String {
	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return 'default_lang'
	 */
	public static function key(): string {
		return 'default_lang';
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 *  
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: 'string', pattern: Languages::SLUG_PATTERN}
	 */
	protected function get_data_structure(): array {
		$string_schema            = parent::get_data_structure();
		$string_schema['pattern'] = Languages::SLUG_PATTERN;

		return $string_schema;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Slug of the default language.', 'easy-wp-translator' );
	}

	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 *  
	 *
	 * @param string  $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return string|WP_Error The sanitized value. An instance of `WP_Error` in case of error.
	 */
	protected function sanitize( $value, Options $options ) {
		$value = parent::sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			return $value;
		}

		/** @var string $value */
		if ( ! get_term_by( 'slug', $value, 'ewt_language' ) ) {
			return new WP_Error( 'ewt_invalid_language', sprintf( 'The language slug \'%s\' is not a valid language.', $value ) );
		}

		return $value;
	}
}
