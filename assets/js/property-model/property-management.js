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
     * RENDER TABLE ROWS
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

        DOM.body.innerHTML = items.map(function (item) {
            const thumb = item.thumbnail_url
                ? '<img class="leb-prop-mgmt-property-thumb" src="' + escHtml(item.thumbnail_url) + '" alt="">'
                : '<div class="leb-prop-mgmt-property-thumb" style="display:flex;align-items:center;justify-content:center;"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg></div>';

            const details = [];
            if (item.guests)   details.push(item.guests + ' guests');
            if (item.bedroom)  details.push(item.bedroom + ' bed');
            if (item.bathroom) details.push(item.bathroom + ' bath');

            const status = (item.status || 'draft').toLowerCase();

            return '<tr class="leb-prop-mgmt-table__tr" data-id="' + item.id + '">'
                + '<td class="leb-prop-mgmt-table__td leb-prop-mgmt-table__td--check">'
                +     '<input type="checkbox" class="leb-prop-mgmt-checkbox leb-prop-row-check" value="' + item.id + '">'
                + '</td>'
                + '<td class="leb-prop-mgmt-table__td">'
                +     '<div class="leb-prop-mgmt-property-cell">'
                +         thumb
                +         '<div class="leb-prop-mgmt-property-info">'
                +             '<span class="leb-prop-mgmt-property-title">' + escHtml(item.title) + '</span>'
                +             '<span class="leb-prop-mgmt-property-details">' + escHtml(details.join(' · ')) + '</span>'
                +         '</div>'
                +     '</div>'
                + '</td>'
                + '<td class="leb-prop-mgmt-table__td">' + escHtml(item.type_name || '—') + '</td>'
                + '<td class="leb-prop-mgmt-table__td">' + escHtml(item.location || '—') + '</td>'
                + '<td class="leb-prop-mgmt-table__td"><strong>₹' + Number(item.price || 0).toLocaleString('en-IN') + '</strong>/night</td>'
                + '<td class="leb-prop-mgmt-table__td">' + escHtml(item.host_name || '—') + '</td>'
                + '<td class="leb-prop-mgmt-table__td">'
                +     '<span class="leb-prop-mgmt-status leb-prop-mgmt-status--' + status + '">'
                +         '<span class="leb-prop-mgmt-status__dot"></span>' + status
                +     '</span>'
                + '</td>'
                + '<td class="leb-prop-mgmt-table__td leb-prop-mgmt-table__td--actions">'
                +     '<div class="leb-prop-mgmt-actions">'
                +         '<a href="' + editBase + item.id + '" class="leb-prop-mgmt-action-btn" title="Edit">'
                +             '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>'
                +         '</a>'
                +         '<button class="leb-prop-mgmt-action-btn leb-prop-mgmt-action-btn--delete leb-prop-delete-btn" data-id="' + item.id + '" title="Delete">'
                +             '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>'
                +         '</button>'
                +     '</div>'
                + '</td>'
                + '</tr>';
        }).join('');
    }

    /* ═══════════════════════════════════════════════════════════
     * PAGINATION
     * ═══════════════════════════════════════════════════════════ */
    function renderPagination(data) {
        const total      = parseInt(data.total, 10) || 0;
        const totalPages = parseInt(data.total_pages, 10) || 1;
        const page       = lebState.page;
        const perPage    = lebState.perPage;

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
        html += '<button class="leb-prop-mgmt-page-btn" data-page="' + (page - 1) + '"' + (page <= 1 ? ' disabled' : '') + '>&laquo;</button>';

        // Page numbers (show max 7 with ellipsis)
        const range = buildRange(page, totalPages);
        range.forEach(function (p) {
            if (p === '...') {
                html += '<span class="leb-prop-mgmt-page-btn" style="pointer-events:none;border:none;">…</span>';
            } else {
                html += '<button class="leb-prop-mgmt-page-btn' + (p === page ? ' leb-prop-mgmt-page-btn--active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
        });

        // Next
        html += '<button class="leb-prop-mgmt-page-btn" data-page="' + (page + 1) + '"' + (page >= totalPages ? ' disabled' : '') + '>&raquo;</button>';

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
            const tab = e.target.closest('.leb-prop-mgmt-tab');
            if (!tab) return;
            DOM.statusTabs.querySelectorAll('.leb-prop-mgmt-tab').forEach(function (t) { t.classList.remove('leb-prop-mgmt-tab--active'); });
            tab.classList.add('leb-prop-mgmt-tab--active');
            lebState.status = tab.dataset.status || '';
            lebState.page = 1;
            fetchListings();
        });

        // Search (debounced)
        let searchTimer;
        DOM.searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () {
                lebState.search = DOM.searchInput.value.trim();
                lebState.page = 1;
                fetchListings();
            }, 350);
        });

        // Select all checkbox
        DOM.selectAll.addEventListener('change', function () {
            const checkboxes = DOM.body.querySelectorAll('.leb-prop-row-check');
            checkboxes.forEach(function (cb) {
                cb.checked = DOM.selectAll.checked;
                const id = parseInt(cb.value, 10);
                DOM.selectAll.checked ? lebState.selected.add(id) : lebState.selected.delete(id);
                cb.closest('tr').classList.toggle('leb-prop-mgmt-table__tr--selected', DOM.selectAll.checked);
            });
            syncBulkBar();
        });

        // Row checkbox delegation
        DOM.body.addEventListener('change', function (e) {
            if (!e.target.classList.contains('leb-prop-row-check')) return;
            const id = parseInt(e.target.value, 10);
            e.target.checked ? lebState.selected.add(id) : lebState.selected.delete(id);
            e.target.closest('tr').classList.toggle('leb-prop-mgmt-table__tr--selected', e.target.checked);
            syncBulkBar();
            // Sync "select all" state
            const allChecks = DOM.body.querySelectorAll('.leb-prop-row-check');
            DOM.selectAll.checked = allChecks.length > 0 && lebState.selected.size === allChecks.length;
        });

        // Single delete delegation
        DOM.body.addEventListener('click', function (e) {
            const btn = e.target.closest('.leb-prop-delete-btn');
            if (!btn) return;
            const id = parseInt(btn.dataset.id, 10);
            LEB_Confirmation.show({
                title:   'Delete Property',
                message: 'Are you sure you want to delete this property? This action cannot be undone.',
                confirmText: 'Delete',
                type: 'danger',
                onConfirm: function () { deleteSingle(id); },
            });
        });

        // Pagination delegation
        DOM.paginationCtrl.addEventListener('click', function (e) {
            const btn = e.target.closest('.leb-prop-mgmt-page-btn');
            if (!btn || btn.disabled) return;
            const p = parseInt(btn.dataset.page, 10);
            if (isNaN(p) || p < 1) return;
            lebState.page = p;
            fetchListings();
            // Scroll table into view
            document.querySelector('.leb-prop-mgmt-wrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Bulk actions
        document.getElementById('leb-prop-bulk-publish')?.addEventListener('click', function () { bulkStatus('published'); });
        document.getElementById('leb-prop-bulk-draft')?.addEventListener('click',   function () { bulkStatus('draft'); });
        document.getElementById('leb-prop-bulk-delete')?.addEventListener('click',  function () {
            LEB_Confirmation.show({
                title:   'Bulk Delete',
                message: 'Delete ' + lebState.selected.size + ' selected properties? This cannot be undone.',
                confirmText: 'Delete All',
                type: 'danger',
                onConfirm: bulkDelete,
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * SINGLE + BULK ACTIONS
     * ═══════════════════════════════════════════════════════════ */
    function deleteSingle(id) {
        jQuery.post(LEB_Ajax.ajax_url, {
            action: 'leb_listing_delete_listing',
            nonce:  LEB_Ajax.nonce,
            id:     id,
        }, function (res) {
            if (res.success) {
                LEB_Toaster.success(res.data.message);
                fetchListings();
            } else {
                LEB_Toaster.error(res.data?.message || 'Delete failed.');
            }
        });
    }

    function bulkDelete() {
        jQuery.post(LEB_Ajax.ajax_url, {
            action: 'leb_listing_bulk_delete',
            nonce:  LEB_Ajax.nonce,
            ids:    Array.from(lebState.selected),
        }, function (res) {
            if (res.success) {
                LEB_Toaster.success(res.data.message);
                fetchListings();
            } else {
                LEB_Toaster.error(res.data?.message || 'Bulk delete failed.');
            }
        });
    }

    function bulkStatus(status) {
        jQuery.post(LEB_Ajax.ajax_url, {
            action: 'leb_listing_bulk_status',
            nonce:  LEB_Ajax.nonce,
            ids:    Array.from(lebState.selected),
            status: status,
        }, function (res) {
            if (res.success) {
                LEB_Toaster.success(res.data.message);
                fetchListings();
            } else {
                LEB_Toaster.error(res.data?.message || 'Status update failed.');
            }
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * UI HELPERS
     * ═══════════════════════════════════════════════════════════ */
    function showLoading(show) {
        if (show) {
            DOM.loading.style.display = '';
            DOM.table.style.display = 'none';
            DOM.empty.style.display = 'none';
        } else {
            DOM.loading.style.display = 'none';
        }
    }

    function syncBulkBar() {
        const count = lebState.selected.size;
        DOM.selectedCount.textContent = count;
        DOM.bulkBar.style.display = count > 0 ? '' : 'none';
    }

    /** Minimal HTML-entity escaper for XSS prevention. */
    function escHtml(str) {
        if (!str) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(str).replace(/[&<>"']/g, function (c) { return map[c]; });
    }

})();
