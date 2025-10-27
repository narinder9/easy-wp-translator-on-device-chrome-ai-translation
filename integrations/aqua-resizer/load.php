<?php
/**
 * Loads the integration with Aqua Resizer.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/aqua-resizer.php';

use EasyWPTranslator\Integrations\aqua_resizer\EWT_Aqua_Resizer;
use EasyWPTranslator\Integrations\EWT_Integrations;

EWT_Integrations::instance()->aq_resizer = new EWT_Aqua_Resizer();
EWT_Integrations::instance()->aq_resizer->init();
