<?php
namespace EasyWPTranslator\Admin\Views;
/**
 * Displays the translations fields for media
 * Needs WP 3.5+
 *
 * @package EasyWPTranslator
 *
 * @var EWT_Admin_Classic_Editor $this    EWT_Admin_Classic_Editor object.
 * @var EWT_Language             $lang    The media language. Default language if no language assigned yet.
 * @var int                      $post_ID The media Id.
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
		?>
		<tr>
			<td class = "ewt-media-language-column"><span class = "ewt-translation-flag"><?php echo $language->flag ? wp_kses( $language->flag, array( 'img' => array( 'src' => true, 'alt' => true, 'class' => true, 'width' => true, 'height' => true, 'style' => true ), 'span' => array( 'class' => true ), 'abbr' => array() ), array_merge( wp_allowed_protocols(), array( 'data' ) ) ) : ''; ?></span><?php echo esc_html( $language->name ); ?></td>
			<td class = "ewt-media-edit-column">
				<?php
				$translation_id = $this->model->post->get_translation( $post_ID, $language );
				if ( ! empty( $translation_id ) && $translation_id !== $post_ID ) {
					// The translation exists
					printf(
						'<input type="hidden" name="media_tr_lang[%s]" value="%d" />',
						esc_attr( $language->slug ),
						(int) $translation_id
					);
					echo wp_kses_post( $this->links->edit_post_translation_link( $translation_id ) );
				} else {
					// No translation
					echo wp_kses_post( $this->links->new_post_translation_link( $post_ID, $language ) );
				}
				?>
			</td>
		</tr>
		<?php
	} // End foreach
	?>
</table>
