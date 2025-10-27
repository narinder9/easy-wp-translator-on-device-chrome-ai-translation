<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Controllers\EWT_Static_Pages;




/**
 * Manages the static front page and the page for posts on frontend
 *
 *  
 */
class EWT_Frontend_Static_Pages extends EWT_Static_Pages {
	/**
	 * Instance of a child class of EWT_Links_Model.
	 *
	 * @var EWT_Links_Model
	 */
	protected $links_model;

	/**
	 * @var EWT_Frontend_Links|null
	 */
	protected $links;

	/**
	 * Stores plugin's options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Constructor: setups filters and actions.
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		parent::__construct( $easywptranslator );

		$this->links_model = &$easywptranslator->links_model;
		$this->links       = &$easywptranslator->links;
		$this->options     = &$easywptranslator->options;

		add_action( 'ewt_home_requested', array( $this, 'ewt_home_requested' ) );

		// Manages the redirection of the homepage.
		add_filter( 'redirect_canonical', array( $this, 'redirect_canonical' ) );

		add_filter( 'ewt_pre_translation_url', array( $this, 'ewt_pre_translation_url' ), 10, 3 );
		add_filter( 'ewt_check_canonical_url', array( $this, 'ewt_check_canonical_url' ) );

		add_filter( 'ewt_set_language_from_query', array( $this, 'page_on_front_query' ), 10, 2 );
		add_filter( 'ewt_set_language_from_query', array( $this, 'page_for_posts_query' ), 10, 2 );

		// Specific cases for the customizer.
		add_action( 'customize_register', array( $this, 'filter_customizer' ) );
	}

	/**
	 * Translates the page_id query var when the site root page is requested
	 *
	 *  
	 *
	 * @return void
	 */
	public function ewt_home_requested() {
		set_query_var( 'page_id', $this->curlang->page_on_front );
	}

	/**
	 * Manages the canonical redirect of the homepage when using a page on front.
	 *
	 *  
	 *
	 * @param string $redirect_url The redirect url.
	 * @return string|false The modified url, false if the redirect is canceled.
	 */
	public function redirect_canonical( $redirect_url ) {
		if ( is_page() && ! is_feed() && get_queried_object_id() == $this->curlang->page_on_front ) {
			$url = is_paged() ? $this->links_model->add_paged_to_link( $this->links->get_home_url(), get_query_var( 'page' ) ) : $this->links->get_home_url();

			// Don't forget additional query vars
			$query = wp_parse_url( $redirect_url, PHP_URL_QUERY );
			if ( ! empty( $query ) ) {
				parse_str( $query, $query_vars );
				$query_vars = rawurlencode_deep( $query_vars ); // WP encodes query vars values
				$url = add_query_arg( $query_vars, $url );
			}

			return $url;
		}

		return $redirect_url;
	}

	/**
	 * Translates the url of the page on front and page for posts.
	 *
	 *  
	 *
	 * @param string       $url               Empty string or the url of the translation of the current page.
	 * @param EWT_Language $language          Language of the translation.
	 * @param int          $queried_object_id Queried object ID.
	 * @return string The translation url.
	 */
	public function ewt_pre_translation_url( $url, $language, $queried_object_id ) {
		if ( empty( $queried_object_id ) ) {
			return $url;
		}

		// Page for posts.
		if ( $GLOBALS['wp_query']->is_posts_page ) {
			$id = $this->model->post->get( $queried_object_id, $language );

			if ( ! empty( $id ) ) {
				return (string) get_permalink( $id );
			}
		}

		// Page on front.
		if ( is_front_page() && ! empty( $language->page_on_front ) ) {
			$id = $this->model->post->get( $queried_object_id, $language );

			if ( $language->page_on_front === $id ) {
				return $language->get_home_url();
			}
		}

		return $url;
	}

	/**
	 * Prevents the canonical redirect if we are on a static front page.
	 *
	 *  
	 *
	 * @param string $redirect_url The redirect url.
	 * @return string|false
	 */
	public function ewt_check_canonical_url( $redirect_url ) {
		return $this->options['redirect_lang'] && ! $this->options['force_lang'] && ! empty( $this->curlang->page_on_front ) && is_page( $this->curlang->page_on_front ) ? false : $redirect_url;
	}

	/**
	 * Is the query for a the static front page (redirected from the language page)?
	 *
	 *  
	 *
	 * @param WP_Query $query The WP_Query object.
	 * @return bool
	 */
	protected function is_front_page( $query ) {
		$query = array_diff( array_keys( $query->query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) );
		return 1 === count( $query ) && in_array( 'ewt_lang', $query );
	}

