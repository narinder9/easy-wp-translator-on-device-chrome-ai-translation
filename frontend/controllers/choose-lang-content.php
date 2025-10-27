<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WP_Term;



/**
 * Choose the language when it is set from content
 * The language is set either in parse_query with priority 2 or in wp with priority 5
 *
 *  
 */
class EWT_Choose_Lang_Content extends EWT_Choose_Lang {

	/**
	 * Defers the language choice to the 'wp' action (when the content is known)
	 *
	 *  
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		if ( ! did_action( 'ewt_language_defined' ) ) {
			// Set the languages from content
			add_action( 'wp', array( $this, 'wp' ), 5 ); // Priority 5 for post types and taxonomies registered in wp hook with default priority

			// If no language found, choose the preferred one
			add_filter( 'ewt_get_current_language', array( $this, 'ewt_get_current_language' ) );
		}
	}

	/**
	 * Overwrites parent::set_language to remove the 'wp' action if the language is set before.
	 *
	 *  
	 *
	 * @param EWT_Language $curlang Current language.
	 * @return void
	 */
	protected function set_language( $curlang ) {
		parent::set_language( $curlang );
		remove_action( 'wp', array( $this, 'wp' ), 5 ); // won't attempt to set the language a 2nd time
	}

	/**
	 * Returns the language based on the queried content
	 *
	 *  
	 *
	 * @return EWT_Language|false detected language, false if none was found
	 */
	protected function get_language_from_content() {
		// No language set for 404
		if ( is_404() || ( is_attachment() && ! $this->options['media_support'] ) ) {
			return $this->get_preferred_language();
		}

		if ( $var = get_query_var( 'ewt_lang' ) ) {
			$lang = explode( ',', $var );
			$lang = $this->model->get_language( reset( $lang ) ); // Choose the first queried language
		}

		elseif ( ( is_single() || is_page() || ( is_attachment() && $this->options['media_support'] ) ) && ( ( $var = get_queried_object_id() ) || ( $var = get_query_var( 'p' ) ) || ( $var = get_query_var( 'page_id' ) ) || ( $var = get_query_var( 'attachment_id' ) ) ) && is_numeric( $var ) ) {
			$lang = $this->model->post->get_language( (int) $var );
		}

		else {
			foreach ( $this->model->get_translated_taxonomies() as $taxonomy ) {
				$tax_object = get_taxonomy( $taxonomy );

				if ( empty( $tax_object ) ) {
					continue;
				}

				$var = get_query_var( $tax_object->query_var );

				if ( ! is_string( $var ) || empty( $var ) ) {
					continue;
				}

				$term = get_term_by( 'slug', $var, $taxonomy );

				if ( ! $term instanceof WP_Term ) {
					continue;
				}

				$lang = $this->model->term->get_language( $term->term_id );
			}
		}

		/**
		 * Filters the language before it is set from the content.
		 *
		 *  
		 *
		 * @param EWT_Language|false $lang Language object or false if none was found.
		 */
		return apply_filters( 'ewt_get_current_language', $lang ?? false );
	}

	/**
	 * Sets the language for the home page.
	 * Adds the lang query var when querying archives with no language code.
	 *
	 *  
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return void
	 */
	public function parse_main_query( $query ) {
		if ( empty( $GLOBALS['wp_the_query'] ) || $query !== $GLOBALS['wp_the_query'] ) {
			return;
		}

		$qv = $query->query_vars;

		// Homepage is requested, let's set the language
		// Take care to avoid posts page for which is_home = 1
		if ( empty( $query->query ) && ( is_home() || is_page() ) ) {
			$this->home_language();
			$this->home_requested();
		}

		parent::parse_main_query( $query );

		$is_archive = ( count( $query->query ) == 1 && ! empty( $qv['paged'] ) ) ||
			$query->is_date ||
			$query->is_author ||
			( ! empty( $qv['post_type'] ) && $query->is_post_type_archive && $this->model->is_translated_post_type( $qv['post_type'] ) );

		// Sets the language in case we hide the default language
		// Use $query->query['s'] as is_search is not set when search is empty
		if ( $this->options['hide_default'] && ! isset( $qv['ewt_lang'] ) && ( $is_archive || isset( $query->query['s'] ) || ( count( $query->query ) == 1 && ! empty( $qv['feed'] ) ) ) ) {
			$this->set_language( $this->model->get_default_language() );
			$this->set_curlang_in_query( $query );
		}
	}

	/**
	 * Sets the language from content
	 *
	 *  
	 *
	 * @return void
	 */
	public function wp() {
		// Nothing to do if the language has already been set ( although normally the filter has been removed )
		if ( empty( $this->curlang ) && $curlang = $this->get_language_from_content() ) {
			parent::set_language( $curlang );
		}
	}

	/**
	 * If no language is found by {@see EWT_Choose_Lang_Content::get_language_from_content()}, returns the preferred one.
	 *
	 *  
	 *
	 * @param EWT_Language|false $lang Language found by {@see EWT_Choose_Lang_Content::get_language_from_content()}.
	 * @return EWT_Language|false
	 */
	public function ewt_get_current_language( $lang ) {
		return ! $lang ? $this->get_preferred_language() : $lang;
	}
}
