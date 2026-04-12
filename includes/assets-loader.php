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

    // Page-specific admin assets (CSS/JS).
    leb_enqueue_admin_assets( $hook_suffix );
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
    // 1. Shared Global Styles (Tokens & Utility Components)
    wp_register_style( 'leb-global-css', LEB_PLUGIN_URL . 'assets/global.css', [], LEB_VERSION );
    wp_register_style( 'leb-shared-css', LEB_PLUGIN_URL . 'assets/css/leb-shared.css', [], LEB_VERSION );
    wp_enqueue_style( 'leb-global-css' );
    wp_enqueue_style( 'leb-shared-css' );

    // 2. Global JS
    wp_register_script( 'leb-global-js', LEB_PLUGIN_URL . 'assets/global.js', [], LEB_VERSION, true );

    // Confirmation Modal system.
    wp_register_style( 'leb-confirmation',  LEB_PLUGIN_URL . 'assets/css/leb-confirmation.css', [], LEB_VERSION );
    wp_register_script( 'leb-confirmation', LEB_PLUGIN_URL . 'assets/js/leb-confirmation.js',   [], LEB_VERSION, false );

    // Toast Notification system.
    wp_register_style( 'leb-toaster',  LEB_PLUGIN_URL . 'assets/css/leb-toaster.css', [], LEB_VERSION );
    wp_register_script( 'leb-toaster', LEB_PLUGIN_URL . 'assets/js/leb-toaster.js',   [], LEB_VERSION, false );

    wp_enqueue_style( 'leb-toaster' );
    wp_enqueue_script( 'leb-toaster' );

    // Global Confirmation.
    wp_enqueue_style( 'leb-confirmation' );
    wp_enqueue_script( 'leb-confirmation' );
}

/**
 * Enqueue the admin-specific stylesheet(s) and script(s).
 *
 * @param string $hook_suffix Current admin page hook.
 */
