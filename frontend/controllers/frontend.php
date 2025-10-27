<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Base\EWT_Base;
use EasyWPTranslator\Frontend\Controllers\EWT_Choose_Lang_Content;
use EasyWPTranslator\Frontend\Controllers\EWT_Choose_Lang_Url;
use EasyWPTranslator\Frontend\Controllers\EWT_Choose_Lang_Domain;
use EasyWPTranslator\Frontend\Controllers\EWT_Frontend_Auto_Translate;
use EasyWPTranslator\Frontend\Controllers\EWT_Frontend_Nav_Menu;
use EasyWPTranslator\Frontend\Controllers\EWT_Frontend_Static_Pages;
use EasyWPTranslator\Frontend\Filters\EWT_Frontend_Filters;
use EasyWPTranslator\Frontend\Filters\EWT_Frontend_Filters_Links;
use EasyWPTranslator\Frontend\Filters\EWT_Frontend_Filters_Search;
use EasyWPTranslator\Frontend\Filters\EWT_Frontend_Filters_Widgets;
use EasyWPTranslator\Frontend\Services\EWT_Frontend_Links;
use EasyWPTranslator\Includes\Helpers\EWT_Default_Term;
use EasyWPTranslator\Includes\Other\EWT_Query;
use EasyWPTranslator\Frontend\Services\EWT_Canonical;
use EasyWPTranslator\Includes\Other\EWT_Switch_Language;



/**
 * Main EasyWPTranslator class when on frontend, accessible from @see EWT().
 *
 *  
 */
#[AllowDynamicProperties]
class EWT_Frontend extends EWT_Base {
	/**
	 * Current language.
	 *
	 * @var EWT_Language|null
	 */
	public $curlang;

	/**
	 * @var EWT_Frontend_Auto_Translate|null
	 */
	public $auto_translate;

	/**
	 * The class selecting the current language.
	 *
	 * @var EWT_Choose_Lang|null
	 */
	public $choose_lang;

	/**
	 * @var EWT_Frontend_Filters|null
	 */
	public $filters;

	/**
	 * @var EWT_Frontend_Filters_Links|null
	 */
	public $filters_links;

	/**
	 * @var EWT_Frontend_Filters_Search|null
	 */
	public $filters_search;

	/**
	 * @var EWT_Frontend_Links|null
	 */
	public $links;

	/**
	 * @var EWT_Default_Term|null
	 */
	public $default_term;

	/**
	 * @var EWT_Frontend_Nav_Menu|null
	 */
	public $nav_menu;

	/**
	 * @var EWT_Frontend_Static_Pages|null
	 */
	public $static_pages;

	/**
	 * @var EWT_Frontend_Filters_Widgets|null
	 */
	public $filters_widgets;

	/**
	 * @var EWT_Canonical
	 */
	public $canonical;

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
	public $rest;

	// Additional dynamic properties
	/**
	 * @var mixed
	 */
	public $pref_lang;

	/**
	 * @var mixed
	 */
	public $filter_lang;

	// Block module properties
	/**
	 * @var mixed
	 */
	public $switcher_block;

