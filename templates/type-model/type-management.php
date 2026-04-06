<?php
/**
 * type-management.php
 *
 * Types List view – search, mobile-card table, and pagination.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="leb-type-list" class="leb-wrap">

    <!-- ── Page Header ──────────────────────────────────── -->
    <div class="leb-tl-header">
        <div class="leb-tl-header-left">
            <div class="leb-tl-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"/>
                    <path d="M2 22v-4a2 2 0 0 1 2-2h2"/>
                    <path d="M18 16h2a2 2 0 0 1 2 2v4"/>
                    <line x1="10" y1="6"  x2="10" y2="6.01"/>
                    <line x1="14" y1="6"  x2="14" y2="6.01"/>
                    <line x1="10" y1="10" x2="10" y2="10.01"/>
                    <line x1="14" y1="10" x2="14" y2="10.01"/>
                    <line x1="10" y1="14" x2="10" y2="14.01"/>
                    <line x1="14" y1="14" x2="14" y2="14.01"/>
                    <line x1="2"  y1="22" x2="22" y2="22"/>
                </svg>
            </div>
            <h1 class="leb-tl-page-title"><?php esc_html_e( 'Manage Types', 'listing-engine-backend' ); ?></h1>
        </div>

        <div class="leb-tl-header-right">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=leb-types&leb_action=add' ) ); ?>"
               class="leb-tl-add-btn"
               id="leb-add-new-type-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="12" y1="5" x2="12" y2="19"/>
                    <line x1="5"  y1="12" x2="19" y2="12"/>
                </svg>
                <?php esc_html_e( 'Add New Type', 'listing-engine-backend' ); ?>
            </a>
        </div>
    </div>

    <!-- ── Search Bar ──────────────────────────────────────── -->
    <div class="leb-tl-search-wrap" id="leb-tl-search-wrap">
        <svg class="leb-tl-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="11" cy="11" r="8"/>
            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>

        <input
            type="text"
            class="leb-tl-search-input"
            id="leb-tl-search-input"
            placeholder="<?php esc_attr_e( 'Search types by name or slug…', 'listing-engine-backend' ); ?>"
            autocomplete="off"
            aria-label="<?php esc_attr_e( 'Search types', 'listing-engine-backend' ); ?>"
        >

        <button class="leb-tl-search-clear" id="leb-tl-search-clear" aria-label="<?php esc_attr_e( 'Clear search', 'listing-engine-backend' ); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="18" y1="6"  x2="6"  y2="18"/>
                <line x1="6"  y1="6"  x2="18" y2="18"/>
            </svg>
        </button>
    </div>

    <!-- ── Data Table / Card List ──────────────────────────── -->
    <div class="leb-tl-table-wrap" id="leb-tl-table-wrap">

        <!-- Bulk Actions Bar -->
        <div id="leb-tl-bulk-actions" class="leb-tl-bulk-actions">
            <div class="leb-tl-bulk-info">
                <input type="checkbox" id="leb-tl-select-all" class="leb-tl-checkbox">
                <span id="leb-tl-selected-count">0 selected</span>
            </div>
            <button type="button" class="leb-tl-bulk-delete-btn" onclick="lebBulkDelete()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/></svg>
                Delete Selected
            </button>
        </div>

        <!-- Cards are injected here by JS -->
        <div class="leb-tl-cards-list" id="leb-tl-cards-list"
             aria-live="polite" aria-label="<?php esc_attr_e( 'Types list', 'listing-engine-backend' ); ?>">
        </div>

        <!-- Pagination Bar -->
        <div class="leb-tl-pagination" id="leb-tl-pagination">
            <span class="leb-tl-pagination-text" id="leb-tl-pagination-text"></span>
            <div class="leb-tl-page-controls" id="leb-tl-page-controls"></div>
        </div>

    </div><!-- /.leb-tl-table-wrap -->

</div><!-- /#leb-type-list -->

<script>
window.lebTypeMgmtCfg = {
    editUrlBase: '<?php echo esc_js( admin_url( 'admin.php?page=leb-types&leb_action=edit&id=' ) ); ?>'
};
</script>
