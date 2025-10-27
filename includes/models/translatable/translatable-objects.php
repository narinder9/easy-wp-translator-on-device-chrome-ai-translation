<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Models\Translatable;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Models\Translated\EWT_Translated_Object;


/**
 * Registry for all translatable objects.
 *
 *  
 *
 * @phpstan-implements IteratorAggregate<non-empty-string, EWT_Translatable_Object>
 * @phpstan-type TranslatedObjectWithTypes EWT_Translated_Object&EWT_Translatable_Object_With_Types_Interface
 * @phpstan-type TranslatableObjectWithTypes EWT_Translatable_Object&EWT_Translatable_Object_With_Types_Interface
 */
class EWT_Translatable_Objects implements \IteratorAggregate {

	/**
	 * Type of the main translatable object.
	 *
	 * @var string
	 */
	private $main_type = '';

	/**
	 * List of registered objects.
	 *
	 * @var EWT_Translatable_Object[] Array keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-var array<non-empty-string, EWT_Translatable_Object>
	 */
	private $objects = array();

	/**
	 * Registers a translatable object.
	 *
	 *  
	 *
	 * @param EWT_Translatable_Object $object The translatable object to register.
	 * @return EWT_Translatable_Object
	 *
	 * @phpstan-return (
	 *     $object is EWT_Translated_Post ? EWT_Translated_Post : (
	 *         $object is EWT_Translated_Term ? EWT_Translated_Term : (
	 *             EWT_Translatable_Object
	 *         )
	 *     )
	 * )
	 */
	public function register( EWT_Translatable_Object $object ) {
		if ( empty( $this->main_type ) ) {
			$this->main_type = $object->get_type();
		}

		if ( ! isset( $this->objects[ $object->get_type() ] ) ) {
			$this->objects[ $object->get_type() ] = $object;
		}

		return $this->objects[ $object->get_type() ];
	}

	/**
	 * Returns all registered translatable objects.
	 *
	 *  
	 *
	 * @return ArrayIterator Iterator on $objects array property. Keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-return ArrayIterator<string, EWT_Translatable_Object>
	 */
	#[\ReturnTypeWillChange]
	public function getIterator() {
		return new \ArrayIterator( $this->objects );
	}

	/**
	 * Returns a translatable object, given an object type.
	 *
	 *  
	 *
	 * @param string $object_type The object type.
	 * @return EWT_Translatable_Object|null
	 *
	 * @phpstan-return (
	 *     $object_type is 'post' ? TranslatedObjectWithTypes : (
	 *         $object_type is 'term' ? TranslatedObjectWithTypes : (
	 *             TranslatedObjectWithTypes|TranslatableObjectWithTypes|EWT_Translated_Object|EWT_Translatable_Object|null
	 *         )
	 *     )
	 * )
	 */
	public function get( $object_type ) {
		if ( ! isset( $this->objects[ $object_type ] ) ) {
			return null;
		}

		return $this->objects[ $object_type ];
	}

	/**
	 * Returns all translatable objects except post one.
	 *
	 *  
	 *
	 * @return EWT_Translatable_Object[] An array of secondary translatable objects. Array keys are the type of translated content (post, term, etc).
	 *
	 * @phpstan-return array<non-empty-string, EWT_Translatable_Object>
	 */
	public function get_secondary_translatable_objects() {
		return array_diff_key( $this->objects, array( $this->main_type => null ) );
	}

	/**
	 * Returns taxonomy names to manage language and translations.
	 *
	 *  
	 *
	 * @param string[] $filter An array on value to filter taxonomy names to return.
	 * @return string[] Taxonomy names.
	 *
	 * @phpstan-param array<'language'|'translations'> $filter
	 * @phpstan-return list<non-empty-string>
	 */
	public function get_taxonomy_names( $filter = array( 'language', 'translations' ) ) {
		$taxonomies = array();

		foreach ( $this->objects as $object ) {
			if ( in_array( 'language', $filter, true ) ) {
				$taxonomies[] = $object->get_tax_language();
			}

			if ( in_array( 'translations', $filter, true ) && $object instanceof EWT_Translated_Object ) {
				$taxonomies[] = $object->get_tax_translations();
			}
		}

		return $taxonomies;
	}
}
