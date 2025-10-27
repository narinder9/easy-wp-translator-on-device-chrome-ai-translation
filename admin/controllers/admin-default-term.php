<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Helpers\EWT_Default_Term;



/**
 * Manages filters and actions related to default terms.
 *
 *  
 *   Extends `EWT_Default_Term`, most of the code is moved to it.
 */
class EWT_Admin_Default_Term extends EWT_Default_Term {

	/**
	 * Setups filters and actions needed.
	 *
	 *  
	 *
	 * @return void
	 */
	public function add_hooks() {
		parent::add_hooks();

		foreach ( $this->taxonomies as $taxonomy ) {
			if ( 'category' === $taxonomy ) {
				// Adds the language column in the 'Terms' table.
				add_filter( 'ewt_first_language_term_column', array( $this, 'first_language_column' ), 10, 2 );
			}
		}
	}

	/**
	 * Identifies the default term in the terms list table to disable the language dropdown in JS.
	 *
	 *  
	 *
	 * @param  string $out     The output.
	 * @param  int    $term_id The term id.
	 * @return string          The HTML string.
	 */
	public function first_language_column( $out, $term_id ) {
		if ( $this->is_default_term( $term_id ) ) {
			$out .= sprintf( '<div class="hidden" id="default_cat_%1$d">%1$d</div>', intval( $term_id ) );
		}

		return $out;
	}
}
