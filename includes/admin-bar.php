<?php
/**
 * Admin bar environment indicator.
 *
 * Adds a status dot and environment label to the WordPress admin bar
 * with details in a hover dropdown.
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

