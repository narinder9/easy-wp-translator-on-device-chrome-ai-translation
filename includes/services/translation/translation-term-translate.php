<?php
/**
 * @package EasyWPTranslator – On-Device Chrome AI Translation
 */

namespace EasyWPTranslator\Includes\Services\Translation;

use EasyWPTranslator\Includes\Other\EWT_Language;
use WP_Error;
use WP_Term;
use Translation_Entry;
use Translations;

/**
 * Handles cloning and translation of taxonomy terms for EasyWPTranslator – On-Device Chrome AI Translation.
 *
 * Provides methods to translate taxonomy terms (categories, tags, custom taxonomies)
 * using translation entries, and to assign parent relationships for translated terms.
 *
 * @since 1.0.0
 */
class Translation_Term_Model {

	/**
	 * Main model for managing languages and translations.
	 *
	 * @var EWT_Model
	 */
	private $model;

	/**
	 * Constructor for Translation_Term_Model.
	 *
	 * @since 1.0.0
	 *
	 * @param EWT_Settings|EWT_Admin $easywptranslator Main plugin object containing model and sync references.
	 */
	public function __construct( &$easywptranslator ) {
		$this->model           = &$easywptranslator->model;
	}

	/**
	 * Translates a taxonomy term using translation entries.
	 *
	 * @since 1.0.0
	 *
	 * @param array         $entry           Array containing term properties and translation data.
	 * @param EWT_Language $target_language Target language object.
	 * @return int|WP_Error The translated term ID, or WP_Error on failure.
	 */
	public function translate( array $entry, EWT_Language $target_language ) {
		if ( ! $entry['data'] instanceof Translations ) {
			/* translators: %d is a term ID. */
			return new WP_Error( 'ewt_translate_term_no_translations', sprintf( __( 'The term with ID %d could not be translated.', 'easy-wp-translator' ), (int) $entry['id'] ) );
		}

		$source_term = get_term( $entry['id'] );

		if ( ! $source_term instanceof WP_Term ) {
			/* translators: %d is a term ID. */
			return new WP_Error( 'ewt_translate_term_no_source_term', sprintf( __( 'The term with ID %d could not be translated as it doesn\'t exist.', 'easy-wp-translator' ), (int) $entry['id'] ) );
		}

		$source_language = $this->model->term->get_language( $source_term->term_id );
		$tr_term_name        = $this->get_translated_term_name( $source_term, $entry['data'] );
		$tr_term_description = $this->get_translated_term_description( $source_term, $entry['data'] );
		$tr_term_id          = $this->model->term->get( $entry['id'], $target_language );
		$tr_term_slug        = $this->get_translated_term_slug( $source_term, $entry['data'] );

		$language_link=apply_filters('ewt_bulk_term_language_link', true);

		if ( $tr_term_id ) {
			// The translation already exists.
			$args = array();
			// Only update name or description if provided in translations.
			if ( $source_term->name !== $tr_term_name ) {
				$args['name'] = $tr_term_name;
			}
			if ( !empty( $source_term->description ) && $source_term->description !== $tr_term_description ) {
				$args['description'] = $tr_term_description;
			}

			$tr_term = $this->model->term->update( $tr_term_id, $args );
			if ( is_wp_error( $tr_term ) ) {
				/* translators: %d is a term ID. */
				return new WP_Error( 'ewt_translate_update_term_failed', sprintf( __( 'The term with ID %d could not be updated.', 'easy-wp-translator' ), (int) $tr_term_id ) );
			}
		} else {
			$args = array();

			if($language_link){
				$args['translations'] = $this->model->term->get_translations( $source_term->term_id );
			}

			if ( !empty( $source_term->description ) ) {
				$args['description'] = $tr_term_description;
			}

			if ( $tr_term_slug && ! empty( $tr_term_slug ) ) {
				$args['slug'] = $tr_term_slug;
			}

			$tr_term = $this->model->term->insert( $tr_term_name, $source_term->taxonomy, $target_language, $args );
			if ( is_wp_error( $tr_term ) ) {
				/* translators: %d is a term ID. */
				return new WP_Error( 'ewt_translate_term_failed', sprintf( __( 'The term with ID %d could not be translated.', 'easy-wp-translator' ), (int) $entry['id'] ) );
			}
			$tr_term_id = (int) $tr_term['term_id'];
		}

		/** @var WP_Term $tr_term */
		$tr_term = get_term( $tr_term_id );

		/**
		 * Fires after a term is saved and its translations are updated.
		 *
		 * @since 1.0.0
		 *
		 * @param int    $tr_term_id      The translated term ID.
		 * @param string $taxonomy        The taxonomy slug.
		 * @param array  $translations    Array of term translations.
		 */
		if($language_link){
			do_action( 'ewt_save_term', $tr_term_id, $source_term->taxonomy, $this->model->term->get_translations( $tr_term_id ) );
			$this->assign_parents( [ $entry['id'] ], $target_language );
		}

		return $tr_term_id;
	}