	/**
	 * Setups query vars when requesting a static front page
	 *
	 *  
	 *
	 * @param EWT_Language|false $lang  The current language, false if it is not set yet.
	 * @param WP_Query           $query The main WP query.
	 * @return EWT_Language|false
	 */
	public function page_on_front_query( $lang, $query ) {
		if ( ! empty( $lang ) || ! $this->page_on_front ) {
			return $lang;
		}

		// Redirect the language page to the homepage when using a static front page
		if ( ( $this->options['redirect_lang'] || $this->options['hide_default'] ) && $this->is_front_page( $query ) && $lang = $this->model->get_language( get_query_var( 'ewt_lang' ) ) ) {
			$query->is_archive = $query->is_tax = false;
			if ( 'page' === get_option( 'show_on_front' ) && ! empty( $lang->page_on_front ) ) {
				$query->set( 'page_id', $lang->page_on_front );
				$query->is_singular = $query->is_page = true;
				unset( $query->query_vars['ewt_lang'], $query->queried_object ); // Reset queried object
			} else {
				// Handle case where the static front page hasn't be translated to avoid a possible infinite redirect loop.
				$query->is_home = true;
			}
		}

		// Fix paged static front page in plain permalinks when Settings > Reading doesn't match the default language
		elseif ( ! $this->links_model->using_permalinks && count( $query->query ) === 1 && ! empty( $query->query['page'] ) ) {
			$lang = $this->model->get_default_language();
			if ( empty( $lang ) ) {
				return $lang;
			}
			$query->set( 'page_id', $lang->page_on_front );
			$query->is_singular = $query->is_page = true;
			$query->is_archive = $query->is_tax = false;
			unset( $query->query_vars['ewt_lang'], $query->queried_object ); // Reset queried object
		}

		// Set the language when requesting a static front page
		else {
			$page_id = $this->get_page_id( $query );
			$languages = $this->model->get_languages_list();
			$pages = wp_list_pluck( $languages, 'page_on_front' );

			if ( ! empty( $page_id ) && false !== $n = array_search( $page_id, $pages ) ) {
				$lang = $languages[ $n ];
			}
		}

		// Fix <!--nextpage--> for page_on_front
		if ( ( $this->options['force_lang'] < 2 || ! $this->options['redirect_lang'] ) && $this->links_model->using_permalinks && ! empty( $lang ) && isset( $query->query['paged'] ) ) {
			$query->set( 'page', $query->query['paged'] );
			unset( $query->query['paged'] );
		} elseif ( ! $this->links_model->using_permalinks && ! empty( $query->query['page'] ) ) {
			$query->is_paged = true;
		}

		return $lang;
	}

	/**
	 * Setups query vars when requesting a posts page
	 *
	 *  
	 *
	 * @param EWT_Language|false $lang  The current language, false if it is not set yet.
	 * @param WP_Query           $query The main WP query.
	 * @return EWT_Language|false
	 */
	public function page_for_posts_query( $lang, $query ) {
		if ( ! empty( $lang ) || ! $this->page_for_posts ) {
			return $lang;
		}

		$page_id = $this->get_page_id( $query );

		if ( empty( $page_id ) ) {
			return $lang;
		}

		$pages = $this->model->get_languages_list( array( 'fields' => 'page_for_posts' ) );
		$pages = array_filter( $pages );

		if ( in_array( $page_id, $pages ) ) {
			_prime_post_caches( $pages ); // Fill the cache with all pages for posts to avoid one query per page later.

			$lang = $this->model->post->get_language( $page_id );
			$query->is_singular = $query->is_page = false;
			$query->is_home = $query->is_posts_page = true;
		}

		return $lang;
	}

	/**
	 * Get the queried page_id (if it exists ).
	 *
	 * If permalinks are used, WordPress does set and use `$query->queried_object_id` and sets `$query->query_vars['page_id']` to 0,
	 * and does set and use `$query->query_vars['page_id']` if permalinks are not used :(.
	 *
	 *  
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return int The page_id.
	 */
	protected function get_page_id( $query ) {
		if ( ! empty( $query->query_vars['pagename'] ) && isset( $query->queried_object_id ) ) {
			return $query->queried_object_id;
		}

		return $query->query_vars['page_id'] ?? 0; // No page queried.
	}

	/**
	 * Adds support for the theme customizer.
	 *
	 *  
	 *
	 * @return void
	 */
	public function filter_customizer() {
		add_filter( 'pre_option_page_on_front', array( $this, 'customize_page' ), 20 ); // After the customizer.
		add_filter( 'pre_option_page_for_post', array( $this, 'customize_page' ), 20 );

		add_filter( 'ewt_pre_translation_url', array( $this, 'customize_translation_url' ), 20, 2 ); // After the generic hook in this class.
	}

	/**
	 * Translates the page ID when customized.
	 *
	 *  
	 *
	 * @param int|false $pre A page ID if the setting is customized, false otherwise.
	 * @return int|false
	 */
	public function customize_page( $pre ) {
		return is_numeric( $pre ) ? ewt_get_post( (int) $pre ) : $pre;
	}

	/**
	 * Fixes the translation URL if the option 'show_on_front' is customized.
	 *
	 *  
	 *
	 * @param string       $url      An empty string or the URL of the translation of the current page.
	 * @param EWT_Language $language The language of the translation.
	 * @return string
	 */
	public function customize_translation_url( $url, $language ) {
		if ( 'posts' === get_option( 'show_on_front' ) && is_front_page() ) {
			// When the page on front displays posts, the home URL is the same as the search URL.
			return $language->get_search_url();
		}
		return $url;
	}
}
