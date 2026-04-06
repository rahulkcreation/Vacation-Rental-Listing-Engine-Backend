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

<div class="leb-pl-bg-decoration">
    <div class="leb-pl-bg-orb-1"></div>
    <div class="leb-pl-bg-orb-2"></div>
</div>

<div class="leb-pl-main-container">

    <div class="leb-pl-header-section">
        <div class="leb-pl-header-top">
            <div class="leb-pl-header-left">
                <div class="leb-pl-header-icon-box">
                    <svg class="leb-pl-header-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18"></path>
                        <path d="M2 22v-4a2 2 0 0 1 2-2h2"></path>
                        <path d="M18 16h2a2 2 0 0 1 2 2v4"></path>
                        <line x1="10" y1="6" x2="10" y2="6.01"></line>
                        <line x1="14" y1="6" x2="14" y2="6.01"></line>
                        <line x1="10" y1="10" x2="10" y2="10.01"></line>
                        <line x1="14" y1="10" x2="14" y2="10.01"></line>
                        <line x1="10" y1="14" x2="10" y2="14.01"></line>
                        <line x1="14" y1="14" x2="14" y2="14.01"></line>
                        <line x1="2" y1="22" x2="22" y2="22"></line>
                    </svg>
                </div>
                <h1 class="leb-pl-page-title">Manage Property</h1>
            </div>

            <div class="leb-pl-header-right">
                <a href="<?php echo esc_url( $add_new_url ); ?>" class="add-type-btn">Add New Property</a>
            </div>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="leb-pl-bulk-actions leb-pl-bulk-bar" id="leb-prop-bulk-bar" style="display:none;">
        <span class="leb-pl-bulk-count"><span id="leb-prop-selected-count">0</span> selected</span>
        <div class="leb-pl-bulk-btn-group">
            <button class="leb-pl-data-btn leb-pl-btn-publish" id="leb-prop-bulk-publish">Publish</button>
            <button class="leb-pl-data-btn leb-pl-btn-draft" id="leb-prop-bulk-draft">Draft</button>
            <button class="leb-pl-data-btn leb-pl-btn-delete" id="leb-prop-bulk-delete">Delete</button>
        </div>
    </div>

    <div class="leb-pl-search-section">
        <div class="leb-pl-search-bar-wrapper" id="leb-pl-search-bar-wrapper">
            <span class="leb-pl-search-icon">
                <svg class="leb-pl-search-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </span>
            <input type="text" class="leb-pl-search-input" placeholder="Search by title or host…" id="leb-prop-search-input" autocomplete="off">
            <button class="leb-pl-search-clear-btn" id="leb-pl-search-clear-btn">
                <svg class="leb-pl-search-clear-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>
    </div>

    <div class="leb-pl-status" id="leb-prop-status-tabs">
        <div class="status active" data-status="">All <span id="leb-prop-count-all">0</span></div>
        <div class="status" data-status="published">Published <span id="leb-prop-count-published">0</span></div>
        <div class="status" data-status="pending">Pending <span id="leb-prop-count-pending">0</span></div>
        <div class="status" data-status="draft">Draft <span id="leb-prop-count-draft">0</span></div>
        <div class="status" data-status="rejected">Rejected <span id="leb-prop-count-rejected">0</span></div>
    </div>

    <div class="leb-prop-state-box" id="leb-prop-loading" style="display:none;">
        <p class="leb-prop-state-text">Loading properties...</p>
    </div>

    <div class="leb-prop-state-box leb-prop-empty-state" id="leb-prop-empty-state" style="display:none;">
        <svg class="leb-prop-empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        <h3 class="leb-prop-empty-title">No properties found</h3>
        <p class="leb-prop-state-text">Try adjusting your search or filter, or add a new property.</p>
    </div>

    <div class="leb-pl-table-section" id="leb-prop-table">
        <div class="leb-pl-table-scroll">
            <div class="leb-pl-table-select-all-bar">
                <input type="checkbox" id="leb-prop-select-all" class="leb-prop-select-all-cb" title="Select all"> 
                <label for="leb-prop-select-all" class="leb-pl-data-label leb-prop-select-all-label">Select All</label>
            </div>
            <div class="leb-pl-table-mobile" id="leb-prop-table-body">
                <!-- Rows injected by JS -->
            </div>
        </div>

        <div class="leb-pl-pagination-bar" id="leb-prop-pagination">
            <span class="leb-pl-pagination-text" id="leb-prop-pagination-info">Showing 0-0 of 0</span>
            <div class="leb-pl-pagination-controls" id="leb-prop-pagination-controls">
                <!-- Page buttons injected by JS -->
            </div>
        </div>
    </div>

</div>
