<?php
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
use EasyWPTranslator\Admin\Controllers\EWT_Admin_Strings;
use EasyWPTranslator\Includes\Controllers\EWT_Switcher;
use EasyWPTranslator\Includes\Other\EWT_Language;
use EasyWPTranslator\Includes\Helpers\EWT_MO;

/**
 * API for languages and translations management.
 * All API functions are loaded when 'ewt_init' action is fired.
 * You can check if EasyWPTranslator is active by checking if the function 'ewt_the_languages' exists.
 *
 *  
 */



/**
 * The EasyWPTranslator public API.
 *
 * @package EasyWPTranslator
 */

/**
 * Template tag: displays the language switcher.
 * The function does nothing if used outside the frontend.
 *
 * @api
 *  
 *
 * @param array $args {
 *   Optional array of arguments.
 *
 *   @type int    $dropdown               The list is displayed as dropdown if set to 1, defaults to 0.
 *   @type int    $echo                   Echoes the list if set to 1, defaults to 1.
 *   @type int    $hide_if_empty          Hides languages with no posts ( or pages ) if set to 1, defaults to 1.
 *   @type int    $show_flags             Displays flags if set to 1, defaults to 0.
 *   @type int    $show_names             Shows language names if set to 1, defaults to 1.
 *   @type string $display_names_as       Whether to display the language name or its slug, valid options are 'slug' and 'name', defaults to name.
 *   @type int    $force_home             Will always link to the homepage in the translated language if set to 1, defaults to 0.
 *   @type int    $hide_if_no_translation Hides the link if there is no translation if set to 1, defaults to 0.
 *   @type int    $hide_current           Hides the current language if set to 1, defaults to 0.
 *   @type int    $post_id                Returns links to the translations of the post defined by post_id if set, defaults to not set.
 *   @type int    $raw                    Return a raw array instead of html markup if set to 1, defaults to 0.
 *   @type string $item_spacing           Whether to preserve or discard whitespace between list items, valid options are 'preserve' and 'discard', defaults to 'preserve'.
 * }
 * @return string|array Either the html markup of the switcher or the raw elements to build a custom language switcher.
 */
function ewt_the_languages( $args = array() ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || empty( $easywptranslator->links ) ) {
		return empty( $args['raw'] ) ? '' : array();
	}

	$switcher = new EWT_Switcher();
	return $switcher->the_languages( $easywptranslator->links, $args );
}

/**
 * Returns the current language on frontend.
 * Returns the language set in admin language filter on backend (false if set to all languages).
 *
 * @api
 *  
 *   Accepts composite values.
 *
 * @param string $field Optional, the language field to return (@see EWT_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see EWT_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|EWT_Language The requested field or object for the current language, `false` if the field isn't set or if current language doesn't exist yet.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? EWT_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function ewt_current_language( $field = 'slug' ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || empty( $easywptranslator->curlang ) ) {
		return false;
	}

	if ( \OBJECT === $field ) {
		return $easywptranslator->curlang;
	}

	return $easywptranslator->curlang->get_prop( $field );
}

/**
 * Returns the default language.
 *
 * @api
 *  
 *   Accepts composite values.
 *
 * @param string $field Optional, the language field to return (@see EWT_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see EWT_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|EWT_Language The requested field or object for the default language, `false` if the field isn't set or if default language doesn't exist yet.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? EWT_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function ewt_default_language( $field = 'slug' ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return false;
	}
	
	$lang = $easywptranslator->model->get_default_language();

	if ( empty( $lang ) ) {
		return false;
	}

	if ( \OBJECT === $field ) {
		return $lang;
	}

	return $lang->get_prop( $field );
}

/**
 * Among the post and its translations, returns the ID of the post which is in the language represented by $lang.
 *
 * @api
 *  
 *   Returns `0` instead of `false` if not translated or if the post has no language.
 *   $lang accepts `EWT_Language` or string.
 *
 * @param int                 $post_id Post ID.
 * @param EWT_Language|string $lang    Optional language (object or slug), defaults to the current language.
 * @return int The translation post ID if exists. 0 if not translated, the post has no language or if the language doesn't exist.
 *
 * @phpstan-return int<0, max>
 */
