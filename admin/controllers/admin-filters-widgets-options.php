<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Filters\EWT_Filters_Widgets_Options;


/**
 * Class EWT_Widgets_Filters
 *
 *  
 *
 * Adds new options to WP_Widget and saves them.
 */
class EWT_Admin_Filters_Widgets_Options extends EWT_Filters_Widgets_Options {
	/**
	 * Modifies the widgets forms to add our language dropdown list.
	 *
	 *  
	 *
	 * @param WP_Widget $widget Widget instance.
	 * @param null      $return Not used.
	 * @param array     $instance Widget settings.
	 * @return void
	 *
	 * @phpstan-param WP_Widget<array<string, mixed>> $widget
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$screen = get_current_screen();

		// Test the Widgets screen and the Customizer to avoid displaying the option in page builders
		// Saving the widget reloads the form. And curiously the action is in $_REQUEST but neither in $_POST, nor in $_GET.
		if ( ( isset( $screen ) && 'widgets' === $screen->base ) || ( isset( $_REQUEST['action'] ) && 'save-widget' === $_REQUEST['action'] ) || isset( $GLOBALS['wp_customize'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			parent::in_widget_form( $widget, $return, $instance );
		}
	}
}
