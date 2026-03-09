<?php
/**
 * Debug configuration for local development.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Ensure debug constants are set for local development.
 *
 * These won't override values already defined in wp-config.php, but they
 * act as sensible fallbacks so you don't need to remember to set them
 * every time you spin up a new site.
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
