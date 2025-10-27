<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Install;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}



use EasyWPTranslator\Includes\Options\Options;
use EasyWPTranslator\Includes\Options\Registry as Options_Registry;
use EasyWPTranslator\Install\EWT_Install_Base;


/**
 * EasyWPTranslator activation / de-activation class
 *
 *  
 */
class EWT_Install extends EWT_Install_Base {

	/**
	 * Checks min PHP and WP version, displays a notice if a requirement is not met.
	 *
	 *  
	 *
	 * @return bool
	 */
	public function can_activate() {
		global $wp_version;

		// Check for Polylang conflict first
		if ( defined( 'POLYLANG_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, 'polylang_conflict_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'polylang_conflict_notice' ) );
			return false;
		}

		if ( version_compare( PHP_VERSION, EWT_MIN_PHP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'php_version_notice' ) );
			return false;
		}

		if ( version_compare( $wp_version, EWT_MIN_WP_VERSION, '<' ) ) {
			add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );
			return false;
		}

		return true;
	}

	/**
	 * Displays a notice if PHP min version is not met.
	 *
	 *  
	 *
	 * @return void
	 */
	public function php_version_notice() {

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current PHP version 3: Required PHP version */
				esc_html__( '%1$s has deactivated itself because you are using an old version of PHP. You are using using PHP %2$s. %1$s requires PHP %3$s.', 'easy-wp-translator' ),
				esc_html( EASY_WP_TRANSLATOR ),
				esc_html( PHP_VERSION ),
				esc_html( EWT_MIN_PHP_VERSION )
			)
		);
	}

	/**
	 * Displays a notice if Polylang is detected.
	 *
	 *  
	 *
	 * @return void
	 */
	public function polylang_conflict_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'Easy WP Translator – On-Device Chrome AI Translation', 'easy-wp-translator' ); ?></strong>
			</p>
			<p>
				<?php 
				echo esc_html__( 'EasyWPTranslator cannot run alongside Polylang. Please deactivate Polylang first.', 'easy-wp-translator' );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Displays a notice if WP min version is not met.
	 *
	 *  
	 *
	 * @return void
	 */
	public function wp_version_notice() {
		global $wp_version;

		printf(
			'<div class="error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: Plugin name 2: Current WordPress version 3: Required WordPress version */
				esc_html__( '%1$s has deactivated itself because you are using an old version of WordPress. You are using using WordPress %2$s. %1$s requires at least WordPress %3$s.', 'easy-wp-translator' ),
				esc_html( EASY_WP_TRANSLATOR ),
				esc_html( $wp_version ),
				esc_html( EWT_MIN_WP_VERSION )
			)
		);
	}

	/**
	 * Plugin activation
	 *
	 *  
	 *
	 * @return void
	 */
	protected function _activate() {
		add_action( 'ewt_init_options_for_blog', array( Options_Registry::class, 'register' ) );
		$options = new Options();
		// Check and store first installation date
		$install_date = get_option('ewt_install_date');
		if (empty($install_date)) {
			update_option('ewt_install_date', gmdate('Y-m-d h:i:s'));
			// Set flag for redirection
			update_option('ewt_needs_setup', 'yes');
			
			// Ensure language switcher meta box is visible for new installations
			$user_id = get_current_user_id();
			if ($user_id) {
				$hidden_meta_boxes = get_user_meta($user_id, 'metaboxhidden_nav-menus', true);
				// If meta doesn't exist yet, initialize as empty array
				if (!is_array($hidden_meta_boxes)) {
					$hidden_meta_boxes = array();
				}
				// Remove language switcher from hidden meta boxes to make it visible
				$hidden_meta_boxes = array_diff($hidden_meta_boxes, array('ewt_lang_switch_box'));
				update_user_meta($user_id, 'metaboxhidden_nav-menus', $hidden_meta_boxes);
			}
		}

		
		// Update version to current
		$options['version'] = EASY_WP_TRANSLATOR_VERSION;

		$options->save(); // Force save here to prevent any conflicts with another instance of `Options`.

		if ( false === get_option( 'ewt_language_from_content_available' ) ) {
			update_option(
				'ewt_language_from_content_available',
				0 === $options['force_lang'] ? 'yes' : 'no'
			);
		}

		if ( ! get_option( 'ewt_language_taxonomies' ) ) {
			update_option( 'ewt_language_taxonomies', array() );
		}
		
		// Also clear any cached language data in the cache object
		if ( class_exists( 'EasyWPTranslator\Includes\Helpers\EWT_Cache' ) ) {
			$cache = new \EasyWPTranslator\Includes\Helpers\EWT_Cache();
			$cache->clean();
		}

		// Don't use flush_rewrite_rules at network activation. 
		delete_option( 'rewrite_rules' );
	}

	/**
	 * Plugin deactivation
	 *
	 *  
	 *
	 * @return void
	 */
	protected function _deactivate() {
		delete_option( 'rewrite_rules' ); // Don't use flush_rewrite_rules at network activation. 
		delete_option( 'ewt_elementor_templates_assigned' );
		wp_clear_scheduled_hook('ewt_extra_data_update');
	}
}
