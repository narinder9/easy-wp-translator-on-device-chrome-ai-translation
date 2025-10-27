<?php
/**
 * Loads the integration with WordPress Importer.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/wordpress-importer.php';

use EasyWPTranslator\Integrations\wp_importer\EWT_WordPress_Importer;
use EasyWPTranslator\Integrations\EWT_Integrations;


EWT_Integrations::instance()->wp_importer = new EWT_WordPress_Importer();
