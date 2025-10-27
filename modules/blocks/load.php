<?php
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'ewt_init',
	function ( $easywptranslator ) {

		if ( $easywptranslator->model->has_languages() && ewt_use_block_editor_plugin() ) {
			// Only register blocks if 'block' switcher is enabled
			if ( ewt_is_switcher_type_enabled( 'block' ) ) {
				$easywptranslator->switcher_block   = ( new \EasyWPTranslator\Modules\Blocks\EWT_Language_Switcher_Block( $easywptranslator ) )->init();
				$easywptranslator->navigation_block = ( new \EasyWPTranslator\Modules\Blocks\EWT_Navigation_Language_Switcher_Block( $easywptranslator ) )->init();
			}
		}
	}
);