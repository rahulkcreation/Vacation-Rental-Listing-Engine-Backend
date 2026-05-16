<?php
/**
 * SVG Upload Support.
 * Hardcoded bypass for SVG upload restrictions.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Early SVG Support (Hardened - Non-Image Decoy Bypass)
// ─────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'leb_early_svg_intercept', 1 );
add_action( 'init', 'leb_register_svg_mimes_early' );
add_filter( 'upload_mimes', 'leb_allow_svg_uploads', PHP_INT_MAX );
add_filter( 'wp_check_filetype_and_ext', 'leb_fix_svg_mime_type', PHP_INT_MAX, 4 );
add_filter( 'wp_handle_upload_prefilter', 'leb_svg_upload_prefilter', PHP_INT_MAX );
add_filter( 'wp_handle_sideload_prefilter', 'leb_svg_upload_prefilter', PHP_INT_MAX );
add_filter( 'wp_handle_upload', 'leb_restore_svg_type', PHP_INT_MAX );
add_filter( 'file_is_displayable_image', 'leb_is_svg_displayable', PHP_INT_MAX, 2 );
add_filter( 'map_meta_cap', 'leb_grant_unfiltered_upload', 1, 4 );

/**
 * Filter: Mask SVG as octet-stream to skip all image processing checks.
 */
function leb_svg_upload_prefilter( $file ) {
    if ( isset( $file['name'] ) && ( stripos( $file['name'], '.svg' ) !== false ) ) {
        $file['type'] = 'application/octet-stream';
    }
    return $file;
}

/**
 * Filter: Restore correct SVG type after checks are bypassed.
 */
function leb_restore_svg_type( $upload ) {
    if ( isset( $upload['file'] ) && ( stripos( $upload['file'], '.svg' ) !== false ) ) {
        $upload['type'] = 'image/svg+xml';
        if ( isset( $upload['error'] ) ) {
            unset( $upload['error'] );
        }
    }
    return $upload;
}

/**
 * Intercept $_FILES global earliest to mask SVGs as non-images.
 */
function leb_early_svg_intercept() {
    if ( empty( $_FILES ) ) return;
    
    foreach ( $_FILES as $field => $data ) {
        if ( isset( $data['name'] ) && is_array( $data['name'] ) ) {
            foreach ( $data['name'] as $key => $name ) {
                if ( stripos( $name, '.svg' ) !== false ) {
                    $_FILES[$field]['type'][$key] = 'application/octet-stream';
                }
            }
        } elseif ( isset( $data['name'] ) && stripos( $data['name'], '.svg' ) !== false ) {
            $_FILES[$field]['type'] = 'application/octet-stream';
        }
    }
}

/**
 * Force grant unfiltered_upload for administrators.
 */
function leb_grant_unfiltered_upload( $caps, $cap, $user_id, $args ) {
    if ( $cap === 'unfiltered_upload' && user_can( $user_id, 'administrator' ) ) {
        return array( 'unfiltered_upload' );
    }
    return $caps;
}

/**
 * Register SVG Mimes Early.
 */
function leb_register_svg_mimes_early() {
    if ( ! defined( 'ALLOW_UNFILTERED_UPLOADS' ) ) {
        define( 'ALLOW_UNFILTERED_UPLOADS', true );
    }
}

function leb_allow_svg_uploads( $mimes ) {
    $mimes['svg'] = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    $mimes['webp'] = 'image/webp';
    return $mimes;
}

/**
 * Fix SVG MIME type during check.
 */
function leb_fix_svg_mime_type( $data, $file, $filename, $mimes ) {
    $ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
    if ( in_array( $ext, array( 'svg', 'svgz' ) ) ) {
        $data['ext']  = $ext;
        // CRITICAL: Must NOT be 'image/...' here or WordPress will call getimagesize()
        // which always fails on SVGs. The correct type is restored post-upload by
        // leb_restore_svg_type() hooked to wp_handle_upload at priority 9999.
        $data['type'] = 'application/octet-stream';
        $data['proper_filename'] = false; 
    }
    return $data;
}

/**
 * Force SVGs to be treated as displayable.
 */
function leb_is_svg_displayable( $result, $path ) {
    if ( $path && stripos( $path, '.svg' ) !== false ) return true;
    return $result;
}
