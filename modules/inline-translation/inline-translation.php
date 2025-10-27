<?php

namespace EasyWPTranslator\Modules\Inline_Translation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EWT_Inline_Translation {


	/**
	 * Singleton instance of EWT_Inline_Translation.
	 *
	 * @var EWT_Inline_Translation
	 */
	private static $instance;

	/**
	 * Get the singleton instance of EWT_Inline_Translation.
	 *
	 * @return EWT_Inline_Translation
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor for EWT_Inline_Translation.
	 */
	public function __construct() {
		add_action( 'enqueue_block_assets', array( $this, 'block_inline_translation_assets' ) );
		add_action('admin_enqueue_scripts', array($this, 'classic_inline_translation_assets'));
		add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'elementor_inline_translation_assets' ) );
	}

	/**
	 * Register block translator assets.
	 */
	public function block_inline_translation_assets() {

		if ( defined( 'EASY_WP_TRANSLATOR_VERSION' ) ) {
			$this->enqueue_inline_translation_assets( 'gutenberg' );
		}
	}

	/**
	 * Enqueue the classic inline translation assets.
	 */
	public function classic_inline_translation_assets() {
		if ( defined( 'EASY_WP_TRANSLATOR_VERSION' ) ) {

			if(!function_exists('get_current_screen')){
				return;
			}

			$current_screen = get_current_screen();

			if ( isset( $current_screen ) && isset( $current_screen->id ) && $current_screen->id === 'edit-page' ) {
				return;
			}

			if ( method_exists( $current_screen, 'is_block_editor' ) && ! $current_screen->is_block_editor() ) {
				$this->enqueue_inline_translation_assets( 'classic' );
			}
		}
	}

	/**
	 * Enqueue the elementor widget translator script.
	 */
	public function elementor_inline_translation_assets() {
		if ( defined( 'EASY_WP_TRANSLATOR_VERSION' ) ) {
			$this->enqueue_inline_translation_assets(
				'elementor',
				array(
					'backbone-marionette',
					'elementor-common',
					'elementor-web-cli',
					'elementor-editor-modules',
				)
			);
		}
	}

	private function enqueue_inline_translation_assets( $type = 'gutenberg', $extra_dependencies = array() ) {

		global $post;

		if(!isset($post) || !isset($post->ID)){
			return;
		}

		if(!is_admin()) {
			return;
		}

		if ( function_exists( 'ewt_current_language' ) ) {
			$current_language      = ewt_current_language();
			$current_language_name = ewt_current_language( 'name' );
			$current_language_code = ewt_current_language( 'code' );
		} else {
			$current_language      = '';
			$current_language_name = '';
			$current_language_code = '';
		}

		$editor_script_asset = include EASY_WP_TRANSLATOR_DIR . '/admin/assets/' . sanitize_file_name( $type ) . '-inline-translate/index.asset.php';

		$core_modal_script_asset = include EASY_WP_TRANSLATOR_DIR . '/admin/assets/inline-translate-modal/index.asset.php';

		if(!is_array($editor_script_asset)) {
			$editor_script_asset = array(
				'dependencies' => array(),
				'version' => EASY_WP_TRANSLATOR_VERSION,
			);
		}

		if(!is_array($core_modal_script_asset)) {
			$core_modal_script_asset = array(
				'dependencies' => array(),
				'version' => EASY_WP_TRANSLATOR_VERSION,
			);
		}

		wp_register_script( 'ewt-inline-translate-modal', plugins_url( '/admin/assets/inline-translate-modal/index.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array_merge( $core_modal_script_asset['dependencies'] ), $core_modal_script_asset['version'], true );

		$extra_dependencies[] = 'ewt-inline-translate-modal';
		
		wp_register_script( 'ewt-' . sanitize_file_name( $type ) . '-inline-translation', plugins_url( '/admin/assets/' . sanitize_file_name( $type ) . '-inline-translate/index.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array_merge( $editor_script_asset['dependencies'], $extra_dependencies ), $editor_script_asset['version'], true );

		wp_enqueue_script( 'ewt-inline-translate-modal' );
		wp_enqueue_script( 'ewt-' . sanitize_file_name( $type ) . '-inline-translation' );

		if ( $current_language && $current_language !== '' ) {
			wp_localize_script(
				'ewt-inline-translate-modal',
				'ewtInlineTranslation',
				array(
					'pageLanguage'     => $current_language
				)
			);
		}
	}
}
