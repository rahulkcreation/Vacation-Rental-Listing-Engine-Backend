<?php
/**
 * Plugin Name:       Listing Engine Backend
 * Plugin URI:        https://arttechfuzion.com
 * Description:       A comprehensive backend for managing vacation rental listing types and database schema.
 * Version:           1.2.0
 * Author:            Art-Tech Fuzion
 * Author URI:        https://arttechfuzion.com
 * Text Domain:       listing-engine-backend
 * License:           GPL-2.0-or-later
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Plugin Constants
// ─────────────────────────────────────────────────────────────
define( 'LEB_VERSION',      '1.2.0' );
define( 'LEB_PLUGIN_DIR',   plugin_dir_path( __FILE__ ) );
define( 'LEB_PLUGIN_URL',   plugin_dir_url( __FILE__ ) );
define( 'LEB_PLUGIN_FILE',  __FILE__ );

// ─────────────────────────────────────────────────────────────
// Load Dependencies
// ─────────────────────────────────────────────────────────────
require_once LEB_PLUGIN_DIR . 'includes/db-schema.php';
require_once LEB_PLUGIN_DIR . 'includes/class-db-handler.php';
require_once LEB_PLUGIN_DIR . 'includes/assets-loader.php';
require_once LEB_PLUGIN_DIR . 'includes/admin-hooks.php';

// ─────────────────────────────────────────────────────────────
// Activation / Deactivation Hooks
// NOTE: NO table creation on activation (as per spec).
// ─────────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'leb_activate' );
register_deactivation_hook( __FILE__, 'leb_deactivate' );

/**
 * Plugin activation callback.
 * Only flushes rewrite rules; does NOT create any DB tables.
 */
function leb_activate() {
    flush_rewrite_rules();
}

/**
 * Plugin deactivation callback.
 */
function leb_deactivate() {
    flush_rewrite_rules();
}
