<?php
/**
 * Loads the integration with Duplicate Post.
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

require_once __DIR__ . '/duplicate-post.php';

use EasyWPTranslator\Integrations\duplicate_post\EWT_Duplicate_Post;
use EasyWPTranslator\Integrations\EWT_Integrations;

add_action(
	'plugins_loaded',
	function () {
		if ( defined( 'DUPLICATE_POST_CURRENT_VERSION' ) ) {
			EWT_Integrations::instance()->duplicate_post = new EWT_Duplicate_Post();
			EWT_Integrations::instance()->duplicate_post->init();
		}
	},
	0
);
