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

// =============================================================================
// GLOBAL SETTINGS TABLES (Types, Amenities, Locations)
// =============================================================================

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_types` table.
 * 
 * This table stores property types (e.g., Apartment, Villa, Cabin).
 * It uses a unique slug for easy filtering in the frontend.
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
 * Returns the CREATE TABLE SQL for the `{prefix}ls_amenities` table.
 *
 * This table stores global amenities that properties can offer (e.g., WiFi, Pool).
 * 'svg_path' stores the URL to the icon displayed in the interface.
 *
 * @return string SQL statement.
 */
function leb_get_amenities_schema() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'ls_amenities';
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
 * This table stores physical locations or regions (e.g., New York, Goa).
 * It includes an icon path for map or list markers.
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

// =============================================================================
// PROPERTY DATA TABLES (Listings, Images, Block Dates)
// =============================================================================

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_property` table.
 *
 * This is the CORE table of the plugin. It stores all primary property information:
 * - host_id:    The WP User ID who owns the listing.
 * - location:  Detailed location info (often longtext/JSON).
 * - amenities: List of IDs/names of amenities available.
 * - bed/bath:  Physical inventory of the property.
 *
 * @return string SQL statement.
 */
function leb_get_listings_schema() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ls_property';
    return "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        host_id bigint(20) UNSIGNED NOT NULL,
        title varchar(255) NOT NULL,
        location longtext NOT NULL,
        address longtext NOT NULL,
        amenities longtext DEFAULT NULL,
        type varchar(255) DEFAULT NULL,
        guests int(11) DEFAULT 0,
        bedroom int(11) DEFAULT 0,
        bed int(11) DEFAULT 0,
        bathroom int(11) DEFAULT 0,
        description longtext DEFAULT NULL,
        price bigint(20) DEFAULT 0,
        map longtext DEFAULT NULL,
        status varchar(50) DEFAULT 'draft',
        updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) {$wpdb->get_charset_collate()};";
}

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_img` table.
 *
 * This table stores a gallery of images for each listing.
 * - property_id: Foreign key to ls_property.id.
 * - image:       Full URL or path to the image file.
 *
 * @return string SQL statement.
 */
function leb_get_ls_img_schema() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ls_img';
    return "CREATE TABLE $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        property_id bigint(20) UNSIGNED NOT NULL,
        image text NOT NULL,
        PRIMARY KEY  (id),
        KEY property_id (property_id)
    ) {$wpdb->get_charset_collate()};";
}

/**
 * Returns the CREATE TABLE SQL for the `{prefix}ls_block_date` table.
 *
 * This table manages availability by "blocking" certain dates for a property.
 * - property_id: Foreign key to ls_property.id.
 * - dates:       A list or JSON string of blocked calendar dates.
 *
 * @return string SQL statement.
 */
function leb_get_ls_block_date_schema() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'ls_block_date';
    $charset_collate = $wpdb->get_charset_collate();

    return "CREATE TABLE {$table_name} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  property_id bigint(20) UNSIGNED NOT NULL,
  dates longtext NOT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id)
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
