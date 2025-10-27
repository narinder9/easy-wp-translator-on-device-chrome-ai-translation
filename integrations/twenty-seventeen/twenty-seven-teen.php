<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\twenty_seventeen;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Options\EWT_Translate_Option;
use EasyWPTranslator\Frontend\Controllers\EWT_Frontend;



/**
 * Manages the compatibility with Twenty_Seventeen.
 *
 *  
 */
class EWT_Twenty_Seventeen {
	/**
	 * Translates the front page panels and the header video.
	 *
	 *  
	 */
	public function init() {
		if ( 'twentyseventeen' === get_template() && did_action( 'ewt_init' ) ) {
			if ( function_exists( 'twentyseventeen_panel_count' ) && EWT() instanceof EWT_Frontend ) {
				$num_sections = twentyseventeen_panel_count();
				for ( $i = 1; $i < ( 1 + $num_sections ); $i++ ) {
					add_filter( 'theme_mod_panel_' . $i, 'ewt_get_post' );
				}
			}

			$theme_slug = get_option( 'stylesheet' ); // In case we are using a child theme.
			new EWT_Translate_Option( "theme_mods_$theme_slug", array( 'external_header_video' => 1 ), array( 'context' => 'Twenty Seventeen' ) );
		}
	}
}
