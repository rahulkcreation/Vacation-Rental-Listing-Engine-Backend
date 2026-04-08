<?php

/**
 * class-db-handler.php
 *
 * Database operations handler for the Listing Engine Backend plugin.
 * All public methods wrap calls through $wpdb->prepare() to prevent SQL injection.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Class LEB_Database_Handler
 *
 * Handles all CRUD operations and table maintenance for the LEB plugin.
 */
class LEB_Database_Handler
{

    /**
     * The fully-qualified name of the ls_types table.
     *
     * @var string
     */
    private string $types_table;

    /**
     * The fully-qualified name of the ls_amenities table.
     *
     * @var string
     */
    private string $amenities_table;

    /**
     * The fully-qualified name of the ls_location table.
     *
     * @var string
     */
    private string $locations_table;

    /**
     * The fully-qualified name of the ls_listings table.
     *
     * @var string
     */
    private string $listings_table;

    /**
     * The fully-qualified name of the ls_img table.
     *
     * @var string
     */
    private string $ls_img_table;

    /**
     * The fully-qualified name of the ls_block_date table.
     *
     * @var string
     */
    private string $ls_block_date_table;

    /**
     * Constructor – resolves table names with the WP prefix.
     */
    public function __construct()
    {
        global $wpdb;
        $this->types_table      = $wpdb->prefix . 'ls_types';
        $this->amenities_table  = $wpdb->prefix . 'ls_amenities';
        $this->locations_table  = $wpdb->prefix . 'ls_location';
        $this->listings_table   = $wpdb->prefix . 'ls_listings';
        $this->ls_img_table     = $wpdb->prefix . 'ls_img';
        $this->ls_block_date_table = $wpdb->prefix . 'ls_block_date';
    }

    // ─────────────────────────────────────────────────────────
    // SECTION 1: GLOBAL LOOKUP TABLES MAINTENANCE
    // (Types, Amenities, Locations)
    // ─────────────────────────────────────────────────────────

    /**
     * Create or repair the ls_types table.
     *
     * Uses dbDelta() for safe upgrades. After ensuring the table exists,
     * inserts any default rows that are not yet present.
     *
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function create_or_repair_types_table()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql    = leb_get_types_schema();
        $result = dbDelta($sql);

        // dbDelta does not return errors directly; check table existence instead.
        $status = leb_check_table_status($this->types_table);

        if (! $status['exists']) {
            return new WP_Error('leb_table_create_failed', __('Table could not be created. Check database permissions.', 'listing-engine-backend'));
        }

        // Insert any missing default rows (if any defined in db-schema.php).
        $this->seed_default_rows();

        return true;
    }

    /**
     * Seeds default rows into the types table if they don't already exist.
     */
    private function seed_default_rows()
    {
        global $wpdb;

        $default_rows = leb_get_default_type_rows();

        if (empty($default_rows)) {
            return;
        }

        foreach ($default_rows as $row) {
            $slug_exists = $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM `{$this->types_table}` WHERE slug = %s",
                    $row['slug']
                )
            );

