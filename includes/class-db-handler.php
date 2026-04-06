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
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class LEB_Database_Handler
 *
 * Handles all CRUD operations and table maintenance for the LEB plugin.
 */
class LEB_Database_Handler {

    /**
     * The fully-qualified name of the ls_types table.
     *
     * @var string
     */
    private string $types_table;

    /**
     * The fully-qualified name of the ls_ameneties table.
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
    public function __construct() {
        global $wpdb;
        $this->types_table      = $wpdb->prefix . 'ls_types';
        $this->amenities_table  = $wpdb->prefix . 'ls_ameneties';
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
    public function create_or_repair_types_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql    = leb_get_types_schema();
        $result = dbDelta( $sql );

        // dbDelta does not return errors directly; check table existence instead.
        $status = leb_check_table_status( $this->types_table );

        if ( ! $status['exists'] ) {
            return new WP_Error( 'leb_table_create_failed', __( 'Table could not be created. Check database permissions.', 'listing-engine-backend' ) );
        }

        // Insert any missing default rows (if any defined in db-schema.php).
        $this->seed_default_rows();

        return true;
    }

    /**
     * Seeds default rows into the types table if they don't already exist.
     */
    private function seed_default_rows() {
        global $wpdb;

        $default_rows = leb_get_default_type_rows();

        if ( empty( $default_rows ) ) {
            return;
        }

        foreach ( $default_rows as $row ) {
            $slug_exists = $wpdb->get_var(
                $wpdb->prepare(
                    // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                    "SELECT COUNT(*) FROM `{$this->types_table}` WHERE slug = %s",
                    $row['slug']
                )
            );

            if ( ! $slug_exists ) {
                $wpdb->insert(
                    $this->types_table,
                    [
                        'name'       => sanitize_text_field( $row['name'] ),
                        'slug'       => sanitize_title( $row['slug'] ),
                        'updated_at' => current_time( 'mysql' ),
                    ],
                    [ '%s', '%s', '%s' ]
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
    public function get_types( string $search = '', int $page = 1, int $per_page = 10 ): array {
        global $wpdb;

        $offset = ( $page - 1 ) * $per_page;

        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';

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
    public function get_type_by_id( int $id ): ?array {
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
    public function create_type( string $name, string $slug ) {
        global $wpdb;

        $name = sanitize_text_field( $name );
        $slug = sanitize_title( $slug );     // Enforces lowercase.

        // Check for duplicate slug.
        if ( $this->slug_exists( $slug ) ) {
            return new WP_Error( 'leb_duplicate_slug', __( 'A type with this slug already exists.', 'listing-engine-backend' ) );
        }

        $inserted = $wpdb->insert(
            $this->types_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            return new WP_Error( 'leb_insert_failed', __( 'Failed to create the type. Please try again.', 'listing-engine-backend' ) );
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
    public function update_type( int $id, string $name, string $slug ) {
        global $wpdb;

        $name = sanitize_text_field( $name );
        $slug = sanitize_title( $slug );

        // Check for duplicate slug excluding the current row.
        if ( $this->slug_exists( $slug, $id ) ) {
            return new WP_Error( 'leb_duplicate_slug', __( 'A type with this slug already exists.', 'listing-engine-backend' ) );
        }

        $updated = $wpdb->update(
            $this->types_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return new WP_Error( 'leb_update_failed', __( 'Failed to update the type. Please try again.', 'listing-engine-backend' ) );
        }

        return true;
    }

    /**
     * Delete an existing type record by its ID.
     *
     * @param int $id Row ID to delete.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_type( int $id ) {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->types_table,
            [ 'id' => $id ],
            [ '%d' ]
        );

        if ( false === $deleted ) {
            return new WP_Error( 'leb_delete_failed', __( 'Failed to delete the type. Please try again.', 'listing-engine-backend' ) );
        }

        return true;
    }

    /**
     * Delete multiple type records by their IDs.
     *
     * @param array $ids Array of row IDs to delete.
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function delete_types( array $ids ) {
        global $wpdb;

        if ( empty( $ids ) ) {
            return true;
        }

        $ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $query           = $wpdb->prepare( "DELETE FROM {$this->types_table} WHERE id IN ($ids_placeholder)", $ids );
        $deleted         = $wpdb->query( $query );

        if ( false === $deleted ) {
            return new WP_Error( 'leb_bulk_delete_failed', __( 'Failed to delete selected types. Please try again.', 'listing-engine-backend' ) );
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
    private function slug_exists( string $slug, ?int $exclude_id = null ): bool {
        global $wpdb;

        if ( $exclude_id ) {
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
     * Create or repair the ls_ameneties table.
     *
     * Uses dbDelta() for safe upgrades. SVG validation is intentionally
     * handled at the AJAX/template layer; this method only concerns itself
     * with table structure.
     *
     * @return true|WP_Error TRUE on success, WP_Error on failure.
     */
    public function create_or_repair_amenities_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql    = leb_get_amenities_schema();
        $result = dbDelta( $sql );

        // dbDelta does not return errors directly; check table existence.
        $status = leb_check_table_status( $this->amenities_table );

        if ( ! $status['exists'] ) {
            return new WP_Error(
                'leb_amen_table_create_failed',
                __( 'Amenities table could not be created. Check database permissions.', 'listing-engine-backend' )
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
    public function get_amenities( string $search = '', int $page = 1, int $per_page = 10 ): array {
        global $wpdb;

        $offset = ( $page - 1 ) * $per_page;

        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';

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
    public function get_amenity_by_id( int $id ): ?array {
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
    public function create_amenity( string $name, string $svg_path = '' ) {
        global $wpdb;

        $name     = sanitize_text_field( $name );
        $svg_path = wp_unslash( $svg_path );

        $inserted = $wpdb->insert(
            $this->amenities_table,
            [
                'name'       => $name,
                'svg_path'   => $svg_path,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            return new WP_Error(
                'leb_amen_insert_failed',
                __( 'Failed to create the amenity. Please try again.', 'listing-engine-backend' )
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
    public function update_amenity( int $id, string $name, string $svg_path = '' ) {
        global $wpdb;

        $name     = sanitize_text_field( $name );
        $svg_path = wp_unslash( $svg_path );

        $updated = $wpdb->update(
            $this->amenities_table,
            [
                'name'       => $name,
                'svg_path'   => $svg_path,
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return new WP_Error(
                'leb_amen_update_failed',
                __( 'Failed to update the amenity. Please try again.', 'listing-engine-backend' )
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
    public function delete_amenity( int $id ) {
        global $wpdb;

        $deleted = $wpdb->delete(
            $this->amenities_table,
            [ 'id' => $id ],
            [ '%d' ]
        );

        if ( false === $deleted ) {
            return new WP_Error(
                'leb_amen_delete_failed',
                __( 'Failed to delete the amenity. Please try again.', 'listing-engine-backend' )
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
    public function delete_amenities( array $ids ) {
        global $wpdb;

        if ( empty( $ids ) ) {
            return true;
        }

        $ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query   = $wpdb->prepare( "DELETE FROM `{$this->amenities_table}` WHERE id IN ($ids_placeholder)", $ids );
        $deleted = $wpdb->query( $query );

        if ( false === $deleted ) {
            return new WP_Error(
                'leb_amen_bulk_delete_failed',
                __( 'Failed to delete selected amenities. Please try again.', 'listing-engine-backend' )
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
    public function create_or_repair_locations_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql    = leb_get_locations_schema();
        $result = dbDelta( $sql );

        $status = leb_check_table_status( $this->locations_table );

        if ( ! $status['exists'] ) {
            return new WP_Error(
                'leb_loc_table_create_failed',
                __( 'Locations table could not be created. Check database permissions.', 'listing-engine-backend' )
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
    public function get_locations( string $search = '', int $page = 1, int $per_page = 10 ): array {
        global $wpdb;
        $offset = ( $page - 1 ) * $per_page;

        if ( ! empty( $search ) ) {
            $like = '%' . $wpdb->esc_like( $search ) . '%';
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->locations_table}` WHERE name LIKE %s OR slug LIKE %s ORDER BY id DESC LIMIT %d OFFSET %d",
                    $like, $like, $per_page, $offset
                ),
                ARRAY_A
            );
            $total = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM `{$this->locations_table}` WHERE name LIKE %s OR slug LIKE %s",
                    $like, $like
                )
            );
        } else {
            $items = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM `{$this->locations_table}` ORDER BY id DESC LIMIT %d OFFSET %d",
                    $per_page, $offset
                ),
                ARRAY_A
            );
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$this->locations_table}`" );
        }

        return [ 'items' => $items ?: [], 'total' => $total ];
    }

    /**
     * Get single location by ID.
     */
    public function get_location_by_id( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM `{$this->locations_table}` WHERE id = %d", $id ),
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
    public function create_location( string $name, string $slug, string $svg_path = '' ) {
        global $wpdb;
        $name = sanitize_text_field( $name );
        $slug = sanitize_title( $slug );

        if ( $this->location_slug_exists( $slug ) ) {
            return new WP_Error( 'leb_loc_duplicate_slug', __( 'A location with this slug already exists.', 'listing-engine-backend' ) );
        }

        $inserted = $wpdb->insert(
            $this->locations_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'svg_path'   => wp_unslash( $svg_path ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s' ]
        );

        if ( false === $inserted ) {
            return new WP_Error( 'leb_loc_insert_failed', __( 'Failed to create the location.', 'listing-engine-backend' ) );
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Update location.
     */
    public function update_location( int $id, string $name, string $slug, string $svg_path = '' ) {
        global $wpdb;
        $name = sanitize_text_field( $name );
        $slug = sanitize_title( $slug );

        if ( $this->location_slug_exists( $slug, $id ) ) {
            return new WP_Error( 'leb_loc_duplicate_slug', __( 'A location with this slug already exists.', 'listing-engine-backend' ) );
        }

        $updated = $wpdb->update(
            $this->locations_table,
            [
                'name'       => $name,
                'slug'       => $slug,
                'svg_path'   => wp_unslash( $svg_path ),
                'updated_at' => current_time( 'mysql' ),
            ],
            [ 'id' => $id ],
            [ '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        if ( false === $updated ) {
            return new WP_Error( 'leb_loc_update_failed', __( 'Failed to update location.', 'listing-engine-backend' ) );
        }
        return true;
    }

    /**
     * Delete single location.
     */
    public function delete_location( int $id ) {
        global $wpdb;
        $deleted = $wpdb->delete( $this->locations_table, [ 'id' => $id ], [ '%d' ] );
        if ( false === $deleted ) {
            return new WP_Error( 'leb_loc_delete_failed', __( 'Failed to delete the location.', 'listing-engine-backend' ) );
        }
        return true;
    }

    /**
     * Bulk delete locations.
     */
    public function delete_locations( array $ids ) {
        global $wpdb;
        if ( empty( $ids ) ) return true;
        $ids_placeholder = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $query   = $wpdb->prepare( "DELETE FROM `{$this->locations_table}` WHERE id IN ($ids_placeholder)", $ids );
        $deleted = $wpdb->query( $query );
        if ( false === $deleted ) {
            return new WP_Error( 'leb_loc_bulk_delete_failed', __( 'Failed to delete locations.', 'listing-engine-backend' ) );
        }
        return true;
    }

    /**
     * Helper: check if location slug exists.
     */
    private function location_slug_exists( string $slug, ?int $exclude_id = null ): bool {
        global $wpdb;
        if ( $exclude_id ) {
            $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->locations_table}` WHERE slug = %s AND id != %d", $slug, $exclude_id ) );
        } else {
            $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$this->locations_table}` WHERE slug = %s", $slug ) );
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
    public function create_or_repair_listings_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = leb_get_listings_schema();
        dbDelta( $sql );
        $status = leb_check_table_status( $this->listings_table );
        if ( ! $status['exists'] ) {
            return new WP_Error( 'leb_listings_table_create_failed', __( 'Listings table could not be created. Check database permissions.', 'listing-engine-backend' ) );
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
    public function create_or_repair_ls_img_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = leb_get_ls_img_schema();
        dbDelta( $sql );
        $status = leb_check_table_status( $this->ls_img_table );
        if ( ! $status['exists'] ) {
            return new WP_Error( 'leb_ls_img_table_create_failed', __( 'Images table could not be created. Check database permissions.', 'listing-engine-backend' ) );
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
    public function create_or_repair_ls_block_date_table() {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = leb_get_ls_block_date_schema();
        dbDelta( $sql );
        $status = leb_check_table_status( $this->ls_block_date_table );
        if ( ! $status['exists'] ) {
            return new WP_Error( 'leb_ls_block_date_table_create_failed', __( 'Block Date table could not be created. Check database permissions.', 'listing-engine-backend' ) );
        }
        return true;
    }
}
