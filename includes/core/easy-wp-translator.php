<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Core;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

use EasyWPTranslator\Includes\Base\EWT_Base;
use EasyWPTranslator\Includes\Options\Options;
use EasyWPTranslator\Includes\Options\Registry as Options_Registry;
use EasyWPTranslator\Install\EWT_Install;
use EasyWPTranslator\Includes\Other\EWT_OLT_Manager;
use EasyWPTranslator\Includes\Other\EWT_Model;
use EasyWPTranslator\Admin\Controllers\EWT_Admin_Model;
use EasyWPTranslator\Admin\Controllers\EWT_Admin;
use EasyWPTranslator\Frontend\Controllers\EWT_Frontend;
use EasyWPTranslator\Includes\Controllers\EWT_REST_Request;
use EasyWPTranslator\Integrations\EWT_Integrations;
use EasyWPTranslator\Settings\Controllers\EWT_Settings;
use EasyWPTranslator\Supported_Blocks\Custom_Block_Post;
use EasyWPTranslator\Custom_Fields\Custom_Fields;
use EasyWPTranslator\Includes\Other\EWT_Translation_Dashboard;

// Default directory to store user data such as custom flags
if ( ! defined( 'EWT_LOCAL_DIR' ) ) {
	define( 'EWT_LOCAL_DIR', WP_CONTENT_DIR . '/easywptranslator' );
}

// Includes local config file if exists
if ( is_readable( EWT_LOCAL_DIR . '/ewt-config.php' ) ) {
	include_once EWT_LOCAL_DIR . '/ewt-config.php';
}

/**
 * Controls the plugin, as well as activation, and deactivation
 *
 *  
 *
 * @template TEWTClass of EWT_Base
 */
class EasyWPTranslator {

	/**
	 * @var EWT_cronjob|null
	 */
	public $ewt_cronjob;

	/**
	 * @var Options|null
	 */
	public $options;

	/**
	 * Constructor
	 *
	 *  
	 */
	public function __construct() {
		require_once __DIR__ . '/../helpers/functions.php'; // VIP functions

		// register an action when plugin is activating.
		register_activation_hook( EASY_WP_TRANSLATOR_BASENAME, array( '\\EasyWPTranslator\\Modules\\Wizard\\EWT_Wizard', 'start_wizard' ) );

		$install = new EWT_Install( EASY_WP_TRANSLATOR_BASENAME );

		// Check if we can activate based on requirements
		if ( ! $install->can_activate() ) {
			return;
		}

		// Plugin initialization
		// Take no action before all plugins are loaded
		add_action( 'plugins_loaded', array( $this, 'init' ), 1 );

		// Override load text domain waiting for the language to be defined
		// Here for plugins which load text domain as soon as loaded :(
		if ( ! defined( 'EWT_OLT' ) || EWT_OLT ) {
			EWT_OLT_Manager::instance();
		}

		// Register the custom post type for the supported blocks
		if(class_exists(Custom_Block_Post::class)){
			Custom_Block_Post::get_instance();
		}

		// Register the custom fields
		if(class_exists(Custom_Fields::class)){
			Custom_Fields::get_instance();
		}

		// Register the translation dashboard
		if(class_exists(EWT_Translation_Dashboard::class)){
			EWT_Translation_Dashboard::get_instance();
		}

		/*
		 * Loads the compatibility with some plugins and themes.
		 * Loaded as soon as possible as we may need to act before other plugins are loaded.
		 */
		if ( ! defined( 'EWT_PLUGINS_COMPAT' ) || EWT_PLUGINS_COMPAT ) {
			EWT_Integrations::instance();
		}
	}

