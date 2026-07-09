<?php
/**
 * Text normalization for speech.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Cleans post/shortcode content into plain, speakable text.
 *
 * Unlike the legacy renderer this produces plain text only. The text travels to
 * the browser in a `data-*` attribute (escaped by WordPress on output), so we
 * don't need any inline-JS quote-escaping.
 */
final class TextSanitizer {

	/**
	 * Entities spoken more naturally as words than as symbols.
	 *
	 * @var array<string, string>
	 */
	private const READABILITY = array(
		'&gt;' => 'greater than',
		'&lt;' => 'less than',
	);

	/**
	 * Normalize content into a single line of speakable plain text.
	 *
	 * @param string $text Raw post or shortcode content.
	 */
	public function clean( string $text ): string {
		$text = strip_shortcodes( $text );
		$text = wp_strip_all_tags( $text, true );

		// Speak comparison operators as words before decoding the rest.
		$text = str_replace( array_keys( self::READABILITY ), array_values( self::READABILITY ), $text );
		$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$collapsed = preg_replace( '/\s+/', ' ', trim( $text ) );

		return null === $collapsed ? '' : $collapsed;
	}
}
