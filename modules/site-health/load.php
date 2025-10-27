<?php
/**
 * Loads the site health.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

use EasyWPTranslator\Admin\Controllers\EWT_Admin;



if ( $easywptranslator instanceof EWT_Admin && $easywptranslator->model->has_languages() ) {
	$easywptranslator->site_health = new EWT_Admin_Site_Health( $easywptranslator );
}
