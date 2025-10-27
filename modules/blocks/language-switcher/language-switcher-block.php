<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Modules\Blocks;

/**
 * Language switcher block.
 *
 */
class EWT_Language_Switcher_Block extends EWT_Abstract_Language_Switcher_Block {

	/**
	 * Returns the language switcher block name with the EasyWPTranslator's namespace.
	 *
	 *
	 * @return string The block name.
	 */
	protected function get_block_name() {
		return 'easywptranslator/language-switcher';
	}

	/**
	 * Renders the `easywptranslator/language-switcher` block on server.
	 *
	 *
	 * @param array    $attributes The block attributes.
	 * @param string   $content The saved content. Unused.
	 * @param \WP_Block $block The parsed block. Unused.
	 * @return string Returns the language switcher.
	 */
	public function render( $attributes, $content, $block ) { //phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		static $dropdown_id = 0;
		++$dropdown_id;
		// Sets a unique id for dropdown in EWT_Switcher::the_language().
		// Only set dropdown ID if dropdown is actually enabled (truthy value)
		if ( ! empty( $attributes['dropdown'] ) ) {
			$attributes['dropdown'] = $dropdown_id;
		}

		$attributes = $this->set_attributes_for_block( $attributes );

		$attributes['raw'] = false;
		$switcher = new \EasyWPTranslator\Includes\Controllers\EWT_Switcher();
		$switcher_output = $switcher->the_languages( $this->links, $attributes );

		if ( empty( $switcher_output ) ) {
			return '';
		}

		$aria_label = __( 'Choose a language', 'easy-wp-translator' );
		if ( $attributes['dropdown'] ) {
			$switcher_output = '<label class="screen-reader-text" for="' . esc_attr( 'lang_choice_' . $attributes['dropdown'] ) . '">' . esc_html( $aria_label ) . '</label>' . $switcher_output;

			$wrap_tag = '<div %1$s>%2$s</div>';
		} else {
			$wrap_tag = '<nav role="navigation" aria-label="' . esc_attr( $aria_label ) . '"><ul %1$s>%2$s</ul></nav>';
		}

		$wrap_attributes = get_block_wrapper_attributes();

		return sprintf( $wrap_tag, $wrap_attributes, $switcher_output );
	}
}
