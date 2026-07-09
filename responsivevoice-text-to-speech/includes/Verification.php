<?php
/**
 * Website-verification state.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Tracks whether the site's origin is verified in the RV Dashboard.
 *
 * "Verified" means `/v2/config` returns an `auth` token, but that field is
 * origin-gated, so it's only visible to a browser request made from the site's
 * own origin (the server-side ConfigClient probe never sees it).
 * An admin script performs that browser probe and persists the outcome
 * here so the server can render a site-wide "verify your website" CTA and the
 * settings page can report the true status.
 */
final class Verification {

	public const OPTION = 'rvtts_verification';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Config probe (cache-only reads here; front-end safe).
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
	 * Verification state for the current key: true (verified), false
	 * (unverified) or null (never probed, or the key changed since).
	 */
	public function is_verified(): ?bool {
		$state = $this->state();

		return null === $state ? null : (bool) $state['verified'];
	}

	/**
	 * The SDK version the client last saw for the current key, or null.
	 */
	public function sdk_version(): ?string {
		$state = $this->state();

		return ( null !== $state && '' !== (string) $state['sdk'] ) ? (string) $state['sdk'] : null;
	}

	/**
	 * When the client last probed (unix time), or 0 when never.
	 */
	public function checked_at(): int {
		$state = $this->state();

		return null === $state ? 0 : (int) $state['checked_at'];
	}

	/**
	 * Whether the key last probed as valid (a 200 from `/v2/config`): true, false
	 * (a bad key), or null when never probed / the key changed.
	 */
	public function is_valid(): ?bool {
		$state = $this->state();

		return null === $state ? null : (bool) $state['valid'];
	}

	/**
	 * Persist a browser-probe outcome for the current key.
	 *
	 * @param bool        $verified Whether `/v2/config` returned an auth token.
	 * @param string|null $sdk      SDK version reported alongside it.
	 * @param bool        $valid    Whether the probe itself succeeded (HTTP 200).
	 */
	public function store( bool $verified, ?string $sdk = null, bool $valid = true ): void {
		update_option(
			self::OPTION,
			array(
				'verified'   => $verified,
				'sdk'        => (string) $sdk,
				'valid'      => $valid,
				'key'        => $this->key_hash(),
				'checked_at' => time(),
			)
		);
	}

	/**
	 * Whether a persistent "verify your website" CTA is warranted: a keyed
	 * WebPlayer account (v2 or a v1 opt-in) whose origin isn't confirmed verified.
	 */
	public function needs_cta(): bool {
		if ( '' === $this->settings->get_api_key() ) {
			return false;
		}

		if ( ! $this->is_webplayer_account() ) {
			return false;
		}

		// A key that probed as invalid (non-200) is a "bad key" situation, not an
		// "unverified" one, so don't prompt to verify for it even with the opt-in on.
		if ( false === $this->is_valid() ) {
			return false;
		}

		return true !== $this->is_verified();
	}

	/**
	 * Whether to nudge a plain v1 account to upgrade: a confirmed-valid v1 key
	 * (or one that omits sdkVersion) that hasn't opted into the v2 preview. v2
	 * accounts and v1 opt-in previews are handled by needs_cta() instead, so the
	 * two notices are mutually exclusive.
	 */
	public function needs_upgrade_notice(): bool {
		if ( '' === $this->settings->get_api_key() ) {
			return false;
		}

		if ( $this->settings->is_v2_optin() ) {
			return false;
		}

		// Only nudge once a probe has confirmed the key is valid but not v2.
		if ( true !== $this->is_valid() ) {
			return false;
		}

		return 'v2' !== $this->sdk_version();
	}

	/**
	 * Whether the account runs the WebPlayer: a v2 account (from the stored
	 * probe or the cached server probe) or a v1 account that opted in.
	 */
	private function is_webplayer_account(): bool {
		if ( $this->settings->is_v2_optin() ) {
			return true;
		}

		if ( 'v2' === $this->sdk_version() ) {
			return true;
		}

		$cached = $this->config->cached();

		return null !== $cached && 'v2' === $cached->sdk_version();
	}

	/**
	 * Normalised stored state for the current key, or null when absent/stale.
	 *
	 * @return array{verified: bool, sdk: string, valid: bool, key: string, checked_at: int}|null
	 */
	private function state(): ?array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) || ! isset( $stored['verified'], $stored['key'] ) ) {
			return null;
		}

		// A key change invalidates a prior verification (different website identity).
		if ( (string) $stored['key'] !== $this->key_hash() ) {
			return null;
		}

		return array(
			'verified'   => (bool) $stored['verified'],
			'sdk'        => isset( $stored['sdk'] ) ? (string) $stored['sdk'] : '',
			// Older records predate the flag; treat a stored record as valid then.
			'valid'      => ! isset( $stored['valid'] ) || (bool) $stored['valid'],
			'key'        => (string) $stored['key'],
			'checked_at' => isset( $stored['checked_at'] ) ? (int) $stored['checked_at'] : 0,
		);
	}

	/**
	 * Stable, non-reversible fingerprint of the current API key.
	 */
	private function key_hash(): string {
		$key = $this->settings->get_api_key();

		return '' === $key ? '' : md5( $key );
	}
}
