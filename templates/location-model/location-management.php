<?php
/**
 * location-management.php
 *
 * Locations List view – search, card table with SVG preview, slug, and pagination.
 * Prefix convention: all HTML IDs and CSS classes use "leb-loc-".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <!-- This hidden h2 and the empty notice container catch WordPress admin notices before they get moved into our custom header. -->
    <h2 class="leb-admin-notice-placeholder"></h2>
    <div id="leb-loc-list" class="leb-global-plugin-wrapper">

    <!-- ── Page Header ──────────────────────────────────────── -->
    <div class="leb-loc-header">
        <div class="leb-loc-header-left">
            <div class="leb-loc-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
            <h1 class="leb-loc-page-title"><?php esc_html_e( 'Manage Locations', 'listing-engine-backend' ); ?></h1>
        </div>

        <div class="leb-loc-header-right">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=leb-locations&leb_action=add' ) ); ?>"
               class="leb-loc-add-btn"
               id="leb-loc-add-new-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5"  y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Add New Location', 'listing-engine-backend' ); ?>
            </a>
        </div>
    </div>

    <!-- ── Search Bar ────────────────────────────────────────── -->
    <div class="leb-loc-search-wrap" id="leb-loc-search-wrap">
        <svg class="leb-loc-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>

        <input
            type="text"
            class="leb-loc-search-input"
            id="leb-loc-search-input"
            placeholder="<?php esc_attr_e( 'Search locations by name…', 'listing-engine-backend' ); ?>"
            autocomplete="off"
            aria-label="<?php esc_attr_e( 'Search locations', 'listing-engine-backend' ); ?>"
        >

        <button class="leb-loc-search-clear" id="leb-loc-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'listing-engine-backend' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6"  x2="6"  y2="18"/>
                <line x1="6"  y1="6"  x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- ── Data Table / Card List ────────────────────────────── -->
    <div class="leb-loc-table-wrap" id="leb-loc-table-wrap">

        <!-- Bulk Actions Bar -->
        <div id="leb-loc-bulk-actions" class="leb-loc-bulk-actions">
            <div class="leb-loc-bulk-info">
                <input type="checkbox" id="leb-loc-select-all" class="leb-loc-checkbox">
                <span id="leb-loc-selected-count">0 selected</span>
            </div>
            <button type="button" class="leb-loc-bulk-delete-btn" onclick="lebLocBulkDelete()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                Delete Selected
            </button>
        </div>

        <!-- Cards are injected here by JS -->
        <div class="leb-loc-cards-list" id="leb-loc-cards-list"
             aria-live="polite" aria-label="<?php esc_attr_e( 'Locations list', 'listing-engine-backend' ); ?>">
        </div>

        <!-- Pagination Bar -->
        <div class="leb-loc-pagination" id="leb-loc-pagination">
            <span class="leb-loc-pagination-text" id="leb-loc-pagination-text"></span>
            <div class="leb-loc-page-controls" id="leb-loc-page-controls"></div>
        </div>

    </div><!-- /.leb-loc-table-wrap -->

</div><!-- /#leb-loc-list -->


</div>
