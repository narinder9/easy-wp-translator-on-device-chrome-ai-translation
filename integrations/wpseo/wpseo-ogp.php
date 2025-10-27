<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\wpseo;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use Yoast\WP\SEO\Presenters\Abstract_Indexable_Presenter;


/**
 * Creates an Opengraph alternate locale meta tag to be consumed by Yoast SEO
 * Requires Yoast SEO 14.0 or newer.
 *
 *  
 */
final class EWT_WPSEO_OGP extends Abstract_Indexable_Presenter {
	/**
	 * Facebook locale
	 *
	 * @var string $locale
	 */
	private $locale;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param string $locale Facebook locale.
	 */
	public function __construct( $locale ) {
		$this->locale = $locale;
	}

	/**
	 * Returns the meta Opengraph alternate locale meta tag
	 *
	 *  
	 *
	 * @return string
	 */
	public function present() {
		return sprintf( '<meta property="og:locale:alternate" content="%s" />', esc_attr( $this->get() ) );
	}

	/**
	 * Returns the alternate locale
	 *
	 *  
	 *
	 * @return string
	 */
	public function get() {
		return $this->locale;
	}
}
