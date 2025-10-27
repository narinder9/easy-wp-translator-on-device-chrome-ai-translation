<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Settings\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Admin\Controllers\EWT_Admin_Base;
use EasyWPTranslator\Admin\Controllers\EWT_Admin_Strings;
use EasyWPTranslator\Admin\Controllers\EWT_Admin_Model;
use EasyWPTranslator\Settings\Controllers\EWT_Settings_Module;
use EasyWPTranslator\Settings\Tables\EWT_Table_Languages;
use EasyWPTranslator\Settings\Tables\EWT_Table_String;
use EasyWPTranslator\Settings\Header\Header;
use EasyWPTranslator\Supported_Blocks\Supported_Blocks;
use EasyWPTranslator\Custom_Fields\Custom_Fields;
use EasyWPTranslator\Includes\Other\EWT_Translation_Dashboard;

use WP_Error;

/**
 * A class for the EasyWPTranslator settings pages, accessible from @see EWT().
 *
 *  
 */
#[AllowDynamicProperties]
class EWT_Settings extends EWT_Admin_Base {

	/**
	 * @var EWT_Admin_Model
	 */
	public $model;

	/**
	 * Name of the active module.
	 *
	 * @var string|null
	 */
	protected $active_tab;

	/**
	 * Array of modules classes.
	 *
	 * @var EWT_Settings_Module[]|null
	 */
	protected $modules;

	// Module properties
	/**
	 * @var mixed
	 */
	public $sitemaps;

	/**
	 * @var mixed
	 */
	public $sync;

	/**
	 * @var mixed
	 */
	public $wizard;

	/**
	 * @var mixed
	 */
	public $rest;

	/**
	 * @var mixed
	 */
	public $switcher_block;

	/**
	 * @var mixed
	 */
	public $navigation_block;

	// Additional dynamic properties
	/**
	 * @var mixed
	 */
	public $pref_lang;

	/**
	 * @var mixed
	 */
	public $filter_lang;

	/**
	 * @var mixed
	 */
	private $header;

	/**
	 * @var mixed
	 */
	private $selected_tab;


	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param EWT_Links_Model $links_model Reference to the links model.
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		$selected_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		
		if ( isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->active_tab = 'ewt' === $_GET['page'] ? 'lang' : substr( sanitize_key( $_GET['page'] ), 5 ); // phpcs:ignore WordPress.Security.NonceVerification
		}

		if($this->active_tab === 'lang'){
			$selected_tab='lang';
		}

		if('' === $selected_tab){
			$this->selected_tab = 'general';
			$selected_tab='general';
		}
		
		if($selected_tab){
			if($selected_tab === 'strings'){
				$this->active_tab = $selected_tab;
			}

			$this->selected_tab = $selected_tab;
		}else{
			$this->selected_tab = $this->active_tab;
		}
		
		$this->header = Header::get_instance($this->selected_tab, $this->model);

		EWT_Admin_Strings::init();

		add_action( 'admin_init', array( $this, 'register_settings_modules' ) );

		// Adds screen options and the about box in the languages admin panel.
		add_action( 'load-toplevel_page_ewt', array( $this, 'load_page' ) );