	/**
	 * @var mixed
	 */
	public $navigation_block;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Links_Model $links_model Reference to the links model.
	 */
	public function __construct( &$links_model ) {
		parent::__construct( $links_model );

		add_action( 'ewt_language_defined', array( $this, 'ewt_language_defined' ), 1 );

		// Avoids the language being the queried object when querying multiple taxonomies
		add_action( 'parse_tax_query', array( $this, 'parse_tax_query' ), 1 );

		// Filters posts by language
		add_action( 'parse_query', array( $this, 'parse_query' ), 6 );

		// Not before 'check_canonical_url'
		if ( ! defined( 'EWT_AUTO_TRANSLATE' ) || EWT_AUTO_TRANSLATE ) {
			add_action( 'template_redirect', array( $this, 'auto_translate' ), 7 );
		}

		add_action( 'admin_bar_menu', array( $this, 'remove_customize_admin_bar' ), 41 ); // After WP_Admin_Bar::add_menus

		/*
		 * Static front page and page for posts.
		 *
		 * Early instantiated to be able to correctly initialize language properties.
		 * Also loaded in customizer preview, directly reading the request as we act before WP.
		 */
		if ( 'page' === get_option( 'show_on_front' ) || ( isset( $_REQUEST['wp_customize'] ) && 'on' === $_REQUEST['wp_customize'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$this->static_pages = new EWT_Frontend_Static_Pages( $this );
		}

		$this->model->set_languages_ready();
	}

	/**
	 * Setups the language chooser based on options
	 *
	 *  
	 */
	public function init() {
		parent::init();

		$this->links = new EWT_Frontend_Links( $this );

		$this->default_term = new EWT_Default_Term( $this );
		$this->default_term->add_hooks();

		// Setup the language chooser
		$c = array( 'Content', 'Url', 'Url', 'Domain' );
		$class_map = array(
			'EWT_Choose_Lang_Content' => EWT_Choose_Lang_Content::class,
			'EWT_Choose_Lang_Url'     => EWT_Choose_Lang_Url::class,
			'EWT_Choose_Lang_Domain'  => EWT_Choose_Lang_Domain::class,
		);
		$class_name = 'EWT_Choose_Lang_' . $c[ $this->options['force_lang'] ];
		$class = $class_map[ $class_name ];
		$this->choose_lang = new $class( $this );
		$this->choose_lang->init();

		// Need to load nav menu class early to correctly define the locations in the customizer when the language is set from the content
		$this->nav_menu = new EWT_Frontend_Nav_Menu( $this );
	}

	/**
	 * Setups filters and nav menus once the language has been defined
	 *
	 *  
	 *
	 * @return void
	 */
	public function ewt_language_defined() {
		// Filters
		$this->filters_links = new EWT_Frontend_Filters_Links( $this );
		$this->filters = new EWT_Frontend_Filters( $this );
		$this->filters_search = new EWT_Frontend_Filters_Search( $this );
		$this->filters_widgets = new EWT_Frontend_Filters_Widgets( $this );

		/*
		 * Redirects to canonical url before WordPress redirect_canonical
		 * but after Nextgen Gallery which hacks $_SERVER['REQUEST_URI'] !!!
		 * and restores it in 'template_redirect' with priority 1.
		 */
		$this->canonical = new EWT_Canonical( $this );
		add_action( 'template_redirect', array( $this->canonical, 'check_canonical_url' ), 4 );

		// Auto translate for Ajax
		if ( ( ! defined( 'EWT_AUTO_TRANSLATE' ) || EWT_AUTO_TRANSLATE ) && wp_doing_ajax() ) {
			$this->auto_translate();
		}
	}

	/**
	 * When querying multiple taxonomies, makes sure that the language is not the queried object.
	 *
	 *  
	 *
	 * @param WP_Query $query WP_Query object.
	 * @return void
	 */
	public function parse_tax_query( $query ) {
		$ewt_query = new EWT_Query( $query, $this->model );
		$queried_taxonomies = $ewt_query->get_queried_taxonomies();

		if ( ! empty( $queried_taxonomies ) && 'ewt_language' == reset( $queried_taxonomies ) ) {
			$query->tax_query->queried_terms['ewt_language'] = array_shift( $query->tax_query->queried_terms );
		}
	}

	/**
	 * Modifies some query vars to "hide" that the language is a taxonomy and avoid conflicts.
	 *
	 *  
	 *
	 * @param WP_Query $query WP_Query object.
	 * @return void
	 */
	public function parse_query( $query ) {
		$qv = $query->query_vars;
		$ewt_query = new EWT_Query( $query, $this->model );
		$taxonomies = $ewt_query->get_queried_taxonomies();

		// Allow filtering recent posts and secondary queries by the current language
		if ( ! empty( $this->curlang ) ) {
			$ewt_query->filter_query( $this->curlang );
		}

		// Modifies query vars when the language is queried
		if ( ! empty( $qv['ewt_lang'] ) || ( ! empty( $taxonomies ) && array( 'ewt_language' ) == array_values( $taxonomies ) ) ) {
			// Do we query a custom taxonomy?
			$taxonomies = array_diff( $taxonomies, array( 'ewt_language', 'category', 'post_tag' ) );

			// Remove pages query when the language is set unless we do a search
			// Take care not to break the single page, attachment and taxonomies queries!
			if ( empty( $qv['post_type'] ) && ! $query->is_search && ! $query->is_singular && empty( $taxonomies ) && ! $query->is_category && ! $query->is_tag ) {
				$query->set( 'post_type', 'post' );
			}

			// Unset the is_archive flag for language pages to prevent loading the archive template
			// Keep archive flag for comment feed otherwise the language filter does not work
			if ( empty( $taxonomies ) && ! $query->is_comment_feed && ! $query->is_post_type_archive && ! $query->is_date && ! $query->is_author && ! $query->is_category && ! $query->is_tag ) {
				$query->is_archive = false;
			}

			// Unset the is_tax flag except if another custom tax is queried
			if ( empty( $taxonomies ) && ( $query->is_category || $query->is_tag || $query->is_author || $query->is_post_type_archive || $query->is_date || $query->is_search || $query->is_feed ) ) {
				$query->is_tax = false;
				unset( $query->queried_object ); // FIXME useless?
			}
		}
	}

	/**
	 * Auto translate posts and terms ids
	 *
	 *  
	 *
	 * @return void
	 */
	public function auto_translate() {
		$this->auto_translate = new EWT_Frontend_Auto_Translate( $this );
	}

	/**
	 * Resets some variables when the blog is switched.
	 * Overrides the parent method.
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

		parent::switch_blog( $new_blog_id, $prev_blog_id );

		// Need to check that some languages are defined when user is logged in, has several blogs, some without any languages.
		if ( ! $this->is_active_on_current_site() || ! $this->model->has_languages() || ! did_action( 'ewt_language_defined' ) ) {
			return;
		}

		static $restore_curlang;

		if ( empty( $restore_curlang ) ) {
			$restore_curlang = $this->curlang->slug; // To always remember the current language through blogs.
		}

		$lang          = $this->model->get_language( $restore_curlang );
		$this->curlang = $lang ?: $this->model->get_default_language();
		if ( empty( $this->curlang ) ) {
			return;
		}

		if ( isset( $this->static_pages ) ) {
			$this->static_pages->init();
		}

		// Send the slug instead of the locale here to avoid conflicts with same locales.
		EWT_Switch_Language::load_strings_translations( $this->curlang->slug );
	}

	/**
	 * Remove the customize admin bar on front-end when using a block theme.
	 *
	 * WordPress removes the Customizer menu if a block theme is activated and no other plugins interact with it.
	 * As EasyWPTranslator interacts with the Customizer, we have to delete this menu ourselves in the case of a block theme,
	 * unless another plugin than EasyWPTranslator interacts with the Customizer.
	 *
	 *  
	 *
	 * @return void
	 */
	public function remove_customize_admin_bar() {
		if ( ! $this->should_customize_menu_be_removed() ) {
			return;
		}

		global $wp_admin_bar;

		remove_action( 'wp_before_admin_bar_render', 'wp_customize_support_script' ); // To avoid the script launch.
		$wp_admin_bar->remove_menu( 'customize' );
	}
}


