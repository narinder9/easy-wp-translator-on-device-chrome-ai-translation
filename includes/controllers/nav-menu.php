<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Options\Options;



/**
 * Manages custom menus translations
 * Common to admin and frontend for the customizer
 *
 *  	
 */
class EWT_Nav_Menu {
	/**
	 * Stores the plugin options.
	 *
	 * @var Options
	 */
	public $options;

	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * Theme name.
	 *
	 * @var string
	 */
	protected $theme;

	/**
	 * Array of menu ids in a given language used when auto add pages to menus.
	 *
	 * @var int[]
	 */
	protected $auto_add_menus = array();

	/**
	 * Constructor: setups filters and actions.
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		$this->model = &$easywptranslator->model;
		$this->options = &$easywptranslator->options;

		$this->theme = get_option( 'stylesheet' );

		add_filter( 'wp_setup_nav_menu_item', array( $this, 'wp_setup_nav_menu_item' ) );

		// Integration with WP customizer
		add_action( 'customize_register', array( $this, 'create_nav_menu_locations' ), 5 );

		// Filter _wp_auto_add_pages_to_menu by language
		add_action( 'transition_post_status', array( $this, 'auto_add_pages_to_menu' ), 5, 3 ); // before _wp_auto_add_pages_to_menu
	}

	/**
	 * Assigns the title and label to the language switcher menu items
	 *
	 *  
	 *
	 * @param stdClass $item Menu item.
	 * @return stdClass
	 */
	public function wp_setup_nav_menu_item( $item ) {
		if ( isset( $item->url ) && '#ewt_switcher' === $item->url ) {
			$item->post_title = __( 'Languages', 'easy-wp-translator' );
			$item->type_label = __( 'Language switcher', 'easy-wp-translator' );
		}
		return $item;
	}

	/**
	 * Create temporary nav menu locations ( one per location and per language ) for all non-default language
	 * to do only one time
	 *
	 *  
	 *
	 * @return void
	 */
	public function create_nav_menu_locations() {
		static $once;
		global $_wp_registered_nav_menus;
		global $easywptranslator;
		$arr = array();

		if ( isset( $_wp_registered_nav_menus ) && ! $once ) {
			foreach ( $_wp_registered_nav_menus as $loc => $name ) {
				// Get languages to show - check if we're in admin with language filter
				$languages_to_show = $this->model->get_languages_list();
				$filter_lang = ! empty( $easywptranslator->filter_lang ) ? $easywptranslator->filter_lang : null;
					
				// Determine which languages to show based on filter
				if ( $filter_lang ) {
					// Show only the selected language
					$languages_to_show = array( $filter_lang );
				}
				foreach ( $languages_to_show as $lang ) {
					$arr[ $this->combine_location( $loc, $lang ) ] = $name . ' ' . $lang->name;
				}
			}

			$_wp_registered_nav_menus = $arr;
			$once = true;
		}
	}

	/**
	 * Creates a temporary nav menu location from a location and a language
	 *
	 *  
	 *
	 * @param string       $loc  Nav menu location.
	 * @param EWT_Language $lang Language object.
	 * @return string
	 */
	public function combine_location( $loc, $lang ) {
		return $loc . ( strpos( $loc, '___' ) || $lang->is_default ? '' : '___' . $lang->slug );
	}

	/**
	 * Get nav menu locations and language from a temporary location.
	 *
	 *  
	 *
	 * @param string $loc Temporary location.
	 * @return string[] {
	 *   @type string $location Nav menu location.
	 *   @type string $lang     Language code.
	 * }
	 */
	public function explode_location( $loc ) {
		$infos = explode( '___', $loc );
		if ( 1 == count( $infos ) ) {
			$infos[] = $this->options['default_lang'];
		}
		return array_combine( array( 'location', 'lang' ), $infos );
	}

	/**
	 * Filters the option nav_menu_options for auto added pages to menu.
	 *
	 *  
	 *
	 * @param array $options Options stored in the option nav_menu_options.
	 * @return array Modified options.
	 */
	public function nav_menu_options( $options ) {
		$options['auto_add'] = array_intersect( $options['auto_add'], $this->auto_add_menus );
		return $options;
	}

	/**
	 * Filters _wp_auto_add_pages_to_menu by language.
	 *
	 *  
	 *
	 * @param string  $new_status Transition to this post status.
	 * @param string  $old_status Previous post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function auto_add_pages_to_menu( $new_status, $old_status, $post ) {
		if ( 'publish' != $new_status || 'publish' == $old_status || 'page' != $post->post_type || ! empty( $post->post_parent ) ) {
			return;
		}

		if ( ! empty( $this->options['nav_menus'][ $this->theme ] ) ) {
			$lang = $this->model->post->get_language( $post->ID );
			$lang = empty( $lang ) ? $this->options['default_lang'] : $lang->slug; // If the page has no language yet, the default language will be assigned

			// Get all the menus in the page language
			foreach ( $this->options['nav_menus'][ $this->theme ] as $loc ) {
				if ( ! empty( $loc[ $lang ] ) ) {
					$this->auto_add_menus[] = $loc[ $lang ];
				}
			}

			add_filter( 'option_nav_menu_options', array( $this, 'nav_menu_options' ) );
		}
	}
}
