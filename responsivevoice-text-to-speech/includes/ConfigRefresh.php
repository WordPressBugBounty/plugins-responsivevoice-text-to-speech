<?php
/**
 * Scheduled /config probe refresh.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Refreshes the stored probe on a daily cron event, and once after a plugin
 * update. It runs on cron so no page render waits on the network.
 */
final class ConfigRefresh {

	public const HOOK = 'rvtts_refresh_config';

	public const VERSION_OPTION = 'rvtts_probe_version';

	private const SCHEDULE = 'daily';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Config probe.
	 *
	 * @var ConfigClient
	 */
	private ConfigClient $config;

	/**
	 * Constructor.
	 *
	 * @param Settings     $settings Settings accessor.
	 * @param ConfigClient $config   Config probe.
	 */
	public function __construct( Settings $settings, ConfigClient $config ) {
		$this->settings = $settings;
		$this->config   = $config;
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'sync' ) );
		add_action( self::HOOK, array( $this, 'run' ) );
	}

	/**
	 * Keep the schedule in step with the key and queue a probe after an update.
	 * Not in the activation hook, so an update applied over FTP or WP-CLI is
	 * covered too.
	 */
	public function sync(): void {
		if ( '' === $this->settings->get_api_key() ) {
			wp_clear_scheduled_hook( self::HOOK );

			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, self::SCHEDULE, self::HOOK );
		}

		if ( get_option( self::VERSION_OPTION ) !== RVTTS_VERSION ) {
			update_option( self::VERSION_OPTION, RVTTS_VERSION );
			wp_schedule_single_event( time(), self::HOOK );
		}
	}

	/**
	 * Re-probe and refresh the stored config.
	 */
	public function run(): void {
		if ( '' === $this->settings->get_api_key() ) {
			return;
		}

		$this->config->fetch( true );
	}
}
