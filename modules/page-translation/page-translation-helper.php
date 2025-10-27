<?php
namespace EasyWPTranslator\Modules\Page_Translation;

/**
 * EWT Page Translation Ajax Handler
 *
 * @package EasyWPTranslator
 */

/**
 * Do not access the page directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use EasyWPTranslator\Includes\Other\EWT_Translation_Dashboard;
use EasyWPTranslator\Custom_Fields\Custom_Fields;

/**
 * Handle EWT Page Translation ajax requests
 */
if ( ! class_exists( 'EWT_Page_Translation_Helper' ) ) {
	class EWT_Page_Translation_Helper {
		/**
		 * Member Variable
		 *
		 * @var instance
		 */
		private static $instance;
		/**
		 * Stores custom block data for processing and retrieval.
		 *
		 * This static array holds the data related to custom blocks that are
		 * used within the plugin. It can be utilized to manage and manipulate
		 * the custom block information as needed during AJAX requests.
		 *
		 * @var array
		 */
		private $custom_block_data_array = array();

		/**
		 * Gets an instance of our plugin.
		 *
		 * @param object $settings_obj timeline settings.
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 *
		 * @param object $settings_obj Plugin settings.
		 */
		public function __construct() {
			if ( is_admin() ) {
				add_action( 'wp_ajax_ewt_update_translate_data', array( $this, 'ewt_update_translate_data' ) );
			}
		}

		/**
		 * Fetches post meta fields via AJAX request.
		 */
		public function fetch_post_meta_fields() {
			if ( ! check_ajax_referer( 'ewt_fetch_post_meta_fields', 'meta_fields_key', false ) ) {
				wp_send_json_error( __( 'Invalid security token sent.', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}

			$post_id = isset( $_POST['postId']) ? absint(sanitize_text_field($_POST['postId'])) : false;

			if(!isset($post_id) || false === $post_id){
				wp_send_json_error( __( 'Invalid Post ID.', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}

			if(!current_user_can('edit_post', $post_id)){
				wp_send_json_error( __( 'Unauthorized', 'easy-wp-translator' ), 403 );
				wp_die( '0', 403 );
			}

			$post_meta_sync = true;

            if (!isset(EWT()->options['sync']) || (isset(EWT()->options['sync']) && !in_array('post_meta', EWT()->options['sync']))) {
                $post_meta_sync = false;
            }

			if($post_meta_sync){
				wp_send_json_success( __( 'Post meta sync is enabled. Please disable post meta sync in EasyWPTranslator settings.', 'easy-wp-translator' ) );
			}

			$allowed_meta_fields=Custom_Fields::get_allowed_custom_fields();
			$post_meta_fields=get_post_meta($post_id);

			$existed_meta_fields=array_intersect(array_keys($post_meta_fields), array_keys($allowed_meta_fields));
			$filtered_meta_fields=array();

			foreach($existed_meta_fields as $key){
				if(isset($post_meta_fields[$key]) && !empty($post_meta_fields[$key]) && isset($allowed_meta_fields[$key]['status']) && true === $allowed_meta_fields[$key]['status']){
					$value=$allowed_meta_fields[$key]['type'] && is_array($post_meta_fields[$key]) ? maybe_unserialize($post_meta_fields[$key][0]) : maybe_unserialize($post_meta_fields[$key]);
					$filtered_meta_fields[$key]=$value;
				}
			}

			wp_send_json_success( array( 'metaFields' => $filtered_meta_fields, 'allowedMetaFields' => $allowed_meta_fields ) );
			exit;
		}

		/**
		 * Fetches post content via AJAX request.
		 */
		public function fetch_post_content() {
			if ( ! check_ajax_referer( 'ewt_page_translation_admin', 'ewt_page_translation_nonce', false ) ) {
				wp_send_json_error( __( 'Invalid security token sent.', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}

			$post_id = absint( isset( $_POST['postId'] ) ? absint( sanitize_text_field( $_POST['postId'] ) ) : false );

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( __( 'Unauthorized', 'easy-wp-translator' ), 403 );
				wp_die( '0', 403 );
			}

			if ( false !== $post_id ) {
				$post_data               = get_post( absint( $post_id ) );
				$locale                  = isset( $_POST['local'] ) ? sanitize_text_field( $_POST['local'] ) : 'en';
				$current_locale          = isset( $_POST['current_local'] ) ? sanitize_text_field( $_POST['current_local'] ) : 'en';

				$slug_translation_option = 'title_translate';

				if(property_exists(EWT(), 'options') && isset(EWT()->options['ai_translation_configuration']['slug_translation_option'])){
					$slug_translation_option = EWT()->options['ai_translation_configuration']['slug_translation_option'];
				}

				$content = $post_data->post_content;

				if ( function_exists( 'ewt_replace_links_with_translations' ) ) {
					$content = ewt_replace_links_with_translations( $content, $locale, $current_locale );
				}

				$meta_fields = get_post_meta( $post_id );

				$data = array(
					'title'      => $post_data->post_title,
					'excerpt'    => $post_data->post_excerpt,
					'content'    => $content,
				);

				if ( $slug_translation_option === 'slug_translate' || $slug_translation_option === 'slug_keep' ) {
					$data['slug_name'] = urldecode( get_post_field( 'post_name', $post_id ) );
				}

				return wp_send_json_success( $data );
			} else {
				wp_send_json_error( __( 'Invalid Post ID.', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}

			exit;
		}

		public function ewt_update_translate_data() {
			if ( ! check_ajax_referer( 'ewt_update_translate_data_nonce', 'update_translation_key', false ) ) {
				wp_send_json_error( __( 'Invalid security token sent.', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}

			$post_id     = isset( $_POST['post_id'] ) ? absint( sanitize_text_field( $_POST['post_id'] ) ) : 0;
			$editor_type = isset( $_POST['editorType'] ) ? sanitize_text_field( $_POST['editorType'] ) : '';
			$extra_data  = isset( $_POST['extraData'] ) ? json_decode( wp_unslash( $_POST['extraData'] ), true ) : array();

			// Require capability based on context
			if ( $post_id > 0 ) {
				if ( ! current_user_can( 'edit_post', $post_id ) && $editor_type !== 'taxonomy' ) {
					wp_send_json_error( __( 'Unauthorized to edit post', 'easy-wp-translator' ), 403 );
					wp_die( '0', 403 );
				}
				
				if ( $editor_type === 'taxonomy' ) {
					if ( ! current_user_can( 'edit_posts' ) ) {
						wp_send_json_error( __( 'Unauthorized to edit terms', 'easy-wp-translator' ), 403 );
						wp_die( '0', 403 );
					}
				}
			} elseif ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error( __( 'Unauthorized', 'easy-wp-translator' ), 403 );
					wp_die( '0', 403 );
			}

			$provider            = isset( $_POST['provider'] ) ? sanitize_text_field( $_POST['provider'] ) : '';
			$total_string_count  = isset( $_POST['totalStringCount'] ) ? absint( $_POST['totalStringCount'] ) : 0;
			$total_word_count    = isset( $_POST['totalWordCount'] ) ? absint( $_POST['totalWordCount'] ) : 0;
			$total_char_count    = isset( $_POST['totalCharacterCount'] ) ? absint( $_POST['totalCharacterCount'] ) : 0;
			$date                = isset( $_POST['date'] ) ? date( 'Y-m-d H:i:s', strtotime( sanitize_text_field( $_POST['date'] ) ) ) : '';
			$source_string_count = isset( $_POST['sourceStringCount'] ) ? absint( $_POST['sourceStringCount'] ) : 0;
			$source_word_count   = isset( $_POST['sourceWordCount'] ) ? absint( $_POST['sourceWordCount'] ) : 0;
			$source_char_count   = isset( $_POST['sourceCharacterCount'] ) ? absint( $_POST['sourceCharacterCount'] ) : 0;
			$source_lang         = isset( $_POST['sourceLang'] ) ? sanitize_text_field( $_POST['sourceLang'] ) : '';
			$target_lang         = isset( $_POST['targetLang'] ) ? sanitize_text_field( $_POST['targetLang'] ) : '';
			$time_taken          = isset( $_POST['timeTaken'] ) ? absint( $_POST['timeTaken'] ) : 0;

			if ( class_exists( EWT_Translation_Dashboard::class ) ) {
				$translation_data = array(
					'post_id'                => $post_id,
					'service_provider'       => $provider,
					'source_language'        => $source_lang,
					'target_language'        => $target_lang,
					'time_taken'             => $time_taken,
					'string_count'           => $total_string_count,
					'word_count'             => $total_word_count,
					'character_count'        => $total_char_count,
					'source_string_count'    => $source_string_count,
					'source_word_count'      => $source_word_count,
					'source_character_count' => $source_char_count,
					'editor_type'            => $editor_type,
					'date_time'              => $date,
					'version_type'           => 'free',
				);

				if ( ! empty( $extra_data ) && is_array( $extra_data ) && count( $extra_data ) > 0 ) {
					foreach ( $extra_data as $key => $value ) {
						if ( ! isset( $translation_data[ $key ] ) && ! empty( $value ) && ! empty( $key ) ) {
							$translation_data[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
						}
					}
				}

				EWT_Translation_Dashboard::store_options(
					'ewt',
					'post_id',
					'update',
					$translation_data
				);

				wp_send_json_success(
					array(
						'message' => __( 'Translation data updated successfully', 'easy-wp-translator' ),
					)
				);
			} else {
				wp_send_json_error(
					array(
						'message' => __( 'EWT_Dashboard class not found', 'easy-wp-translator' ),
					)
				);
			}
			exit;
		}

		/**
		 * Handle AJAX request to update Elementor data.
		 */
		public function update_elementor_data() {
			if ( ! check_ajax_referer( 'ewt_page_translation_admin', 'ewt_page_translation_nonce', false ) ) {
				wp_send_json_error( __( 'Invalid security token sent.', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}
			$post_id = isset( $_POST['post_id'] ) ? absint( sanitize_text_field( $_POST['post_id'] ) ) : 0;
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error( __( 'Unauthorized', 'easy-wp-translator' ), 403 );
				wp_die( '0', 403 );
			}

			// Optional hardening: enforce valid JSON if not using Elementor Document API
			if ( isset( $_POST['elementor_data'] ) && is_string( $_POST['elementor_data'] ) ) {
				$decoded = json_decode( stripslashes( $_POST['elementor_data'] ), true );
				if ( json_last_error() !== JSON_ERROR_NONE ) {
					wp_send_json_error( __( 'Invalid data.', 'easy-wp-translator' ), 400 );
					wp_die( '0', 400 );
				}
			}

			$parent_post_id          = intval( $_POST['parent_post_id'] );

			$current_slug            = get_post_field( 'post_name', $post_id );
			$new_post_name           = false;
			
			$slug_translation_option = 'title_translate';
			if(property_exists(EWT(), 'options') && isset(EWT()->options['ai_translation_configuration']['slug_translation_option'])){
				$slug_translation_option = EWT()->options['ai_translation_configuration']['slug_translation_option'];
			}

			$elementor_data = isset( $_POST['elementor_data'] ) ? sanitize_text_field( wp_unslash( $_POST['elementor_data'] ) ) : '';

			if ( '' === $current_slug ) {
				if ( isset( $_POST['post_name'] ) && '' !== $_POST['post_name'] && $slug_translation_option === 'slug_translate' ) {
					$new_post_name = sanitize_title( $_POST['post_name'] );
				} elseif ( $slug_translation_option === 'slug_keep' ) {
					$new_post_name = sanitize_text_field( get_post_field( 'post_name', $parent_post_id ) );
				}
			}

			// Check if the current post has Elementor data
			if ( $elementor_data && '' !== $elementor_data ) {
				if ( class_exists( 'Elementor\Plugin' ) ) {
					$plugin   = \Elementor\Plugin::$instance;
					$document = $plugin->documents->get( $post_id );

					$elementor_data = json_decode( wp_unslash( $_POST['elementor_data'] ), true );

					if ( json_last_error() !== JSON_ERROR_NONE ) {
						wp_send_json_error( __( 'Invalid Elementor data.', 'easy-wp-translator' ), 400 );
						wp_die( '0', 400 );
					}

					$document->save(
						array(
							'elements' => $elementor_data,
						)
					);

					$plugin->files_manager->clear_cache();
					update_post_meta( $post_id, '_ewt_elementor_translated', 'true' );
				}
			}

			if ( $new_post_name && '' !== $new_post_name ) {
				wp_update_post(
					array(
						'ID'        => $post_id,
						'post_name' => $new_post_name,
					)
				);
			}

			wp_send_json_success( 'Elementor data updated.' );
			exit;
		}
	}
}
