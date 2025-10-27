<?php
/**
 * Loads the integration with WP Offload Media Lite.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/as3cf.php';

use EasyWPTranslator\Integrations\wp_offload_media\EWT_AS3CF;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action(
	'plugins_loaded',
	function () {
		if ( function_exists( 'as3cf_init' ) && class_exists( 'EWT_AS3CF' ) ) {
			add_action( 'ewt_init', array( EWT_Integrations::instance()->as3cf = new EWT_AS3CF(), 'init' ) );
		}
	},
	0
);