            if (! $slug_exists) {
                $wpdb->insert(
                    $this->types_table,
                    [
                        'name'       => sanitize_text_field($row['name']),
                        'slug'       => sanitize_title($row['slug']),
                        'updated_at' => current_time('mysql'),
                    ],
                    ['%s', '%s', '%s']
                );
            }
        }
    }

    // ─────────────────────────────────────────────────────────
    // Read Operations
    // ─────────────────────────────────────────────────────────

    /**
     * Retrieve a paginated, optionally searched list of types.
     *
     * @param string $search   Search term (matched against name and slug).
     * @param int    $page     1-based page number.
     * @param int    $per_page Items per page.
     * @return array {
     *     @type array $items  Rows from the database.
     *     @type int   $total  Total row count matching the query.
     * }
     */
    public function get_types(string $search = '', int $page = 1, int $per_page = 10): array
    {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        if (! empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM `{$this->types_table}` WHERE name LIKE %s OR slug LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
                    $like,
                    $like,
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );

            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM `{$this->types_table}` WHERE name LIKE %s OR slug LIKE %s",
                    $like,
                    $like
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM `{$this->types_table}` ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total = (int) $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM `{$this->types_table}`"
            );
        }

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }

    /**
     * Retrieve a single type record by its ID.
     *
     * @param int $id Row ID.
     * @return array|null Associative array of the row, or null if not found.
     */
    public function get_type_by_id(int $id): ?array
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $row = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `{$this->types_table}` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    // ─────────────────────────────────────────────────────────
    // Write Operations
    // ─────────────────────────────────────────────────────────

    /**
     * Insert a new type record.
     *
     * @param string $name Raw type name (will be sanitized).
     * @param string $slug Raw slug (will be converted to lowercase via sanitize_title).
     * @return int|WP_Error Inserted row ID on success, WP_Error on failure.
     */
    public function create_type(string $name, string $slug)
    {
        global $wpdb;

        $name = sanitize_text_field($name);
        $slug = sanitize_title($slug);     // Enforces lowercase.

        // Check for duplicate slug.
        if ($this->slug_exists($slug)) {
            return new WP_Error('leb_duplicate_slug', __('A type with this slug already exists.', 'listing-engine-backend'));
        }

        $inserted = $wpdb->insert(
            $this->types_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );

        if (false === $inserted) {
            return new WP_Error('leb_insert_failed', __('Failed to create the type. Please try again.', 'listing-engine-backend'));
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing type record.
     *
     * @param int    $id   Row ID to update.
     * @param string $name New type name.
     * @param string $slug New slug (enforced lowercase).
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function update_type(int $id, string $name, string $slug)
    {
        global $wpdb;

        $name = sanitize_text_field($name);
        $slug = sanitize_title($slug);

        // Check for duplicate slug excluding the current row.
        if ($this->slug_exists($slug, $id)) {
            return new WP_Error('leb_duplicate_slug', __('A type with this slug already exists.', 'listing-engine-backend'));
        }

        $updated = $wpdb->update(
            $this->types_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if (false === $updated) {
            return new WP_Error('leb_update_failed', __('Failed to update the type. Please try again.', 'listing-engine-backend'));
        }

        return true;
    }

    /**
     * Delete an existing type record by its ID.
     *
     * @param int $id Row ID to delete.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_type(int $id)
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->types_table,
            ['id' => $id],
            ['%d']
        );

        if (false === $deleted) {
            return new WP_Error('leb_delete_failed', __('Failed to delete the type. Please try again.', 'listing-engine-backend'));
        }

        return true;
    }

    /**
     * Delete multiple type records by their IDs.
     *
     * @param array $ids Array of row IDs to delete.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_types(array $ids)
    {
        global $wpdb;

        if (empty($ids)) {
            return true;
        }

        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        $query           = $wpdb->prepare("DELETE FROM {$this->types_table} WHERE id IN ($ids_placeholder)", $ids);
        $deleted         = $wpdb->query($query);

        if (false === $deleted) {
            return new WP_Error('leb_bulk_delete_failed', __('Failed to delete selected types. Please try again.', 'listing-engine-backend'));
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────
    // Internal Helpers
    // ─────────────────────────────────────────────────────────

    /**
     * Check whether a given slug already exists (optionally excluding a specific row ID).
     *
     * @param string   $slug      Slug to check.
     * @param int|null $exclude_id Row ID to exclude from the check (used during updates).
     * @return bool TRUE if a duplicate exists.
     */
    private function slug_exists(string $slug, ?int $exclude_id = null): bool
    {
        global $wpdb;

        if ($exclude_id) {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM `{$this->types_table}` WHERE slug = %s AND id != %d",
                    $slug,
                    $exclude_id
                )
            );
        } else {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM `{$this->types_table}` WHERE slug = %s",
                    $slug
                )
            );
        }

        return (bool) $count;
    }

    // ─────────────────────────────────────────────────────────
    // Amenities – Table Maintenance
    // ─────────────────────────────────────────────────────────

    /**
     * Create or repair the ls_amenities table.
     *
     * Uses dbDelta() for safe upgrades. SVG validation is intentionally
     * handled at the AJAX/template layer; this method only concerns itself
     * with table structure.
     *
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function create_or_repair_amenities_table()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql    = leb_get_amenities_schema();
        $result = dbDelta($sql);

        // dbDelta does not return errors directly; check table existence.
        $status = leb_check_table_status($this->amenities_table);

        if (! $status['exists']) {
            return new WP_Error(
                'leb_amen_table_create_failed',
                __('Amenities table could not be created. Check database permissions.', 'listing-engine-backend')
            );
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────
    // Amenities – Read Operations
    // ─────────────────────────────────────────────────────────

    /**
     * Retrieve a paginated, optionally searched list of amenities.
     *
     * @param string $search   Search term (matched against name).
     * @param int    $page     1-based page number.
     * @param int    $per_page Items per page.
     * @return array {
     *     @type array $items  Rows from the database.
     *     @type int   $total  Total row count matching the query.
     * }
     */
    public function get_amenities(string $search = '', int $page = 1, int $per_page = 10): array
    {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        if (! empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM `{$this->amenities_table}` WHERE name LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
                    $like,
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );

            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM `{$this->amenities_table}` WHERE name LIKE %s",
                    $like
                )
            );
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT * FROM `{$this->amenities_table}` ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total = (int) $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM `{$this->amenities_table}`"
            );
        }

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }

    /**
     * Retrieve a single amenity record by its ID.
     *
     * @param int $id Row ID.
     * @return array|null Associative array of the row, or null if not found.
     */
    public function get_amenity_by_id(int $id): ?array
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $row = $wpdb->get_row(
            $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM `{$this->amenities_table}` WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    // ─────────────────────────────────────────────────────────
    // Amenities – Write Operations
    // ─────────────────────────────────────────────────────────

    /**
     * Insert a new amenity record.
     *
     * @param string $name     Amenity display name.
     * @param string $svg_path WordPress media attachment URL for the SVG icon (may be empty).
     * @return int|WP_Error Inserted row ID on success, WP_Error on failure.
     */
    public function create_amenity(string $name, string $svg_path = '')
    {
        global $wpdb;

        $name     = sanitize_text_field($name);
        $svg_path = wp_unslash($svg_path);

        $inserted = $wpdb->insert(
            $this->amenities_table,
            [
                'name'       => $name,
                'svg_path'   => $svg_path,
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s']
        );

        if (false === $inserted) {
            return new WP_Error(
                'leb_amen_insert_failed',
                __('Failed to create the amenity. Please try again.', 'listing-engine-backend')
            );
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Update an existing amenity record.
     *
     * @param int    $id       Row ID to update.
     * @param string $name     New amenity display name.
     * @param string $svg_path New SVG attachment URL (empty string clears it).
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function update_amenity(int $id, string $name, string $svg_path = '')
    {
        global $wpdb;

        $name     = sanitize_text_field($name);
        $svg_path = wp_unslash($svg_path);

        $updated = $wpdb->update(
            $this->amenities_table,
            [
                'name'       => $name,
                'svg_path'   => $svg_path,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if (false === $updated) {
            return new WP_Error(
                'leb_amen_update_failed',
                __('Failed to update the amenity. Please try again.', 'listing-engine-backend')
            );
        }

        return true;
    }

    /**
     * Delete an existing amenity record by its ID.
     *
     * @param int $id Row ID to delete.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_amenity(int $id)
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->amenities_table,
            ['id' => $id],
            ['%d']
        );

        if (false === $deleted) {
            return new WP_Error(
                'leb_amen_delete_failed',
                __('Failed to delete the amenity. Please try again.', 'listing-engine-backend')
            );
        }

        return true;
    }

    /**
     * Delete multiple amenity records by their IDs.
     *
     * @param array $ids Array of row IDs to delete.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_amenities(array $ids)
    {
        global $wpdb;

        if (empty($ids)) {
            return true;
        }

        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query   = $wpdb->prepare("DELETE FROM `{$this->amenities_table}` WHERE id IN ($ids_placeholder)", $ids);
        $deleted = $wpdb->query($query);

        if (false === $deleted) {
            return new WP_Error(
                'leb_amen_bulk_delete_failed',
                __('Failed to delete selected amenities. Please try again.', 'listing-engine-backend')
            );
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────
    // Locations – Table Maintenance
    // ─────────────────────────────────────────────────────────

    /**
     * Create or repair the ls_location table.
     *
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function create_or_repair_locations_table()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql    = leb_get_locations_schema();
        $result = dbDelta($sql);

        $status = leb_check_table_status($this->locations_table);

        if (! $status['exists']) {
            return new WP_Error(
                'leb_loc_table_create_failed',
                __('Locations table could not be created. Check database permissions.', 'listing-engine-backend')
            );
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────
    // Locations – Read Operations
    // ─────────────────────────────────────────────────────────

    /**
     * Retrieve a paginated, optionally searched list of locations.
     */
    public function get_locations(string $search = '', int $page = 1, int $per_page = 10): array
    {
        global $wpdb;
        $offset = ($page - 1) * $per_page;

        if (! empty($search)) {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->locations_table}` WHERE name LIKE %s OR slug LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
                    $like,
                    $like,
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$this->locations_table}` WHERE name LIKE %s OR slug LIKE %s",
                    $like,
                    $like
                )
            );
        } else {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->locations_table}` ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                ),
                ARRAY_A
            );
            $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$this->locations_table}`");
        }

        return ['items' => $items ?: [], 'total' => $total];
    }

    /**
     * Get single location by ID.
     */
    public function get_location_by_id(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$this->locations_table}` WHERE id = %d", $id),
            ARRAY_A
        );
        return $row ?: null;
    }

    // ─────────────────────────────────────────────────────────
    // Locations – Write Operations
    // ─────────────────────────────────────────────────────────

    /**
     * Create location.
     */
    public function create_location(string $name, string $slug, string $svg_path = '')
    {
        global $wpdb;
        $name = sanitize_text_field($name);
        $slug = sanitize_title($slug);

        if ($this->location_slug_exists($slug)) {
            return new WP_Error('leb_loc_duplicate_slug', __('A location with this slug already exists.', 'listing-engine-backend'));
        }

        $inserted = $wpdb->insert(
            $this->locations_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'svg_path'   => wp_unslash($svg_path),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s']
        );

        if (false === $inserted) {
            return new WP_Error('leb_loc_insert_failed', __('Failed to create the location.', 'listing-engine-backend'));
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Update location.
     */
    public function update_location(int $id, string $name, string $slug, string $svg_path = '')
    {
        global $wpdb;
        $name = sanitize_text_field($name);
        $slug = sanitize_title($slug);

        if ($this->location_slug_exists($slug, $id)) {
            return new WP_Error('leb_loc_duplicate_slug', __('A location with this slug already exists.', 'listing-engine-backend'));
        }

        $updated = $wpdb->update(
            $this->locations_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'svg_path'   => wp_unslash($svg_path),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if (false === $updated) {
            return new WP_Error('leb_loc_update_failed', __('Failed to update location.', 'listing-engine-backend'));
        }
        return true;
    }

    /**
     * Delete single location.
     */
    public function delete_location(int $id)
    {
        global $wpdb;
        $deleted = $wpdb->delete($this->locations_table, ['id' => $id], ['%d']);
        if (false === $deleted) {
            return new WP_Error('leb_loc_delete_failed', __('Failed to delete the location.', 'listing-engine-backend'));
        }
        return true;
    }

    /**
     * Bulk delete locations.
     */
    public function delete_locations(array $ids)
    {
        global $wpdb;
        if (empty($ids)) return true;
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        $query   = $wpdb->prepare("DELETE FROM `{$this->locations_table}` WHERE id IN ($ids_placeholder)", $ids);
        $deleted = $wpdb->query($query);
        if (false === $deleted) {
            return new WP_Error('leb_loc_bulk_delete_failed', __('Failed to delete locations.', 'listing-engine-backend'));
        }
        return true;
    }

    /**
     * Helper: check if location slug exists.
     */
    private function location_slug_exists(string $slug, ?int $exclude_id = null): bool
    {
        global $wpdb;
        if ($exclude_id) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$this->locations_table}` WHERE slug = %s AND id != %d", $slug, $exclude_id));
        } else {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$this->locations_table}` WHERE slug = %s", $slug));
        }
        return (bool) $count;
    }

    // ─────────────────────────────────────────────────────────
    // SECTION 2: PROPERTY LISTING DATA TABLES MAINTENANCE
    // (Listings, Images, Block Dates)
    // ─────────────────────────────────────────────────────────

    /**
     * Create or repair the ls_listings table.
     *
     * @return true|WP_Error
     */
    /**
     * Create or repair the 'ls_listings' table.
     * 
     * This is the main table that stores user-submitted property listings.
     *
     * @return true|WP_Error
     */
    public function create_or_repair_listings_table()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = leb_get_listings_schema();
        dbDelta($sql);
        $status = leb_check_table_status($this->listings_table);
        if (! $status['exists']) {
            return new WP_Error('leb_listings_table_create_failed', __('Listings table could not be created. Check database permissions.', 'listing-engine-backend'));
        }
        return true;
    }

    /**
     * Create or repair the ls_img table.
     *
     * @return true|WP_Error
     */
    /**
     * Create or repair the 'ls_img' table.
     * 
     * Handles images linked to properties via property_id.
     *
     * @return true|WP_Error
     */
    public function create_or_repair_ls_img_table()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = leb_get_ls_img_schema();
        dbDelta($sql);
        $status = leb_check_table_status($this->ls_img_table);
        if (! $status['exists']) {
            return new WP_Error('leb_ls_img_table_create_failed', __('Images table could not be created. Check database permissions.', 'listing-engine-backend'));
        }
        return true;
    }

    /**
     * Create or repair the ls_block_date table.
     *
     * @return true|WP_Error
     */
    /**
     * Create or repair the 'ls_block_date' table.
     * 
     * Handles blocked calendar dates for property availability.
     *
     * @return true|WP_Error
     */
    public function create_or_repair_ls_block_date_table()
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = leb_get_ls_block_date_schema();
        dbDelta($sql);
        $status = leb_check_table_status($this->ls_block_date_table);
        if (! $status['exists']) {
            return new WP_Error('leb_ls_block_date_table_create_failed', __('Block Date table could not be created. Check database permissions.', 'listing-engine-backend'));
        }
        return true;
    }

    // ─────────────────────────────────────────────────────────
    // SECTION 3: PROPERTY LISTINGS CRUD
    // (Listings, Images, Block Dates – Full CRUD)
    // ─────────────────────────────────────────────────────────

    /**
     * Retrieve a paginated, optionally searched/filtered list of property listings.
     *
     * Performs LEFT JOINs to:
     *  - wp_ls_types  → to resolve type ID into display name.
     *  - wp_users     → to resolve user_id into login username.
     *  - wp_ls_img    → to fetch the first image for the thumbnail.
     *
     * @param string $search   Search term (matched against title).
     * @param int    $page     1-based page number.
     * @param int    $per_page Items per page.
     * @param string $status   Optional status filter (pending, published, rejected, draft).
     * @return array {
     *     @type array $items  Rows from the database.
     *     @type int   $total  Total row count matching the query.
     * }
     */
    public function get_listings(string $search = '', int $page = 1, int $per_page = 10, string $status = ''): array
    {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        // Base SELECT with JOINs for type name, username, and first image.
        $base_select = "SELECT l.*, 
            t.name AS type_name, 
            u.user_login AS username,
            img.image AS first_image
            FROM `{$this->listings_table}` AS l
            LEFT JOIN `{$this->types_table}` AS t ON l.type = t.id
            LEFT JOIN `{$wpdb->users}` AS u ON l.user_id = u.ID
            LEFT JOIN `{$this->ls_img_table}` AS img ON img.property_id = l.id";

        $base_count = "SELECT COUNT(*) FROM `{$this->listings_table}` AS l";

        // Build WHERE clauses.
        $where_parts = [];
        $where_vals  = [];

        if (! empty($status)) {
            $where_parts[] = 'l.status = %s';
            $where_vals[]  = $status;
        }

        if (! empty($search)) {
            $like          = '%' . $wpdb->esc_like($search) . '%';
            $where_parts[] = 'l.title LIKE %s';
            $where_vals[]  = $like;
        }

        $where_sql = '';
        if (! empty($where_parts)) {
            $where_sql = ' WHERE ' . implode(' AND ', $where_parts);
        }

        // Count total matching rows.
        if (! empty($where_vals)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var($wpdb->prepare($base_count . $where_sql, $where_vals));
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var($base_count . $where_sql);
        }

        // Fetch items with pagination.
        $order_limit = ' GROUP BY l.id ORDER BY l.id DESC LIMIT %d OFFSET %d';
        $query_vals  = array_merge($where_vals, [$per_page, $offset]);

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $items = $wpdb->get_results(
            $wpdb->prepare($base_select . $where_sql . $order_limit, $query_vals),
            ARRAY_A
        );

        // Process images: Decode the JSON and take the first image's URL.
        if ($items) {
            foreach ($items as &$item) {
                if (! empty($item['first_image'])) {
                    $imgs = json_decode($item['first_image'], true);
                    if (is_array($imgs) && ! empty($imgs[0]['url'])) {
                        $item['first_image'] = $imgs[0]['url'];
                    } else {
                        $item['first_image'] = '';
                    }
                }
            }
            unset($item);
        }

        return [
            'items' => $items ?: [],
            'total' => $total,
        ];
    }

    /**
     * Retrieve a single listing by ID with all related data.
     *
     * Fetches:
     *  - Main listing row with type name and username.
     *  - Images from wp_ls_img.
     *  - Blocked dates from wp_ls_block_date.
     *  - Host info (email, mobile) from wp_users and wp_usermeta.
     *
     * @param int $id Row ID.
     * @return array|null Full listing data, or null if not found.
     */
    public function get_listing_by_id(int $id): ?array
    {
        global $wpdb;

        // Main listing with joins.
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT l.*, 
                    t.name AS type_name, 
                    u.user_login AS username,
                    u.user_email AS user_email
                FROM `{$this->listings_table}` AS l
                LEFT JOIN `{$this->types_table}` AS t ON l.type = t.id
                LEFT JOIN `{$wpdb->users}` AS u ON l.user_id = u.ID
                WHERE l.id = %d",
                $id
            ),
            ARRAY_A
        );

        if (! $row) {
            return null;
        }

        // Fetch images for this property.
        $images_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT image FROM `{$this->ls_img_table}` WHERE property_id = %d LIMIT 1",
                $id
            ),
            ARRAY_A
        );
        $row['images'] = $images_row ? $images_row['image'] : '[]';

        // Fetch blocked dates.
        $dates_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT dates FROM `{$this->ls_block_date_table}` WHERE property_id = %d LIMIT 1",
                $id
            ),
            ARRAY_A
        );
        $row['blocked_dates'] = $dates_row ? $dates_row['dates'] : '[]';

        // Fetch host mobile number from usermeta.
        if (! empty($row['user_id'])) {
            $mobile = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT meta_value FROM `{$wpdb->usermeta}` WHERE user_id = %d AND meta_key = 'mobile_number' LIMIT 1",
                    $row['user_id']
                )
            );
            $row['user_mobile'] = $mobile ?: '';
        }

        return $row;
    }

    /**
     * Create a new property listing along with images and blocked dates.
     *
     * @param array $data {
     *     @type int    $user_id     WordPress user ID.
     *     @type string $title       Property title.
     *     @type string $description Property description.
     *     @type int    $guests      Number of guests.
     *     @type int    $bedroom     Number of bedrooms.
     *     @type int    $bed         Number of beds.
     *     @type int    $bathroom    Number of bathrooms.
     *     @type int    $price       Price per night.
     *     @type int    $type        Type ID from ls_types.
     *     @type int    $location    Location ID from ls_location.
     *     @type string $amenities   Comma-separated amenity IDs.
     *     @type string $status      Listing status (draft, pending, published, rejected).
     *     @type string $images      JSON array of image objects.
     *     @type string $dates       JSON array of blocked date strings.
     * }
     * @return int|WP_Error Inserted listing ID on success, WP_Error on failure.
     */
    public function create_listing(array $data)
    {
        global $wpdb;

        $amenities = $data['amenities'] ?? '';
        if (is_array($amenities)) {
            $amenities = json_encode($amenities);
        }

        $inserted = $wpdb->insert(
            $this->listings_table,
            [
                'user_id'     => absint($data['user_id'] ?? 0),
                'title'       => sanitize_text_field($data['title'] ?? ''),
                'description' => wp_kses_post($data['description'] ?? ''),
                'guests'      => absint($data['guests'] ?? 0),
                'bedroom'     => absint($data['bedroom'] ?? 0),
                'bed'         => absint($data['bed'] ?? 0),
                'bathroom'    => absint($data['bathroom'] ?? 0),
                'price'       => absint($data['price'] ?? 0),
                'type'        => sanitize_text_field($data['type'] ?? ''),
                'location'    => sanitize_text_field($data['location'] ?? ''),
                'address'     => sanitize_text_field($data['address'] ?? ''),
                'amenities'   => sanitize_text_field($amenities),
                'status'      => sanitize_text_field($data['status'] ?? 'draft'),
                'updated_at'  => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if (false === $inserted) {
            return new WP_Error('leb_listing_insert_failed', __('Failed to create the listing.', 'listing-engine-backend'));
        }

        $listing_id = (int) $wpdb->insert_id;

        // Save images (single row per property).
        $images_json = $data['images'] ?? '[]';
        if (is_array($images_json)) {
            $images_json = json_encode($images_json);
        }

        $wpdb->insert(
            $this->ls_img_table,
            [
                'property_id' => $listing_id,
                'image'       => $images_json,
            ],
            ['%d', '%s']
        );

        // Save blocked dates (single row per property).
        $dates_json = $data['dates'] ?? '[]';
        if (is_array($dates_json)) {
            $dates_json = json_encode($dates_json);
        }

        if (! empty($dates_json) && '[]' !== $dates_json) {
            $wpdb->insert(
                $this->ls_block_date_table,
                [
                    'property_id' => $listing_id,
                    'dates'       => $dates_json,
                    'created_at'  => current_time('mysql'),
                ],
                ['%d', '%s', '%s']
            );
        }

        return $listing_id;
    }

    /**
     * Update an existing property listing and its related data.
     *
     * @param int   $id   Listing ID to update.
     * @param array $data Same structure as create_listing data.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function update_listing(int $id, array $data)
    {
        global $wpdb;

        $amenities = $data['amenities'] ?? '';
        if (is_array($amenities)) {
            $amenities = json_encode($amenities);
        }

        $updated = $wpdb->update(
            $this->listings_table,
            [
                'title'       => sanitize_text_field($data['title'] ?? ''),
                'description' => wp_kses_post($data['description'] ?? ''),
                'guests'      => absint($data['guests'] ?? 0),
                'bedroom'     => absint($data['bedroom'] ?? 0),
                'bed'         => absint($data['bed'] ?? 0),
                'bathroom'    => absint($data['bathroom'] ?? 0),
                'price'       => absint($data['price'] ?? 0),
                'type'        => sanitize_text_field($data['type'] ?? ''),
                'location'    => sanitize_text_field($data['location'] ?? ''),
                'address'     => sanitize_text_field($data['address'] ?? ''),
                'amenities'   => sanitize_text_field($amenities),
                'status'      => sanitize_text_field($data['status'] ?? 'draft'),
                'updated_at'  => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s', '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if (false === $updated) {
            return new WP_Error('leb_listing_update_failed', __('Failed to update the listing.', 'listing-engine-backend'));
        }

        // Update images: delete existing row, insert fresh.
        $images_json = $data['images'] ?? '[]';
        if (is_array($images_json)) {
            $images_json = json_encode($images_json);
        }

        $wpdb->delete($this->ls_img_table, ['property_id' => $id], ['%d']);
        $wpdb->insert(
            $this->ls_img_table,
            [
                'property_id' => $id,
                'image'       => $images_json,
            ],
            ['%d', '%s']
        );

        // Update blocked dates: delete existing, insert fresh.
        $dates_json = $data['dates'] ?? '[]';
        if (is_array($dates_json)) {
            $dates_json = json_encode($dates_json);
        }

        $wpdb->delete($this->ls_block_date_table, ['property_id' => $id], ['%d']);
        if (! empty($dates_json) && '[]' !== $dates_json) {
            $wpdb->insert(
                $this->ls_block_date_table,
                [
                    'property_id' => $id,
                    'dates'       => $dates_json,
                    'created_at'  => current_time('mysql'),
                ],
                ['%d', '%s', '%s']
            );
        }

        return true;
    }

    /**
     * Delete a single property listing and all related data (images, block dates).
     *
     * @param int $id Listing ID.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_listing(int $id)
    {
        global $wpdb;

        // Fetch and delete associated Media Library attachments first.
        $images_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT image FROM `{$this->ls_img_table}` WHERE property_id = %d LIMIT 1",
                $id
            )
        );

        if ($images_row && ! empty($images_row->image)) {
            $images = json_decode($images_row->image, true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    $attachment_id = $img['id'] ?? null;
                    if ($attachment_id) {
                        wp_delete_attachment($attachment_id, true);
                    }
                }
            }
        }

        // Cascade: remove related images and block dates first.
        $wpdb->delete($this->ls_img_table, ['property_id' => $id], ['%d']);
        $wpdb->delete($this->ls_block_date_table, ['property_id' => $id], ['%d']);

        $deleted = $wpdb->delete($this->listings_table, ['id' => $id], ['%d']);

        if (false === $deleted) {
            return new WP_Error('leb_listing_delete_failed', __('Failed to delete the listing.', 'listing-engine-backend'));
        }

        return true;
    }

    /**
     * Bulk delete multiple property listings and all related data.
     *
     * @param array $ids Array of listing IDs.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_listings(array $ids)
    {
        global $wpdb;

        if (empty($ids)) {
            return true;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));

        // Fetch all associated Media Library attachments for these listings.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $images_results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT image FROM `{$this->ls_img_table}` WHERE property_id IN ($placeholders)",
                $ids
            )
        );

        if ($images_results) {
            foreach ($images_results as $row) {
                if (! empty($row->image)) {
                    $images = json_decode($row->image, true);
                    if (is_array($images)) {
                        foreach ($images as $img) {
                            $attachment_id = $img['id'] ?? null;
                            if ($attachment_id) {
                                wp_delete_attachment($attachment_id, true);
                            }
                        }
                    }
                }
            }
        }

        // Cascade: remove related images and block dates.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("DELETE FROM `{$this->ls_img_table}` WHERE property_id IN ($placeholders)", $ids));
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare("DELETE FROM `{$this->ls_block_date_table}` WHERE property_id IN ($placeholders)", $ids));

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM `{$this->listings_table}` WHERE id IN ($placeholders)", $ids));

        if (false === $deleted) {
            return new WP_Error('leb_listing_bulk_delete_failed', __('Failed to delete selected listings.', 'listing-engine-backend'));
        }

        return true;
    }

    /**
     * Bulk update status for multiple listings.
     *
     * @param array  $ids    Array of listing IDs.
     * @param string $status New status value.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function update_listings_status(array $ids, string $status)
    {
        global $wpdb;

        if (empty($ids)) {
            return true;
        }

        $status       = sanitize_text_field($status);
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $values       = array_merge([$status, current_time('mysql')], $ids);

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $updated = $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$this->listings_table}` SET status = %s, updated_at = %s WHERE id IN ($placeholders)",
                $values
            )
        );

        if (false === $updated) {
            return new WP_Error('leb_listing_bulk_status_failed', __('Failed to update status for selected listings.', 'listing-engine-backend'));
        }

        return true;
    }

    /**
     * Get listing counts grouped by status.
     *
     * @return array Associative array of status => count.
     */
    public function get_status_counts(): array
    {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM `{$this->listings_table}` GROUP BY status",
            ARRAY_A
        );

        $counts = [
            'pending'   => 0,
            'published' => 0,
            'rejected'  => 0,
            'draft'     => 0,
        ];

        if ($results) {
            foreach ($results as $row) {
                if (isset($counts[$row['status']])) {
                    $counts[$row['status']] = (int) $row['count'];
                }
            }
        }

        return $counts;
    }

    /**
     * Fetch ALL amenities (no pagination) for the add/edit property form checkbox picker.
     *
     * @return array Array of amenity rows with id, name, svg_path.
     */
    public function get_all_amenities(): array
    {
        global $wpdb;
        $items = $wpdb->get_results(
            "SELECT id, name, svg_path FROM `{$this->amenities_table}` ORDER BY name ASC",
            ARRAY_A
        );
        return $items ?: [];
    }

    /**
     * Fetch ALL locations (no pagination) for the add/edit property form dropdown.
     *
     * @return array Array of location rows with id, name.
     */
    public function get_all_locations(): array
    {
        global $wpdb;
        $items = $wpdb->get_results(
            "SELECT id, name FROM `{$this->locations_table}` ORDER BY name ASC",
            ARRAY_A
        );
        return $items ?: [];
    }

    /**
     * Fetch ALL types (no pagination) for the add/edit property form dropdown.
     *
     * @return array Array of type rows with id, name.
     */
    public function get_all_types(): array
    {
        global $wpdb;
        $items = $wpdb->get_results(
            "SELECT id, name FROM `{$this->types_table}` ORDER BY name ASC",
            ARRAY_A
        );
        return $items ?: [];
    }
}
