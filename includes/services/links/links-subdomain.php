<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Services\Links;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Other\EWT_Language;


/**
 * Links model for use when the language code is added in the url as a subdomain
 * for example en.mysite.com/something.
 *
 *  
 */
class EWT_Links_Subdomain extends EWT_Links_Abstract_Domain {
	/**
	 * Stores whether the home url includes www. or not.
	 * Either '://' or '://www.'.
	 *
	 * @var string
	 */
	protected $www;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Model $model Instance of EWT_Model.
	 */
	public function __construct( &$model ) {
		parent::__construct( $model );
		$this->www = ( false === strpos( $this->home, '://www.' ) ) ? '://' : '://www.';
	}

	/**
	 * Adds the language code in a url.
	 *
	 *  
	 *   Accepts now a language slug.
	 *
	 * @param string                    $url      The url to modify.
	 * @param EWT_Language|string|false $language Language object or slug.
	 * @return string The modified url.
	 */
	public function add_language_to_link( $url, $language ) {
		if ( $language instanceof EWT_Language ) {
			$language = $language->slug;
		}

		if ( ! empty( $language ) && false === strpos( $url, '://' . $language . '.' ) ) {
			$url = $this->options['default_lang'] === $language && $this->options['hide_default'] ? $url : str_replace( $this->www, '://' . $language . '.', $url );
		}
		return $url;
	}

	/**
	 * Returns the url without the language code.
	 *
	 *  
	 *
	 * @param string $url The url to modify.
	 * @return string The modified url.
	 */
	public function remove_language_from_link( $url ) {
		$languages = $this->model->get_languages_list(
			array(
				'hide_default' => $this->options['hide_default'],
				'fields'       => 'slug',
			)
		);

		if ( ! empty( $languages ) ) {
			$url = preg_replace( '#://(' . implode( '|', $languages ) . ')\.#', $this->www, $url );
		}

		return $url;
	}

	/**
	 * Get the hosts managed on the website.
	 *
	 *  
	 *
	 * @return string[] The list of hosts.
	 */
	public function get_hosts() {
		$hosts = array();
		foreach ( $this->model->get_languages_list() as $lang ) {
			$host = wp_parse_url( $this->home_url( $lang ), PHP_URL_HOST );
			$hosts[ $lang->slug ] = $host ?: '';
		}
		return $hosts;
	}
}
