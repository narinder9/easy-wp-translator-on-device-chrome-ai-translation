<?php
/**
 * @package EasyWPTranslator
 */
namespace EasyWPTranslator\Admin\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use EasyWPTranslator\Includes\Controllers\EWT_Static_Pages;



/**
 * Manages the static front page and the page for posts on admin side
 *
 *  
 */
class EWT_Admin_Static_Pages extends EWT_Static_Pages {
	/**
	 * @var EWT_Admin_Links|null
	 */
	protected $links;

	/**
	 * Constructor: setups filters and actions.
	 *
	 *  
	 *
	 * @param object $easywptranslator An array of attachment metadata.
	 */
	public function __construct( &$easywptranslator ) {
		parent::__construct( $easywptranslator );

		$this->links = &$easywptranslator->links;

		// Add post state for translations of the front page and posts page
		add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );

		// Refreshes the language cache when a static front page or page for for posts has been translated.
		add_action( 'ewt_save_post', array( $this, 'ewt_save_post' ), 10, 3 );

		// Prevents WP resetting the option
		add_filter( 'pre_update_option_show_on_front', array( $this, 'update_show_on_front' ), 10, 2 );

		add_action( 'admin_notices', array( $this, 'notice_must_translate' ) );
	}

	/**
	 * Adds post state for translations of the front page and posts page.
	 *
	 *  
	 *
	 * @param string[] $post_states An array of post display states.
	 * @param WP_Post  $post        The current post object.
	 * @return string[]
	 */
	public function display_post_states( $post_states, $post ) {
		if ( in_array( $post->ID, $this->model->get_languages_list( array( 'fields' => 'page_on_front' ) ) ) ) {
			$post_states['page_on_front'] = __( 'Front Page', 'easy-wp-translator' );
		}

		if ( in_array( $post->ID, $this->model->get_languages_list( array( 'fields' => 'page_for_posts' ) ) ) ) {
			$post_states['page_for_posts'] = __( 'Posts Page', 'easy-wp-translator' );
		}

		return $post_states;
	}

	/**
	 * Refreshes the language cache when a static front page or page for for posts has been translated.
	 *
	 *  
	 *
	 * @param int     $post_id      Not used.
	 * @param WP_Post $post         Not used.
	 * @param int[]   $translations Translations of the post being saved.
	 * @return void
	 */
	public function ewt_save_post( $post_id, $post, $translations ) {
		if ( in_array( $this->page_on_front, $translations ) || in_array( $this->page_for_posts, $translations ) ) {
			$this->model->clean_languages_cache();
		}
	}

	/**
	 * Prevents WP resetting the option if the admin language filter is active for a language with no pages.
	 *
	 *  
	 *
	 * @param string $value     The new, unserialized option value.
	 * @param string $old_value The old option value.
	 * @return string
	 */
	public function update_show_on_front( $value, $old_value ) {
		if ( ! empty( $GLOBALS['pagenow'] ) && 'options-reading.php' === $GLOBALS['pagenow'] && 'posts' === $value && ! get_pages() && get_pages( array( 'lang' => '' ) ) ) {
			$value = $old_value;
		}
		return $value;
	}

	/**
	 * Add a notice to translate the static front page if it is not translated in all languages
	 * This is especially useful after a new language is created.
	 * The notice is not dismissible and displayed on the Languages pages and the list of pages.
	 *
	 *  
	 *
	 * @return void
	 */
	public function notice_must_translate() {
		$screen = get_current_screen();

		if ( ! empty( $screen ) && ( 'toplevel_page_ewt' === $screen->id || 'edit-page' === $screen->id ) ) {
			$message = $this->get_must_translate_message();

			if ( ! empty( $message ) ) {
				printf(
					'<div class="error"><p>%s</p></div>',
					$message // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
		}
	}

	/**
	 * Returns the message asking to translate the static front page in all languages.
	 *
	 *  
	 *
	 * @return string
	 */
	public function get_must_translate_message() {
		$message = '';

		if ( $this->page_on_front ) {
			$untranslated = array();

			foreach ( $this->model->get_languages_list() as $language ) {
				if ( ! $this->model->post->get( $this->page_on_front, $language ) ) {
					$untranslated[] = sprintf(
						'<a href="%s">%s</a>',
						esc_url( $this->links->get_new_post_translation_link( $this->page_on_front, $language ) ),
						esc_html( $language->name )
					);
				}
			}

			if ( ! empty( $untranslated ) ) {
				$message = sprintf(
					/* translators: %s is a comma separated list of native language names */
					esc_html__( 'You must translate your static front page in %s.', 'easy-wp-translator' ),
					implode( ', ', $untranslated ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
		}

		return $message;
	}
}
