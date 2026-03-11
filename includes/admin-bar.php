<?php
/**
 * Admin bar environment indicator.
 *
 * Adds a color-coded icon to the WordPress admin bar with environment
 * details in a hover dropdown. Colors the entire admin bar by environment.
 *
 * @package HerdPress
 */

namespace HerdPress;

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
		'title' => esc_html( $label ),
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

