<?php
/**
 * Plugin Name:       ResponsiveVoice Text To Speech
 * Plugin URI:        https://responsivevoice.org/wordpress-text-to-speech-plugin/
 * Description:       Add HTML5 text-to-speech to your WordPress posts and pages with ResponsiveVoice.
 * Version:           2.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            ResponsiveVoice
 * Author URI:        https://responsivevoice.org
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       responsivevoice-text-to-speech
 *
 * @package ResponsiveVoice
 */

/*
 * Copyright 2015-2026 ResponsiveVoice
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

namespace ResponsiveVoice;

defined( 'ABSPATH' ) || exit;

const MIN_PHP = '7.4';

// Graceful PHP-floor guard: show a notice instead of a fatal error on older PHP.
if ( version_compare( PHP_VERSION, MIN_PHP, '<' ) ) {
	require_once __DIR__ . '/includes/php-too-old.php';
	return;
}

define( 'RVTTS_PLUGIN_FILE', __FILE__ );
define( 'RVTTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RVTTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RVTTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Single source of truth for the version: the plugin header (managed by release-sync).
$rvtts_headers = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
define( 'RVTTS_VERSION', '' !== $rvtts_headers['Version'] ? $rvtts_headers['Version'] : '0.0.0' );
unset( $rvtts_headers );

require_once __DIR__ . '/vendor/autoload.php';

register_activation_hook( __FILE__, array( Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Deactivator::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		Plugin::instance()->register();
	}
);
