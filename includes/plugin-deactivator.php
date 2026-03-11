<?php
/**
 * Plugin deactivation for local development.
 *
 * Prevents caching, CDN/security, analytics, and email plugins from
 * running locally where they cause problems or interfere with Herd's
 * built-in services.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Get the list of plugin slugs to suppress locally.
 *
 * Checks `HERDPRESS_DEACTIVATE_PLUGINS`:
 *   - false        → feature disabled entirely
 *   - array        → custom slug list
 *   - undefined    → default list
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
 * Shows only the count of plugins actually suppressed (intersection of
 * suppression list and active plugins), not the total list size.
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