function ewt_get_post( $post_id, $lang = '' ) {
	$lang = $lang ?: ewt_current_language();

	if ( empty( $lang ) ) {
		return 0;
	}

	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return 0;
	}

	return $easywptranslator->model->post->get( $post_id, $lang );
}

/**
 * Among the term and its translations, returns the ID of the term which is in the language represented by $lang.
 *
 * @api
 *  
 *   Returns `0` instead of `false` if not translated or if the term has no language.
 *   $lang accepts EWT_Language or string.
 *
 * @param int                 $term_id Term ID.
 * @param EWT_Language|string $lang    Optional language (object or slug), defaults to the current language.
 * @return int The translation term ID if exists. 0 if not translated, the term has no language or if the language doesn't exist.
 *
 * @phpstan-return int<0, max>
 */
function ewt_get_term( $term_id, $lang = '' ) {
	$lang = $lang ?: ewt_current_language();

	if ( empty( $lang ) ) {
		return 0;
	}

	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return 0;
	}

	return $easywptranslator->model->term->get( $term_id, $lang );
}

/**
 * Returns the home url in a language.
 *
 * @api
 *  
 *
 * @param string $lang Optional language code, defaults to the current language.
 * @return string
 */
function ewt_home_url( $lang = '' ) {
	if ( empty( $lang ) ) {
		$lang = ewt_current_language();
	}

	$easywptranslator = EWT();
	if ( empty( $lang ) || ! $easywptranslator || empty( $easywptranslator->links ) ) {
		return home_url( '/' );
	}

	return $easywptranslator->links->get_home_url( $lang );
}

/**
 * Registers a string for translation in the "strings translation" panel.
 *
 * @api
 *  
 *
 * @param string $name      A unique name for the string.
 * @param string $string    The string to register.
 * @param string $context   Optional, the group in which the string is registered, defaults to 'easy-wp-translator'.
 * @param bool   $multiline Optional, true if the string table should display a multiline textarea,
 *                          false if should display a single line input, defaults to false.
 * @return void
 */
function ewt_register_string( $name, $string, $context = 'EasyWPTranslator', $multiline = false ) {
	if ( EWT() instanceof EWT_Admin_Base ) {
		EWT_Admin_Strings::register_string( $name, $string, $context, $multiline );
	}
}

/**
 * Translates a string ( previously registered with ewt_register_string ).
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @return string The string translated in the current language.
 */
function ewt__( $string ) {
	if ( ! is_scalar( $string ) || '' === $string ) {
		return $string;
	}

	if ( ! empty( $GLOBALS['l10n']['ewt_string'] ) && $GLOBALS['l10n']['ewt_string'] instanceof EWT_MO ) {
		return $GLOBALS['l10n']['ewt_string']->translate( $string );
	}
}

/**
 * Translates a string ( previously registered with ewt_register_string ) and escapes it for safe use in HTML output.
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @return string The string translated in the current language.
 */
function ewt_esc_html__( $string ) {
	return esc_html( ewt__( $string ) );
}

/**
 * Translates a string ( previously registered with ewt_register_string ) and escapes it for safe use in HTML attributes.
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @return string The string translated in the current language.
 */
function ewt_esc_attr__( $string ) {
	return esc_attr( ewt__( $string ) );
}

/**
 * Echoes a translated string ( previously registered with ewt_register_string )
 * It is an equivalent of _e() and is not escaped.
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @return void
 */
function ewt_e( $string ) {
	echo ewt__( $string ); // phpcs:ignore
}

/**
 * Echoes a translated string ( previously registered with ewt_register_string ) and escapes it for safe use in HTML output.
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @return void
 */