		// Saves the per-page value in screen options.
		add_filter( 'set_screen_option_ewt_lang_per_page', array( $this, 'set_screen_option' ), 10, 3 );
		add_filter( 'set_screen_option_ewt_strings_per_page', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Initializes the modules
	 * 
	 * Note: Legacy settings modules are no longer needed since React handles settings.
	 * Only external modules from filters are registered now.
	 *
	 *  
	 *
	 * @return void
	 */
	public function register_settings_modules() {
		$modules = array();

		/**
		 * Filter the list of setting modules
		 * Allows external plugins/modules to add their own settings modules
		 *
		 *  
		 *
		 * @param array $modules the list of module classes
		 */
		$modules = apply_filters( 'ewt_settings_modules', $modules );

		foreach ( $modules as $key => $class ) {
			// Handle namespace mapping for remaining modules (mainly sync)
			if ( 'EWT_Settings_Sync' === $class ) {
				$class = \EasyWPTranslator\Modules\sync\EWT_Settings_Sync::class;
			}
			
			// Extract class name for the key if it's a full class name
			if ( is_string( $class ) && strpos( $class, '\\' ) !== false ) {
				$class_parts = explode( '\\', $class );
				$class_name = end( $class_parts );
			} else {
				$class_name = $class;
			}
			
			$key = is_numeric( $key ) ? strtolower( str_replace( 'EWT_Settings_', '', $class_name ) ) : $key;
			$this->modules[ $key ] = new $class( $this );
		}
	}

	/**
	 * Adds screen options and the about box in the languages admin panel
	 *
	 *  
	 *
	 * @return void
	 */
	public function load_page() {
		

		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Languages', 'easy-wp-translator' ),
				'default' => 10,
				'option'  => 'ewt_lang_per_page',
			)
		);

