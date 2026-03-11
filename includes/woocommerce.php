<?php
/**
 * WooCommerce staging mode.
 *
 * Forces WooCommerce into staging mode on local environments, which
 * disables live payment processing and pauses subscription renewals.
 *
 * @package HerdPress
 */

namespace HerdPress;

/**
 * Enable WooCommerce staging mode if WooCommerce is active.
 *
 * Hooked to `plugins_loaded` so WooCommerce has already been loaded.
 * The `WC_STAGING` constant is WooCommerce's built-in mechanism for
 * disabling live transactions.
 */
function enable_woocommerce_staging(): void {
	if ( ! class_exists( 'WooCommerce' ) ) {
		return;
	}

	if ( ! defined( 'WC_STAGING' ) ) {
		define( 'WC_STAGING', true );
	}
}
