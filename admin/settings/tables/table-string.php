<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Settings\Tables;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Admin\Controllers\EWT_Admin_Strings;
use EasyWPTranslator\Includes\Helpers\EWT_MO;
use EasyWPTranslator\Includes\Other\EWT_Language;
use EasyWPTranslator\Settings\Controllers\EWT_Settings;
use WP_List_Table;
use WP_Error;


if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php'; // since WP 3.1
}

/**
 * A class to create the strings translations table
 *
 *  
 */
class EWT_Table_String extends WP_List_Table {
	/**
	 * The list of languages.
	 *
	 * @var EWT_Language[]
	 */
	protected $languages;

	/**
	 * Registered strings.
	 *
	 * @var array
	 */
	protected $strings;

	/**
	 * The string groups.
	 *
	 * @var string[]
	 */
	protected $groups;

	/**
	 * The selected string group or -1 if none is selected.
	 *
	 * @var string|int
	 */
	protected $selected_group;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param EWT_Language[] $languages List of languages.
	 */
	public function __construct( $languages ) {
		parent::__construct(
			array(
				'plural' => 'Strings translations', // Do not translate ( used for css class )
				'ajax'   => false,
			)
		);

		$this->languages = $languages;
		$this->strings = EWT_Admin_Strings::get_strings();
		$this->groups = array_unique( wp_list_pluck( $this->strings, 'context' ) );

		$this->selected_group = -1;

		if ( ! empty( $_GET['group'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$group = sanitize_text_field( wp_unslash( $_GET['group'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( in_array( $group, $this->groups ) ) {
				$this->selected_group = $group;
			}
		}

		add_action( 'ewt_action_string-translation', array( $this, 'save_translations' ) );
	}

	/**
	 * Displays the item information in a column (default case).
	 *
	 *  
	 *
	 * @param array  $item        Data related to the current string.
	 * @param string $column_name The current column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		return $item[ $column_name ];
	}

	/**
	 * Displays the checkbox in first column.
	 *
	 *  
	 *
	 * @param array $item Data related to the current string.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<label class="screen-reader-text" for="cb-select-%1$s">%2$s</label><input id="cb-select-%1$s" type="checkbox" name="strings[]" value="%1$s" %3$s />',
			esc_attr( isset( $item['row'] ) ? $item['row'] : '' ),
			/* translators:  accessibility text, %s is a string potentially in any language */
			sprintf( __( 'Select %s', 'easy-wp-translator' ), format_to_edit( $item['string'] ) ),
			empty( $item['icl'] ) ? 'disabled' : '' // Only strings registered with WPML API can be removed.
		);
	}

	/**
	 * Displays the string to translate.
	 *
	 *  
	 *
	 * @param array $item Data related to the current string.
	 * @return string
	 */
	public function column_string( $item ) {
		return format_to_edit( $item['string'] ); // Don't interpret special chars for the string column.
	}

	/**
	 * Displays the translations to edit.
	 *
	 *  
	 *
	 * @param array $item Data related to the current string.
	 * @return string
	 */
	public function column_translations( $item ) {
		$out       = '';
		$languages = array();

		foreach ( $this->languages as $language ) {
			$languages[ $language->slug ] = $language->name;
		}

		// Check if translations exist and is an array before iterating
		if ( isset( $item['translations'] ) && is_array( $item['translations'] ) ) {
			foreach ( $item['translations'] as $key => $translation ) {
				$input_type = $item['multiline'] ?
					'<textarea name="translation[%1$s][%2$s]" id="%1$s-%2$s">%4$s</textarea>' :
					'<input type="text" name="translation[%1$s][%2$s]" id="%1$s-%2$s" value="%4$s" />';
				$out .= sprintf(
					'<div class="translation"><label for="%1$s-%2$s">%3$s</label>' . $input_type . '</div>' . "\n",
					esc_attr( $key ),
					esc_attr( isset( $item['row'] ) ? $item['row'] : '' ),
					esc_html( $languages[ $key ] ),
					format_to_edit( $translation ) // Don't interpret special chars.
				);
			}
		}

		return $out;
	}

	/**
	 * Gets the list of columns.
	 *
	 *  
	 *
	 * @return string[] The list of column titles.
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />', // Checkbox.
			'string'       => esc_html__( 'String', 'easy-wp-translator' ),
			'name'         => esc_html__( 'Name', 'easy-wp-translator' ),
			'context'      => esc_html__( 'Group', 'easy-wp-translator' ),
			'translations' => esc_html__( 'Translations', 'easy-wp-translator' ),
		);
	}

	/**
	 * Gets the list of sortable columns
	 *
	 *  
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'string'  => array( 'string', false ),
			'name'    => array( 'name', false ),
			'context' => array( 'context', false ),
		);
	}

	/**
	 * Gets the name of the default primary column.
	 *
	 *  
	 *
	 * @return string Name of the default primary column, in this case, 'string'.
	 */
	protected function get_default_primary_column_name() {
		return 'string';
	}

	/**
	 * Search for a string in translations. Case insensitive.
	 *
	 *  
	 *
	 * @param EWT_Language[] $languages An array of language objects.
	 * @param string         $s         Searched string.
	 * @return string[] Found strings.
	 */
	protected function search_in_translations( $languages, $s ) {
		$founds = array();

		foreach ( $languages as $language ) {
			$mo = new EWT_MO();
			$mo->import_from_db( $language );
			foreach ( wp_list_pluck( $mo->entries, 'translations' ) as $string => $translation ) {
				if ( false !== stripos( $translation[0], $s ) ) {
					$founds[] = $string;
				}
			}
		}

		return array_unique( $founds );
	}

	/**
	 * Sorts registered string items.
	 *
	 *  
	 *
	 * @param array $a The first item to compare.
	 * @param array $b The second item to compare.
	 * @return int -1 or 1 if $a is considered to be respectively less than or greater than $b.
	 */
	protected function usort_reorder( $a, $b ) {
		if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$orderby = sanitize_key( $_GET['orderby'] ); // phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $a[ $orderby ], $b[ $orderby ] ) ) {
				$result = strcmp( $a[ $orderby ], $b[ $orderby ] ); // Determine sort order
				return ( empty( $_GET['order'] ) || 'asc' === $_GET['order'] ) ? $result : -$result; // phpcs:ignore WordPress.Security.NonceVerification
			}
		}

		return 0;
	}

	/**
	 * Prepares the list of registered strings for display.
	 *
	 *  
	 *
	 * @return void
	 */
	public function prepare_items() {
		// Is admin language filter active?
		if ( $lg = get_user_meta( get_current_user_id(), 'ewt_filter_content', true ) ) {
			$languages = wp_list_filter( $this->languages, array( 'slug' => $lg ) );
		} else {
			$languages = $this->languages;
		}

		$data = $this->strings;

		// Filter by selected group
		if ( -1 !== $this->selected_group ) {
			$data = wp_list_filter( $data, array( 'context' => $this->selected_group ) );
		}

		// Filter by searched string
		$s = empty( $_GET['s'] ) ? '' : sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $s ) ) {
			// Search in translations
			$in_translations = $this->search_in_translations( $languages, $s );

			foreach ( $data as $key => $row ) {
				if ( stripos( $row['name'], $s ) === false && stripos( $row['string'], $s ) === false && ! in_array( $row['string'], $in_translations ) ) {
					unset( $data[ $key ] );
				}
			}
		}

