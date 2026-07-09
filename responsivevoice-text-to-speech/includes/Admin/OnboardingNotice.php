<?php
/**
 * Keyless onboarding admin notice.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice\Admin;

use ResponsiveVoice\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * A dismissible site-wide pointer to the settings page while no API key is set.
 *
 * With no key, the other two notices stay silent (VerificationNotice and
 * AnnouncementBanner both need a key), so this one can never stack with them.
 * That keeps us to at most one ResponsiveVoice notice per screen, per
 * WordPress.org Guideline 11. Hidden on the plugin's own settings page (that's
 * where it points, and that page has its own richer onboarding notice), and
 * dismissed per user forever via Dismissal.
 */
final class OnboardingNotice {

	private const CAPABILITY    = 'manage_options';
	private const SETTINGS_SLUG = 'responsivevoice-text-to-speech';
	private const NOTICE_ID     = 'onboarding';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Per-user dismissal store.
	 *
	 * @var Dismissal
	 */
	private Dismissal $dismissal;

	/**
	 * Constructor.
	 *
	 * @param Settings  $settings  Settings accessor.
	 * @param Dismissal $dismissal Per-user dismissal store.
	 */
	public function __construct( Settings $settings, Dismissal $dismissal ) {
		$this->settings  = $settings;
		$this->dismissal = $dismissal;
	}

	/**
	 * Hook the notice into the admin.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render' ) );
	}

	/**
	 * Print the notice while no key is set, anywhere in the admin except the
	 * plugin's own settings page.
	 */
	public function render(): void {
		if ( ! $this->should_show() ) {
			return;
		}

		$this->dismissal->enqueue();
		printf(
			'<div class="notice notice-info is-dismissible rvtts-onboarding-notice" data-rvtts-dismiss="%1$s">'
			. '<p><strong>%2$s</strong> %3$s</p>'
			. '<p><a class="button button-primary" href="%4$s">%5$s</a></p>'
			. '</div>',
			esc_attr( self::NOTICE_ID ),
			esc_html__( 'ResponsiveVoice:', 'responsivevoice-text-to-speech' ),
			esc_html__( "Add your free API key to unlock the full voice catalog and the WebPlayer. Without one, listen buttons use only the browser's built-in voice.", 'responsivevoice-text-to-speech' ),
			esc_url( admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ),
			esc_html__( 'Open ResponsiveVoice settings', 'responsivevoice-text-to-speech' )
		);
	}

	/**
	 * Whether every gate for showing the notice is satisfied.
	 */
	private function should_show(): bool {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_' . self::SETTINGS_SLUG === $screen->id ) {
			return false;
		}

		if ( '' !== $this->settings->get_api_key() ) {
			return false;
		}

		return ! $this->dismissal->is_dismissed( self::NOTICE_ID );
	}
}
