<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * A class to manage manage the language cookie
 *
 *  
 */
class EWT_Cookie {
	/**
	 * Parses the cookie parameters.
	 *
	 *  
	 *
	 * @param array $args {@see EWT_Cookie::set()}
	 * @return array
	 */
	protected static function parse_args( $args ) {
		/**
		 * Filters the EasyWPTranslator cookie duration.
		 *
		 * If a cookie duration of 0 is specified, a session cookie will be set.
		 * If a negative cookie duration is specified, the cookie is removed.
		 * /!\ This filter may be fired *before* the theme is loaded.
		 *
		 *  
		 *
		 * @param int $duration Cookie duration in seconds.
		 */
		$expiration = (int) apply_filters( 'ewt_cookie_expiration', YEAR_IN_SECONDS );

		$defaults = array(
			'expires'  => 0 !== $expiration ? time() + $expiration : 0,
			'path'     => COOKIEPATH,
			'domain'   => COOKIE_DOMAIN, // Cookie domain must be set to false for localhost (default value for `COOKIE_DOMAIN`)
			'secure'   => is_ssl(),
			'httponly' => false,
			'samesite' => 'Lax',
		);

		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filters the EasyWPTranslator cookie arguments.
		 * /!\ This filter may be fired *before* the theme is loaded.
		 *
		 *  
		 *
		 * @param array $args {
		 *   Optional. Array of arguments for setting the cookie.
		 *
		 *   @type int    $expires  Cookie duration.
		 *                          If a cookie duration of 0 is specified, a session cookie will be set.
		 *                          If a negative cookie duration is specified, the cookie is removed.
		 *   @type string $path     Cookie path.
		 *   @type string $domain   Cookie domain. Must be set to false for localhost (default value for `COOKIE_DOMAIN`).
		 *   @type bool   $secure   Should the cookie be sent only over https?
		 *   @type bool   $httponly Should the cookie be accessed only over http protocol?.
		 *   @type string $samesite Either 'Strict', 'Lax' or 'None'.
		 * }
		 */
		return (array) apply_filters( 'ewt_cookie_args', $args );
	}

	/**
	 * Sets the cookie.
	 *
	 *  
	 *
	 * @param string $lang Language cookie value.
	 * @param array  $args {
	 *   Optional. Array of arguments for setting the cookie.
	 *
	 *   @type string $path     Cookie path, defaults to COOKIEPATH.
	 *   @type string $domain   Cookie domain, defaults to COOKIE_DOMAIN
	 *   @type bool   $secure   Should the cookie be sent only over https?
	 *   @type bool   $httponly Should the cookie accessed only over http protocol? Defaults to false.
	 *   @type string $samesite Either 'Strict', 'Lax' or 'None', defaults to 'Lax'.
	 * }
	 * @return void
	 */
	public static function set( $lang, $args = array() ) {
		$args = self::parse_args( $args );

		if ( ! headers_sent() && EWT_COOKIE !== false && self::get() !== $lang ) {
			if ( version_compare( PHP_VERSION, '7.3', '<' ) ) {
				$args['path'] .= '; SameSite=' . $args['samesite']; // Hack to set SameSite value in PHP < 7.3. Doesn't work with newer versions.
				setcookie( EWT_COOKIE, $lang, $args['expires'], $args['path'], $args['domain'], $args['secure'], $args['httponly'] );
			} else {
				setcookie( EWT_COOKIE, $lang, $args );
			}
		}
	}

	/**
	 * Returns the language cookie value.
	 *
	 *  
	 *
	 * @return string
	 */
	public static function get() {
		return isset( $_COOKIE[ EWT_COOKIE ] ) ? sanitize_key( $_COOKIE[ EWT_COOKIE ] ) : '';
	}
}
