<?php
/**
 * amenity-management.php
 *
 * Amenities List view – search, card table with SVG preview, and pagination.
 * Prefix convention: all HTML IDs and CSS classes use "leb-amen" or "leb-am-".
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="leb-amen-list" class="leb-wrap">

    <!-- ── Page Header ──────────────────────────────────────── -->
    <div class="leb-am-header">
        <div class="leb-am-header-left">
            <div class="leb-am-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>
                </svg>
            </div>
            <h1 class="leb-am-page-title"><?php esc_html_e( 'Manage Amenities', 'listing-engine-backend' ); ?></h1>
        </div>

        <div class="leb-am-header-right">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=leb-amenities&leb_action=add' ) ); ?>"
               class="leb-am-add-btn"
               id="leb-amen-add-new-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5"  y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Add New Amenity', 'listing-engine-backend' ); ?>
            </a>
        </div>
    </div>

    <!-- ── Search Bar ────────────────────────────────────────── -->
    <div class="leb-am-search-wrap" id="leb-amen-search-wrap">
        <svg class="leb-am-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>

        <input
            type="text"
            class="leb-am-search-input"
            id="leb-amen-search-input"
            placeholder="<?php esc_attr_e( 'Search amenities by name…', 'listing-engine-backend' ); ?>"
            autocomplete="off"
            aria-label="<?php esc_attr_e( 'Search amenities', 'listing-engine-backend' ); ?>"
        >

        <button class="leb-am-search-clear" id="leb-amen-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'listing-engine-backend' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6"  x2="6"  y2="18"/>
                <line x1="6"  y1="6"  x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- ── Data Table / Card List ────────────────────────────── -->
    <div class="leb-am-table-wrap" id="leb-amen-table-wrap">

        <!-- Bulk Actions Bar -->
        <div id="leb-amen-bulk-actions" class="leb-am-bulk-actions">
            <div class="leb-am-bulk-info">
                <input type="checkbox" id="leb-amen-select-all" class="leb-am-checkbox">
                <span id="leb-amen-selected-count">0 selected</span>
            </div>
            <button type="button" class="leb-am-bulk-delete-btn" onclick="lebAmenBulkDelete()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                Delete Selected
            </button>
        </div>

        <!-- Cards are injected here by JS -->
        <div class="leb-am-cards-list" id="leb-amen-cards-list"
             aria-live="polite" aria-label="<?php esc_attr_e( 'Amenities list', 'listing-engine-backend' ); ?>">
        </div>

        <!-- Pagination Bar -->
        <div class="leb-am-pagination" id="leb-amen-pagination">
            <span class="leb-am-pagination-text" id="leb-amen-pagination-text"></span>
            <div class="leb-am-page-controls" id="leb-amen-page-controls"></div>
        </div>

    </div><!-- /.leb-am-table-wrap -->

</div><!-- /#leb-amen-list -->

<script>
window.lebAmenMgmtCfg = {
    editUrlBase: '<?php echo esc_js( admin_url( 'admin.php?page=leb-amenities&leb_action=edit&id=' ) ); ?>',
    defaultSvgPath: '<?php echo esc_url( LEB_PLUGIN_URL . "assets/images/default-amenity.svg" ); ?>'
};
</script>
