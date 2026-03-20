<?php
/**
 * Plugin Name: HerdPress
 * Description: Must-use plugin for local WordPress development on Laravel Herd. Reroutes email to Herd's mail server, fixes static file 404s, enables debug tooling, suppresses problematic plugins, and proxies missing uploads. Auto-detects local environment — safe if accidentally deployed.
 * Author: Needmore Designs
 * Author URI: https://needmoredesigns.com
 * Version: 2.0.0
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * Single-file mu-plugin — drop this directly into wp-content/mu-plugins/.
 * No loader file or subdirectory needed.
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


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Environment Detection
// ═══════════════════════════════════════════════════════════════════════════════
//
// Determines if we're running in a local Herd environment.
// This gate controls the entire plugin — nothing else loads if this returns false.
//
// Detection order:
// 1. Explicit HERDPRESS_LOCAL constant (true/false) — highest priority
// 2. HERD_HOME environment variable (set by Herd automatically)
// 3. .test TLD on the current hostname
// 4. Common local hostnames (localhost, *.local)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Determine if we're running in a local Herd environment.
 */
function is_local_environment(): bool {
	// Explicit override — lets you force on/off.
	if ( defined( 'HERDPRESS_LOCAL' ) ) {
		return (bool) HERDPRESS_LOCAL;
	}

	// Herd sets this env var when it manages the PHP process.
	if ( ! empty( getenv( 'HERD_HOME' ) ) ) {
		return true;
	}

	$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';

	// .test TLD (Herd default) or .local
	if ( preg_match( '/\.(test|local)$/i', $host ) ) {
		return true;
	}

	// Bare localhost
	if ( str_starts_with( $host, 'localhost' ) || $host === '127.0.0.1' ) {
		return true;
	}

	return false;
}


// ─── Environment Gate ────────────────────────────────────────────────────────
// Bail immediately if this isn't a local environment. Everything below this
// line only executes on local dev machines.
// ─────────────────────────────────────────────────────────────────────────────

if ( ! is_local_environment() ) {
	return;
}

