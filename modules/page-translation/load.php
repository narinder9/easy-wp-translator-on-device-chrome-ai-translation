<?php
/**
 * Loads the setup wizard.
 *
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Modules\Page_Translation;
use EasyWPTranslator\Admin\Controllers\EWT_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

if ( $easywptranslator->model->has_languages() ) {
    class_exists(EWT_Page_Translation::class) && new EWT_Page_Translation($easywptranslator);
}
