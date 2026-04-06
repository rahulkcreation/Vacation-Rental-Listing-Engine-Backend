/**
 * property-management.js
 *
 * Client-side logic for the Property Management Dashboard.
 * Handles: AJAX fetch + render, status-tab filtering, live search
 * with debounce, select-all / individual card checkboxes, bulk
 * actions (publish, draft, delete), pagination, and single-card
 * edit/delete actions.
 *
 * Prefix Convention: All DOM IDs and class hooks use "leb-pm" prefix.
 *
 * @package ListingEngineBackend
 */
(function () {
    'use strict';

    /* ─── State ─────────────────────────────────────────────── */
    const lebPmState = {
        page:        1,
        perPage:     10,
        search:      '',
        status:      'published', // Default to published instead of '' (All)
        selected:    new Set(),
    };

    /* ─── Cached DOM References ─────────────────────────────── */
    const DOM = {
        body:           document.getElementById('leb-pm-table-body'),
        loading:        document.getElementById('leb-pm-loading'),
        empty:          document.getElementById('leb-pm-empty-state'),
        table:          document.getElementById('leb-pm-table'),
        searchInput:    document.getElementById('leb-pm-search-input'),
        searchWrapper:  document.getElementById('leb-pm-search-bar-wrapper'),
        searchClearBtn: document.getElementById('leb-pm-search-clear-btn'),
        statusTabs:     document.getElementById('leb-pm-status-tabs'),
        selectAll:      document.getElementById('leb-pm-select-all'),
        bulkBar:        document.getElementById('leb-pm-bulk-bar'),
        selectedCount:  document.getElementById('leb-pm-selected-count'),
        paginationInfo: document.getElementById('leb-pm-pagination-info'),
        paginationCtrl: document.getElementById('leb-pm-pagination-controls'),
        // Tab counts
        countPublished: document.getElementById('leb-pm-count-published'),
        countDraft:     document.getElementById('leb-pm-count-draft'),
        countPending:   document.getElementById('leb-pm-count-pending'),
        countRejected:  document.getElementById('leb-pm-count-rejected'),
    };

    /* ─── Init ──────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        fetchListings();
        bindEvents();
    });

    /* ═══════════════════════════════════════════════════════════
     * AJAX FETCH
     * ═══════════════════════════════════════════════════════════ */
    function fetchListings() {
        showLoading(true);
        lebPmState.selected.clear();
        syncBulkBar();
        if (DOM.selectAll) DOM.selectAll.checked = false;

        jQuery.post(LEB_Ajax.ajax_url, {
            action:   'leb_listing_get_listings',
            nonce:    LEB_Ajax.nonce,
            search:   lebPmState.search,
            page:     lebPmState.page,
            per_page: lebPmState.perPage,
            status:   lebPmState.status,
        }, function (res) {
            showLoading(false);
            if (res.success) {
                renderCards(res.data.items || []);
                renderPagination(res.data);
                updateTabCounts(res.data.status_counts || {});
                
                // Show notification if no items found in search
                if (lebPmState.search && (!res.data.items || res.data.items.length === 0)) {
                    LEB_Toaster.info('No listings found for "' + lebPmState.search + '"');
                }
            } else {
                renderCards([]);
                LEB_Toaster.error((res.data && res.data.message) || 'Failed to load listings.');
            }
        }).fail(function () {
            showLoading(false);
            renderCards([]);
            LEB_Toaster.error('Network error. Please try again.');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * RENDER PROPERTY CARDS
     * ═══════════════════════════════════════════════════════════ */
    function renderCards(items) {
        if (!items.length) {
            DOM.body.innerHTML = '';
            DOM.table.style.display = 'none';
            DOM.empty.style.display = '';
            return;
        }

        DOM.table.style.display  = '';
        DOM.empty.style.display  = 'none';

        const editBase = lebPmState.editBaseUrl ||
            (window.location.href.split('?')[0] + '?page=leb-properties&leb_action=edit&id=');

        DOM.body.innerHTML = items.map(function (item, index) {
            // Thumbnail - item.first_image is now a URL string from backend
            const thumb = item.first_image
                ? '<img class="leb-pm-card-thumb" src="' + escHtml(item.first_image) + '" alt="" loading="lazy">'
                : '<div class="leb-pm-card-thumb" style="display:flex;align-items:center;justify-content:center;background:var(--leb-border-light);">'
                + '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>'
                + '</div>';

            const status = (item.status || 'draft').toLowerCase();
            const sNo    = ((lebPmState.page - 1) * lebPmState.perPage) + index + 1;

            return '<div class="leb-pm-card" data-id="' + item.id + '">'

                // Checkbox
                + '<input class="leb-pm-card-checkbox leb-pm-row-check" type="checkbox" value="' + item.id + '">'

                // Serial Number
                + '<div class="leb-pm-card-sno">' + sNo + '</div>'

                // Thumbnail
                + '<div class="leb-pm-card-thumb-wrap">' + thumb + '</div>'

                // Card Data Body
                + '<div class="leb-pm-card-body">'

                +   '<div class="leb-pm-card-row">'
                +     '<div class="leb-pm-card-label">Title</div>'
                +     '<div class="leb-pm-card-value">' + escHtml(item.title) + '</div>'
                +   '</div>'

                +   '<div class="leb-pm-card-row">'
                +     '<div class="leb-pm-card-label">Type</div>'
                +     '<div class="leb-pm-card-value">' + escHtml(item.type_name || '—') + '</div>'
                +   '</div>'

                +   '<div class="leb-pm-card-row">'
                +     '<div class="leb-pm-card-label">Price</div>'
                +     '<div class="leb-pm-card-value">₹' + Number(item.price || 0).toLocaleString('en-IN') + ' / Night</div>'
                +   '</div>'

                +   '<div class="leb-pm-card-row">'
                +     '<div class="leb-pm-card-label">Host</div>'
                +     '<div class="leb-pm-card-value">' + escHtml(item.username || '—') + '</div>'
                +   '</div>'

                +   '<div class="leb-pm-card-row">'
                +     '<div class="leb-pm-card-label">Status</div>'
                +     '<div class="leb-pm-card-value">'
                +       '<span class="leb-pm-status-badge leb-pm-status-badge--' + status + '">' + status + '</span>'
                +     '</div>'
                +   '</div>'

                + '</div>' // end .leb-pm-card-body

                // Action Buttons
                + '<div class="leb-pm-card-actions">'
                +   '<a href="' + editBase + item.id + '" class="leb-pm-action-btn">'
                +     '<svg class="leb-pm-action-btn-icon" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
                +     'Edit'
                +   '</a>'
                +   '<button class="leb-pm-action-btn leb-pm-action-btn--delete leb-pm-delete-btn" type="button" data-id="' + item.id + '">'
                +     '<svg class="leb-pm-action-btn-icon" viewBox="0 0 24 24" fill="none" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>'
                +     'Delete'
                +   '</button>'
                + '</div>'

                + '</div>'; // end .leb-pm-card
        }).join('');
    }

    /* ═══════════════════════════════════════════════════════════
     * PAGINATION
     * ═══════════════════════════════════════════════════════════ */
    function renderPagination(data) {
        const total      = parseInt(data.total, 10) || 0;
        const perPage    = lebPmState.perPage;
        const totalPages = Math.ceil(total / perPage) || 1;
        const page       = lebPmState.page;

        // Info text
        if (total > 0) {
            const start = ((page - 1) * perPage) + 1;
            const end   = Math.min(page * perPage, total);
            DOM.paginationInfo.textContent = 'Showing ' + start + '–' + end + ' of ' + total;
        } else {
            DOM.paginationInfo.textContent = '';
        }

        if (totalPages <= 1) {
            DOM.paginationCtrl.innerHTML = '';
            return;
        }

        let html = '';

        // Prev
        html += '<button class="leb-pm-page-btn" data-page="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>'
              + '<svg class="leb-pm-page-btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>'
              + '</button>';

        // Page numbers
        buildRange(page, totalPages).forEach(function (p) {
            if (p === '...') {
                html += '<span class="leb-pm-page-btn" style="pointer-events:none;border:none;background:transparent;">…</span>';
            } else {
                html += '<button class="leb-pm-page-btn' + (p === page ? ' leb-pm-page-btn--active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        // Next
        html += '<button class="leb-pm-page-btn" data-page="' + (page + 1) + '"' + (page >= totalPages ? ' disabled' : '') + '>'
              + '<svg class="leb-pm-page-btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>'
              + '</button>';

        DOM.paginationCtrl.innerHTML = html;
    }

    function buildRange(current, total) {
        if (total <= 7) {
            const arr = [];
            for (let i = 1; i <= total; i++) arr.push(i);
            return arr;
        }
        if (current <= 4)          return [1, 2, 3, 4, 5, '...', total];
        if (current >= total - 3)  return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    /* ═══════════════════════════════════════════════════════════
     * TAB COUNTS
     * ═══════════════════════════════════════════════════════════ */
    function updateTabCounts(counts) {
        if (DOM.countPublished) DOM.countPublished.textContent = counts.published || 0;
        if (DOM.countDraft)     DOM.countDraft.textContent     = counts.draft     || 0;
        if (DOM.countPending)   DOM.countPending.textContent   = counts.pending   || 0;
        if (DOM.countRejected)  DOM.countRejected.textContent  = counts.rejected  || 0;
    }

    /* ═══════════════════════════════════════════════════════════
     * EVENTS
     * ═══════════════════════════════════════════════════════════ */
    function bindEvents() {

        // ── Status Tabs ──────────────────────────────────────
        if (DOM.statusTabs) {
            DOM.statusTabs.addEventListener('click', function (e) {
                const tab = e.target.closest('.leb-pm-tab');
                if (!tab) return;
                DOM.statusTabs.querySelectorAll('.leb-pm-tab').forEach(function (t) {
                    t.classList.remove('leb-pm-tab--active');
                });
                tab.classList.add('leb-pm-tab--active');
                lebPmState.status = tab.dataset.status || 'published';
                lebPmState.page   = 1;
                fetchListings();
            });
        }

        // ── Search (debounced) ────────────────────────────────
        let searchTimer;

        function updateSearchClearBtn() {
            if (!DOM.searchClearBtn) return;
            if (DOM.searchInput.value.length > 0) {
                DOM.searchClearBtn.classList.add('leb-pm-search-clear-visible');
            } else {
                DOM.searchClearBtn.classList.remove('leb-pm-search-clear-visible');
            }
        }

        if (DOM.searchInput) {
            DOM.searchInput.addEventListener('input', function () {
                updateSearchClearBtn();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    lebPmState.search = DOM.searchInput.value.trim();
                    lebPmState.page   = 1;
                    fetchListings();
                }, 350);
            });

            DOM.searchInput.addEventListener('focus', function () {
                if (DOM.searchWrapper) DOM.searchWrapper.classList.add('leb-pm-search-focused');
            });

            DOM.searchInput.addEventListener('blur', function () {
                if (DOM.searchWrapper) DOM.searchWrapper.classList.remove('leb-pm-search-focused');
            });
        }

        if (DOM.searchClearBtn) {
            DOM.searchClearBtn.addEventListener('click', function () {
                DOM.searchInput.value = '';
                updateSearchClearBtn();
                lebPmState.search = '';
                lebPmState.page   = 1;
                fetchListings();
            });
        }

        // ── Select All ────────────────────────────────────────
        if (DOM.selectAll) {
            DOM.selectAll.addEventListener('change', function () {
                const checkboxes = DOM.body.querySelectorAll('.leb-pm-row-check');
                checkboxes.forEach(function (cb) {
                    cb.checked = DOM.selectAll.checked;
                    const card = cb.closest('.leb-pm-card');
                    if (cb.checked) {
                        lebPmState.selected.add(parseInt(cb.value, 10));
                        if (card) card.classList.add('leb-pm-card--selected');
                    } else {
                        lebPmState.selected.delete(parseInt(cb.value, 10));
                        if (card) card.classList.remove('leb-pm-card--selected');
                    }
                });
                syncBulkBar();
            });
        }

        // ── Individual Card Checkbox ──────────────────────────
        if (DOM.body) {
            DOM.body.addEventListener('change', function (e) {
                if (!e.target.classList.contains('leb-pm-row-check')) return;
                const id   = parseInt(e.target.value, 10);
                const card = e.target.closest('.leb-pm-card');
                if (e.target.checked) {
                    lebPmState.selected.add(id);
                    if (card) card.classList.add('leb-pm-card--selected');
                } else {
                    lebPmState.selected.delete(id);
                    if (card) card.classList.remove('leb-pm-card--selected');
                }
                syncBulkBar();
                updateSelectAllState();
            });

            // ── Single Delete ─────────────────────────────────
            DOM.body.addEventListener('click', function (e) {
                const btn = e.target.closest('.leb-pm-delete-btn');
                if (!btn) return;
                const id = parseInt(btn.dataset.id, 10);
                if (!id) return;

                LEB_Confirm.show('Are you sure you want to delete this property?', function () {
                    // Corrected action: leb_listing_delete_listing (singular)
                    performAJAXAction('leb_listing_delete_listing', [id], 'Property deleted successfully.', function () {
                        if (DOM.body.children.length === 1 && lebPmState.page > 1) {
                            lebPmState.page--;
                        }
                        fetchListings();
                    });
                });
            });
        }

        // ── Pagination ────────────────────────────────────────
        if (DOM.paginationCtrl) {
            DOM.paginationCtrl.addEventListener('click', function (e) {
                const btn = e.target.closest('.leb-pm-page-btn');
                if (!btn || btn.disabled) return;
                const p = parseInt(btn.dataset.page, 10);
                if (p && p !== lebPmState.page) {
                    lebPmState.page = p;
                    fetchListings();
                }
            });
        }

        // ── Bulk Actions ──────────────────────────────────────
        const bulkPublish = document.getElementById('leb-pm-bulk-publish');
        const bulkDraft   = document.getElementById('leb-pm-bulk-draft');
        const bulkDelete  = document.getElementById('leb-pm-bulk-delete');

        if (bulkPublish) bulkPublish.addEventListener('click', function () { doBulk('published'); });
        if (bulkDraft)   bulkDraft.addEventListener('click',   function () { doBulk('draft'); });
        if (bulkDelete)  bulkDelete.addEventListener('click',  function () { doBulk('delete'); });
    }

    function doBulk(actionType) {
        if (!lebPmState.selected.size) return;
        const ids = Array.from(lebPmState.selected);
        let action     = '';
        let confirmMsg = '';
        let successMsg = '';

        if (actionType === 'delete') {
            // Corrected action: leb_listing_bulk_delete
            action     = 'leb_listing_bulk_delete';
            confirmMsg = 'Delete ' + ids.length + ' selected propert' + (ids.length > 1 ? 'ies' : 'y') + '?';
            successMsg = 'Properties deleted.';
        } else {
            action     = 'leb_listing_bulk_status';
            confirmMsg = 'Mark ' + ids.length + ' propert' + (ids.length > 1 ? 'ies' : 'y') + ' as ' + actionType + '?';
            successMsg = 'Properties updated.';
        }

        LEB_Confirm.show(confirmMsg, function () {
            performAJAXAction(action, ids, successMsg, function () {
                lebPmState.selected.clear();
                if (actionType === 'delete' && DOM.body.children.length <= ids.length && lebPmState.page > 1) {
                    lebPmState.page--;
                }
                fetchListings();
            }, (actionType !== 'delete' ? actionType : null));
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * UTILITIES
     * ═══════════════════════════════════════════════════════════ */

    /** Show or hide the loading spinner and swap visibility states. */
    function showLoading(show) {
        if (DOM.loading) DOM.loading.style.display = show ? '' : 'none';
        if (show) {
            if (DOM.table) DOM.table.style.display = 'none';
            if (DOM.empty) DOM.empty.style.display = 'none';
        }
    }

    /** Show/hide the bulk action bar and update the count label. */
    function syncBulkBar() {
        const count = lebPmState.selected.size;
        if (DOM.selectedCount) DOM.selectedCount.textContent = count;
        if (DOM.bulkBar) DOM.bulkBar.style.display = count > 0 ? 'flex' : 'none';
        if (count === 0 && DOM.selectAll) DOM.selectAll.checked = false;
    }

    /** Keep the Select-All checkbox in sync with individual card checkboxes. */
    function updateSelectAllState() {
        if (!DOM.selectAll) return;
        const checkboxes = DOM.body.querySelectorAll('.leb-pm-row-check');
        if (!checkboxes.length) {
            DOM.selectAll.checked = false;
            return;
        }
        let allChecked = true;
        checkboxes.forEach(function (cb) { if (!cb.checked) allChecked = false; });
        DOM.selectAll.checked = allChecked;
    }

    /**
     * Generic AJAX action helper for bulk publish / draft / delete.
     *
     * @param {string}   action     WordPress AJAX action name.
     * @param {number[]} ids        Array of listing IDs.
     * @param {string}   successMsg Message to display on success.
     * @param {Function} onSuccess  Callback executed after success.
     * @param {string}   [newStatus] Optional status for bulk-status actions.
     */
    function performAJAXAction(action, ids, successMsg, onSuccess, newStatus) {
        const data = { action: action, nonce: LEB_Ajax.nonce, ids: ids };
        if (newStatus) data.status = newStatus;

        jQuery.post(LEB_Ajax.ajax_url, data, function (res) {
            if (res.success) {
                LEB_Toaster.success(successMsg);
                if (typeof onSuccess === 'function') onSuccess();
            } else {
                LEB_Toaster.error((res.data && res.data.message) || 'Action failed.');
            }
        }).fail(function () {
            LEB_Toaster.error('Network error during action.');
        });
    }

    /** Minimal XSS-safe HTML escape. */
    const escMap = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (s) { return escMap[s]; });
    }

})();
