<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Other\EWT_Translation_Dashboard;

/**
 * A class to manage admin notices
 * displayed only to admin, based on 'manage_options' capability
 * and only on dashboard, plugins and EasyWPTranslator admin pages
 *
 *  
 *   Dismissed notices are stored in an option instead of a user meta
 */
class EWT_Admin_Notices {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	protected $options;

	/**
	 * Stores custom notices.
	 *
	 * @var string[]
	 */
	private static $notices = array();

	/**
	 * Constructor
	 * Setup actions
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( $easywptranslator ) {
		$this->options = &$easywptranslator->options;

		add_action( 'admin_init', array( $this, 'hide_notice' ) );
		add_action( 'admin_notices', array( $this, 'display_notices' ) );
		
		// Add inline CSS and JS for notice positioning on ?page=ewt
		add_action( 'admin_enqueue_scripts', array( $this, 'add_notice_positioning_inline' ) );
	}

	/**
	 * Add a custom notice
	 *
	 *  
	 *
	 * @param string $name Notice name
	 * @param string $html Content of the notice
	 * @return void
	 */
	public static function add_notice( $name, $html ) {
		self::$notices[ $name ] = $html;
	}

	/**
	 * Get custom notices.
	 *
	 *  
	 *
	 * @return string[]
	 */
	public static function get_notices() {
		return self::$notices;
	}

	/**
	 * Has a notice been dismissed?
	 *
	 *  
	 *
	 * @param string $notice Notice name
	 * @return bool
	 */
	public static function is_dismissed( $notice ) {
		$dismissed = get_option( 'ewt_dismissed_notices', array() );

		// Handle legacy user meta
		$dismissed_meta = get_user_meta( get_current_user_id(), 'ewt_dismissed_notices', true );
		if ( is_array( $dismissed_meta ) ) {
			if ( array_diff( $dismissed_meta, $dismissed ) ) {
				$dismissed = array_merge( $dismissed, $dismissed_meta );
				update_option( 'ewt_dismissed_notices', $dismissed );
			}
			if ( ! is_multisite() ) {
				// Don't delete on multisite to avoid the notices to appear in other sites.
				delete_user_meta( get_current_user_id(), 'ewt_dismissed_notices' );
			}
		}

		return in_array( $notice, $dismissed );
	}

	/**
	 * Should we display notices on this screen?
	 *
	 *  
	 *
	 * @param string $notice          The notice name.
	 * @param array  $allowed_screens The screens allowed to display the notice.
	 *                                If empty, default screens are used, i.e. dashboard, plugins, languages, strings and settings.
	 *
	 * @return bool
	 */
	protected function can_display_notice( string $notice, array $allowed_screens = array() ) {
		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return false;
		}
		
		if ( empty( $allowed_screens ) ) {
			$screen_id       = sanitize_title( __( 'Languages', 'easy-wp-translator' ) );
			$allowed_screens = array(
				'dashboard',
				'plugins',
				$screen_id . '_page_ewt_strings',
				$screen_id . '_page_ewt_settings',
			);
		}

