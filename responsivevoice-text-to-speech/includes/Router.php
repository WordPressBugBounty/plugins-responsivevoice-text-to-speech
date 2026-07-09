<?php
/**
 * Render-engine routing.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Decides which render engine is active for the current request.
 *
 * With no API key we still return the webplayer engine, so buttons and
 * shortcodes get the v2 SDK in demo mode. The WebPlayer itself stays off until
 * a key is set (see WebPlayerEngine::payload()). With a key set, WebPlayer runs
 * for v2 accounts, and for v1 accounts that opted in; everything else is legacy.
 * Reads cached config only, so there's no front-end HTTP.
 */
final class Router {

	public const ENGINE_LEGACY    = 'legacy';
	public const ENGINE_WEBPLAYER = 'webplayer';

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
	 * The engine to use for the current request.
	 */
	public function active_engine(): string {
		if ( '' === $this->settings->get_api_key() ) {
			return self::ENGINE_WEBPLAYER;
		}

		$resolved = $this->config->resolved();
		$sdk      = null !== $resolved ? $resolved->sdk_version() : null;

		return ( 'v2' === $sdk || $this->settings->is_v2_optin() )
			? self::ENGINE_WEBPLAYER
			: self::ENGINE_LEGACY;
	}
}
