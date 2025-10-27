<?php
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

use EasyWPTranslator\Includes\Services\Links\EWT_Links_Abstract_Domain;



if ( $easywptranslator->model->has_languages() ) {
	if ( $easywptranslator->links_model instanceof EWT_Links_Abstract_Domain ) {
		$easywptranslator->sitemaps = new EWT_Sitemaps_Domain( $easywptranslator );
	} else {
		$easywptranslator->sitemaps = new EWT_Sitemaps( $easywptranslator );
	}
	$easywptranslator->sitemaps->init();
}
