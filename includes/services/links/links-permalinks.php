<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Services\Links;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Other\EWT_Language;



/**
 * Links model base class when using pretty permalinks.
 *
 *  
 */
abstract class EWT_Links_Permalinks extends EWT_Links_Model {
	/**
	 * Tells this child class of EWT_Links_Model is for pretty permalinks.
	 *
	 * @var bool
	 */
	public $using_permalinks = true;

	/**
	 * The name of the index file which is the entry point to all requests.
	 * We need this before the global $wp_rewrite is created.
	 * Also hardcoded in WP_Rewrite.
	 *
	 * @var string
	 */
	protected $index = 'index.php';

	/**
	 * The prefix for all permalink structures.
	 *
	 * @var string
	 */
	protected $root;

	/**
	 * Whether to add trailing slashes.
	 *
	 * @var bool
	 */
	protected $use_trailing_slashes;

	/**
	 * The name of the rewrite rules to always modify.
	 *
	 * @var string[]
	 */
	protected $always_rewrite = array( 'date', 'root', 'comments', 'search', 'author' );

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Model $model EWT_Model instance.
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );

		// Inspired by WP_Rewrite.
		$permalink_structure = get_option( 'permalink_structure' );
		$this->root = preg_match( '#^/*' . $this->index . '#', $permalink_structure ) ? $this->index . '/' : '';
		$this->use_trailing_slashes = ( '/' == substr( $permalink_structure, -1, 1 ) );
	}

	/**
	 * Initializes permalinks.
	 *
	 *  
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		if ( did_action( 'wp_loaded' ) ) {
			$this->do_prepare_rewrite_rules();
		} else {
			add_action( 'wp_loaded', array( $this, 'do_prepare_rewrite_rules' ), 9 ); // Just before WordPress callback `WP_Rewrite::flush_rules()`.
		}
	}

	/**
	 * Fires our own action telling EasyWPTranslator plugins
	 * and third parties are able to prepare rewrite rules.
	 *
	 *  
	 *
	 * @return void
	 */
	public function do_prepare_rewrite_rules() {
		self::$can_filter_rewrite_rules = true;

		/**
		 * Tells when EasyWPTranslator is able to prepare rewrite rules filters.
		 * Action fired right after `wp_loaded` and just before WordPress `WP_Rewrite::flush_rules()` callback.
		 *
		 *  
		 *
		 * @param EWT_Links_Permalinks $links Current links object.
		 */
		do_action( 'ewt_prepare_rewrite_rules', $this );
	}

	/**
	 * Returns the link to the first page when using pretty permalinks.
	 *
	 *  
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_paged_from_link( $url ) {
		/**
		 * Filters an url after the paged part has been removed.
		 *
		 *  
		 *
		 * @param string $modified_url The link to the first page.
		 * @param string $original_url The link to the original paged page.
		 */
		return apply_filters( 'ewt_remove_paged_from_link', preg_replace( '#/page/[0-9]+/?#', $this->use_trailing_slashes ? '/' : '', $url ), $url );
	}

	/**
	 * Returns the link to the paged page when using pretty permalinks.
	 *
	 *  
	 *
	 * @param string $url  The url to modify.
	 * @param int    $page The page number.
	 * @return string The modified url.
	 */
	public function add_paged_to_link( $url, $page ) {
		/**
		 * Filters an url after the paged part has been added.
		 *
		 *  
		 *
		 * @param string $modified_url The link to the paged page.
		 * @param string $original_url The link to the original first page.
		 * @param int    $page         The page number.
		 */
		return apply_filters( 'ewt_add_paged_to_link', user_trailingslashit( trailingslashit( $url ) . 'page/' . $page, 'paged' ), $url, $page );
	}

	/**
	 * Returns the home url in a given language.
	 *
	 *  
	 *   Accepts now a language slug.
	 *
	 * @param EWT_Language|string $language Language object or slug.
	 * @return string
	 */
	public function home_url( $language ) {
		if ( $language instanceof EWT_Language ) {
			$language = $language->slug;
		}

		return trailingslashit( parent::home_url( $language ) );
	}

	/**
	 * Returns the static front page url.
	 *
	 *  
	 *   Accepts now an array of language properties.
	 *
	 * @param EWT_Language|array $language Language object or array of language properties.
	 * @return string The static front page url.
	 */
	public function front_page_url( $language ) {
		if ( $language instanceof EWT_Language ) {
			$language = $language->to_array();
		}

		if ( $this->options['hide_default'] && $language['is_default'] ) {
			return trailingslashit( $this->home );
		}
		$url = home_url( $this->root . get_page_uri( $language['page_on_front'] ) );
		$url = $this->use_trailing_slashes ? trailingslashit( $url ) : untrailingslashit( $url );
		return $this->options['force_lang'] ? $this->add_language_to_link( $url, $language['slug'] ) : $url;
	}

	/**
	 * Prepares rewrite rules filters.
	 *
	 *  
	 *
	 * @return string[]
	 */
	public function get_rewrite_rules_filters() {
		// Make sure that we have the right post types and taxonomies.
		$types = array_values( array_merge( $this->model->get_translated_post_types(), $this->model->get_translated_taxonomies(), $this->model->get_filtered_taxonomies() ) );
		$types = array_merge( $this->always_rewrite, $types );

		/**
		 * Filters the list of rewrite rules filters to be used by EasyWPTranslator.
		 *
		 *  
		 *
		 * @param array $types The list of filters (without '_rewrite_rules' at the end).
		 */
		return apply_filters( 'ewt_rewrite_rules', $types );
	}

	/**
	 * Removes hooks to filter rewrite rules, called when switching blog @see {EWT_Base::switch_blog()}.
	 *
	 *  
	 *
	 * @return void
	 */
	public function remove_filters() {
		parent::remove_filters();

		remove_all_actions( 'ewt_prepare_rewrite_rules' );
	}
}