function ewt_esc_html_e( $string ) {
	echo ewt_esc_html__( $string ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Echoes a translated a string ( previously registered with ewt_register_string ) and escapes it for safe use in HTML attributes.
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @return void
 */
function ewt_esc_attr_e( $string ) {
	echo ewt_esc_attr__( $string ); // phpcs:ignore WordPress.Security.EscapeOutput
}

/**
 * Translates a string ( previously registered with ewt_register_string ).
 *
 * @api
 *  
 *
 * @param string $string The string to translate.
 * @param string $lang   Language code.
 * @return string The string translated in the requested language.
 */
function ewt_translate_string( $string, $lang ) {
	if ( EWT() instanceof EWT_Frontend && ewt_current_language() === $lang ) {
		return ewt__( $string );
	}

	if ( ! is_scalar( $string ) || '' === $string ) {
		return $string;
	}

	$lang = EWT()->model->get_language( $lang );

	if ( empty( $lang ) ) {
		return $string;
	}

	$mo = new EWT_MO();
	$mo->import_from_db( $lang );

	return $mo->translate( $string );
}

/**
 * Returns true if EasyWPTranslator manages languages and translations for this post type.
 *
 * @api
 *  
 *
 * @param string $post_type Post type name.
 * @return bool
 */
function ewt_is_translated_post_type( $post_type ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return false;
	}
	return $easywptranslator->model->is_translated_post_type( $post_type );
}

/**
 * Returns true if EasyWPTranslator manages languages and translations for this taxonomy.
 *
 * @api
 *  
 *
 * @param string $tax Taxonomy name.
 * @return bool
 */
function ewt_is_translated_taxonomy( $tax ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return false;
	}
	return $easywptranslator->model->is_translated_taxonomy( $tax );
}

/**
 * Returns the list of available languages.
 *
 * @api
 *  
 *
 * @param array $args {
 *   Optional array of arguments.
 *
 *   @type bool   $hide_empty Hides languages with no posts if set to true ( defaults to false ).
 *   @type string $fields     Return only that field if set ( @see EWT_Language for a list of fields ), defaults to 'slug'.
 * }
 * @return string[]
 */
function ewt_languages_list( $args = array() ) {
	$args = wp_parse_args( $args, array( 'fields' => 'slug' ) );
	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return array();
	}
	return $easywptranslator->model->get_languages_list( $args );
}

/**
 * Sets the post language.
 *
 * @api
 *  
 *   $lang accepts EWT_Language or string.
 *   Returns a boolean.
 *
 * @param int                 $id   Post ID.
 * @param EWT_Language|string $lang Language (object or slug).
 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
 *              the post).
 */
function ewt_set_post_language( $id, $lang ) {
	return EWT()->model->post->set_language( $id, $lang );
}

/**
 * Sets the term language.
 *
 * @api
 *  
 *   $lang accepts EWT_Language or string.
 *   Returns a boolean.
 *
 * @param int                 $id   Term ID.
 * @param EWT_Language|string $lang Language (object or slug).
 * @return bool True when successfully assigned. False otherwise (or if the given language is already assigned to
 *              the term).
 */
function ewt_set_term_language( $id, $lang ) {
	return EWT()->model->term->set_language( $id, $lang );
}

