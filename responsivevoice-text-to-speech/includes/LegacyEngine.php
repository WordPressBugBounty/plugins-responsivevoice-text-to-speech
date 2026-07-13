<?php
/**
 * Legacy (v1) shortcode renderer.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

/**
 * Reproduces the original button shortcodes, but CSP-clean: buttons carry
 * `data-rvtts-*` attributes and a single static script (enqueued elsewhere)
 * attaches the behaviour.
 */
final class LegacyEngine {

	/**
	 * ResponsiveVoice mark, inline so it inherits the button's text colour
	 * (`fill="currentColor"`) and stays crisp at any size. Static trusted markup.
	 */
	private const ICON = '<svg class="rvtts-icon" width="22" height="22" viewBox="0 0 22 22" fill="currentColor" aria-hidden="true" focusable="false"><path fill-rule="evenodd" clip-rule="evenodd" d="M11 0C4.92345 0 0 4.92345 0 11C0 13.2683 0.690345 15.3772 1.86621 17.1221L0.811724 21.0517L4.70345 20.0124C6.48621 21.2641 8.65586 22 11 22C17.0766 22 22 17.0766 22 11C22 4.92345 17.0766 0 11 0ZM3.99793 9.99862C3.99793 9.44483 4.44552 8.99724 4.99931 8.99724C5.5531 8.99724 6.00069 9.44483 6.00069 9.99862V12.0014C6.00069 12.5552 5.5531 13.0028 4.99931 13.0028C4.44552 13.0028 3.99793 12.5552 3.99793 12.0014V9.99862ZM8.99724 13.9966C8.99724 14.5503 8.54966 14.9979 7.99586 14.9979C7.44207 14.9979 6.99448 14.5503 6.99448 13.9966V7.99586C6.99448 7.44207 7.44207 6.99448 7.99586 6.99448C8.54966 6.99448 8.99724 7.44207 8.99724 7.99586V13.9966ZM12.0014 17.0007C12.0014 17.5545 11.5538 18.0021 11 18.0021C10.4462 18.0021 9.99862 17.5545 9.99862 17.0007V4.99931C9.99862 4.44552 10.4462 3.99793 11 3.99793C11.5538 3.99793 12.0014 4.44552 12.0014 4.99931V17.0007ZM14.9979 13.9966C14.9979 14.5503 14.5503 14.9979 13.9966 14.9979C13.4428 14.9979 12.9952 14.5503 12.9952 13.9966V7.99586C12.9952 7.44207 13.4428 6.99448 13.9966 6.99448C14.5503 6.99448 14.9979 7.44207 14.9979 7.99586V13.9966ZM18.0021 12.0014C18.0021 12.5552 17.5545 13.0028 17.0007 13.0028C16.4469 13.0028 15.9993 12.5552 15.9993 12.0014V9.99862C15.9993 9.44483 16.4469 8.99724 17.0007 8.99724C17.5545 8.99724 18.0021 9.44483 18.0021 9.99862V12.0014Z"/></svg>';

	/**
	 * Content cleaner.
	 *
	 * @var TextSanitizer
	 */
	private TextSanitizer $sanitizer;