	/**
	 * Gets the translated term name, or falls back to the source name.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term      $source_term   The source term object.
	 * @param Translations $translations  Translation data object.
	 * @return string The translated name.
	 */
	private function get_translated_term_name( WP_Term $source_term, Translations $translations ) {
		$entry = new Translation_Entry( array( 'singular' => $source_term->name, 'context' => 'name' ) );

		$translated_entry = $translations->translate_entry( $entry );

		$translated_text = isset( $translated_entry->translation[0] ) && ! empty( $translated_entry->translation[0] ) ? $translated_entry->translation[0] : $source_term->name;

		return $translated_text;
	}

	/**
	 * Gets the translated term description, or falls back to the source description.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term      $source_term   The source term object.
	 * @param Translations $translations  Translation data object.
	 * @return string The translated description.
	 */
	private function get_translated_term_description( WP_Term $source_term, Translations $translations ) {
		$entry = new Translation_Entry( array( 'singular' => $source_term->description, 'context' => 'description' ) );

		$translated_entry = $translations->translate_entry( $entry );

		$translated_text = isset( $translated_entry->translation[0] ) && ! empty( $translated_entry->translation[0] ) ? $translated_entry->translation[0] : '';

		return $translated_text;
	}

	/**
	 * Gets the translated term slug, or returns an empty string if not available.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Term      $source_term   The source term object.
	 * @param Translations $translations  Translation data object.
	 * @return string The translated slug.
	 */
	private function get_translated_term_slug( WP_Term $source_term, Translations $translations ) {
		$entry = new Translation_Entry( array( 'singular' => $source_term->slug, 'context' => 'slug' ) );

		$translated_entry = $translations->translate_entry( $entry );

		$translated_text = isset( $translated_entry->translation[0] ) && ! empty( $translated_entry->translation[0] ) ? $translated_entry->translation[0] : '';

		return $translated_text;
	}

	/**
	 * Assigns parent terms to translated terms after import.
	 *
	 * @since 1.0.0
	 *
	 * @param int[]         $ids             Array of source term IDs.
	 * @param EWT_Language  $target_language Target language object.
	 * @return void
	 */
	public function assign_parents( array $ids, EWT_Language $target_language ) {
		// Get the terms with their parents (or 0).
		$terms = get_terms(
			array(
				'include'    => $ids,
				'hide_empty' => false,
				'fields'     => 'id=>parent',
			)
		);

		if ( ! is_array( $terms ) ) {
			// No terms with parents.
			return;
		}

		// ‘id=>parent’ returns an array of numeric strings, so let's cast it into int.
		$terms = array_map( 'intval', array_filter( $terms, 'is_numeric' ) );

		// Keep only the terms that have a parent.
		$terms = array_filter( $terms );

		if ( empty( $terms ) ) {
			// No terms with parents.
			return;
		}

		$tr_ids = array();
		foreach ( $terms as $child => $term_id ) {
			$tr_ids[ $child ] = $this->model->term->get( $child, $target_language->slug );
		}
		$tr_ids = array_filter( $tr_ids );

		if ( empty( $tr_ids ) ) {
			// No translations.
			return;
		}

		foreach ( $terms as $child => $term_id ) {
			if ( empty( $tr_ids[ $child ] ) ) {
				// Not translated.
				continue;
			}

			$tr_parent_term = $this->model->term->get( $term_id, $target_language->slug );
			if ( empty( $tr_parent_term ) ) {
				// The parent term is not translated.
				continue;
			}

			$tr_term_id = $this->model->term->get( $tr_ids[ $child ], $target_language->slug );
			if ( empty( $tr_term_id ) ) {
				continue;
			}

			$tr_term = get_term( $tr_term_id );
			if ( ! $tr_term instanceof WP_Term ) {
				continue;
			}

			// Set term parent for shared slugs.
			$this->model->term->update( $tr_term->term_id, array( 'parent' => $tr_parent_term ) );
		}
	}
}
