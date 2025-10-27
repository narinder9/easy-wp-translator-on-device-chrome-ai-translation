<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Base;



if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base class for both admin and frontend
 *
 *  
 */
use EasyWPTranslator\Modules\REST\Request;
use EasyWPTranslator\Includes\Core\EasyWPTranslator;
use EasyWPTranslator\Includes\Services\Crud\EWT_CRUD_Posts;
use EasyWPTranslator\Includes\Services\Crud\EWT_CRUD_Terms;
use EasyWPTranslator\Includes\Options\EWT_Translate_Option;
use EasyWPTranslator\Includes\Helpers\EWT_MO;
use EasyWPTranslator\Includes\Widgets\EWT_Widget_Languages;
use EasyWPTranslator\Includes\Widgets\EWT_Widget_Calendar;
use EasyWPTranslator\Includes\Other\EWT_Model;
use EasyWPTranslator\Includes\Other\EWT_Switch_Language;
use WP_Hook;



#[AllowDynamicProperties]
abstract class EWT_Base {
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
	 * Registers hooks on insert / update post related actions and filters.
	 *
	 * @var EWT_CRUD_Posts|null
	 */
	public $posts;

	/**
	 * Registers hooks on insert / update term related action and filters.
	 *
	 * @var EWT_CRUD_Terms|null
	 */
	public $terms;
	/**
	 * @var Request
	 */
	public $request;

	/**
	 * Navigation menu handler.
	 *
	 * @var mixed
	 */
	public $nav_menu;

	/**
	 * Static pages handler.
	 *
	 * @var mixed
	 */
	public $static_pages;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Links_Model $links_model Links Model.
	 */
	public function __construct( &$links_model ) {
		$this->links_model = &$links_model;
		$this->model = &$links_model->model;
		$this->options = &$this->model->options;
		$this->request     = new Request( $this->model );

		EWT_Switch_Language::init( $this->model );

		$GLOBALS['l10n_unloaded']['ewt_string'] = true; // Short-circuit _load_textdomain_just_in_time() for 'ewt_string' domain in WP 4.6+

		add_action( 'widgets_init', array( $this, 'widgets_init' ) );

		// User defined strings translations
		add_action( 'ewt_language_defined', array( $this, 'load_strings_translations' ), 5 );
		add_action( 'change_locale', array( $this, 'load_strings_translations' ) ); // Since WP 4.7
		add_action( 'personal_options_update', array( $this, 'load_strings_translations' ), 1, 0 ); // Before WP, for confirmation request when changing the user email.
		add_action( 'lostpassword_post', array( $this, 'load_strings_translations' ), 10, 0 ); // Password reset email.
		// Switch_to_blog
		add_action( 'switch_blog', array( $this, 'switch_blog' ), 10, 2 );
	}

	/**
	 * Instantiates classes reacting to CRUD operations on posts and terms,
	 * only when at least one language is defined.
	 *
	 *  
	 *
	 * @return void
	 */
	public function init() {
		if ( $this->model->has_languages() ) {
			$this->posts = new EWT_CRUD_Posts( $this );
			$this->terms = new EWT_CRUD_Terms( $this );

			// WordPress options.
			new EWT_Translate_Option( 'blogname', array(), array( 'context' => 'WordPress' ) );
			new EWT_Translate_Option( 'blogdescription', array(), array( 'context' => 'WordPress' ) );
			new EWT_Translate_Option( 'date_format', array(), array( 'context' => 'WordPress' ) );
			new EWT_Translate_Option( 'time_format', array(), array( 'context' => 'WordPress' ) );
		}
	}

	/**
	 * Registers our widgets
	 *
	 *  
	 *
	 * @return void
	 */
	public function widgets_init() {
		if ( ewt_is_switcher_type_enabled( 'default' ) ) {
			register_widget( EWT_Widget_Languages::class );
		}

		// Overwrites the calendar widget to filter posts by language
		if ( ! defined( 'EWT_WIDGET_CALENDAR' ) || EWT_WIDGET_CALENDAR ) {
			unregister_widget( 'WP_Widget_Calendar' );
			register_widget( EWT_Widget_Calendar::class );
		}
	}

	/**
	 * Loads user defined strings translations
	 *
	 *  
	 *   $locale parameter added.
	 *
	 * @param string $locale Language locale or slug. Defaults to current locale.
	 * @return void
	 */
	public function load_strings_translations( $locale = '' ) {
		if ( empty( $locale ) ) {
			$locale = ( is_admin() && ! EasyWPTranslator::is_ajax_on_front() ) ? get_user_locale() : get_locale();
		}

		$language = $this->model->get_language( $locale );

		if ( ! empty( $language ) ) {
			$mo = new EWT_MO();
			$mo->import_from_db( $language );
			$GLOBALS['l10n']['ewt_string'] = &$mo;
		} else {
			unset( $GLOBALS['l10n']['ewt_string'] );
		}
	}

	/**
	 * Resets some variables when the blog is switched.
	 * Applied only if EasyWPTranslator is active on the new blog.
	 *
	 *  
	 *
	 * @param int $new_blog_id  New blog ID.
	 * @param int $prev_blog_id Previous blog ID.
	 * @return void
	 */
	public function switch_blog( $new_blog_id, $prev_blog_id ) {
		if ( (int) $new_blog_id === (int) $prev_blog_id ) {
			// Do nothing if same blog.
			return;
		}

		$this->links_model->remove_filters();

		if ( $this->is_active_on_current_site() ) {
			$this->links_model = $this->model->get_links_model();
		}
	}

	/**
	 * Checks if EasyWPTranslator is active on the current blog (useful when the blog is switched).
	 *
	 *  
	 *
	 * @return bool
	 */
	protected function is_active_on_current_site(): bool {
		return ewt_is_plugin_active( EASY_WP_TRANSLATOR_BASENAME ) && ! empty( $this->options['version'] );
	}

	/**
	 * Check if the customize menu should be removed or not.
	 *
	 *  
	 *
	 * @return bool True if it should be removed, false otherwise.
	 */
	public function should_customize_menu_be_removed() {
		// Exit if a block theme isn't activated.
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return false;
		}

		return ! $this->is_customize_register_hooked();
	}

	/**
	 * Tells whether or not EasyWPTranslator or third party callbacks are hooked to `customize_register`.
	 *
	 *  
	 *
	 * @global $wp_filter
	 *
	 * @return bool True if EasyWPTranslator's callbacks are hooked, false otherwise.
	 */
	protected function is_customize_register_hooked() {
		global $wp_filter;

		if ( empty( $wp_filter['customize_register'] ) || ! $wp_filter['customize_register'] instanceof WP_Hook ) {
			return false;
		}

		/*
		 * 'customize_register' is hooked by:
		 * @see EWT_Nav_Menu::create_nav_menu_locations()
		 * @see EWT_Frontend_Static_Pages::filter_customizer()
		 */
		$floor = 0;
		if ( ! empty( $this->nav_menu ) && (bool) $wp_filter['customize_register']->has_filter( 'customize_register', array( $this->nav_menu, 'create_nav_menu_locations' ) ) ) {
			++$floor;
		}

		if ( ! empty( $this->static_pages ) && (bool) $wp_filter['customize_register']->has_filter( 'customize_register', array( $this->static_pages, 'filter_customizer' ) ) ) {
			++$floor;
		}

		$count = array_sum( array_map( 'count', $wp_filter['customize_register']->callbacks ) );

		return $count > $floor;
	}
}
