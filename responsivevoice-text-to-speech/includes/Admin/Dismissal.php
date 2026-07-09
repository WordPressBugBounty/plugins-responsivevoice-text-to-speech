<?php
/**
 * Per-user dismissal state for admin notices.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Persists a "the current user dismissed notice X" flag in user meta, and
 * handles the AJAX write-back a dismissible notice posts when its close button
 * is clicked. Standalone so any notice (e.g. the announcement banner) can gate
 * itself on is_dismissed() without re-implementing the capability/nonce
 * logic.
 */
final class Dismissal {

	public const ACTION = 'rvtts_dismiss';
	public const HANDLE = 'rvtts-dismiss';

	private const CAPABILITY  = 'manage_options';
	private const META_PREFIX = 'rvtts_dismissed_';

	/**
	 * Hook the AJAX endpoint. Callers enqueue the client via enqueue().
	 */
	public function register(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle_dismiss' ) );
	}

	/**
	 * Whether the given user dismissed a notice.
	 *
	 * @param string   $id      Notice identifier.
	 * @param int|null $user_id User; defaults to the current user.
	 */
	public function is_dismissed( string $id, ?int $user_id = null ): bool {
		$user_id = $user_id ?? get_current_user_id();

		return '1' === get_user_meta( $user_id, self::meta_key( $id ), true );
	}

	/**
	 * Record that the given user dismissed a notice.
	 *
	 * @param string   $id      Notice identifier.
	 * @param int|null $user_id User; defaults to the current user.
	 */
	public function dismiss( string $id, ?int $user_id = null ): void {
		$user_id = $user_id ?? get_current_user_id();

		update_user_meta( $user_id, self::meta_key( $id ), '1' );
	}

	/**
	 * Clear a dismissal (re-shows the notice for that user).
	 *
	 * @param string   $id      Notice identifier.
	 * @param int|null $user_id User; defaults to the current user.
	 */
	public function undismiss( string $id, ?int $user_id = null ): void {
		$user_id = $user_id ?? get_current_user_id();

		delete_user_meta( $user_id, self::meta_key( $id ) );
	}

	/**
	 * Enqueue the client that posts a dismissal when a notice's close button is
	 * clicked. Callers invoke this while rendering a dismissible notice.
	 */
	public function enqueue(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		$file  = RVTTS_PLUGIN_DIR . 'build/dismiss.asset.php';
		$asset = file_exists( $file ) ? (array) ( require $file ) : array();
		$ver   = (string) ( $asset['version'] ?? RVTTS_VERSION );
		$deps  = (array) ( $asset['dependencies'] ?? array() );

		wp_enqueue_script( self::HANDLE, RVTTS_PLUGIN_URL . 'build/dismiss.js', $deps, $ver, true );
		wp_localize_script(
			self::HANDLE,
			'rvttsDismiss',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::ACTION,
				'nonce'   => wp_create_nonce( self::ACTION ),
			)
		);
	}

	/**
	 * AJAX endpoint: persist the dismissal, then acknowledge.
	 */
	public function handle_dismiss(): void {
		if ( ! $this->dismiss_from_request() ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}

		wp_send_json_success();
	}

	/**
	 * Validate and persist a dismissal from the current request. Returns false
	 * (without dying) on a capability, nonce, or missing-id failure so callers
	 * and tests can branch on the outcome.
	 */
	public function dismiss_from_request(): bool {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return false;
		}

		if ( ! check_ajax_referer( self::ACTION, false, false ) ) {
			return false;
		}

		$id = isset( $_POST['notice'] ) ? sanitize_key( wp_unslash( $_POST['notice'] ) ) : '';
		if ( '' === $id ) {
			return false;
		}

		$this->dismiss( $id );

		return true;
	}

	/**
	 * User-meta key for a notice's dismissal flag.
	 *
	 * @param string $id Notice identifier.
	 */
	private static function meta_key( string $id ): string {
		return self::META_PREFIX . sanitize_key( $id );
	}

	/**
	 * Delete every user's dismissal flag. The notice-id space is open-ended, so
	 * this clears the whole `rvtts_dismissed_*` prefix rather than a fixed list.
	 * For uninstall cleanup.
	 */
	public static function purge_all(): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- one-shot uninstall cleanup of a prefixed key.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s",
				$wpdb->esc_like( self::META_PREFIX ) . '%'
			)
		);
	}
}
