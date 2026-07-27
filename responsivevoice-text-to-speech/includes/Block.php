<?php
/**
 * Block registration.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the built Gutenberg block and, in the editor, loads the account's
 * engine core so the block's inspector can list the SDK's voices live.
 */
final class Block {

	/**
	 * The editor script handle register_block_type() generates from block.json.
	 */
	private const EDITOR_HANDLE = 'rvtts-listen-button-editor-script';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Engine router (v1 vs v2).
	 *
	 * @var Router
	 */
	private Router $router;

	/**
	 * Core-library URL resolver.
	 *
	 * @var AssetManager
	 */
	private AssetManager $assets;

	/**
	 * Constructor.
	 *
	 * @param Settings     $settings Settings accessor.
	 * @param Router       $router   Engine router.
	 * @param AssetManager $assets   Core-library URL resolver.
	 */
	public function __construct( Settings $settings, Router $router, AssetManager $assets ) {
		$this->settings = $settings;
		$this->router   = $router;
		$this->assets   = $assets;
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_block' ) );
		add_filter( 'block_type_metadata', array( $this, 'stamp_version' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Version the block's assets from the plugin version rather than a
	 * hand-maintained block.json field.
	 *
	 * @param array<string, mixed> $metadata Block metadata read from block.json.
	 * @return array<string, mixed>
	 */
	public function stamp_version( array $metadata ): array {
		if ( 'rvtts/listen-button' === ( $metadata['name'] ?? '' ) ) {
			$metadata['version'] = RVTTS_VERSION;
		}

		return $metadata;
	}

	/**
	 * Register the block from its built metadata.
	 */
	public function register_block(): void {
		$dir = RVTTS_PLUGIN_DIR . 'build/block';

		if ( file_exists( $dir . '/block.json' ) ) {
			register_block_type( $dir );
		}
	}

	/**
	 * Class and style attributes for the block's button element.
	 *
	 * Colour, border, typography and padding are marked skipped in block.json, so
	 * get_block_wrapper_attributes() leaves them to us. Margin isn't skipped and stays
	 * on the wrapper as the block's outer spacing. Presets reach the style engine as
	 * `var:preset|...` strings, the way core's own support handlers build them.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string Attribute string for the button element.
	 */
	public static function button_attributes( array $attributes ): string {
		$style = isset( $attributes['style'] ) && is_array( $attributes['style'] )
			? $attributes['style']
			: array();

		$preset = static function ( string $key, string $kind ) use ( $attributes ): ?string {
			return isset( $attributes[ $key ] ) && '' !== $attributes[ $key ]
				? 'var:preset|' . $kind . '|' . $attributes[ $key ]
				: null;
		};

		$block_styles = array(
			'color'      => array(
				'text'       => $preset( 'textColor', 'color' ) ?? ( $style['color']['text'] ?? null ),
				'background' => $preset( 'backgroundColor', 'color' ) ?? ( $style['color']['background'] ?? null ),
				'gradient'   => $preset( 'gradient', 'gradient' ) ?? ( $style['color']['gradient'] ?? null ),
			),
			'border'     => is_array( $style['border'] ?? null ) ? $style['border'] : array(),
			'typography' => is_array( $style['typography'] ?? null ) ? $style['typography'] : array(),
			'spacing'    => array( 'padding' => $style['spacing']['padding'] ?? null ),
		);

		$border_color = $preset( 'borderColor', 'color' );
		if ( null !== $border_color ) {
			$block_styles['border']['color'] = $border_color;
		}

		$font_size = $preset( 'fontSize', 'font-size' );
		if ( null !== $font_size ) {
			$block_styles['typography']['fontSize'] = $font_size;
		}

		$styles = wp_style_engine_get_styles( $block_styles, array( 'convert_vars_to_classnames' => true ) );

		$classes = 'responsivevoice-button wp-block-rvtts-listen-button__button';
		if ( ! empty( $styles['classnames'] ) ) {
			$classes .= ' ' . $styles['classnames'];
		}

		$attribute_string = 'class="' . esc_attr( $classes ) . '"';
		if ( ! empty( $styles['css'] ) ) {
			$attribute_string .= ' style="' . esc_attr( $styles['css'] ) . '"';
		}

		return $attribute_string;
	}

	/**
	 * In the block editor, load the same engine core the frontend uses so the
	 * inspector's voice picker can read `getVoices()` live. Gated to a keyed
	 * account (an unkeyed site only has the browser voice, so free text is fine).
	 */
	public function enqueue_block_editor_assets(): void {
		$api_key = $this->settings->get_api_key();
		if ( '' === $api_key ) {
			return;
		}

		// External CDN/library; upstream-versioned, so no ?ver is appended.
		wp_register_script( AssetManager::CORE_HANDLE, $this->assets->core_url(), array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( AssetManager::CORE_HANDLE );

		wp_localize_script(
			self::EDITOR_HANDLE,
			'rvttsBlockData',
			array(
				'apiKey' => $api_key,
				'engine' => Router::ENGINE_WEBPLAYER === $this->router->active_engine() ? 'v2' : 'v1',
			)
		);
	}
}
