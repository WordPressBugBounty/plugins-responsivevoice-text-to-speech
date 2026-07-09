<?php
/**
 * Site-wide "verify your website" CTA.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice\Admin;

use ResponsiveVoice\Settings;
use ResponsiveVoice\Verification;
use WP_Admin_Bar;

defined( 'ABSPATH' ) || exit;

/**
 * Surfaces an unverified-website state everywhere it matters.
 *
 * The v2 WebPlayer silently does nothing until the site's origin is verified in
 * the RV Dashboard. Detection is client-only (the `auth` token in `/v2/config`
 * is origin-gated), so a tiny script runs on every admin page, probes from the
 * browser, and persists the result via Verification. This service then
 * renders a persistent, dismissible admin notice on all screens, adds an admin
 * bar warning (front + back), and handles the AJAX write-back.
 */
final class VerificationNotice {

	private const CAPABILITY         = 'manage_options';
	private const SETTINGS_SLUG      = 'responsivevoice-text-to-speech';
	private const ACTION             = 'rvtts_verify';
	private const HANDLE             = 'rvtts-verify';
	private const UPGRADE_DISMISS_ID = 'v2-upgrade';
	private const CONFIG_URL         = 'https://texttospeech.responsivevoice.org/v2/config';
	private const VERIFY_URL         = 'https://app.responsivevoice.org';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Verification state.
	 *
	 * @var Verification
	 */
	private Verification $verification;

	/**
	 * Per-user dismissal store (for the upgrade upsell).
	 *
	 * @var Dismissal
	 */
	private Dismissal $dismissal;

	/**
	 * Constructor.
	 *
	 * @param Settings     $settings     Settings accessor.
	 * @param Verification $verification Verification state.
	 * @param Dismissal    $dismissal    Per-user dismissal store.
	 */
	public function __construct( Settings $settings, Verification $verification, Dismissal $dismissal ) {
		$this->settings     = $settings;
		$this->verification = $verification;
		$this->dismissal    = $dismissal;
	}

	/**
	 * Hook into WordPress. Admin-only hooks self-gate; `admin_bar_menu` also
	 * fires on the front end so logged-in admins see the warning there too.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'render_notice' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar' ), 100 );
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_store' ) );
	}

	/**
	 * Enqueue the browser-side verification probe on every admin page (for
	 * capable users with a key set); it reports back via handle_store().
	 */
	public function enqueue(): void {
		if ( ! current_user_can( self::CAPABILITY ) || '' === $this->settings->get_api_key() ) {
			return;
		}

		$file  = RVTTS_PLUGIN_DIR . 'build/verify.asset.php';
		$asset = file_exists( $file ) ? (array) ( require $file ) : array();
		$ver   = (string) ( $asset['version'] ?? RVTTS_VERSION );
		$deps  = (array) ( $asset['dependencies'] ?? array() );

		wp_enqueue_script( self::HANDLE, RVTTS_PLUGIN_URL . 'build/verify.js', $deps, $ver, true );
		wp_localize_script(
			self::HANDLE,
			'rvttsVerify',
			array(
				'apiKey'    => $this->settings->get_api_key(),
				'configUrl' => self::CONFIG_URL,
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'action'    => self::ACTION,
				'nonce'     => wp_create_nonce( self::ACTION ),
				'verified'  => $this->verification->is_verified(),
			)
		);
	}

	/**
	 * Print the persistent CTA on any admin screen while the site is unverified.
	 * Dismissible per page view (WordPress re-shows it on the next load), so it
	 * keeps nudging until verification actually succeeds.
	 */
	public function render_notice(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		// The plugin's own settings page carries richer, contextual status notices;
		// don't stack a site-wide copy on top of them there.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'toplevel_page_' . self::SETTINGS_SLUG === $screen->id ) {
			return;
		}

