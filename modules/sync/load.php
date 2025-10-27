<?php
/**
 * Loads the module for general synchronization such as metas and taxonomies.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

use EasyWPTranslator\Admin\Controllers\EWT_Admin_Base;



if ( $easywptranslator->model->has_languages() ) {
	if ( $easywptranslator instanceof EWT_Admin_Base ) {
		$easywptranslator->sync = new EWT_Admin_Sync( $easywptranslator );
	} else {
		$easywptranslator->sync = new EWT_Sync( $easywptranslator );
	}

	add_filter(
		'ewt_settings_modules',
		function ( $modules ) {
			$modules[] = 'EWT_Settings_Sync';
			return $modules;
		}
	);
}
