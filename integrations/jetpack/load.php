<?php
/**
 * Loads the integration with Jetpack.
 * Works for Twenty Fourteen featured content too.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/jetpack.php';
require_once __DIR__ . '/featured-content.php';

use EasyWPTranslator\Integrations\jetpack\EWT_Jetpack;
use EasyWPTranslator\Integrations\jetpack\EWT_Featured_Content;
use EasyWPTranslator\Integrations\EWT_Integrations;

EWT_Integrations::instance()->jetpack = new EWT_Jetpack(); // Must be loaded before the plugin is active.
add_action( 'ewt_init', array( EWT_Integrations::instance()->featured_content = new EWT_Featured_Content(), 'init' ) );
