<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Core\EasyWPTranslator;
use EasyWPTranslator\Frontend\Services\EWT_Accept_Languages_Collection;
use EasyWPTranslator\Includes\Other\EWT_Language;
use EasyWPTranslator\Includes\Helpers\EWT_Cookie;
use EasyWPTranslator\Includes\Other\EWT_Query;



/**
 * Base class to choose the language
 *
 *  
 */
abstract class EWT_Choose_Lang {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * Instance of a child class of EWT_Links_Model.
	 *
	 * @var EWT_Links_Model
	 */
	public $links_model;

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
		$this->links_model = &$easywptranslator->links_model;
		$this->model = &$easywptranslator->model;
		$this->options = &$easywptranslator->options;

		$this->curlang = &$easywptranslator->curlang;
	}

	/**
	 * Sets the language for ajax requests
	 * and setup actions
	 * Any child class must call this method if it overrides it
	 *
	 *  
	 *
	 * @return void
	 */
	public function init() {
		if ( EasyWPTranslator::is_ajax_on_front() || ! wp_using_themes() ) {
			$this->set_language( empty( $_REQUEST['ewt_lang'] ) ? $this->get_preferred_language() : $this->model->get_language( sanitize_key( $_REQUEST['ewt_lang'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		add_action( 'pre_comment_on_post', array( $this, 'pre_comment_on_post' ) ); // sets the language of comment
		add_action( 'parse_query', array( $this, 'parse_main_query' ), 2 ); // sets the language in special cases
		add_action( 'wp', array( $this, 'maybe_setcookie' ), 7 );
	}

	/**
	 * Sets the current language
	 * and fires the action 'ewt_language_defined'.
	 *
	 *  
	 *
	 * @param EWT_Language|false $curlang Current language.
	 * @return void
	 */
	protected function set_language( $curlang ) {
		// Don't set the language a second time
		if ( isset( $this->curlang ) ) {
			return;
		}
		
		// Final check in case $curlang has an unexpected value
		if ( ! $curlang instanceof EWT_Language ) {
			$curlang = $this->model->get_default_language();

			if ( ! $curlang instanceof EWT_Language ) {
				return;
			}
		}

		$this->curlang = $curlang;

		$GLOBALS['text_direction'] = $this->curlang->is_rtl ? 'rtl' : 'ltr';
		if ( did_action( 'wp_default_styles' ) ) {
			wp_styles()->text_direction = $GLOBALS['text_direction'];
		}

		/**
		 * Fires when the current language is defined.
		 *
		 *  
		 *
		 * @param string       $slug    Current language code.
		 * @param EWT_Language $curlang Current language object.
		 */
		do_action( 'ewt_language_defined', $this->curlang->slug, $this->curlang );
	}

	/**
	 * Set a cookie to remember the language.
	 * Setting EWT_COOKIE to false will disable cookie although it will break some functionalities
	 *
	 *  
	 *
	 * @return void
	 */
	public function maybe_setcookie() {
		// Don't set cookie in javascript when a cache plugin is active.
		if ( ! ewt_is_cache_active() && ! empty( $this->curlang ) && ! is_404() ) {
			$args = array(
				'domain'   => 2 === $this->options['force_lang'] ? wp_parse_url( $this->links_model->home, PHP_URL_HOST ) : COOKIE_DOMAIN,
				'samesite' => 3 === $this->options['force_lang'] ? 'None' : 'Lax',
			);
			EWT_Cookie::set( $this->curlang->slug, $args );
		}
	}

	/**
	 * Get the preferred language according to the browser preferences.
	 *
	 *  
	 *
	 * @return string|bool The preferred language slug or false.
	 */
	public function get_preferred_browser_language() {
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$accept_langs = EWT_Accept_Languages_Collection::from_accept_language_header( sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );

			$accept_langs->bubble_sort();

			$languages = $this->model->get_languages_list( array( 'hide_empty' => true ) ); // Hides languages with no post.

			/**
			 * Filters the list of languages to use to match the browser preferences.
			 *
			 *  
			 *
			 * @param array $languages Array of EWT_Language objects.
			 */
			$languages = apply_filters( 'ewt_languages_for_browser_preferences', $languages );

			return $accept_langs->find_best_match( $languages );
		}

		return false;
	}

	/**
	 * Returns the preferred language
	 * either from the cookie if it's a returning visit
	 * or according to browser preference
	 * or the default language
	 *
	 *  
	 *
	 * @return EWT_Language|false browser preferred language or default language
	 */
	public function get_preferred_language() {
		$language = false;
		$cookie   = false;

		if ( isset( $_COOKIE[ EWT_COOKIE ] ) ) {
			// Check first if the user was already browsing this site.
			$language = sanitize_key( $_COOKIE[ EWT_COOKIE ] );
			$cookie   = true;
		} elseif ( $this->options['browser'] ) {
			$language = $this->get_preferred_browser_language();
		}

		/**
		 * Filter the visitor's preferred language (normally set first by cookie
		 * if this is not the first visit, then by the browser preferences).
		 * If no preferred language has been found or set by this filter,
		 * EasyWPTranslator fallbacks to the default language
		 *
		 *  
		 *   Added $cookie parameter.
		 *
		 * @param string|bool $language Preferred language code, false if none has been found.
		 * @param bool        $cookie   Whether the preferred language has been defined by the cookie.
		 */
		$slug = apply_filters( 'ewt_preferred_language', $language, $cookie );

		// Return default if there is no preferences in the browser or preferences does not match our languages or it is requested not to use the browser preference
		return ( $lang = $this->model->get_language( $slug ) ) ? $lang : $this->model->get_default_language();
	}

	/**
	 * Sets the language when home page is requested
	 *
	 *  
	 *
	 * @return void
	 */
	protected function home_language() {
		// Test referer in case EWT_COOKIE is set to false. Since WP 3.6.1, wp_get_referer() validates the host which is exactly what we want
		$language = $this->options['hide_default'] && ( wp_get_referer() || ! $this->options['browser'] ) ?
			$this->model->get_default_language() :
			$this->get_preferred_language(); // Sets the language according to browser preference or default language
		$this->set_language( $language );
	}

	/**
	 * To call when the home page has been requested
	 * Make sure to call this after 'setup_theme' has been fired as we need $wp_query
	 * Performs a redirection to the home page in the current language if needed
	 *
	 *  
	 *
	 * @return void
	 */
	public function home_requested() {
		if ( empty( $this->curlang ) ) {
			return;
		}

		// We are already on the right page
		if ( $this->curlang->is_default && $this->options['hide_default'] ) {
			$this->set_curlang_in_query( $GLOBALS['wp_query'] );

			/**
			 * Fires when the site root page is requested
			 *
			 *  
			 */
			do_action( 'ewt_home_requested' );
		}
		// Redirect to the home page in the right language
		// Test to avoid crash if get_home_url returns something wrong
		// Don't redirect if $_POST is not empty as it could break other plugins
		elseif ( is_string( $redirect = $this->curlang->get_home_url() ) && empty( $_POST ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// Don't forget the query string which may be added by plugins
			$query_string = wp_parse_url( ewt_get_requested_url(), PHP_URL_QUERY );
			if ( ! empty( $query_string ) ) {
				$redirect .= ( $this->links_model->using_permalinks ? '?' : '&' ) . $query_string;
			}

			/**
			 * When a visitor reaches the site home, EasyWPTranslator redirects to the home page in the correct language.
			 * This filter allows plugins to modify the redirected url or prevent this redirection
			 * /!\ this filter may be fired *before* the theme is loaded
			 *
			 *  
			 *
			 * @param string $redirect the url the visitor will be redirected to
			 */
			$redirect = apply_filters( 'ewt_redirect_home', $redirect );
			if ( $redirect && wp_validate_redirect( $redirect ) ) {
				$this->maybe_setcookie();
				header( 'Vary: Accept-Language' );
				wp_safe_redirect( $redirect, 302, EASY_WP_TRANSLATOR );
				exit;
			}
		}
	}

	/**
	 * Set the language when posting a comment
	 *
	 *  
	 *
	 * @param int $post_id the post being commented
	 * @return void
	 */
	public function pre_comment_on_post( $post_id ) {
		$this->set_language( $this->model->post->get_language( $post_id ) );
	}

	/**
	 * Modifies some main query vars for the home page and the page for posts
	 * to enable one home page (and one page for posts) per language.
	 *
	 *  
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return void
	 */
	public function parse_main_query( $query ) {
		if ( ! $query->is_main_query() ) {
			return;
		}

		/**
		 * This filter allows to set the language based on information contained in the main query
		 *
		 *  
		 *
		 * @param EWT_Language|false $lang  Language object or false.
		 * @param WP_Query           $query WP_Query object.
		 */
		if ( $lang = apply_filters( 'ewt_set_language_from_query', false, $query ) ) {
			$this->set_language( $lang );
			$this->set_curlang_in_query( $query );
		} elseif ( ( count( $query->query ) == 1 || ( is_paged() && count( $query->query ) == 2 ) ) && $lang = get_query_var( 'ewt_lang' ) ) {
			$lang = $this->model->get_language( $lang );
			$this->set_language( $lang ); // Set the language now otherwise it will be too late to filter sticky posts!

			// Set is_home on translated home page when it displays posts. It must be true on page 2, 3... too.
			$query->is_home    = true;
			$query->is_tax     = false;
			$query->is_archive = false;

			// Filters is_front_page() in case a static front page is not translated in this language.
			add_filter( 'option_show_on_front', array( $this, 'filter_option_show_on_front' ) );
		}
	}

	/**
	 * Filters the option show_on_front when the current front page displays posts.
	 *
	 * This is useful when a static front page is not translated in all languages.
	 *
	 * @return string
	 */
	public function filter_option_show_on_front() {
		return 'posts';
	}

	/**
	 * Sets the current language in the query.
	 *
	 *  
	 *
	 * @param WP_Query $query Instance of WP_Query.
	 * @return void
	 */
	protected function set_curlang_in_query( &$query ) {
		if ( ! empty( $this->curlang ) ) {
			$ewt_query = new EWT_Query( $query, $this->model );
			$ewt_query->set_language( $this->curlang );
		}
	}
}
