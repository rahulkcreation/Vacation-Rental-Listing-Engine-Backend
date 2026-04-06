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
if (! defined('ABSPATH')) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Admin Menu Registration
// ─────────────────────────────────────────────────────────────
add_action('admin_menu', 'leb_register_admin_menus');

// SVG UI & Media Library Display Fixes (Core Upload filters moved to main file)
add_filter('wp_prepare_attachment_for_js', 'leb_fix_svg_attachment_for_js', 2147483647, 3);
add_filter('wp_generate_attachment_metadata', 'leb_skip_svg_metadata', 2147483647, 2);
add_filter('plupload_init', 'leb_add_svg_to_plupload', 2147483647);
add_filter('wp_image_editors', 'leb_skip_svg_image_editor_check', 2147483647);
add_action('admin_head', 'leb_admin_svg_display_fix');

/**
 * Register the LEB main menu and all sub-menus.
 */
function leb_register_admin_menus()
{

    // Main menu icon – custom SVG encoded as a data URI.
    $icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/><path d="M2 22v-4a2 2 0 0 1 2-2h2"/><path d="M18 16h2a2 2 0 0 1 2 2v4"/><line x1="10" y1="6" x2="10" y2="6.01"/><line x1="14" y1="6" x2="14" y2="6.01"/><line x1="10" y1="10" x2="10" y2="10.01"/><line x1="14" y1="10" x2="14" y2="10.01"/><line x1="10" y1="14" x2="10" y2="14.01"/><line x1="14" y1="14" x2="14" y2="14.01"/><line x1="2" y1="22" x2="22" y2="22"/></svg>'
    );

    // Main top-level menu (no UI of its own – redirects to first sub-menu).
    add_menu_page(
        __('Listing Engine Backend', 'listing-engine-backend'),
        __('LEB', 'listing-engine-backend'),
        'manage_options',
        'leb-types',                      // Points to Types as the landing page.
        'leb_render_type_management_page',
        $icon_svg,
        26                                // Position just after Comments (25).
    );

    // Sub-menu 1: Types (duplicated to rename the auto-created first item).
    add_submenu_page(
        'leb-types',
        __('Manage Types', 'listing-engine-backend'),
        __('Types', 'listing-engine-backend'),
        'manage_options',
        'leb-types',
        'leb_render_type_management_page'
    );

    // Sub-menu 2: Database.
    add_submenu_page(
        'leb-types',
        __('Database Management', 'listing-engine-backend'),
        __('Database', 'listing-engine-backend'),
        'manage_options',
        'leb-database',
        'leb_render_database_page'
    );

    // Sub-menu 3: Amenities.
    add_submenu_page(
        'leb-types',
        __('Manage Amenities', 'listing-engine-backend'),
        __('Amenities', 'listing-engine-backend'),
        'manage_options',
        'leb-amenities',
        'leb_render_amenity_management_page'
    );

    // Sub-menu 4: Locations.
    add_submenu_page(
        'leb-types',
        __('Manage Locations', 'listing-engine-backend'),
        __('Locations', 'listing-engine-backend'),
        'manage_options',
        'leb-locations',
        'leb_render_location_management_page'
    );

    // Sub-menu 5: Properties.
    add_submenu_page(
        'leb-types',
        __('Manage Properties', 'listing-engine-backend'),
        __('Properties', 'listing-engine-backend'),
        'manage_options',
        'leb-properties',
        'leb_render_property_management_page'
    );
}

// ─────────────────────────────────────────────────────────────
// Page Render Callbacks
// ─────────────────────────────────────────────────────────────

/**
 * Render the Type Management screen.
 * Handles both the list view and the add/edit form via `?leb_action=edit&id=X`.
 */
function leb_render_type_management_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'listing-engine-backend'));
    }

    // Determine which sub-template to show.
    $leb_action = isset($_GET['leb_action']) ? sanitize_text_field(wp_unslash($_GET['leb_action'])) : 'list';

    if (in_array($leb_action, ['add', 'edit'], true)) {
        require_once LEB_TEMPLATES_PATH . 'type-model/add-edit-type.php';
    } else {
        require_once LEB_TEMPLATES_PATH . 'type-model/type-management.php';
    }
}

/**
 * Render the Database Management screen.
 */
function leb_render_database_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'listing-engine-backend'));
    }
    require_once LEB_TEMPLATES_PATH . 'database-page.php';
}

/**
 * Render the Amenity Management screen.
 * Handles both the list view and the add/edit form via `?leb_action=edit&id=X`.
 */
function leb_render_amenity_management_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'listing-engine-backend'));
    }

    $leb_action = isset($_GET['leb_action']) ? sanitize_text_field(wp_unslash($_GET['leb_action'])) : 'list';

    if (in_array($leb_action, ['add', 'edit'], true)) {
        require_once LEB_TEMPLATES_PATH . 'amenity-model/add-edit-amenity.php';
    } else {
        require_once LEB_TEMPLATES_PATH . 'amenity-model/amenity-management.php';
    }
}

/**
 * Render the Location Management screen.
 */
