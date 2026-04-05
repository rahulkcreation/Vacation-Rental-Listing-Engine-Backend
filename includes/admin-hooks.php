<?php
/**
 * admin-hooks.php
 *
 * All WordPress administrative hooks, menu registrations,
 * page render callbacks, and AJAX handlers for the LEB plugin.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Admin Menu Registration
// ─────────────────────────────────────────────────────────────
add_action( 'admin_menu', 'leb_register_admin_menus' );

/**
 * Register the LEB main menu and all sub-menus.
 */
function leb_register_admin_menus() {

    // Main menu icon – custom SVG encoded as a data URI.
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M2 22v-4a2 2 0 0 1 2-2h2"/><path d="M18 16h2a2 2 0 0 1 2 2v4"/><line x1="10" y1="6" x2="10" y2="6.01"/><line x1="14" y1="6" x2="14" y2="6.01"/><line x1="10" y1="10" x2="10" y2="10.01"/><line x1="14" y1="10" x2="14" y2="10.01"/><line x1="10" y1="14" x2="10" y2="14.01"/><line x1="14" y1="14" x2="14" y2="14.01"/><line x1="2" y1="22" x2="22" y2="22"/></svg>'
    );

    // Main top-level menu (no UI of its own – redirects to first sub-menu).
    add_menu_page(
        __( 'Listing Engine Backend', 'listing-engine-backend' ),
        __( 'LEB', 'listing-engine-backend' ),
        'manage_options',
        'leb-types',                      // Points to Types as the landing page.
        'leb_render_type_management_page',
        $icon_svg,
        26                                // Position just after Comments (25).
    );

    // Sub-menu 1: Types (duplicated to rename the auto-created first item).
    add_submenu_page(
        'leb-types',
        __( 'Manage Types', 'listing-engine-backend' ),
        __( 'Types', 'listing-engine-backend' ),
        'manage_options',
        'leb-types',
        'leb_render_type_management_page'
    );

    // Sub-menu 2: Database.
    add_submenu_page(
        'leb-types',
        __( 'Database Management', 'listing-engine-backend' ),
        __( 'Database', 'listing-engine-backend' ),
        'manage_options',
        'leb-database',
        'leb_render_database_page'
    );
}

// ─────────────────────────────────────────────────────────────
// Page Render Callbacks
// ─────────────────────────────────────────────────────────────

/**
 * Render the Type Management screen.
 * Handles both the list view and the add/edit form via `?leb_action=edit&id=X`.
 */
function leb_render_type_management_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'listing-engine-backend' ) );
    }

    // Determine which sub-template to show.
    $leb_action = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : 'list';

    if ( $leb_action === 'edit' || $leb_action === 'add' ) {
        require_once LEB_TEMPLATES_PATH . 'add-edit-type.php';
    } else {
        require_once LEB_TEMPLATES_PATH . 'type-management.php';
    }
}

/**
 * Render the Database Management screen.
 */
function leb_render_database_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'listing-engine-backend' ) );
    }
    require_once LEB_TEMPLATES_PATH . 'database-page.php';
}

// ─────────────────────────────────────────────────────────────
// Plugins Page Settings Link
// ─────────────────────────────────────────────────────────────
add_filter( 'plugin_action_links_' . plugin_basename( LEB_PLUGIN_FILE ), 'leb_add_settings_link' );

/**
 * Append a "Settings" link on the Plugins page row.
 *
 * @param array $links Existing action links.
 * @return array Modified links array.
 */
