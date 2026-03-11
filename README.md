# HerdPress

A WordPress must-use plugin for local development on [Laravel Herd](https://herd.laravel.com). Drop it in, and it handles the annoyances — mail routing, broken static files, debug constants, problematic plugins, and missing uploads — so you can focus on building.

HerdPress auto-detects whether it's running locally and does absolutely nothing if it lands on a production server.

## What it does

**Mail routing** — Redirects all WordPress email through Herd's built-in SMTP server (port 2525). Mail shows up in Herd's Mail UI, grouped by project. No emails accidentally sent to real users.

**Static file 404 fix** — Herd's Nginx config redirects missing static files (images, fonts, CSS, JS) to the homepage with a 200 status instead of returning a proper 404. HerdPress intercepts these and returns a clean 404 response.

**Debug constants** — Sets sensible defaults for local development (`SAVEQUERIES`, `WP_DEBUG_DISPLAY`, `SCRIPT_DEBUG`, `WP_ENVIRONMENT_TYPE`, `DISALLOW_FILE_EDIT`) without overriding anything you've already defined in `wp-config.php`.

**Plugin deactivation** — Automatically suppresses plugins that cause problems locally: caching (W3 Total Cache, WP Super Cache, WP Rocket, etc.), CDN/security (Cloudflare, Wordfence, Sucuri), analytics (Google Analytics, Site Kit), and email (WP Mail SMTP, Fluent SMTP, Post SMTP). Email plugins are especially important to suppress since they can bypass Herd's mail routing even with HerdPress's `phpmailer_init` hook.

**Production image proxy** — Redirects requests for missing uploads to your production server, so you don't need to sync the entire `wp-content/uploads` directory. Just define `HERDPRESS_PRODUCTION_URL` and missing images will 302 redirect to production. Local files are served normally.

**Admin bar** — Adds an admin bar menu with environment details in a dropdown: hostname, PHP version, mail config, suppressed plugin count, and image proxy target.

## Installation

HerdPress is a directory-based mu-plugin. WordPress only auto-loads PHP files in the `mu-plugins` root, so you need both the directory and a one-line loader file.

```bash
# Symlink the plugin into mu-plugins
ln -s /path/to/herdpress wp-content/mu-plugins/herdpress

# Copy the loader into the mu-plugins root
cp /path/to/herdpress/herdpress-loader.php wp-content/mu-plugins/
```

That's it. No activation step — mu-plugins load automatically.

## Environment detection

HerdPress determines it's running locally using these checks, in order:

1. **`HERDPRESS_LOCAL` constant** — Explicit override. Define `true` or `false` in `wp-config.php`.
2. **`HERD_HOME` env var** — Set automatically by Herd when it manages the PHP process.
3. **`.test` or `.local` TLD** — Matches Herd's default domain setup.
4. **`localhost` / `127.0.0.1`** — Fallback for bare localhost setups.

If none of these match, the plugin returns early and loads nothing.

## Configuration

All configuration is optional. Define any of these constants in `wp-config.php` to override defaults:

| Constant | Default | Description |
|---|---|---|
| `HERDPRESS_LOCAL` | *(auto-detected)* | Force enable (`true`) or disable (`false`) the plugin |
| `HERDPRESS_SMTP_HOST` | `127.0.0.1` | SMTP server host |
| `HERDPRESS_SMTP_PORT` | `2525` | SMTP server port (use `1025` for Mailpit) |
| `HERDPRESS_DEACTIVATE_PLUGINS` | *(default list)* | `false` to disable, or an array of plugin slugs |
| `HERDPRESS_PRODUCTION_URL` | *(unset)* | Production URL for image proxy (e.g. `https://example.com`) |
| `HERDPRESS_UPLOADS_PATH` | `wp-content/uploads` | Custom uploads path for image proxy |
| `HERDPRESS_ENV` | `local` | Environment identifier, available to your theme/plugin code |

### Filters

| Filter | Description |
|---|---|
| `herdpress_deactivated_plugins` | Modify the list of plugin slugs to suppress locally |
| `herdpress_admin_bar_items` | Add or modify admin bar status items |

## Requirements

- PHP 8.1+
- WordPress 6.0+
- [Laravel Herd](https://herd.laravel.com) (Pro recommended for built-in mail)

## License

MIT — see [LICENSE](LICENSE).

Built by [Needmore Designs](https://needmoredesigns.com).