function leb_render_location_management_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'listing-engine-backend'));
    }

    $leb_action = isset($_GET['leb_action']) ? sanitize_text_field(wp_unslash($_GET['leb_action'])) : 'list';

    if (in_array($leb_action, ['add', 'edit'], true)) {
        require_once LEB_TEMPLATES_PATH . 'location-model/add-edit-location.php';
    } else {
        require_once LEB_TEMPLATES_PATH . 'location-model/location-management.php';
    }
}

/**
 * Render the Property Management screen.
 * Dispatches to list or add/edit template based on the ?leb_action query parameter.
 */
function leb_render_property_management_page()
{
    if (! current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'listing-engine-backend'));
    }

    $leb_action = isset($_GET['leb_action']) ? sanitize_text_field(wp_unslash($_GET['leb_action'])) : 'list';

    if (in_array($leb_action, ['add', 'edit'], true)) {
        require_once LEB_TEMPLATES_PATH . 'property-model/add-edit-property.php';
    } else {
        require_once LEB_TEMPLATES_PATH . 'property-model/property-management.php';
    }
}

// ─────────────────────────────────────────────────────────────
// Plugins Page Settings Link
// ─────────────────────────────────────────────────────────────
add_filter('plugin_action_links_' . plugin_basename(LEB_PLUGIN_FILE), 'leb_add_settings_link');

/**
 * Append a "Settings" link on the Plugins page row.
 *
 * @param array $links Existing action links.
 * @return array Modified links array.
 */
function leb_add_settings_link($links)
{
    $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=leb-types')) . '">'
        . esc_html__('Settings', 'listing-engine-backend')
        . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

// ─────────────────────────────────────────────────────────────
// AJAX Handlers
// ─────────────────────────────────────────────────────────────

// -- Types: Get list (search + pagination) --
add_action('wp_ajax_leb_get_types',       'leb_ajax_get_types');

// -- Types: Create --
add_action('wp_ajax_leb_create_type',     'leb_ajax_create_type');

// -- Types: Update --
add_action('wp_ajax_leb_update_type',     'leb_ajax_update_type');

// -- Types: Get single (for edit pre-fill) --
add_action('wp_ajax_leb_get_type',        'leb_ajax_get_type');

// -- Types: Delete --
add_action('wp_ajax_leb_delete_type',      'leb_ajax_delete_type');
add_action('wp_ajax_leb_bulk_delete_types', 'leb_ajax_bulk_delete_types');

// -- Database: Refresh table status --
add_action('wp_ajax_leb_db_status',       'leb_ajax_db_status');

// -- Database: Create / Repair table --
add_action('wp_ajax_leb_db_create_repair', 'leb_ajax_db_create_repair');

// ── Amenity AJAX Hooks ────────────────────────────────────────
// -- Amenities: Get list (search + pagination) --
add_action('wp_ajax_leb_amen_get_amenities',       'leb_ajax_amen_get_amenities');

// -- Amenities: Create --
add_action('wp_ajax_leb_amen_create_amenity',      'leb_ajax_amen_create_amenity');

// -- Amenities: Update --
add_action('wp_ajax_leb_amen_update_amenity',      'leb_ajax_amen_update_amenity');

// -- Amenities: Get single (for edit pre-fill) --
add_action('wp_ajax_leb_amen_get_amenity',         'leb_ajax_amen_get_amenity');

// -- Amenities: Delete single --
add_action('wp_ajax_leb_amen_delete_amenity',       'leb_ajax_amen_delete_amenity');

// -- Amenities: Bulk delete --
add_action('wp_ajax_leb_amen_bulk_delete_amenities', 'leb_ajax_amen_bulk_delete_amenities');

// ── Location AJAX Hooks ────────────────────────────────────────
add_action('wp_ajax_leb_loc_get_locations',        'leb_ajax_loc_get_locations');
add_action('wp_ajax_leb_loc_create_location',       'leb_ajax_loc_create_location');
add_action('wp_ajax_leb_loc_update_location',       'leb_ajax_loc_update_location');
add_action('wp_ajax_leb_loc_get_location',          'leb_ajax_loc_get_location');
add_action('wp_ajax_leb_loc_delete_location',       'leb_ajax_loc_delete_location');
add_action('wp_ajax_leb_loc_bulk_delete_locations', 'leb_ajax_loc_bulk_delete_locations');

// ── Property Listing AJAX Hooks ─────────────────────────────────
add_action('wp_ajax_leb_listing_get_listings',       'leb_ajax_listing_get_listings');
add_action('wp_ajax_leb_listing_get_listing',        'leb_ajax_listing_get_listing');
add_action('wp_ajax_leb_listing_create_listing',     'leb_ajax_listing_create_listing');
add_action('wp_ajax_leb_listing_update_listing',     'leb_ajax_listing_update_listing');
add_action('wp_ajax_leb_listing_delete_listing',     'leb_ajax_listing_delete_listing');
add_action('wp_ajax_leb_listing_bulk_delete',        'leb_ajax_listing_bulk_delete');
add_action('wp_ajax_leb_listing_bulk_status',        'leb_ajax_listing_bulk_status');
add_action('wp_ajax_leb_listing_get_amenities_all',  'leb_ajax_listing_get_amenities_all');
add_action('wp_ajax_leb_listing_get_locations_all',  'leb_ajax_listing_get_locations_all');
add_action('wp_ajax_leb_listing_get_types_all',      'leb_ajax_listing_get_types_all');

/**
 * AJAX: Return paginated / searched list of types.
 */
function leb_ajax_get_types()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $search   = isset($_POST['search'])   ? sanitize_text_field(wp_unslash($_POST['search']))   : '';
    $page     = isset($_POST['page'])     ? absint($_POST['page'])     : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;

    $handler = new LEB_Database_Handler();
    $result  = $handler->get_types($search, $page, $per_page);

    wp_send_json_success($result);
}

