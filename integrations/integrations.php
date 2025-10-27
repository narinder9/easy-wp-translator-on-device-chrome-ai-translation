<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use EasyWPTranslator\Includes\Core\EasyWPTranslator;



/**
 * Container for 3rd party plugins ( and themes ) integrations.
 * This class is available as soon as the plugin is loaded.
 *
 *  
 *   Renamed from EWT_Plugins_Compat to EWT_Integrations.
 */
#[AllowDynamicProperties]
class EWT_Integrations {
	/**
	 * Singleton instance.
	 *
	 * @var EWT_Integrations|null
	 */
	protected static $instance = null;

	// Integration properties
	/**
	 * @var mixed
	 */
	public $aq_resizer;

	/**
	 * @var mixed
	 */
	public $dm;

	/**
	 * @var mixed
	 */
	public $jetpack;

	/**
	 * @var mixed
	 */
	public $featured_content;

	/**
	 * @var mixed
	 */
	public $no_category_base;

	/**
	 * @var mixed
	 */
	public $twenty_seventeen;

	/**
	 * @var mixed
	 */
	public $wp_importer;

	/**
	 * @var mixed
	 */
	public $yarpp;

	/**
	 * @var mixed
	 */
	public $wpseo;

	/**
	 * @var mixed
	 */
	public $wp_sweep;

	/**
	 * @var mixed
	 */
	public $as3cf;

	/**
	 * @var mixed
	 */
	public $duplicate_post;

	/**
	 * @var mixed
	 */
	public $cft;

	/**
	 * @var mixed
	 */
	public $cache_compat;

	/**
	 * Constructor.
	 *
	 *  
	 */
	protected function __construct() {}

	/**
	 * Returns the single instance of the class.
	 *
	 *  
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Requires integrations.
	 *
	 *  
	 *
	 * @return void
	 */
	protected function init(): void {
		// Loads external integrations.
		foreach ( glob( __DIR__ . '/*/load.php', GLOB_NOSORT ) as $load_script ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable
			require_once $load_script; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		}
	}
}

class_alias( 'EasyWPTranslator\Integrations\EWT_Integrations', 'EWT_Integrations' ); // For global access.
