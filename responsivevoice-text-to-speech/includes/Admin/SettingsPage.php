<?php
/**
 * Admin settings page.
 *
 * @package ResponsiveVoice
 */

namespace ResponsiveVoice\Admin;

use ResponsiveVoice\Settings;
use ResponsiveVoice\ConfigClient;
use ResponsiveVoice\ConfigResult;
use ResponsiveVoice\Verification;
use ResponsiveVoice\WebPlayerEngine;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the plugin's settings screen.
 *
 * On each visit it re-probes `/v2/config` (the "react on admin visit" flow) so a
 * customer who switched v1->v2 in the RV Dashboard sees the change reflected.
 * Markup is WordPress-native for now; RV branding layers on later.
 */
final class SettingsPage {


	private const SLUG         = 'responsivevoice-text-to-speech';
	private const GROUP        = 'rvtts_settings';
	private const CAPABILITY   = 'manage_options';
	private const STATUS_ID    = 'rvtts-verify-status';
	private const UPGRADE_ID   = 'rvtts-upgrade-notice';
	private const RESET_ACTION = 'rvtts_reset_settings';

	private const URL_REGISTER  = 'https://responsivevoice.org/register';
	private const URL_DASHBOARD = 'https://app.responsivevoice.org';
	private const SDK_URL       = 'https://cdn.responsivevoice.org/sdk/latest/responsivevoice.js';
	private const CHANGELOG_URL = 'https://docs.responsivevoice.org/changelog/core/';

	// White (negative) ResponsiveVoice icon for the admin menu.
	private const MENU_ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIj48ZyB0cmFuc2Zvcm09InRyYW5zbGF0ZSgxIDEpIHNjYWxlKDAuODE4MTgyKSI+PHBhdGggZmlsbD0iI2ZmZiIgZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMSAwQzQuOTIzNDUgMCAwIDQuOTIzNDUgMCAxMUMwIDEzLjI2ODMgMC42OTAzNDUgMTUuMzc3MiAxLjg2NjIxIDE3LjEyMjFMMC44MTE3MjQgMjEuMDUxN0w0LjcwMzQ1IDIwLjAxMjRDNi40ODYyMSAyMS4yNjQxIDguNjU1ODYgMjIgMTEgMjJDMTcuMDc2NiAyMiAyMiAxNy4wNzY2IDIyIDExQzIyIDQuOTIzNDUgMTcuMDc2NiAwIDExIDBaTTMuOTk3OTMgOS45OTg2MkMzLjk5NzkzIDkuNDQ0ODMgNC40NDU1MiA4Ljk5NzI0IDQuOTk5MzEgOC45OTcyNEM1LjU1MzEgOC45OTcyNCA2LjAwMDY5IDkuNDQ0ODMgNi4wMDA2OSA5Ljk5ODYyVjEyLjAwMTRDNi4wMDA2OSAxMi41NTUyIDUuNTUzMSAxMy4wMDI4IDQuOTk5MzEgMTMuMDAyOEM0LjQ0NTUyIDEzLjAwMjggMy45OTc5MyAxMi41NTUyIDMuOTk3OTMgMTIuMDAxNFY5Ljk5ODYyWk04Ljk5NzI0IDEzLjk5NjZDOC45OTcyNCAxNC41NTAzIDguNTQ5NjYgMTQuOTk3OSA3Ljk5NTg2IDE0Ljk5NzlDNy40NDIwNyAxNC45OTc5IDYuOTk0NDggMTQuNTUwMyA2Ljk5NDQ4IDEzLjk5NjZWNy45OTU4NkM2Ljk5NDQ4IDcuNDQyMDcgNy40NDIwNyA2Ljk5NDQ4IDcuOTk1ODYgNi45OTQ0OEM4LjU0OTY2IDYuOTk0NDggOC45OTcyNCA3LjQ0MjA3IDguOTk3MjQgNy45OTU4NlYxMy45OTY2Wk0xMi4wMDE0IDE3LjAwMDdDMTIuMDAxNCAxNy41NTQ1IDExLjU1MzggMTguMDAyMSAxMSAxOC4wMDIxQzEwLjQ0NjIgMTguMDAyMSA5Ljk5ODYyIDE3LjU1NDUgOS45OTg2MiAxNy4wMDA3VjQuOTk5MzFDOS45OTg2MiA0LjQ0NTUyIDEwLjQ0NjIgMy45OTc5MyAxMSAzLjk5NzkzQzExLjU1MzggMy45OTc5MyAxMi4wMDE0IDQuNDQ1NTIgMTIuMDAxNCA0Ljk5OTMxVjE3LjAwMDdaTTE0Ljk5NzkgMTMuOTk2NkMxNC45OTc5IDE0LjU1MDMgMTQuNTUwMyAxNC45OTc5IDEzLjk5NjYgMTQuOTk3OUMxMy40NDI4IDE0Ljk5NzkgMTIuOTk1MiAxNC41NTAzIDEyLjk5NTIgMTMuOTk2NlY3Ljk5NTg2QzEyLjk5NTIgNy40NDIwNyAxMy40NDI4IDYuOTk0NDggMTMuOTk2NiA2Ljk5NDQ4QzE0LjU1MDMgNi45OTQ0OCAxNC45OTc5IDcuNDQyMDcgMTQuOTk3OSA3Ljk5NTg2VjEzLjk5NjZaTTE4LjAwMjEgMTIuMDAxNEMxOC4wMDIxIDEyLjU1NTIgMTcuNTU0NSAxMy4wMDI4IDE3LjAwMDcgMTMuMDAyOEMxNi40NDY5IDEzLjAwMjggMTUuOTk5MyAxMi41NTUyIDE1Ljk5OTMgMTIuMDAxNFY5Ljk5ODYyQzE1Ljk5OTMgOS40NDQ4MyAxNi40NDY5IDguOTk3MjQgMTcuMDAwNyA4Ljk5NzI0QzE3LjU1NDUgOC45OTcyNCAxOC4wMDIxIDkuNDQ0ODMgMTguMDAyMSA5Ljk5ODYyVjEyLjAwMTRaIi8+PC9nPjwvc3ZnPg==';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Config probe.
	 *
	 * @var ConfigClient
	 */
	private ConfigClient $config;

