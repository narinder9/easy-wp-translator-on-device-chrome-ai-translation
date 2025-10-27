<?php
/**
 * Loads the integration with cache plugins.
 *
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}
use EasyWPTranslator\Includes\Helpers\EWT_Cache;
use EasyWPTranslator\Integrations\cache\EWT_Cache_Compat;
use EasyWPTranslator\Integrations\EWT_Integrations;


add_action(
	'plugins_loaded',
	function () {
		if ( ewt_is_cache_active() ) {
			add_action( 'ewt_init', array( EWT_Integrations::instance()->cache_compat = new EWT_Cache_Compat(), 'init' ) );
		}
	},
	0
);
