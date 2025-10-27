<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Frontend\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class EWT_Accept_Languages_Collection.
 *
 * Represents a collection of values parsed from an Accept-Language HTTP header.
 *
 *  
 */
class EWT_Accept_Languages_Collection {
	/**
	 * @var EWT_Accept_Language[]
	 */
	protected $accept_languages = array();

	/**
	 * Parse Accept-Language HTTP header according to IETF BCP 47.
	 *
	 *  
	 *
	 * @param string $http_header Value of the Accept-Language HTTP Header. Formatted as stated BCP 47 for language tags {@see https://tools.ietf.org/html/bcp47}.
	 * @return EWT_Accept_Languages_Collection
	 */
	public static function from_accept_language_header( $http_header ) {
		$lang_parse = array();
		// Break up string into pieces ( languages and q factors ).
		$language_pattern = implode( '', EWT_Accept_Language::SUBTAG_PATTERNS );
		$quality_pattern = '\s*;\s*q\s*=\s*((?>1|0)(?>\.[0-9]+)?)';
		$full_pattern = "/{$language_pattern}(?:{$quality_pattern})?/i";

		preg_match_all(
			$full_pattern,
			$http_header,
			$lang_parse,
			PREG_SET_ORDER
		);

		return new EWT_Accept_Languages_Collection(
			array_map(
				array( EWT_Accept_Language::class, 'from_array' ),
				$lang_parse
			)
		);
	}

	/**
	 * EWT_Accept_Languages_Collection constructor.
	 *
	 *  
	 *
	 * @param EWT_Accept_Language[] $accept_languages Objects representing Accept-Language HTTP headers.
	 */
	public function __construct( $accept_languages = array() ) {
		$this->accept_languages = $accept_languages;
	}

	/**
	 * Bubble sort (need a stable sort for Android, so can't use a PHP sort function).
	 *
	 *  
	 *
	 * @return void
	 */
	public function bubble_sort() {
		$k = $this->accept_languages;
		$v = array_map(
			function ( $accept_lang ) {
				return $accept_lang->get_quality();
			},
			$this->accept_languages
		);

		if ( $n = count( $k ) ) {

			if ( $n > 1 ) {
				for ( $i = 2; $i <= $n; $i++ ) {
					for ( $j = 0; $j <= $n - 2; $j++ ) {
						if ( $v[ $j ] < $v[ $j + 1 ] ) {
							// Swap values.
							$temp = $v[ $j ];
							$v[ $j ] = $v[ $j + 1 ];
							$v[ $j + 1 ] = $temp;
							// Swap keys.
							$temp = $k[ $j ];
							$k[ $j ] = $k[ $j + 1 ];
							$k[ $j + 1 ] = $temp;
						}
					}
				}
			}
			$this->accept_languages = array_filter(
				$k,
				function ( $accept_lang ) {
					return $accept_lang->get_quality() > 0;
				}
			);
		}
	}

	/**
	 * Looks through sorted list and use first one that matches our language list.
	 *
	 *  
	 *
	 * @param EWT_Language[] $languages The language list.
	 * @return string|false A language slug if there's a match, false otherwise.
	 */
	public function find_best_match( $languages = array() ) {
		foreach ( $this->accept_languages as $accept_lang ) {
			// First loop to match the exact locale.
			foreach ( $languages as $language ) {
				if ( 0 === strcasecmp( $accept_lang, $language->get_locale( 'display' ) ) ) {
					return $language->slug;
				}
			}

			// In order of priority.
			$subsets = array();
			if ( ! empty( $accept_lang->get_subtag( 'region' ) ) ) {
				$subsets[] = $accept_lang->get_subtag( 'language' ) . '-' . $accept_lang->get_subtag( 'region' );
				$subsets[] = $accept_lang->get_subtag( 'region' );
			}
			if ( ! empty( $accept_lang->get_subtag( 'variant' ) ) ) {
				$subsets[] = $accept_lang->get_subtag( 'language' ) . '-' . $accept_lang->get_subtag( 'variant' );
			}
			$subsets[] = $accept_lang->get_subtag( 'language' );

			// More loops to match the subsets.
			foreach ( $languages as $language ) {
				foreach ( $subsets as $subset ) {

					if ( 0 === stripos( $subset, $language->slug ) || 0 === stripos( $language->get_locale( 'display' ), $subset ) ) {
						return $language->slug;
					}
				}
			}
		}
		return false;
	}
}
