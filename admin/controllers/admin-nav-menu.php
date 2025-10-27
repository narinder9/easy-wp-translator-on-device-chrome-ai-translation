<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Admin\Controllers;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



use EasyWPTranslator\Includes\Controllers\EWT_Nav_Menu;
use EasyWPTranslator\Includes\Controllers\EWT_Switcher;



/**
 * Manages custom menus translations as well as the language switcher menu item on admin side
 *
 *  
 */
class EWT_Admin_Nav_Menu extends EWT_Nav_Menu {

	/**
	 * Current language (used to filter the content).
	 *
	 * @var EWT_Language|null
	 */
	public $filter_lang;

	/**
	 * Constructor: setups filters and actions
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		parent::__construct( $easywptranslator );
		
		// Reference to global filter language (same as posts/pages)
		$this->filter_lang = &$easywptranslator->filter_lang;

		// Populates nav menus locations
		// Since WP 4.4, must be done before customize_register is fired
		add_filter( 'theme_mod_nav_menu_locations', array( $this, 'theme_mod_nav_menu_locations' ), 20 );

		// Integration in the WP menu interface
		add_action( 'admin_init', array( $this, 'admin_init' ) ); // after EasyWPTranslator upgrade
	}

	/**
	 * Setups filters and terms
	 * adds the language switcher metabox and create new nav menu locations
	 *
	 *  
	 *
	 * @return void
	 */
	public function admin_init() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'wp_update_nav_menu_item', array( $this, 'wp_update_nav_menu_item' ), 10, 2 );

		// Translation of menus based on chosen locations
		add_filter( 'pre_update_option_theme_mods_' . $this->theme, array( $this, 'pre_update_option_theme_mods' ) );
		add_action( 'delete_nav_menu', array( $this, 'delete_nav_menu' ) );
		add_action( 'admin_footer', array( $this, 'ewt_nav_menu_language_controls' ), 10 );
		
		// Filter menu dropdown list by language
		add_filter( 'wp_get_nav_menus', array( $this, 'filter_nav_menus_by_language' ), 10, 1 );
		add_action( 'load-nav-menus.php', array( $this, 'maybe_update_selected_menu' ), 10 );
		add_action( 'admin_init', array( $this, 'maybe_update_selected_menu_on_init' ), 10 );
		add_filter( 'wp_redirect', array( $this, 'preserve_lang_param_on_redirect' ), 10, 2 );
		add_meta_box( 'ewt_lang_switch_box', __( 'Language switcher', 'easy-wp-translator' ), array( $this, 'lang_switch' ), 'nav-menus', 'side', 'high' );

		$this->create_nav_menu_locations();
	}

	/**
	 * Language switcher metabox
	 * The checkbox and all hidden fields are important
	 *
	 *  
	 *
	 * @return void
	 */
	public function lang_switch() {
		global $_nav_menu_placeholder, $nav_menu_selected_id;
		$_nav_menu_placeholder = 0 > $_nav_menu_placeholder ? $_nav_menu_placeholder - 1 : -1;
		?>
		<div id="posttype-lang-switch" class="posttypediv">
			<div id="tabs-panel-lang-switch" class="tabs-panel tabs-panel-active">
				<ul id="lang-switch-checklist" class="categorychecklist form-no-clear">
					<li>
						<label class="menu-item-title">
							<input type="checkbox" class="menu-item-checkbox" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-object-id]" value="-1" > <?php esc_html_e( 'Languages', 'easy-wp-translator' ); ?>
						</label>
						<input type="hidden" class="menu-item-type" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-type]" value="custom">
						<input type="hidden" class="menu-item-title" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-title]" value="<?php esc_attr_e( 'Languages', 'easy-wp-translator' ); ?>">
						<input type="hidden" class="menu-item-url" name="menu-item[<?php echo (int) $_nav_menu_placeholder; ?>][menu-item-url]" value="#ewt_switcher">
					</li>
				</ul>
			</div>
			<p class="button-controls">
				<span class="add-to-menu">
					<input type="submit" <?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu', 'easy-wp-translator' ); ?>" name="add-post-type-menu-item" id="submit-posttype-lang-switch">
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Prepares javascript to modify the language switcher menu item
	 *
	 *  
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( empty( $screen ) || 'nav-menus' !== $screen->base ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_script( 'ewt_nav_menu', plugins_url( "admin/assets/js/build/nav-menu{$suffix}.js", EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION, false );
		wp_enqueue_style( 'ewt_nav_menu_filter', plugins_url( "admin/assets/css/admin-nav-menu-filter.css", EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION );
		wp_enqueue_script( 'ewt_nav_menu_filter_js', plugins_url( "admin/assets/js/admin-nav-menu-filter.js", EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION, false );
		
		// Pass current language filter to JavaScript
		$current_filter_lang = ! empty( $this->filter_lang ) ? $this->filter_lang->slug : 'all';
		wp_localize_script( 'ewt_nav_menu_filter_js', 'ewt_nav_menu_filter', array(
			'current_lang' => $current_filter_lang
		) );
		$data = array(
			'strings' => EWT_Switcher::get_switcher_options( 'menu', 'string' ), // The strings for the options
			'title'   => __( 'Languages', 'easy-wp-translator' ), // The title
			'val'     => array(),
		);

		// Get all language switcher menu items
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- This query is intentionally using meta_key to fetch all nav_menu_item posts with our custom meta for language switcher options. This is required to gather all relevant menu items for admin JS config.
		$items = get_posts( array('numberposts' => -1, 'nopaging'  => true, 'post_type' => 'nav_menu_item', 'fields' => 'ids', 'meta_key' => '_ewt_menu_item'));

		// The options values for the language switcher
		foreach ( $items as $item ) {
			$data['val'][ $item ] = get_post_meta( $item, '_ewt_menu_item', true );
		}

		// Send all these data to javascript
		wp_localize_script( 'ewt_nav_menu', 'ewt_data', $data );
	}

	/**
	 * Save our menu item options.
	 *
	 *  
	 *
	 * @param int $menu_id         ID of the updated menu.
	 * @param int $menu_item_db_id ID of the updated menu item.
	 * @return void
	 */
	public function wp_update_nav_menu_item( $menu_id = 0, $menu_item_db_id = 0 ) {
		if ( empty( $_POST['menu-item-url'][ $menu_item_db_id ] ) || '#ewt_switcher' !== $_POST['menu-item-url'][ $menu_item_db_id ] ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		// Security check as 'wp_update_nav_menu_item' can be called from outside WP admin
		if ( current_user_can( 'edit_theme_options' ) ) {
			check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

			$options = array( 'hide_if_no_translation' => 0, 'hide_current' => 0, 'force_home' => 0, 'show_flags' => 0, 'show_names' => 1, 'dropdown' => 0 ); // Default values
			// Our jQuery form has not been displayed
			if ( empty( $_POST['menu-item-ewt-detect'][ $menu_item_db_id ] ) ) {
				if ( ! get_post_meta( $menu_item_db_id, '_ewt_menu_item', true ) ) { // Our options were never saved
					update_post_meta( $menu_item_db_id, '_ewt_menu_item', $options );
				}
			}
			else {
				foreach ( array_keys( $options ) as $opt ) {
					$options[ $opt ] = empty( $_POST[ 'menu-item-' . $opt ][ $menu_item_db_id ] ) ? 0 : 1;
				}
				update_post_meta( $menu_item_db_id, '_ewt_menu_item', $options ); // Allow us to easily identify our nav menu item
			}
		}
	}

	/**
	 * Assigns menu languages and translations based on (temporary) locations.
	 *
	 *  
	 *
	 * @param array $locations Nav menu locations.
	 * @return array
	 */
	public function update_nav_menu_locations( $locations ) {
		// Extract language and menu from locations.
		$nav_menus = $this->options->get( 'nav_menus' );

		foreach ( $locations as $loc => $menu ) {
			$infos = $this->explode_location( $loc );
			$nav_menus[ $this->theme ][ $infos['location'] ][ $infos['lang'] ] = $menu ?: 0;

			if ( $this->options->get( 'default_lang' ) !== $infos['lang'] ) {
				unset( $locations[ $loc ] ); // Remove temporary locations before database update.
			}
		}

		$this->options->set( 'nav_menus', $nav_menus );

		return $locations;
	}

	/**
	 * Assigns menu languages and translations based on (temporary) locations.
	 *
	 *  
	 *
	 * @param mixed $mods Theme mods.
	 * @return mixed
	 */
	public function pre_update_option_theme_mods( $mods ) {
		if ( current_user_can( 'edit_theme_options' ) && is_array( $mods ) && isset( $mods['nav_menu_locations'] ) ) {

			// Manage Locations tab in Appearance -> Menus
			if ( isset( $_GET['action'] ) && 'locations' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				check_admin_referer( 'save-menu-locations' );

				$nav_menus = $this->options->get( 'nav_menus' );
				$nav_menus[ $this->theme ] = array();
				$this->options->set( 'nav_menus', $nav_menus );
			}

			// Edit Menus tab in Appearance -> Menus
			// Add the test of $_POST['update-nav-menu-nonce'] to avoid conflict with Vantage theme
			elseif ( isset( $_POST['action'], $_POST['update-nav-menu-nonce'] ) && 'update' === $_POST['action'] ) {
				check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

				$nav_menus = $this->options->get( 'nav_menus' );
				$nav_menus[ $this->theme ] = array();
				$this->options->set( 'nav_menus', $nav_menus );
			}

			// Customizer
			// Don't reset locations in this case.
			elseif ( isset( $_POST['action'] ) && 'customize_save' == $_POST['action'] ) {
				check_ajax_referer( 'save-customize_' . $GLOBALS['wp_customize']->get_stylesheet(), 'nonce' );
			}

			else {
				return $mods; // No modification for nav menu locations
			}

			$mods['nav_menu_locations'] = $this->update_nav_menu_locations( $mods['nav_menu_locations'] );
		}
		return $mods;
	}

	/**
	 * Fills temporary menu locations based on menus translations
	 *
	 *  
	 *
	 * @param bool|array $menus Associative array of registered navigation menu IDs keyed by their location name.
	 * @return bool|array
	 */
	public function theme_mod_nav_menu_locations( $menus ) {
		// Prefill locations with 0 value in case a location does not exist in $menus
		$locations = get_registered_nav_menus();
		if ( is_array( $locations ) ) {
			$locations = array_fill_keys( array_keys( $locations ), 0 );
			$menus = is_array( $menus ) ? array_merge( $locations, $menus ) : $locations;
		}

		if ( is_array( $menus ) ) {
			foreach ( array_keys( $menus ) as $loc ) {
				foreach ( $this->model->get_languages_list() as $lang ) {
					if ( ! empty( $this->options['nav_menus'][ $this->theme ][ $loc ][ $lang->slug ] ) && term_exists( $this->options['nav_menus'][ $this->theme ][ $loc ][ $lang->slug ], 'nav_menu' ) ) {
						$menus[ $this->combine_location( $loc, $lang ) ] = $this->options['nav_menus'][ $this->theme ][ $loc ][ $lang->slug ];
					}
				}
			}
		}

		return $menus;
	}

	/**
	 * Removes the nav menu term_id from the locations stored in EasyWPTranslator options when a nav menu is deleted
	 *
	 *  
	 *
	 * @param int $term_id nav menu id
	 * @return void
	 */
	public function delete_nav_menu( $term_id ) {
		$nav_menus = $this->options->get( 'nav_menus' );

		if ( empty( $nav_menus ) ) {
			return;
		}

		foreach ( $nav_menus as $theme => $locations ) {
			foreach ( $locations as $loc => $languages ) {
				foreach ( $languages as $lang => $menu_id ) {
					if ( $menu_id === $term_id ) {
						unset( $nav_menus[ $theme ][ $loc ][ $lang ] );
					}
				}
			}
		}

		$this->options->set( 'nav_menus', $nav_menus );
	}

	/**
	 * Adds language filter controls to the nav menu page
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function ewt_nav_menu_language_controls() {
		$screen = get_current_screen();
		if ( empty( $screen ) || 'nav-menus' !== $screen->base ) {
			return;
		}

		// Get all available languages
		$ewt_languages = $this->model->get_languages_list();
		
		if ( count( $ewt_languages ) <= 1 ) {
			return; // No need for language filters if there's only one language
		}

		// Get current language filter from global filter_lang (same as posts/pages)
		$current_lang = ! empty( $this->filter_lang ) ? $this->filter_lang->slug : 'all';
		$base_url = admin_url( 'nav-menus.php' );
		
		// Preserve other query parameters (like action=locations)
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		$current_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

		?>
		<div class='ewt_subsubsub' style='display:none; clear:both;'>
			<ul class='ewt_subsubsub_list'>
				<?php
				// All Languages link
				$all_class = 'all' === $current_lang ? 'current' : '';
				$all_url_args = array( 'lang' => 'all' );
				if ( ! empty( $current_action ) ) {
					$all_url_args['action'] = $current_action;
				}
				$all_url = 'all' !== $current_lang ? add_query_arg( $all_url_args, $base_url ) : '';
				// Get total count directly from database - count nav menu terms
				$total_menus = wp_count_terms( array( 'taxonomy' => 'nav_menu' ) );
				?>
				<li class='ewt_lang_all'>
					<a href="<?php echo esc_url( $all_url ); ?>" class="<?php echo esc_attr( $all_class ); ?>">
						All <span class="count">(<?php echo esc_html( $total_menus ); ?>)</span>
					</a>
				</li>
				
				<?php foreach ( $ewt_languages as $lang ) : ?>
					<?php
					$lang_class = $lang->slug === $current_lang ? 'current' : '';
					$lang_url_args = array(
						'lang' => $lang->slug,
					);
					if ( ! empty( $current_action ) ) {
						$lang_url_args['action'] = $current_action;
					}
					$lang_url = $lang->slug !== $current_lang ? add_query_arg( $lang_url_args, $base_url ) : '';
					$flag_url = isset( $lang->flag_url ) ? $lang->flag_url : '';
					
					$menu_count = 0;
					$nav_menus = $this->options->get( 'nav_menus' );
					$theme = get_option( 'stylesheet' );
					if ( ! empty( $nav_menus[ $theme ] ) ) {
						foreach ( $nav_menus[ $theme ] as $location => $languages ) {
							if ( isset( $languages[ $lang->slug ] ) && $languages[ $lang->slug ] > 0 ) {
								$menu_count++;
							}
						}
					}
					?>
					<li class='ewt_lang_<?php echo esc_attr( $lang->slug ); ?>'>
						<a href="<?php echo esc_url( $lang_url ); ?>" class="<?php echo esc_attr( $lang_class ); ?>">
							<?php if ( ! empty( $flag_url ) ) : ?>
								<img src="<?php echo esc_url( $flag_url ); ?>" alt="<?php echo esc_attr( $lang->name ); ?>" width="16" style="margin-right: 5px;">
							<?php endif; ?>
                            <?php echo esc_html( $lang->name ); ?>
                            <?php if ( ! empty( $lang->is_default ) ) : ?> <span class="icon-default-lang" aria-hidden="true"></span><?php endif; ?><span class="count">(<?php echo esc_html( $menu_count ); ?>)</span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Filters the nav menus list based on current language filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $menus Array of nav menu objects.
	 * @return array Filtered array of nav menus.
	 */
	public function filter_nav_menus_by_language( $menus ) {
		// Only filter on nav-menus page.
		$screen = get_current_screen();
		if ( empty( $screen ) || 'nav-menus' !== $screen->base ) {
			return $menus;
		}

		// Early return if no menus to filter.
		if ( empty( $menus ) || ! is_array( $menus ) ) {
			return $menus;
		}

		// Get current language filter from global filter_lang (same as posts/pages).
		$current_lang = ! empty( $this->filter_lang ) ? $this->filter_lang->slug : 'all';
		
		// If showing all languages, return all menus.
		if ( 'all' === $current_lang ) {
			return $menus;
		}

		// Use the global filter language object.
		$selected_lang = $this->filter_lang;
		if ( ! $selected_lang ) {
			return $menus;
		}

		// Get nav menu assignments.
		$nav_menus = $this->options->get( 'nav_menus' );
		$theme = get_option( 'stylesheet' );

		// Early return if no nav menu assignments.
		if ( empty( $nav_menus[ $theme ] ) ) {
			return $menus;
		}

		// Filter menus based on language assignments.
		$filtered_menus = array();

		foreach ( $menus as $menu ) {
			// Skip invalid menu objects.
			if ( ! is_object( $menu ) || ! isset( $menu->term_id ) ) {
				continue;
			}

			$show_menu = $this->should_show_menu_for_language( $menu, $current_lang, $selected_lang, $nav_menus[ $theme ] );

			if ( $show_menu ) {
				$filtered_menus[] = $menu;
			}
		}

		return $filtered_menus;
	}

	/**
	 * Determines if a menu should be shown for the current language.
	 *
	 * @since 1.0.0
	 *
	 * @param object $menu           Menu object.
	 * @param string $current_lang   Current language slug.
	 * @param object $selected_lang  Selected language object.
	 * @param array  $nav_menus      Nav menu assignments by location.
	 * @return bool True if menu should be shown, false otherwise.
	 */
	private function should_show_menu_for_language( $menu, $current_lang, $selected_lang, $nav_menus ) {
		// Check if this menu is assigned to any location for the selected language.
		foreach ( $nav_menus as $location => $languages ) {
			if ( isset( $languages[ $current_lang ] ) && $languages[ $current_lang ] === $menu->term_id ) {
				return true;
			}
		}

		// For default language, also show menus that don't have specific language assignments.
		if ( $selected_lang->is_default ) {
			return ! $this->is_menu_assigned_to_other_language( $menu, $current_lang, $nav_menus );
		}

		return false;
	}

	/**
	 * Checks if a menu is assigned to any language other than the current one.
	 *
	 * @since 1.0.0
	 *
	 * @param object $menu         Menu object.
	 * @param string $current_lang Current language slug.
	 * @param array  $nav_menus    Nav menu assignments by location.
	 * @return bool True if assigned to other language, false otherwise.
	 */
	private function is_menu_assigned_to_other_language( $menu, $current_lang, $nav_menus ) {
		foreach ( $nav_menus as $location => $languages ) {
			foreach ( $languages as $lang_slug => $menu_id ) {
				if ( $menu_id === $menu->term_id && $lang_slug !== $current_lang ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Preserves the language parameter during redirects on nav-menus page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $location The redirect location.
	 * @param int    $status   The redirect status code.
	 * @return string Modified redirect location.
	 */
	public function preserve_lang_param_on_redirect( $location, $status ) {
		// Only handle redirects on nav-menus admin page.
		$screen = get_current_screen();
		if ( empty( $screen ) || 'nav-menus' !== $screen->base ) {
			return $location;
		}

		// Only handle nav-menus.php redirects.
		if ( false === strpos( $location, 'nav-menus.php' ) ) {
			return $location;
		}

		// Get current language from global filter_lang (same as posts/pages).
		$current_lang = ! empty( $this->filter_lang ) ? $this->filter_lang->slug : '';
		if ( empty( $current_lang ) || 'all' === $current_lang ) {
			return $location;
		}

		// Check if language parameter is already in the redirect URL.
		if ( false !== strpos( $location, 'lang=' ) ) {
			return $location;
		}

		// Add language parameter to redirect URL.
		return add_query_arg( 'lang', $current_lang, $location );
	}

	/**
	 * Maybe update the selected menu when language filter changes.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_update_selected_menu() {
		// Only run on Edit Menus tab, not Manage Locations.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		if ( isset( $_GET['action'] ) && 'locations' === $_GET['action'] ) {
			return;
		}

		// Only run when language filter is set and no specific menu is requested.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		if ( ! isset( $_GET['lang'] ) || isset( $_GET['menu'] ) ) {
			return;
		}

		// Get current language filter.
		$current_lang = ! empty( $this->filter_lang ) ? $this->filter_lang->slug : 'all';
		if ( 'all' === $current_lang ) {
			return; // Don't auto-select when showing all languages.
		}

		// Get filtered menus for the current language.
		$filtered_menus = $this->filter_nav_menus_by_language( wp_get_nav_menus() );
		
		if ( empty( $filtered_menus ) ) {
			return;
		}

		// Get the first menu for this language.
		$first_menu = reset( $filtered_menus );
		if ( ! isset( $first_menu->term_id ) ) {
			return;
		}

		// Redirect to include the menu parameter.
		$redirect_url = add_query_arg( array(
			'menu' => $first_menu->term_id,
			'lang' => $current_lang,
		), admin_url( 'nav-menus.php' ) );
		
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Alternative method to update selected menu on admin_init.
	 * Catches cases where load-nav-menus.php doesn't trigger.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybe_update_selected_menu_on_init() {
		// Only run on nav-menus page.
		$screen = get_current_screen();
		if ( empty( $screen ) || 'nav-menus' !== $screen->base ) {
			return;
		}

		// Only run on Edit Menus tab, not Manage Locations.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		if ( isset( $_GET['action'] ) && 'locations' === $_GET['action'] ) {
			return;
		}

		// Only run when language filter is set and no specific menu is requested.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only parameter for filtering
		if ( ! isset( $_GET['lang'] ) || isset( $_GET['menu'] ) ) {
			return;	
		}

		// Get current language filter.
		$current_lang = ! empty( $this->filter_lang ) ? $this->filter_lang->slug : 'all';
		if ( 'all' === $current_lang ) {
			return; // Don't auto-select when showing all languages.
		}

		// Get filtered menus for the current language.
		$filtered_menus = $this->filter_nav_menus_by_language( wp_get_nav_menus() );
		
		if ( empty( $filtered_menus ) ) {
			return;
		}

		// Get the first menu for this language.
		$first_menu = reset( $filtered_menus );
		if ( ! isset( $first_menu->term_id ) ) {
			return;
		}

		// Check if we're not already on the right menu.
		global $nav_menu_selected_id;
		if ( ! empty( $nav_menu_selected_id ) && $nav_menu_selected_id === $first_menu->term_id ) {
			return;
		}

		// Redirect to include the menu parameter.
		$redirect_url = add_query_arg( array(
			'menu' => $first_menu->term_id,
			'lang' => $current_lang,
		), admin_url( 'nav-menus.php' ) );
		
		wp_safe_redirect( $redirect_url );
		exit;
	}
}
