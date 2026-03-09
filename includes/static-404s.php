<?php
/**
 * Static file 404 fix for Herd's Nginx routing.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Fix Herd's Nginx routing issue where missing static files (images, fonts,
 * etc.) get 301-redirected to the homepage instead of returning a proper 404.
 *
 * This hooks very early on template_redirect and checks if the request URI
 * looks like a static asset that doesn't exist on disk. If so, it sends a
 * clean 404 response and exits, preventing WordPress from rendering the
 * homepage with a 200 status.
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
