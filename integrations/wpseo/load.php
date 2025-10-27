<?php
/**
 * Loads the integration with Yoast SEO.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/wpseo.php';

use EasyWPTranslator\Integrations\wpseo\EWT_WPSEO;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'WPSEO_VERSION' ) ) {
			add_action( 'ewt_init', array( EWT_Integrations::instance()->wpseo = new EWT_WPSEO(), 'init' ) );
		}
	},
	0
);
