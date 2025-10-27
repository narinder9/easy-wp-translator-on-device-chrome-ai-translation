<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Options\Business;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Options\Abstract_Option;
use EasyWPTranslator\Includes\Options\Options;

/**
 * Class defining post types list option.
 *
 *  
 */
class Ai_Translation_Configuration extends Abstract_Option {
	/**
	 * Returns option key.
	 *
	 *  
	 *
	 * @return string
	 *
	 * @phpstan-return 'post_types'
	 */
	public static function key(): string {
		return 'ai_translation_configuration';
	}

    /**
     * Returns the default value.
     *
     *  
     *
     * @return array
     */
    protected function get_default() {
        $data= array(
            'provider' => array(
                'chrome_local_ai' => true,
            ),
            'bulk_translation_post_status' => 'draft',
            'slug_translation_option' => 'title_translate',
        );

        return $data;
    }

    /**
     * Returns the JSON schema part specific to this option.
     *
     *  
     *
     * @return array Partial schema.
     */
    protected function get_data_structure(): array {
        return array(
            'type' => 'object',
            'properties' => array(
                'provider' => array('type' => 'object', 'properties' => array('chrome_local_ai'=>array('type' => 'boolean'))),
                'bulk_translation_post_status' => array('type' => 'string', 'enum' => array('draft', 'publish')),
                'slug_translation_option' => array('type' => 'string', 'enum' => array('title_translate', 'slug_translate', 'slug_keep')),
            ),
        );
    }

    
	/**
	 * Sanitizes option's value.
	 * Can populate the `$errors` property with blocking and non-blocking errors: in case of non-blocking errors,
	 * the value is sanitized and can be stored.
	 *
	 *  
	 *
	 * @param array   $value   Value to sanitize.
	 * @param Options $options All options.
	 * @return array|WP_Error The sanitized value. An instance of `WP_Error` in case of blocking error.
	 *
	 * @phpstan-return DomainsValue|WP_Error
	 */
	protected function sanitize( $value, Options $options ) {
        $filtered_value = array();
        $data_structure=self::get_data_structure();
        $provider_data=array_keys($data_structure['properties']['provider']['properties']);
        
        if(isset($value['provider'])){
            $filtered_value['provider'] = array();
            foreach($value['provider'] as $key => $provider_value){

                if(in_array($key, $provider_data)){
                    $filtered_value['provider'][$key] = filter_var($provider_value, FILTER_VALIDATE_BOOLEAN);
                }
            }
        }

        if(isset($value['bulk_translation_post_status']) && in_array($value['bulk_translation_post_status'], array('draft', 'publish'))){
            $filtered_value['bulk_translation_post_status'] = sanitize_text_field($value['bulk_translation_post_status']);
        }

        if(isset($value['slug_translation_option']) && in_array($value['slug_translation_option'], array('title_translate', 'slug_translate', 'slug_keep'))){
            $filtered_value['slug_translation_option'] = sanitize_text_field($value['slug_translation_option']);
        }

        return $filtered_value;
    }

	/**
	 * Returns the description used in the JSON schema.
	 *
	 *  
	 *
	 * @return string
	 */
	protected function get_description(): string {
		return __( 'List of post types to translate.', 'easy-wp-translator' );
	}
}
