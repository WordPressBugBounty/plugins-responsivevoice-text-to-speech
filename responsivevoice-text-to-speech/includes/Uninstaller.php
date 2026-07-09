<?php
/**
 * Uninstall cleanup.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

use ResponsiveVoice\Admin\Dismissal;

/**
 * Removes every piece of data the plugin stores. Invoked from `uninstall.php`.
 */
final class Uninstaller {

	/**
	 * Delete the plugin's options, cached config, and per-post/per-user meta.
	 */
	public static function run(): void {
		$settings = new Settings();

		// Cached /config probe and durable store: read the key before the options go.
		( new ConfigClient( $settings ) )->purge( $settings->get_api_key() );

		delete_option( Settings::OPTION );
		delete_option( Verification::OPTION );

		delete_post_meta_by_key( WebPlayerEngine::META_KEY );
		Dismissal::purge_all();
	}
}
