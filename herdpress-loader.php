<?php
/**
 * HerdPress MU-Plugin Loader
 *
 * Copy or symlink this file into wp-content/mu-plugins/ to load HerdPress
 * from its subdirectory. WordPress only auto-loads single PHP files in the
 * mu-plugins root, not files inside subdirectories.
 *
 * If the herdpress directory is symlinked into mu-plugins:
 *   ln -s /path/to/herdpress wp-content/mu-plugins/herdpress
 *   cp /path/to/herdpress/herdpress-loader.php wp-content/mu-plugins/
 */

require_once __DIR__ . '/herdpress/herdpress.php';
