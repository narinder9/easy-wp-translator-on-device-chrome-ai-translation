<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Install;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * A generic activation / de-activation class compatible with multisite
 *
 *  
 */
class EWT_Install_Base {
	/**
	 * The plugin basename.
	 *
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * Constructor
	 *
	 *  
	 *
	 * @param string $plugin_basename Plugin basename
	 */
	public function __construct( $plugin_basename ) {
		$this->plugin_basename = $plugin_basename;

		// Site creation on multisite.
		add_action( 'wp_initialize_site', array( $this, 'new_site' ), 50 ); // After WP (prio 10).
	}

	/**
	 * Activation or deactivation for all blogs.
	 *
	 *  
	 *
	 * @param string $what        Either 'activate' or 'deactivate'.
	 * @param bool   $networkwide Whether the plugin is (de)activated for all sites in the network or just the current site.
	 * @return void
	 */
	protected function do_for_all_blogs( $what, $networkwide ) {
		// Network
		if ( is_multisite() && $networkwide ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB query is intentionally used here to efficiently retrieve all blog IDs in a multisite network. There is no performant or reliable WordPress API alternative for this operation, especially for older WP versions (< 4.2). This is required for correct plugin activation/deactivation across all sites.
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				'activate' == $what ? $this->_activate() : $this->_deactivate();
			}
			restore_current_blog();
		}

		// Single blog
		else {
			'activate' == $what ? $this->_activate() : $this->_deactivate();
		}
	}

	/**
	 * Plugin activation for multisite.
	 *
	 *  
	 *
	 * @param bool $networkwide Whether the plugin is activated for all sites in the network or just the current site.
	 * @return void
	 */
	public function activate( $networkwide ) {
		$this->do_for_all_blogs( 'activate', $networkwide );
	}

	/**
	 * Plugin activation
	 *
	 *  
	 *
	 * @return void
	 */
	protected function _activate() {
		// Can be overridden in child class
	}

	/**
	 * Plugin deactivation for multisite.
	 *
	 *  
	 *
	 * @param bool $networkwide Whether the plugin is deactivated for all sites in the network or just the current site.
	 * @return void
	 */
	public function deactivate( $networkwide ) {
		$this->do_for_all_blogs( 'deactivate', $networkwide );
	}

	/**
	 * Plugin deactivation
	 *
	 *  
	 *
	 * @return void
	 */
	protected function _deactivate() {
		// Can be overridden in child class
	}

	/**
	 * Site creation on multisite ( to set default options )
	 *
	 *  
	 *
	 * @param WP_Site $new_site New site object.
	 * @return void
	 */
	public function new_site( $new_site ) {
		switch_to_blog( $new_site->id );
		$this->_activate();
		restore_current_blog();
	}
}
