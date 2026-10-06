<?php
/**
 * Plugin settings.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Typed wrapper over the `RV_settings` option (preserved from v1 for migration).
 */
final class Settings {

	public const OPTION = 'RV_settings';

	public const DELIVERY_CDN     = 'cdn';
	public const DELIVERY_BUNDLED = 'bundled';

	/**
	 * WordPress-aware default mount selector. Themes rarely wrap post content in
	 * a bare <article> (block themes use .wp-block-post-content / .entry-content),
	 * so the SDK's own `article` default often matches nothing. SDK nesting
	 * protection keeps only the outermost match, so listing article first stays
	 * safe on themes that do expose it.
	 */
	public const DEFAULT_SELECTOR = 'article, .entry-content, .wp-block-post-content';

	/**
	 * Selector of the plugin-injected mount slot the default position targets;
	 * SdkRuntime injects the matching `<div>`. Keep in sync with the customizer JS.
	 */
	public const WEBPLAYER_SLOT_SELECTOR = '.rvtts-player-slot';

	/**
	 * WebPlayer enable modes: `on` forces the player (the plugin default), `off`
	 * forces it off, `default` defers to the account's `/config` value.
	 */
	public const WEBPLAYER_ON      = 'on';
	public const WEBPLAYER_OFF     = 'off';
	public const WEBPLAYER_DEFAULT = 'default';

	/**
	 * Read the stored API key
	 */
	public function get_api_key(): string {
		$options = get_option( self::OPTION, array() );

		return ( is_array( $options ) && isset( $options['RV_text_api_key'] ) )
			? (string) $options['RV_text_api_key']
			: '';
	}

	/**
	 * Whether a v1 account opted in to preview the v2 WebPlayer (reversible).
	 */
	public function is_v2_optin(): bool {
		$options = get_option( self::OPTION, array() );

		return is_array( $options ) && ! empty( $options['v2_optin'] );
	}

	/**
	 * The saved WebPlayer enable mode (a WEBPLAYER_* value, default `on`). v2
	 * accounts pick this in the customizer; v1 accounts use the opt-in instead.
	 */
	public function get_webplayer_mode(): string {
		$options = get_option( self::OPTION, array() );
		$mode    = ( is_array( $options ) && isset( $options['webplayer_mode'] ) ) ? (string) $options['webplayer_mode'] : self::WEBPLAYER_ON;

		return in_array( $mode, array( self::WEBPLAYER_ON, self::WEBPLAYER_OFF, self::WEBPLAYER_DEFAULT ), true )
			? $mode
			: self::WEBPLAYER_ON;
	}

	/**
	 * Public types with an editor UI (minus attachments), as slug => plural label.
	 *
	 * @return array<string, string>
	 */
	public function get_manageable_post_types(): array {
		$objects = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'objects'
		);
		unset( $objects['attachment'] );

		$types = array();
		foreach ( $objects as $slug => $object ) {
			$types[ (string) $slug ] = (string) $object->label;
		}