/**
 * AJAX: Create a new type entry.
 */
function leb_ajax_create_type()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug']))      : '';

    if (empty($name) || empty($slug)) {
        wp_send_json_error(['message' => __('Name and Slug are required.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->create_type($name, $slug);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Type created successfully.', 'listing-engine-backend'), 'id' => $result]);
}

/**
 * AJAX: Update an existing type entry.
 */
function leb_ajax_update_type()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id   = isset($_POST['id'])   ? absint($_POST['id'])                                        : 0;
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name']))           : '';
    $slug = isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug']))                : '';

    if (! $id || empty($name) || empty($slug)) {
        wp_send_json_error(['message' => __('ID, Name, and Slug are required.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->update_type($id, $name, $slug);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Type updated successfully.', 'listing-engine-backend')]);
}

/**
 * AJAX: Get a single type row (for the edit form pre-fill).
 */
function leb_ajax_get_type()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $type    = $handler->get_type_by_id($id);

    if (! $type) {
        wp_send_json_error(['message' => __('Type not found.', 'listing-engine-backend')]);
    }

    wp_send_json_success(['type' => $type]);
}

/**
 * AJAX: Delete an existing type entry.
 */
function leb_ajax_delete_type()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_type($id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Type deleted successfully.', 'listing-engine-backend')]);
}

/**
 * AJAX: Delete multiple type entries (Bulk Action).
 */
function leb_ajax_bulk_delete_types()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
    $ids = array_filter(array_map('absint', $ids));

    if (empty($ids)) {
        wp_send_json_error(['message' => __('No valid IDs provided.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_types($ids);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => sprintf(__('%d types deleted successfully.', 'listing-engine-backend'), count($ids))]);
}

/**
 * AJAX: Return current DB status for all registered tables.
 *
 * Returns both ls_types and ls_amenities so the JS refresh handler can
 * update both cards with a single network request.
 */
function leb_ajax_db_status()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    global $wpdb;

    // Types table status.
    $types_table  = $wpdb->prefix . 'ls_types';
    $types_status = leb_check_table_status($types_table);

    // Amenities table status.
    $amen_table  = $wpdb->prefix . 'ls_amenities';
    $amen_status = leb_check_table_status($amen_table);

    wp_send_json_success([
        'tables' => [
            [
                'key'           => 'ls_types',
                'title'         => __('Types Table', 'listing-engine-backend'),
                'table_name'    => $types_table,
                'exists'        => $types_status['exists'],
                'rows_complete' => $types_status['rows_complete'],
            ],
            [
                'key'           => 'ls_amenities',
                'title'         => __('Amenities Table', 'listing-engine-backend'),
                'table_name'    => $amen_table,
                'exists'        => $amen_status['exists'],
                'rows_complete' => $amen_status['rows_complete'],
            ],
            [
                'key'           => 'ls_location',
                'title'         => __('Locations Table', 'listing-engine-backend'),
                'table_name'    => $wpdb->prefix . 'ls_location',
                'exists'        => leb_check_table_status($wpdb->prefix . 'ls_location')['exists'],
                'rows_complete' => leb_check_table_status($wpdb->prefix . 'ls_location')['rows_complete'],
            ],
            // Listings Table Status.
            [
                'key'           => 'ls_listings',
                'title'         => __('Listings Table', 'listing-engine-backend'),
                'table_name'    => $wpdb->prefix . 'ls_listings',
                'exists'        => leb_check_table_status($wpdb->prefix . 'ls_listings')['exists'],
                'rows_complete' => leb_check_table_status($wpdb->prefix . 'ls_listings')['rows_complete'],
            ],
            // Images Table Status.
            [
                'key'           => 'ls_img',
                'title'         => __('Images Table', 'listing-engine-backend'),
                'table_name'    => $wpdb->prefix . 'ls_img',
                'exists'        => leb_check_table_status($wpdb->prefix . 'ls_img')['exists'],
                'rows_complete' => leb_check_table_status($wpdb->prefix . 'ls_img')['rows_complete'],
            ],
            // Block Dates Table Status.
            [
                'key'           => 'ls_block_date',
                'title'         => __('Block Dates Table', 'listing-engine-backend'),
                'table_name'    => $wpdb->prefix . 'ls_block_date',
                'exists'        => leb_check_table_status($wpdb->prefix . 'ls_block_date')['exists'],
                'rows_complete' => leb_check_table_status($wpdb->prefix . 'ls_block_date')['rows_complete'],
            ],
        ],
    ]);
}

/**
 * AJAX: Create or repair a specific table.
 *
 * Now supports both 'ls_types' and 'ls_amenities' table keys.
 */
function leb_ajax_db_create_repair()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $table_key = isset($_POST['table_key']) ? sanitize_text_field(wp_unslash($_POST['table_key'])) : '';
    $handler   = new LEB_Database_Handler();

    if ($table_key === 'ls_types') {
        $result = $handler->create_or_repair_types_table();
    } elseif ($table_key === 'ls_amenities') {
        $result = $handler->create_or_repair_amenities_table();
    } elseif ($table_key === 'ls_location') {
        $result = $handler->create_or_repair_locations_table();
    } elseif ($table_key === 'ls_listings') {
        // Repair Listings table.
        $result = $handler->create_or_repair_listings_table();
    } elseif ($table_key === 'ls_img') {
        // Repair Images table.
        $result = $handler->create_or_repair_ls_img_table();
    } elseif ($table_key === 'ls_block_date') {
        // Repair Block Dates table.
        $result = $handler->create_or_repair_ls_block_date_table();
    } else {
        wp_send_json_error(['message' => __('Unknown table key.', 'listing-engine-backend')]);
    }

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Table created / repaired successfully.', 'listing-engine-backend')]);
}

// ─────────────────────────────────────────────────────────────
// Amenity AJAX Handlers
// ─────────────────────────────────────────────────────────────

/**
 * AJAX: Return paginated / searched list of amenities.
 */
function leb_ajax_amen_get_amenities()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $search   = isset($_POST['search'])   ? sanitize_text_field(wp_unslash($_POST['search']))   : '';
    $page     = isset($_POST['page'])     ? absint($_POST['page'])     : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;

    $handler = new LEB_Database_Handler();
    $result  = $handler->get_amenities($search, $page, $per_page);

    if (! empty($result['items'])) {
        foreach ($result['items'] as &$item) {
            if (! empty($item['svg_path'])) {
                $decoded = json_decode($item['svg_path'], true);
                if (is_array($decoded) && isset($decoded['path'])) {
                    $item['svg_path']      = $decoded['path'];
                    $item['attachment_id'] = $decoded['attachment_id'] ?? 0;
                }
            }
        }
    }

    wp_send_json_success($result);
}

