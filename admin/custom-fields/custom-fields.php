<?php

namespace EasyWPTranslator\Custom_Fields;

if(!defined('ABSPATH')) exit;

if(!class_exists('Custom_Fields')) {

    class Custom_Fields {
        private static $instance = null;
        private $ewt_saved_fields = array();
        private $ewt_allowed_fields = array();
    
        public static function get_instance() {
            if(null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct() {
            add_action('wp_ajax_ewt_update_custom_fields_content', array($this, 'update_custom_fields_content'));
        }
    
        public static function enqueue_editor_assets() {
            wp_enqueue_script( 'ewt-datatable-script', plugins_url( 'admin/assets/js/dataTables.min.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION, true );
			wp_enqueue_script( 'ewt-datatable-style', plugins_url( 'admin/assets/js/dataTables.min.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION, true );
			wp_enqueue_style( 'ewt-editor-custom-fields', plugins_url( 'admin/assets/css/ewt-custom-data-table.min.css', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION );
			wp_enqueue_script( 'ewt-editor-custom-fields', plugins_url( 'admin/assets/js/ewt-custom-data-table.min.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array('ewt-datatable-script'), EASY_WP_TRANSLATOR_VERSION, true );
        
            wp_localize_script( 'ewt-editor-custom-fields', 'ewtCustomTableDataObject', array(
                'admin_url' => esc_url(admin_url('admin-ajax.php')),
                'save_button_handler' => 'ewt_update_custom_fields_content',
                'save_button_nonce' => wp_create_nonce('ewt_save_custom_fields'),
                'save_button_enabled'=>true,
                'save_button_text'=>__('Save Fields', 'easy-wp-translator'),
                'save_button_class'=>'ewt-save-custom-fields',
            ) );
        }
    
        public function ewt_render_custom_fields_page() {
                $this->ewt_allowed_fields = self::get_allowed_custom_fields();
                $s_no                        = 1;
                ?>
                <div class="ewt-custom-data-table-wrapper ewt-custom-fields">
                    <h3><?php echo __('Custom Fields Translation Settings', 'easy-wp-translator'); ?>
                    <br>
                    <p><?php echo sprintf(esc_html__('Select which custom fields will be translated by %s.', 'easy-wp-translator'), 'EasyWPTranslator'); ?></p>
                    </h3>
                    <button class="button button-primary ewt-save-custom-fields"><?php esc_html_e( 'Save Fields', 'easy-wp-translator' ); ?></button>
                    <div class="ewt-custom-data-table-filters">
                        <div class="ewt-filter-tab" data-column="3" data-default="all">
                            <label for="ewt-fields-filter"><?php esc_html_e( 'Show Fields:', 'easy-wp-translator' ); ?></label>
                            <select id="ewt-fields-filter" name="ewt_fields_filter">
                                <option value="all"><?php esc_html_e( 'All', 'easy-wp-translator' ); ?></option>
                                <option value="supported"><?php esc_html_e( 'Translatable', 'easy-wp-translator' ); ?></option>
                                <option value="unsupported"><?php esc_html_e( 'Non-Translatable', 'easy-wp-translator' ); ?></option>
                            </select>
                        </div>
                        <div class="ewt-filter-tab" data-column="2" data-default="all">
                            <label for="ewt-fields-filter"><?php esc_html_e( 'Type:', 'easy-wp-translator' ); ?></label>
                            <select id="ewt-fields-value-type-filter" name="ewt_fields_value_type_filter">
                                <option value="all"><?php esc_html_e( 'All', 'easy-wp-translator' ); ?></option>
                                <option value="string"><?php esc_html_e( 'String', 'easy-wp-translator' ); ?></option>
                                <option value="array"><?php esc_html_e( 'Array', 'easy-wp-translator' ); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="ewt-custom-table-section">
                        <div class="ewt-custom-table-lists">
                            <table class="ewt-custom-data-table-table" id="ewt-custom-datatable">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Sr.No', 'easy-wp-translator' ); ?></th>
                                        <th><?php esc_html_e( 'Field Name', 'easy-wp-translator' ); ?></th>
                                        <th><?php esc_html_e( 'Type', 'easy-wp-translator' ); ?></th>
                                        <th><?php esc_html_e( 'Status', 'easy-wp-translator' ); ?></th>
                                        <th align="center"><?php esc_html_e( 'Translate', 'easy-wp-translator' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $this->get_all_meta_fields_table();
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php
        }

        public function get_all_meta_fields_table() {
            $meta_fields=self::get_custom_fields_data();
            if($meta_fields && is_array($meta_fields)) {
                $s_no                        = 1;
                foreach($meta_fields as $meta_field => $value) { 
                    $checked=isset($this->ewt_allowed_fields[$meta_field]) && !empty($this->ewt_allowed_fields[$meta_field]['status']) ? 'checked' : '';
                    $status=isset($value['status']) && !empty($value['status']) ? $value['status'] : 'Unsupported';
                    $value_type=isset($value['type']) && !empty($value['type']) ? $value['type'] : 'string';
                    
                    echo '<tr>';
                    echo '<td>' . $s_no++ . '</td>';
                    echo '<td>' . $meta_field . '</td>';
                    echo '<td>' . $value_type . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '<td align="center"><input type="checkbox" name="ewt_fields_status" value="' . $meta_field . '" ' . $checked . '></td>';
                    echo '</tr>';
                }
            }
        }

        public function update_custom_fields_content(){
            if ( ! check_ajax_referer( 'ewt_save_custom_fields', 'ewt_nonce', false ) ) {
                wp_send_json_error( __( 'Invalid security token sent.', 'easy-wp-translator' ) );
                wp_die( '0', 400 );
            }

            if(!current_user_can('edit_posts')){
                wp_send_json_error( __( 'Unauthorized', 'easy-wp-translator' ), 403 );
                wp_die( '0', 403 );
            }
            
            $json = isset($_POST['save_custom_fields_data']) ? wp_unslash($_POST['save_custom_fields_data']) : false;
            $updated_custom_fields_data = json_decode($json, true);

			$updated_custom_fields_data=array_map('sanitize_text_field', $updated_custom_fields_data);
			$existing_fields=get_option('ewt_allowed_custom_fields', false);

			if(json_last_error() !== JSON_ERROR_NONE){ 
                wp_send_json_error( __( 'Invalid JSON', 'easy-wp-translator' ) );
                wp_die( '0', 400 );
            }
			
			$allowed_fields=self::get_custom_fields_data();

			if(!$allowed_fields || !is_array($allowed_fields)){
				wp_send_json_error( __( 'Invalid allowed fields', 'easy-wp-translator' ) );
				wp_die( '0', 400 );
			}

			$allowed_fields_values=array_keys($allowed_fields);

			$valid_fields=array_intersect($updated_custom_fields_data, $allowed_fields_values);
			
			$sanitize_fields=array();
			$old_fields=array();

			foreach($valid_fields as $field){
				if(!isset($existing_fields[$field]) || $existing_fields[$field]['status'] !== true || $existing_fields[$field]['type'] !== $allowed_fields[$field]['type']){
					$sanitize_fields[sanitize_text_field($field)]=['status'=>true, 'type'=>sanitize_text_field($allowed_fields[$field]['type'])];
				}else{
					$old_fields[$field]=$existing_fields[$field];
				}
			}

			$unset_fields=array_diff(array_keys($existing_fields), $updated_custom_fields_data );

			foreach($unset_fields as $field){
				if(isset($existing_fields[$field]) && $existing_fields[$field]['status'] === true){
					$sanitize_fields[$field]=$existing_fields[$field];
					$sanitize_fields[$field]['status']=false;
				}
			}

			if(count($sanitize_fields) < 1){
				wp_send_json_success(array( 'message' => __( 'No changes detected. All selected custom fields are already up to date.', 'easy-wp-translator' ) ));
				exit;
			}

			update_option('ewt_allowed_custom_fields', array_merge($old_fields, $sanitize_fields));

			$save_settings=get_option('ewt_allowed_custom_fields', false);

			if ( ! $save_settings || ! is_array( $save_settings ) || count( $save_settings ) < 1 ) {
				wp_send_json_success( array( 'message' => __( 'No custom fields selected. Autopoly cannot translate any fields.', 'easy-wp-translator' ) ) );
				exit;
			}

            wp_send_json_success( array(
                'message' => __( 'Custom fields translation settings have been updated successfully. Your selected fields will now be translated automatically.', 'easy-wp-translator' ),
                'updated_fields' => $sanitize_fields
            ) );

			exit;
        }

        public static function get_custom_fields_data(){
			$result=self::get_custom_fields_query();

			$data=array();

			if($result && is_array($result)){
				$excluded_fields=self::get_excluded_custom_fields_keys();
				$allowed_fields=self::get_allowed_custom_fields();

				foreach($result as $result){
					if(in_array($result['meta_key'], $excluded_fields)){
						continue;
					}

					$serialized_value=maybe_unserialize($result['meta_value']);
					$value_type=json_decode($result['meta_value'], true) ? 'array' : (is_array($serialized_value) ? 'array' : 'string');
					
					$type=isset($allowed_fields[$result['meta_key']]) && true === $allowed_fields[$result['meta_key']]['status'] ? $allowed_fields[$result['meta_key']]['type'] : $value_type;
					
					$status=isset($allowed_fields[$result['meta_key']]) && true === $allowed_fields[$result['meta_key']]['status'] ? 'Supported' : 'Unsupported';

					$data[sanitize_text_field($result['meta_key'])]=['type'=>$type, 'status'=>$status];
				}
			}

			$default_allowed_fields=self::get_default_allowed_fields();

			$default_key_diff=array_diff(array_keys($default_allowed_fields), array_keys($data));

			foreach($default_key_diff as $key){
				$status='Supported';
				$saved_allowed_fields=get_option('ewt_allowed_custom_fields', false);
				$status=isset($saved_allowed_fields[$key]) && true === $saved_allowed_fields[$key]['status'] ? 'Supported' : 'Unsupported';

				$data[$key]=['type'=>$default_allowed_fields[$key]['type'], 'status'=>$status];
			}

			$data=apply_filters('ewt/custom_fields/all_fields', $data);

			return $data;
		}

		private static function get_custom_fields_query(){
			global $wpdb;

             // Escape LIKE pattern for system meta (_%)
			 $like_pattern = $wpdb->esc_like('_') . '%';

			// SQL with DISTINCT + filtering
			$sql = $wpdb->prepare(
				"
				SELECT DISTINCT pm.meta_key, pm.meta_value
				FROM {$wpdb->postmeta} pm
				WHERE pm.meta_key NOT LIKE %s
				AND pm.meta_value <> ''                         -- skip empty
				AND pm.meta_value NOT IN ('0','1')              -- skip boolean
				AND pm.meta_value NOT REGEXP '^[0-9]+$'         -- skip integer
				AND pm.meta_value NOT REGEXP '^[0-9]+\\.[0-9]+$' -- skip decimal
				AND pm.meta_value NOT REGEXP '^(https?:\/\/|www\.)[A-Za-z0-9\.\-]+.*$' -- skip URLs
				ORDER BY pm.meta_key ASC
				",
				$like_pattern
			);

            // Get results
            $results = $wpdb->get_results($sql, ARRAY_A);

			return $results;
		}

		private static function get_excluded_custom_fields_keys(){
			$excluded_fields= array(
                '_edit_last',
                '_edit_lock',
                '_wp_page_template',
                '_wp_attachment_metadata',
                '_icl_translator_note',
                '_alp_processed',
                '_pingme',
                '_encloseme',
                '_icl_lang_duplicate_of',
                'atfpp_parent_post_language',
                'atfp_parent_post_language_slug',
                'atfpp_parent_post_language_slug',
                'ewt_parent_post_language',
                'ewt_parent_post_language_slug',
                'twae_exists',
                'twae_post_migration',
                'twae_style_migration',
                '_thumbnail_id',
            );

            return apply_filters('ewt/custom_fields/excluded_keys', $excluded_fields);
		}

		public static function get_allowed_custom_fields(){
			$allowed_custom_fields=self::get_allowed_custom_fields_data();
			$allowed_custom_fields=apply_filters('ewt/custom_fields/allowed_fields', $allowed_custom_fields);
		
			return $allowed_custom_fields;
		}

		private static function get_allowed_custom_fields_data(){			
			$allowed_fields=get_option('ewt_allowed_custom_fields', false);

            if(!$allowed_fields){
                $allowed_fields=array();
            }

			if($allowed_fields && is_array($allowed_fields) && count($allowed_fields) > 0){
				return $allowed_fields;
			}

			if(!$allowed_fields || !is_array($allowed_fields)){
				$default_allowed_fields=self::get_default_allowed_fields();

				foreach($default_allowed_fields as $key => $value){
					$allowed_fields[$key]=['status'=>true, 'type'=>'string'];
				}

				update_option('ewt_allowed_custom_fields', $allowed_fields);
			}

			ksort($allowed_fields);
			
			return $allowed_fields;
		}

		private static function get_default_allowed_fields(){
			$found=false;

			$response = wp_remote_get( esc_url_raw( EASY_WP_TRANSLATOR_URL . 'modules/page-translation/block-translation-rules/default-allow-metafields.json' ), array(
				'timeout' => 15,
			) );

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				global $wp_filesystem;

				// Initialize the WordPress filesystem
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				WP_Filesystem();

				$local_path = EASY_WP_TRANSLATOR_DIR_PATH . 'modules/page-translation/block-translation-rules/default-allow-metafields.json';
				if($wp_filesystem->exists($local_path) && $wp_filesystem->is_readable( $local_path )){
					$found=true;
					$default_allowed_fields = $wp_filesystem->get_contents( $local_path );
				}
			}else{
				$found=true;
				$default_allowed_fields = wp_remote_retrieve_body( $response );
			}

			if(!$found){
				return array();
			}

			return json_decode($default_allowed_fields, true)	;
		}
    }
}