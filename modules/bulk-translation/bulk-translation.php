<?php
namespace EasyWPTranslator\Modules\Bulk_Translation;

use EasyWPTranslator\Admin\Controllers\EWT_Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'EWT_Bulk_Translation' ) ) :
	class EWT_Bulk_Translation {

		private static $instance;

		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
		
		public function __construct() {
			global $easywptranslator;
			
			if ( $easywptranslator instanceof EWT_Admin ) {
				add_action( 'current_screen', array( $this, 'bulk_translate_btn' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_bulk_translate_assets' ) );
			}
			
		}

		public function bulk_translate_btn( $current_screen ) {
			global $easywptranslator;

			if ( ! $easywptranslator || ! property_exists( $easywptranslator, 'model' ) ) {
				return;
			}

			$translated_post_types = $easywptranslator->model->get_translated_post_types();
			$translated_taxonomies = $easywptranslator->model->get_translated_taxonomies();

			$translated_post_types = array_keys($translated_post_types);
			$translated_taxonomies = array_keys($translated_taxonomies);

			$translated_post_types=array_filter($translated_post_types, function($post_type){
				return is_string($post_type);
			});
		
			$translated_taxonomies=array_filter($translated_taxonomies, function($taxonomy){
				return is_string($taxonomy);
			});

			$valid_post_type=(isset($current_screen->post_type) && !empty($current_screen->post_type)) && in_array($current_screen->post_type, $translated_post_types) && $current_screen->post_type !== 'attachment' ? $current_screen->post_type : false;
			$valid_taxonomy=(isset($current_screen->taxonomy) && !empty($current_screen->taxonomy)) && in_array($current_screen->taxonomy, $translated_taxonomies) ? $current_screen->taxonomy : false;
			
			if((!$valid_post_type && !$valid_taxonomy) || ((!$valid_post_type || empty($valid_post_type)) && !isset($valid_taxonomy)) || (isset($current_screen->taxonomy) && !empty($current_screen->taxonomy) && !$valid_taxonomy)){
				return;
			}

			$post_status=isset($_GET['post_status']) ? sanitize_text_field(wp_unslash($_GET['post_status'])) : '';
            
            if('trash' === $post_status){
                return;
            }

			add_filter( "views_{$current_screen->id}", array( $this, 'ewt_bulk_translate_button' ) );

			add_action( 'admin_footer', array( $this, 'bulk_translate_container' ) );
		}

		public function ewt_bulk_translate_button( $views ) {
			$providers_config_class=' providers-config-no-active';

			if(property_exists(EWT(), 'options') && isset(EWT()->options['ai_translation_configuration']['provider'])){
				$providers = EWT()->options['ai_translation_configuration']['provider'];

				foreach($providers as $provider => $value){
					if($value){
						$providers_config_class = '';
						break;
					}
				}
			}

			echo "<button class='button ewt-bulk-translate-btn".esc_attr($providers_config_class)."' style='display:none;'>Bulk Translate</button>";

			return $views;
		}

		public function bulk_translate_container() {
			echo "<div id='ewt-bulk-translate-wrapper'></div>";
		}

		public function enqueue_bulk_translate_assets() {
			global $easywptranslator;
        
        if(!$easywptranslator || !property_exists($easywptranslator, 'model')){
            return;
        }
        
        $current_screen = function_exists('get_current_screen') ? get_current_screen() : false;

		if(!$current_screen){
			return;
		}

		$translated_post_types = $easywptranslator->model->get_translated_post_types();
		$translated_taxonomies = $easywptranslator->model->get_translated_taxonomies();

		$translated_post_types = array_keys($translated_post_types);
		$translated_taxonomies = array_keys($translated_taxonomies);

		$translated_post_types=array_filter($translated_post_types, function($post_type){
			return is_string($post_type);
		});
		
		$translated_taxonomies=array_filter($translated_taxonomies, function($taxonomy){
			return is_string($taxonomy);
		});

		$valid_post_type=(isset($current_screen->post_type) && !empty($current_screen->post_type)) && in_array($current_screen->post_type, $translated_post_types) && $current_screen->post_type !== 'attachment' ? $current_screen->post_type : false;
		$valid_taxonomy=(isset($current_screen->taxonomy) && !empty($current_screen->taxonomy)) && in_array($current_screen->taxonomy, $translated_taxonomies) ? $current_screen->taxonomy : false;
				
		if((!$valid_post_type && !$valid_taxonomy) || ((!$valid_post_type || empty($valid_post_type)) && !isset($valid_taxonomy)) || (isset($current_screen->taxonomy) && !empty($current_screen->taxonomy) && !$valid_taxonomy)){
			return;
		}

        $post_status=isset($_GET['post_status']) ? sanitize_text_field(wp_unslash($_GET['post_status'])) : '';

        if('trash' === $post_status){
            return;
        }

        $post_label=__("Pages", "autopoly-ai-translation-for-easywptranslator-pro");
        $taxonomy_page=false;

        if(isset($current_screen->post_type)){
            $post_type = $current_screen->post_type;

            if(isset(get_post_type_object($post_type)->label) && !empty(get_post_type_object($post_type)->label)){
                $post_label = get_post_type_object($post_type)->label;
            }

            if(isset($current_screen->taxonomy) && !empty($current_screen->taxonomy)){
                $taxonomy_page=$current_screen->taxonomy;    
                $taxonomy_object = get_taxonomy($current_screen->taxonomy);

                if(isset($taxonomy_object->label) && !empty($taxonomy_object->label)){
                    $post_label = $taxonomy_object->label;

                    if(isset($taxonomy_object->labels->singular_name) && !empty($taxonomy_object->labels->singular_name)){
                        $post_label = $taxonomy_object->labels->singular_name;
                    }
                }
            }
        }

        $editor_script_asset = include EASY_WP_TRANSLATOR_DIR . '/admin/assets/bulk-translate/index.asset.php';

		if ( ! is_array( $editor_script_asset ) ) {
			$editor_script_asset = array(
				'dependencies' => array(),
				'version'      => EASY_WP_TRANSLATOR_VERSION,
			);
		}
                
        $rtl=function_exists('is_rtl') ? is_rtl() : false;
        $css_file=$rtl ? 'index-rtl.css' : 'index.css';
      
		wp_enqueue_script( 'ewt-bulk-translate', plugins_url( 'admin/assets/bulk-translate/index.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array_merge( $editor_script_asset['dependencies'] ), $editor_script_asset['version'], true );
   
		wp_enqueue_style( 'ewt-bulk-translate', plugins_url( 'admin/assets/bulk-translate/index.css', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), $editor_script_asset['version'] );

        $languages = EWT()->model->get_languages_list();

        $lang_object = array();

		$default_language=EWT()->model->get_default_language();
		$default_language_slug=false;

		if(isset($default_language->slug) && !empty($default_language->slug)){
			$default_language_slug=$default_language->slug;
		}

        foreach ($languages as $lang) {
			$lang_object[$lang->slug] = array('name' => $lang->name, 'flag' => $lang->flag_url, 'locale' => $lang->locale);
        }

		$providers=array();

		if(property_exists(EWT(), 'options') && isset(EWT()->options['ai_translation_configuration']['provider'])){
			$providers = EWT()->options['ai_translation_configuration']['provider'];
		}

		$active_providers=array();

		foreach($providers as $provider => $value){
			if($value){
				$provdername = $provider==='chrome_local_ai' ? 'localAiTranslator' : $provider;
				$active_providers[] = $provdername;
			}
		}

		$slug_translation_option = 'title_translate';

		if(property_exists(EWT(), 'options') && isset(EWT()->options['ai_translation_configuration']['slug_translation_option'])){
			$slug_translation_option = EWT()->options['ai_translation_configuration']['slug_translation_option'];
		}

		$extra_data = array();

        if(!$taxonomy_page || empty($taxonomy_page)){
            if (!isset(EWT()->options['sync']) || (isset(EWT()->options['sync']) && !in_array('post_meta', EWT()->options['sync']))) {
                $extra_data['postMetaSync'] = 'false';
            } else {
                $extra_data['postMetaSync'] = 'true';
            }
        }

        wp_localize_script(
            'ewt-bulk-translate',
            'ewtBulkTranslationGlobal',
            array_merge(array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'languageObject' => $lang_object,
                'nonce' => wp_create_nonce('wp_rest'),
                'bulkTranslateRouteUrl' =>  get_rest_url( null, 'ewt/v1/bulk-translate' ),
                'bulkTranslatePrivateKey' => wp_create_nonce('ewt_bulk_translate_entries_nonce'),
                'ewt_url'                => plugins_url( '', EASY_WP_TRANSLATOR_ROOT_FILE ) . '/',
                'admin_url' => admin_url(),
                'post_label' => $post_label,
                'update_translate_data' => 'ewt_update_translate_data',
                'slug_translation_option' => $slug_translation_option,
                'taxonomy_page' => $taxonomy_page,
				'providers'                => $active_providers,
				'default_language_slug' => $default_language_slug,
            ), $extra_data)
        );
		}
	}
endif;
