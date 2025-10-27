<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Filters;


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


use EasyWPTranslator\Includes\Walkers\EWT_Walker_Dropdown;


/**
 * Class EWT_Widgets_Filters
 *
 *  
 *
 * Add new options to  WP_Widget and saves them.
 */
class EWT_Filters_Widgets_Options {

	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * EWT_Widgets_Filters constructor.
	 *
	 *
	 * @param EWT_Base $easywptranslator The EasyWPTranslator object.
	 * @return void
	 */
	public function __construct( $easywptranslator ) {
		$this->model = $easywptranslator->model;

		add_action( 'in_widget_form', array( $this, 'in_widget_form' ), 10, 3 );
		add_filter( 'widget_update_callback', array( $this, 'widget_update_callback' ), 10, 2 );
	}

	/**
	 * Add the language filter field to the widgets options form.
	 *
	 *   Rename lang_choice field name and id to ewt_lang as the widget setting.
	 *
	 * @param WP_Widget $widget   The widget instance (passed by reference).
	 * @param null      $return   Return null if new fields are added.
	 * @param array     $instance An array of the widget's settings.
	 * @return void
	 *
	 * @phpstan-param WP_Widget<array<string, mixed>> $widget
	 */
	public function in_widget_form( $widget, $return, $instance ) {
		$dropdown = new EWT_Walker_Dropdown();

		$dropdown_html = $dropdown->walk(
			array_merge(
				array( (object) array( 'slug' => 0, 'name' => __( 'All languages', 'easy-wp-translator' ) ) ),
				$this->model->get_languages_list()
			),
			-1,
			array(
				'id' => $widget->get_field_id( 'ewt_lang' ),
				'name' => $widget->get_field_name( 'ewt_lang' ),
				'class' => 'tags-input ewt-lang-choice',
				'selected' => empty( $instance['ewt_lang'] ) ? '' : $instance['ewt_lang'],
			)
		);

		printf(
			'<p><label for="%1$s">%2$s %3$s</label></p>',
			esc_attr( $widget->get_field_id( 'ewt_lang' ) ),
			esc_html__( 'The widget is displayed for:', 'easy-wp-translator' ),
			wp_kses(
				$dropdown_html,
				array(
					'span'   => array( 'class' => true ),
					'img'    => array(
						'src'      => true,
						'alt'      => true,
						'width'    => true,
						'height'   => true,
						'class'    => true,
						'style'    => true,
						'loading'  => true,
						'decoding' => true,
					),
					'select' => array(
						'name'     => true,
						'id'       => true,
						'class'    => true,
						'lang'     => true,
						'disabled' => true,
					),
					'option' => array(
						'value'     => true,
						'lang'      => true,
						'aria-label'=> true,
						'selected'  => true,
						'data-lang' => true,
					),
				),
				array_merge( wp_allowed_protocols(), array( 'data' ) )
			)
		);
	}

	/**
	 * Called when widget options are saved.
	 * Saves the language associated to the widget.
	 *
	 *  
	 *   Remove unused $old_instance and $widget parameters.
	 *
	 * @param array $instance     The current Widget's options.
	 * @param array $new_instance The new Widget's options.
	 * @return array Widget options.
	 */
	public function widget_update_callback( $instance, $new_instance ) {
		if ( ! empty( $new_instance['ewt_lang'] ) && $lang = $this->model->get_language( $new_instance['ewt_lang'] ) ) {
			$instance['ewt_lang'] = $lang->slug;
		} else {
			unset( $instance['ewt_lang'] );
		}

		return $instance;
	}
}
