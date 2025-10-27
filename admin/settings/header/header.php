<?php

namespace EasyWPTranslator\Settings\Header;

/**
 * Header file for settings page
 *
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EasyWPTranslator\Settings\Header\Header' ) ) {
    /**
     * Header class
     * @param mixed $tab
     */
	class Header {

		/**
		 * Instance of the class
		 * @var mixed
		 */
		private static $instance;

		/**
		 * Active tab
		 * @var mixed
		 */
		private $active_tab;

        /**
         * Model
         * @var mixed
         */
        private $model;

		/**
		 * Get instance of the class
		 * @param mixed $tab
		 * @param mixed $model
		 * @return mixed
		 */
		public static function get_instance( $tab, $model ) {
			if ( null === self::$instance ) {
				self::$instance = new self( $tab, $model );
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 * @param mixed $tab
		 * @param mixed $model
		 */
		public function __construct( $tab, $model ) {
			$this->active_tab = sanitize_text_field( $tab );
			$this->model = $model;
		}

		/**
		 * Set active tab
		 * @param mixed $tab
		 */
		public function set_active_tab( $tab ) {
			$this->active_tab = $tab;
		}

		/**
		 * Tabs
		 * @return mixed
		 */
		public function tabs() {
			$default_url = '';

			if ( $this->active_tab && in_array($this->active_tab, ['strings', 'lang', 'supported-blocks','custom-fields']) ) {
				$default_url = 'ewt_settings';
			}

			$tabs = array(
				'general'     => array( 'title' => __( 'General Settings', 'easy-wp-translator' ) ),
				'lang'   => array( 'title' => __( 'Manage Languages', 'easy-wp-translator' ), 'redirect' => true, 'redirect_url' => 'ewt' ),
				'translation' => array( 'title' => __( 'AI Translation', 'easy-wp-translator' ) ),
				'switcher'    => array( 'title' => __( 'Language Switcher', 'easy-wp-translator' ) ),
				'supported-blocks' => array( 'title' => __( 'Supported Blocks', 'easy-wp-translator' ), 'redirect' => true, 'redirect_url' => 'ewt_settings&tab=supported-blocks' ),
				'custom-fields' => array( 'title' => __( 'Custom Fields', 'easy-wp-translator' ), 'redirect' => true, 'redirect_url' => 'ewt_settings&tab=custom-fields' ),
			);

            $languages = $this->model->get_languages_list();
            $static_strings_visibility = $this->model->options->get( 'static_strings_visibility' );
            if(!empty($languages) && $static_strings_visibility){
                $tabs['strings']     = array(
					'title'        => __( 'Static Strings', 'easy-wp-translator' ),
					'redirect'     => true,
					'redirect_url' => 'ewt_settings&tab=strings',
				);
            }

			if ( $default_url && ! empty( $default_url ) ) {
				$tabs['general']['redirect']         = true;
				$tabs['general']['redirect_url']     = $default_url . '&tab=general';
				$tabs['translation']['redirect']     = true;
				$tabs['translation']['redirect_url'] = $default_url . '&tab=translation';

				$tabs['switcher']['redirect']     = true;
				$tabs['switcher']['redirect_url'] = $default_url . '&tab=switcher';
			}

			return apply_filters( 'ewt_settings_header_tabs', $tabs );
		}

		/**
		 * @return void
		 */
		public function header() {
			echo '<div id="ewt-settings-header">';
			echo '<div id="ewt-settings-header-tabs">';
			echo '<div class="ewt-settings-header-tab-container">';
			echo '<div class="ewt-settings-header-logo">';
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=ewt_settings&tab=general' ) ) . '"><img src="' . esc_url( plugin_dir_url( EASY_WP_TRANSLATOR_ROOT_FILE ) . 'assets/logo/easywptranslator_icon.svg' ) . '" alt="EasyWPTranslator" /></a>';
			echo '</div>';
			echo '<div class="ewt-settings-header-tab-list">';
			foreach ( $this->tabs() as $key => $value ) {
				$active_class = $this->active_tab === $key ? 'active' : '';
				$title        = $value['title'];
				$redirect     = isset( $value['redirect'] ) ? $value['redirect'] : false;
				$redirect_url = $redirect && isset( $value['redirect_url'] ) ? $value['redirect_url'] : false;
				if ( $redirect && $redirect_url && $this->active_tab !== $key ) {
					echo '<a href="' . esc_url( admin_url( 'admin.php?page=' . esc_attr( $redirect_url ) ) ) . '"><div class="ewt-settings-header-tab ' . esc_attr( $active_class ) . '" data-tab="' . esc_attr( $key ) . '" title="' . esc_attr( $title ) . '" data-link="true">' . esc_html(  $title  ) . '</div></a>';
				} else {
					echo '<div class="ewt-settings-header-tab ' . esc_attr( $active_class ) . '" data-tab="' . esc_attr( $key ) . '" title="' . esc_attr( $title ) . '">' . esc_html(  $title  ) . '</div>';
				}
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '</div>';
		}

		/**
		 * @return void
		 */
		public function header_assets() {
			wp_enqueue_style( 'ewt-settings-header', plugins_url( 'admin/assets/css/settings-header.css', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION );
		}
	}

}
