<?php
namespace EasyWPTranslator\Includes\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_Error;

/**
 * @package EasyWPTranslator
 */


use EasyWPTranslator\Includes\Options\Options;



/**
 * Class defining a single option.
 *
 *
 * @phpstan-type SchemaType 'string'|'null'|'number'|'integer'|'boolean'|'array'|'object'
 * @phpstan-type Schema array{
 *     type: SchemaType
 * }
 */
abstract class Abstract_Option {
	/**
	 * Option value.
	 *
	 * @var mixed
	 */
	private $value;

	/**
	 * Cached option JSON schema.
	 *
	 * @var array|null
	 *
	 * @phpstan-var Schema|null
	 */
	private $schema;

	/**
	 * Validation and sanitization errors.
	 *
	 * @var WP_Error
	 */
	protected $errors;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param mixed $value Optional. Option value.
	 */
	public function __construct( $value = null ) {
		$this->errors = new WP_Error();

		if ( ! isset( $value ) ) {
			$this->value = $this->get_default();
			return;
		}

		$value = rest_sanitize_value_from_schema( $this->prepare( $value ), $this->get_data_structure(), static::key() );

		if ( ! is_wp_error( $value ) ) {
			$this->value = $value;
		} else {
			$this->value = $this->get_default();
		}
	}

	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return non-falsy-string
	 */
	abstract public static function key(): string;

	/**
	 * Sets option's value if valid, does nothing otherwise.
	 *
	 *  
	 *
	 * @param mixed   $value   Value to set.
	 * @param Options $options All options.
	 * @return bool True if the value has been assigned. False in case of errors.
	 */
	public function set( $value, Options $options ): bool {
		$this->errors = new WP_Error(); // Reset errors.
		$value        = $this->prepare( $value );
		$is_valid     = rest_validate_value_from_schema( $value, $this->get_data_structure(), static::key() );

		if ( is_wp_error( $is_valid ) ) {
			// Blocking validation error.
			$this->errors->merge_from( $is_valid );
			return false;
		}

		$value = $this->sanitize( $value, $options );

		if ( is_wp_error( $value ) ) {
			// Blocking sanitization error.
			$this->errors->merge_from( $value );
			return false;
		}

		$this->value = $value;
		return true;
	}

	/**
	 * Returns option's value.
	 *
	 *  
	 *
	 * @return mixed
	 */
	public function &get() {
		return $this->value;
	}

	/**
	 * Sets default option value.
	 *
	 *  
	 *
	 * @return mixed The new value.
	 */
	public function reset() {
		$this->value = $this->get_default();
		return $this->value;
	}

	/**
	 * Returns JSON schema of the option.
	 *
	 *  
	 *
	 * @return array The schema.
	 *
	 * @phpstan-return Schema
	 */
	public function get_schema(): array {
		if ( is_array( $this->schema ) ) {
			return $this->schema;
		}

		$this->schema = array_merge(
			array(
				'description' => $this->get_description(),
				'default'     => $this->get_default(),
			),
			$this->get_data_structure()
		);

		return $this->schema;
	}

	/**
	 * Returns non-blocking sanitization errors.
	 *
	 *  
	 *
	 * @return WP_Error
	 */
	public function get_errors(): WP_Error {
		return $this->errors;
	}

	/**
	 * Prepares a value before validation.
	 *
	 *  
	 *
	 * @param mixed $value Value to format.
	 * @return mixed
	 */
	protected function prepare( $value ) {
		return $value;
	}

	/**
	 * Sanitizes option's value, can be overridden for specific cases not handled by `rest_sanitize_value_from_schema()`.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 *  
	 *
	 * @param mixed   $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return mixed The sanitized value. An instance of `WP_Error` in case of blocking error.
	 */
	protected function sanitize( $value, Options $options ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return rest_sanitize_value_from_schema( $value, $this->get_data_structure(), static::key() );
	}

	/**
	 * Returns the default value.
	 *
	 *  
	 *
	 * @return mixed
	 */
	abstract protected function get_default();

	/**
	 * Returns the JSON schema part specific to this option.
	 *
	 *  
	 *
	 * @return array Partial schema.
	 *
	 * @phpstan-return array{type: SchemaType}
	 */
	abstract protected function get_data_structure(): array;

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	abstract protected function get_description(): string;

	/**
	 * Returns a list of language terms.
	 *
	 *  
	 *
	 * @return array
	 *
	 * @phpstan-return list<WP_Term>
	 */
	protected function get_language_terms(): array {
		$language_terms = get_terms(
			array(
				'taxonomy'   => 'ewt_language',
				'hide_empty' => false,
			)
		);
		return is_array( $language_terms ) ? $language_terms : array();
	}

	/**
	 * Adds a non-blocking error warning about unknown language slugs.
	 *
	 *  
	 *
	 * @param array $language_slugs List of language slugs.
	 * @return void
	 */
	protected function add_unknown_languages_warning( array $language_slugs ): void {
		if ( 1 === count( $language_slugs ) ) {
			/* translators: %s is a language slug. */
			$message = __( 'The language %s is unknown and has been discarded.', 'easy-wp-translator' );
		} else {
			/* translators: %s is a list of language slugs. */
			$message = __( 'The languages %s are unknown and have been discarded.', 'easy-wp-translator' );
		}

		$this->errors->add(
			sprintf( 'ewt_unknown_%s_languages', static::key() ),
			sprintf(
				$message,
				wp_sprintf_l(
					'%l',
					array_map(
						function ( $slug ) {
							return "<code>{$slug}</code>";
						},
						$language_slugs
					)
				)
			),
			'warning'
		);
	}

	/**
	 * Adds information to the site health info array.
	 *  * Does nothing by default.
	 *
	 *
	 * @param array   $info    The current site health information.
	 * @param Options $options An instance of the Options class providing additional configuration.
	 *
	 * @return array The updated site health information.
	 */
	public function get_site_health_info( Options $options ): array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		return array();
	}

	/**
	 * Renders site health information by appending additional fields.
	 *
	 *
	 * @param array  $info  An array containing existing site health information.
	 * @param mixed  $value The value to be added to the site health fields.
	 * @param string $key   The key used to identify the added field.
	 *
	 * @return array Updated array of site health information including the new fields.
	 */
	protected function format_single_value_for_site_health_info( mixed $value ): array {
		return array(
			'label' => static::key(),
			'value' => $value,
		);
	}

	/**
	 * Formats an array to display in options information.
	 *
	 *
	 * @param array $array An array of formatted data.
	 * @return string
	 */
	protected function format_array_for_site_health_info( array $array ): string {
		array_walk(
			$array,
			function ( &$value, $key ) {
				if ( is_array( $value ) ) {
					$ids = implode( ' , ', $value );
					$value = "$key => $ids";
				}
				else {
					$value = "$key => $value";
				}
			}
		);
		return implode( ' | ', $array );
	}
}