		// Verify (blocker) and upgrade (promo) are mutually exclusive by account state.
		if ( $this->verification->needs_cta() ) {
			printf(
				'<div class="notice notice-warning is-dismissible rvtts-verify-notice">'
				. '<p><strong>%1$s</strong> %2$s</p>'
				. '<p><a class="button button-primary" href="%3$s" target="_blank" rel="noopener noreferrer">%4$s</a></p>'
				. '</div>',
				esc_html__( 'ResponsiveVoice:', 'responsivevoice-text-to-speech' ),
				esc_html__( 'Your website isn\'t verified yet, so text to speech won\'t work — listen buttons and the WebPlayer stay silent. Verify this site\'s domain in your ResponsiveVoice dashboard to activate them.', 'responsivevoice-text-to-speech' ),
				esc_url( self::VERIFY_URL ),
				esc_html__( 'Verify your website', 'responsivevoice-text-to-speech' )
			);
			return;
		}

		// The upgrade nudge is an upsell, so it must be dismissible-for-good
		// (Guideline 11), unlike the verify blocker above, which self-clears.
		if ( $this->verification->needs_upgrade_notice() && ! $this->dismissal->is_dismissed( self::UPGRADE_DISMISS_ID ) ) {
			$this->dismissal->enqueue();
			printf(
				'<div class="notice notice-info is-dismissible rvtts-upgrade-notice" data-rvtts-dismiss="%1$s">'
				. '<p><strong>%2$s</strong> %3$s</p>'
				. '<p><a class="button button-primary" href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a></p>'
				. '</div>',
				esc_attr( self::UPGRADE_DISMISS_ID ),
				esc_html__( 'ResponsiveVoice:', 'responsivevoice-text-to-speech' ),
				esc_html__( "You're on the older version. Upgrade to ResponsiveVoice 2.0 — it's free — for 100+ voices across 50+ languages, including premium neural voices.", 'responsivevoice-text-to-speech' ),
				esc_url( self::VERIFY_URL ),
				esc_html__( 'Upgrade for free', 'responsivevoice-text-to-speech' )
			);
		}
	}

	/**
	 * Add a warning node to the admin toolbar (front + back) while unverified.
	 *
	 * @param WP_Admin_Bar $bar The admin bar.
	 */
	public function admin_bar( WP_Admin_Bar $bar ): void {
		if ( ! current_user_can( self::CAPABILITY ) || ! $this->verification->needs_cta() ) {
			return;
		}

		$bar->add_node(
			array(
				'id'    => self::HANDLE,
				'title' => '⚠ ' . esc_html__( 'Verify your website', 'responsivevoice-text-to-speech' ),
				'href'  => self::VERIFY_URL,
				'meta'  => array(
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
					'title'  => esc_attr__( 'ResponsiveVoice needs your website verified for text to speech to play.', 'responsivevoice-text-to-speech' ),
				),
			)
		);
	}

	/**
	 * AJAX endpoint: persist the browser probe outcome, then return the state.
	 */
	public function handle_store(): void {
		if ( ! $this->store_from_request() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		wp_send_json_success( array( 'verified' => $this->verification->is_verified() ) );
	}

	/**
	 * Validate and persist a verification write from the current request.
	 * Returns false (without dying) on a capability or nonce failure so callers,
	 * and tests, can branch on the outcome.
	 */
	public function store_from_request(): bool {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		if ( ! check_ajax_referer( self::ACTION, false, false ) ) {
			return false;
		}

		$verified = isset( $_POST['verified'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['verified'] ) );
		// The probe posts sdk; the SDK-auth-error path omits it, so keep
		// the tier we already know (a URL change unverifies, it doesn't change tier).
		$sdk = isset( $_POST['sdk'] )
			? sanitize_text_field( wp_unslash( $_POST['sdk'] ) )
			: $this->verification->sdk_version();
		// The probe reports validity (a 200 from /v2/config); default true when omitted.
		$valid = ! isset( $_POST['valid'] ) || '1' === sanitize_text_field( wp_unslash( $_POST['valid'] ) );

		$this->verification->store( $verified, $sdk, $valid );

		return true;
	}
}
