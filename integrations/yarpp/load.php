<?php
/**
 * Loads the integration with Yet Another Related Posts Plugin.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/yarpp.php';

use EasyWPTranslator\Integrations\yarpp\EWT_Yarpp;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'YARPP_VERSION' ) ) {
			add_action( 'init', array( EWT_Integrations::instance()->yarpp = new EWT_Yarpp(), 'init' ) );
		}
	},
	0
);
