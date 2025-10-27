<?php
/**
 * Displays the Languages admin panel
 *
 * @package EasyWPTranslator
 *
 * @var string $active_tab Active EasyWPTranslator settings page.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require ABSPATH . 'wp-admin/options-head.php'; // Displays the errors messages as when we were a child of options-general.php
?>
<div class="wrap">
	<?php
	switch ( $active_tab ) {
		case 'lang':     // Languages tab
		case 'strings':  // String translations tab
			include __DIR__ . '/view-tab-' . $active_tab . '.php';
			break;

		default:
			/**
			 * Fires when loading the active EasyWPTranslator settings tab
			 * Allows plugins to add their own tab
			 *
			 *  
			 */
			do_action( 'ewt_settings_active_tab_' . $active_tab );
			break;
	}
	?>
</div><!-- wrap -->
