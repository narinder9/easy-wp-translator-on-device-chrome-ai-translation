<?php
/**
 * Loads the setup wizard.
 *
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Modules\Inline_Translation;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly
}

if ( $easywptranslator->model->has_languages() ) {
    class_exists(EWT_Inline_Translation::class) && new EWT_Inline_Translation();
}