		/**
		 * Filters admin notices which can be displayed.
		 *
		 *  
		 *
		 * @param bool   $display Whether the notice should be displayed or not.
		 * @param string $notice  The notice name.
		 */
		return apply_filters( 'ewt_can_display_notice', in_array( $screen->id, $allowed_screens, true ), $notice );
	}

	/**
	 * Stores a dismissed notice in the database.
	 *
	 *  
	 *
	 * @param string $notice Notice name.
	 * @return void
	 */
	public static function dismiss( $notice ) {
		$dismissed = get_option( 'ewt_dismissed_notices', array() );

		if ( ! in_array( $notice, $dismissed ) ) {
			$dismissed[] = $notice;
			update_option( 'ewt_dismissed_notices', array_unique( $dismissed ) );
		}
	}

	/**
	 * Handle a click on the dismiss button
	 *
	 *  
	 *
	 * @return void
	 */
	public function hide_notice() {
		if ( isset( $_GET['ewt-hide-notice'], $_GET['_ewt_notice_nonce'] ) ) {
			$notice = sanitize_key( $_GET['ewt-hide-notice'] );
			check_admin_referer( $notice, '_ewt_notice_nonce' );
			self::dismiss( $notice );
			wp_safe_redirect( remove_query_arg( array( 'ewt-hide-notice', '_ewt_notice_nonce' ), wp_get_referer() ) );
			exit;
		}
	}

	/**
	 * Displays notices
	 *
	 *  
	 *
	 * @return void
	 */
	public function display_notices() {
		// Check if we're on the specific ?page=ewt page and should suppress notices
		if ( current_user_can( 'manage_options' ) ) {

			// Custom notices
			foreach ( static::get_notices() as $notice => $html ) {
				if ( $this->can_display_notice( $notice ) && ! static::is_dismissed( $notice ) ) {
					?>
					<div class="ewt-notice notice notice-info">
						<?php
						$this->dismiss_button( $notice );
						echo wp_kses_post( $html );
						?>
					</div>
					<?php
				}
			}
		}
		if ( $this->is_ewt_page() ) {
			// Don't display notices here, they will be captured and displayed later
			return;
		}
	}

	/**
	 * Displays a dismiss button
	 *
	 *  
	 *
	 * @param string $name Notice name
	 * @return void
	 */
	public function dismiss_button( $name ) {
		printf(
			'<a class="notice-dismiss" href="%s"><span class="screen-reader-text">%s</span></a>',
			esc_url( wp_nonce_url( add_query_arg( 'ewt-hide-notice', $name ), $name, '_ewt_notice_nonce' ) ),
			/* translators: accessibility text */
			esc_html__( 'Dismiss this notice.', 'easy-wp-translator' )
		);
	}

	/**
	 * Check if we're on the specific ?page=ewt page
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function is_ewt_page() {
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return false;
		}
		
		// Check if we're specifically on the ?page=ewt page
		return $screen->id === 'toplevel_page_ewt' || $screen->id === 'languages_page_ewt_settings';
	}
	/**
	 * Add inline CSS and JavaScript for notice positioning on ?page=ewt
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_notice_positioning_inline() {
		if ( ! $this->is_ewt_page() ) {
			return;
		}

		// Add inline CSS
		$css = "
		/* Notice positioning for ?page=ewt */
		body.toplevel_page_ewt .notice,
		body.toplevel_page_ewt .error,
		body.toplevel_page_ewt .updated,
		body.toplevel_page_ewt .notice-error,
		body.toplevel_page_ewt .notice-warning,
		body.toplevel_page_ewt .notice-info,
		body.toplevel_page_ewt .notice-success {
			display: none !important;
			margin-left: 2rem;
		}

		/* Show notices after they are moved */
		body.toplevel_page_ewt .ewt-moved-notice {
			display: block !important;
			margin-left: 2rem;
			margin-right: 2rem;
			width: auto;
		}
		";
		wp_add_inline_style( 'easywptranslator_admin', $css );

		// Add inline JavaScript
		$js = "
		jQuery(document).ready(function($) {
			// Wait for the page to load
			setTimeout(function() {
				// Find all notices including error, updated, and other notice classes
				var notices = $('.notice, .error, .updated, .notice-error, .notice-warning, .notice-info, .notice-success');
				if (notices.length > 0) {
					// Find the header container
					var headerContainer = $('#ewt-settings-header');
					if (headerContainer.length > 0) {
						// Move notices after the header
						notices.detach().insertAfter(headerContainer);
						// Add class to make notices visible
						notices.addClass('ewt-moved-notice');
					}
				}
			}, 100);
		});
		";
		wp_add_inline_script( 'ewt_admin', $js );
	}
}
