<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Modules\Editors;

use WP_Post;
use EasyWPTranslator\Includes\Base\EWT_Base;
use EasyWPTranslator\Includes\Other\EWT_Model;
use EasyWPTranslator\Includes\Other\EWT_Language;
use EasyWPTranslator\Admin\Controllers\EWT_Admin_Block_Editor;
use WP_Block_Editor_Context;

/**
 * Class to filter REST preload paths.
 *
 */
class Filter_Preload_Paths {
	/**
	 * @var EWT_Model
	 */
	protected $model;

	/**
	 * @var EWT_Language|false|null
	 */
	protected $curlang;

	/**
	 * @var EWT_Admin_Block_Editor|null
	 */
	protected $block_editor;

	/**
	 * Constructor
	 *
	 *
	 * @param EWT_Base $easywptranslator EasyWPTranslator object.
	 */
	public function __construct( EWT_Base &$easywptranslator ) {
		$this->model        = &$easywptranslator->model;
		$this->curlang      = &$easywptranslator->curlang;
		$this->block_editor = &$easywptranslator->block_editor;
	}

	/**
	 * Adds required hooks.
	 *
	 *
	 * @return self
	 */
	public function init(): self {
		add_filter( 'block_editor_rest_api_preload_paths', array( $this, 'filter_preload_paths' ), 50, 2 );
		add_filter( 'ewt_filtered_rest_routes', array( $this, 'filter_navigation_fallback_route' ) );

		return $this;
	}

	/**
	 * Filters preload paths based on the context (block editor for posts, site editor or widget editor for instance).
	 *
	 *
	 * @param (string|string[])[]     $preload_paths Preload paths.
	 * @param WP_Block_Editor_Context $context       Editor context.
	 * @return array Filtered preload paths.
	 */
	public function filter_preload_paths( $preload_paths, $context ) {
		if ( ! $context instanceof WP_Block_Editor_Context || empty( $this->block_editor ) ) {
			return $preload_paths;
		}

		if ( $context->post instanceof WP_Post && ! $this->model->is_translated_post_type( $context->post->post_type ) ) {
			return $preload_paths;
		}

		$preload_paths = (array) $preload_paths;

		// Do nothing if in post editor since `EWT_Admin_Block_Editor` has already filtered.
		if ( 'core/edit-post' !== $context->name ) {
			$lang = ! empty( $this->curlang ) ? $this->curlang->slug : null;

			if ( empty( $lang ) || 'core/edit-widgets' === $context->name ) {
				$lang = $this->model->options['default_lang'];
			}

			$preload_paths = $this->block_editor->filter_rest_routes->add_query_parameters(
				$preload_paths,
				array(
					'lang' => $lang,
				)
			);

			if ( 'core/edit-site' === $context->name ) {
				// User data required for the site editor (WP already adds it to the post block editor).
				$preload_paths[] = '/wp/v2/users/me';
			}
		}

		$preload_paths[] = '/ewt/v1/languages';

		return $preload_paths;
	}

	/**
	 * Adds navigation fallback REST route to the filterable ones.
	 *
	 *
	 * @param string[] $routes Filterable REST routes.
	 * @return string[] Filtered filterable REST routes.
	 */
	public function filter_navigation_fallback_route( $routes ) {
		$routes['navigation-fallback'] = 'wp-block-editor/v1/navigation-fallback';

		return $routes;
	}
}
