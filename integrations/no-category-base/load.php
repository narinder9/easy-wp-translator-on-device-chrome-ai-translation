<?php
/**
 * Loads the integration with No Category Base (WPML).
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/no-category-base.php';

use EasyWPTranslator\Integrations\no_category_base\EWT_No_Category_Base;
use EasyWPTranslator\Integrations\EWT_Integrations;

EWT_Integrations::instance()->no_category_base = new EWT_No_Category_Base();
EWT_Integrations::instance()->no_category_base->init();
