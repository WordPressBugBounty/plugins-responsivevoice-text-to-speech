<?php
/**
 * WebPlayer (v2) feature.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * The v2 WebPlayer feature: decides whether the auto-player should mount on the
 * current view and builds its config. Booting the SDK and emitting the front-end
 * element is SdkRuntime's job; this class attaches no WordPress hooks.
 */
final class WebPlayerEngine {

	/**
	 * Per-post override meta key ('on'|'off'|'').
	 */
	public const META_KEY = '_rvtts_webplayer';

	/**
	 * Config paths the server manages.
	 *
	 * @var array<int, string>
	 */
	public const MANAGED_PATHS = array(
		'controls.brand',
		'controls.skip',
		'controls.speed',
		'controls.time',
		'navigation.paragraphHighlight',
		'navigation.paragraphClick',
		'miniPlayer.enabled',
	);

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
	 * The WebPlayer feature payload for the current request, or null when no
	 * auto-player should mount (no key, or not a renderable view). SdkRuntime
	 * attaches this to the SDK boot when present.
	 *
	 * @return array<string, mixed>|null
	 */
	public function payload(): ?array {
		if ( '' === $this->settings->get_api_key() || ! $this->should_render() ) {
			return null;
		}

		$config = $this->web_player_config();

		// The enable "tri-state" may resolve off (mode 'off', or 'default' when the
		// server config disables the player). In that case the SDK boots with no
		// auto-player.
		if ( empty( $config['enabled'] ) ) {
			return null;
		}

		return $config;
	}

	/**
	 * Whether the WebPlayer should run on the current request.
	 *
	 * Singular views of an enabled post type, unless a per-post override flips
	 * it off; a per-post 'on' override forces it on any singular view.
	 */
	public function should_render(): bool {
		if ( ! is_singular() ) {
			return false;
		}

		$post_id  = get_queried_object_id();
		$override = $post_id ? (string) get_post_meta( $post_id, self::META_KEY, true ) : '';

		if ( 'off' === $override ) {
			return false;
		}
		if ( 'on' === $override ) {
			return true;
		}

		return in_array( (string) get_post_type( $post_id ), $this->settings->get_enabled_post_types(), true );
	}

	/**
	 * Build the WebPlayer config: server config as the base, the site owner's saved
	 * customizer settings deep-merged on top, `enabled` resolved from the "tri-state" switch,
	 * then a code-level filter for full override.
	 *
	 * @return array<string, mixed>
	 */
	public function web_player_config(): array {
		$server = $this->config->web_player_base();

		// Server config is the base; the site owner's saved customizer settings
		// (theme, position, layout, voice, colours, etc.) override it, and deep-merged so a
		// sparse override such as `layout.display` keeps the server's sibling keys.
		// The seed also carries the default selector when the owner has not set their own.
		$overrides         = $this->drop_managed_appearance( $this->settings->get_webplayer_config_seed() );
		$config            = array_replace_recursive( $server, $overrides );
		$config['enabled'] = $this->resolve_enabled( $config );

		/**
		 * Filter the WebPlayer config passed to responsiveVoice.init().
		 *
		 * @param array<string, mixed> $config Merged WebPlayer config.
		 */
		$config = apply_filters( 'rvtts_webplayer_config', $config );

		return is_array( $config ) ? $config : array();
	}

	/**
	 * The effective WebPlayer enabled state from the tri-state + server config,
	 * independent of the current view. Drives the admin customizer's visibility.
	 */
	public function is_enabled(): bool {
		return $this->resolve_enabled( $this->config->web_player_base() );
	}

	/**
	 * Drop the managed paths, and a custom palette, from the overrides. Filters the
	 * overrides only; the stored option keeps the site's saved values.
	 *
	 * @param array<string, mixed> $overrides Saved customizer overrides.
	 * @return array<string, mixed>
	 */
	private function drop_managed_appearance( array $overrides ): array {
		if ( ! $this->config->appearance_managed() ) {
			return $overrides;
		}

		foreach ( self::MANAGED_PATHS as $path ) {
			$keys = explode( '.', $path );
			$leaf = array_pop( $keys );
			$node = &$overrides;
			foreach ( $keys as $key ) {
				if ( ! isset( $node[ $key ] ) || ! is_array( $node[ $key ] ) ) {
					continue 2;
				}
				$node = &$node[ $key ];
			}
			unset( $node[ $leaf ] );
			unset( $node );
		}

		if ( isset( $overrides['theme'] ) && is_array( $overrides['theme'] ) ) {
			unset( $overrides['theme'] );
		}

		return $overrides;
	}

	/**
	 * Resolve the effective `enabled` from the tri-state: `on` forces true, `off`
	 * forces false, `default` honours the merged server value (defaults to enabled).
	 *
	 * @param array<string, mixed> $merged Server + override config before resolution.
	 */
	private function resolve_enabled( array $merged ): bool {
		$mode = $this->settings->get_webplayer_mode();

		if ( Settings::WEBPLAYER_ON === $mode ) {
			return true;
		}
		if ( Settings::WEBPLAYER_OFF === $mode ) {
			return false;
		}

		return ! isset( $merged['enabled'] ) || (bool) $merged['enabled'];
	}
}
