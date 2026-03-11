# WooCommerce Staging Mode & Expanded Plugin Suppression

**Date:** 2026-03-11
**Status:** Approved

## Summary

Two additions to HerdPress:

1. Automatically enable WooCommerce staging mode on local environments
2. Expand the default plugin suppression list to include email marketing and newsletter plugins

## 1. WooCommerce Staging Mode

### What

New module `includes/woocommerce.php` that defines `WC_STAGING` constant when WooCommerce is active locally. This is WooCommerce's built-in staging mechanism — it disables live payment processing and pauses subscription renewals.

### How

- Hook: `plugins_loaded` (runs after WooCommerce has loaded)
- Check: `class_exists('WooCommerce')` to confirm WC is active
- Action: `define('WC_STAGING', true)` if not already defined
- No admin bar item — the existing "Local" dot already signals the environment

### Files

- **New:** `includes/woocommerce.php` — single function, `enable_woocommerce_staging()`
- **Modified:** `herdpress.php` — add `require_once` and hook registration

## 2. Expanded Plugin Suppression List

### What

Add email marketing, newsletter, and marketing automation plugins to the default suppression list in `plugin-deactivator.php`.

### New slugs

Email marketing / newsletters:
- `mailchimp-for-woocommerce`
- `mailchimp-for-wp`
- `constant-contact-forms`
- `newsletter`
- `mailpoet`
- `the-newsletter-plugin`

Marketing automation:
- `klaviyo`
- `hubspot-for-woocommerce`
- `convertkit`

### Files

- **Modified:** `includes/plugin-deactivator.php` — add slugs to `$defaults` array with new comment sections

## Out of scope

- WooCommerce action scheduler suppression (mail routing via Herd SMTP is sufficient)
- Admin bar items for WooCommerce status
- WooCommerce webhook suppression (staging mode handles this)
