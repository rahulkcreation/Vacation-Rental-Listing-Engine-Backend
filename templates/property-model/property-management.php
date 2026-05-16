<?php

/**
 * Property Management – Main Listing Dashboard
 *
 * Renders the admin list view for property listings.
 * Features: status-filter tabs, live search, bulk actions bar,
 * responsive card grid, and AJAX-powered pagination.
 *
 * Prefix Convention: All classes and IDs use "leb-pm" prefix.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

// Build the "Add New" link programmatically.
$add_new_url = admin_url('admin.php?page=leb-properties&leb_action=add');
?>
<div class="wrap">
    <!-- This hidden h2 and the empty notice container catch WordPress admin notices before they get moved into our custom header. -->
    <h2 class="leb-admin-notice-placeholder"></h2>
    <div class="leb-global-plugin-wrapper">

        <!-- ══════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════ -->
        <div class="leb-pm-header-section">
            <div class="leb-pm-header-top">
                <div class="leb-pm-header-left">
                    <div class="leb-pm-header-icon-box">
                        <svg class="leb-pm-header-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
                    <h1 class="leb-pm-page-title">Manage Properties</h1>
                </div>

                <div class="leb-pm-header-right">
                    <a href="<?php echo esc_url($add_new_url); ?>" class="leb-pm-add-btn" id="leb-pm-add-new-btn">
                        <svg class="leb-pm-add-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add New Property
                    </a>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
         BULK ACTIONS BAR
    ══════════════════════════════════════════ -->
        <div class="leb-pm-bulk-bar" id="leb-pm-bulk-bar" style="display:none;">
            <span class="leb-pm-bulk-count">
                <span id="leb-pm-selected-count">0</span> selected
            </span>
            <div class="leb-pm-bulk-btn-group">
                <button class="leb-pm-bulk-btn leb-pm-bulk-btn--publish" id="leb-pm-bulk-publish">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    Publish
                </button>
                <button class="leb-pm-bulk-btn leb-pm-bulk-btn--draft" id="leb-pm-bulk-draft">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Draft
                </button>
                <button class="leb-pm-bulk-btn leb-pm-bulk-btn--delete" id="leb-pm-bulk-delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                    Delete
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
         SEARCH BAR
    ══════════════════════════════════════════ -->
        <div class="leb-pm-search-section">
            <div class="leb-pm-search-bar-wrapper" id="leb-pm-search-bar-wrapper">
                <span class="leb-pm-search-icon">
                    <svg class="leb-pm-search-icon-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </span>
                <input
                    type="text"
                    class="leb-pm-search-input"
                    placeholder="Search by title or host…"
                    id="leb-pm-search-input"
                    autocomplete="off">
                <button class="leb-pm-search-clear-btn" id="leb-pm-search-clear-btn" type="button">
                    <svg class="leb-pm-search-clear-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
         STATUS TABS
    ══════════════════════════════════════════ -->
        <div class="leb-pm-status-tabs" id="leb-pm-status-tabs">
            <div class="leb-pm-tab leb-pm-tab--active" data-status="published">
                Published <span id="leb-pm-count-published">0</span>
            </div>
            <div class="leb-pm-tab" data-status="pending">
                Pending <span id="leb-pm-count-pending">0</span>
            </div>
            <div class="leb-pm-tab" data-status="draft">
                Draft <span id="leb-pm-count-draft">0</span>
            </div>
            <div class="leb-pm-tab" data-status="rejected">
                Rejected <span id="leb-pm-count-rejected">0</span>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
         LOADING STATE
    ══════════════════════════════════════════ -->
        <div class="leb-pm-state-box" id="leb-pm-loading" style="display:none;">
            <div class="leb-pm-spinner"></div>
            <p class="leb-pm-state-text">Loading properties…</p>
        </div>

        <!-- ══════════════════════════════════════════
         EMPTY STATE
    ══════════════════════════════════════════ -->
        <div class="leb-pm-state-box leb-pm-empty-state" id="leb-pm-empty-state" style="display:none;">
            <svg class="leb-pm-empty-icon" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
                <line x1="8" y1="21" x2="16" y2="21" />
                <line x1="12" y1="17" x2="12" y2="21" />
            </svg>
            <h3 class="leb-pm-empty-title">No properties found</h3>
            <p class="leb-pm-state-text">Try adjusting your search or filter, or add a new property.</p>
        </div>

        <!-- ══════════════════════════════════════════
         TABLE SECTION
    ══════════════════════════════════════════ -->
        <div class="leb-pm-table-section" id="leb-pm-table">
            <div class="leb-pm-table-scroll">

                <!-- Select All Bar -->
                <div class="leb-pm-select-all-bar">
                    <label class="leb-pm-select-all-label">
                        <input type="checkbox" id="leb-pm-select-all" class="leb-pm-select-all-cb">
                        <span>Select All</span>
                    </label>
                </div>

                <!-- Card Grid (rows injected by JS) -->
                <div class="leb-pm-card-grid" id="leb-pm-table-body">
                    <!-- Rows injected by JS -->
                </div>
            </div>

            <!-- Pagination -->
            <div class="leb-pm-pagination-bar" id="leb-pm-pagination">
                <span class="leb-pm-pagination-text" id="leb-pm-pagination-info">Showing 0–0 of 0</span>
                <div class="leb-pm-pagination-controls" id="leb-pm-pagination-controls">
                    <!-- Page buttons injected by JS -->
                </div>
            </div>
        </div>

    </div>
</div>