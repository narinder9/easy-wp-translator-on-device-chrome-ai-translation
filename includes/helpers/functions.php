<?php
/**
 * @package EasyWPTranslator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}





/**
 * Determines whether we should load the cache compatibility
 *
 *  
 *
 * @return bool True if the cache compatibility must be loaded
 */
function ewt_is_cache_active() {
	/**
	 * Filters whether we should load the cache compatibility
	 *
	 *  
	 *
	 * @bool $is_cache True if a known cache plugin is active
	 *                 incl. WP Fastest Cache which doesn't use WP_CACHE
	 */
	return apply_filters( 'ewt_is_cache_active', ( defined( 'WP_CACHE' ) && WP_CACHE ) || defined( 'WPFC_MAIN_PATH' ) );
}

/**
 * Get the the current requested url
 *
 *  
 *
 * @return string Requested url
 */
function ewt_get_requested_url() {
	if ( isset( $_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'] ) ) {
		return set_url_scheme( sanitize_url( wp_unslash( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ) ) );
	}

	/** @var string */
	$home_url = get_option( 'home' );

	/*
	 * In WP CLI context, few developers define superglobals in wp-config.php
	 * So let's return the unfiltered home url to avoid a bunch of notices.
	 */
	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return $home_url;
	}

	/*
	 * When using system CRON instead of WP_CRON, the superglobals are likely undefined.
	 */
	if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
		return $home_url;
	}

	if ( WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions
		trigger_error( '$_SERVER[\'HTTP_HOST\'] or $_SERVER[\'REQUEST_URI\'] are required but not set.' );
	}

	return '';
}

/**
 * Tells if a constant is defined.
 *
 *  
 *
 * @param string $constant_name Name of the constant.
 * @return bool True if the constant is defined, false otherwise.
 *
 * @phpstan-param non-falsy-string $constant_name
 */
function ewt_has_constant( string $constant_name ): bool {
	return defined( $constant_name ); // phpcs:ignore WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName
}

/**
 * Returns the value of a constant if it is defined.
 *
 *  
 *
 * @param string $constant_name Name of the constant.
 * @param mixed  $default       Optional. Value to return if the constant is not defined. Defaults to `null`.
 * @return mixed The value of the constant.
 *
 * @phpstan-param non-falsy-string $constant_name
 * @phpstan-param int|float|string|bool|array|null $default
 */
function ewt_get_constant( string $constant_name, $default = null ) {
	if ( ! ewt_has_constant( $constant_name ) ) {
		return $default;
	}

	return constant( $constant_name );
}

/**
 * Defines a constant if it is not already defined.
 *
 *  
 *
 * @param string $constant_name Name of the constant.
 * @param mixed  $value         Value to set.
 * @return bool True on success, false on failure or already defined.
 *
 * @phpstan-param non-falsy-string $constant_name
 * @phpstan-param int|float|string|bool|array|null $value
 */
function ewt_set_constant( string $constant_name, $value ): bool {
	if ( ewt_has_constant( $constant_name ) ) {
		return false;
	}

	return define( $constant_name, $value ); // phpcs:ignore WordPressVIPMinimum.Constants.ConstantString.NotCheckingConstantName
}

/**
 * Determines whether a plugin is active.
 *
 * We define our own function because `is_plugin_active()` is available only in the backend.
 *
 *  
 *
 * @param string $plugin_name Plugin basename.
 * @return bool True if activated, false otherwise.
 */
function ewt_is_plugin_active( string $plugin_name ) {
	$sitewide_plugins     = get_site_option( 'active_sitewide_plugins' );
	$sitewide_plugins     = ! empty( $sitewide_plugins ) && is_array( $sitewide_plugins ) ? array_keys( $sitewide_plugins ) : array();
	$current_site_plugins = (array) get_option( 'active_plugins', array() );
	$plugins              = array_merge( $sitewide_plugins, $current_site_plugins );

	return in_array( $plugin_name, $plugins );
}

/**
 * Prepares and registers notices.
 *
 * Wraps `add_settings_error()` to make its use more consistent.
 *
 *  
 *
 * @param WP_Error $error Error object.
 * @return void
 */
function ewt_add_notice( WP_Error $error ) {
	if ( ! $error->has_errors() ) {
		return;
	}

	foreach ( $error->get_error_codes() as $error_code ) {
		// Extract the "error" type.
		$data = $error->get_error_data( $error_code );
		$type = empty( $data ) || ! is_string( $data ) ? 'error' : $data;

		$message = wp_kses(
			implode( '<br>', $error->get_error_messages( $error_code ) ),
			array(
				'a'    => array( 'href' => true ),
				'br'   => array(),
				'code' => array(),
				'em'   => array(),
			)
		);

		add_settings_error( 'easy-wp-translator', $error_code, $message, $type );
	}
}

/**
 * Replaces links with their translated versions.
 *
 * @param string $content The content to replace links in.
 * @param string $locale The locale to replace links for.
 * @return string The content with links replaced.
 */
