<?php
namespace EasyWPTranslator\Admin\Views;
/**
 * Displays the translations fields for posts
 *
 * @package EasyWPTranslator
 *
 * @var EWT_Admin_Classic_Editor $this    EWT_Admin_Classic_Editor object.
 * @var EWT_Language             $lang    The post language. Default language if no language assigned yet.
 * @var int                      $post_ID The post id.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


?>
<p><strong><?php esc_html_e( 'Translations', 'easy-wp-translator' ); ?></strong></p>
<table>
	<?php
	foreach ( $this->model->get_languages_list() as $language ) {
		if ( $language->term_id === $lang->term_id ) {
			continue;
		}

		$translation_id = $this->model->post->get_translation( $post_ID, $language );
		if ( ! $translation_id || $translation_id === $post_ID ) { // $translation_id == $post_ID happens if the post has been (auto)saved before changing the language.
			$translation_id = 0;
		}

		if ( ! empty( $from_post_id ) ) {
			$translation_id = $this->model->post->get( $from_post_id, $language );
		}

		$add_link    = $this->links->new_post_translation_link( $post_ID, $language );
		$link        = $add_link;
		$translation = null;
		if ( $translation_id ) {
			$translation = get_post( $translation_id );
			$link = $this->links->edit_post_translation_link( $translation_id );
		}
		?>
		<tr>
			<th class = "ewt-language-column"><?php echo $language->flag ? wp_kses( $language->flag, array( 'img' => array( 'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true, 'style' => true ), 'span' => array( 'class' => true ), 'abbr' => array() ), array_merge( wp_allowed_protocols(), array( 'data' ) ) ) : esc_html( $language->slug ); ?></th>
			<td class = "hidden"><?php echo wp_kses_post( $add_link ); ?></td>
			<td class = "ewt-edit-column ewt-column-icon"><?php echo wp_kses_post( $link ); ?></td>
			<?php

			/**
			 * Fires before the translation column is outputted in the language metabox.
			 * The dynamic portion of the hook name, `$lang`, refers to the language code.
			 *
			 *  
			 */
			do_action( 'ewt_before_post_translation_' . $language->slug );
			?>
			<td class = "ewt-translation-column">
				<?php
				printf(
					'<label class="screen-reader-text" for="tr_lang_%1$s">%2$s</label>
					<input type="hidden" name="post_tr_lang[%1$s]" id="htr_lang_%1$s" value="%3$s" />
					<span lang="%5$s" dir="%6$s"><input type="text" class="tr_lang" id="tr_lang_%1$s" value="%4$s" /></span>',
					esc_attr( $language->slug ),
					/* translators: accessibility text */
					esc_html__( 'Translation', 'easy-wp-translator' ),
					( empty( $translation ) ? '0' : esc_attr( (string) $translation->ID ) ),
					( empty( $translation ) ? '' : esc_attr( $translation->post_title ) ),
					esc_attr( $language->get_locale( 'display' ) ),
					( $language->is_rtl ? 'rtl' : 'ltr' )
				);
				?>
			</td>
		</tr>
		<?php
	}
	?>
</table>