	/**
	 * Website-verification state.
	 *
	 * @var Verification
	 */
	private Verification $verification;

	/**
	 * The page's admin hook suffix (for asset enqueueing).
	 *
	 * @var string
	 */
	private string $hook_suffix = '';

	/**
	 * Constructor.
	 *
	 * @param Settings     $settings     Settings accessor.
	 * @param ConfigClient $config       Config probe.
	 * @param Verification $verification Website-verification state.
	 */
	public function __construct( Settings $settings, ConfigClient $config, Verification $verification ) {
		$this->settings     = $settings;
		$this->config       = $config;
		$this->verification = $verification;
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_' . self::RESET_ACTION, array( $this, 'handle_reset' ) );
		add_filter( 'plugin_action_links_' . RVTTS_PLUGIN_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Prepend a Settings link to the plugin's row on the Plugins screen.
	 *
	 * @param array<string> $links Existing action links.
	 * @return array<string>
	 */
	public function action_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ),
				esc_html__( 'Settings', 'responsivevoice-text-to-speech' )
			)
		);

		return $links;
	}

	/**
	 * Handle the "Reset to defaults" POST: verify capability + nonce, reset every
	 * preference (keeping the API key), then redirect back with a success flag.
	 */
	public function handle_reset(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You are not allowed to do that.', 'responsivevoice-text-to-speech' ) );
		}
		check_admin_referer( self::RESET_ACTION );

		$this->settings->reset();

		wp_safe_redirect( add_query_arg( 'rvtts_reset', '1', admin_url( 'admin.php?page=' . self::SLUG ) ) );
		exit;
	}

	/**
	 * Add the top-level menu entry.
	 */
	public function add_menu(): void {
		$this->hook_suffix = (string) add_menu_page(
			__( 'ResponsiveVoice Text To Speech', 'responsivevoice-text-to-speech' ),
			__( 'ResponsiveVoice', 'responsivevoice-text-to-speech' ),
			self::CAPABILITY,
			self::SLUG,
			array( $this, 'render' ),
			self::MENU_ICON
		);
	}

	/**
	 * Enqueue the branded admin styles on this page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( $hook !== $this->hook_suffix ) {
			return;
		}

		$file  = RVTTS_PLUGIN_DIR . 'build/admin.asset.php';
		$asset = file_exists( $file ) ? (array) ( require $file ) : array();
		$ver   = (string) ( $asset['version'] ?? RVTTS_VERSION );
		$deps  = (array) ( $asset['dependencies'] ?? array() );

		wp_enqueue_style(
			'rvtts-admin',
			RVTTS_PLUGIN_URL . 'build/admin.css',
			array(),
			$ver
		);
		wp_style_add_data( 'rvtts-admin', 'rtl', 'replace' );

		// Load the CDN SDK so the customizer can mount a live preview. Only when a
		// key exists (an unkeyed/onboarding screen has nothing to preview).
		if ( '' !== $this->settings->get_api_key() ) {
			wp_register_script( 'rvtts-sdk', self::SDK_URL, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
			wp_enqueue_script( 'rvtts-sdk' );
			$deps[] = 'rvtts-sdk';
		}

		wp_enqueue_script( 'rvtts-admin', RVTTS_PLUGIN_URL . 'build/admin.js', $deps, $ver, true );
	}

	/**
	 * Register the option + its sanitizer.
	 */
	public function register_setting(): void {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			array( 'sanitize_callback' => array( $this->settings, 'sanitize' ) )
		);
	}

	/**
	 * Render the settings screen.
	 */
	public function render(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		// Re-probe on visit so a dashboard v1<->v2 switch is reflected here.
		$probe = $this->config->fetch( true );

		echo '<div class="wrap rvtts-admin">';
		$this->brand_bar( $probe->is_valid() ? $probe->sdk_version() : null );
		// A .wp-header-end anchor so core places admin notices correctly (the brand
		// bar carries the page heading in place of a bare <h1>).
		echo '<hr class="wp-header-end" />';

		$this->status_notice( $probe );

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag set by the nonce-checked reset redirect.
		if ( isset( $_GET['rvtts_reset'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html__( 'Settings reset to defaults.', 'responsivevoice-text-to-speech' )
			);
		}

		echo '<form action="options.php" method="post">';
		settings_fields( self::GROUP );

		// Resolved once, shared by the visibility row and the customizer below.
		$is_v2           = $probe->is_valid() && 'v2' === $probe->sdk_version();
		$server          = $probe->is_valid() ? $probe->web_player() : array();
		$default_enabled = ! isset( $server['enabled'] ) || (bool) $server['enabled'];
		$enabled         = ( new WebPlayerEngine( $this->settings, $this->config ) )->is_enabled();
		$active          = ( $is_v2 && $enabled ) || ( ! $is_v2 && $this->settings->is_v2_optin() );

		echo '<div class="rvtts-admin__card">';

		$this->field_row(
			'rvtts-RV_text_api_key',
			__( 'API Key', 'responsivevoice-text-to-speech' ),
			$this->control_text( 'RV_text_api_key', $this->settings->get_api_key() ),
			__( 'Your ResponsiveVoice website key', 'responsivevoice-text-to-speech' )
		);

		// A v1 account may opt in to preview the v2 WebPlayer (reversible pill toggle);
		// a v2 account instead gets the enable tri-state plus the per-type visibility list.
		if ( $probe->is_valid() && 'v1' === $probe->sdk_version() ) {
			$this->optin_row();
		} elseif ( $is_v2 ) {
			$this->webplayer_mode_row();
			$this->visibility_row( $enabled );
		}

		echo '</div>';

		// Live WebPlayer customizer + preview (JS-driven; mounts the CDN SDK). Hidden
		// and SDK-dormant unless the WebPlayer will actually render: a v2 account with
		// the enable tri-state on, or a v1 user opting in to preview.
		if ( $probe->is_valid() ) {
			$this->customizer( $active, $is_v2, $default_enabled );
		}

		// Advanced delivery options, collapsed by default (carded to match the page).
		printf(
			'<div class="rvtts-admin__card"><details class="rvtts-advanced"><summary>%s</summary><div class="rvtts-advanced__body">',
			esc_html__( 'Advanced', 'responsivevoice-text-to-speech' )
		);

		$this->field_row(
			'rvtts-core_delivery',
			__( 'Core delivery', 'responsivevoice-text-to-speech' ),
			$this->control_select(
				'core_delivery',
				$this->settings->get_core_delivery(),
				array(
					Settings::DELIVERY_CDN     => __( 'CDN (recommended)', 'responsivevoice-text-to-speech' ),
					Settings::DELIVERY_BUNDLED => __( 'Bundled (self-hosted)', 'responsivevoice-text-to-speech' ),
				)
			),
			''
		);

		$this->field_row(
			'rvtts-core_version',
			__( 'Pinned version', 'responsivevoice-text-to-speech' ),
			$this->control_text( 'core_version', $this->settings->get_core_version() ),
			__( 'Optional. Pin a specific CDN version (e.g. 2.0.1) for immutability; leave blank for the latest release.', 'responsivevoice-text-to-speech' )
		);

		$this->reset_row();

		echo '</div></details></div>';
		submit_button();
		echo '</form></div>';
	}

	/**
	 * Print the compact purple brand bar (white wordmark, product name, and a badge
	 * showing the account's SDK version: blue for v2, grey for v1, omitted when
	 * there's no valid version yet). The badge text is the tier ('v1'/'v2') as a
	 * fallback; version-badge.js swaps it for the loaded SDK's real
	 * `responsiveVoice.version` when that's available (colour stays tier-driven).
	 *
	 * @param string|null $sdk_version 'v1' | 'v2' | null.
	 */
	private function brand_bar( ?string $sdk_version ): void {
		$badge = '';
		if ( null !== $sdk_version && '' !== $sdk_version ) {
			// v2 versions link to the core changelog (version-badge.js deep-links to
			// the exact version's anchor); v1 has no reliable changelog, so no link.
			$changelog = 'v2' === $sdk_version
				? sprintf( ' data-rvtts-changelog="%s"', esc_url( self::CHANGELOG_URL ) )
				: '';
			$badge     = sprintf(
				'<span class="rvtts-admin__badge%1$s" data-rvtts-sdk-badge%2$s>%3$s</span>',
				'v2' === $sdk_version ? '' : ' rvtts-admin__badge--v1',
				$changelog,
				esc_html( $sdk_version )
			);
		}

		printf(
			'<div class="rvtts-admin__brandbar">'
			. '<img src="%1$s" alt="%2$s" />'
			. '<span class="rvtts-admin__sep" aria-hidden="true"></span>'
			. '<div class="rvtts-admin__brandtext">'
			. '<h1 class="rvtts-admin__title">%3$s</h1>'
			. '<p class="rvtts-admin__tagline">%4$s</p>'
			. '</div>'
			. '%5$s'
			. '</div>',
			esc_url( RVTTS_PLUGIN_URL . 'images/resvoice-logo-white.svg' ),
			esc_attr__( 'ResponsiveVoice', 'responsivevoice-text-to-speech' ),
			esc_html__( 'Text To Speech', 'responsivevoice-text-to-speech' ),
			esc_html__( 'Add a WebPlayer or listen buttons to your posts and pages.', 'responsivevoice-text-to-speech' ),
			$badge // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built + escaped above.
		);
	}

	/**
	 * Print the reversible v1 "try the v2 WebPlayer" opt-in as a settings row: the
	 * label in the left column, the pill toggle + New/v2 badges as the control.
	 * Keeps the `v2_optin` id/name so the form and customizer JS still bind.
	 */
	private function optin_row(): void {
		$control = sprintf(
			'<div class="rvtts-toggle-row">'
			. '<label class="rvtts-switch"><input type="checkbox" id="rvtts-v2_optin" name="%1$s[v2_optin]" value="1"%2$s /><span class="rvtts-switch__track"></span></label>'
			. '<span class="rvtts-pill rvtts-pill--new">%3$s</span>'
			. '<span class="rvtts-pill rvtts-pill--v2">%4$s</span>'
			. '</div>',
			esc_attr( Settings::OPTION ),
			checked( $this->settings->is_v2_optin(), true, false ),
			esc_html__( 'New', 'responsivevoice-text-to-speech' ),
			esc_html__( 'v2', 'responsivevoice-text-to-speech' )
		);

		$this->field_row(
			'rvtts-v2_optin',
			__( 'Try the v2 WebPlayer', 'responsivevoice-text-to-speech' ),
			$control,
			__( 'Preview the new WebPlayer here. You can switch back to the older version anytime.', 'responsivevoice-text-to-speech' )
		);
	}

	/**
	 * Print the v2 WebPlayer enable control: a segmented Account default / Enabled /
	 * Disabled switch bound to a hidden `webplayer_mode` field. The admin JS builds
	 * the segmented control from this server-rendered mount.
	 */
	private function webplayer_mode_row(): void {
		$control = sprintf(
			'<div id="rvtts-webplayer-mode" class="rvtts-webplayer-mode">'
			. '<input type="hidden" id="rvtts-webplayer-mode-field" name="%1$s[webplayer_mode]" value="%2$s" />'
			. '</div>',
			esc_attr( Settings::OPTION ),
			esc_attr( $this->settings->get_webplayer_mode() )
		);

		$this->field_row(
			'rvtts-webplayer-mode-field',
			__( 'WebPlayer', 'responsivevoice-text-to-speech' ),
			$control,
			__( 'Show the WebPlayer on your posts and pages. "Account default" follows your ResponsiveVoice account setting.', 'responsivevoice-text-to-speech' )
		);
	}

	/**
	 * Print the per-type visibility list: an Enabled|Disabled pill per manageable
	 * post type, hidden with the customizer when the master is off.
	 *
	 * @param bool $active Whether the row starts visible.
	 */
	private function visibility_row( bool $active ): void {
		$enabled = $this->settings->get_enabled_post_types();

		$rows = '';
		foreach ( $this->settings->get_manageable_post_types() as $slug => $label ) {
			$state = in_array( $slug, $enabled, true ) ? 'on' : 'off';
			$rows .= sprintf(
				'<div class="rvtts-visibility__item">'
				. '<span class="rvtts-visibility__type">%1$s</span>'
				. '<div class="rvtts-visibility__control" data-rvtts-visibility="%2$s">'
				. '<input type="hidden" name="%3$s[post_types][%2$s]" value="%4$s" />'
				. '</div></div>',
				esc_html( $label ),
				esc_attr( $slug ),
				esc_attr( Settings::OPTION ),
				esc_attr( $state )
			);
		}

		$desc = sprintf(
			'%1$s<br />%2$s',
			esc_html__( 'Choose which content types show the WebPlayer by default.', 'responsivevoice-text-to-speech' ),
			esc_html__( 'Override it per post in the editor.', 'responsivevoice-text-to-speech' )
		);

		printf(
			'<div id="rvtts-webplayer-visibility" class="rvtts-field rvtts-visibility"%1$s>'
			. '<div class="rvtts-field__row"><span class="rvtts-field__label">%2$s</span>'
			. '<div class="rvtts-field__control rvtts-visibility__list">%3$s</div></div>'
			. '<p class="description rvtts-field__desc">%4$s</p></div>',
			$active ? '' : ' hidden',
			esc_html__( 'Show the WebPlayer on', 'responsivevoice-text-to-speech' ),
			$rows, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built + escaped above.
			$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html parts + literal <br />.
		);
	}

	/**
	 * Print the WebPlayer customizer + live-preview mount. Controls are built
	 * client-side from the shared schema; this just seeds apiKey + saved config
	 * and provides the mount points and the hidden field the form persists.
	 *
	 * @param bool $active          Whether the section starts visible (v2 enabled or
	 *                              opted-in); otherwise it renders hidden and dormant.
	 * @param bool $is_v2           Whether the account's default engine is v2 (status copy).
	 * @param bool $default_enabled What the "Account default" tri-state resolves to (the
	 *                              server's enabled), so the JS can toggle visibility live.
	 */
	private function customizer( bool $active, bool $is_v2, bool $default_enabled ): void {
		$config = (string) wp_json_encode( (object) $this->settings->get_webplayer_config_seed() );

		// Sample content for the player to attach to (its selectors target an article).
		$sample = sprintf(
			'<article id="rvtts-wp-preview-article"><h2>%1$s</h2><p>%2$s</p><p>%3$s</p></article>',
			esc_html__( 'Preview', 'responsivevoice-text-to-speech' ),
			esc_html__( 'This is a live preview of the ResponsiveVoice WebPlayer. Adjust the options on the left to see and hear how it will appear on your site.', 'responsivevoice-text-to-speech' ),
			esc_html__( 'The player attaches to your content and reads it aloud, paragraph by paragraph.', 'responsivevoice-text-to-speech' )
		);

		printf(
			'<div id="rvtts-customizer" class="rvtts-customizer" data-rvtts-apikey="%1$s" data-rvtts-config="%2$s"'
			. ' data-rvtts-verifyurl="%7$s" data-rvtts-status="%8$s"'
			. ' data-rvtts-unverified-msg="%9$s" data-rvtts-verify-cta="%10$s"'
			. ' data-rvtts-default-enabled="%12$s"%5$s>'
			. '<div class="rvtts-customizer__optionswrap">'
			. '<p class="rvtts-section-label">%11$s</p>'
			. '<div id="rvtts-customizer-controls" class="rvtts-customizer__controls"></div>'
			. '</div>'
			. '<div class="rvtts-customizer__previewwrap">'
			. '<p class="rvtts-section-label">%6$s</p>'
			. '<div class="rvtts-customizer__preview">%4$s</div>'
			. '</div>'
			. '<input type="hidden" id="rvtts-webplayer-config-field" name="%3$s[webplayer_config]" value="%2$s" />'
			. '</div>',
			esc_attr( $this->settings->get_api_key() ),
			esc_attr( $config ),
			esc_attr( Settings::OPTION ),
			$sample, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html__ parts above.
			$active ? '' : ' hidden',
			esc_html__( 'Live preview', 'responsivevoice-text-to-speech' ),
			esc_url( self::URL_DASHBOARD ),
			esc_attr( self::STATUS_ID ),
			esc_attr( $this->verify_notice_message( $is_v2 ) ),
			esc_attr__( 'Verify your website', 'responsivevoice-text-to-speech' ),
			esc_html__( 'Web Player', 'responsivevoice-text-to-speech' ),
			$default_enabled ? '1' : '0'
		);
	}

	/**
	 * Print the key-status / SDK-version notice.
	 *
	 * @param ConfigResult $probe Probe result.
	 */
	private function status_notice( ConfigResult $probe ): void {
		if ( '' === $this->settings->get_api_key() ) {
			$this->notice_link(
				'info',
				__( "Get started with ResponsiveVoice — register your website to receive an API key, then paste it below. Without a key, only your browser's built-in voice is used.", 'responsivevoice-text-to-speech' ),
				self::URL_REGISTER,
				__( 'Register your website', 'responsivevoice-text-to-speech' )
			);
			return;
		}

		if ( ! $probe->is_valid() ) {
			$this->notice_link(
				'error',
				__( "We couldn't validate this API key. Double-check it, or confirm your website is verified in your account.", 'responsivevoice-text-to-speech' ),
				self::URL_DASHBOARD,
				__( 'Open your account', 'responsivevoice-text-to-speech' )
			);
			return;
		}

		// Anyone using the v2 player (a v2 account, or a v1 account previewing via
		// the opt-in) must pass the v2 origin gate: a verified domain yields `auth`.
		$is_v2         = 'v2' === $probe->sdk_version();
		$optin_preview = ! $is_v2 && $this->settings->is_v2_optin();

		if ( $is_v2 || $optin_preview ) {
			$verified = $this->verification->is_verified();

			// The verification blocker has a stable id so the customizer can resolve
			// the unknown ("Confirming…") state live from the SDK's mount / AuthError.
			if ( false === $verified ) {
				$this->notice_link(
					'warning',
					$this->verify_notice_message( $is_v2 ),
					self::URL_DASHBOARD,
					__( 'Verify your website', 'responsivevoice-text-to-speech' ),
					self::STATUS_ID
				);
			} elseif ( null === $verified ) {
				$this->notice(
					'info',
					__( 'Confirming this website is verified…', 'responsivevoice-text-to-speech' ),
					self::STATUS_ID
				);
			} else {
				// Verified, so no confirmation notice; the status area is for real
				// problems only. Keep an empty, hidden slot so the customizer can
				// still surface a live AuthError.
				printf(
					'<div class="notice notice-warning" id="%s" hidden><p></p></div>',
					esc_attr( self::STATUS_ID )
				);
			}

			// A v1 account previewing v2 always runs a limited feature set, so it
			// gets a persistent advisory, independent of verification, in its own notice.
			if ( $optin_preview ) {
				$this->notice_link(
					'warning',
					$this->upgrade_advisory_message(),
					self::URL_DASHBOARD,
					__( 'Upgrade for free', 'responsivevoice-text-to-speech' ),
					self::UPGRADE_ID
				);
			}
			return;
		}

		$this->notice_link(
			'warning',
			sprintf(
				/* translators: %s: bold product name, "ResponsiveVoice v2". */
				__( "Your key is valid, but on the older version. Upgrade to %s — it's free — for 100+ voices across 50+ languages, including premium neural voices.", 'responsivevoice-text-to-speech' ),
				'<strong>' . esc_html__( 'ResponsiveVoice v2', 'responsivevoice-text-to-speech' ) . '</strong>'
			),
			self::URL_DASHBOARD,
			__( 'Upgrade for free', 'responsivevoice-text-to-speech' )
		);
	}

	/**
	 * Print a dismissible-style admin notice.
	 *
	 * @param string $type    One of info|success|error|warning.
	 * @param string $message Message text.
	 * @param string $id      Optional element id (for live client updates).
	 */
	private function notice( string $type, string $message, string $id = '' ): void {
		printf(
			'<div class="notice notice-%1$s"%3$s><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $message ),
			'' !== $id ? ' id="' . esc_attr( $id ) . '"' : ''
		);
	}

	/**
	 * Print an admin notice with a call-to-action button beneath the message.
	 *
	 * @param string $type    One of info|success|error|warning.
	 * @param string $message Message text; may contain a <strong> emphasis span.
	 * @param string $url     CTA URL.
	 * @param string $cta     CTA button text.
	 * @param string $id      Optional element id (for live client updates).
	 */
	private function notice_link( string $type, string $message, string $url, string $cta, string $id = '' ): void {
		printf(
			'<div class="notice notice-%1$s"%5$s><p>%2$s</p>'
			. '<p><a class="rvtts-cta" href="%3$s" target="_blank" rel="noopener noreferrer">%4$s</a></p></div>',
			esc_attr( $type ),
			wp_kses( $message, array( 'strong' => array() ) ),
			esc_url( $url ),
			esc_html( $cta ),
			'' !== $id ? ' id="' . esc_attr( $id ) . '"' : ''
		);
	}

	/**
	 * The "website not verified" notice, by tier (a v2 account vs a v1 opt-in
	 * preview). Shared by status_notice() and the customizer's live update.
	 *
	 * @param bool $is_v2 Whether the account's default engine is v2.
	 */
	private function verify_notice_message( bool $is_v2 ): string {
		return $is_v2
			? __( "Your account is on ResponsiveVoice v2, but this website isn't verified yet, so text to speech won't work — listen buttons and the WebPlayer can't play. Verify this site's domain in your dashboard.", 'responsivevoice-text-to-speech' )
			: __( "To preview the v2 WebPlayer, this website's domain must be verified in your dashboard first — until then, text to speech can't play on your site.", 'responsivevoice-text-to-speech' );
	}

	/**
	 * The persistent "you're on v1, upgrade" advisory shown to a v1 account
	 * previewing v2.
	 */
	private function upgrade_advisory_message(): string {
		return __( "You're previewing the v2 WebPlayer on a v1 account, so only a limited set of features is available. Upgrade to ResponsiveVoice v2 — it's free — to use them.", 'responsivevoice-text-to-speech' );
	}

	/**
	 * The "Reset to defaults" control in the Advanced section: a separator, then a
	 * button that stays disabled until there is something to clear. It's a plain
	 * type=button (the surrounding form posts to options.php); the admin JS confirms
	 * and POSTs to admin-post.php with the nonce carried on the button.
	 */
	private function reset_row(): void {
		$has_custom = $this->settings->has_custom_settings();

		printf(
			'<hr class="rvtts-advanced__sep" />'
			. '<div class="rvtts-field"><div class="rvtts-field__row">'
			. '<span class="rvtts-field__label">%1$s</span>'
			. '<div class="rvtts-field__control">'
			. '<button type="button" class="button rvtts-reset" data-rvtts-reset-url="%2$s" data-rvtts-reset-nonce="%3$s" data-rvtts-reset-confirm="%4$s"%5$s>%6$s</button>'
			. '</div></div>'
			. '<p class="description rvtts-field__desc">%7$s</p></div>',
			esc_html__( 'Reset', 'responsivevoice-text-to-speech' ),
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( wp_create_nonce( self::RESET_ACTION ) ),
			esc_attr__( 'Reset all settings to their defaults? Your API key is kept.', 'responsivevoice-text-to-speech' ),
			$has_custom ? '' : ' disabled',
			esc_html__( 'Reset to defaults', 'responsivevoice-text-to-speech' ),
			esc_html__( 'Restore all settings to their defaults. Your API key is kept.', 'responsivevoice-text-to-speech' )
		);
	}

	/**
	 * Print one settings row: a fixed-width label column, with the description on its own line under the control.
	 * It keeps the label vertically centred on the control, not on the taller label+description cell.
	 *
	 * @param string $control_id  The control's id, for the <label for>.
	 * @param string $label       Field label.
	 * @param string $control     Control HTML (built + escaped by the caller).
	 * @param string $description Help text (plain).
	 */
	private function field_row( string $control_id, string $label, string $control, string $description ): void {
		$desc = '' !== $description
		? '<p class="description rvtts-field__desc">' . esc_html( $description ) . '</p>'
		: '';

		printf(
			'<div class="rvtts-field"><div class="rvtts-field__row">'
			. '<label class="rvtts-field__label" for="%1$s">%2$s</label>'
			. '<div class="rvtts-field__control">%3$s</div>'
			. '</div>%4$s</div>',
			esc_attr( $control_id ),
			esc_html( $label ),
			$control, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built + escaped by the caller.
			$desc // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
		);
	}

	/**
	 * Build a text input control (escaped).
	 *
	 * @param string $key   Option sub-key.
	 * @param string $value Current value.
	 * @return string
	 */
	private function control_text( string $key, string $value ): string {
		return sprintf(
			'<input type="text" id="rvtts-%1$s" class="regular-text" name="%2$s[%1$s]" value="%3$s" />',
			esc_attr( $key ),
			esc_attr( Settings::OPTION ),
			esc_attr( $value )
		);
	}

	/**
	 * Build a select control (escaped).
	 *
	 * @param string                $key      Option sub-key.
	 * @param string                $selected Current value.
	 * @param array<string, string> $choices  Value => label.
	 * @return string
	 */
	private function control_select( string $key, string $selected, array $choices ): string {
		$options = '';
		foreach ( $choices as $value => $text ) {
			$options .= sprintf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $value ),
				selected( $selected, $value, false ),
				esc_html( $text )
			);
		}

		return sprintf(
			'<select id="rvtts-%1$s" name="%2$s[%1$s]">%3$s</select>',
			esc_attr( $key ),
			esc_attr( Settings::OPTION ),
			$options // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr/esc_html parts above.
		);
	}
}