/**
 * AJAX: Create a new amenity entry.
 *
 * SVG from WP Media Library:
 *  – Only SVG mime-type is accepted.
 *  – Max size: 1 MB.
 *  – Dimensions must be 24×24 px (verified server-side via attachment post meta).
 */
function leb_ajax_amen_create_amenity()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $name          = isset($_POST['name'])          ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $svg_path      = isset($_POST['svg_path'])      ? esc_url_raw(wp_unslash($_POST['svg_path']))     : '';
    $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id'])                   : 0;

    if (empty($name)) {
        wp_send_json_error(['message' => __('Amenity Name is required.', 'listing-engine-backend')]);
    }

    if (empty($svg_path) && empty($attachment_id)) {
        wp_send_json_error(['message' => __('Amenity SVG Icon is required.', 'listing-engine-backend')]);
    }

    // Validate SVG attachment if one was provided.
    if ($attachment_id) {
        $svg_validation = leb_amen_validate_svg_attachment($attachment_id);
        if (is_wp_error($svg_validation)) {
            wp_send_json_error(['message' => $svg_validation->get_error_message()]);
        }
    }

    $svg_data = '';
    if (! empty($svg_path) || $attachment_id) {
        $svg_data = wp_json_encode([
            'path'          => $svg_path,
            'attachment_id' => $attachment_id,
        ]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->create_amenity($name, $svg_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Amenity created successfully.', 'listing-engine-backend'), 'id' => $result]);
}

/**
 * AJAX: Update an existing amenity entry.
 */
function leb_ajax_amen_update_amenity()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id            = isset($_POST['id'])            ? absint($_POST['id'])                             : 0;
    $name          = isset($_POST['name'])          ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $svg_path      = isset($_POST['svg_path'])      ? esc_url_raw(wp_unslash($_POST['svg_path']))     : '';
    $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id'])                   : 0;

    if (! $id || empty($name)) {
        wp_send_json_error(['message' => __('ID and Name are required.', 'listing-engine-backend')]);
    }

    if (empty($svg_path) && empty($attachment_id)) {
        wp_send_json_error(['message' => __('Amenity SVG Icon is required.', 'listing-engine-backend')]);
    }

    // Validate new SVG attachment if a new one was selected.
    if ($attachment_id) {
        $svg_validation = leb_amen_validate_svg_attachment($attachment_id);
        if (is_wp_error($svg_validation)) {
            wp_send_json_error(['message' => $svg_validation->get_error_message()]);
        }
    }

    $svg_data = '';
    if (! empty($svg_path) || $attachment_id) {
        $svg_data = wp_json_encode([
            'path'          => $svg_path,
            'attachment_id' => $attachment_id,
        ]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->update_amenity($id, $name, $svg_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Amenity updated successfully.', 'listing-engine-backend')]);
}

/**
 * AJAX: Get a single amenity row (for the edit form pre-fill).
 */
function leb_ajax_amen_get_amenity()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $handler  = new LEB_Database_Handler();
    $amenity  = $handler->get_amenity_by_id($id);

    if (! $amenity) {
        wp_send_json_error(['message' => __('Amenity not found.', 'listing-engine-backend')]);
    }

    if (! empty($amenity['svg_path'])) {
        $decoded = json_decode($amenity['svg_path'], true);
        if (is_array($decoded) && isset($decoded['path'])) {
            $amenity['svg_path']      = $decoded['path'];
            $amenity['attachment_id'] = $decoded['attachment_id'] ?? 0;
        }
    }

    wp_send_json_success(['amenity' => $amenity]);
}

/**
 * AJAX: Delete an existing amenity entry.
 */
function leb_ajax_amen_delete_amenity()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;

    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_amenity($id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Amenity deleted successfully.', 'listing-engine-backend')]);
}

/**
 * AJAX: Delete multiple amenity entries (Bulk Action).
 */
function leb_ajax_amen_bulk_delete_amenities()
{
    check_ajax_referer('leb_nonce', 'nonce');

    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
    $ids = array_filter(array_map('absint', $ids));

    if (empty($ids)) {
        wp_send_json_error(['message' => __('No valid IDs provided.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_amenities($ids);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => sprintf(__('%d amenities deleted successfully.', 'listing-engine-backend'), count($ids))]);
}

// ─────────────────────────────────────────────────────────────
// SVG Attachment Validation Helper
// ─────────────────────────────────────────────────────────────

/**
 * Validate that a WP media attachment meets the SVG icon requirements:
 *   – Must exist and be an SVG (image/svg+xml).
 *   – File size must not exceed 1 MB.
 *   – Image dimensions must be exactly 24 × 24 px.
 *
 * @param int $attachment_id WordPress attachment post ID.
 * @return true|WP_Error TRUE if valid, WP_Error with a human-readable message otherwise.
 */
function leb_amen_validate_svg_attachment(int $attachment_id)
{
    // Verify the attachment post exists.
    $attachment = get_post($attachment_id);
    if (! $attachment || 'attachment' !== $attachment->post_type) {
        return new WP_Error('leb_amen_invalid_attachment', __('Invalid attachment ID.', 'listing-engine-backend'));
    }

    // Verify the MIME type is SVG.
    $mime_type = get_post_mime_type($attachment_id);
    if ('image/svg+xml' !== $mime_type) {
        return new WP_Error('leb_amen_not_svg', __('Only SVG files are allowed for amenity icons.', 'listing-engine-backend'));
    }

    // Verify file size (must be ≤ 1 MB = 1,048,576 bytes).
    $file_path = get_attached_file($attachment_id);
    if ($file_path && file_exists($file_path)) {
        $file_size = filesize($file_path);
        if ($file_size > 1048576) {
            return new WP_Error(
                'leb_amen_file_too_large',
                __('SVG file size must not exceed 1 MB.', 'listing-engine-backend')
            );
        }
    }

    // Verify dimensions (must be exactly 24 × 24 px).
    // Note: PHP getimagesize() does not read SVG dimensions, so we check
    // the attachment meta first; if unavailable, we parse the SVG file.
    $image_meta = wp_get_attachment_metadata($attachment_id);
    $width      = isset($image_meta['width'])  ? (int) $image_meta['width']  : 0;
    $height     = isset($image_meta['height']) ? (int) $image_meta['height'] : 0;

    // Fallback: read width/height attributes directly from the SVG markup.
    if ((! $width || ! $height) && $file_path && file_exists($file_path)) {
        $svg_content = file_get_contents($file_path); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ($svg_content) {
            // Match width="24" and height="24" attributes (or viewBox="0 0 24 24").
            if (preg_match('/\bwidth=["\']?(\d+)["\']?/i', $svg_content, $w_match)) {
                $width = (int) $w_match[1];
            }
            if (preg_match('/\bheight=["\']?(\d+)["\']?/i', $svg_content, $h_match)) {
                $height = (int) $h_match[1];
            }
            // Try viewBox as last resort.
            if ((! $width || ! $height) && preg_match('/\bviewBox=["\']?\d+\s+\d+\s+(\d+)\s+(\d+)["\']?/i', $svg_content, $vb)) {
                $width  = (int) $vb[1];
                $height = (int) $vb[2];
            }
        }
    }

    if ($width && $height && ($width !== 24 || $height !== 24)) {
        return new WP_Error(
            'leb_amen_wrong_dimensions',
            /* translators: 1: actual width, 2: actual height */
            sprintf(
                __('SVG icon must be exactly 24×24 px. Uploaded file is %1$d×%2$d px.', 'listing-engine-backend'),
                $width,
                $height
            )
        );
    }

    return true;
}

// ─────────────────────────────────────────────────────────────
// Location AJAX Handlers
// ─────────────────────────────────────────────────────────────

/**
 * AJAX: Return paginated / searched list of locations.
 */
function leb_ajax_loc_get_locations()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $search   = isset($_POST['search'])   ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $page     = isset($_POST['page'])     ? absint($_POST['page']) : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;

    $handler = new LEB_Database_Handler();
    $result  = $handler->get_locations($search, $page, $per_page);

    if (! empty($result['items'])) {
        foreach ($result['items'] as &$item) {
            if (! empty($item['svg_path'])) {
                $decoded = json_decode($item['svg_path'], true);
                if (is_array($decoded) && isset($decoded['path'])) {
                    $item['svg_path']      = $decoded['path'];
                    $item['attachment_id'] = $decoded['attachment_id'] ?? 0;
                }
            }
        }
    }
    wp_send_json_success($result);
}

/**
 * AJAX: Create a new location entry.
 */
function leb_ajax_loc_create_location()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $name          = isset($_POST['name'])          ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $slug          = isset($_POST['slug'])          ? sanitize_title(wp_unslash($_POST['slug']))      : '';
    $svg_path      = isset($_POST['svg_path'])      ? esc_url_raw(wp_unslash($_POST['svg_path']))     : '';
    $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id'])                   : 0;

    if (empty($name) || empty($slug)) wp_send_json_error(['message' => 'Name and Slug are required.']);
    if (empty($svg_path) && empty($attachment_id)) wp_send_json_error(['message' => 'SVG Icon is required.']);

    // Validate SVG attachment if one was provided.
    if ($attachment_id) {
        $svg_validation = leb_amen_validate_svg_attachment($attachment_id);
        if (is_wp_error($svg_validation)) {
            wp_send_json_error(['message' => $svg_validation->get_error_message()]);
        }
    }

    $svg_data = wp_json_encode(['path' => $svg_path, 'attachment_id' => $attachment_id]);

    $handler = new LEB_Database_Handler();
    $result  = $handler->create_location($name, $slug, $svg_data);

    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);
    wp_send_json_success(['message' => 'Location created successfully.', 'id' => $result]);
}

/**
 * AJAX: Update an existing location entry.
 */
function leb_ajax_loc_update_location()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $id            = isset($_POST['id'])            ? absint($_POST['id'])                             : 0;
    $name          = isset($_POST['name'])          ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $slug          = isset($_POST['slug'])          ? sanitize_title(wp_unslash($_POST['slug']))      : '';
    $svg_path      = isset($_POST['svg_path'])      ? esc_url_raw(wp_unslash($_POST['svg_path']))     : '';
    $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id'])                   : 0;

    if (! $id || empty($name) || empty($slug)) wp_send_json_error(['message' => 'ID, Name, and Slug are required.']);
    if (empty($svg_path) && empty($attachment_id)) wp_send_json_error(['message' => 'SVG Icon is required.']);

    // Validate new SVG attachment if a new one was selected.
    if ($attachment_id) {
        $svg_validation = leb_amen_validate_svg_attachment($attachment_id);
        if (is_wp_error($svg_validation)) {
            wp_send_json_error(['message' => $svg_validation->get_error_message()]);
        }
    }

    $svg_data = wp_json_encode(['path' => $svg_path, 'attachment_id' => $attachment_id]);

    $handler = new LEB_Database_Handler();
    $result  = $handler->update_location($id, $name, $slug, $svg_data);

    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);
    wp_send_json_success(['message' => 'Location updated successfully.']);
}

/**
 * AJAX: Get a single location row.
 */
function leb_ajax_loc_get_location()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (! $id) wp_send_json_error(['message' => 'Invalid ID']);

    $handler  = new LEB_Database_Handler();
    $location = $handler->get_location_by_id($id);

    if (! $location) wp_send_json_error(['message' => 'Location not found']);

    if (! empty($location['svg_path'])) {
        $decoded = json_decode($location['svg_path'], true);
        if (is_array($decoded)) {
            $location['svg_path']      = $decoded['path'] ?? '';
            $location['attachment_id'] = $decoded['attachment_id'] ?? 0;
        }
    }
    wp_send_json_success(['location' => $location]);
}

/**
 * AJAX: Delete single location.
 */
function leb_ajax_loc_delete_location()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (! $id) wp_send_json_error(['message' => 'Invalid ID']);
    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_location($id);

    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);
    wp_send_json_success(['message' => 'Location deleted successfully.']);
}