		return $types;
	}

	/**
	 * Post types the WebPlayer is enabled on (defaults to posts + pages). Stored as a
	 * `{slug => 'on'|'off'}` map; also reads the legacy plain-list.
	 *
	 * @return array<int, string>
	 */
	public function get_enabled_post_types(): array {
		$options = get_option( self::OPTION, array() );
		$stored  = ( is_array( $options ) && isset( $options['post_types'] ) && is_array( $options['post_types'] ) )
			? $options['post_types']
			: null;

		if ( null === $stored ) {
			return array( 'post', 'page' );
		}

		if ( $this->is_assoc_array( $stored ) ) {
			return array_keys(
				array_filter( $stored, static fn( $state ): bool => 'on' === $state )
			);
		}

		return array_values( array_filter( array_map( 'strval', $stored ) ) );
	}

	/**
	 * Whether an array has string/non-sequential keys (a map) rather than a plain list.
	 *
	 * @param array<mixed> $value Array to test.
	 */
	private function is_assoc_array( array $value ): bool {
		if ( array() === $value ) {
			return false;
		}

		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}

	/**
	 * Core delivery mode: CDN (default) or a self-hosted bundle.
	 */
	public function get_core_delivery(): string {
		$options  = get_option( self::OPTION, array() );
		$delivery = ( is_array( $options ) && isset( $options['core_delivery'] ) ) ? (string) $options['core_delivery'] : self::DELIVERY_CDN;

		return self::DELIVERY_BUNDLED === $delivery ? self::DELIVERY_BUNDLED : self::DELIVERY_CDN;
	}

	/**
	 * Pinned core version for the CDN, or '' for the latest channel.
	 */
	public function get_core_version(): string {
		$options = get_option( self::OPTION, array() );
		$version = ( is_array( $options ) && isset( $options['core_version'] ) ) ? (string) $options['core_version'] : '';

		return preg_match( '/^\d+\.\d+\.\d+$/', $version ) ? $version : '';
	}

	/**
	 * The saved sparse WebPlayer config (only customised keys; the SDK + /v2/config
	 * fill the rest). Empty when nothing has been customised.
	 *
	 * @return array<string, mixed>
	 */
	public function get_webplayer_config(): array {
		$options = get_option( self::OPTION, array() );
		$config  = ( is_array( $options ) && isset( $options['webplayer_config'] ) && is_array( $options['webplayer_config'] ) )
			? $options['webplayer_config']
			: array();

		return $config;
	}

	/**
	 * Saved WebPlayer overrides with the WordPress-aware default selector applied
	 * when the site owner hasn't set their own. Shared by the customizer (so the
	 * Selector field shows the real default) and the front-end renderer, so the
	 * value on screen and the value that ships never diverge.
	 *
	 * @return array<string, mixed>
	 */
	public function get_webplayer_config_seed(): array {
		$config   = $this->get_webplayer_config();
		$selector = isset( $config['selector'] ) ? trim( (string) $config['selector'] ) : '';
		if ( '' === $selector ) {
			$config['selector'] = self::DEFAULT_SELECTOR;
		}

		// Default the player into the plugin's centred slot; a saved position
		// (before/after/custom) overrides this and suppresses the slot injection.
		if ( ! isset( $config['position'] ) ) {
			$config['position'] = array(
				'target' => self::WEBPLAYER_SLOT_SELECTOR,
				'at'     => 'inside',
			);
		}

		return $config;
	}

	/**
	 * Allowed top-level keys of the WebPlayer config (mirrors WebPlayerFeatureSchema).
	 *
	 * @return array<int, string>
	 */
	private function webplayer_config_keys(): array {
		// `enabled` is intentionally absent: the tri-state `webplayer_mode` owns it,
		// so the customizer config never carries an enabled flag.
		return array( 'selector', 'paragraphSelector', 'position', 'theme', 'controls', 'navigation', 'layout', 'miniPlayer', 'sanitize', 'voice', 'pitch', 'rate', 'volume' );
	}

	/**
	 * Whether any non-default preference is stored beyond the API key, i.e. whether
	 * "Reset to defaults" has anything to clear. The API key is a credential, not a
	 * preference, so it never counts.
	 */
	public function has_custom_settings(): bool {
		$options = get_option( self::OPTION, array() );
		if ( ! is_array( $options ) ) {
			return false;
		}

		if ( ! empty( $options['webplayer_config'] ) ) {
			return true;
		}
		if ( isset( $options['webplayer_mode'] ) && self::WEBPLAYER_ON !== $options['webplayer_mode'] ) {
			return true;
		}
		if ( isset( $options['core_delivery'] ) && self::DELIVERY_CDN !== $options['core_delivery'] ) {
			return true;
		}
		if ( ! empty( $options['core_version'] ) ) {
			return true;
		}

		return ! empty( $options['v2_optin'] );
	}

	/**
	 * Reset every preference to its default, preserving only the API key (a
	 * credential the user shouldn't have to re-enter). With the preference keys
	 * gone, each getter falls back to its default.
	 */
	public function reset(): void {
		$options = get_option( self::OPTION, array() );
		$key     = is_array( $options ) && isset( $options['RV_text_api_key'] )
			? (string) $options['RV_text_api_key']
			: '';

		update_option( self::OPTION, '' !== $key ? array( 'RV_text_api_key' => $key ) : array() );
	}

	/**
	 * Sanitize callback for `register_setting`.
	 *
	 * @param mixed $input Raw submitted settings.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();

		$key = isset( $input['RV_text_api_key'] ) ? (string) $input['RV_text_api_key'] : '';
		if ( preg_match( '/key=([0-9a-zA-Z]+)/i', $key, $matches ) ) {
			// The user pasted a full embed script; pull the key out of it.
			$key = $matches[1];
		} else {
			$key = sanitize_text_field( $key );
		}
		$input['RV_text_api_key'] = $key;

		$input['v2_optin'] = ! empty( $input['v2_optin'] );

		if ( isset( $input['webplayer_mode'] ) ) {
			$input['webplayer_mode'] = in_array( $input['webplayer_mode'], array( self::WEBPLAYER_ON, self::WEBPLAYER_OFF, self::WEBPLAYER_DEFAULT ), true )
				? $input['webplayer_mode']
				: self::WEBPLAYER_ON;
		}

		if ( isset( $input['core_delivery'] ) ) {
			$input['core_delivery'] = self::DELIVERY_BUNDLED === $input['core_delivery']
				? self::DELIVERY_BUNDLED
				: self::DELIVERY_CDN;
		}

		if ( isset( $input['core_version'] ) ) {
			$version               = (string) $input['core_version'];
			$input['core_version'] = preg_match( '/^\d+\.\d+\.\d+$/', $version ) ? $version : '';
		}

		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			$manageable = $this->get_manageable_post_types();
			$map        = array();
			foreach ( $input['post_types'] as $slug => $state ) {
				$slug = sanitize_key( (string) $slug );
				if ( '' === $slug || ! isset( $manageable[ $slug ] ) ) {
					continue;
				}
				$map[ $slug ] = 'on' === $state ? 'on' : 'off';
			}
			$input['post_types'] = $map;
		}

		// The customizer submits the sparse WebPlayer config as a JSON string.
		$config = $input['webplayer_config'] ?? array();
		if ( is_string( $config ) ) {
			$decoded = json_decode( $config, true );
			$config  = is_array( $decoded ) ? $decoded : array();
		}
		$input['webplayer_config'] = is_array( $config ) ? $this->sanitize_webplayer_config( $config ) : array();

		return $input;
	}

	/**
	 * Sanitize the sparse WebPlayer config against WebPlayerFeatureSchema. A value
	 * of the wrong type or outside its enum is dropped, so the SDK + /v2/config
	 * default applies instead.
	 *
	 * @param array<mixed> $config Decoded config.
	 * @return array<string, mixed>
	 */
	private function sanitize_webplayer_config( array $config ): array {
		$config = array_intersect_key( $config, array_flip( $this->webplayer_config_keys() ) );
		$clean  = array();

		foreach ( $config as $key => $value ) {
			switch ( $key ) {
				case 'selector':
				case 'paragraphSelector':
					$clean[ $key ] = $this->sanitize_selector( $value );
					break;
				case 'position':
					$clean[ $key ] = $this->sanitize_position( $value );
					break;
				case 'theme':
					$clean[ $key ] = $this->sanitize_theme( $value );
					break;
				case 'controls':
					$clean[ $key ] = $this->sanitize_controls( $value );
					break;
				case 'navigation':
					$clean[ $key ] = $this->pick_booleans( $value, array( 'paragraphHighlight', 'paragraphClick' ) );
					break;
				case 'layout':
					$clean[ $key ] = $this->drop_empty(
						array(
							'mode'    => $this->pick_enum( $value['mode'] ?? null, array( 'shrink', 'fill' ) ),
							'display' => $this->pick_enum( $value['display'] ?? null, array( 'inline', 'block' ) ),
						)
					);
					break;
				case 'miniPlayer':
					$clean[ $key ] = $this->sanitize_mini_player( $value );
					break;
				case 'sanitize':
					$exclude       = isset( $value['exclude'] ) && is_array( $value['exclude'] ) ? $value['exclude'] : array();
					$clean[ $key ] = $this->drop_empty(
						$this->pick_booleans( $value, array( 'enabled' ) )
						+ array( 'exclude' => array_values( array_filter( array_map( array( $this, 'sanitize_selector' ), $exclude ) ) ) )
					);
					break;
				case 'voice':
					$clean[ $key ] = $this->sanitize_voice( $value );
					break;
				case 'pitch':
				case 'rate':
					$clean[ $key ] = is_numeric( $value ) ? min( 2.0, max( 0.0, (float) $value ) ) : null;
					break;
				case 'volume':
					$clean[ $key ] = is_numeric( $value ) ? min( 1.0, max( 0.0, (float) $value ) ) : null;
					break;
			}
		}

		return $this->drop_empty( $clean );
	}

	/**
	 * Sanitize the WebPlayer `theme`: a preset name or an object of colour tokens.
	 *
	 * @param mixed $theme Raw theme value.
	 * @return string|array<string, string>|null
	 */
	private function sanitize_theme( $theme ) {
		if ( ! is_array( $theme ) ) {
			return $this->pick_enum( $theme, array( 'neutral', 'responsivevoice' ) );
		}

		$allowed = array( 'bg', 'fg', 'muted', 'accent', 'accentSoft', 'hover', 'border', 'track', 'fill' );
		$tokens  = array();
		foreach ( array_intersect_key( $theme, array_flip( $allowed ) ) as $token => $colour ) {
			$colour = is_string( $colour ) ? sanitize_hex_color( $colour ) : '';
			if ( $colour ) {
				$tokens[ $token ] = $colour;
			}
		}

		return $tokens;
	}

	/**
	 * Sanitize the WebPlayer `controls` toggles. `brand` is a boolean or an
	 * `{ icon, poweredBy }` object.
	 *
	 * @param mixed $controls Raw controls value.
	 * @return array<string, mixed>
	 */
	private function sanitize_controls( $controls ): array {
		$clean = $this->pick_booleans( $controls, array( 'progress', 'time', 'skip', 'speed', 'brand' ) );

		if ( isset( $controls['brand'] ) && is_array( $controls['brand'] ) ) {
			$clean['brand'] = $this->pick_booleans( $controls['brand'], array( 'icon', 'poweredBy' ) );
		}

		return $this->drop_empty( $clean );
	}

	/**
	 * Sanitize the WebPlayer `miniPlayer`: a boolean, or an object whose `position`
	 * is a corner keyword or a CSS-offset object.
	 *
	 * @param mixed $mini_player Raw miniPlayer value.
	 * @return bool|array<string, mixed>|null
	 */
	private function sanitize_mini_player( $mini_player ) {
		if ( ! is_array( $mini_player ) ) {
			return is_bool( $mini_player ) ? $mini_player : null;
		}

		$clean    = $this->pick_booleans( $mini_player, array( 'enabled' ) );
		$position = $mini_player['position'] ?? null;

		$clean['position']  = is_array( $position )
			? $this->pick_strings( $position, array( 'top', 'right', 'bottom', 'left' ) )
			: $this->pick_enum( $position, array( 'top-left', 'top-right', 'bottom-left', 'bottom-right' ) );
		$clean['animation'] = $this->pick_enum( $mini_player['animation'] ?? null, array( 'none', 'fade', 'slide', 'pop' ) );

		return $this->drop_empty( $clean );
	}

	/**
	 * Sanitize the WebPlayer `voice`: a voice name, or a structured query or
	 * regex selector.
	 *
	 * @param mixed $voice Raw voice value.
	 * @return string|array<string, mixed>|null
	 */
	private function sanitize_voice( $voice ) {
		if ( ! is_array( $voice ) ) {
			return is_string( $voice ) ? sanitize_text_field( $voice ) : null;
		}

		$clean           = $this->pick_strings( $voice, array( 'name', 'lang', 'provider', 'flags' ) );
		$clean['gender'] = $this->pick_enum( $voice['gender'] ?? null, array( 'f', 'm', 'male', 'female' ) );
		$clean          += $this->pick_booleans( $voice, array( 'isByok' ) );

		// A regex source is kept verbatim; text sanitizing would corrupt the pattern.
		if ( isset( $voice['regex'] ) && is_string( $voice['regex'] ) ) {
			$clean['regex'] = $voice['regex'];
		}

		return $this->drop_empty( $clean );
	}

	/**
	 * A CSS selector with any markup removed. Not `sanitize_text_field()`, which
	 * strips the percent-encoded octets an attribute selector can carry.
	 *
	 * @param mixed $selector Raw selector.
	 */
	private function sanitize_selector( $selector ): string {
		return is_string( $selector ) ? trim( wp_strip_all_tags( $selector ) ) : '';
	}

	/**
	 * The listed keys of `$value` that hold a real boolean.
	 *
	 * @param mixed              $value Raw value, expected to be an array.
	 * @param array<int, string> $keys  Keys to keep.
	 * @return array<string, bool>
	 */
	private function pick_booleans( $value, array $keys ): array {
		$clean = array();
		foreach ( $keys as $key ) {
			if ( is_array( $value ) && isset( $value[ $key ] ) && is_bool( $value[ $key ] ) ) {
				$clean[ $key ] = $value[ $key ];
			}
		}

		return $clean;
	}

	/**
	 * The listed keys of `$value` that hold a non-empty string, text-sanitized.
	 *
	 * @param array<mixed>       $value Raw array.
	 * @param array<int, string> $keys  Keys to keep.
	 * @return array<string, string>
	 */
	private function pick_strings( array $value, array $keys ): array {
		$clean = array();
		foreach ( $keys as $key ) {
			$text = isset( $value[ $key ] ) && is_string( $value[ $key ] ) ? sanitize_text_field( $value[ $key ] ) : '';
			if ( '' !== $text ) {
				$clean[ $key ] = $text;
			}
		}

		return $clean;
	}

	/**
	 * `$value` when it is one of the allowed strings, otherwise null.
	 *
	 * @param mixed              $value   Raw value.
	 * @param array<int, string> $allowed Allowed values.
	 */
	private function pick_enum( $value, array $allowed ): ?string {
		return is_string( $value ) && in_array( $value, $allowed, true ) ? $value : null;
	}

	/**
	 * Remove null, empty-string and empty-array entries; `false` is a real value.
	 *
	 * @param array<string, mixed> $values Values to filter.
	 * @return array<string, mixed>
	 */
	private function drop_empty( array $values ): array {
		return array_filter(
			$values,
			static fn( $value ): bool => null !== $value && '' !== $value && array() !== $value
		);
	}

	/**
	 * Sanitize the WebPlayer `position`: either an article-relative keyword
	 * (`inline`|`before`|`after`) or a custom-container object
	 * (`{ target: <CSS selector>, at: inside|before|after }`). A targetless
	 * object is meaningless, so it falls back to the keyword default.
	 *
	 * @param mixed $position Raw position value.
	 * @return string|array<string, string>
	 */
	private function sanitize_position( $position ) {
		if ( is_array( $position ) ) {
			$target = isset( $position['target'] ) ? sanitize_text_field( (string) $position['target'] ) : '';
			if ( '' === $target ) {
				return 'before';
			}
			$at = isset( $position['at'] ) ? (string) $position['at'] : 'inside';

			return array(
				'target' => $target,
				'at'     => in_array( $at, array( 'inside', 'before', 'after' ), true ) ? $at : 'inside',
			);
		}

		$position = (string) $position;

		return in_array( $position, array( 'inline', 'before', 'after' ), true ) ? $position : 'before';
	}
}
