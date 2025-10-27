<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Integrations\domain_mapping;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages the compatibility with WordPress MU Domain Mapping.
 *
 *  
 */
class EWT_Domain_Mapping {

	/**
	 * Setups actions.
	 *
	 *  
	 */
	public function __construct() {
		if ( function_exists( 'redirect_to_mapped_domain' ) ) {
			$options = get_option( 'easywptranslator' );

			if ( is_array( $options ) && $options['force_lang'] < 2 ) {
				ewt_set_constant( 'EWT_CACHE_HOME_URL', false );
			}

			if ( ! get_site_option( 'dm_no_primary_domain' ) ) {
				remove_action( 'template_redirect', 'redirect_to_mapped_domain' );
				add_action( 'template_redirect', array( $this, 'dm_redirect_to_mapped_domain' ) );
			}
		}
	}

	/**
	 * Fix primary domain check which forces only one domain per blog.
	 * Accept only known domains/subdomains for the current blog.
	 *
	 *  
	 */
	public function dm_redirect_to_mapped_domain() {
		// Don't go further if we stopped loading the plugin early (for example when deactivate-easywptranslator=1).
		if ( ! function_exists( 'EWT' ) ) {
			// Rely on MU Domain Mapping.
			redirect_to_mapped_domain();
		}

		// The language is set from the subdomain or domain name
		if ( EWT()->options['force_lang'] > 1 ) {
			// Don't redirect the main site
			if ( is_main_site() ) {
				return;
			}

			// Don't redirect post previews
			if ( isset( $_GET['preview'] ) && 'true' === $_GET['preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			// Don't redirect theme customizer
			if ( isset( $_POST['customize'] ) && isset( $_POST['theme'] ) && 'on' === $_POST['customize'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}

			// If we can't associate the requested domain to a language, redirect to the default domain
			$requested_url  = ewt_get_requested_url();
			$requested_host = wp_parse_url( $requested_url, PHP_URL_HOST );

			$hosts = EWT()->links_model->get_hosts();
			$lang  = array_search( $requested_host, $hosts );

			if ( empty( $lang ) ) {
				$status   = get_site_option( 'dm_301_redirect' ) ? '301' : '302'; // Honor status redirect option
				$redirect = str_replace( '://' . $requested_host, '://' . $hosts[ EWT()->options['default_lang'] ], $requested_url );
				wp_safe_redirect( $redirect, $status );
				exit;
			}
		} else {
			// Otherwise rely on MU Domain Mapping
			redirect_to_mapped_domain();
		}
	}
}
