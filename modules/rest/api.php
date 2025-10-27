<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Modules\REST;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Other\EWT_Model;



/**
 * Sets all EasyWPTranslator REST controllers up.
 *
 *  
 */
class API {
	/**
	 * REST languages.
	 *
	 * @var V1\Languages|null
	 */
	public $languages;

	/**
	 * REST settings.
	 *
	 * @var V1\Settings|null
	 */
	public $settings;

	/**
	 * REST bulk translate.
	 *
	 * @var V1\Bulk_Translate|null
	 */
	public $bulk_translate;

	/**
	 * @var EWT_Model
	 */
	private $model;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Model $model EasyWPTranslator's model.
	 */
	public function __construct( EWT_Model $model ) {
		$this->model = $model;
	}

	/**
	 * Adds hooks and registers endpoints.
	 *
	 *  
	 *
	 * @return void
	 */
	public function init(): void {
		$this->languages = new V1\Languages( $this->model );
		$this->languages->register_routes();

		$this->settings = new V1\Settings( $this->model );
		$this->settings->register_routes();

		$this->bulk_translate = new V1\Bulk_Translation( $this->model );
		$this->bulk_translate->register_routes();
	}
}
