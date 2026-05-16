document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    if (typeof LEB_Ajax === 'undefined') {
        console.warn('LEB_Ajax is not defined. AJAX functionality may be broken. Check if assets are enqueued correctly.');
    }

    var ajaxUrl = (typeof LEB_Ajax !== 'undefined') ? LEB_Ajax.ajax_url : '';
    var nonce = (typeof LEB_Ajax !== 'undefined') ? LEB_Ajax.nonce : '';

    if (typeof LEB_Toaster === 'undefined') {
        console.warn('LEB_Toaster is not defined. UI notifications will not be shown.');
    }

    /* ── Badge HTML builder (mirrors PHP leb_render_db_card_statuses) ── */
    function lebBuildStatusHtml(tableData) {
        var html = '';

        // Table Exists.
        html += '<div class="leb-db-status-row">';
        html += '<span class="leb-db-status-label">Table Created:</span>';
        if (tableData.exists) {
            html += '<span class="leb-badge leb-badge--success">&#10003; Yes</span>';
        } else {
            html += '<span class="leb-badge leb-badge--error">&#10007; Not Created</span>';
        }
        html += '</div>';

        // Rows Complete (only if table exists).
        if (tableData.exists) {
            html += '<div class="leb-db-status-row">';
            html += '<span class="leb-db-status-label">Rows Complete:</span>';
            if (tableData.rows_complete) {
                html += '<span class="leb-badge leb-badge--success">&#10003; All Present</span>';
            } else {
                html += '<span class="leb-badge leb-badge--warning">&#9888; Missing Rows</span>';
            }
            html += '</div>';
        }

        return html;
    }

    /* ── Generic: set button loading state ──────────────────── */
    function lebSetBtnLoading(btn, loading) {
        btn.disabled = loading;
        if (loading) {
            btn.classList.add('leb-btn-loading');
        } else {
            btn.classList.remove('leb-btn-loading');
        }
    }

    /* ── Refresh Handler ─────────────────────────────────────── */
    document.querySelectorAll('.leb-db-btn--refresh').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tableKey = this.getAttribute('data-table-key');
            var statusEl = document.getElementById('leb-db-statuses-' + tableKey);

            lebSetBtnLoading(btn, true);

            var fd = new FormData();
            fd.append('action', 'leb_db_status');
            fd.append('nonce', nonce);

            fetch(ajaxUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    lebSetBtnLoading(btn, false);

                    if (data.success && data.data && data.data.tables) {
                        data.data.tables.forEach(function(t) {
                            if (t.key === tableKey && statusEl) {
                                statusEl.innerHTML = lebBuildStatusHtml(t);
                            }
                        });
                        if (typeof LEB_Toaster !== 'undefined') {
                            LEB_Toaster.show('Status refreshed.', 'info');
                        }
                    } else {
                        if (typeof LEB_Toaster !== 'undefined') {
                            LEB_Toaster.show('Could not retrieve status.', 'error');
                        }
                    }
                })
                .catch(function() {
                    lebSetBtnLoading(btn, false);
                    if (typeof LEB_Toaster !== 'undefined') {
                        LEB_Toaster.show('Network error. Please try again.', 'error');
                    }
                });
        });
    });

    /* ── Create / Repair Handler ─────────────────────────────── */
    document.querySelectorAll('.leb-db-btn--repair').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tableKey = this.getAttribute('data-table-key');
            var statusEl = document.getElementById('leb-db-statuses-' + tableKey);
            var refreshBtn = document.getElementById('leb-db-refresh-' + tableKey);

            lebSetBtnLoading(btn, true);
            if (refreshBtn) {
                refreshBtn.disabled = true;
            }

            var fd = new FormData();
            fd.append('action', 'leb_db_create_repair');
            fd.append('nonce', nonce);
            fd.append('table_key', tableKey);

            fetch(ajaxUrl, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(data) {
                    lebSetBtnLoading(btn, false);
                    if (refreshBtn) {
                        refreshBtn.disabled = false;
                    }

                    if (data.success) {
                        if (typeof LEB_Toaster !== 'undefined') {
                            LEB_Toaster.show(data.data.message || 'Table created / repaired.', 'success');
                        }
                        // Trigger a status refresh to update the card.
                        if (refreshBtn) {
                            refreshBtn.click();
                        }
                    } else {
                        if (typeof LEB_Toaster !== 'undefined') {
                            LEB_Toaster.show((data.data && data.data.message) ? data.data.message : 'Operation failed.', 'error');
                        }
                    }
                })
                .catch(function() {
                    lebSetBtnLoading(btn, false);
                    if (refreshBtn) {
                        refreshBtn.disabled = false;
                    }
                    if (typeof LEB_Toaster !== 'undefined') {
                        LEB_Toaster.show('Network error. Please try again.', 'error');
                    }
                });
        });
    });

});
