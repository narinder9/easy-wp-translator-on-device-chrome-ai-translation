<?php
namespace EasyWPTranslator\Supported_Blocks;

use EasyWPTranslator\Modules\Page_Translation\EWT_Page_Translation_Helper;

use WP_Block_Type_Registry;

/**
 * Do not access the page directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Supported_Blocks' ) ) {
	/**
	 * Class Supported_Blocks
	 *
	 * This class handles the supported blocks for the EasyWPTranslator - AI Translation For Polylang plugin.
	 *
	 * @package EWTP
	 */
	class Supported_Blocks {
		/**
		 * Singleton instance.
		 *
		 * @var Supported_Blocks
		 */
		private static $instance = null;


		/**
		 * Stores custom block data for processing and retrieval.
		 *
		 * @var array
		 */
		private $custom_block_data_array = array();

		/**
		 * EWTP plugin category.
		 *
		 * @var array
		 */
		private $ewt_plugin_category = array();

		/**
		 * Get the singleton instance of the class.
		 *
		 * @return Supported_Blocks
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public static function enqueue_editor_assets() {
			wp_enqueue_script( 'ewt-datatable-script', plugins_url( 'admin/assets/js/dataTables.min.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION, true );
			wp_enqueue_script( 'ewt-datatable-style', plugins_url( 'admin/assets/js/dataTables.min.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION, true );
			wp_enqueue_style( 'ewt-custom-data-table', plugins_url( 'admin/assets/css/ewt-custom-data-table.min.css', EASY_WP_TRANSLATOR_ROOT_FILE ), array(), EASY_WP_TRANSLATOR_VERSION );
			wp_enqueue_script( 'ewt-custom-data-table', plugins_url( 'admin/assets/js/ewt-custom-data-table.min.js', EASY_WP_TRANSLATOR_ROOT_FILE ), array('ewt-datatable-script'), EASY_WP_TRANSLATOR_VERSION, true );
		}

		/**
		 * Render the support blocks page.
		 */
		public function ewt_render_support_blocks_page() {
			?>
		<div class="ewt-custom-data-table-wrapper">
			<h3><?php echo __('Supported Blocks Translation Settings', 'easy-wp-translator'); ?>
			<br>
			<p><?php echo sprintf(esc_html__('Manage Gutenberg blocks to make them translation-ready with %s.', 'easy-wp-translator'), 'EasyWPTranslator'); ?></p>
			</h3>
			<div class="ewt-custom-data-table-filters">
				<div class="ewt-filter-tab" data-column="1" data-default="all">
					<label for="ewt-blocks-category"><?php esc_html_e( 'Block Type Category:', 'easy-wp-translator' ); ?></label>
					<select id="ewt-blocks-category" name="ewt_blocks_category">
						<option value="all"><?php esc_html_e( 'All', 'easy-wp-translator' ); ?></option>
						<option value="core">Core</option>
						<?php $this->ewt_get_blocks_category(); ?>
					</select>
				</div>
				<div class="ewt-filter-tab" data-column="3" data-default="all">
					<label for="ewt-blocks-filter"><?php esc_html_e( 'Show Blocks:', 'easy-wp-translator' ); ?></label>
					<select id="ewt-blocks-filter" name="ewt_blocks_filter">
						<option value="all"><?php esc_html_e( 'All', 'easy-wp-translator' ); ?></option>
						<option value="supported"><?php esc_html_e( 'Supported Blocks', 'easy-wp-translator' ); ?></option>
						<option value="unsupported"><?php esc_html_e( 'Unsupported Blocks', 'easy-wp-translator' ); ?></option>
					</select>
				</div>
			</div>
			<div class="ewt-custom-table-section">
				<div class="ewt-custom-table-lists">
					<table class="ewt-custom-data-table-table" id="ewt-custom-datatable">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Sr.No', 'easy-wp-translator' ); ?></th>
								<th><?php esc_html_e( 'Block Name', 'easy-wp-translator' ); ?></th>
								<th><?php esc_html_e( 'Block Title', 'easy-wp-translator' ); ?></th>
								<th><?php esc_html_e( 'Status', 'easy-wp-translator' ); ?></th>
								<th><?php esc_html_e( 'Modify', 'easy-wp-translator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php 
								$this->ewt_get_supported_blocks_table()
							?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
			<?php
		}

		/**
		 * Get the blocks category.
		 */
		public function ewt_get_blocks_category() {
			$blocks_data                 = WP_Block_Type_Registry::get_instance()->get_all_registered();

			$filter_blocks_data = array_filter( $blocks_data, function( $block ) {
				return !in_array($block->category, array( 'media', 'reusable' ));
			} );
			foreach ( $filter_blocks_data as $block ) {
				$plugin_name = explode('/', $block->name);
				$plugin_name = isset($plugin_name[0]) ? $plugin_name[0] : '';

				if(!empty($plugin_name)){
					$filter_plugin_name = $this->ewt_supported_block_name($plugin_name);
					$filter_plugin_name=str_replace('-',' ',$filter_plugin_name);
					$filter_plugin_name=ucwords($filter_plugin_name);

					if(in_array($plugin_name, $this->ewt_plugin_category) || $plugin_name === 'core'){
						continue;
					}

					$this->ewt_plugin_category[] = $plugin_name;
					echo '<option value="' . esc_attr( $plugin_name ) . '">' . esc_html( $filter_plugin_name ) . '</option>';
				}
			}
		}

		/**
		 * Get the supported blocks.
		 */
		public function ewt_get_supported_blocks_table() {
			if ( class_exists( WP_Block_Type_Registry::class ) && method_exists( WP_Block_Type_Registry::class, 'get_all_registered' ) ) {
				$ewt_block_parse_rules       = $this->block_parsing_rules();

				$blocks_data                 = WP_Block_Type_Registry::get_instance()->get_all_registered();

				$ewt_supported_blocks       = isset($ewt_block_parse_rules['EWTBlockParseRules']) ? $ewt_block_parse_rules['EWTBlockParseRules'] : array();
				$ewt_supported_blocks_names = array_keys( $ewt_supported_blocks );
				$s_no                        = 1;
				$ewt_post_id                = self::get_custom_block_post_id();

				$filter_blocks_data=$blocks_data;

				foreach ( $filter_blocks_data as $block ) {

					$block_name  = esc_html( $block->name );
					$block_title = esc_html( $block->title );

					$status      = ! in_array( $block_name, $ewt_supported_blocks_names ) ? 'Unsupported' : 'Supported'; // You can modify this logic based on your requirements
					$modify_text = ! in_array( $block_name, $ewt_supported_blocks_names ) ? esc_html__( 'Add', 'easy-wp-translator' ) : esc_html__( 'Edit', 'easy-wp-translator' );
					$modify_link = '<a href="' . esc_url( admin_url( 'post.php?post=' . esc_attr( $ewt_post_id ) . '&action=edit&ewt_new_block=' ) . esc_attr( $block_name ) ) . '">' . $modify_text . '</a>'; // Modify link
					$modify_link = '<a href="' . esc_url( admin_url( 'post.php?post=' . esc_attr( $ewt_post_id ) . '&action=edit&ewt_new_block=' ) . esc_attr( $block_name ) ) . '">' . $modify_text . '</a>'; // Modify link

					echo '<tr data-block-name="' . esc_attr( strtolower( $block_name ) ) . '" data-block-status="' . esc_attr( strtolower( $status ) ) . '" >';
					echo '<td>' . esc_html($s_no++) . '</td>';
					echo '<td>' . esc_html($block_name) . '</td>';
					echo '<td>' . esc_html($block_title) . '</td>';
					echo '<td>' . esc_html($status) . '</td>';
					echo '<td>' . wp_kses($modify_link, array('a' => array('href' => array(), 'target' => array(), 'rel' => array()))) . '</td>';
					echo '</tr>';
				}
			}

		}

		private function ewt_supported_block_name($block_name){
			$predfined_blocks = array(
				'ub' => 'Ultimate Blocks',
				'uagb' => 'Spectra',
				'themeisle-blocks' => 'Otter Blocks'
			);
			
			if(array_key_exists($block_name, $predfined_blocks)){
				return $predfined_blocks[$block_name];
			}

			return $block_name;
		}

		private static function get_custom_block_post_id()
		{
			$first_post_id = null;

			$query = new \WP_Query(
				array(
					'post_type'      => 'ewt_add_blocks',
					'posts_per_page' => 1,
					'orderby'        => 'date',
					'order'          => 'ASC',
				)
			);

			$existing_post = $query->posts ? $query->posts[0] : null;

			if (! $existing_post) {
				$post_title    = esc_html__('Add More Gutenberg Blocks', 'easy-wp-translator');
				$first_post_id = wp_insert_post(
					array(
						'post_title'   => $post_title,
						'post_content' => '',
						'post_status'  => 'publish',
						'post_type'    => 'ewt_add_blocks',
					)
				);
			} elseif ($query->have_posts()) {
				$query->the_post();
				$first_post_id = get_the_ID();
			}

			return $first_post_id;
		}

		/**
		 * Block Parsing Rules
		 *
		 * Handles the block parsing rules AJAX request.
		 */
		public function block_parsing_rules() {
			$this->custom_block_data_array = array();
			$block_parse_rules = $this->get_block_parse_rules();
			
			return $block_parse_rules;
		}

		public function get_block_parse_rules() {
			$path_url = plugins_url( '/modules/page-translation/block-translation-rules/block-rules.json', EASY_WP_TRANSLATOR_ROOT_FILE );
			$response = wp_remote_get(
				esc_url_raw( $path_url ),
				array(
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
				global $wp_filesystem;

				// Initialize the WordPress filesystem
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				WP_Filesystem();

				$local_path = esc_url_raw( $path_url );
				if ( $wp_filesystem->exists( $local_path ) && $wp_filesystem->is_readable( $local_path ) ) {
					$block_rules = $wp_filesystem->get_contents( $local_path );
				} else {
					$block_rules = array();
				}
			} else {
				$block_rules = wp_remote_retrieve_body( $response );
			}

			if ( empty( $block_rules ) ) {
				return array();
			}

			$block_translation_rules = json_decode( $block_rules, true );

			$this->custom_block_data_array = isset( $block_translation_rules['EWTBlockParseRules'] ) ? $block_translation_rules['EWTBlockParseRules'] : null;

			$custom_block_translation = get_option( 'ewt_custom_block_translation', false );

			if ( ! empty( $custom_block_translation ) && is_array( $custom_block_translation ) ) {
				foreach ( $custom_block_translation as $key => $block_data ) {
					$block_rules = isset( $block_translation_rules['EWTBlockParseRules'][ $key ] ) ? $block_translation_rules['EWTBlockParseRules'][ $key ] : null;
					$this->filter_custom_block_rules( array( $key ), $block_data, $block_rules );
				}
			}

			$block_translation_rules['EWTBlockParseRules'] = $this->custom_block_data_array ? $this->custom_block_data_array : array();

			return $block_translation_rules;
		}

		private function filter_custom_block_rules( array $id_keys, $value, $block_rules, $attr_key = false ) {
			$block_rules = is_object( $block_rules ) ? json_decode( json_encode( $block_rules ) ) : $block_rules;

			if ( ! isset( $block_rules ) ) {
				return $this->merge_nested_attribute( $id_keys, $value );
			}
			if ( is_object( $value ) && isset( $block_rules ) ) {
				foreach ( $value as $key => $item ) {
					if ( isset( $block_rules[ $key ] ) && is_object( $item ) ) {
						$this->filter_custom_block_rules( array_merge( $id_keys, array( $key ) ), $item, $block_rules[ $key ], false );
						continue;
					} elseif ( ! isset( $block_rules[ $key ] ) && true === $item ) {
						$this->merge_nested_attribute( array_merge( $id_keys, array( $key ) ), true );
						continue;
					} elseif ( ! isset( $block_rules[ $key ] ) && is_object( $item ) ) {
						$this->merge_nested_attribute( array_merge( $id_keys, array( $key ) ), $item );
						continue;
					}
				}
			}
		}

		private function merge_nested_attribute( array $id_keys, $value ) {
			$value = is_object( $value ) ? json_decode( json_encode( $value ), true ) : $value;

			$current_array = &$this->custom_block_data_array;

			foreach ( $id_keys as $index => $id ) {
				if ( ! isset( $current_array[ $id ] ) ) {
					$current_array[ $id ] = array();
				}
				$current_array = &$current_array[ $id ];
			}

			$current_array = $value;
		}

		/**
		 * Update the custom blocks content.
		 *
		 * @param array $updated_blocks_data The updated blocks data.
		 */
		public function update_custom_blocks_content($updated_blocks_data){

			$this->custom_block_data_array = array();

			if ( $updated_blocks_data ) {
				$block_parse_rules = $this->block_parsing_rules();

				if ( isset( $block_parse_rules['EWTBlockParseRules'] ) ) {
					$previous_translate_data = get_option( 'ewt_custom_block_translation', false );
					if ( $previous_translate_data && ! empty( $previous_translate_data ) ) {
						$this->custom_block_data_array = $previous_translate_data;
					}

					foreach ( $updated_blocks_data as $key => $block_data ) {
						$this->filter_custom_block_rules( array( $key ), $block_data, $block_parse_rules['EWTBlockParseRules'][ $key ] );
					}

					if ( count( $this->custom_block_data_array ) > 0 ) {
						update_option( 'ewt_custom_block_translation', $this->custom_block_data_array );
					}

					delete_option( 'ewt_custom_block_data' );
				}
			}
		}
	}
}
