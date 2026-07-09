<?php
/**
 * /v2/config probe.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the site's config from the RV TTS API.
 *
 * The probe is secret-free: it sends only the API key as `X-API-Key` to
 * `/v2/config` endpoint. fetch() makes the HTTP request on the admin side,
 * on settings load or a re-check, and caches the result. cached() only
 * reads that cache and never hits the network, so it's safe to call on the frontend.
 */
final class ConfigClient {

	private const ENDPOINT       = 'https://texttospeech.responsivevoice.org/v2/config';
	private const TRANSIENT_BASE = 'rvtts_config_';
	private const STORE_BASE     = 'rvtts_config_store_';
	private const TTL            = 300;

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Probe the API (or return the cache) and refresh the cache.
	 *
	 * @param bool $force Bypass the cache and re-probe.
	 */
	public function fetch( bool $force = false ): ConfigResult {
		$key = $this->settings->get_api_key();
		if ( '' === $key ) {
			return ConfigResult::invalid();
		}

		if ( ! $force ) {
			$cached = $this->cached();
			if ( null !== $cached ) {
				return $cached;
			}
		}

		$result = $this->probe( $key );
		set_transient( $this->transient_key( $key ), $result->to_array(), self::TTL );

		// Persist the last *valid* probe so the front end always has a
		// complete server config to merge, even once the short-lived transient
		// expires (a failed probe must never wipe the last-known-good).
		if ( $result->is_valid() ) {
			update_option( $this->store_key( $key ), $result->to_array(), false );
		}

		return $result;
	}

	/**
	 * The most current usable config without making a request: the short-lived probe
	 * cache when it's valid, otherwise the last-known-good we persisted on the
	 * most recent successful admin probe. That way engine routing and the
	 * WebPlayer config survive a cold transient, so a v2 account stays v2 between
	 * admin visits. It returns an invalid result for an empty key, and null when
	 * there's a key but nothing cached or stored.
	 */
	public function resolved(): ?ConfigResult {
		$key = $this->settings->get_api_key();
		if ( '' === $key ) {
			return ConfigResult::invalid();
		}

		$cached = $this->cached();
		if ( null !== $cached && $cached->is_valid() ) {
			return $cached;
		}

		$stored = get_option( $this->store_key( $key ), array() );

		return ( is_array( $stored ) && ! empty( $stored ) )
			? ConfigResult::from_array( $stored )
			: $cached;
	}

	/**
	 * The server WebPlayer config from the best source available (see resolved()).
	 *
	 * @return array<string, mixed>
	 */
	public function web_player_base(): array {
		$resolved = $this->resolved();

		return ( null !== $resolved && $resolved->is_valid() ) ? $resolved->web_player() : array();
	}

	/**
	 * Read the cached result without making a request. Null when uncached.
	 */
	public function cached(): ?ConfigResult {
		$key = $this->settings->get_api_key();
		if ( '' === $key ) {
			return ConfigResult::invalid();
		}

		$cached = get_transient( $this->transient_key( $key ) );

		return is_array( $cached ) ? ConfigResult::from_array( $cached ) : null;
	}

	/**
	 * Drop the cached result (e.g. on an admin "re-check key" action).
	 */
	public function flush(): void {
		$key = $this->settings->get_api_key();
		if ( '' !== $key ) {
			delete_transient( $this->transient_key( $key ) );
			delete_option( $this->store_key( $key ) );
		}
	}

	/**
	 * Delete the cached probe for a given API key.
	 *
	 * @param string $api_key API key to purge.
	 */
	public function purge( string $api_key ): void {
		if ( '' !== $api_key ) {
			delete_transient( $this->transient_key( $api_key ) );
			delete_option( $this->store_key( $api_key ) );
		}
	}

	/**
	 * Perform the HTTP probe and map it to a result.
	 *
	 * @param string $key API key.
	 */
	private function probe( string $key ): ConfigResult {
		$response = wp_remote_get(
			self::ENDPOINT,
			array(
				'timeout' => 5,
				'headers' => array( 'X-API-Key' => $key ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return ConfigResult::invalid();
		}

		if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// 401/403 = invalid/blocked; anything non-200 is treated as unusable.
			return ConfigResult::invalid();
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return ConfigResult::invalid();
		}

		$sdk_version = isset( $body['sdkVersion'] ) ? (string) $body['sdkVersion'] : null;
		$web_player  = ( isset( $body['features']['webPlayer'] ) && is_array( $body['features']['webPlayer'] ) )
			? $body['features']['webPlayer']
			: array();
		$paid        = isset( $body['analytics']['enabled'] ) ? (bool) $body['analytics']['enabled'] : false;

		return ConfigResult::valid( $sdk_version, $web_player, $paid );
	}

	/**
	 * Transient key for an API key.
	 *
	 * @param string $key API key.
	 */
	private function transient_key( string $key ): string {
		return self::TRANSIENT_BASE . md5( $key );
	}

	/**
	 * Durable last-known-good option key for an API key.
	 *
	 * @param string $key API key.
	 */
	private function store_key( string $key ): string {
		return self::STORE_BASE . md5( $key );
	}
}
