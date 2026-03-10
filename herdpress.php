<?php
/**
 * Plugin Name: HerdPress
 * Description: Must-use plugin for local WordPress development on Laravel Herd. Reroutes email to Herd's mail server, fixes static file 404s, enables debug tooling, and sets environment flags. Auto-detects local environment — safe if accidentally deployed.
 * Author: Needmore Designs
 * Author URI: https://needmoredesigns.com
 * Version: 1.0.0
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * This plugin lives in a directory (mu-plugins/herdpress/) and requires a
 * one-line loader file in the mu-plugins root. See herdpress-loader.php.
 *
 * It auto-detects whether it's running locally (via .test TLD, HERD_HOME
 * env var, or explicit constant) and does nothing on production.
 *
 * To force-enable outside of .test domains, define in wp-config.php:
 *   define( 'HERDPRESS_LOCAL', true );
 *
 * To force-disable even on .test domains:
 *   define( 'HERDPRESS_LOCAL', false );
 */

namespace HerdPress;

defined( 'ABSPATH' ) || exit;

// Plugin root path for includes.
define( 'HERDPRESS_DIR', __DIR__ );

// Load environment detection first — everything else depends on it.
require_once HERDPRESS_DIR . '/includes/environment.php';

// Bail immediately if this isn't a local environment.
if ( ! is_local_environment() ) {
	return;
}

// Set the environment flag so theme/plugin code can check it.
if ( ! defined( 'HERDPRESS_ENV' ) ) {
	define( 'HERDPRESS_ENV', 'local' );
}

// Load feature modules.
require_once HERDPRESS_DIR . '/includes/mail.php';
require_once HERDPRESS_DIR . '/includes/static-404s.php';
require_once HERDPRESS_DIR . '/includes/debug.php';
require_once HERDPRESS_DIR . '/includes/update-blocker.php';
require_once HERDPRESS_DIR . '/includes/admin-bar.php';

// ─── Bootstrap ───────────────────────────────────────────────────────────────

add_action( 'phpmailer_init',    __NAMESPACE__ . '\\configure_herd_mail', 999 );
add_action( 'template_redirect', __NAMESPACE__ . '\\fix_static_404s', 1 );
add_action( 'init',              __NAMESPACE__ . '\\configure_debug_constants', 1 );
add_filter( 'pre_http_request',  __NAMESPACE__ . '\\block_update_checks', 10, 3 );
add_action( 'admin_bar_menu',    __NAMESPACE__ . '\\register_admin_bar_menu', 999 );
add_action( 'wp_head',           __NAMESPACE__ . '\\admin_bar_styles' );
add_action( 'admin_head',        __NAMESPACE__ . '\\admin_bar_styles' );
