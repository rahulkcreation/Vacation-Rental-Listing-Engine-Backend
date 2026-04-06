<?php
/**
 * assets-loader.php
 *
 * Centralized asset path management and enqueue logic for the
 * Listing Engine Backend plugin.
 *
 * All CSS/JS files are loaded ONLY on LEB admin pages.
 * Cache-busting uses filemtime() so browsers always receive updated files.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Path / URL Constants
// ─────────────────────────────────────────────────────────────

/**
 * Full URL to the plugin's assets directory.
 * Example: https://example.com/wp-content/plugins/listing-engine-backend/assets/
 */
define( 'LEB_ASSETS_URL',       LEB_PLUGIN_URL . 'assets/' );

/**
 * Full URL to the plugin's CSS directory.
 * Example: …/assets/css/
 */
define( 'LEB_ASSETS_CSS_URL',   LEB_PLUGIN_URL . 'assets/css/' );

/**
 * Full URL to the plugin's JS directory.
 * Example: …/assets/js/
 */
define( 'LEB_ASSETS_JS_URL',    LEB_PLUGIN_URL . 'assets/js/' );

/**
 * Absolute server path to the plugin's templates directory.
 * Example: /var/www/html/wp-content/plugins/listing-engine-backend/templates/
 */
define( 'LEB_TEMPLATES_PATH',   LEB_PLUGIN_DIR . 'templates/' );

// ─────────────────────────────────────────────────────────────
// Enqueue Hook
// ─────────────────────────────────────────────────────────────

add_action( 'admin_enqueue_scripts', 'leb_admin_enqueue_scripts' );

/**
 * Master enqueue callback.
 * Detects the current admin page and selectively loads LEB assets.
 *
 * @param string $hook_suffix The hook suffix for the current admin page.
 */
function leb_admin_enqueue_scripts( string $hook_suffix ) {

    // Only proceed on LEB-owned pages.
    // WP generates page hooks like: "leb-types_page_leb-database", "toplevel_page_leb-types".
    if ( ! leb_is_leb_page( $hook_suffix ) ) {
        return;
    }

    // Global assets – loaded on every LEB page.
    leb_enqueue_global_styles();
    leb_enqueue_toaster_assets();

    // AJAX configuration – must be localized early (in header) so inline
    // template scripts can read LEB_Ajax.ajax_url and LEB_Ajax.nonce.
    leb_localize_ajax_data();

    // Page-specific admin styles.
    leb_enqueue_admin_styles( $hook_suffix );
}

// ─────────────────────────────────────────────────────────────
// Individual Enqueue Functions
// ─────────────────────────────────────────────────────────────

/**
 * Enqueue the global CSS variable/typography stylesheet.
 * Uses filemtime() for reliable cache-busting on every deploy.
 */
function leb_enqueue_global_styles() {
    $file_path = LEB_PLUGIN_DIR . 'assets/global.css';

    wp_enqueue_style(
        'leb-global',
        LEB_ASSETS_URL . 'global.css',
        [],
        file_exists( $file_path ) ? (string) filemtime( $file_path ) : LEB_VERSION
    );
}

/**
 * Enqueue the Toaster notification CSS and JS bundle.
 * The JS file is loaded in the header so that the LEB_Toaster object
 * and localized LEB_Ajax data are available before inline template scripts execute.
 */
function leb_enqueue_toaster_assets() {
    // Toast Notification system.
    wp_register_style( 'leb-toaster',  LEB_PLUGIN_URL . 'assets/css/leb-toaster.css', [], LEB_VERSION );
    wp_register_script( 'leb-toaster', LEB_PLUGIN_URL . 'assets/js/leb-toaster.js',   [], LEB_VERSION, false );

    // Confirmation Modal system.
    wp_register_style( 'leb-confirmation',  LEB_PLUGIN_URL . 'assets/css/leb-confirmation.css', [], LEB_VERSION );
    wp_register_script( 'leb-confirmation', LEB_PLUGIN_URL . 'assets/js/leb-confirmation.js',   [], LEB_VERSION, false );

    wp_enqueue_style( 'leb-toaster' );
    wp_enqueue_script( 'leb-toaster' );

    // Global Confirmation.
    wp_enqueue_style( 'leb-confirmation' );
    wp_enqueue_script( 'leb-confirmation' );
}

