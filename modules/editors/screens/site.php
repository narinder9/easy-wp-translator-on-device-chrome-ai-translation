<?php
/**
 * @package EasyWPTranslator 
 */

namespace EasyWPTranslator\Modules\Editors\Screens;

use EasyWPTranslator\Includes\Base\EWT_Base;
use EasyWPTranslator\Includes\Other\EWT_Model;
use WP_Screen;
use EasyWPTranslator\Includes\Other\EWT_Language;
use EasyWPTranslator\Modules\Full_Site_Editing\EWT_FSE_Tools;

/**
 * Class to manage Site editor scripts.
 */
class Site extends Abstract_Screen {
	/**
	 * @var EWT_Language|false|null
	 */
	protected $curlang;

	/**
	 * Constructor
	 *
	 *
	 * @param EWT_Base $easywptranslator EasyWPTranslator object.
	 */
	public function __construct( EWT_Base &$easywptranslator ) {
		parent::__construct( $easywptranslator );

		$this->curlang = &$easywptranslator->curlang;
	}

	/**
	 * Adds required hooks.
	 *
	 *
	 * @return static
	 */
	public function init() {
		parent::init();
		add_filter( 'ewt_admin_ajax_params', array( $this, 'ajax_filter' ) );

		return $this;
	}

	/**
	 * Adds the language to the data added to all AJAX requests.
	 *
	 *
	 * @param array $params List of parameters to add to the admin ajax request.
	 * @return array
	 */
	public function ajax_filter( $params ) {
		$screen = get_current_screen();

		if ( empty( $screen ) ) {
			return $params;
		}

		if ( ! $this->screen_matches( $screen ) ) {
			return $params;
		}

		$editor_lang = $this->get_language();

		if ( empty( $editor_lang ) ) {
			return $params;
		}

		$params['lang'] = $editor_lang->slug;
		return $params;
	}


	/**
	 * Tells whether the given screen is the Site edtitor or not.
	 *
	 *
	 * @param  WP_Screen $screen The current screen.
	 * @return bool True if Site editor screen, false otherwise.
	 */
	protected function screen_matches( WP_Screen $screen ): bool {
		return (
			'site-editor' === $screen->base
			&& $this->model->post_types->is_translated( 'wp_template_part' )
			&& method_exists( $screen, 'is_block_editor' )
			&& $screen->is_block_editor()
		);
	}

	/**
	 * Returns the language to use in the Site editor.
	 *
	 *
	 * @return EWT_Language|null
	 */
	protected function get_language(): ?EWT_Language {
		if ( ! empty( $this->curlang ) && EWT_FSE_Tools::is_site_editor() ) {
			return $this->curlang;
		}

		return null;
	}

	/**
	 * Returns the screen name for the Site editor to use across all process.
	 *
	 *
	 * @return string
	 */
	protected function get_screen_name(): string {
		return 'site';
	}
}
