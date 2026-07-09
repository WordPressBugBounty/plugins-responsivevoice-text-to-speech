<?php
/**
 * Per-post WebPlayer override meta box.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice\Admin;

use WP_Post;
use ResponsiveVoice\Settings;
use ResponsiveVoice\Router;
use ResponsiveVoice\WebPlayerEngine;

defined( 'ABSPATH' ) || exit;

/**
 * Adds an on/off/default WebPlayer override to enabled post types.
 */
final class MetaBox {

	private const NONCE_ACTION = 'rvtts_metabox';
	private const NONCE_NAME   = 'rvtts_metabox_nonce';
	private const FIELD        = 'rvtts_webplayer';

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
	 * WebPlayer feature.
	 *
	 * @var WebPlayerEngine
	 */
	private WebPlayerEngine $web_player;

	/**
	 * Constructor.
	 *
	 * @param Settings        $settings   Settings accessor.
	 * @param Router          $router     Engine router.
	 * @param WebPlayerEngine $web_player WebPlayer feature.
	 */
	public function __construct( Settings $settings, Router $router, WebPlayerEngine $web_player ) {
		$this->settings   = $settings;
		$this->router     = $router;
		$this->web_player = $web_player;
	}

	/**
	 * Hook into WordPress only when the WebPlayer will run.
	 */
	public function register(): void {
		if ( ! $this->should_display() ) {
			return;
		}
		add_action( 'add_meta_boxes', array( $this, 'add' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 1 );
	}

	/**
	 * Whether the per-post override should appear: WebPlayer is the active engine
	 * and the master enable is on.
	 */
	public function should_display(): bool {
		return Router::ENGINE_WEBPLAYER === $this->router->active_engine()
			&& '' !== $this->settings->get_api_key()
			&& $this->web_player->is_enabled();
	}

	/**
	 * Register the meta box on every manageable type, so overrides work on
	 * default-off types too.
	 */
	public function add(): void {
		foreach ( array_keys( $this->settings->get_manageable_post_types() ) as $type ) {
			add_meta_box(
				'rvtts-webplayer',
				__( 'ResponsiveVoice WebPlayer', 'responsivevoice-text-to-speech' ),
				array( $this, 'render' ),
				$type,
				'side'
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param WP_Post $post Current post.
	 */
	public function render( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$current = (string) get_post_meta( $post->ID, WebPlayerEngine::META_KEY, true );

		$choices = array(
			''    => __( 'Use default', 'responsivevoice-text-to-speech' ),
			'on'  => __( 'Enabled', 'responsivevoice-text-to-speech' ),
			'off' => __( 'Disabled', 'responsivevoice-text-to-speech' ),
		);

		$options = '';
		foreach ( $choices as $value => $label ) {
			$options .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $current, $value, false ),
				esc_html( $label )
			);
		}

		printf(
			'<p><select name="%1$s">%2$s</select></p>',
			esc_attr( self::FIELD ),
			$options // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr/esc_html parts above.
		);
	}

	/**
	 * Persist the override.
	 *
	 * @param int $post_id Post being saved.
	 */
	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}
		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}
		// Revisions must not write through to the parent
		// (update/delete_post_meta redirect a revision ID to its parent post).
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$value = isset( $_POST[ self::FIELD ] ) ? sanitize_key( wp_unslash( $_POST[ self::FIELD ] ) ) : '';

		if ( in_array( $value, array( 'on', 'off' ), true ) ) {
			update_post_meta( $post_id, WebPlayerEngine::META_KEY, $value );
		} else {
			delete_post_meta( $post_id, WebPlayerEngine::META_KEY );
		}
	}
}