/**
 * Enqueue the admin-specific stylesheet(s).
 *
 * @param string $hook_suffix Current admin page hook.
 */
function leb_enqueue_admin_styles( string $hook_suffix ) {
    $template_handle = '';
    $template_file   = '';

    // Robust matching for Types / Add-Edit pages.
    if ( false !== strpos( $hook_suffix, 'leb-types' ) ) {
        $leb_action = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : '';
        if ( in_array( $leb_action, [ 'add', 'edit' ], true ) ) {
            $template_handle = 'leb-add-edit-type';
            $template_file   = 'type-model/add-edit-type.css';
        } else {
            $template_handle = 'leb-type-management';
            $template_file   = 'type-model/type-management.css';
        }
    } 
    // Robust matching for Database page.
    elseif ( false !== strpos( $hook_suffix, 'leb-database' ) ) {
        $template_handle = 'leb-database-page';
        $template_file   = 'database-page.css';
    }
    // Robust matching for Amenities list / Add-Edit pages.
    elseif ( false !== strpos( $hook_suffix, 'leb-amenities' ) ) {
        $leb_action = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : '';
        if ( in_array( $leb_action, [ 'add', 'edit' ], true ) ) {
            $template_handle = 'leb-add-edit-amenity';
            $template_file   = 'amenity-model/add-edit-amenity.css';
            // WP Media Library is required for the SVG picker.
            wp_enqueue_media();
        } else {
            $template_handle = 'leb-amenity-management';
            $template_file   = 'amenity-model/amenity-management.css';
        }
    }

    if ( $template_handle && $template_file ) {
        $full_path = LEB_PLUGIN_DIR . 'assets/css/' . $template_file;
        wp_enqueue_style(
            $template_handle,
            LEB_ASSETS_CSS_URL . $template_file,
            [ 'leb-global' ],
            file_exists( $full_path ) ? (string) filemtime( $full_path ) : LEB_VERSION
        );
    }
}

/**
 * Localize AJAX configuration (nonce + admin URL) onto the toaster script handle.
 *
 * Called separately (and early) so the LEB_Ajax global is injected in the
 * document head, before any inline template scripts attempt to read it.
 */
function leb_localize_ajax_data() {
    wp_localize_script(
        'leb-toaster',
        'LEB_Ajax',
        [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'leb_nonce' ),
        ]
    );
}

// ─────────────────────────────────────────────────────────────
// Helper
// ─────────────────────────────────────────────────────────────

/**
 * Determines whether the current admin page belongs to the LEB plugin.
 *
 * Uses strpos pattern matching instead of exact string comparison because
 * WordPress can generate different hook suffix formats depending on version
 * and menu registration order (e.g. hyphens vs underscores, parent slug
 * prefix variations). Matching against the known LEB slug substrings is
 * the most robust approach.
 *
 * @param string $hook_suffix WordPress hook suffix.
 * @return bool TRUE if on a LEB page.
 */
function leb_is_leb_page( string $hook_suffix ): bool {

    // Match any hook that contains one of the LEB menu slugs.
    $leb_slug_patterns = [
        'leb-types',      // Covers 'toplevel_page_leb-types' and the add/edit ?leb_action= variant.
        'leb-database',   // Covers any hook suffix referencing the Database submenu.
        'leb-amenities',  // Covers the new Amenities submenu and its add/edit form.
    ];

    foreach ( $leb_slug_patterns as $pattern ) {
        if ( false !== strpos( $hook_suffix, $pattern ) ) {
            return true;
        }
    }

    return false;
}
