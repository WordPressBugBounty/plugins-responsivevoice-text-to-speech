<?php
/**
 * SDK runtime bootstrap (v2).
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Boots the v2 SDK on the front end. The core library is enqueued by
 * AssetManager; this adds the init script and an element carrying the apiKey
 * (always, so the SDK boots site-wide for buttons, shortcodes and demo mode),
 * plus the WebPlayerEngine feature payload when a keyed, renderable view should
 * mount the auto-player.
 * As part of handling strict CSPs, only the core-library origin (if CDN is used)
 * needs to be added.
 */
final class SdkRuntime {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * The WebPlayer feature (payload provider).
	 *
	 * @var WebPlayerEngine
	 */
	private WebPlayerEngine $web_player;

	/**
	 * Whether the mount slot has already been injected this request (guards against
	 * themes/plugins that run `the_content` more than once).
	 *
	 * @var bool
	 */
	private bool $slot_injected = false;

	/**
	 * Constructor.
	 *
	 * @param Settings        $settings   Settings accessor.
	 * @param WebPlayerEngine $web_player WebPlayer feature.
	 */
	public function __construct( Settings $settings, WebPlayerEngine $web_player ) {
		$this->settings   = $settings;
		$this->web_player = $web_player;
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_footer', array( $this, 'render' ) );
		add_filter( 'the_content', array( $this, 'inject_slot' ) );
	}

	/**
	 * Inject the centred mount slot at the top of the main post content, only when
	 * the player will mount there at its default slot position.
	 *
	 * @param string $content Post content HTML.
	 * @return string
	 */
	public function inject_slot( $content ): string {
		$content = (string) $content;

		if ( $this->slot_injected || ! in_the_loop() || ! is_main_query() || ! $this->slot_should_render() ) {
			return $content;
		}

		$this->slot_injected = true;

		return sprintf(
			'<div class="%s"></div>',
			esc_attr( ltrim( Settings::WEBPLAYER_SLOT_SELECTOR, '.' ) )
		) . $content;
	}

	/**
	 * Whether the player will mount on this view with its position targeting the slot.
	 */
	private function slot_should_render(): bool {
		$payload = $this->web_player->payload();
		if ( null === $payload ) {
			return false;
		}

		$position = $payload['position'] ?? null;

		return is_array( $position )
			&& isset( $position['target'] )
			&& Settings::WEBPLAYER_SLOT_SELECTOR === $position['target'];
	}

	/**
	 * Enqueue the SDK-init script (depends on the core library).
	 *
	 * Site-wide, not just on renderable views: it boots the SDK for buttons,
	 * shortcodes and demo mode (no key) on every page.
	 */
	public function enqueue(): void {
		$file = RVTTS_PLUGIN_DIR . 'build/sdk.asset.php';
		$meta = file_exists( $file ) ? require $file : array();
		$deps = isset( $meta['dependencies'] ) && is_array( $meta['dependencies'] ) ? $meta['dependencies'] : array();
		$ver  = isset( $meta['version'] ) ? (string) $meta['version'] : RVTTS_VERSION;

		wp_enqueue_script(
			'rvtts-sdk',
			RVTTS_PLUGIN_URL . 'build/sdk.js',
			array_merge( $deps, array( AssetManager::CORE_HANDLE ) ),
			$ver,
			true
		);

		// Front-end style: only when the auto-player will mount (its host exists),
		// to shield it from block themes' blanket :focus outline bleed.
		if ( null !== $this->web_player->payload() ) {
			wp_enqueue_style(
				'rvtts-webplayer',
				RVTTS_PLUGIN_URL . 'build/sdk.css',
				array(),
				$ver
			);
			wp_style_add_data( 'rvtts-webplayer', 'rtl', 'replace' );
		}
	}

	/**
	 * Emit the boot element the init script reads: the apiKey always (empty = demo
	 * mode), plus the WebPlayer feature payload when one should mount.
	 */
	public function render(): void {
		$api_key = $this->settings->get_api_key();
		$payload = $this->web_player->payload();

		if ( null !== $payload ) {
			printf(
				'<div id="rvtts-sdk" data-rvtts-apikey="%1$s" data-rvtts-webplayer="%2$s"></div>',
				esc_attr( $api_key ),
				esc_attr( (string) wp_json_encode( $payload ) )
			);
			return;
		}

		printf(
			'<div id="rvtts-sdk" data-rvtts-apikey="%1$s"></div>',
			esc_attr( $api_key )
		);
	}
}
