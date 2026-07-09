<?php
/**
 * Plugin orchestrator.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Boots and wires the plugin's services. Singleton.
 */
final class Plugin {

	/**
	 * Shared instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Retrieve the shared instance.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor; use Plugin::instance().
	 */
	private function __construct() {}

	/**
	 * Register the plugin's services with WordPress.
	 */
	public function register(): void {
		$settings     = new Settings();
		$config       = new ConfigClient( $settings );
		$router       = new Router( $settings, $config );
		$verification = new Verification( $settings, $config );
		$dismissal    = new Admin\Dismissal();

		// Per-user notice dismissals (the AJAX write-back shared by dismissible notices).
		$dismissal->register();

		// v1 Shortcodes.
		( new LegacyEngine( new TextSanitizer() ) )->register();

		// Core library (v1 or v2, per engine) + the static button handler. Shared
		// with the block so its editor loads the same engine core for getVoices().
		$assets = new AssetManager( $settings, $router );
		$assets->register();

		// Gutenberg block: listen button.
		( new Block( $settings, $router, $assets ) )->register();

		// Site-wide "verify your website" CTA (admin notices + admin-bar warning +
		// the browser probe write-back). Registered unconditionally so the admin-bar
		// node also appears on the front end.
		( new Admin\VerificationNotice( $settings, $verification, $dismissal ) )->register();

		// The v2 SDK runtime (boots the SDK + mounts the WebPlayer feature) runs only
		// when WebPlayer is the active engine.
		if ( Router::ENGINE_WEBPLAYER === $router->active_engine() ) {
			( new SdkRuntime( $settings, new WebPlayerEngine( $settings, $config ) ) )->register();
		}

		if ( is_admin() ) {
			( new Admin\SettingsPage( $settings, $config, $verification ) )->register();
			// MetaBox::register() self-gates on whether the WebPlayer will run.
			( new Admin\MetaBox( $settings, $router, new WebPlayerEngine( $settings, $config ) ) )->register();

			// "Meet the WebPlayer" promo, for capable, verified accounts that have the player off.
			( new Admin\AnnouncementBanner( $settings, $router, $verification, new WebPlayerEngine( $settings, $config ), $dismissal ) )->register();

			// Keyless onboarding: a dismissible site-wide pointer to the settings page.
			( new Admin\OnboardingNotice( $settings, $dismissal ) )->register();
		}
	}
}
