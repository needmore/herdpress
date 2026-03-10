# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

HerdPress is a WordPress must-use (MU) plugin for local development on Laravel Herd. It auto-detects local environments and configures mail routing, static file 404 fixes, debug constants, update check blocking, and an admin status notice. It does nothing on production — safe if accidentally deployed.

## Architecture

Single-namespace (`HerdPress`) MU-plugin with a flat module structure:

- `herdpress.php` — Entry point. Runs environment detection, bails if not local, loads modules, registers all hooks.
- `herdpress-loader.php` — Standalone one-liner copied into `mu-plugins/` root to bootstrap the subdirectory plugin (WordPress only auto-loads root-level mu-plugin files).
- `includes/` — One file per feature, each in the `HerdPress` namespace:
  - `environment.php` — `is_local_environment()` detection chain
  - `mail.php` — PHPMailer SMTP override for Herd's mail server
  - `static-404s.php` — Intercepts missing static assets that Herd's Nginx misroutes
  - `debug.php` — Sets debug constants as fallback defaults
  - `update-blocker.php` — Short-circuits `api.wordpress.org` update-check requests
  - `plugin-deactivator.php` — Suppresses problematic plugins (caching, security, analytics, email) locally
  - `image-proxy.php` — 302 redirects missing uploads to production server
  - `admin-bar.php` — Color-coded admin bar with environment details via `herdpress_admin_bar_items` filter

All user-facing configuration constants use the `HERDPRESS_` prefix. The plugin defines `HERDPRESS_DIR` for internal path resolution.

## Development Notes

- No build step, no Composer, no tests — this is a plain PHP mu-plugin.
- All functions are namespaced under `HerdPress`, not class-based.
- Hook registration is centralized in `herdpress.php`, not scattered across modules.
- Modules are only loaded after environment detection passes — don't add side effects at file scope in `includes/`.
- The plugin must remain safe to deploy anywhere. Never add code that runs unconditionally outside the `is_local_environment()` gate.
