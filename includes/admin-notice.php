<?php
/**
 * Admin notice for local environment indicator.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Show a subtle admin notice so it's always clear you're on a local site.
 * Helps prevent the "wait, am I on staging or local?" confusion.
 */
function local_environment_notice(): void {
	$host         = $_SERVER['HTTP_HOST'] ?? 'unknown';
	$php_version  = PHP_VERSION;
	$herd_home    = getenv( 'HERD_HOME' ) ?: 'not detected';
	$mail_status  = 'Herd SMTP (port ' . ( defined( 'HERDPRESS_SMTP_PORT' ) ? HERDPRESS_SMTP_PORT : '2525' ) . ')';
	$update_block = ( defined( 'HERDPRESS_BLOCK_UPDATE_CHECKS' ) && ! HERDPRESS_BLOCK_UPDATE_CHECKS ) ? 'Off' : 'On';

	printf(
		'<div class="notice notice-info" style="padding:8px 12px;background:#f0f7ff;border-left-color:#2271b1;">
			<strong>HerdPress</strong> &nbsp;—&nbsp; %s &nbsp;·&nbsp; PHP %s &nbsp;·&nbsp; Mail: %s &nbsp;·&nbsp; Update blocks: %s
		</div>',
		esc_html( $host ),
		esc_html( $php_version ),
		esc_html( $mail_status ),
		esc_html( $update_block )
	);
}
