<?php
/**
 * Admin notice shown when the site's PHP version is below the plugin's requirement.
 * Loaded directly (before the autoloader) so it must not rely on plugin classes.
 *
 * @package ResponsiveVoice
 */

defined( 'ABSPATH' ) || exit;

add_action(
	'admin_notices',
	static function () {
		$message = sprintf(
			/* translators: 1: required PHP version, 2: current PHP version. */
			esc_html__(
				'ResponsiveVoice Text To Speech requires PHP %1$s or higher. Your site is running PHP %2$s. Please upgrade PHP to use this plugin.',
				'responsivevoice-text-to-speech'
			),
			'7.4',
			esc_html( PHP_VERSION )
		);

		printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
	}
);
