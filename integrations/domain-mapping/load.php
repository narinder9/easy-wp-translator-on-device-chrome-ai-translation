<?php
/**
 * Loads the integration with WordPress MU Domain Mapping.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/domain-mapping.php';

use EasyWPTranslator\Integrations\domain_mapping\EWT_Domain_Mapping;
use EasyWPTranslator\Integrations\EWT_Integrations;

EWT_Integrations::instance()->dm = new EWT_Domain_Mapping();
