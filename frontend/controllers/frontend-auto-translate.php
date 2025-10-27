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
 * Auto translates the posts and terms ids
 * Useful for example for themes querying a specific cat
 *
 *  
 */
class EWT_Frontend_Auto_Translate {
	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * Current language.
	 *
	 * @var EWT_Language|null
	 */
	public $curlang;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		$this->model = &$easywptranslator->model;
		$this->curlang = &$easywptranslator->curlang;

		add_action( 'parse_query', array( $this, 'translate_included_ids_in_query' ), 100 ); // After all EasyWPTranslator filters.
		add_filter( 'get_terms_args', array( $this, 'get_terms_args' ), 20, 2 );
	}

	/**
	 * Helper function to get the translated post in the current language.
	 *
	 *  
	 *
	 * @param int $post_id The ID of the post to translate.
	 * @return int
	 *
	 * @phpstan-return int<0, max>
	 */
	protected function get_post( $post_id ) {
		return $this->model->post->get( $post_id, $this->curlang );
	}

	/**
	 * Helper function to get the translated term in the current language.
	 *
	 *  
	 *
	 * @param int $term_id The ID of the term to translate.
	 * @return int
	 *
	 * @phpstan-return int<0, max>
	 */
	protected function get_term( $term_id ) {
		return $this->model->term->get( $term_id, $this->curlang );
	}

	/**
	 * Filters posts query to automatically translate included ids
	 *
	 *  
	 *
	 * @param WP_Query $query WP_Query object
	 * @return void
	 */
	public function translate_included_ids_in_query( $query ) {
		global $wpdb;
		$qv = &$query->query_vars;

		if ( $query->is_main_query() || isset( $qv['lang'] ) || ( ! empty( $qv['post_type'] ) && ! $this->model->is_translated_post_type( $qv['post_type'] ) ) ) {
			return;
		}

		// /!\ always keep untranslated as is

		// Term ids separated by a comma
		$arr = array();
		if ( ! empty( $qv['cat'] ) ) {
			foreach ( explode( ',', $qv['cat'] ) as $cat ) {
				$tr = $this->get_term( abs( (int) $cat ) );
				$arr[] = $cat < 0 ? -$tr : $tr;
			}

			$qv['cat'] = implode( ',', $arr );
		}

		// Category_name
		$arr = array();
		if ( ! empty( $qv['category_name'] ) ) {
			foreach ( explode( ',', $qv['category_name'] ) as $slug ) {
				$arr[] = $this->get_translated_term_by( 'slug', $slug, 'category' );
			}

			$qv['category_name'] = implode( ',', $arr );
		}

		// Array of term ids
		foreach ( array( 'category__and', 'category__in', 'category__not_in', 'tag__and', 'tag__in', 'tag__not_in' ) as $key ) {
			$arr = array();
			if ( ! empty( $qv[ $key ] ) ) {
				foreach ( $qv[ $key ] as $cat ) {
					$arr[] = ( $tr = $this->get_term( $cat ) ) ? $tr : $cat;
				}
				$qv[ $key ] = $arr;
			}
		}

		// Tag
		if ( ! empty( $qv['tag'] ) ) {
			$qv['tag'] = $this->translate_terms_list( $qv['tag'], 'post_tag' );
		}

		// tag_id can only take one id
		if ( ! empty( $qv['tag_id'] ) && $tr_id = $this->get_term( $qv['tag_id'] ) ) {
			$qv['tag_id'] = $tr_id;
		}

		// Array of tag slugs
		foreach ( array( 'tag_slug__and', 'tag_slug__in' ) as $key ) {
			$arr = array();
			if ( ! empty( $qv[ $key ] ) ) {
				foreach ( $qv[ $key ] as $slug ) {
					$arr[] = $this->get_translated_term_by( 'slug', $slug, 'post_tag' );
				}

				$qv[ $key ] = $arr;
			}
		}

		// Custom taxonomies
		foreach ( array_intersect( $this->model->get_translated_taxonomies(), get_taxonomies( array( '_builtin' => false ) ) ) as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			if ( ! empty( $tax ) && ! empty( $tax->query_var ) && ! empty( $qv[ $tax->query_var ] ) ) {
				$qv[ $tax->query_var ] = $this->translate_terms_list( $qv[ $tax->query_var ], $taxonomy );
			}
		}

		// Tax_query since WP 3.1
		if ( ! empty( $qv['tax_query'] ) && is_array( $qv['tax_query'] ) ) {
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Intentionally using tax_query here to fetch posts associated with a term and all its translations, which is critical for correct multilingual syncing. No performant or reliable alternative exists in core WP for this multilingual logic; this is required for accurate results across languages.
			$qv['tax_query'] = $this->translate_tax_query_recursive( $qv['tax_query'] );
		}

		// p, page_id, post_parent can only take one id
		foreach ( array( 'p', 'page_id', 'post_parent' ) as $key ) {
			if ( ! empty( $qv[ $key ] ) && $tr_id = $this->get_post( $qv[ $key ] ) ) {
				$qv[ $key ] = $tr_id;
			}
		}

		// name, can only take one slug
		if ( ! empty( $qv['name'] ) && is_string( $qv['name'] ) ) {
			if ( empty( $qv['post_type'] ) ) {
				$post_types = array( 'post' );
			} elseif ( 'any' === $qv['post_type'] ) {
				$post_types = get_post_types( array( 'exclude_from_search' => false ) ); // May return a empty array
			} else {
				$post_types = (array) $qv['post_type'];
			}

			if ( ! empty( $post_types ) ) {
				$posts = get_posts( array(
					'name'           => $qv['name'],
					'post_type'      => $post_types,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'post_status'    => 'any',
					'lang'           => '', // This tells our filter_query to skip this query
					'suppress_filters' => false, // Keep other filters active
				) );
				
				$id = ! empty( $posts ) ? $posts[0] : null;
				$qv['name'] = ( $id && ( $tr_id = $this->get_post( $id ) ) && $tr = get_post( $tr_id ) ) ? $tr->post_name : $qv['name'];
			}
		}

		// pagename, the page id is already available in queried_object_id
		if ( ! empty( $qv['pagename'] ) && ! empty( $query->queried_object_id ) && $tr_id = $this->get_post( $query->queried_object_id ) ) {
			$query->queried_object_id = $tr_id;
			$qv['pagename'] = get_page_uri( $tr_id );
		}

		// Array of post ids
		// post_parent__in & post_parent__not_in since WP 3.6
		foreach ( array( 'post__in', 'post__not_in', 'post_parent__in', 'post_parent__not_in' ) as $key ) { // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn
			$arr = array();
			if ( ! empty( $qv[ $key ] ) ) {
				// post__in used by the 2 functions below
				// Useless to filter them as output is already in the right language and would result in performance loss
				foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $trace ) { // phpcs:ignore WordPress.PHP.DevelopmentFunctions
					if ( in_array( $trace['function'], array( 'wp_nav_menu', 'gallery_shortcode' ) ) ) {
						return;
					}
				}

				foreach ( $qv[ $key ] as $p ) {
					$arr[] = ( $tr = $this->get_post( $p ) ) ? $tr : $p;
				}

				$qv[ $key ] = $arr;
			}
		}
	}

	/**
	 * Filters the terms query to automatically translate included ids.
	 *
	 *  
	 *
	 * @param array $args       An array of get_terms() arguments.
	 * @param array $taxonomies An array of taxonomy names.
	 * @return array
	 */
	public function get_terms_args( $args, $taxonomies ) {
		if ( ! isset( $args['lang'] ) && ! empty( $args['include'] ) && ( empty( $taxonomies ) || $this->model->is_translated_taxonomy( $taxonomies ) ) ) {
			$arr = array();

			foreach ( wp_parse_id_list( $args['include'] ) as $id ) {
				$arr[] = ( $tr = $this->get_term( $id ) ) ? $tr : $id;
			}

			$args['include'] = $arr;
		}
		return $args;
	}

	/**
	 * Translates tax queries
	 * Compatible with nested tax queries introduced in WP 4.1
	 *
	 *  
	 *
	 * @param array $tax_queries An array of tax queries.
	 * @return array Translated tax queries.
	 */
	protected function translate_tax_query_recursive( $tax_queries ) {
		foreach ( $tax_queries as $key => $q ) {
			if ( ! is_array( $q ) ) {
				continue;
			}

			if ( isset( $q['taxonomy'], $q['terms'] ) && $this->model->is_translated_taxonomy( $q['taxonomy'] ) ) {
				$arr = array();
				$field = isset( $q['field'] ) && in_array( $q['field'], array( 'slug', 'name' ) ) ? $q['field'] : 'term_id';
				foreach ( (array) $q['terms'] as $t ) {
					$arr[] = $this->get_translated_term_by( $field, $t, $q['taxonomy'] );
				}

				$tax_queries[ $key ]['terms'] = $arr;
			} else {
				// Nested queries.
				$tax_queries[ $key ] = $this->translate_tax_query_recursive( $q );
			}
		}

		return $tax_queries;
	}

	/**
	 * Translates a term given one field.
	 *
	 *  
	 *
	 * @param string     $field    Either 'slug', 'name', 'term_id', or 'term_taxonomy_id'
	 * @param string|int $term     Search for this term value
	 * @param string     $taxonomy Taxonomy name.
	 * @return string|int Translated term slug, name, term_id or term_taxonomy_id
	 */
	protected function get_translated_term_by( $field, $term, $taxonomy ) {
		if ( 'term_id' === $field ) {
			if ( $tr_id = $this->get_term( $term ) ) {
				return $tr_id;
			}
		} else {
			$terms = get_terms( array( 'taxonomy' => $taxonomy, $field => $term, 'lang' => '' ) );

			if ( ! empty( $terms ) && is_array( $terms ) ) {
				$t = reset( $terms );
				if ( ! $t instanceof WP_Term ) {
					return $term;
				}
				$tr_id = $this->get_term( $t->term_id );

				if ( ! is_wp_error( $tr = get_term( $tr_id, $taxonomy ) ) ) {
					return $tr->$field;
				}
			}
		}
		return $term;
	}

	/**
	 * Translates a list of term slugs provided either as an array or a string
	 * with slugs separated by a comma or a '+'.
	 *
	 *  
	 *
	 * @param string|string[] $query_var The list of term slugs.
	 * @param string          $taxonomy  The taxonomy for terms.
	 * @return string|string[] The translated list.
	 */
	protected function translate_terms_list( $query_var, $taxonomy ) {
		$slugs = array();

		if ( is_array( $query_var ) ) {
			$slugs = $query_var;
		} elseif ( is_string( $query_var ) ) {
			$sep   = strpos( $query_var, ',' ) !== false ? ',' : '+'; // Two possible separators.
			$slugs = explode( $sep, $query_var );
		}

		foreach ( $slugs as &$slug ) {
			if ( ! is_string( $slug ) ) {
				// We got an unexpected query var, let return it unchanged.
				return $query_var;
			}
			$slug = $this->get_translated_term_by( 'slug', $slug, $taxonomy );
		}

		if ( ! empty( $sep ) ) {
			return implode( $sep, $slugs );
		}

		return $slugs;
	}
}