		// Sorting
		uasort( $data, array( $this, 'usort_reorder' ) );

		// Paging
		$per_page = $this->get_items_per_page( 'ewt_strings_per_page' );
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$total_items = count( $data );
		$this->items = array_slice( $data, ( $this->get_pagenum() - 1 ) * $per_page, $per_page, true );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $total_items / $per_page ),
			)
		);

		// Translate strings
		// Kept for the end as it is a slow process
		foreach ( $languages as $language ) {
			$mo = new EWT_MO();
			$mo->import_from_db( $language );
			foreach ( $this->items as $key => $row ) {
				$this->items[ $key ]['translations'][ $language->slug ] = $mo->translate_if_any( $row['string'] );
				$this->items[ $key ]['row']                             = $key; // Store the row number for convenience
			}
		}
	}

	

	/**
	 * Get the current action selected from the bulk actions dropdown.
	 * overrides parent function to avoid submit button to trigger bulk actions
	 *
	 *  
	 *
	 * @return string|false The action name or False if no action was selected
	 */
	public function current_action() {
		return empty( $_POST['submit'] ) ? parent::current_action() : false; // phpcs:ignore WordPress.Security.NonceVerification
	}

	/**
	 * Displays the dropdown list to filter strings per group
	 *
	 *  
	 *
	 * @param string $which only 'top' is supported
	 * @return void
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		echo '<div class="alignleft actions">';
		printf(
			'<label class="screen-reader-text" for="select-group" >%s</label>',
			/* translators: accessibility text */
			esc_html__( 'Filter by group', 'easy-wp-translator' )
		);
		echo '<select id="select-group" name="group">' . "\n";
		printf(
			'<option value="-1"%s>%s</option>' . "\n",
			selected( $this->selected_group, -1, false ),
			esc_html__( 'View all groups', 'easy-wp-translator' )
		);

		foreach ( $this->groups as $group ) {
			printf(
				'<option value="%s"%s>%s</option>' . "\n",
				esc_attr( urlencode( $group ) ),
				selected( $this->selected_group, $group, false ),
				esc_html( $group )
			);
		}
		echo '</select>' . "\n";

		submit_button( __( 'Filter', 'easy-wp-translator' ), 'button', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
		echo '</div>';
	}

	/**
	 * Saves the strings translations in DB
	 * Optionally clean the DB
	 *
	 *  
	 *
	 * @return void
	 */
	public function save_translations() {
		check_admin_referer( 'string-translation', '_wpnonce_string-translation' );

		if ( ! empty( $_POST['submit'] ) ) {
			foreach ( $this->languages as $language ) {
				if ( empty( $_POST['translation'][ $language->slug ] ) || ! is_array( $_POST['translation'][ $language->slug ] ) ) { // In case the language filter is active
					continue;
				}
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below with sanitize_textarea_field
				$translations = array_map( 'sanitize_textarea_field', array_map( 'trim', (array) wp_unslash( $_POST['translation'][ $language->slug ] ) ) );

				$mo = new EWT_MO();
				$mo->import_from_db( $language );

				foreach ( $translations as $key => $translation ) {
					/**
					 * Filters the string translation before it is saved in DB.
					 * Allows to sanitize strings registered with ewt_register_string().
					 *
					 *  
					 *   The translation passed to the filter is unslashed.
					 *   Add original string as 4th parameter.
					 *
					 * @param string $translation The string translation.
					 * @param string $name        The name as defined in ewt_register_string.
					 * @param string $context     The context as defined in ewt_register_string.
					 * @param string $original    The original string to translate.
					 */
					$translation = apply_filters( 'ewt_sanitize_string_translation', $translation, $this->strings[ $key ]['name'], $this->strings[ $key ]['context'], $this->strings[ $key ]['string'] );
					$mo->add_entry(
						$mo->make_entry(
							$this->strings[ $key ]['string'],
							$translation
						)
					);
				}

				// Clean database ( removes all strings which were registered some day but are no more )
				if ( ! empty( $_POST['clean'] ) ) {
					$new_mo = new EWT_MO();

					foreach ( $this->strings as $string ) {
						$new_mo->add_entry( $mo->make_entry( $string['string'], $mo->translate( $string['string'] ) ) );
					}
				}

				isset( $new_mo ) ? $new_mo->export_to_db( $language ) : $mo->export_to_db( $language );
			}

			ewt_add_notice( new WP_Error( 'ewt_strings_translations_updated', __( 'Translations updated.', 'easy-wp-translator' ), 'success' ) );

			/**
			 * Fires after the strings translations are saved in DB
			 *
			 *  
			 */
			do_action( 'ewt_save_strings_translations' );
		}

		// Unregisters strings registered through WPML API
		if ( $this->current_action() === 'delete' && ! empty( $_POST['strings'] ) && function_exists( 'icl_unregister_string' ) ) {
			foreach ( array_map( 'sanitize_key', $_POST['strings'] ) as $key ) {
				icl_unregister_string( $this->strings[ $key ]['context'], $this->strings[ $key ]['name'] );
			}
		}

		// To refresh the page 
		$args = array_intersect_key( $_REQUEST, array_flip( array( 's', 'paged', 'group' ) ) );
		if ( ! empty( $_GET['paged'] ) && ! empty( $_POST['submit'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['paged'] = (int) $_GET['paged']; // Don't rely on $_REQUEST['paged'] or $_POST['paged']. 
		}
		if ( ! empty( $args['s'] ) ) {
			$args['s'] = urlencode( $args['s'] ); // Searched string needs to be encoded as it comes from $_POST
		}
		EWT_Settings::redirect( $args );
	}
}