	/**
	 * Tells whether the current request is an ajax request on frontend or not
	 *
	 *  
	 *
	 * @return bool
	 */
	public static function is_ajax_on_front() {
		// Special test for plupload which does not use jquery ajax and thus does not pass our ajax prefilter
		// Special test for customize_save done in frontend but for which we want to load the admin
		// Special test for Elementor actions which should be treated as admin/backend operations
		$excluded_actions = array( 'upload-attachment', 'customize_save' );
		
		// Add Elementor-specific actions that should be treated as backend
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		if ( isset( $_REQUEST['action'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
			$action = sanitize_key( $_REQUEST['action'] );
			// Check for Elementor actions - these should be treated as admin operations
			if ( strpos( $action, 'elementor' ) !== false || 
				 in_array( $action, array( 'heartbeat' ) ) ) {
				$excluded_actions[] = $action;
			}
		}
		
		$in = isset( $_REQUEST['action'] ) && in_array( sanitize_key( $_REQUEST['action'] ), $excluded_actions ); // phpcs:ignore WordPress.Security.NonceVerification
		$is_ajax_on_front = wp_doing_ajax() && empty( $_REQUEST['ewt_ajax_backend'] ) && ! $in; // phpcs:ignore WordPress.Security.NonceVerification

		/**
		 * Filters whether the current request is an ajax request on front.
		 *
		 *  
		 *
		 * @param bool $is_ajax_on_front Whether the current request is an ajax request on front.
		 */
		return apply_filters( 'ewt_is_ajax_on_front', $is_ajax_on_front );
	}

	/**
	 * Is the current request a REST API request?
	 * Inspired by WP::parse_request()
	 * Needed because at this point, the constant REST_REQUEST is not defined yet
	 *
	 *  
	 *
	 * @return bool
	 */
	public static function is_rest_request() {
		// Handle pretty permalinks.
		$home_path       = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

		$req_uri = trim( (string) wp_parse_url( ewt_get_requested_url(), PHP_URL_PATH ), '/' );
		$req_uri = (string) preg_replace( $home_path_regex, '', $req_uri );
		$req_uri = trim( $req_uri, '/' );
		$req_uri = str_replace( 'index.php', '', $req_uri );
		$req_uri = trim( $req_uri, '/' );

		// And also test rest_route query string parameter is not empty for plain permalinks.
		$query_string = array();
		wp_parse_str( (string) wp_parse_url( ewt_get_requested_url(), PHP_URL_QUERY ), $query_string );
		$rest_route = isset( $query_string['rest_route'] ) && is_string( $query_string['rest_route'] ) ? trim( $query_string['rest_route'], '/' ) : false;

		return 0 === strpos( $req_uri, rest_get_url_prefix() . '/' ) || ! empty( $rest_route );
	}

	/**
	 * Tells if we are in the wizard process.
	 *
	 *  
	 *
	 * @return bool
	 */
	public static function is_wizard() {
		return isset( $_GET['page'] ) && ! empty( $_GET['page'] ) && 'ewt_wizard' === sanitize_key( $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Defines constants
	 * May be overridden by a plugin if set before plugins_loaded, 1
	 *
	 *  
	 *
	 * @return void
	 */
	public static function define_constants() {
		// Cookie name. no cookie will be used if set to false
		if ( ! defined( 'EWT_COOKIE' ) ) {
			define( 'EWT_COOKIE', 'ewt_language' );
		}



		// Admin
		if ( ! defined( 'EWT_ADMIN' ) ) {
			define( 'EWT_ADMIN', wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( is_admin() && ! self::is_ajax_on_front() ) );
		}

		// Settings page whatever the tab except for the wizard which needs to be an admin process.
		if ( ! defined( 'EWT_SETTINGS' ) ) {
			define( 'EWT_SETTINGS', is_admin() && ( ( isset( $_GET['page'] ) && 0 === strpos( sanitize_key( $_GET['page'] ), 'ewt' ) && ! self::is_wizard() ) || ! empty( $_REQUEST['ewt_ajax_settings'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
	}

	/**
	 * EasyWPTranslator initialization
	 * setups models and separate admin and frontend
	 *
	 *  
	 *
	 * @return void
	 */
	public function init() {
		self::define_constants();

		// Plugin options.
		add_action( 'ewt_init_options_for_blog', array( Options_Registry::class, 'register' ) );
		$options = new Options();

		// Set current version
		$options['version'] = EASY_WP_TRANSLATOR_VERSION;
		/**
		 * Filter the model class to use
		 * /!\ this filter is fired *before* the $easywptranslator object is available
		 *
		 *  
		 *
		 * @param string $class either EWT_Model or EWT_Admin_Model
		 */
		$class = apply_filters( 'ewt_model', EWT_SETTINGS || self::is_wizard() ? 'EWT_Admin_Model' : 'EWT_Model' );
		
		// Handle namespaced classes for dynamic instantiation
		if ( 'EWT_Admin_Model' === $class ) {
			$class = EWT_Admin_Model::class;
		} elseif ( 'EWT_Model' === $class ) {
			$class = EWT_Model::class;
		}
		
		/** @var EWT_Model $model */
		$model = new $class( $options );

		if ( ! $model->has_languages() ) {
			/**
			 * Fires when no language has been defined yet
			 * Used to load overridden textdomains
			 *
			 *  
			 */
			do_action( 'ewt_no_language_defined' );
		}

		$class = '';

		if ( EWT_SETTINGS ) {
			$class = 'EWT_Settings';
		} elseif ( EWT_ADMIN ) {
			$class = 'EWT_Admin';
		} elseif ( self::is_rest_request() ) {
			$class = 'EWT_REST_Request';
		} elseif ( $model->has_languages() ) {
			$class = 'EWT_Frontend';
		}

		/**
		 * Filters the class to use to instantiate the $easywptranslator object
		 *
		 *  
		 *
		 * @param string $class A class name.
		 */
		$class = apply_filters( 'ewt_context', $class );

		if ( ! empty( $class ) ) {
			// Handle namespaced classes for dynamic instantiation
			if ( 'EWT_Admin' === $class ) {
				$class = EWT_Admin::class;
			} elseif ( 'EWT_Frontend' === $class ) {
				$class = EWT_Frontend::class;
			} elseif ( 'EWT_Settings' === $class ) {
				$class = EWT_Settings::class;
			} elseif ( 'EWT_REST_Request' === $class ) {
				$class = EWT_REST_Request::class;
			}
			
			/** @phpstan-var class-string<TEWTClass> $class */
			$this->init_context( $class, $model );
		}
	}

	/**
	 * EasyWPTranslator initialization.
	 * Setups the EasyWPTranslator Context, loads the modules and init EasyWPTranslator.
	 *
	 *  
	 *
	 * @param string    $class The class name.
	 * @param EWT_Model $model Instance of EWT_Model.
	 * @return EWT_Base
	 *
	 * @phpstan-param class-string<TEWTClass> $class
	 * @phpstan-return TEWTClass
	 */
	public function init_context( string $class, EWT_Model $model ): EWT_Base {
		global $easywptranslator;

		$links_model = $model->get_links_model();
		$easywptranslator    = new $class( $links_model );
		
		// Set the options property for backward compatibility
		$easywptranslator->options = $model->options;

		/**
		 * Fires after EasyWPTranslator's model init.
		 * This is the best place to register a custom table (see `EWT_Model`'s constructor).
		 * /!\ This hook is fired *before* the $easywptranslator object is available.
		 * /!\ The languages are also not available yet.
		 *
		 *  
		 *
		 * @param EWT_Model $model EasyWPTranslator model.
		 */
		do_action( 'ewt_model_init', $model );

		$model->maybe_create_language_terms();

		/**
		 * Fires after the $easywptranslator object is created and before the API is loaded
		 *
		 *  
		 *
		 * @param object $easywptranslator
		 */
		do_action_ref_array( 'ewt_pre_init', array( &$easywptranslator ) );

		// Loads the API
		require_once EASY_WP_TRANSLATOR_DIR . '/includes/api/language-api.php';

		// Loads the modules.
		$load_scripts = glob( EASY_WP_TRANSLATOR_DIR . '/modules/*/load.php', GLOB_NOSORT );
		if ( is_array( $load_scripts ) ) {
			foreach ( $load_scripts as $load_script ) {
				require_once $load_script; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
			}
		}
		$easywptranslator->init();
		/**
		 * Fires after the $easywptranslator object and the API is loaded
		 *
		 *  
		 *
		 * @param object $easywptranslator
		 */
		do_action_ref_array( 'ewt_init', array( &$easywptranslator ) );

			return $easywptranslator;
}
}


