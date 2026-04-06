<?php
/**
 * Property Management – Main Listing Dashboard
 *
 * Renders the admin list view for property listings.
 * Features: status-filter tabs, live search, bulk actions bar,
 * responsive data table, and AJAX-powered pagination.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Build the "Add New" link programmatically.
$add_new_url = admin_url( 'admin.php?page=leb-properties&leb_action=add' );
?>

<div class="leb-prop-mgmt-wrap">

    <!-- ─── Page Header ──────────────────────────────────────── -->
    <div class="leb-prop-mgmt-header">
        <div class="leb-prop-mgmt-header__left">
            <h1 class="leb-prop-mgmt-title">Property Management</h1>
            <p class="leb-prop-mgmt-subtitle">Create, manage, and organise your vacation rental listings.</p>
        </div>
        <div class="leb-prop-mgmt-header__right">
            <a href="<?php echo esc_url( $add_new_url ); ?>" class="leb-prop-mgmt-btn leb-prop-mgmt-btn--primary" id="leb-prop-add-new-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add New Property
            </a>
        </div>
    </div>

    <!-- ─── Toolbar: Tabs + Search ───────────────────────────── -->
    <div class="leb-prop-mgmt-toolbar">
        <div class="leb-prop-mgmt-tabs" id="leb-prop-status-tabs">
            <button class="leb-prop-mgmt-tab leb-prop-mgmt-tab--active" data-status="">All <span class="leb-prop-mgmt-tab__count" id="leb-prop-count-all">0</span></button>
            <button class="leb-prop-mgmt-tab" data-status="published">Published <span class="leb-prop-mgmt-tab__count" id="leb-prop-count-published">0</span></button>
            <button class="leb-prop-mgmt-tab" data-status="draft">Draft <span class="leb-prop-mgmt-tab__count" id="leb-prop-count-draft">0</span></button>
            <button class="leb-prop-mgmt-tab" data-status="pending">Pending <span class="leb-prop-mgmt-tab__count" id="leb-prop-count-pending">0</span></button>
            <button class="leb-prop-mgmt-tab" data-status="rejected">Rejected <span class="leb-prop-mgmt-tab__count" id="leb-prop-count-rejected">0</span></button>
        </div>
        <div class="leb-prop-mgmt-search">
            <svg class="leb-prop-mgmt-search__icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" class="leb-prop-mgmt-search__input" id="leb-prop-search-input" placeholder="Search by title or host…" autocomplete="off">
        </div>
    </div>

    <!-- ─── Bulk Actions Bar (hidden until items selected) ──── -->
    <div class="leb-prop-mgmt-bulk-bar" id="leb-prop-bulk-bar" style="display:none;">
        <span class="leb-prop-mgmt-bulk-bar__count"><span id="leb-prop-selected-count">0</span> selected</span>
        <div class="leb-prop-mgmt-bulk-bar__actions">
            <button class="leb-prop-mgmt-btn leb-prop-mgmt-btn--sm leb-prop-mgmt-btn--outline" id="leb-prop-bulk-publish" title="Publish Selected">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                Publish
            </button>
            <button class="leb-prop-mgmt-btn leb-prop-mgmt-btn--sm leb-prop-mgmt-btn--outline" id="leb-prop-bulk-draft" title="Set as Draft">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                Draft
            </button>
            <button class="leb-prop-mgmt-btn leb-prop-mgmt-btn--sm leb-prop-mgmt-btn--danger" id="leb-prop-bulk-delete" title="Delete Selected">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                Delete
            </button>
        </div>
    </div>

    <!-- ─── Data Table ───────────────────────────────────────── -->
    <div class="leb-prop-mgmt-table-wrap">
        <table class="leb-prop-mgmt-table" id="leb-prop-table">
            <thead>
                <tr>
                    <th class="leb-prop-mgmt-table__th leb-prop-mgmt-table__th--check">
                        <input type="checkbox" id="leb-prop-select-all" class="leb-prop-mgmt-checkbox" title="Select all">
                    </th>
                    <th class="leb-prop-mgmt-table__th">Property</th>
                    <th class="leb-prop-mgmt-table__th">Type</th>
                    <th class="leb-prop-mgmt-table__th">Location</th>
                    <th class="leb-prop-mgmt-table__th">Price</th>
                    <th class="leb-prop-mgmt-table__th">Host</th>
                    <th class="leb-prop-mgmt-table__th">Status</th>
                    <th class="leb-prop-mgmt-table__th leb-prop-mgmt-table__th--actions">Actions</th>
                </tr>
            </thead>
            <tbody id="leb-prop-table-body">
                <!-- Rows injected by JS -->
            </tbody>
        </table>

        <!-- Empty state (shown via JS) -->
        <div class="leb-prop-mgmt-empty" id="leb-prop-empty-state" style="display:none;">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="var(--leb-text-muted)" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
            <h3>No properties found</h3>
            <p>Try adjusting your search or filter, or add a new property.</p>
        </div>

        <!-- Loading skeleton (shown via JS) -->
        <div class="leb-prop-mgmt-loading" id="leb-prop-loading" style="display:none;">
            <div class="leb-prop-mgmt-spinner"></div>
            <span>Loading properties…</span>
        </div>
    </div>

    <!-- ─── Pagination ───────────────────────────────────────── -->
    <div class="leb-prop-mgmt-pagination" id="leb-prop-pagination">
        <div class="leb-prop-mgmt-pagination__info" id="leb-prop-pagination-info">
            <!-- e.g. "Showing 1–10 of 42" -->
        </div>
        <div class="leb-prop-mgmt-pagination__controls" id="leb-prop-pagination-controls">
            <!-- Page buttons injected by JS -->
        </div>
    </div>

</div>
