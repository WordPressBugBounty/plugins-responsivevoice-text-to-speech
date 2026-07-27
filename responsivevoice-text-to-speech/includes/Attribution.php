<?php
/**
 * Listen-button attribution.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * The link a listen button carries. Never hits the network, so the front end
 * can call it.
 */
final class Attribution {

	private const URL = 'https://responsivevoice.org/?utm_source=wordpress-plugin&utm_medium=referral&utm_campaign=powered-by';

	private const ATTRIBUTED_LEVEL = 0;

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Config probe (cached).
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
	 * Whether this site's buttons carry the link.
	 */
	public function is_visible(): bool {
		if ( '' === $this->settings->get_api_key() ) {
			return true;
		}

		$resolved = $this->config->resolved();

		return null !== $resolved && self::ATTRIBUTED_LEVEL === $resolved->level();
	}

	/**
	 * The attribution target.
	 */
	public function url(): string {
		return self::URL;
	}

	/**
	 * The attribution text.
	 */
	public function label(): string {
		return __( 'Powered by ResponsiveVoice', 'responsivevoice-text-to-speech' );
	}
}