		add_action( 'admin_notices', array( $this, 'notice_objects_with_no_lang' ) );
	}

	/**
	 * Adds screen options in the strings translations admin panel
	 *
	 *  
	 *
	 * @return void
	 */
	public function load_page_strings() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Strings translations', 'easy-wp-translator' ),
				'default' => 10,
				'option'  => 'ewt_strings_per_page',
			)
		);
	}
	/**
	 * Saves the number of rows in the languages or strings table set by this user.
	 *
	 *  
	 *
	 * @param mixed  $screen_option False or value returned by a previous filter, not used.
	 * @param string $option        The name of the option, not used.
	 * @param int    $value         The new value of the option to save.
	 * @return int The new value of the option.
	 */
	public function set_screen_option( $screen_option, $option, $value ) {
		return (int) $value;
	}

	/**
	 * Manages the user input for the languages pages.
	 *
	 *  
	 *
	 * @param string $action The action name.
	 * @return void
	 */
	public function handle_actions( string $action ): void {
		switch ( $action ) {
			case 'add':
				check_admin_referer( 'add-lang', '_wpnonce_add-lang' );
				$sanitized_data = array(
					'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
					'slug' => sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) ),
					'locale' => sanitize_locale_name( wp_unslash( $_POST['locale'] ?? '' ) ),
					'rtl' => isset( $_POST['rtl'] ) ? (bool) $_POST['rtl'] : false,
					'term_group' => isset( $_POST['term_group'] ) ? (int) $_POST['term_group'] : 0,
					'flag' => sanitize_text_field( wp_unslash( $_POST['flag'] ?? '' ) ),
				);
				$errors = $this->model->add_language( $sanitized_data );

				if ( is_wp_error( $errors ) ) {
						ewt_add_notice( $errors );
				} else {
					ewt_add_notice( new WP_Error( 'ewt_languages_created', __( 'Language added.', 'easy-wp-translator' ), 'success' ) );
					$locale = $sanitized_data['locale'];

					if ( 'en_US' !== $locale && current_user_can( 'install_languages' ) ) {
						// Attempts to install the language pack
						require_once ABSPATH . 'wp-admin/includes/translation-install.php';
						if ( ! wp_download_language_pack( $locale ) ) {
							ewt_add_notice( new WP_Error( 'ewt_download_mo', __( 'The language was created, but the WordPress language file was not downloaded. Please install it manually.', 'easy-wp-translator' ), 'warning' ) );
						}

						// Force checking for themes and plugins translations updates
						wp_clean_themes_cache();
						wp_clean_plugins_cache();
					}
				}
		
				break;

			case 'delete':
				check_admin_referer( 'delete-lang' );

				if ( ! empty( $_GET['lang'] ) && $this->model->delete_language( (int) $_GET['lang'] ) ) {
					ewt_add_notice( new WP_Error( 'ewt_languages_deleted', __( 'Language deleted.', 'easy-wp-translator' ), 'success' ) );
				}

				
				break;

			case 'update':
				check_admin_referer( 'add-lang', '_wpnonce_add-lang' );
				$sanitized_data = array(
					'lang_id' => absint( wp_unslash( $_POST['lang_id'] ?? 0 ) ),
					'name' => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
					'slug' => sanitize_key( wp_unslash( $_POST['slug'] ?? '' ) ),
					'locale' => sanitize_locale_name( wp_unslash( $_POST['locale'] ?? '' ) ),
					'rtl' => isset( $_POST['rtl'] ) ? (bool) $_POST['rtl'] : false,
					'term_group' => isset( $_POST['term_group'] ) ? (int) $_POST['term_group'] : 0,
					'flag' => sanitize_text_field( wp_unslash( $_POST['flag'] ?? '' ) ),
				);
				$errors = $this->model->update_language( $sanitized_data );

				if ( is_wp_error( $errors ) ) {
					ewt_add_notice( $errors );
				} else {
					ewt_add_notice( new WP_Error( 'ewt_languages_updated', __( 'Language updated.', 'easy-wp-translator' ), 'success' ) );
				}


				break;

			case 'default-lang':
				check_admin_referer( 'default-lang' );

				if ( $lang = $this->model->get_language( (int) $_GET['lang'] ) ) {
					$this->model->update_default_lang( $lang->slug );
				}


				break;

			case 'content-default-lang':
				check_admin_referer( 'content-default-lang' );

				$this->model->set_language_in_mass();


				break;

			case 'activate':
				check_admin_referer( 'ewt_activate' );
				if ( isset( $_GET['module'] ) ) {
					$module = sanitize_key( $_GET['module'] );
					if ( isset( $this->modules[ $module ] ) ) {
						$this->modules[ $module ]->activate();
					}
				}

				break;

			case 'deactivate':
				check_admin_referer( 'ewt_deactivate' );
				if ( isset( $_GET['module'] ) ) {
					$module = sanitize_key( $_GET['module'] );
					if ( isset( $this->modules[ $module ] ) ) {
						$this->modules[ $module ]->deactivate();
					}
				}
				break;

			default:
				/**
				 * Fires when a non default action has been sent to EasyWPTranslator settings
				 *
				 *  
				 */
				do_action( "ewt_action_$action" );
				break;
		}
		self::redirect();
	}

	/**
	 * Displays the 3 tabs pages: languages, strings translations, settings
	 * Also manages user input for these pages
	 *
	 *  
	 *
	 * @return void
	 */
	public function languages_page() {

		// Custom Fields
		if($this->selected_tab === 'custom-fields' && class_exists(Custom_Fields::class)){
			$this->header->header();
			Custom_Fields::get_instance()->ewt_render_custom_fields_page();
			return;
		}

		// Support Blocks
		if($this->selected_tab === 'supported-blocks' && class_exists(Supported_Blocks::class)){
			$this->header->header();
			Supported_Blocks::get_instance()->ewt_render_support_blocks_page();
			return;
		}

		// return if the active tab is localizations
		if($this->active_tab === 'localizations'){
			return;
		}
		
		// Check if this is a settings tab (not lang, strings, or wizard which has its own handling)
		$is_settings_tab = ! in_array( $this->active_tab, array( 'lang', 'strings', 'wizard' ), true );
		
		if ( $is_settings_tab ) {
			// Handle user input for legacy actions
			$action = isset( $_REQUEST['ewt_action'] ) && is_string( $_REQUEST['ewt_action'] ) ? sanitize_key( $_REQUEST['pll_action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( ! empty( $action ) ) {
				$this->handle_actions( $action );
			}

			// Render the React container for settings
			$this->header->header();
			echo '<div class="wrap ewt-styles">';
			echo '<div id="ewt-settings"></div>';
			echo '</div>';
			return;
		}

		// Original logic for lang and strings tabs
		switch ( $this->active_tab ) {
			case 'lang':
				// Prepare the list table of languages
				$list_table = new EWT_Table_Languages();
				$list_table->prepare_items( $this->model->get_languages_list() );
				break;

			case 'strings':
				$string_table = new EWT_Table_String( $this->model->get_languages_list() );
				$string_table->prepare_items();
				break;
		}

		// Handle user input
		$action = isset( $_REQUEST['ewt_action'] ) ? sanitize_key( $_REQUEST['ewt_action'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'edit' === $action && ! empty( $_GET['lang'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			// phpcs:ignore WordPress.Security.NonceVerification, VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$edit_lang = $this->model->get_language( (int) $_GET['lang'] );
		} elseif ( ! empty( $action ) ) {
			$this->handle_actions( $action );
		}

		// Displays the page
		$modules    = $this->modules;
		$active_tab = $this->active_tab;
		$header = $this->header;
		include __DIR__ . '/../views/view-languages.php';
	}

	/**
	 * Get synchronization options formatted for JavaScript
	 *
	 *  
	 *
	 * @return array Array of sync options with label and value
	 */
	private function get_sync_options() {
		// Use the static method from EWT_Settings_Sync to get sync options
		if ( class_exists( 'EasyWPTranslator\Modules\sync\EWT_Settings_Sync' ) ) {
			$sync_metas = \EasyWPTranslator\Modules\sync\EWT_Settings_Sync::list_metas_to_sync();
			
			// Format for JavaScript consumption
			$formatted_options = array();
			foreach ( $sync_metas as $value => $label ) {
				$formatted_options[] = array(
					'label' => $label,
					'value' => $value,
				);
			}
			
			return $formatted_options;
		}
		
		// Fallback to empty array if class not available
		return array();
	}

	/**
	 * Get language switcher options formatted for JavaScript
	 *
	 *  
	 *
	 * @return array Array of language switcher options with label and value
	 */
	private function get_language_switcher_options() {
        $language_switcher_options = array(
            array(
                'label' => __( 'Classic (Widgets) Based', 'easy-wp-translator' ),
                'value' => 'default',
				'subheading' => 'Standard language switcher widget that can be added to widget areas and sidebars.'
            ),
            array(
                'label' => __( 'Block Based', 'easy-wp-translator' ),
                'value' => 'block',
				'subheading' => 'Gutenberg block widget for the block editor, compatible with modern WordPress themes.'
            )
        );
        if(ewt_is_plugin_active('elementor/elementor.php')){
            $language_switcher_options[] = array(
                'label' => __( 'Elementor Widget Based', 'easy-wp-translator' ),
                'value' => 'elementor',
				'subheading' => 'Specialized widget for Elementor page builder with enhanced styling and customization options.'
            );
        }
        return $language_switcher_options;
    }  

	/**
	 * Enqueues scripts and styles
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		parent::admin_enqueue_scripts();

		// Check if this is a settings tab (not lang, strings, or wizard which has its own handling)
		$is_settings_tab = ! in_array( $this->active_tab, array( 'lang', 'strings', 'wizard' ), true );
		$active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : false;
		$supported_blocks_tab = $is_settings_tab && $active_tab === 'supported-blocks';
		$custom_fields_tab = $is_settings_tab && $active_tab === 'custom-fields';
		
		if ( $is_settings_tab && (!$active_tab || empty($active_tab) || 'strings' !== $active_tab) && !$supported_blocks_tab && !$custom_fields_tab) {
			// Enqueue React-based settings for settings tabs
			$asset_file = plugin_dir_path( EASY_WP_TRANSLATOR_ROOT_FILE ) . 'admin/assets/frontend/settings/settings.asset.php';

			if ( ! file_exists( $asset_file ) ) {
				return;
			}

			$asset = require $asset_file;
			
			$this->header->header_assets();
			// Enqueue header assets

			$translations_data=array('total_string_count' => 0, 'total_character_count' => 0, 'total_time_taken' => 0, 'service_providers' => array());
			if(EWT_Translation_Dashboard::class){
				$avilable_service_providers = array('localAiTranslator'=>'Chrome AI Translator');
				$cpt_dashboard_data=EWT_Translation_Dashboard::get_translation_data('ewt');
				$translation_providers=(isset($cpt_dashboard_data['service_providers']) && is_array($cpt_dashboard_data['service_providers'])) ? $cpt_dashboard_data['service_providers'] : array();
				$translations_data['total_string']=isset($cpt_dashboard_data['total_string_count']) ? $this->ewt_format_number($cpt_dashboard_data['total_string_count'], 'easy-wp-translator') : 0;
				$translations_data['total_character']=isset($cpt_dashboard_data['total_character_count']) ? $this->ewt_format_number($cpt_dashboard_data['total_character_count'], 'easy-wp-translator') : 0;
				$translations_data['total_time']=isset($cpt_dashboard_data['total_time_taken']) ? $this->ewt_format_time_taken($cpt_dashboard_data['total_time_taken'], 'easy-wp-translator') : 0;
				$translations_data['total_pages']=isset($cpt_dashboard_data['data']) ? count($cpt_dashboard_data['data']) : 0;
				$translations_data['service_providers']=array_map(function($item) use ($avilable_service_providers){
					return $avilable_service_providers[$item];
				}, $translation_providers);
			}

			// Enqueue React-based settings script
			wp_enqueue_script(
				'ewt_settings',
				plugins_url( 'admin/assets/frontend/settings/settings.js', EASY_WP_TRANSLATOR_ROOT_FILE ),
				$asset['dependencies'],
				$asset['version'],
				true
			);

			// Localize script with settings data
			wp_localize_script(
				'ewt_settings',
				'ewt_settings',
				array(
					'dismiss_notice' => esc_html__( 'Dismiss this notice.', 'easy-wp-translator' ),
					'api_url'        => rest_url( 'ewt/v1/' ),
					'nonce'          => wp_create_nonce( 'wp_rest' ),
					'languages'      => $this->model->get_languages_list(),
					'all_languages'  => self::get_predefined_languages(),
					'home_url'       => get_home_url(),
					'modules'        => ( $this->modules ? array_keys( $this->modules ) : array() ),
					'active_tab'     => $this->active_tab,
					'sync_options'   => $this->get_sync_options(),
					'language_switcher_options' => $this->get_language_switcher_options(),
					'translations_data' => $translations_data,
				)
			);
			wp_localize_script(
				'ewt_settings',
				'ewt_settings_logo_data',
				[
					'logoUrl' => plugin_dir_url(EASY_WP_TRANSLATOR_ROOT_FILE) . '/assets/logo/',
					'nonce' => wp_create_nonce('wp_rest'),
					'restUrl' => rest_url('ewt/v1/'),
				]
			);

			// Enqueue styles
			wp_enqueue_style(
				'ewt_settings',
				plugins_url( 'admin/assets/css/build/main.css', EASY_WP_TRANSLATOR_ROOT_FILE ),
				array(),
				EASY_WP_TRANSLATOR_VERSION
			);

			
		} else if($supported_blocks_tab){
			$this->header->header_assets();
			if(class_exists(Supported_Blocks::class)){
				Supported_Blocks::enqueue_editor_assets();
			}
		}else if($custom_fields_tab){
			$this->header->header_assets();
			if(class_exists(Custom_Fields::class)){
				Custom_Fields::enqueue_editor_assets();
			}
		}
		else {
			$this->header->header_assets();

			// Original scripts for lang and strings tabs
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_enqueue_script( 'ewt_settings', plugins_url( 'admin/assets/js/build/settings' . $suffix . '.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array( 'jquery', 'wp-ajax-response', 'postbox', 'jquery-ui-selectmenu', 'wp-hooks' ), EASY_WP_TRANSLATOR_VERSION, true );
			wp_localize_script( 'ewt_settings', 'ewt_settings', array( 'dismiss_notice' => esc_html__( 'Dismiss this notice.', 'easy-wp-translator' ) ) );

			wp_enqueue_style( 'ewt_selectmenu', plugins_url( 'admin/assets/css/build/selectmenu' . $suffix . '.css', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION );
		}
	}

	function ewt_format_time_taken($time_taken) {
		if ($time_taken === 0) return esc_html__('0', 'easy-wp-translator');
		if ($time_taken < 60) return sprintf(esc_html__('%d sec', 'easy-wp-translator'), $time_taken);
		if ($time_taken < 3600) {
			$min = floor($time_taken / 60);
			$sec = $time_taken % 60;
			return sprintf(esc_html__('%d min %d sec', 'easy-wp-translator'), $min, $sec);
		}
		$hours = floor($time_taken / 3600);
		$min = floor(($time_taken % 3600) / 60);
		return sprintf(esc_html__('%d hours %d min', 'easy-wp-translator'), $hours, $min);
	}

	public function ewt_format_number($number, $text_domain) {
		if ($number >= 1000000000) {
			return round($number / 1000000000, 1) . esc_html__('B', $text_domain);
		} elseif ($number >= 1000000) {
			return round($number / 1000000, 1) . esc_html__('M', $text_domain);
		} elseif ($number >= 1000) {
			return round($number / 1000, 1) . esc_html__('K', $text_domain);
		}
		return $number;
	}

	/**
	 * Displays a notice when there are objects with no language assigned
	 *
	 *  
	 *
	 * @return void
	 */
	public function notice_objects_with_no_lang() {
		if ( ! empty( $this->options['default_lang'] ) && $this->model->get_objects_with_no_lang( 1 ) ) {
			printf(
				'<div class="error"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'There are posts, pages, categories or tags without language.', 'easy-wp-translator' ),
				esc_url( wp_nonce_url( '?page=ewt&ewt_action=content-default-lang&noheader=true', 'content-default-lang' ) ),
				esc_html__( 'You can set them all to the default language.', 'easy-wp-translator' )
			);
		}
	}

	/**
	 * Redirects to language page ( current active tab )
	 * saves error messages in a transient for reuse in redirected page
	 *
	 *  
	 *
	 * @param array $args query arguments to add to the url
	 * @return void
	 */
	public static function redirect( array $args = array() ): void {
		$errors = get_settings_errors( 'easy-wp-translator' );
		if ( ! empty( $errors ) ) {
			set_transient( 'ewt_settings_errors', $errors, 30 );
			$args['settings-updated'] = 1;
		}
		// Remove possible 'ewt_action' and 'lang' query args from the referer before redirecting
		wp_safe_redirect( add_query_arg( $args, remove_query_arg( array( 'ewt_action', 'lang' ), wp_get_referer() ) ) );
		exit;
	}

	/**
	 * Get the list of predefined languages
	 *
	 *  
	 *
	 * @return string[][] {
	 *   An array of array of language properties.
	 *
	 *   @type string[] {
	 *      @type string $code     ISO 639-1 language code.
	 *      @type string $locale   WordPress locale.
	 *      @type string $name     Native language name.
	 *      @type string $dir      Text direction: 'ltr' or 'rtl'.
	 *      @type string $flag     Flag code, generally the country code.
	 *      @type string $w3c      W3C locale.
	 *      @type string $facebook Facebook locale.
	 *   }
	 * }
	 */
	public static function get_predefined_languages() {
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';

		$languages    = include __DIR__ . '/languages.php';
		$translations = wp_get_available_translations();

		// Keep only languages with existing WP language pack
		// Unless the transient has expired and we don't have an internet connection to refresh it
		if ( ! empty( $translations ) ) {
			$translations['en_US'] = ''; // Languages packs don't include en_US
			$languages = array_intersect_key( $languages, $translations );
		}

		/**
		 * Filter the list of predefined languages
		 *
		 *  
		 *   The languages arrays use associative keys instead of numerical keys
		 *
		 * @param array $languages
		 */
		$languages = apply_filters( 'ewt_predefined_languages', $languages );

		// Keep only languages with all necessary information
		foreach ( $languages as $k => $lang ) {
			if ( ! isset( $lang['code'], $lang['locale'], $lang['name'], $lang['dir'], $lang['flag'] ) ) {
				unset( $languages[ $k ] );
			}
		}

		return $languages;
	}
}
