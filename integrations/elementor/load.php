<?php
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}
// Load Elementor compatibility
if ( ewt_is_plugin_active( 'elementor/elementor.php' ) ) {
	require_once __DIR__ . '/elementor.php';
	require_once __DIR__ . '/ewt-template-translation.php';
	require_once __DIR__ . '/ewt-display-conditions.php';
	new EasyWPTranslator\Integrations\elementor\EWT_Elementor();
	new EasyWPTranslator\Integrations\elementor\EWT_Template_Translation();
	new EasyWPTranslator\Integrations\elementor\EWT_Display_Conditions();
	$easywptranslator = get_option( 'easywptranslator' );
	// Only load Elementor widget if 'elementor' switcher is enabled
	if ( $easywptranslator['ewt_language_switcher_options'] && in_array( 'elementor', $easywptranslator['ewt_language_switcher_options'] ) ) {
		require_once __DIR__ . '/ewt-register-widget.php';
		new EasyWPTranslator\Integrations\elementor\EWT_Register_Widget();
	}
}