function ewt_replace_links_with_translations($content, $locale, $current_locale){
	// Get all URLs in the content that start with the current home page URL (current domain), regardless of attribute or tag.
	$home_url = preg_quote(get_home_url(), '/');
	$pattern = '/(' . $home_url . '[^\s"\'<>]*)/i';
	
	$taxonomies=get_taxonomies([],'objects');
 
	 $terms_data=array();

	 foreach($taxonomies as $key=>$taxonomy){
		 if(isset($taxonomy->rewrite['slug'])){
			 $terms_data[$taxonomy->rewrite['slug']]=$key;
		 }else{
			 $terms_data[$key]=$key;
		 }
	 }
 
	 function ewt_extract_taxonomy_name($path, $terms_data){
		 // Remove the language prefix if using EasyWPTranslator
		 $languages = ewt_languages_list(); // e.g., ['en', 'fr']
		 $segments = explode('/', $path);
		 if (in_array($segments[0], $languages)) {
			 array_shift($segments); // remove 'en', 'fr', etc.
		 }
		 
		 if (empty($segments)) {
			 return null;
		 }
 
		 // First segment after language is usually the taxonomy slug
		 $possible_tax = $segments[0];
 
		 if (taxonomy_exists($possible_tax) || (isset($terms_data[$possible_tax]) && taxonomy_exists($terms_data[$possible_tax]))) {
				return isset($terms_data[$possible_tax]) ? $terms_data[$possible_tax] : $possible_tax;
		 }
 
		 return false;
	 }
 
 
	if (preg_match_all($pattern, $content, $matches)) {
		foreach ($matches[1] as $href) {
			$postID = url_to_postid($href);
 
			if ($postID > 0) {
				$translatedPost = ewt_get_post($postID, $locale);
				if ($translatedPost) {
					$link = get_permalink($translatedPost);
					
					if ($link) {
						$link=esc_url(urldecode_deep($link));
						$content = str_replace($href, $link, $content);
					}
				}
			} else {
				 $path = trim(str_replace(ewt_home_url($current_locale), '', $href), '/');
				 $path_parts = array_filter(explode('/', $path));
				 $category_slug = end($path_parts);
				 $taxonomy_name=ewt_extract_taxonomy_name($path, $terms_data);
				 $taxonomy_name=$taxonomy_name ? $taxonomy_name : 'category';
 
				$category = get_term_by('slug', $category_slug, $taxonomy_name);
 
				if(!$category){
						// Remove the language prefix if using EasyWPTranslator
					$languages = ewt_languages_list(); // e.g., ['en', 'fr']
					$segments = explode('/', $path);
					if (in_array($segments[0], $languages)) {
						$lang_code=$segments[0];
						$category_id=EWT()->model->term_exists_by_slug($category_slug, $lang_code, $taxonomy_name);
 
						if($category_id){
							$category=get_term($category_id, $taxonomy_name);
						}
					}
				}
 
				
				if ($category) {
					$term_id = ewt_get_term($category->term_id, $locale);
					if ($term_id > 0) {
						$link = get_category_link($term_id);
						$content = str_replace($href, esc_url($link), $content);
					}
				}
			}
		}
	}
	
	return $content;
 }
function ewt_is_edit_rest_request(WP_REST_Request $request): bool {
	if (in_array($request->get_method(), array('PATCH', 'POST', 'PUT'), true)) {
		return true;
	}
	return 'GET' === $request->get_method() && 'edit' === $request->get_param('context');
}


/**
 * Determines whether we should load the block editor plugin or the legacy languages metabox.
 *
 *
 * @return bool True to use the block editor plugin.
 */
function ewt_use_block_editor_plugin() {
	/**
	 * Filters whether we should load the block editor plugin or the legacy languages metabox.
	 *
	 * @param bool $use_plugin True when loading the block editor plugin.
	 */
	return class_exists( 'EasyWPTranslator\Modules\Editors\Screens\Abstract_Screen' ) && apply_filters( 'ewt_use_block_editor_plugin', ! defined( 'EWT_USE_BLOCK_EDITOR_PLUGIN' ) || EWT_USE_BLOCK_EDITOR_PLUGIN );
}

/**
 * Checks if a specific language switcher type is enabled
 *
 *  
 *
 * @param string $switcher_type The switcher type to check ('default', 'block', 'elementor')
 * @return bool True if the switcher type is enabled
 */
function ewt_is_switcher_type_enabled( $switcher_type ) {
	// Get the options instance
	global $easywptranslator;
	
	if ( ! isset( $easywptranslator->options ) ) {
		// Fallback to default if options not available
		return 'default' === $switcher_type;
	}
	
	$enabled_switchers = $easywptranslator->options->get( 'ewt_language_switcher_options' );
	
	// Ensure it's an array
	if ( ! is_array( $enabled_switchers ) ) {
		$enabled_switchers = array( 'default' );
	}
	return in_array( $switcher_type, $enabled_switchers, true );
}