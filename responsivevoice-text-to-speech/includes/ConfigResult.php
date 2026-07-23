<?php
/**
 * Result of a /v2/config probe.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object describing the outcome of a `/v2/config` probe.
 */
final class ConfigResult {

	private const MANAGED_LEVEL = 0;

	/**
	 * Whether the key validated.
	 *
	 * @var bool
	 */
	private bool $valid;

	/**
	 * The site's SDK version ('v1'|'v2') or null when unknown.
	 *
	 * @var string|null
	 */
	private ?string $sdk_version;

	/**
	 * Server-provided WebPlayer config.
	 *
	 * @var array<string, mixed>
	 */
	private array $web_player;

	/**
	 * The server-reported account level, or null when it did not report one.
	 *
	 * @var int|null
	 */
	private ?int $level;

	/**
	 * Constructor.
	 *
	 * @param bool                 $valid       Whether the key validated.
	 * @param string|null          $sdk_version SDK version or null.
	 * @param array<string, mixed> $web_player  WebPlayer config.
	 * @param int|null             $level       Account level or null.
	 */
	private function __construct( bool $valid, ?string $sdk_version, array $web_player, ?int $level ) {
		$this->valid       = $valid;
		$this->sdk_version = $sdk_version;
		$this->web_player  = $web_player;
		$this->level       = $level;
	}

	/**
	 * An invalid/blocked result.
	 */
	public static function invalid(): self {
		return new self( false, null, array(), null );
	}

	/**
	 * A valid result.
	 *
	 * @param string|null          $sdk_version SDK version or null.
	 * @param array<string, mixed> $web_player  WebPlayer config.
	 * @param int|null             $level       Account level or null.
	 */
	public static function valid( ?string $sdk_version, array $web_player, ?int $level = null ): self {
		return new self( true, $sdk_version, $web_player, $level );
	}

	/**
	 * Rebuild from a cached array.
	 *
	 * @param array<string, mixed> $data Cached payload.
	 */
	public static function from_array( array $data ): self {
		return new self(
			! empty( $data['valid'] ),
			isset( $data['sdk_version'] ) ? (string) $data['sdk_version'] : null,
			isset( $data['web_player'] ) && is_array( $data['web_player'] ) ? $data['web_player'] : array(),
			isset( $data['level'] ) ? (int) $data['level'] : null
		);
	}

	/**
	 * Serialize for the transient cache.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'valid'       => $this->valid,
			'sdk_version' => $this->sdk_version,
			'web_player'  => $this->web_player,
			'level'       => $this->level,
		);
	}

	/**
	 * Whether the key validated.
	 */
	public function is_valid(): bool {
		return $this->valid;
	}

	/**
	 * SDK version ('v1'|'v2') or null.
	 */
	public function sdk_version(): ?string {
		return $this->sdk_version;
	}

	/**
	 * Server WebPlayer config.
	 *
	 * @return array<string, mixed>
	 */
	public function web_player(): array {
		return $this->web_player;
	}

	/**
	 * The server-reported account level, or null when it did not report one.
	 */
	public function level(): ?int {
		return $this->level;
	}

	/**
	 * Whether the server manages this account's player appearance.
	 */
	public function appearance_managed(): bool {
		return self::MANAGED_LEVEL === $this->level;
	}
}
