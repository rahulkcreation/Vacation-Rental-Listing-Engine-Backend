/**
 * property-management.js
 *
 * Client-side logic for the Property Listing Dashboard.
 * Handles: AJAX fetch + render, status-tab filtering, live search
 * with debounce, select-all / individual row checkboxes, bulk
 * actions (publish, draft, delete), pagination, and single-row
 * edit/delete actions.
 *
 * @package ListingEngineBackend
 */
(function () {
    'use strict';

    /* ─── State ────────────────────────────────────────────── */
    const lebState = {
        page:     1,
        perPage:  10,
        search:   '',
        status:   '',       // '' = All
        selected: new Set(),
    };

    /* ─── Cached DOM References ────────────────────────────── */
    const DOM = {
        body:           document.getElementById('leb-prop-table-body'),
        loading:        document.getElementById('leb-prop-loading'),
        empty:          document.getElementById('leb-prop-empty-state'),
        table:          document.getElementById('leb-prop-table'),
        searchInput:    document.getElementById('leb-prop-search-input'),
        statusTabs:     document.getElementById('leb-prop-status-tabs'),
        selectAll:      document.getElementById('leb-prop-select-all'),
        bulkBar:        document.getElementById('leb-prop-bulk-bar'),
        selectedCount:  document.getElementById('leb-prop-selected-count'),
        paginationInfo: document.getElementById('leb-prop-pagination-info'),
        paginationCtrl: document.getElementById('leb-prop-pagination-controls'),
        // Tab counts
        countAll:       document.getElementById('leb-prop-count-all'),
        countPublished: document.getElementById('leb-prop-count-published'),
        countDraft:     document.getElementById('leb-prop-count-draft'),
        countPending:   document.getElementById('leb-prop-count-pending'),
        countRejected:  document.getElementById('leb-prop-count-rejected'),
    };

    /* ─── Init ─────────────────────────────────────────────── */
    document.addEventListener('DOMContentLoaded', function () {
        fetchListings();
        bindEvents();
    });

    /* ═══════════════════════════════════════════════════════════
     * AJAX FETCH
     * ═══════════════════════════════════════════════════════════ */
    function fetchListings() {
        showLoading(true);
        lebState.selected.clear();
        syncBulkBar();
        if (DOM.selectAll) DOM.selectAll.checked = false;

        jQuery.post(LEB_Ajax.ajax_url, {
            action:   'leb_listing_get_listings',
            nonce:    LEB_Ajax.nonce,
            search:   lebState.search,
            page:     lebState.page,
            per_page: lebState.perPage,
            status:   lebState.status,
        }, function (res) {
            showLoading(false);
            if (res.success) {
                renderRows(res.data.items || []);
                renderPagination(res.data);
                updateTabCounts(res.data.status_counts || {});
            } else {
                renderRows([]);
                LEB_Toaster.error(res.data?.message || 'Failed to load listings.');
            }
        }).fail(function () {
            showLoading(false);
            renderRows([]);
            LEB_Toaster.error('Network error. Please try again.');
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * RENDER TABLE ROWS (PREMIUM UI CARDS)
     * ═══════════════════════════════════════════════════════════ */
    function renderRows(items) {
        if (!items.length) {
            DOM.body.innerHTML = '';
            DOM.table.style.display = 'none';
            DOM.empty.style.display = '';
            return;
        }

        DOM.table.style.display = '';
        DOM.empty.style.display = 'none';

        const editBase = lebState.editBaseUrl || (window.location.href.split('?')[0] + '?page=leb-properties&leb_action=edit&id=');

        DOM.body.innerHTML = items.map(function (item, index) {
            const thumb = item.first_image
                ? '<img class="leb-data-img" src="' + escHtml(item.first_image) + '" alt="">'
                : '<div class="leb-data-img" style="display:flex;align-items:center;justify-content:center;background:#e9eef3;"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>';

            const status = (item.status || 'draft').toLowerCase();
            const sNo = ((lebState.page - 1) * lebState.perPage) + index + 1;

            return '<div class="leb-pl-t-datas" data-id="' + item.id + '">'
                + '<input class="leb-pl-checkbox leb-prop-row-check" type="checkbox" value="' + item.id + '">'
                + '<div class="leb-data-img-cont">' + thumb + '</div>'
                + '<div class="leb-pl-s-no">' + sNo + '</div>'
                + '<div class="data-entry">'
                +     '<div class="leb-pl-t-data-entry">'
                +         '<div class="leb-pl-data-label">Title</div>'
                +         '<div class="leb-pl-data-entry" style="font-weight:600;">' + escHtml(item.title) + '</div>'
                +     '</div>'
                +     '<div class="leb-pl-t-data-entry">'
                +         '<div class="leb-pl-data-label">Type</div>'
                +         '<div class="leb-pl-data-entry">' + escHtml(item.type_name || '—') + '</div>'
                +     '</div>'
                +     '<div class="leb-pl-t-data-entry">'
                +         '<div class="leb-pl-data-label">Price</div>'
                +         '<div class="leb-pl-data-entry">₹' + Number(item.price || 0).toLocaleString('en-IN') + ' / Night</div>'
                +     '</div>'
                +     '<div class="leb-pl-t-data-entry">'
                +         '<div class="leb-pl-data-label">Host</div>'
                +         '<div class="leb-pl-data-entry">' + escHtml(item.username || '—') + '</div>'
                +     '</div>'
                +     '<div class="leb-pl-t-data-entry">'
                +         '<div class="leb-pl-data-label">Status</div>'
                +         '<div class="leb-pl-data-entry"><span class="leb-pl-data-status leb-pl-data-status--' + status + '">' + status + '</span></div>'
                +     '</div>'
                + '</div>'
                + '<div class="leb-pl-t-data-entry leb-pl-t-data-action">'
                +     '<a href="' + editBase + item.id + '" class="leb-pl-data-btn">'
                +         '<svg class="leb-pl-action-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>'
                +         'Edit'
                +     '</a>'
                +     '<button class="leb-pl-data-btn leb-pl-data-entry-delete leb-prop-delete-btn" data-id="' + item.id + '">'
                +         '<svg class="leb-pl-action-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>'
                +         'Delete'
                +     '</button>'
                + '</div>'
                + '</div>';
        }).join('');
    }

    /* ═══════════════════════════════════════════════════════════
     * PAGINATION
     * ═══════════════════════════════════════════════════════════ */
    function renderPagination(data) {
        const total      = parseInt(data.total, 10) || 0;
        const perPage    = lebState.perPage;
        const totalPages = Math.ceil(total / perPage) || 1;
        const page       = lebState.page;

        // Info text
        if (total > 0) {
            const start = ((page - 1) * perPage) + 1;
            const end   = Math.min(page * perPage, total);
            DOM.paginationInfo.textContent = 'Showing ' + start + '–' + end + ' of ' + total;
        } else {
            DOM.paginationInfo.textContent = '';
        }

        // Page buttons
        if (totalPages <= 1) {
            DOM.paginationCtrl.innerHTML = '';
            return;
        }

        let html = '';

        // Prev
        html += '<button class="leb-pl-page-btn" data-page="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '><svg class="leb-pl-page-btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg></button>';

        // Page numbers
        const range = buildRange(page, totalPages);
        range.forEach(function (p) {
            if (p === '...') {
                html += '<span class="leb-pl-page-btn" style="pointer-events:none;border:none;background:transparent;">…</span>';
            } else {
                html += '<button class="leb-pl-page-btn' + (p === page ? ' leb-pl-page-btn-active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        // Next
        html += '<button class="leb-pl-page-btn" data-page="' + (page + 1) + '"' + (page >= totalPages ? ' disabled' : '') + '><svg class="leb-pl-page-btn-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>';

        DOM.paginationCtrl.innerHTML = html;
    }

    function buildRange(current, total) {
        if (total <= 7) {
            const arr = [];
            for (let i = 1; i <= total; i++) arr.push(i);
            return arr;
        }
        if (current <= 4) return [1, 2, 3, 4, 5, '...', total];
        if (current >= total - 3) return [1, '...', total - 4, total - 3, total - 2, total - 1, total];
        return [1, '...', current - 1, current, current + 1, '...', total];
    }

    /* ═══════════════════════════════════════════════════════════
     * TAB COUNTS
     * ═══════════════════════════════════════════════════════════ */
    function updateTabCounts(counts) {
        const all = (parseInt(counts.published, 10) || 0)
                  + (parseInt(counts.draft, 10) || 0)
                  + (parseInt(counts.pending, 10) || 0)
                  + (parseInt(counts.rejected, 10) || 0);

        if (DOM.countAll)       DOM.countAll.textContent       = all;
        if (DOM.countPublished) DOM.countPublished.textContent = counts.published || 0;
        if (DOM.countDraft)     DOM.countDraft.textContent     = counts.draft     || 0;
        if (DOM.countPending)   DOM.countPending.textContent   = counts.pending   || 0;
        if (DOM.countRejected)  DOM.countRejected.textContent  = counts.rejected  || 0;
    }

    /* ═══════════════════════════════════════════════════════════
     * EVENTS
     * ═══════════════════════════════════════════════════════════ */
    function bindEvents() {
        // Status tabs
        DOM.statusTabs.addEventListener('click', function (e) {
            const tab = e.target.closest('.status');
            if (!tab) return;
            DOM.statusTabs.querySelectorAll('.status').forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            lebState.status = tab.dataset.status || '';
            lebState.page = 1;
            fetchListings();
        });

        // Search (debounced)
        let searchTimer;
        const lebWrapper = document.getElementById('leb-pl-search-bar-wrapper');
        const clearBtn = document.getElementById('leb-pl-search-clear-btn');
        
        function updateSearchClearBtn() {
            if (DOM.searchInput.value.length > 0) clearBtn.classList.add('leb-pl-search-clear-visible');
            else clearBtn.classList.remove('leb-pl-search-clear-visible');
        }

        DOM.searchInput.addEventListener('input', function () {
            updateSearchClearBtn();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                lebState.search = DOM.searchInput.value.trim();
                lebState.page = 1;
                fetchListings();
            }, 350);
        });
        
        DOM.searchInput.addEventListener('focus', function () { if(lebWrapper) lebWrapper.classList.add('leb-pl-search-focused'); });
        DOM.searchInput.addEventListener('blur', function () { if(lebWrapper) lebWrapper.classList.remove('leb-pl-search-focused'); });
        clearBtn.addEventListener('click', function() {
            DOM.searchInput.value = '';
            updateSearchClearBtn();
            lebState.search = '';
            lebState.page = 1;
            fetchListings();
        });

        // Select all checkbox
        DOM.selectAll.addEventListener('change', function () {
            const checkboxes = DOM.body.querySelectorAll('.leb-prop-row-check');
            checkboxes.forEach(function (cb) {
                cb.checked = DOM.selectAll.checked;
                const card = cb.closest('.leb-pl-t-datas');
                if (cb.checked) {
                    lebState.selected.add(parseInt(cb.value, 10));
                    if (card) card.classList.add('selected');
                } else {
                    lebState.selected.delete(parseInt(cb.value, 10));
                    if (card) card.classList.remove('selected');
                }
            });
            syncBulkBar();
        });

        // Individual ROW checkbox
        DOM.body.addEventListener('change', function (e) {
            if (e.target.classList.contains('leb-prop-row-check')) {
                const id = parseInt(e.target.value, 10);
                const card = e.target.closest('.leb-pl-t-datas');
                if (e.target.checked) {
                    lebState.selected.add(id);
                    if (card) card.classList.add('selected');
                } else {
                    lebState.selected.delete(id);
                    if (card) card.classList.remove('selected');
                }
                syncBulkBar();
                updateSelectAllState();
            }
        });

        // Pagination clicks
        DOM.paginationCtrl.addEventListener('click', function (e) {
            const btn = e.target.closest('.leb-pl-page-btn');
            if (!btn || btn.disabled) return;
            const p = parseInt(btn.dataset.page, 10);
            if (p && p !== lebState.page) {
                lebState.page = p;
                fetchListings();
            }
        });

        // Delete SINGLE
        DOM.body.addEventListener('click', function (e) {
            const btn = e.target.closest('.leb-prop-delete-btn');
            if (!btn) return;
            const id = parseInt(btn.dataset.id, 10);
            if (!id) return;

            LEB_Confirm.show('Are you sure you want to delete this property?', function () {
                performAJAXAction('leb_listing_delete_listings', [id], 'Property deleted successfully.', function () {
                    // if it's the last item on the page, go back one page if possible
                    if (DOM.body.children.length === 1 && lebState.page > 1) {
                        lebState.page--;
                    }
                    fetchListings();
                });
            });
        });

        // Bulk Actions
        document.getElementById('leb-prop-bulk-publish').addEventListener('click', function() { doBulk('published'); });
        document.getElementById('leb-prop-bulk-draft').addEventListener('click',   function() { doBulk('draft'); });
        document.getElementById('leb-prop-bulk-delete').addEventListener('click',  function() { doBulk('delete'); });
    }

    function doBulk(actionType) {
        if (!lebState.selected.size) return;
        const ids = Array.from(lebState.selected);
        let action = '';
        let confirmMsg = '';
        let successMsg = '';

        if (actionType === 'delete') {
            action = 'leb_listing_delete_listings';
            confirmMsg = 'Delete ' + ids.length + ' selected propert' + (ids.length > 1 ? 'ies' : 'y') + '?';
            successMsg = 'Properties deleted.';
        } else {
            action = 'leb_listing_bulk_status';
            confirmMsg = 'Mark ' + ids.length + ' propert' + (ids.length > 1 ? 'ies' : 'y') + ' as ' + actionType + '?';
            successMsg = 'Properties updated.';
        }

        LEB_Confirm.show(confirmMsg, function () {
            performAJAXAction(action, ids, successMsg, function () {
                lebState.selected.clear();
                if (actionType === 'delete' && DOM.body.children.length === ids.length && lebState.page > 1) {
                    lebState.page--;
                }
                fetchListings();
            }, (actionType !== 'delete' ? actionType : null));
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * UTILS
     * ═══════════════════════════════════════════════════════════ */
    function syncBulkBar() {
        const count = lebState.selected.size;
        DOM.selectedCount.textContent = count;
        DOM.bulkBar.style.display = count > 0 ? 'flex' : 'none';
        if (count === 0 && DOM.selectAll) DOM.selectAll.checked = false;
    }

    function updateSelectAllState() {
        if (!DOM.selectAll) return;
        const checkboxes = DOM.body.querySelectorAll('.leb-prop-row-check');
        if (!checkboxes.length) {
            DOM.selectAll.checked = false;
            return;
        }
        let allChecked = true;
        checkboxes.forEach(function (cb) { if (!cb.checked) allChecked = false; });
        DOM.selectAll.checked = allChecked;
    }

    function showLoading(show) {
        DOM.loading.style.display = show ? '' : 'none';
        if (show) {
            DOM.table.style.display = 'none';
            DOM.empty.style.display = 'none';
        }
    }

    function performAJAXAction(action, ids, successMsg, onSuccess, newStatus) {
        const data = { action: action, nonce: LEB_Ajax.nonce, ids: ids };
        if (newStatus) data.status = newStatus;

        jQuery.post(LEB_Ajax.ajax_url, data, function (res) {
            if (res.success) {
                LEB_Toaster.success(successMsg);
                if (typeof onSuccess === 'function') onSuccess();
            } else {
                LEB_Toaster.error(res.data?.message || 'Action failed.');
            }
        }).fail(function () {
            LEB_Toaster.error('Network error during action.');
        });
    }

    // Basic XSS escape
    const divMap = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (s) { return divMap[s]; });
    }

})();
