<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\no_category_base;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the compatibility with No Category Base.
 * Works for Yoast SEO too.
 *
 *  
 */
class EWT_No_Category_Base {
	/**
	 * Setups actions.
	 *
	 *  
	 */
	public function init() {
		add_filter( 'get_terms_args', array( $this, 'no_category_base_get_terms_args' ), 5 ); // Before adding our cache domain.
	}

	/**
	 * Make sure No category base plugins get all the categories when flushing rules.
	 *
	 *  
	 *
	 * @param array $args WP_Term_Query arguments.
	 * @return array
	 */
	public function no_category_base_get_terms_args( $args ) {
		if ( doing_filter( 'category_rewrite_rules' ) ) {
			$args['lang'] = '';
		}
		return $args;
	}
}
