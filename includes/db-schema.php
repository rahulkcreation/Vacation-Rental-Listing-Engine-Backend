<?php
/**
 * db-schema.php
 *
 * Defines the database schema for the Listing Engine Backend plugin.
 * Contains table SQL, default row definitions, and a status-check helper.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_types` table.
 *
 * Uses dbDelta-compatible formatting:
 * - Two spaces before each KEY definition.
 * - Single blank line between column groups.
 *
 * @return string SQL statement.
 */
function leb_get_types_schema() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'ls_types';
    $charset_collate = $wpdb->get_charset_collate();

    // NOTE: dbDelta requires TWO spaces before PRIMARY KEY / KEY lines.
    return "CREATE TABLE {$table_name} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug)
) {$charset_collate};";
}

/**
 * Returns an array of default rows to seed into `{prefix}ls_types`.
 *
 * Per the specification, no mandatory defaults are defined; extend
 * this array if seed data is required in the future.
 *
 * @return array[] Array of associative arrays with keys: name, slug.
 */
function leb_get_default_type_rows() {
    /**
     * Filter the default type rows seeded into the ls_types table.
     *
     * @param array $rows Array of default row data.
     */
    return apply_filters( 'leb_default_type_rows', [] );
}

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_ameneties` table.
 *
 * Columns:
 *   id         – Auto-incrementing primary key.
 *   name       – Display name of the amenity.
 *   svg_path   – WordPress media library attachment URL for the 24×24 SVG icon.
 *   updated_at – Auto-updated timestamp.
 *
 * Uses dbDelta-compatible formatting (two spaces before KEY definitions).
 *
 * @return string SQL statement.
 */
function leb_get_amenities_schema() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'ls_ameneties';
    $charset_collate = $wpdb->get_charset_collate();

    // NOTE: dbDelta requires TWO spaces before PRIMARY KEY / KEY lines.
    return "CREATE TABLE {$table_name} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  svg_path varchar(2048) DEFAULT NULL,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
) {$charset_collate};";
}

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_location` table.
 *
 * Columns:
 *   id         – Primary key.
 *   name       – Location name.
 *   slug       – Unique URL-friendly slug.
 *   svg_path   – JSON string containing 'path' and 'attachment_id'.
 *   updated_at – Updated timestamp.
 *
 * @return string SQL statement.
 */
function leb_get_locations_schema() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'ls_location';
    $charset_collate = $wpdb->get_charset_collate();

    return "CREATE TABLE {$table_name} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  name varchar(255) NOT NULL,
  slug varchar(255) NOT NULL,
  svg_path varchar(2048) DEFAULT NULL,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug)
) {$charset_collate};";
}

// ─────────────────────────────────────────────────────────────

/**
 * Checks the current status of a given table in the WordPress database.
 *
 * @param string $table_name Fully-qualified table name (with prefix).
 * @return array {
 *     @type bool $exists         Whether the table exists in the DB.
 *     @type bool $rows_complete  Whether all required default rows are present.
 * }
 */
function leb_check_table_status( $table_name ) {
    global $wpdb;

    // Check if the table exists by querying INFORMATION_SCHEMA.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $exists = (bool) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s',
            $wpdb->dbname,
            $table_name
        )
    );

    $rows_complete = false;

    if ( $exists ) {
        $default_rows = leb_get_default_type_rows();

        if ( empty( $default_rows ) ) {
            // No defaults required → always considered complete.
            $rows_complete = true;
        } else {
            // Verify each required slug exists.
            $all_present = true;
            foreach ( $default_rows as $row ) {
                $slug_exists = $wpdb->get_var(
                    $wpdb->prepare(
                        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                        "SELECT COUNT(*) FROM `{$table_name}` WHERE slug = %s",
                        $row['slug']
                    )
                );
                if ( ! $slug_exists ) {
                    $all_present = false;
                    break;
                }
            }
            $rows_complete = $all_present;
        }
    }

    return [
        'exists'        => $exists,
        'rows_complete' => $rows_complete,
    ];
}