function leb_add_settings_link( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=leb-types' ) ) . '">'
        . esc_html__( 'Settings', 'listing-engine-backend' )
        . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// ─────────────────────────────────────────────────────────────
// AJAX Handlers
// ─────────────────────────────────────────────────────────────

// -- Types: Get list (search + pagination) --
add_action( 'wp_ajax_leb_get_types',       'leb_ajax_get_types' );

// -- Types: Create --
add_action( 'wp_ajax_leb_create_type',     'leb_ajax_create_type' );

// -- Types: Update --
add_action( 'wp_ajax_leb_update_type',     'leb_ajax_update_type' );

// -- Types: Get single (for edit pre-fill) --
add_action( 'wp_ajax_leb_get_type',        'leb_ajax_get_type' );

// -- Types: Delete --
add_action( 'wp_ajax_leb_delete_type',     'leb_ajax_delete_type' );

// -- Database: Refresh table status --
add_action( 'wp_ajax_leb_db_status',       'leb_ajax_db_status' );

// -- Database: Create / Repair table --
add_action( 'wp_ajax_leb_db_create_repair','leb_ajax_db_create_repair' );

/**
 * AJAX: Return paginated / searched list of types.
 */
function leb_ajax_get_types() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    $search   = isset( $_POST['search'] )   ? sanitize_text_field( wp_unslash( $_POST['search'] ) )   : '';
    $page     = isset( $_POST['page'] )     ? absint( $_POST['page'] )     : 1;
    $per_page = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 10;

    $handler = new LEB_Database_Handler();
    $result  = $handler->get_types( $search, $page, $per_page );

    wp_send_json_success( $result );
}

/**
 * AJAX: Create a new type entry.
 */
function leb_ajax_create_type() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
    $slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) )      : '';

    if ( empty( $name ) || empty( $slug ) ) {
        wp_send_json_error( [ 'message' => __( 'Name and Slug are required.', 'listing-engine-backend' ) ] );
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->create_type( $name, $slug );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }

    wp_send_json_success( [ 'message' => __( 'Type created successfully.', 'listing-engine-backend' ), 'id' => $result ] );
}

/**
 * AJAX: Update an existing type entry.
 */
function leb_ajax_update_type() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    $id   = isset( $_POST['id'] )   ? absint( $_POST['id'] )                                        : 0;
    $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) )           : '';
    $slug = isset( $_POST['slug'] ) ? sanitize_title( wp_unslash( $_POST['slug'] ) )                : '';

    if ( ! $id || empty( $name ) || empty( $slug ) ) {
        wp_send_json_error( [ 'message' => __( 'ID, Name, and Slug are required.', 'listing-engine-backend' ) ] );
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->update_type( $id, $name, $slug );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }

    wp_send_json_success( [ 'message' => __( 'Type updated successfully.', 'listing-engine-backend' ) ] );
}

/**
 * AJAX: Get a single type row (for the edit form pre-fill).
 */
function leb_ajax_get_type() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

    if ( ! $id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid ID.', 'listing-engine-backend' ) ] );
    }

    $handler = new LEB_Database_Handler();
    $type    = $handler->get_type_by_id( $id );

    if ( ! $type ) {
        wp_send_json_error( [ 'message' => __( 'Type not found.', 'listing-engine-backend' ) ] );
    }

    wp_send_json_success( [ 'type' => $type ] );
}

/**
 * AJAX: Delete an existing type entry.
 */
function leb_ajax_delete_type() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    $id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

    if ( ! $id ) {
        wp_send_json_error( [ 'message' => __( 'Invalid ID.', 'listing-engine-backend' ) ] );
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_type( $id );

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }

    wp_send_json_success( [ 'message' => __( 'Type deleted successfully.', 'listing-engine-backend' ) ] );
}

/**
 * AJAX: Return current DB status for all registered tables.
 */
function leb_ajax_db_status() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ls_types';
    $status     = leb_check_table_status( $table_name );

    wp_send_json_success( [
        'tables' => [
            [
                'key'            => 'ls_types',
                'title'          => __( 'Types Table', 'listing-engine-backend' ),
                'table_name'     => $table_name,
                'exists'         => $status['exists'],
                'rows_complete'  => $status['rows_complete'],
            ],
        ],
    ] );
}

/**
 * AJAX: Create or repair a specific table.
 */
function leb_ajax_db_create_repair() {
    check_ajax_referer( 'leb_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'listing-engine-backend' ) ] );
    }

    $table_key = isset( $_POST['table_key'] ) ? sanitize_text_field( wp_unslash( $_POST['table_key'] ) ) : '';

    if ( $table_key !== 'ls_types' ) {
        wp_send_json_error( [ 'message' => __( 'Unknown table key.', 'listing-engine-backend' ) ] );
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->create_or_repair_types_table();

    if ( is_wp_error( $result ) ) {
        wp_send_json_error( [ 'message' => $result->get_error_message() ] );
    }

    wp_send_json_success( [ 'message' => __( 'Table created / repaired successfully.', 'listing-engine-backend' ) ] );
}