/**
 * AJAX: Bulk delete locations.
 */
function leb_ajax_loc_bulk_delete_locations()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) wp_send_json_error(['message' => 'Unauthorized']);

    $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
    $ids = array_filter(array_map('absint', $ids));

    if (empty($ids)) wp_send_json_error(['message' => 'No valid IDs provided.']);

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_locations($ids);

    if (is_wp_error($result)) wp_send_json_error(['message' => $result->get_error_message()]);
    wp_send_json_success(['message' => sprintf('%d locations deleted successfully.', count($ids))]);
}

// ─────────────────────────────────────────────────────────────
// Property Listing AJAX Handlers
// ─────────────────────────────────────────────────────────────

/**
 * AJAX: Return paginated, searched, and status-filtered list of property listings.
 * Includes status tab counts for the filter bar.
 */
function leb_ajax_listing_get_listings()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $search   = isset($_POST['search'])   ? sanitize_text_field(wp_unslash($_POST['search'])) : '';
    $page     = isset($_POST['page'])     ? absint($_POST['page'])     : 1;
    $per_page = isset($_POST['per_page']) ? absint($_POST['per_page']) : 10;
    $status   = isset($_POST['status'])   ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

    $handler = new LEB_Database_Handler();
    $result  = $handler->get_listings($search, $page, $per_page, $status);
    $counts  = $handler->get_status_counts();

    $result['status_counts'] = $counts;

    wp_send_json_success($result);
}

