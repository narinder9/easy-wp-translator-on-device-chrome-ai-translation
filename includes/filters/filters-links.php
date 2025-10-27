<?php
/**
 * @package EasyWPTranslator
 */

namespace EasyWPTranslator\Includes\Filters;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Manages links filters needed on both frontend and admin
 *
 *  
 */
class EWT_Filters_Links {
	/**
	 * Stores the plugin options.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * @var EWT_Model
	 */
	public $model;

	/**
	 * Instance of a child class of EWT_Links_Model.
	 *
	 * @var EWT_Links_Model
	 */
	public $links_model;

	/**
	 * @var EWT_Links|null
	 */
	public $links;

	/**
	 * Current language.
	 *
	 * @var EWT_Language|null
	 */
	public $curlang;

	/**
	 * Constructor.
	 *
	 *  
	 *
	 * @param object $easywptranslator The EasyWPTranslator object.
	 */
	public function __construct( &$easywptranslator ) {
		$this->links = &$easywptranslator->links;
		$this->links_model = &$easywptranslator->links_model;
		$this->model = &$easywptranslator->model;
		$this->options = &$easywptranslator->options;
		$this->curlang = &$easywptranslator->curlang;

		// Low priority on links filters to come after any other modifications.
		if ( $this->options['force_lang'] ) {
			add_filter( 'post_link', array( $this, 'post_type_link' ), 20, 2 );
			add_filter( '_get_page_link', array( $this, '_get_page_link' ), 20, 2 );
		}

		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 20, 2 );
		add_filter( 'term_link', array( $this, 'term_link' ), 20, 3 );

		if ( $this->options['force_lang'] > 0 ) {
			add_filter( 'attachment_link', array( $this, 'attachment_link' ), 20, 2 );
		}

		// Keeps the preview post link on default domain when using multiple domains and SSO is not available.
		if ( 3 === $this->options['force_lang'] && ! class_exists( 'EWT_Xdata_Domain' ) ) {
			add_filter( 'preview_post_link', array( $this, 'preview_post_link' ), 20 );
		}

		// Rewrites post types archives links to filter them by language.
		add_filter( 'post_type_archive_link', array( $this, 'post_type_archive_link' ), 20, 2 );
	}

	/**
	 * Modifies page links
	 *
	 *  
	 *
	 * @param string $link    post link
	 * @param int    $post_id post ID
	 * @return string modified post link
	 */
	public function _get_page_link( $link, $post_id ) {
		// /!\ WP does not use pretty permalinks for preview
		return false !== strpos( $link, 'preview=true' ) && false !== strpos( $link, 'page_id=' ) ? $link : $this->links_model->switch_language_in_link( $link, $this->model->post->get_language( $post_id ) );
	}

	/**
	 * Modifies attachment links
	 *
	 *  
	 *
	 * @param string $link    attachment link
	 * @param int    $post_id attachment link
	 * @return string modified attachment link
	 */
	public function attachment_link( $link, $post_id ) {
		return wp_get_post_parent_id( $post_id ) ? $link : $this->links_model->switch_language_in_link( $link, $this->model->post->get_language( $post_id ) );
	}

	/**
	 * Modifies custom posts links.
	 *
	 *  
	 *
	 * @param string  $link Post link.
	 * @param WP_Post $post Post object.
	 * @return string Modified post link.
	 */
	public function post_type_link( $link, $post ) {
		// /!\ WP does not use pretty permalinks for preview
		if ( ( false === strpos( $link, 'preview=true' ) || false === strpos( $link, 'p=' ) ) && $this->model->is_translated_post_type( $post->post_type ) ) {
			$lang = $this->model->post->get_language( $post->ID );
			$link = $this->options['force_lang'] ? $this->links_model->switch_language_in_link( $link, $lang ) : $link;

			/**
			 * Filters a post or custom post type link.
			 *
			 *  
			 *
			 * @param string       $link The post link.
			 * @param EWT_Language $lang The current language.
			 * @param WP_Post      $post The post object.
			 */
			$link = apply_filters( 'ewt_post_type_link', $link, $lang, $post );
		}

		return $link;
	}

	/**
	 * Modifies term links.
	 *
	 *  
	 *
	 * @param string  $link Term link.
	 * @param WP_Term $term Term object.
	 * @param string  $tax  Taxonomy name;
	 * @return string Modified term link.
	 */
	public function term_link( $link, $term, $tax ) {
		if ( $this->model->is_translated_taxonomy( $tax ) ) {
			$lang = $this->model->term->get_language( $term->term_id );
			$link = $this->options['force_lang'] ? $this->links_model->switch_language_in_link( $link, $lang ) : $link;

			/**
			 * Filter a term link
			 *
			 *  
			 *
			 * @param string       $link The term link.
			 * @param EWT_Language $lang The current language.
			 * @param WP_Term      $term The term object.
			 */
			return apply_filters( 'ewt_term_link', $link, $lang, $term );
		}

		// In case someone calls get_term_link for the 'ewt_language' taxonomy.
		if ( 'ewt_language' === $tax ) {
			$lang = $this->model->get_language( $term->term_id );
			if ( $lang ) {
				return $this->links_model->home_url( $lang );
			}
		}

		return $link;
	}

	/**
	 * Keeps the preview post link on default domain when using multiple domains.
	 *
	 *  
	 *
	 * @param string $url URL used for the post preview.
	 * @return string The modified url.
	 */
	public function preview_post_link( $url ) {
		return $this->links_model->remove_language_from_link( $url );
	}

	/**
	 * Modifies the post type archive links to add the language parameter
	 * only if the post type is translated.
	 *
	 * The filter was originally only on frontend but is needed on admin too for
	 * compatibility with the archive link of the ACF link field since ACF 5.4.0.
	 *
	 *  
	 *
	 * @param string $link      The post type archive permalink.
	 * @param string $post_type Post type name.
	 * @return string
	 */
	public function post_type_archive_link( $link, $post_type ) {
		return $this->model->is_translated_post_type( $post_type ) && 'post' !== $post_type ? $this->links_model->switch_language_in_link( $link, $this->curlang ) : $link;
	}
}
