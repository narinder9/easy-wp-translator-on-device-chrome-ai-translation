<?php
/**
 * Displays the wizard notice content
 *
 * @package EasyWPTranslator
 *
 *  
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Don't access directly.
}

$wizard_url = add_query_arg(
	array(
		'page' => 'ewt_wizard',
	),
	admin_url( 'admin.php' )
);
?>
<p>
	<strong>
	<?php
	printf(
		/* translators: %s is the plugin name */
		esc_html__( 'Welcome to %s', 'easy-wp-translator' ),
		esc_html( EASY_WP_TRANSLATOR )
	);
	?>
	</strong>
	<?php
	echo ' &#8211; ';
	esc_html_e( 'You&lsquo;re almost ready to translate your contents!', 'easy-wp-translator' );
	?>
</p>
<p class="buttons">
	<a
		href="<?php echo esc_url( $wizard_url ); ?>"
		class="button button-primary"
	>
		<?php esc_html_e( 'Run the Setup Wizard', 'easy-wp-translator' ); ?>
	</a>
	<a
		class="button button-secondary skip"
		href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ewt-hide-notice', 'wizard' ), 'wizard', '_ewt_notice_nonce' ) ); ?>"
	>
		<?php esc_html_e( 'Skip setup', 'easy-wp-translator' ); ?>
	</a>
</p>
