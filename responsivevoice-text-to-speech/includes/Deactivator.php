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
	 * Deactivation tasks. Reserved for clearing scheduled events / transient caches.
	 */
	public static function deactivate(): void {
		// Intentionally empty for now.
	}
}
