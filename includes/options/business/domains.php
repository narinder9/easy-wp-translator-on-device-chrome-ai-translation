<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Options\Business;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Options\Abstract_Option;
use EasyWPTranslator\Includes\Options\Options;
use EasyWPTranslator\Includes\Models\Languages;



/**
 * Class defining single associative array of domain as value and language slug as key option.
 * /!\ Sanitization depends on `force_lang`: this option must be set AFTER `force_lang`.
 *
 *  
 *
 * @phpstan-type DomainsValue array<non-falsy-string, string>
 */
class Domains extends Abstract_Option {
	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return 'domains'
	 */
	public static function key(): string {
		return 'domains';
	}

	/**
	 * Returns the default value.
	 *
	 *  
	 *
	 * @return array
	 */
	protected function get_default() {
		return array();
	}

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 *  
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{
	 *     type: 'object',
	 *     patternProperties: non-empty-array<non-empty-string, array{type: 'string', format: 'uri'}>,
	 *     additionalProperties: false
	 * }
	 */
	protected function get_data_structure(): array {
		return array(
			'type'                 => 'object', // Correspond to associative array in PHP.
			'patternProperties'    => array(
				Languages::SLUG_PATTERN => array( // Language slug as key.
					'type'   => 'string',
					'format' => 'uri',
				),
			),
			'additionalProperties' => false,
		);
	}

	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 *  
	 *
	 * @param array   $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return array|WP_Error The sanitized value. An instance of `WP_Error` in case of blocking error.
	 *
	 * @phpstan-return DomainsValue|WP_Error
	 */
	protected function sanitize( $value, Options $options ) {
		// Sanitize new URLs.
		$value = parent::sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			// Blocking error.
			return $value;
		}

		/** @phpstan-var DomainsValue */
		$current_value = $this->get();
		/** @phpstan-var DomainsValue $value */
		$all_values     = array(); // Previous and new values.
		$missing_langs  = array(); // Lang names corresponding to the empty values.
		$language_terms = $this->get_language_terms();

		// Detect empty values, fill missing keys with previous values.
		foreach ( $language_terms as $lang ) {
			if ( array_key_exists( $lang->slug, $value ) ) {
				// Use the new value.
				$all_values[ $lang->slug ] = $value[ $lang->slug ];
				unset( $value[ $lang->slug ] );
			} else {
				// Use previous value.
				$all_values[ $lang->slug ] = $current_value[ $lang->slug ] ?? '';
			}

			if ( empty( $all_values[ $lang->slug ] ) ) {
				// The value is empty.
				$missing_langs[] = $lang->name;
			}
		}

		// Detect invalid language slugs.
		if ( ! empty( $value ) ) {
			// Non-blocking error.
			$this->add_unknown_languages_warning( array_keys( $value ) );
		}

		if ( 3 === $options->get( 'force_lang' ) && ! empty( $missing_langs ) ) {
			// Non-blocking error.
			if ( 1 === count( $missing_langs ) ) {
				/* translators: %s is a native language name. */
				$message = __( 'Please enter a valid URL for %s.', 'easy-wp-translator' );
			} else {
				/* translators: %s is a list of native language names. */
				$message = __( 'Please enter valid URLs for %s.', 'easy-wp-translator' );
			}

			$this->errors->add(
				'ewt_empty_domains',
				sprintf( $message, wp_sprintf_l( '%l', $missing_langs ) ),
				'warning'
			);
		}

		// Domain accessibility check has been moved to REST API validation (before save)
		// This prevents duplicate validation and ensures blocking errors instead of warnings

		/** @phpstan-var DomainsValue */
		return $all_values;
	}

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'Domains used when the language is set from different domains.', 'easy-wp-translator' );

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
		if ( 3 === $options->get( 'force_lang' ) ) {
			return $this->get_site_health_info( $info, $this->get(), self::key() );
				}

		return $info;
	}
}
