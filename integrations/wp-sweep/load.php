<?php
/**
 * Loads the integration with WP Sweep.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/wp-sweep.php';

use EasyWPTranslator\Integrations\wp_sweep\EWT_WP_Sweep;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'WP_SWEEP_VERSION' ) ) {
			EWT_Integrations::instance()->wp_sweep = new EWT_WP_Sweep();
			EWT_Integrations::instance()->wp_sweep->init();
		}
	},
	0
);
