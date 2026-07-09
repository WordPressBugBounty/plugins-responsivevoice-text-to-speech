<?php
/**
 * Front-end asset loading.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the ResponsiveVoice core library (v1 library or v2 core, depending
 * on the active engine) plus the static button handler.
 */
final class AssetManager {

	public const CORE_HANDLE = 'responsivevoice';

	private const V1_LIBRARY  = 'https://code.responsivevoice.org/responsivevoice.js';
	private const V2_CDN_BASE = 'https://cdn.responsivevoice.org/sdk';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Engine router.
	 *
	 * @var Router
	 */
	private Router $router;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 * @param Router   $router   Engine router.
	 */
	public function __construct( Settings $settings, Router $router ) {
		$this->settings = $settings;
		$this->router   = $router;
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend' ) );
		// Fires for the front end and the block-editor canvas iframe, so one hook
		// styles the button everywhere it renders: shortcodes, block, and preview.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_button_style' ) );
	}

	/**
	 * Enqueue the neutral listen-button stylesheet. Shared by the block, its
	 * editor preview and the legacy shortcodes (all output `.responsivevoice-button`).
	 */
	public function enqueue_button_style(): void {
		wp_enqueue_style(
			'rvtts-button',
			RVTTS_PLUGIN_URL . 'build/button.css',
			array(),
			RVTTS_VERSION
		);
		wp_style_add_data( 'rvtts-button', 'rtl', 'replace' );
	}

	/**
	 * Register the core library and enqueue the button handler.
	 *
	 * We load this on every front-end view: the core library has to be present
	 * wherever an author placed a button or shortcode, and for keyless demo mode.
	 * The rendered player markup and its CSS still stay gated per view.
	 */
	public function enqueue_frontend(): void {
		$asset = $this->asset_meta( 'frontend' );

		// External CDN library; its versioning is controlled upstream, so no ?ver is added like we used to do in v1.
		wp_register_script( self::CORE_HANDLE, $this->core_url(), array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( self::CORE_HANDLE );

		wp_enqueue_script(
			'rvtts-frontend',
			RVTTS_PLUGIN_URL . 'build/frontend.js',
			array_merge( $asset['dependencies'], array( self::CORE_HANDLE ) ),
			$asset['version'],
			true
		);
	}

	/**
	 * Resolve the core library URL for the active engine.
	 */
	public function core_url(): string {
		if ( Router::ENGINE_WEBPLAYER === $this->router->active_engine() ) {
			return $this->v2_core_url();
		}

		return $this->v1_library_url();
	}

	/**
	 * The v1 library URL, with the API key appended when configured.
	 */
	private function v1_library_url(): string {
		$key = $this->settings->get_api_key();

		return '' !== $key ? add_query_arg( 'key', rawurlencode( $key ), self::V1_LIBRARY ) : self::V1_LIBRARY;
	}

	/**
	 * The v2 core URL: a self-hosted bundle, or a CDN channel (pinned or latest).
	 */
	private function v2_core_url(): string {
		if ( Settings::DELIVERY_BUNDLED === $this->settings->get_core_delivery() ) {
			return RVTTS_PLUGIN_URL . 'build/core.js';
		}

		$version = $this->settings->get_core_version();

		return '' !== $version
			? self::V2_CDN_BASE . '/v/' . $version . '/responsivevoice.js'
			: self::V2_CDN_BASE . '/latest/responsivevoice.js';
	}

	/**
	 * Read a wp-scripts asset manifest (dependencies + content-hash version).
	 *
	 * @param string $entry Build entry name.
	 * @return array{dependencies: array<int, string>, version: string}
	 */
	private function asset_meta( string $entry ): array {
		$file = RVTTS_PLUGIN_DIR . 'build/' . $entry . '.asset.php';
		$meta = file_exists( $file ) ? require $file : array();

		return array(
			'dependencies' => isset( $meta['dependencies'] ) && is_array( $meta['dependencies'] ) ? $meta['dependencies'] : array(),
			'version'      => isset( $meta['version'] ) ? (string) $meta['version'] : RVTTS_VERSION,
		);
	}
}