	/**
	 * Constructor.
	 *
	 * @param TextSanitizer $sanitizer Content cleaner.
	 */
	public function __construct( TextSanitizer $sanitizer ) {
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Register the v1 shortcode tags.
	 */
	public function register(): void {
		add_shortcode( 'ResponsiveVoice', array( $this, 'render_enclosed' ) );
		add_shortcode( 'responsivevoice', array( $this, 'render_enclosed' ) );
		add_shortcode( 'responsivevoice_button', array( $this, 'render_post_button' ) );
		add_shortcode( 'ListenToPostButton', array( $this, 'render_post_button' ) );
		add_shortcode( 'RVListenButton', array( $this, 'render_post_button' ) );
	}

	/**
	 * "Listen to this" button that reads the whole post.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render_post_button( $atts ): string {
		$atts = shortcode_atts(
			array(
				// Empty = account/website default voice (set in the ResponsiveVoice
				// app). Only emit a voice when the author passes one explicitly.
				'voice'      => '',
				'buttontext' => 'Listen to this',
				'rate'       => '',
				'pitch'      => '',
				'volume'     => '',
				'class'      => '',
			),
			$atts,
			'responsivevoice_button'
		);

		return $this->button( $this->clean_content( (string) get_the_content() ), $atts );
	}

	/**
	 * Button that reads the text enclosed by the shortcode.
	 *
	 * @param array<string, string>|string $atts    Shortcode attributes.
	 * @param string                       $content Enclosed content.
	 * @return string
	 */
	public function render_enclosed( $atts, string $content = '' ): string {
		$atts = shortcode_atts(
			array(
				// Empty = account/website default voice; only emit when passed.
				'voice'          => '',
				'buttontext'     => 'Play',
				'buttonposition' => 'before',
				'rate'           => '',
				'pitch'          => '',
				'volume'         => '',
				'class'          => '',
			),
			$atts,
			'ResponsiveVoice'
		);

		// Space between button and content: a gap for inline text, ignored between
		// block elements.
		$button = $this->button( $this->clean_content( $content ), $atts );

		return 'after' === $atts['buttonposition']
			? $content . ' ' . $button
			: $button . ' ' . $content;
	}

	/**
	 * Apply the v1 content filters around cleaning (kept for back-compat).
	 *
	 * @param string $raw Raw content.
	 * @return string
	 */
	private function clean_content( string $raw ): string {
		/** This filter is documented as part of the v1 public API. */
		$raw   = (string) apply_filters( 'responsivevoice_content_before_cleaning', $raw );
		$clean = $this->sanitizer->clean( $raw );

		/** This filter is documented as part of the v1 public API. */
		return (string) apply_filters( 'responsivevoice_content_after_cleaning', $clean );
	}

	/**
	 * "Listen to this" button for the Gutenberg block. Same speak button, but the
	 * caller (block render.php) supplies `get_block_wrapper_attributes()` so the
	 * block's colour, typography, spacing and border supports land on the button
	 * element itself.
	 *
	 * @param array<string, string> $atts               Block attributes (voice/buttontext/rate/pitch/volume).
	 * @param string                $wrapper_attributes  Output of get_block_wrapper_attributes() (safe HTML attrs).
	 * @return string
	 */
	public function render_block_button( array $atts, string $wrapper_attributes ): string {
		$atts = shortcode_atts(
			array(
				// Empty = account/library default voice (valid for v1 and v2).
				'voice'      => '',
				'buttontext' => 'Listen to this',
				'rate'       => '',
				'pitch'      => '',
				'volume'     => '',
			),
			$atts
		);

		return $this->button( $this->clean_content( (string) get_the_content() ), $atts, $wrapper_attributes );
	}

	/**
	 * Build a CSP-clean speak button.
	 *
	 * @param string                $text               Speakable text.
	 * @param array<string, string> $atts               Resolved attributes.
	 * @param string                $wrapper_attributes Optional block wrapper attributes (class + supports style);
	 *                                                  when empty the shortcode falls back to the bare class.
	 * @return string
	 */
	private function button( string $text, array $atts, string $wrapper_attributes = '' ): string {
		$data = array(
			'data-rvtts-action' => 'speak',
			'data-rvtts-text'   => $text,
		);

		// Omit the voice entirely when unset so the SDK uses its configured default "voice profile" voice.
		// Don't emit an empty data-rvtts-voice.
		if ( '' !== (string) $atts['voice'] ) {
			$data['data-rvtts-voice'] = (string) $atts['voice'];
		}

		foreach ( array( 'rate', 'pitch', 'volume' ) as $param ) {
			if ( '' !== (string) $atts[ $param ] && is_numeric( $atts[ $param ] ) ) {
				$data[ 'data-rvtts-' . $param ] = (string) $atts[ $param ];
			}
		}

		$attributes = '';
		foreach ( $data as $name => $value ) {
			$attributes .= sprintf( ' %s="%s"', $name, esc_attr( $value ) );
		}

		// The block routes its supports here via get_block_wrapper_attributes()
		// (already escaped). Shortcodes have no block context, so we build the class
		// ourselves, appending any author-supplied `class` attribute as a styling hook.
		$extra     = trim( (string) ( $atts['class'] ?? '' ) );
		$classes   = 'responsivevoice-button' . ( '' !== $extra ? ' ' . $extra : '' );
		$container = '' !== $wrapper_attributes ? $wrapper_attributes : 'class="' . esc_attr( $classes ) . '"';

		return sprintf(
			'<button %1$s type="button" title="%2$s"%3$s>%4$s<span class="responsivevoice-button__label">%5$s</span></button>',
			$container,
			esc_attr__( 'ResponsiveVoice Tap to Start/Stop Speech', 'responsivevoice-text-to-speech' ),
			$attributes,
			self::ICON,
			esc_html( (string) $atts['buttontext'] )
		);
	}
}
