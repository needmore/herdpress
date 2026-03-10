<?php
/**
 * Production image proxy for local development.
 *
 * Redirects requests for missing uploads to the production server,
 * eliminating the need to sync the uploads directory locally.
 *
 * Requires `HERDPRESS_PRODUCTION_URL` to be defined in wp-config.php.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Redirect missing uploads to the production server.
 *
 * Hooks `template_redirect` at priority 0 (before `fix_static_404s` at 1).
 * Only activates when `HERDPRESS_PRODUCTION_URL` is defined.
 *
 * If the requested path is under the uploads directory and the file
 * doesn't exist locally, issues a 302 redirect to the production URL.
 * Local files are served normally; unmatched requests fall through to
 * the static 404 handler.
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
 * Only shown when `HERDPRESS_PRODUCTION_URL` is defined.
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
