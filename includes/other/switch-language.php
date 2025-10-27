<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Includes\Other;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Class to handle site language switch.
 */
class EWT_Switch_Language {

	/**
	 * @var EWT_Model
	 */
	private static $model;

	/**
	 * The previous language.
	 *
	 * @var EWT_Language|null
	 */
	public static $previous_language;

	/**
	 * The original language.
	 *
	 * @var EWT_Language|null
	 */
	private static $original_language;

	/**
	 * The current language.
	 *
	 * @var EWT_Language|null
	 */
	private static $current_language;

	/**
	 * Setups filters.
	 *
	 *
	 * @param EWT_Model $model Instance of `EWT_Model`.
	 * @return void
	 */
	public static function init( EWT_Model $model ): void {
		self::$model = $model;

		add_action( 'ewt_language_defined', array( static::class, 'set_current_language' ), -1000 );
	}

	/**
	 * Sets the current language.
	 * Hooked to `ewt_language_defined`.
	 *
	 *
	 * @param string $slug Current language slug.
	 * @return void
	 */
	public static function set_current_language( $slug ): void {
		$language = self::$model->languages->get( $slug );
		self::$current_language = ! empty( $language ) ? $language : null;
	}

	/**
	 * Switches to the given language.
	 * Hooked to `ewt_post_synchronized` at first.
	 *
	 *
	 * @param int    $post_id ID of the source post.
	 * @param int    $tr_id   ID of the target post.
	 * @param string $lang    Language of the target post.
	 * @return void
	 */
	public static function on_post_synchronized( $post_id, $tr_id, $lang ): void {
		self::switch_language( $lang );
	}

	/**
	 * Switches the language back.
	 * Hooked to `ewt_post_synchronized` at last.
	 *
	 * @since 3.7
	 *
	 * @return void
	 */
	public static function after_post_synchronized(): void {
		self::$previous_language = self::$current_language;

		self::restore_original_language();
	}

	/**
	 * Switches the site to the given language.
	 *
	 *
	 * @param EWT_Language|string|null $language The language we want to switch to.
	 * @return void
	 */
	public static function switch_language( $language = null ): void {
		if ( null === $language ) {
			self::$current_language = null;
			return;
		}

		$language = self::$model->languages->get( $language );
		if ( ! $language instanceof EWT_Language ) {
			return;
		}

		if ( self::$current_language === $language ) {
			return;
		}

		if ( ! in_array( $language, self::$model->languages->get_list(), true ) ) {
			return;
		}

		// Stores the original language if not done yet.
		if ( null === self::$original_language ) {
			self::$original_language = self::$current_language;
		}

		self::$current_language = $language;

		self::load_strings_translations( $language->slug );

		/**
		 * Fires when the language is switched.
		 *
		 *
		 * @param EWT_Language $language The new language.
		 */
		do_action( 'ewt_switch_language', $language );
	}

	/**
	 * Restores the original language.
	 *
	 *
	 * @return void
	 */
	public static function restore_original_language(): void {
		self::switch_language( self::$original_language );
	}

	/**
	 * Loads user defined strings translations
	 *
	 *
	 * @param string $locale Language locale or slug. Defaults to current locale.
	 * @return void
	 */
	public static function load_strings_translations( $locale = '' ): void {
		if ( empty( $locale ) ) {
			$locale = ( is_admin() && ! EasyWPTranslator::is_ajax_on_front() ) ? get_user_locale() : get_locale();
		}

		$language = self::$model->get_language( $locale );

		if ( ! empty( $language ) ) {
			$mo = new EWT_MO();
			$mo->import_from_db( $language );
			$GLOBALS['l10n']['ewt_string'] = &$mo;
		} else {
			unset( $GLOBALS['l10n']['ewt_string'] );
		}
	}
}