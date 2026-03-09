<?php
/**
 * Environment detection.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Determine if we're running in a local Herd environment.
 *
 * Detection order:
 * 1. Explicit HERDPRESS_LOCAL constant (true/false) — highest priority
 * 2. HERD_HOME environment variable (set by Herd automatically)
 * 3. .test TLD on the current hostname
 * 4. Common local hostnames (localhost, *.local)
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
