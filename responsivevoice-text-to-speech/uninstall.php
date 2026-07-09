<?php
/**
 * Uninstall cleanup — removes the plugin's stored data.
 *
 * @package ResponsiveVoice
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

ResponsiveVoice\Uninstaller::run();
