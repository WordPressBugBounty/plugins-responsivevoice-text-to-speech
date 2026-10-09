<?php
/**
 * Generator meta tag.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Names the plugin in the document head. Carries no version.
 */
final class GeneratorTag {

	private const CONTENT = 'ResponsiveVoice Text To Speech';

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'render' ) );
	}

	/**
	 * Print the tag.
	 */
	public function render(): void {
		/**
		 * Filters the generator tag content. Return an empty string to remove the tag.
		 *
		 * @param string $content Tag content.
		 */
		$content = apply_filters( 'rvtts_generator_tag', self::CONTENT );

		if ( ! is_string( $content ) || '' === $content ) {
			return;
		}

		printf( '<meta name="generator" content="%s">' . "\n", esc_attr( $content ) );
	}
}