function leb_enqueue_admin_assets( string $hook_suffix ) {
    $template_handle = '';
    $css_file        = '';
    $js_file         = '';

    // ── 1. Types Management ─────────────────────────────────────
    // Matches the top-level 'LEB' menu or the 'Types' submenu.
    // Hook patterns: 'toplevel_page_leb-types', 'leb-types_page_leb-types'.
    if ( false !== strpos( $hook_suffix, 'toplevel_page_leb-types' ) || false !== strpos( $hook_suffix, 'page_leb-types' ) ) {
        $leb_action = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : '';
        if ( in_array( $leb_action, [ 'add', 'edit' ], true ) ) {
            $css_path = 'type-model/add-edit-type.css';
            $js_path  = 'type-model/add-edit-type.js';
            wp_enqueue_style( 'leb-add-edit-type', LEB_ASSETS_CSS_URL . $css_path, [ 'leb-global', 'leb-shared-css' ], filemtime( LEB_PLUGIN_DIR . 'assets/css/' . $css_path ) );
            wp_enqueue_script( 'leb-add-edit-type', LEB_ASSETS_JS_URL . $js_path, [ 'jquery' ], filemtime( LEB_PLUGIN_DIR . 'assets/js/' . $js_path ), true );
        } else {
            $css_path = 'type-model/type-management.css';
            $js_path  = 'type-model/type-management.js';
            wp_enqueue_style( 'leb-type-management', LEB_ASSETS_CSS_URL . $css_path, [ 'leb-global', 'leb-shared-css' ], filemtime( LEB_PLUGIN_DIR . 'assets/css/' . $css_path ) );
            wp_enqueue_script( 'leb-type-management', LEB_ASSETS_JS_URL . $js_path, [ 'jquery' ], filemtime( LEB_PLUGIN_DIR . 'assets/js/' . $js_path ), true );
        }
    } 
    // ── 2. Database Management ──────────────────────────────────
    // Matches the 'Database' submenu page.
    // Hook pattern: 'leb-types_page_leb-database'.
    elseif ( false !== strpos( $hook_suffix, 'page_leb-database' ) ) {
        $css_path = 'database-page.css';
        wp_enqueue_style( 'leb-database-page', LEB_ASSETS_CSS_URL . $css_path, [ 'leb-global', 'leb-shared-css' ], filemtime( LEB_PLUGIN_DIR . 'assets/css/' . $css_path ) );
    }
    // ── 3. Amenities Management ─────────────────────────────────
    // Matches the 'Amenities' submenu and Handles both List and Add/Edit (viam query param leb_action).
    // Hook pattern: 'leb-types_page_leb-amenities'.
    elseif ( false !== strpos( $hook_suffix, 'page_leb-amenities' ) ) {
        $leb_action = isset($_GET['leb_action']) ? sanitize_text_field(wp_unslash($_GET['leb_action'])) : 'list';
        if (in_array($leb_action, ['add', 'edit'], true)) {
            $css_path = 'amenity-model/add-edit-amenity.css';
            $js_path  = 'amenity-model/add-edit-amenity.js';
            wp_enqueue_style('leb-amenity-add-edit', LEB_ASSETS_CSS_URL . $css_path, ['leb-global', 'leb-shared-css'], filemtime(LEB_PLUGIN_DIR . 'assets/css/' . $css_path));
            wp_enqueue_script('leb-amenity-add-edit', LEB_ASSETS_JS_URL . $js_path, ['jquery'], filemtime(LEB_PLUGIN_DIR . 'assets/js/' . $js_path), true);
        } else {
            $css_path = 'amenity-model/amenity-management.css';
            $js_path  = 'amenity-model/amenity-management.js';
            wp_enqueue_style('leb-amenity-management', LEB_ASSETS_CSS_URL . $css_path, ['leb-global', 'leb-shared-css'], filemtime(LEB_PLUGIN_DIR . 'assets/css/' . $css_path));
            wp_enqueue_script('leb-amenity-management', LEB_ASSETS_JS_URL . $js_path, ['jquery'], filemtime(LEB_PLUGIN_DIR . 'assets/js/' . $js_path), true);
        }
    } 
    // ── 4. Location Management ──────────────────────────────────
    // Matches the 'Locations' submenu and Handles both List and Add/Edit (viam query param leb_action).
    // Hook pattern: 'leb-types_page_leb-locations'.
    elseif ( false !== strpos( $hook_suffix, 'page_leb-locations' ) ) {
        $leb_action = isset($_GET['leb_action']) ? sanitize_text_field(wp_unslash($_GET['leb_action'])) : 'list';
        if (in_array($leb_action, ['add', 'edit'], true)) {
            $css_path = 'location-model/add-edit-location.css';
            $js_path  = 'location-model/add-edit-location.js';
            wp_enqueue_style('leb-loc-add-edit', LEB_ASSETS_CSS_URL . $css_path, ['leb-global', 'leb-shared-css'], filemtime(LEB_PLUGIN_DIR . 'assets/css/' . $css_path));
            wp_enqueue_script('leb-loc-add-edit', LEB_ASSETS_JS_URL . $js_path, ['jquery'], filemtime(LEB_PLUGIN_DIR . 'assets/js/' . $js_path), true);
        } else {
            $css_path = 'location-model/location-management.css';
            $js_path  = 'location-model/location-management.js';
            wp_enqueue_style('leb-loc-management', LEB_ASSETS_CSS_URL . $css_path, ['leb-global', 'leb-shared-css'], filemtime(LEB_PLUGIN_DIR . 'assets/css/' . $css_path));
            wp_enqueue_script('leb-loc-management', LEB_ASSETS_JS_URL . $js_path, ['jquery'], filemtime(LEB_PLUGIN_DIR . 'assets/js/' . $js_path), true);
        }
    }
    // ── 5. Property Management ──────────────────────────────────
    // Matches the 'Properties' submenu. Handles List and Add/Edit.
    // Hook pattern: 'leb-types_page_leb-properties'.
    elseif ( false !== strpos( $hook_suffix, 'page_leb-properties' ) ) {
        $leb_action = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : 'list';
        if ( in_array( $leb_action, [ 'add', 'edit' ], true ) ) {
            // WordPress Media Library scripts for image picker.
            wp_enqueue_media();
            $css_path = 'property-model/add-edit-property.css';
            $js_path  = 'property-model/add-edit-property.js';
            wp_enqueue_style( 'leb-prop-add-edit', LEB_ASSETS_CSS_URL . $css_path, [ 'leb-global', 'leb-shared-css' ], filemtime( LEB_PLUGIN_DIR . 'assets/css/' . $css_path ) );
            wp_enqueue_script( 'leb-prop-add-edit', LEB_ASSETS_JS_URL . $js_path, [ 'jquery' ], filemtime( LEB_PLUGIN_DIR . 'assets/js/' . $js_path ), true );
        } else {
            $css_path = 'property-model/property-management.css';
            $js_path  = 'property-model/property-management.js';
            wp_enqueue_style( 'leb-prop-management', LEB_ASSETS_CSS_URL . $css_path, [ 'leb-global', 'leb-shared-css' ], filemtime( LEB_PLUGIN_DIR . 'assets/css/' . $css_path ) );
            wp_enqueue_script( 'leb-prop-management', LEB_ASSETS_JS_URL . $js_path, [ 'jquery' ], filemtime( LEB_PLUGIN_DIR . 'assets/js/' . $js_path ), true );
        }
    }

    // Assets are now enqueued directly within each block above for better isolation.
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
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'leb_nonce' ),
            'manage_url' => admin_url( 'admin.php?page=leb-properties' ),
            'assets_url' => LEB_ASSETS_URL,
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
        'leb-types',       // Covers 'toplevel_page_leb-types' and the add/edit ?leb_action= variant.
        'leb-database',    // Covers any hook suffix referencing the Database submenu.
        'leb-amenities',   // Covers the new Amenities submenu and its add/edit form.
        'leb-locations',   // Covers the new Locations submenu and its add/edit form.
        'leb-properties',  // Covers the Properties submenu and its add/edit form.
    ];

    foreach ( $leb_slug_patterns as $pattern ) {
        if ( false !== strpos( $hook_suffix, $pattern ) ) {
            return true;
        }
    }

    return false;
}
