<?php
namespace EasyWPTranslator\Includes\Walkers;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use Walker;
use EasyWPTranslator\Includes\Other\EWT_Language;



/**
 * @package EasyWPTranslator
 */
/**
 * A class for displaying various tree-like language structures.
 *
 * Extend the `EWT_Walker` class to use it, and implement some of the methods from `Walker`.
 *
 *  
 */
class EWT_Walker extends Walker {
	/**
	 * Database fields to use.
	 *
	 *
	 * @var string[]
	 */
	public $db_fields = array( 'parent' => 'parent', 'id' => 'id' );

	/**
	 * Overrides Walker::display_element as it expects an object with a parent property.
	 *
	 *
	 * @param EWT_Language|stdClass $element           Data object. `EWT_language` in our case.
	 * @param array                 $children_elements List of elements to continue traversing.
	 * @param int                   $max_depth         Max depth to traverse.
	 * @param int                   $depth             Depth of current element.
	 * @param array                 $args              An array of arguments.
	 * @param string                $output            Passed by reference. Used to append additional content.
	 * @return void
	 */
	public function display_element( $element, &$children_elements, $max_depth, $depth, $args, &$output ) {
		if ( $element instanceof EWT_Language ) {
			$element = $element->to_std_class();

			// Sets the w3c locale as the main locale.
			$element->locale = $element->w3c ?? $element->locale;
		}

		$element->parent = $element->id = 0; // Don't care about this.

		parent::display_element( $element, $children_elements, $max_depth, $depth, $args, $output );
	}

	/**
	 * Sets `EWT_Walker::walk()` arguments as it should
	 * and triggers an error in case of misuse of them.
	 *
	 *  
	 *
	 * @param array|int $max_depth The maximum hierarchical depth. Passed by reference.
	 * @param array     $args      Additional arguments. Passed by reference.
	 * @return void
	 */
	protected function maybe_fix_walk_args( &$max_depth, &$args ) {
		if ( ! is_array( $max_depth ) ) {
			$args = $args[0] ?? array();
			return;
		}
	}
}
