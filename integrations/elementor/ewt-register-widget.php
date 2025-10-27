<?php
/**
 * Language Switcher Elementor Widget
 *
 * @package           EasyWPTranslator
 * @wordpress-plugin
 */

namespace EasyWPTranslator\Integrations\elementor;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EWT_Register_Widget
 *
 * Handles the registration of custom Elementor widget.
 */
class EWT_Register_Widget {

	/**
	 * Constructor
	 *
	 * Initialize the class and set up hooks.
	 */
	public function __construct() {
		add_action( 'elementor/widgets/register', array( $this, 'ewt_register_widgets' ) );
	}

	/**
	 * Register custom Elementor widgets
	 *
	 * @return void
	 */
	public function ewt_register_widgets() {
		require_once EASY_WP_TRANSLATOR_DIR . '/integrations/elementor/ewt-widget.php';
		\Elementor\Plugin::instance()->widgets_manager->register( new EWT_Widget() );
	}
}