/**
 * AJAX: Get a single listing with all related data for the edit form.
 */
function leb_ajax_listing_get_listing()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $listing = $handler->get_listing_by_id($id);

    if (! $listing) {
        wp_send_json_error(['message' => __('Listing not found.', 'listing-engine-backend')]);
    }

    wp_send_json_success(['listing' => $listing]);
}

/**
 * AJAX: Create a new property listing.
 */
function leb_ajax_listing_create_listing()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    if (empty($title)) {
        wp_send_json_error(['message' => __('Property title is required.', 'listing-engine-backend')]);
    }

    $data = [
        'user_id'     => get_current_user_id(),
        'title'       => $title,
        'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
        'guests'      => isset($_POST['guests'])      ? absint($_POST['guests'])      : 0,
        'bedroom'     => isset($_POST['bedroom'])     ? absint($_POST['bedroom'])     : 0,
        'bed'         => isset($_POST['bed'])         ? absint($_POST['bed'])         : 0,
        'bathroom'    => isset($_POST['bathroom'])    ? absint($_POST['bathroom'])    : 0,
        'price'       => isset($_POST['price'])       ? absint($_POST['price'])       : 0,
        'type'        => isset($_POST['type'])        ? sanitize_text_field(wp_unslash($_POST['type']))     : '',
        'location'    => isset($_POST['location'])    ? sanitize_text_field(wp_unslash($_POST['location'])) : '',
        'amenities'   => isset($_POST['amenities'])   ? sanitize_text_field(wp_unslash($_POST['amenities'])) : '',
        'status'      => isset($_POST['status'])      ? sanitize_text_field(wp_unslash($_POST['status']))  : 'draft',
        'images'      => isset($_POST['images'])      ? wp_unslash($_POST['images'])  : '[]',
        'dates'       => isset($_POST['dates'])       ? wp_unslash($_POST['dates'])   : '[]',
    ];

    // Validate images
    $image_check = leb_validate_listing_images($data['images']);
    if (is_wp_error($image_check)) {
        wp_send_json_error(['message' => $image_check->get_error_message()]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->create_listing($data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success([
        'message' => __('Property created successfully!', 'listing-engine-backend'),
        'id'      => $result,
    ]);
}

/**
 * AJAX: Update an existing property listing.
 */
function leb_ajax_listing_update_listing()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $data = [
        'title'       => isset($_POST['title'])       ? sanitize_text_field(wp_unslash($_POST['title']))     : '',
        'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description']))      : '',
        'guests'      => isset($_POST['guests'])      ? absint($_POST['guests'])      : 0,
        'bedroom'     => isset($_POST['bedroom'])     ? absint($_POST['bedroom'])     : 0,
        'bed'         => isset($_POST['bed'])         ? absint($_POST['bed'])         : 0,
        'bathroom'    => isset($_POST['bathroom'])    ? absint($_POST['bathroom'])    : 0,
        'price'       => isset($_POST['price'])       ? absint($_POST['price'])       : 0,
        'type'        => isset($_POST['type'])        ? sanitize_text_field(wp_unslash($_POST['type']))     : '',
        'location'    => isset($_POST['location'])    ? sanitize_text_field(wp_unslash($_POST['location'])) : '',
        'amenities'   => isset($_POST['amenities'])   ? sanitize_text_field(wp_unslash($_POST['amenities'])) : '',
        'status'      => isset($_POST['status'])      ? sanitize_text_field(wp_unslash($_POST['status']))  : 'draft',
        'images'      => isset($_POST['images'])      ? wp_unslash($_POST['images'])  : '[]',
        'dates'       => isset($_POST['dates'])       ? wp_unslash($_POST['dates'])   : '[]',
    ];

    // Validate images
    $image_check = leb_validate_listing_images($data['images']);
    if (is_wp_error($image_check)) {
        wp_send_json_error(['message' => $image_check->get_error_message()]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->update_listing($id, $data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Property updated successfully!', 'listing-engine-backend')]);
}

/**
 * AJAX: Delete a single property listing.
 */
function leb_ajax_listing_delete_listing()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
    if (! $id) {
        wp_send_json_error(['message' => __('Invalid ID.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_listing($id);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => __('Listing deleted successfully.', 'listing-engine-backend')]);
}

/**
 * AJAX: Bulk delete multiple property listings.
 */
function leb_ajax_listing_bulk_delete()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $ids = isset($_POST['ids']) ? (array) $_POST['ids'] : [];
    $ids = array_filter(array_map('absint', $ids));

    if (empty($ids)) {
        wp_send_json_error(['message' => __('No valid IDs provided.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->delete_listings($ids);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => sprintf(__('%d listings deleted successfully.', 'listing-engine-backend'), count($ids))]);
}

/**
 * AJAX: Bulk update status for multiple listings.
 */
function leb_ajax_listing_bulk_status()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $ids    = isset($_POST['ids'])    ? (array) $_POST['ids']    : [];
    $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
    $ids    = array_filter(array_map('absint', $ids));

    if (empty($ids) || empty($status)) {
        wp_send_json_error(['message' => __('IDs and status are required.', 'listing-engine-backend')]);
    }

    $valid_statuses = ['draft', 'pending', 'published', 'rejected'];
    if (! in_array($status, $valid_statuses, true)) {
        wp_send_json_error(['message' => __('Invalid status value.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $result  = $handler->update_listings_status($ids, $status);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => sprintf(__('Status updated for %d listings.', 'listing-engine-backend'), count($ids))]);
}

/**
 * AJAX: Return all amenities (unpaginated) for the property form checkbox picker.
 */
function leb_ajax_listing_get_amenities_all()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $items   = $handler->get_all_amenities();

    wp_send_json_success(['items' => $items]);
}

/**
 * AJAX: Return all locations (unpaginated) for the property form dropdown.
 */
function leb_ajax_listing_get_locations_all()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $items   = $handler->get_all_locations();

    wp_send_json_success(['items' => $items]);
}

/**
 * AJAX: Return all types (unpaginated) for the property form dropdown.
 */
function leb_ajax_listing_get_types_all()
{
    check_ajax_referer('leb_nonce', 'nonce');
    if (! current_user_can('manage_options')) {
        wp_send_json_error(['message' => __('Unauthorized.', 'listing-engine-backend')]);
    }

    $handler = new LEB_Database_Handler();
    $items   = $handler->get_all_types();

    wp_send_json_success(['items' => $items]);
}

/**
 * Filter: Skip metadata generation (thumbnails) for SVGs.
 * This is the crucial step that usually throws the "cannot be processed" error.
 */
function leb_skip_svg_metadata($metadata, $attachment_id)
{
    $mime = get_post_mime_type($attachment_id);
    if ('image/svg+xml' === $mime) {
        return array(); // Return empty metadata to skip processing
    }
    return $metadata;
}

/**
 * Filter: Final fallback to force SVG upload success even if WP throws a processing error.
 */
function leb_force_svg_upload_success($upload)
{
    if (isset($upload['type']) && 'application/svg+xml' === $upload['type']) {
        $upload['type'] = 'image/svg+xml';
    }

    if (isset($upload['error']) && stripos($upload['file'], '.svg') !== false) {
        $errors_to_clear = [
            'processed by the web server',
            'not an image',
            'security reasons',
            'mismatch',
        ];

        foreach ($errors_to_clear as $err_frag) {
            if (stripos($upload['error'], $err_frag) !== false) {
                unset($upload['error']);
                break;
            }
        }
    }
    return $upload;
}

/**
 * Filter: Add SVG support to Plupload settings.
 */
function leb_add_svg_to_plupload($params)
{
    $params['filters']['mime_types'][] = array(
        'title'      => 'SVG Images',
        'extensions' => 'svg,svgz',
    );
    $params['resize'] = false;
    return $params;
}

/**
 * Filter: Ensure Media Library JS handles SVG attachments correctly.
 */
function leb_fix_svg_attachment_for_js($response, $attachment, $meta)
{
    if ('image/svg+xml' === $response['mime'] && empty($response['sizes'])) {
        $svg_path = get_attached_file($attachment->ID);

        if (! file_exists($svg_path)) {
            $svg_path = $response['url'];
        }

        $response['sizes'] = [
            'full' => [
                'url'         => $response['url'],
                'width'       => 24,
                'height'      => 24,
                'orientation' => 'portrait',
            ],
        ];
    }

    return $response;
}

/**
 * Action: Fix SVG display in the WordPress Admin / Media Library.
 *
 * By default, WordPress doesn't show previews for SVGs in the Media Library
 * grid view because they have no intrinsic dimensions. This CSS ensures they
 * fill their container.
 */
function leb_admin_svg_display_fix()
{
    echo '<style type="text/css">
        .attachment-24x24, .thumbnail img[src$=".svg"] { width: 24px !important; height: 24px !important; }
        .media-icon img[src$=".svg"], .attachments .portrait img, .attachments .landscape img { width: 100% !important; height: auto !important; }
    </style>';
}

/**
 * Filter: Skip image editing for SVGs to prevent server errors.
 */
function leb_skip_svg_image_editor_check($editors)
{
    return $editors;
}

/**
 * Filter: Final safeguard against "This file cannot be processed" error.
 */
function leb_skip_svg_image_editor($editors, $path)
{
    if (strpos($path, '.svg') !== false) {
        return false;
    }
    return $editors;
}

/**
 * Helper: Validate listing images against constraints (1MB, 1200x800).
 *
 * @param array|string $images The images array (from JS).
 * @return true|WP_Error
 */
function leb_validate_listing_images($images)
{
    if (is_string($images)) {
        $images = json_decode($images, true);
    }

    if (! is_array($images) || empty($images)) {
        return new WP_Error('invalid_images', __('Please select at least one property image.', 'listing-engine-backend'));
    }

    foreach ($images as $img) {
        $id = isset($img['id']) ? absint($img['id']) : 0;
        if (! $id) {
            continue;
        }

        // Check file size
        $file_path = get_attached_file($id);
        if ($file_path && file_exists($file_path)) {
            $size = filesize($file_path);
            if ($size > 1048576) { // 1MB
                return new WP_Error(
                    'image_too_large',
                    sprintf(__('Image "%s" exceeds 1MB limit.', 'listing-engine-backend'), get_the_title($id))
                );
            }
        }

        // Check dimensions
        $meta = wp_get_attachment_metadata($id);
        if ($meta) {
            $w = isset($meta['width']) ? absint($meta['width']) : 0;
            $h = isset($meta['height']) ? absint($meta['height']) : 0;
            if ($w !== 1200 || $h !== 800) {
                return new WP_Error(
                    'invalid_dimensions',
                    sprintf(
                        __('Image "%s" must be exactly 1200x800px (Actual: %dx%d).', 'listing-engine-backend'),
                        get_the_title($id),
                        $w,
                        $h
                    )
                );
            }
        }
    }

    return true;
}
