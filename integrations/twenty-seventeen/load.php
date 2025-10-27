<?php
/**
 * Loads the integration with Twenty Seventeen.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/twenty-seven-teen.php';

use EasyWPTranslator\Integrations\twenty_seventeen\EWT_Twenty_Seventeen;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action( 'init', array( EWT_Integrations::instance()->twenty_seventeen = new EWT_Twenty_Seventeen(), 'init' ) );