/**
 * Save posts translations.
 *
 * @api
 *  
 *   Returns an associative array of translations.
 *
 * @param int[] $arr An associative array of translations with language code as key and post ID as value.
 * @return int[] An associative array with language codes as key and post IDs as values.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function ewt_save_post_translations( $arr ) {
	$id = reset( $arr );
	if ( $id ) {
		return EWT()->model->post->save_translations( $id, $arr );
	}

	return array();
}

/**
 * Save terms translations
 *
 * @api
 *  
 *   Returns an associative array of translations.
 *
 * @param int[] $arr An associative array of translations with language code as key and term ID as value.
 * @return int[] An associative array with language codes as key and term IDs as values.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function ewt_save_term_translations( $arr ) {
	$id = reset( $arr );
	if ( $id ) {
		return EWT()->model->term->save_translations( $id, $arr );
	}

	return array();
}

/**
 * Returns the post language.
 *
 * @api
 *  
 *   Accepts composite values for `$field`.
 *
 * @param int    $post_id Post ID.
 * @param string $field Optional, the language field to return (@see EWT_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see EWT_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|EWT_Language The requested field or object for the post language, `false` if no language is associated to that post.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? EWT_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function ewt_get_post_language( $post_id, $field = 'slug' ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return false;
	}
	
	$lang = $easywptranslator->model->post->get_language( $post_id );

	if ( empty( $lang ) || \OBJECT === $field ) {
		return $lang;
	}

	return $lang->get_prop( $field );
}

/**
 * Returns the term language.
 *
 * @api
 *  
 *   Accepts composite values for `$field`.
 *
 * @param int    $term_id Term ID.
 * @param string $field Optional, the language field to return (@see EWT_Language), defaults to `'slug'`.
 *                      Pass `\OBJECT` constant to get the language object. A composite value can be used for language
 *                      term property values, in the form of `{language_taxonomy_name}:{property_name}` (see
 *                      {@see EWT_Language::get_tax_prop()} for the possible values). Ex: `term_language:term_taxonomy_id`.
 * @return string|int|bool|string[]|EWT_Language The requested field or object for the post language, `false` if no language is associated to that term.
 *
 * @phpstan-return (
 *     $field is \OBJECT ? EWT_Language : (
 *         $field is 'slug' ? non-empty-string : string|int|bool|list<non-empty-string>
 *     )
 * )|false
 */
function ewt_get_term_language( $term_id, $field = 'slug' ) {
	$lang = EWT()->model->term->get_language( $term_id );

	if ( empty( $lang ) || \OBJECT === $field ) {
		return $lang;
	}

	return $lang->get_prop( $field );
}

/**
 * Returns an array of translations of a post.
 *
 * @api
 *  
 *
 * @param int $post_id Post ID.
 * @return int[] An associative array of translations with language code as key and translation post ID as value.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function ewt_get_post_translations( $post_id ) {
	$easywptranslator = EWT();
	if ( ! $easywptranslator || ! isset( $easywptranslator->model ) ) {
		return array();
	}
	return $easywptranslator->model->post->get_translations( $post_id );
}

/**
 * Returns an array of translations of a term.
 *
 * @api
 *  
 *
 * @param int $term_id Term ID.
 * @return int[] An associative array of translations with language code as key and translation term ID as value.
 *
 * @phpstan-return array<non-empty-string, positive-int>
 */
function ewt_get_term_translations( $term_id ) {
	return EWT()->model->term->get_translations( $term_id );
}

/**
 * Counts posts in a language.
 *
 * @api
 *  
 *
 * @param string $lang Language code.
 * @param array  $args {
 *   Optional array of arguments.
 *
 *   @type string $post_type   Post type.
 *   @type int    $m           YearMonth ( ex: 201307 ).
 *   @type int    $year        4 digit year.
 *   @type int    $monthnum    Month number (from 1 to 12).
 *   @type int    $day         Day of the month (from 1 to 31).
 *   @type int    $author      Author id.
 *   @type string $author_name Author nicename.
 *   @type string $post_format Post format.
 *   @type string $post_status Post status.
 *  @type array $meta_query custom meta fields.
 * }
 * @return int Posts count.
 */
function ewt_count_posts( $lang, $args = array() ) {
	$lang = EWT()->model->get_language( $lang );

	if ( empty( $lang ) ) {
		return 0;
	}

	return EWT()->model->count_posts( $lang, $args );
}

/**
 * Wraps `wp_insert_post` with language feature.
 *
 *  
 *
 * @param array               $postarr {
 *     An array of elements that make up a post to insert.
 *
 *     @type string[] $translations The translation group to assign to the post with language slug as keys and post ID as values.
 * }
 * @param EWT_Language|string $language The post language object or slug.
 * @return int|WP_Error The post ID on success. The value `WP_Error` on failure.
 */
