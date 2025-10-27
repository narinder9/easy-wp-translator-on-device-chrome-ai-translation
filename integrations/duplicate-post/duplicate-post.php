<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\duplicate_post;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the compatibility with Duplicate Post.
 *
 *  
 */
class EWT_Duplicate_Post {
	/**
	 * Setups actions.
	 *
	 *  
	 */
	public function init() {
		add_filter( 'option_duplicate_post_taxonomies_blacklist', array( $this, 'taxonomies_blacklist' ) );
	}

	/**
	 * Avoid duplicating the 'ewt_post_translations' taxonomy.
	 *
	 *  
	 *
	 * @param array|string $taxonomies The list of taxonomies not to duplicate.
	 * @return array
	 */
	public function taxonomies_blacklist( $taxonomies ) {
		if ( empty( $taxonomies ) ) {
			$taxonomies = array(); // As we get an empty string when there is no taxonomy.
		}

		$taxonomies[] = 'ewt_post_translations';
		return $taxonomies;
	}
}
