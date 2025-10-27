<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Includes\Other;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Core\EasyWPTranslator;
use WP_Locale;



/**
 * It is best practice that plugins do nothing before `plugins_loaded` is fired.
 * So it is what EasyWPTranslator intends to do.
 * But some plugins load their textdomain as soon as loaded, thus before `plugins_loaded` is fired.
 * This class defers textdomain loading until the language is defined either in a `plugins_loaded` action
 * or in a `wp` action (when the language is set from content on frontend).
 *
 *  
 */
class EWT_OLT_Manager {
	/**
	 * Singleton instance
	 *
	 * @var EWT_OLT_Manager|null
	 */
	protected static $instance;

	/**
	 * Constructor: setups relevant filters.
	 *
	 *  
	 */
	public function __construct() {
		// Allows EasyWPTranslator to be the first plugin loaded ;-)
		add_filter( 'pre_update_option_active_plugins', array( $this, 'make_easywptranslator_first' ) );
		add_filter( 'pre_update_option_active_sitewide_plugins', array( $this, 'make_easywptranslator_first' ) );

		// Overriding load text domain only on front since WP 4.7.
		if ( ( is_admin() && ! EasyWPTranslator::is_ajax_on_front() ) || EasyWPTranslator::is_rest_request() ) {
			return;
		}

		// Filters for text domain management.
		add_filter( 'load_textdomain_mofile', '__return_empty_string' );

		// Loads text domains.
		add_action( 'ewt_language_defined', array( $this, 'load_textdomains' ), 2 ); // After EWT_Frontend::ewt_language_defined.
		add_action( 'ewt_no_language_defined', array( $this, 'load_textdomains' ) );
	}

	/**
	 * Access to the single instance of the class
	 *
	 *  
	 *
	 * @return EWT_OLT_Manager
	 */
	public static function instance() {
		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Loads textdomains.
	 *
	 *  
	 *
	 * @return void
	 */
	public function load_textdomains() {
		// Our load_textdomain_mofile filter has done its job. let's remove it to enable translation.
		remove_filter( 'load_textdomain_mofile', '__return_empty_string' );

		$GLOBALS['l10n'] = array();
		$new_locale      = get_locale();

		// Fallback to WordPress site language if get_locale() returns null
		if ( null === $new_locale || empty( $new_locale ) ) {
			// Temporarily remove our filter to avoid infinite loop
			remove_filter( 'locale', array( $this, 'get_locale' ) );
			$site_locale = get_locale();
			add_filter( 'locale', array( $this, 'get_locale' ) );
			if ( ! empty( $site_locale ) ) {
				$new_locale = $site_locale;
			}
		}

		load_default_textdomain( $new_locale );

		// Act only if the language has not been set early (before default textdomain loading and $wp_locale creation).
		if ( ! empty( $GLOBALS['wp_locale'] ) ) {
			// Reinitializes wp_locale for weekdays and months.
			unset( $GLOBALS['wp_locale'] );
			$GLOBALS['wp_locale'] = new WP_Locale();
		}

		if ( ! empty( $GLOBALS['wp_locale_switcher'] ) ) {
			/** This action is documented in wp-includes/class-wp-locale-switcher.php */
			do_action( 'change_locale', $new_locale );
		}
	}

	/**
	 * Allows EasyWPTranslator to be the first plugin loaded ;-).
	 *
	 *  
	 *
	 * @param string[] $plugins List of active plugins.
	 * @return string[] List of active plugins.
	 */
	public function make_easywptranslator_first( $plugins ) {
		if ( $key = array_search( EASY_WP_TRANSLATOR_BASENAME, $plugins ) ) {
			unset( $plugins[ $key ] );
			array_unshift( $plugins, EASY_WP_TRANSLATOR_BASENAME );
		}
		return $plugins;
	}
}
