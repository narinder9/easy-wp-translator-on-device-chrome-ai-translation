<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\jetpack;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Frontend\Controllers\EWT_Frontend;
use Featured_Content;



/**
 * Manages the compatibility with the Jetpack Twenty Fourteenn Featured content
 *
 *  
 */
class EWT_Featured_Content {
	/**
	 * Constructor
	 *
	 *  
	 */
	public function init() {
		add_filter( 'transient_featured_content_ids', array( $this, 'featured_content_ids' ) );
		add_filter( 'option_featured-content', array( $this, 'option_featured_content' ) );
	}

	/**
	 * Tell whether the theme supports the featured content
	 *
	 *  
	 *
	 * @return bool
	 */
	protected function is_active() {
		return 'twentyfourteen' === get_template() || ( defined( 'JETPACK__VERSION' ) && get_theme_support( 'featured-content' ) );
	}

	/**
	 * Get the theme featured posts filter name
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_featured_posts_filter() {
		$theme_support = get_theme_support( 'featured-content' );

		if ( isset( $theme_support[0]['featured_content_filter'] ) ) {
			$theme_support[0]['filter'] = $theme_support[0]['featured_content_filter'];
			unset( $theme_support[0]['featured_content_filter'] );
		}

		return $theme_support[0]['filter'];
	}

	/**
	 * Rewrites the function Featured_Content::get_featured_post_ids()
	 *
	 *  
	 *
	 * @param array $featured_ids Featured posts ids
	 * @return array modified featured posts ids ( include all languages )
	 */
	public function featured_content_ids( $featured_ids ) {
		if ( ! $this->is_active() || false !== $featured_ids ) {
			return $featured_ids;
		}

		$settings = Featured_Content::get_setting();

		if ( ! $term = get_term_by( 'name', $settings['tag-name'], 'post_tag' ) ) {
			return $featured_ids;
		}

		// Get featured tag translations
		$tags = EWT()->model->term->get_translations( $term->term_id );
		$ids = array();

		// Query for featured posts in all languages
		// One query per language to get the correct number of posts per language
		foreach ( $tags as $tag ) {
			$args = array(
				'lang'        => 0, // Avoid language filters.
				'fields'      => 'ids',
				'numberposts' => Featured_Content::$max_posts,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- get_posts() with tax_query is intentionally used here to fetch featured posts for each tag translation across all languages. This is necessary to ensure the correct number of featured posts per language for multilingual themes/plugins, and there is no performant alternative in core WP for this use-case.
				'tax_query'   => array(
					array(
						'taxonomy' => 'post_tag',
						'terms'    => (int) $tag,
					),
				),
			);

			// Available in Jetpack, but not in Twenty Fourteen.
			if ( isset( Featured_Content::$post_types ) ) {
				$args['post_type'] = Featured_Content::$post_types;
			}

			$_ids = get_posts( $args );
			$ids  = array_merge( $ids, $_ids );
		}

		$ids = array_map( 'absint', $ids );
		set_transient( 'ewt_featured_content_ids', $ids );

		return $ids;
	}

	/**
	 * Translates the featured tag id in featured content settings
	 * Mainly to allow hiding it when requested in featured content options
	 * Acts only on frontend
	 *
	 *  
	 *
	 * @param array $settings featured content settings
	 * @return array modified $settings
	 */
	public function option_featured_content( $settings ) {
		if ( $this->is_active() && EWT() instanceof EWT_Frontend && $settings['tag-id'] && $tr = ewt_get_term( $settings['tag-id'] ) ) {
			$settings['tag-id'] = $tr;
		}

		return $settings;
	}
}
