<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Modules\REST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/api.php';
require_once __DIR__ . '/abstract-controller.php';
require_once __DIR__ . '/v1/languages.php';
require_once __DIR__ . '/v1/settings.php';
require_once __DIR__ . '/v1/bulk-translation.php';

add_action(
	'ewt_init',
	function ( $easywptranslator ) {
		$easywptranslator->rest = new API( $easywptranslator->model );
		add_action( 'rest_api_init', array( $easywptranslator->rest, 'init' ) );
	}
);
