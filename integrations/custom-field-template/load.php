<?php
/**
 * Loads the integration with Custom Field Template.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/cft.php';

use EasyWPTranslator\Integrations\custom_field_template\EWT_Cft;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'CFT_VERSION' ) ) {
			EWT_Integrations::instance()->cft = new EWT_Cft();
			EWT_Integrations::instance()->cft->init();
		}
	},
	0
);