if ( ! defined( 'HERDPRESS_ENV' ) ) {
	define( 'HERDPRESS_ENV', 'local' );
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Mail Routing
// ═══════════════════════════════════════════════════════════════════════════════
//
// Overrides WordPress's PHPMailer to send through Herd's built-in SMTP server.
// Herd Pro runs a local SMTP service on 127.0.0.1:2525 by default.
// All captured mail shows up in Herd's Mail UI, organized by app.
//
// If you're using Mailpit instead (common with Herd free), change the
// port to 1025 or define HERDPRESS_SMTP_PORT in wp-config.php.
//
// Constants: HERDPRESS_SMTP_HOST (default 127.0.0.1), HERDPRESS_SMTP_PORT (default 2525)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Configure PHPMailer to route through Herd's local SMTP.
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
 */
function configure_herd_mail( $phpmailer ): void {
	$phpmailer->isSMTP();
	$phpmailer->Host       = defined( 'HERDPRESS_SMTP_HOST' ) ? HERDPRESS_SMTP_HOST : '127.0.0.1';
	$phpmailer->Port       = defined( 'HERDPRESS_SMTP_PORT' ) ? HERDPRESS_SMTP_PORT : 2525;
	$phpmailer->SMTPAuth   = false;
	$phpmailer->SMTPSecure = '';

	// Tag the sender so Herd groups mail by project.
	$site_name = sanitize_title( get_bloginfo( 'name' ) ?: basename( ABSPATH ) );
	$phpmailer->Sender = $phpmailer->From = "{$site_name}@localhost";

	// Override From name to make it obvious in the Herd mail UI.
	if ( $phpmailer->FromName === 'WordPress' ) {
		$phpmailer->FromName = get_bloginfo( 'name' ) . ' (Local)';
	}
}

/**
 * Add mail port status to the admin bar.
 *
 * @param array $items Current admin bar items.
 * @return array
 */
function admin_bar_mail_item( array $items ): array {
	$port          = defined( 'HERDPRESS_SMTP_PORT' ) ? HERDPRESS_SMTP_PORT : 2525;
	$items['mail'] = 'Mail via :' . $port;

	return $items;
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Static File 404 Fix
// ═══════════════════════════════════════════════════════════════════════════════
//
// Herd's Nginx config redirects missing static files (images, fonts, CSS, JS)
// to the homepage with a 200 status instead of returning a proper 404.
// This intercepts those requests early and returns a clean 404 response.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Intercept missing static asset requests and return a proper 404.
 */
function fix_static_404s(): void {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) {
		return;
	}

	$request_uri = $_SERVER['REQUEST_URI'] ?? '';

	// Only act on requests that look like static files.
	$static_extensions = '/\.(?:jpg|jpeg|png|gif|webp|avif|svg|ico|css|js|map|woff2?|ttf|eot|otf|pdf|mp4|webm|mp3|zip)(?:\?.*)?$/i';
	if ( ! preg_match( $static_extensions, $request_uri ) ) {
		return;
	}

	// Strip query string and decode for filesystem check.
	$path      = parse_url( $request_uri, PHP_URL_PATH );
	$file_path = ABSPATH . ltrim( urldecode( $path ), '/' );

	if ( ! file_exists( $file_path ) ) {
		status_header( 404 );
		header( 'Content-Type: text/plain; charset=UTF-8' );
		echo '404 — File not found: ' . esc_html( $path );
		exit;
	}
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Debug Constants
// ═══════════════════════════════════════════════════════════════════════════════
//
// Sets sensible debug defaults for local development without overriding
// anything already defined in wp-config.php. Acts as a safety net so you
// don't need to remember to set these every time you spin up a new site.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Define debug constants as fallback defaults.
 */
function configure_debug_constants(): void {
	if ( ! defined( 'SAVEQUERIES' ) ) {
		define( 'SAVEQUERIES', true ); // Enables Query Monitor's query tracking.
	}

	if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
		define( 'WP_DEBUG_DISPLAY', true );
	}

	if ( ! defined( 'SCRIPT_DEBUG' ) ) {
		define( 'SCRIPT_DEBUG', true ); // Use unminified core JS/CSS.
	}

	if ( ! defined( 'WP_ENVIRONMENT_TYPE' ) ) {
		define( 'WP_ENVIRONMENT_TYPE', 'local' );
	}

	if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
		define( 'DISALLOW_FILE_EDIT', true ); // No code editing in wp-admin, even locally.
	}
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: WooCommerce Staging Mode
// ═══════════════════════════════════════════════════════════════════════════════
//
// Forces WooCommerce into staging mode on local environments, which disables
// live payment processing and pauses subscription renewals. Uses WooCommerce's
// built-in WC_STAGING constant. Only activates when WooCommerce is present.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Enable WooCommerce staging mode if WooCommerce is active.
 */
function enable_woocommerce_staging(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! defined( 'WC_STAGING' ) ) {
		define( 'WC_STAGING', true );
	}
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Plugin Deactivator
// ═══════════════════════════════════════════════════════════════════════════════
//
// Prevents caching, CDN/security, analytics, email, and email marketing
// plugins from running locally where they cause problems or interfere with
// Herd's built-in services.
//
// Constants: HERDPRESS_DEACTIVATE_PLUGINS
//   - false    → feature disabled entirely
//   - array    → custom slug list
//   - unset    → default list
//
// Filters: herdpress_deactivated_plugins — modify the slug list
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Get the list of plugin slugs to suppress locally.
 *
 * @return array Plugin directory slugs to suppress.
 */
function get_deactivated_plugins(): array {
	if ( defined( 'HERDPRESS_DEACTIVATE_PLUGINS' ) ) {
		if ( HERDPRESS_DEACTIVATE_PLUGINS === false ) {
			return [];
		}
		if ( is_array( HERDPRESS_DEACTIVATE_PLUGINS ) ) {
			return apply_filters( 'herdpress_deactivated_plugins', HERDPRESS_DEACTIVATE_PLUGINS );
		}
	}

	$defaults = [
		// Caching
		'w3-total-cache',
		'wp-super-cache',
		'wp-rocket',
		'litespeed-cache',
		'wp-fastest-cache',

		// CDN / Security
		'cloudflare',
		'wordfence',
		'sucuri-scanner',
		'better-wp-security',

		// Analytics
		'google-analytics-for-wordpress',
		'google-site-kit',

		// Email (can bypass Herd's mail routing)
		'wp-mail-smtp',
		'fluent-smtp',
		'post-smtp',
		'smtp-mailer',

		// Email marketing / newsletters
		'mailchimp-for-woocommerce',
		'mailchimp-for-wp',
		'constant-contact-forms',
		'newsletter',
		'mailpoet',
		'the-newsletter-plugin',

		// Marketing automation
		'klaviyo',
		'hubspot-for-woocommerce',
		'convertkit',
	];

	return apply_filters( 'herdpress_deactivated_plugins', $defaults );
}

/**
 * Filter active plugins to remove suppressed ones.
 *
 * Hooked to `option_active_plugins` at priority 1 so it runs before
 * WordPress loads any plugins.
 *
 * @param array $plugins Active plugin paths (e.g. 'wp-mail-smtp/wp_mail_smtp.php').
 * @return array Filtered plugin list with suppressed plugins removed.
 */
function deactivate_plugins_locally( array $plugins ): array {
	$suppressed = get_deactivated_plugins();

	if ( empty( $suppressed ) ) {
		return $plugins;
	}

	$filtered = array_filter( $plugins, function ( $plugin_path ) use ( $suppressed ) {
		$slug = dirname( $plugin_path );
		return ! in_array( $slug, $suppressed, true );
	} );

	return array_values( $filtered );
}

/**
 * Add suppressed plugin count to the admin bar.
 *
 * @param array $items Current admin bar items.
 * @return array
 */
function admin_bar_deactivator_item( array $items ): array {
	$suppressed = get_deactivated_plugins();

	if ( empty( $suppressed ) ) {
		return $items;
	}

	// Get the raw active plugins list (bypass our own filter).
	$raw_plugins = get_option( 'active_plugins', [] );
	$count       = 0;

	foreach ( $raw_plugins as $plugin_path ) {
		if ( in_array( dirname( $plugin_path ), $suppressed, true ) ) {
			$count++;
		}
	}

	if ( $count > 0 ) {
		$items['plugins'] = $count . ' plugin' . ( $count !== 1 ? 's' : '' ) . ' suppressed';
	}

	return $items;
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Production Image Proxy
// ═══════════════════════════════════════════════════════════════════════════════
//
// Redirects requests for missing uploads to the production server, eliminating
// the need to sync the uploads directory locally. Only activates when
// HERDPRESS_PRODUCTION_URL is defined in wp-config.php.
//
// Constants: HERDPRESS_PRODUCTION_URL, HERDPRESS_UPLOADS_PATH (default wp-content/uploads)
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Redirect missing uploads to the production server.
 *
 * Hooks `template_redirect` at priority 0 (before `fix_static_404s` at 1).
 */
function proxy_uploads_to_production(): void {
	if ( ! defined( 'HERDPRESS_PRODUCTION_URL' ) ) {
		return;
	}

	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || defined( 'REST_REQUEST' ) ) {
		return;
	}

	$uploads_path = defined( 'HERDPRESS_UPLOADS_PATH' )
		? trim( HERDPRESS_UPLOADS_PATH, '/' )
		: 'wp-content/uploads';

	$request_uri = $_SERVER['REQUEST_URI'] ?? '';
	$path        = parse_url( $request_uri, PHP_URL_PATH );

	if ( ! str_starts_with( ltrim( $path, '/' ), $uploads_path ) ) {
		return;
	}

	$file_path = ABSPATH . ltrim( urldecode( $path ), '/' );

	if ( file_exists( $file_path ) ) {
		return;
	}

	$production_url = rtrim( HERDPRESS_PRODUCTION_URL, '/' ) . $path;

	header( 'X-HerdPress: image-proxy' );
	wp_redirect( $production_url, 302 );
	exit;
}

/**
 * Add image proxy status to the admin bar.
 *
 * @param array $items Current admin bar items.
 * @return array
 */
function admin_bar_image_proxy_item( array $items ): array {
	if ( ! defined( 'HERDPRESS_PRODUCTION_URL' ) ) {
		return $items;
	}

	$host             = parse_url( HERDPRESS_PRODUCTION_URL, PHP_URL_HOST ) ?: HERDPRESS_PRODUCTION_URL;
	$items['images']  = 'Images via ' . $host;

	return $items;
}


// ═══════════════════════════════════════════════════════════════════════════════
// MODULE: Admin Bar
// ═══════════════════════════════════════════════════════════════════════════════
//
// Adds a status dot and environment label to the WordPress admin bar with
// detail sub-items in a hover dropdown (hostname, PHP version, mail config,
// suppressed plugins, image proxy target).
//
// Filters: herdpress_admin_bar_items — add or modify detail items
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Add HerdPress node and detail sub-items to the admin bar.
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function register_admin_bar_menu( \WP_Admin_Bar $wp_admin_bar ): void {
	$env   = defined( 'HERDPRESS_ENV' ) ? HERDPRESS_ENV : 'local';
	$label = ucfirst( $env );

	$wp_admin_bar->add_node( [
		'id'    => 'herdpress',
		'title' => '<span class="herdpress-dot" aria-hidden="true"></span>' . esc_html( $label ),
		'meta'  => [
			'class'    => 'menupop',
			'tabindex' => 0,
		],
	] );

	$items = [
		'host' => $_SERVER['HTTP_HOST'] ?? 'unknown',
		'php'  => 'PHP ' . PHP_VERSION,
	];

	$details = apply_filters( 'herdpress_admin_bar_items', $items );

	foreach ( $details as $id => $text ) {
		$wp_admin_bar->add_node( [
			'parent' => 'herdpress',
			'id'     => 'herdpress-' . $id,
			'title'  => esc_html( $text ),
		] );
	}
}

/**
 * Output minimal styles for the status dot.
 */
function admin_bar_styles(): void {
	if ( ! is_admin_bar_showing() ) {
		return;
	}
	?>
	<style>
		#wpadminbar #wp-admin-bar-herdpress > .ab-item .herdpress-dot {
			display: inline-block;
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: #3bc248;
			margin-right: 6px;
			vertical-align: middle;
		}
	</style>
	<?php
}


