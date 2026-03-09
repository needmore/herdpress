<?php
/**
 * Email: Route through Herd's local mail server.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Override WordPress's PHPMailer to send through Herd's built-in SMTP server.
 *
 * Herd Pro runs a local SMTP service on 127.0.0.1:2525 by default.
 * All captured mail shows up in Herd's Mail UI, organized by app.
 *
 * If you're using Mailpit instead (common with Herd free), change the
 * port to 1025 or define HERDPRESS_SMTP_PORT in wp-config.php.
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer
 */
function configure_herd_mail( $phpmailer ): void {
	$phpmailer->isSMTP();
	$phpmailer->Host       = defined( 'HERDPRESS_SMTP_HOST' ) ? HERDPRESS_SMTP_HOST : '127.0.0.1';
	$phpmailer->Port       = defined( 'HERDPRESS_SMTP_PORT' ) ? HERDPRESS_SMTP_PORT : 2525;
	$phpmailer->SMTPAuth   = false;
	$phpmailer->SMTPSecure = '';

	// Tag the sender so Herd groups mail by project.
	$site_name = sanitize_title( get_bloginfo( 'name' ) ?: basename( ABSPATH ) );
	$phpmailer->Sender = $phpmailer->From = "{$site_name}@localhost";

	// Override From name to make it obvious in the Herd mail UI.
	if ( $phpmailer->FromName === 'WordPress' ) {
		$phpmailer->FromName = get_bloginfo( 'name' ) . ' (Local)';
	}
}
