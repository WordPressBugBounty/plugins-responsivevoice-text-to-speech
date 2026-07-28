<?php
/**
 * Deactivation handler.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Runs on plugin deactivation.
 */
final class Deactivator {

	/**
	 * Deactivation tasks.
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( ConfigRefresh::HOOK );
	}
}
