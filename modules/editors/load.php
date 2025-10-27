<?php
/**
 * @package EasyWPTranslator
 */

use EasyWPTranslator\Modules\Editors\Screens\Post;
use EasyWPTranslator\Modules\Editors\Screens\Site;
use EasyWPTranslator\Modules\Editors\Screens\Widget;
use EasyWPTranslator\Modules\Editors\Filter_Preload_Paths;
use EasyWPTranslator\Admin\Controllers\EWT_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

add_action(
	'ewt_init',
	function ( $easywptranslator ) {
		if (
			$easywptranslator->model->languages->has()
			&& $easywptranslator instanceof EWT_Admin
			&& ewt_use_block_editor_plugin()
		) {
			$easywptranslator->site_editor   = ( new Site( $easywptranslator ) )->init();
			$easywptranslator->post_editor   = ( new Post( $easywptranslator ) )->init();
			$easywptranslator->widget_editor = ( new Widget( $easywptranslator ) )->init();
			$easywptranslator->filter_path   = ( new Filter_Preload_Paths( $easywptranslator ) )->init();
		}
	}
);
