<?php
/**
 * @package EasyWPTranslator 
 */

namespace EasyWPTranslator\Modules\Editors\Screens;

use EasyWPTranslator\Includes\Base\EWT_Base;
use WP_Screen;
use EasyWPTranslator\Includes\Other\EWT_Language;
use EasyWPTranslator\Includes\Services\Crud\EWT_CRUD_Posts;

/**
 * Class to manage Post editor scripts.
 */
class Post extends Abstract_Screen {
	/**
	 * @var EWT_CRUD_Posts|null
	 */
	protected $posts;

	/**
	 * Constructor
	 *
	 *
	 * @param EWT_Base $easywptranslator EasyWPTranslator object.
	 */
	public function __construct( EWT_Base &$easywptranslator ) {
		parent::__construct( $easywptranslator );

		$this->posts = &$easywptranslator->posts;
	}


	/**
	 * Tells whether the given screen is the Post edtitor or not.
	 *
	 *
	 * @param  WP_Screen $screen The current screen.
	 * @return bool True if Post editor screen, false otherwise.
	 */
	protected function screen_matches( WP_Screen $screen ): bool {
		return (
			'post' === $screen->base
			&& $this->model->post_types->is_translated( $screen->post_type )
			&& method_exists( $screen, 'is_block_editor' )
			&& $screen->is_block_editor()
		);
	}

	/**
	 * Returns the language to use in the Post editor.
	 *
	 *
	 * @return EWT_Language|null
	 */
	protected function get_language(): ?EWT_Language {
		global $post;


		
		if ( ! empty( $post ) ) {

			
			// Check what languages are available
			$all_languages = $this->model->get_languages_list();

			
			// Check if post type is translated
			$is_translated = $this->model->post_types->is_translated( $post->post_type );
		}

		if ( ! empty( $post ) && ! empty( $this->posts ) && $this->model->post_types->is_translated( $post->post_type ) ) {
			
			// Before setting default language
			$existing_lang = $this->model->post->get_language( $post->ID );

			
			$this->posts->set_default_language( $post->ID );
			
			// After setting default language
			$post_lang = $this->model->post->get_language( $post->ID );

			return ! empty( $post_lang ) ? $post_lang : null;
		}

		return null;
	}

	/**
	 * Returns the screen name for the Post editor to use across all process.
	 *
	 *
	 * @return string
	 */
	protected function get_screen_name(): string {
		return 'post';
	}
}
