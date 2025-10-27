<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Includes\Other;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EWT_Language factory.
 *
 *  
 *
 * @phpstan-import-type LanguageData from EWT_Language
 */
class EWT_Language_Factory {
	/**
	 * Predefined languages.
	 *
	 * @var array[]
	 *
	 * @phpstan-var array<string, array<string, string>>
	 */
	private static $languages;

	/**
	 * EasyWPTranslator's options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param array $options Array of Poylang's options passed by reference.
	 * @return void
	 */
	public function __construct( &$options ) {
		$this->options = &$options;
	}

	/**
	 * Returns a language object matching the given data, looking up in cached transient.
	 *
	 *  
	 *
	 * @param array $language_data Language object properties stored as an array.
	 *
	 * @return EWT_Language A language object if given data pass sanitization.
	 *
	 * @phpstan-param LanguageData $language_data
	 */
	public function get( $language_data ) {
		return new EWT_Language( $this->sanitize_data( $language_data ) );
	}

	/**
	 * Returns a language object based on terms.
	 *
	 *  
	 *
	 * @param WP_Term[] $terms List of language terms, with the language taxonomy names as array keys.
	 *                         `ewt_language` is a mandatory key for the object to be created.
	 *                         `ewt_term_language` should be too in a fully operational environment.
	 * @return EWT_Language|null Language object on success, `null` on failure.
	 *
	 * @phpstan-param array{ewt_language?:WP_Term}&array<string, WP_Term> $terms
	 */
	public function get_from_terms( array $terms ) {
		if ( ! isset( $terms['ewt_language'] ) ) {
			return null;
		}

		$languages = $this->get_languages();
		$data      = array(
			'name'       => $terms['ewt_language']->name,
			'slug'       => $terms['ewt_language']->slug,
			'term_group' => $terms['ewt_language']->term_group,
			'term_props' => array(),
			'is_default' => $this->options['default_lang'] === $terms['ewt_language']->slug,
		);

		foreach ( $terms as $term ) {
			$data['term_props'][ $term->taxonomy ] = array(
				'term_id'          => $term->term_id,
				'term_taxonomy_id' => $term->term_taxonomy_id,
				'count'            => $term->count,
			);
		}

		// The description fields can contain any property.
		$description = maybe_unserialize( $terms['ewt_language']->description );

		if ( is_array( $description ) ) {
			$description = array_intersect_key(
				$description,
				array( 'locale' => null, 'rtl' => null, 'flag_code' => null, 'active' => null, 'fallbacks' => null )
			);

			foreach ( $description as $prop => $value ) {
				if ( 'rtl' === $prop ) {
					$data['is_rtl'] = $value;
				} else {
					$data[ $prop ] = $value;
				}
			}
		}

		if ( ! empty( $data['locale'] ) ) {
			if ( isset( $languages[ $data['locale'] ]['w3c'] ) ) {
				$data['w3c'] = $languages[ $data['locale'] ]['w3c'];
			} else {
				$data['w3c'] = str_replace( '_', '-', $data['locale'] );
			}

			if ( isset( $languages[ $data['locale'] ]['facebook'] ) ) {
				$data['facebook'] = $languages[ $data['locale'] ]['facebook'];
			}
		}

		$flag_props = $this->get_flag(
			isset( $data['flag_code'] ) ? $data['flag_code'] : 'en',
			isset( $data['name'] ) ? $data['name'] : 'English',
			isset( $data['slug'] ) ? $data['slug'] : 'en',
			isset( $data['locale'] ) ? $data['locale'] : 'en_US'
		);
		$data       = array_merge( $data, $flag_props );

		$additional_data = array();
		/**
		 * Filters additional data to add to the language before it is created.
		 *
		 *  
		 *
		 * @param array $additional_data.
		 * @param array $data Language data.
		 */
		$additional_data = apply_filters( 'ewt_additional_language_data', $additional_data, $data );

		$allowed_additional_data = array(
			'home_url'       => '',
			'search_url'     => '',
			'page_on_front'  => 0,
			'page_for_posts' => 0,
		);

		$data = array_merge( $data, array_intersect_key( $additional_data, $allowed_additional_data ) );

		return new EWT_Language( $this->sanitize_data( $data ) );
	}

	/**
	 * Sanitizes data, to be ready to be used in the constructor.
	 * This doesn't verify that the language terms exist.
	 *
	 *  
	 *
	 * @param array $data Data to process.
	 * @return array Sanitized Data.
	 *
	 * @phpstan-return LanguageData
	 */
	private function sanitize_data( array $data ) {
		foreach ( $data['term_props'] as $tax => $props ) {
			$data['term_props'][ $tax ] = array_map( 'absint', $props );
		}

		$data['is_rtl'] = ! empty( $data['is_rtl'] ) ? 1 : 0;

		$positive_fields = array( 'term_group', 'page_on_front', 'page_for_posts' );

		foreach ( $positive_fields as $field ) {
			$data[ $field ] = ! empty( $data[ $field ] ) ? absint( $data[ $field ] ) : 0;
		}

		$data['active'] = isset( $data['active'] ) ? (bool) $data['active'] : true;

		if ( array_key_exists( 'fallbacks', $data ) && ! is_array( $data['fallbacks'] ) ) {
			unset( $data['fallbacks'] );
		}

		/**
		 * @var LanguageData
		 */
		return $data;
	}

