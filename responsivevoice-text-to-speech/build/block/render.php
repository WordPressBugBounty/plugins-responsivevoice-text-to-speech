<?php
/**
 * Server render for the rvtts/listen-button block.
 *
 * @package ResponsiveVoice
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$rvtts_settings = new \ResponsiveVoice\Settings();
$rvtts_engine   = new \ResponsiveVoice\LegacyEngine(
	new \ResponsiveVoice\TextSanitizer(),
	new \ResponsiveVoice\Attribution(
		$rvtts_settings,
		new \ResponsiveVoice\ConfigClient( $rvtts_settings )
	)
);

$rvtts_atts = array(
	// Empty voice = use the account/library default (valid for both v1 and v2);
	// the engine omits data-rvtts-voice so the SDK picks its configured default.
	'voice'      => isset( $attributes['voice'] ) ? (string) $attributes['voice'] : '',
	'buttontext' => isset( $attributes['buttontext'] ) ? (string) $attributes['buttontext'] : 'Listen to this',
);

// Forward playback params only when the author set them; the engine treats an
// empty value as "use the SDK default" and numeric-guards each one.
foreach ( array( 'rate', 'pitch', 'volume' ) as $rvtts_param ) {
	if ( isset( $attributes[ $rvtts_param ] ) && is_numeric( $attributes[ $rvtts_param ] ) ) {
		$rvtts_atts[ $rvtts_param ] = (string) $attributes[ $rvtts_param ];
	}
}

// The wrapper is what the theme's layout rules position; the button carries the styling.
$rvtts_wrapper = get_block_wrapper_attributes();
$rvtts_button  = \ResponsiveVoice\Block::button_attributes( $attributes );

// Both attribute strings are escaped at source; echo as trusted markup.
echo '<div ' . $rvtts_wrapper . '>' . $rvtts_engine->render_block_button( $rvtts_atts, $rvtts_button ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
