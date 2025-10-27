<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Services;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Services\Links\EWT_Links;
use EasyWPTranslator\Includes\Helpers\EWT_Cache;
use WP_Term;


/**
 * Manages links filters and url of translations on frontend
 *
 *  
 */
class EWT_Frontend_Links extends EWT_Links {

	/**
	 * Internal non persistent cache object.
	 *
	 * @var EWT_Cache<string>
	 */
	public $cache;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		parent::__construct( $easywptranslator );

		$this->curlang = &$easywptranslator->curlang;
		$this->cache = new EWT_Cache();
	}

	/**
	 * Returns the url of the translation (if it exists) of the current page.
	 *
	 *  
	 *
	 * @param EWT_Language $language Language object.
	 * @return string
	 */
	public function get_translation_url( $language ) {
		// Validate that $language is an object with slug property
		if ( ! is_object( $language ) || ! isset( $language->slug ) ) {
			return '';
		}

		global $wp_query;

		if ( false !== $translation_url = $this->cache->get( 'translation_url:' . $language->slug ) ) {
			return $translation_url;
		}

		// Make sure that we have the queried object
		$queried_object_id = $wp_query->get_queried_object_id();

		/**
		 * Filters the translation url before EasyWPTranslator attempts to find one.
		 * Internally used by EasyWPTranslator for the static front page and posts page.
		 *
		 *  
		 *
		 * @param string       $url               Empty string or the url of the translation of the current page.
		 * @param EWT_Language $language          Language of the translation.
		 * @param int          $queried_object_id Queried object ID.
		 */
		if ( ! $url = apply_filters( 'ewt_pre_translation_url', '', $language, $queried_object_id ) ) {
			$qv = $wp_query->query_vars;

			// Post and attachment
			if ( is_single() && ( $this->options['media_support'] || ! is_attachment() ) && ( $id = $this->model->post->get( $queried_object_id, $language ) ) && $this->model->post->current_user_can_read( $id ) ) {
				$url = get_permalink( $id );
			}

			// Page
			elseif ( is_page() && ( $id = $this->model->post->get( $queried_object_id, $language ) ) && $this->model->post->current_user_can_read( $id ) ) {
				$url = get_page_link( $id );
			}

			elseif ( is_search() ) {
				$url = $this->get_archive_url( $language );

				// Special case for search filtered by translated taxonomies: taxonomy terms are translated in the translation url
				if ( ! empty( $wp_query->tax_query->queries ) ) {
					foreach ( $wp_query->tax_query->queries as $tax_query ) {
						if ( ! empty( $tax_query['taxonomy'] ) && $this->model->is_translated_taxonomy( $tax_query['taxonomy'] ) ) {

							$tax = get_taxonomy( $tax_query['taxonomy'] );
							$terms = get_terms( array( 'taxonomy' => $tax->name, 'fields' => 'id=>slug' ) ); // Filtered by current language

							foreach ( $tax_query['terms'] as $slug ) {
								$term_id = array_search( $slug, $terms ); // What is the term_id corresponding to taxonomy term?
								if ( $term_id && $term_id = $this->model->term->get_translation( $term_id, $language ) ) { // Get the translated term_id
									$term = get_term( $term_id, $tax->name );

									if ( ! $term instanceof WP_Term ) {
										continue;
									}

									$url = str_replace( $slug, $term->slug, $url );
								}
							}
						}
					}
				}
			}

			// Translated taxonomy
			// Take care that is_tax() is false for categories and tags
			elseif ( ( is_category() || is_tag() || is_tax() ) && ( $term = get_queried_object() ) && $this->model->is_translated_taxonomy( $term->taxonomy ) ) {
				$lang = $this->model->term->get_language( $term->term_id );

				if ( ! $lang || $language->slug == $lang->slug ) {
					$url = get_term_link( $term, $term->taxonomy ); // Self link
				}

				elseif ( $tr_id = $this->model->term->get_translation( $term->term_id, $language ) ) {
					$tr_term = get_term( $tr_id, $term->taxonomy );
					if ( $tr_term instanceof WP_Term ) {
						// Check if translated term ( or children ) have posts
						$count = $tr_term->count || ( is_taxonomy_hierarchical( $term->taxonomy ) && array_sum( wp_list_pluck( get_terms( array( 'taxonomy' => $term->taxonomy, 'child_of' => $tr_term->term_id, 'lang' => $language->slug ) ), 'count' ) ) );

						/**
						 * Filter whether to hide an archive translation url
						 *
						 *  
						 *
						 * @param bool   $hide True to hide the translation url.
						 *                     defaults to true when the translated archive is empty, false otherwise.
						 * @param string $lang The language code of the translation
						 * @param array  $args Arguments used to evaluated the number of posts in the archive
						 */
						if ( ! apply_filters( 'ewt_hide_archive_translation_url', ! $count, $language->slug, array( 'taxonomy' => $term->taxonomy ) ) ) {
							$url = get_term_link( $tr_term, $term->taxonomy );
						}
					}
				}
			}

			// Post type archive
			elseif ( is_post_type_archive() ) {
				if ( $this->model->is_translated_post_type( $qv['post_type'] ) ) {
					$args = array( 'post_type' => $qv['post_type'] );
					$count = $this->model->count_posts( $language, $args );

					/** This filter is documented in frontend/frontend-links.php */
					if ( ! apply_filters( 'ewt_hide_archive_translation_url', ! $count, $language->slug, $args ) ) {
						$url = $this->get_archive_url( $language );
					}
				}
			}

			// Other archives
			elseif ( is_archive() ) {
				$keys = array( 'post_type', 'm', 'year', 'monthnum', 'day', 'author', 'author_name' );
				$keys = array_merge( $keys, $this->model->get_filtered_taxonomies_query_vars() );
				$args = array_intersect_key( $qv, array_flip( $keys ) );
				$count = $this->model->count_posts( $language, $args );

				/** This filter is documented in frontend/frontend-links.php */
				if ( ! apply_filters( 'ewt_hide_archive_translation_url', ! $count, $language->slug, $args ) ) {
					$url = $this->get_archive_url( $language );
				}
			}

			// Front page when it is the list of posts
			elseif ( is_front_page() ) {
				$url = $this->get_home_url( $language );
			}
		}

		$url = ! empty( $url ) && ! is_wp_error( $url ) ? $url : null;

		/**
		 * Filter the translation url of the current page before EasyWPTranslator caches it
		 *
		 *  
		 *
		 * @param null|string $url      The translation url, null if none was found
		 * @param string      $language The language code of the translation
		 */
		$translation_url = (string) apply_filters( 'ewt_translation_url', $url, $language->slug );

		// Don't cache before template_redirect to avoid a conflict with Barrel + WP Bakery Page Builder
		if ( did_action( 'template_redirect' ) ) {
			$this->cache->set( 'translation_url:' . $language->slug, $translation_url );
		}

		return $translation_url;
	}

	/**
	 * Get the translation of the current archive url
	 * used also for search
	 *
	 *  
	 *
	 * @param EWT_Language $language An object representing a language.
	 * @return string
	 */
	public function get_archive_url( $language ) {
		$url = ewt_get_requested_url();
		$url = $this->links_model->switch_language_in_link( $url, $language );
		$url = $this->links_model->remove_paged_from_link( $url );

		/**
		 * Filter the archive url
		 *
		 *  
		 *
		 * @param string $url      Url of the archive
		 * @param object $language Language of the archive
		 */
		return apply_filters( 'ewt_get_archive_url', $url, $language );
	}

	/**
	 * Returns the home url in the right language.
	 *
	 *  
	 *
	 * @param EWT_Language|string $language  Optional, defaults to current language.
	 * @param bool                $is_search Optional, whether we need the home url for a search form, defaults to false.
	 */
	public function get_home_url( $language = '', $is_search = false ) {
		if ( empty( $language ) ) {
			$language = $this->curlang;
		}

		return parent::get_home_url( $language, $is_search );
	}
}
