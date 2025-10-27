<?php
/**
 * Loads the setup wizard.
 *
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Modules\Wizard;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

require_once __DIR__ . '/wizard.php';

use EasyWPTranslator\Admin\Controllers\EWT_Admin_Base;
use EasyWPTranslator\Modules\Wizard\EWT_Wizard;

if ( $easywptranslator instanceof EWT_Admin_Base ) {
	$easywptranslator->wizard = new EWT_Wizard( $easywptranslator );
}
