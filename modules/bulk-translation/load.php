<?php
/**
 * Loads the setup wizard.
 *
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Modules\Bulk_Translation;

use EasyWPTranslator\Admin\Controllers\EWT_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

if ( $easywptranslator->model->has_languages() ) {
	class_exists( EWT_Bulk_Translation::class ) && new EWT_Bulk_Translation();
}
