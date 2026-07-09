<?php
/**
 * "Meet the WebPlayer" admin announcement banner.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice\Admin;

use ResponsiveVoice\Settings;
use ResponsiveVoice\Router;
use ResponsiveVoice\Verification;
use ResponsiveVoice\WebPlayerEngine;

defined( 'ABSPATH' ) || exit;

/**
 * A dismissible promo introducing the WebPlayer to a capable account.
 *
 * Shown to an account that can run the WebPlayer (v2 or a v1 opt-in) and is
 * verified. The content is state-aware: "switch it on" when the player is off,
 * "customise it" when it's on. Doesn't show if a website needs to be verified:
 * requiring "verified" makes needs_cta() false, and requiring "capable" makes
 * needs_upgrade_notice() false, so this can never stack with a VerificationNotice.
 * That shows at most one ResponsiveVoice notice per screen, per WordPress.org
 * Guideline 11. It's scoped to the Dashboard and Plugins screens (not the plugin's
 * own settings page, which is where it points), and dismissed per user forever
 * via the Dismissal class.
 */
final class AnnouncementBanner {

	private const CAPABILITY    = 'manage_options';
	private const SETTINGS_SLUG = 'responsivevoice-text-to-speech';
	private const NOTICE_ID     = 'webplayer-promo';
	private const STYLE_HANDLE  = 'rvtts-announcement';
	private const SCREENS       = array( 'dashboard', 'plugins' );

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Engine router (WebPlayer capability).
	 *
	 * @var Router
	 */
	private Router $router;

	/**
	 * Verification state.
	 *
	 * @var Verification
	 */
	private Verification $verification;

	/**
	 * WebPlayer feature (enabled state).
	 *
	 * @var WebPlayerEngine
	 */
	private WebPlayerEngine $engine;

	/**
	 * Per-user dismissal store.
	 *
	 * @var Dismissal
	 */
	private Dismissal $dismissal;

	/**
	 * Constructor.
	 *
	 * @param Settings        $settings     Settings accessor.
	 * @param Router          $router       Engine router.
	 * @param Verification    $verification Verification state.
	 * @param WebPlayerEngine $engine       WebPlayer feature.
	 * @param Dismissal       $dismissal    Per-user dismissal store.
	 */
	public function __construct(
		Settings $settings,
		Router $router,
		Verification $verification,
		WebPlayerEngine $engine,
		Dismissal $dismissal
	) {
		$this->settings     = $settings;
		$this->router       = $router;
		$this->verification = $verification;
		$this->engine       = $engine;
		$this->dismissal    = $dismissal;
	}

	/**
	 * Hook the notice into the admin.
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Load the banner's brand stylesheet and the dismiss client, in the head, on
	 * the screens where it will actually render.
	 */
	public function enqueue_assets(): void {
		if ( ! $this->should_show() ) {
			return;
		}

		wp_enqueue_style( self::STYLE_HANDLE, RVTTS_PLUGIN_URL . 'build/announcement.css', array(), RVTTS_VERSION );
		wp_style_add_data( self::STYLE_HANDLE, 'rtl', 'replace' );
		$this->dismissal->enqueue();
	}

	/**
	 * Print the banner when the account is capable + verified + the player is off,
	 * on the Dashboard or Plugins screen, and it hasn't been dismissed.
	 */
	public function render(): void {
		if ( ! $this->should_show() ) {
			return;
		}

		// State-aware copy: nudge to switch it on when off, to customise it when on.
		if ( $this->engine->is_enabled() ) {
			$heading = __( 'Your ResponsiveVoice WebPlayer is live', 'responsivevoice-text-to-speech' );
			$body    = __( 'Customize how it looks and sounds, and choose where it appears, in Settings.', 'responsivevoice-text-to-speech' );
			$cta     = __( 'Customize the WebPlayer', 'responsivevoice-text-to-speech' );
		} else {
			$heading = __( 'Meet the ResponsiveVoice WebPlayer', 'responsivevoice-text-to-speech' );
			$body    = __( "It's ready on your account — switch it on to add an audio player to your posts.", 'responsivevoice-text-to-speech' );
			$cta     = __( 'Enable the WebPlayer', 'responsivevoice-text-to-speech' );
		}

		printf(
			'<div class="notice notice-info is-dismissible rvtts-announcement" data-rvtts-dismiss="%1$s">'
			. '<img class="rvtts-announcement__img" src="%2$s" alt="%3$s" width="366" height="52" />'
			. '<span class="rvtts-announcement__sep" aria-hidden="true"></span>'
			. '<div class="rvtts-announcement__text">'
			. '<p class="rvtts-announcement__title">%4$s</p>'
			. '<p class="rvtts-announcement__desc">%5$s</p>'
			. '</div>'
			. '<a class="rvtts-announcement__cta" href="%6$s">%7$s</a>'
			. '</div>',
			esc_attr( self::NOTICE_ID ),
			esc_url( RVTTS_PLUGIN_URL . 'images/webplayer-pill.png' ),
			esc_attr__( 'The ResponsiveVoice WebPlayer', 'responsivevoice-text-to-speech' ),
			esc_html( $heading ),
			esc_html( $body ),
			esc_url( admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ),
			esc_html( $cta )
		);
	}

	/**
	 * Whether every gate for showing the banner is satisfied.
	 */
	private function should_show(): bool {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, self::SCREENS, true ) ) {
			return false;
		}

		if ( '' === $this->settings->get_api_key() ) {
			return false;
		}

		// Capable: the WebPlayer is this account's engine (v2, or a v1 opt-in).
		if ( Router::ENGINE_WEBPLAYER !== $this->router->active_engine() ) {
			return false;
		}

		// Verified. This also guarantees the verify blocker isn't showing (precedence).
		if ( true !== $this->verification->is_verified() ) {
			return false;
		}

		// Visibility is enable-state-agnostic; the content adapts (on vs off).
		// Shown once per admin and dismissable.
		return ! $this->dismissal->is_dismissed( self::NOTICE_ID );
	}
}