	/**
	 * Returns predefined languages data.
	 *
	 *  
	 *
	 * @return array[]
	 *
	 * @phpstan-return array<string, array<string, string>>
	 */
	private function get_languages() {
		if ( empty( self::$languages ) ) {
			self::$languages = include EASY_WP_TRANSLATOR_DIR . '/admin/settings/controllers/languages.php';
		}

		return self::$languages;
	}


	/**
	 * Creates flag_url and flag language properties. Also takes care of custom flag.
	 *
	 *  
	 *
	 * @param string $flag_code Flag code.
	 * @param string $name      Language name.
	 * @param string $slug      Language slug.
	 * @param string $locale    Language locale.
	 * @return array {
	 *     Array of the flag properties.
	 *     @type string  $flag_url        URL of the flag.
	 *     @type string  $flag            HTML markup of the flag.
	 *     @type string  $custom_flag_url Optional. URL of the custom flag if it exists.
	 *     @type string  $custom_flag     Optional. HTML markup of the custom flag if it exists.
	 * }
	 *
	 * @phpstan-return array{
	 *     flag_url: string,
	 *     flag: string,
	 *     custom_flag_url?: non-empty-string,
	 *     custom_flag?: non-empty-string
	 * }
	 */
	private function get_flag( $flag_code, $name, $slug, $locale ) {
		$flags = array(
			'flag' => EWT_Language::get_flag_information( $flag_code ),
		);

		// Custom flags?
		$directories = array(
			EWT_LOCAL_DIR,
			get_stylesheet_directory() . '/easywptranslator',
			get_template_directory() . '/easywptranslator',
		);

		foreach ( $directories as $dir ) {
			if ( is_readable( $file = "{$dir}/{$locale}.png" ) || is_readable( $file = "{$dir}/{$locale}.jpg" ) || is_readable( $file = "{$dir}/{$locale}.jpeg" ) || is_readable( $file = "{$dir}/{$locale}.svg" ) ) {
				$flags['custom_flag'] = array(
					'url' => content_url( '/' . str_replace( WP_CONTENT_DIR, '', $file ) ),
				);
				break;
			}
		}

		/**
		 * Filters the custom flag information.
		 *
		 *  
		 *
		 * @param array|null $flag {
		 *   Information about the custom flag.
		 *
		 *   @type string $url    Flag url.
		 *   @type string $src    Optional, src attribute value if different of the url, for example if base64 encoded.
		 *   @type int    $width  Optional, flag width in pixels.
		 *   @type int    $height Optional, flag height in pixels.
		 * }
		 * @param string     $code Flag code.
		 */
		$flags['custom_flag'] = apply_filters( 'ewt_custom_flag', empty( $flags['custom_flag'] ) ? null : $flags['custom_flag'], $flag_code );

		if ( ! empty( $flags['custom_flag']['url'] ) ) {
			if ( empty( $flags['custom_flag']['src'] ) ) {
				$flags['custom_flag']['src'] = esc_url( set_url_scheme( $flags['custom_flag']['url'], 'relative' ) );
			}

			$flags['custom_flag']['url'] = sanitize_url( $flags['custom_flag']['url'] );
		} else {
			unset( $flags['custom_flag'] );
		}

		/**
		 * Filters the flag title attribute.
		 * Defaults to the language name.
		 *
		 *  
		 *
		 * @param string $title  The flag title attribute.
		 * @param string $slug   The language code.
		 * @param string $locale The language locale.
		 */
		$title  = apply_filters( 'ewt_flag_title', $name, $slug, $locale );
		$return = array();

		/**
		 * @var array{
		 *     flag: array{
		 *         url: string,
		 *         src: string,
		 *         width?: positive-int,
		 *         height?: positive-int
		 *     },
		 *     custom_flag?: array{
		 *         url: non-empty-string,
		 *         src: non-empty-string,
		 *         width?: positive-int,
		 *         height?: positive-int
		 *     }
		 * } $flags
		 */
		foreach ( $flags as $key => $flag ) {
			$return[ "{$key}_url" ] = $flag['url'];

			/**
			 * Filters the html markup of a flag.
			 *
			 *  
			 *
			 * @param string $flag Html markup of the flag or empty string.
			 * @param string $slug Language code.
			 */
			$return[ $key ] = apply_filters(
				'ewt_get_flag',
				EWT_Language::get_flag_html( $flag, $title, $name ),
				$slug
			);
		}

		/**
		 * @var array{
		 *     flag_url: string,
		 *     flag: string,
		 *     custom_flag_url?: non-empty-string,
		 *     custom_flag?: non-empty-string
		 * } $return
		 */
		return $return;
	}
}
