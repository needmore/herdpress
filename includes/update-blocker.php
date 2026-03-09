<?php
/**
 * Block external update checks to speed up local dev.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Optionally block outbound update check requests to speed up local dev.
 *
 * WordPress phones home frequently for core/plugin/theme updates, which
 * slows down page loads on local sites. This intercepts those specific
 * requests and returns an empty response.
 *
 * To disable this behavior (if you want update checks locally):
 *   define( 'HERDPRESS_BLOCK_UPDATE_CHECKS', false );
 *
 * @param mixed  $preempt Whether to preempt an HTTP request's return value.
 * @param array  $parsed_args HTTP request arguments.
 * @param string $url The request URL.
 * @return mixed
 */
function block_update_checks( $preempt, $parsed_args, $url ) {
	if ( defined( 'HERDPRESS_BLOCK_UPDATE_CHECKS' ) && ! HERDPRESS_BLOCK_UPDATE_CHECKS ) {
		return $preempt;
	}

	$blocked_hosts = [
		'api.wordpress.org',
	];

	$host = parse_url( $url, PHP_URL_HOST );
	$path = parse_url( $url, PHP_URL_PATH ) ?? '';

	// Only block update-related endpoints, not the plugin/theme API entirely
	// (you still want to be able to search/install plugins).
	$update_paths = [
		'/core/version-check/',
		'/plugins/update-check/',
		'/themes/update-check/',
	];

	if ( in_array( $host, $blocked_hosts, true ) ) {
		foreach ( $update_paths as $update_path ) {
			if ( str_contains( $path, $update_path ) ) {
				return [
					'response' => [ 'code' => 200, 'message' => 'OK' ],
					'body'     => json_encode( (object) [ 'offers' => [], 'plugins' => (object) [], 'themes' => (object) [] ] ),
					'headers'  => [],
					'cookies'  => [],
				];
			}
		}
	}

	return $preempt;
}
