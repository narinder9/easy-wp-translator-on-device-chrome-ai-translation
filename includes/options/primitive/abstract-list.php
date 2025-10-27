<?php
namespace EasyWPTranslator\Includes\Options\Primitive;
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Options\Abstract_Option;



/**
 * Class defining single list option, default value type to mixed.
 *
 *  
 *
 * @phpstan-import-type SchemaType from Abstract_Option
 */
abstract class Abstract_List extends Abstract_Option {
	/**
	 * Prepares a value before validation.
	 * Allows to receive a string-keyed array but returns an integer-keyed array.
	 *
	 *  
	 *
	 * @param mixed $value Value to format.
	 * @return mixed
	 */
	protected function prepare( $value ) {
		if ( is_array( $value ) ) {
			return array_values( array_unique( $value ) );
		}
		return $value;
	}

	/**
	 * Returns the JSON schema value type for the list items.
	 * Possible values are `'string'`, `'null'`, `'number'` (float), `'integer'`, `'boolean'`,
	 * `'array'` (array with integer keys), and `'object'` (array with string keys).
	 *
	 *
	 * @return string
	 *
	 * @phpstan-return SchemaType
	 */
	protected function get_type(): string {
		return 'string';
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
	 * @phpstan-return array{type: 'array', items: array{type: SchemaType}}
	 */
	protected function get_data_structure(): array {
		return array(
			'type'  => 'array',
			'items' => array(
				'type' => $this->get_type(),
			),
		);
	}
}