// ═══════════════════════════════════════════════════════════════════════════════
// BOOTSTRAP: Hook Registration
// ═══════════════════════════════════════════════════════════════════════════════
//
// All WordPress hooks are registered here in one place. This makes it easy to
// see every entry point at a glance and understand execution order.
// ═══════════════════════════════════════════════════════════════════════════════

add_action( 'phpmailer_init',            __NAMESPACE__ . '\\configure_herd_mail', 999 );
add_action( 'template_redirect',         __NAMESPACE__ . '\\proxy_uploads_to_production', 0 );
add_action( 'template_redirect',         __NAMESPACE__ . '\\fix_static_404s', 1 );
add_action( 'init',                      __NAMESPACE__ . '\\configure_debug_constants', 1 );
add_action( 'plugins_loaded',            __NAMESPACE__ . '\\enable_woocommerce_staging', 1 );
add_filter( 'option_active_plugins',     __NAMESPACE__ . '\\deactivate_plugins_locally', 1 );
add_filter( 'herdpress_admin_bar_items', __NAMESPACE__ . '\\admin_bar_mail_item', 10 );
add_filter( 'herdpress_admin_bar_items', __NAMESPACE__ . '\\admin_bar_deactivator_item', 30 );
add_filter( 'herdpress_admin_bar_items', __NAMESPACE__ . '\\admin_bar_image_proxy_item', 40 );
add_action( 'admin_bar_menu',            __NAMESPACE__ . '\\register_admin_bar_menu', 999 );
add_action( 'wp_head',                   __NAMESPACE__ . '\\admin_bar_styles' );
add_action( 'admin_head',               __NAMESPACE__ . '\\admin_bar_styles' );
