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
		'title' => '<span class="herdpress-dot" aria-hidden="true"></span>' . esc_html( $label ),
		'meta'  => [
			'class'    => 'menupop',
			'tabindex' => 0,
		],
	] );

	$host         = $_SERVER['HTTP_HOST'] ?? 'unknown';
	$php_version  = PHP_VERSION;
	$mail_port    = defined( 'HERDPRESS_SMTP_PORT' ) ? HERDPRESS_SMTP_PORT : '2525';
	$updates      = ( defined( 'HERDPRESS_BLOCK_UPDATE_CHECKS' ) && ! HERDPRESS_BLOCK_UPDATE_CHECKS ) ? 'Enabled' : 'Blocked';

	$details = [
		'host'    => $host,
		'php'     => 'PHP ' . $php_version,
		'mail'    => 'Mail via :' . $mail_port,
		'updates' => 'Updates: ' . $updates,
	];

	foreach ( $details as $id => $text ) {
		$wp_admin_bar->add_node( [
			'parent' => 'herdpress',
			'id'     => 'herdpress-' . $id,
			'title'  => esc_html( $text ),
		] );
	}
}

/**
 * Output admin bar styles on both frontend and admin.
 *
 * Colors:
 *   local      → green
 *   staging    → amber
 *   production → red (only visible if force-enabled via HERDPRESS_LOCAL)
 */
function admin_bar_styles(): void {
	if ( ! is_admin_bar_showing() ) {
		return;
	}

	$env    = defined( 'HERDPRESS_ENV' ) ? HERDPRESS_ENV : 'local';
	$colors = [
		'local'      => [ 'bar' => '#2a6e30', 'hover' => '#23572a' ],
		'staging'    => [ 'bar' => '#8c6210', 'hover' => '#6d4c0d' ],
		'production' => [ 'bar' => '#8b1a1a', 'hover' => '#6e1515' ],
	];

	$c = $colors[ $env ] ?? $colors['local'];

	?>
	<style>
		#wpadminbar {
			background: <?php echo $c['bar']; ?> !important;
		}
		#wpadminbar .ab-item,
		#wpadminbar a.ab-item,
		#wpadminbar > #wp-toolbar span.ab-label,
		#wpadminbar > #wp-toolbar span.noticon {
			color: rgba(255,255,255,.9) !important;
		}
		#wpadminbar .ab-top-menu > li:hover > .ab-item,
		#wpadminbar .ab-top-menu > li.hover > .ab-item {
			background: <?php echo $c['hover']; ?> !important;
			color: #fff !important;
		}
		#wpadminbar .ab-submenu,
		#wpadminbar .ab-sub-wrapper {
			background: <?php echo $c['hover']; ?> !important;
		}
		#wpadminbar .ab-submenu .ab-item,
		#wpadminbar .ab-sub-wrapper .ab-item {
			color: rgba(255,255,255,.85) !important;
		}
		#wpadminbar .ab-submenu .ab-item:hover,
		#wpadminbar .ab-sub-wrapper .ab-item:hover {
			color: #fff !important;
		}

		/* Status dot */
		#wpadminbar #wp-admin-bar-herdpress > .ab-item .herdpress-dot {
			display: inline-block;
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: rgba(255,255,255,.85);
			margin-right: 6px;
			vertical-align: middle;
			box-shadow: 0 0 0 2px rgba(255,255,255,.25);
		}

		/* Dashicon overrides for contrast */
		#wpadminbar .ab-icon,
		#wpadminbar .ab-item:before {
			color: rgba(255,255,255,.85) !important;
		}
	</style>
	<?php
}