function ewt_insert_post( array $postarr, $language ) {
	$language = EWT()->model->get_language( $language );

	if ( ! $language instanceof EWT_Language ) {
		return new WP_Error( 'invalid_language', __( 'Please provide a valid language.', 'easy-wp-translator' ) );
	}

	return EWT()->model->post->insert( $postarr, $language );
}

/**
 * Wraps `wp_insert_term` with language feature.
 *
 *  
 *
 * @param string              $term     The term name to add.
 * @param string              $taxonomy The taxonomy to which to add the term.
 * @param EWT_Language|string $language The term language object or slug.
 * @param array               $args {
 *     Optional. Array of arguments for inserting a term.
 *
 *     @type string   $alias_of     Slug of the term to make this term an alias of.
 *                                  Default empty string. Accepts a term slug.
 *     @type string   $description  The term description. Default empty string.
 *     @type int      $parent       The id of the parent term. Default 0.
 *     @type string   $slug         The term slug to use. Default empty string.
 *     @type string[] $translations The translation group to assign to the term with language slug as keys and `term_id` as values.
 * }
 * @return array|WP_Error {
 *     An array of the new term data, `WP_Error` otherwise.
 *
 *     @type int        $term_id          The new term ID.
 *     @type int|string $term_taxonomy_id The new term taxonomy ID. Can be a numeric string.
 * }
 */
function ewt_insert_term( string $term, string $taxonomy, $language, array $args = array() ) {
	$language = EWT()->model->get_language( $language );

	if ( ! $language instanceof EWT_Language ) {
		return new WP_Error( 'invalid_language', __( 'Please provide a valid language.', 'easy-wp-translator' ) );
	}

	return EWT()->model->term->insert( $term, $taxonomy, $language, $args );
}

/**
 * Wraps `wp_update_post` with language feature.
 *
 *  
 *
 * @param array $postarr {
 *     Optional. An array of elements that make up a post to update.
 *
 *     @type EWT_Language|string $lang         The post language object or slug.
 *     @type string[]            $translations The translation group to assign to the post with language slug as keys and post ID as values.
 * }
 * @return int|WP_Error The post ID on success. The value `WP_Error` on failure.
 */
function ewt_update_post( array $postarr ) {
	return EWT()->model->post->update( $postarr );
}

/**
 * Wraps `wp_update_term` with language feature.
 *
 *  
 *
 * @param int   $term_id The ID of the term.
 * @param array $args {
 *     Optional. Array of arguments for updating a term.
 *
 *     @type string              $alias_of     Slug of the term to make this term an alias of.
 *                                             Default empty string. Accepts a term slug.
 *     @type string              $description  The term description. Default empty string.
 *     @type int                 $parent       The id of the parent term. Default 0.
 *     @type string              $slug         The term slug to use. Default empty string.
 *     @type string              $name         The term name.
 *     @type EWT_Language|string $lang         The term language object or slug.
 *     @type string[]            $translations The translation group to assign to the term with language slug as keys and `term_id` as values.
 * }
 * @return array|WP_Error {
 *     An array containing the `term_id` and `term_taxonomy_id`, `WP_Error` otherwise.
 *
 *     @type int        $term_id          The new term ID.
 *     @type int|string $term_taxonomy_id The new term taxonomy ID. Can be a numeric string.
 * }
 */
function ewt_update_term( int $term_id, array $args = array() ) {
	return EWT()->model->term->update( $term_id, $args );
}

/**
 * Allows to access the EasyWPTranslator instance.
 * However, it is always preferable to use API functions
 * as internal methods may be changed without prior notice.
 *
 *  
 *
 * @return EWT_Frontend|EWT_Admin|EWT_Settings|EWT_REST_Request|null
 */
function EWT() { // PHPCS:ignore WordPress.NamingConventions.ValidFunctionName
	return isset( $GLOBALS['easywptranslator'] ) ? $GLOBALS['easywptranslator'] : null;
}
