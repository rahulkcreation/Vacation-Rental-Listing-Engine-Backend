document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    var cfg = window.lebTypeMgmtCfg || {};
    var editUrlBase = cfg.editUrlBase || '';

    /* ── State ────────────────────────────────────────────────── */
    const lebState = {
        currentPage : 1,
        perPage     : 10,
        totalItems  : 0,
        searchTerm  : '',
        searchTimer : null,
        isLoading   : false,
        selectedIds : []
    };

    /* ── DOM References ──────────────────────────────────────── */
    var domCardsList    = document.getElementById( 'leb-tl-cards-list' );
    var domPagText      = document.getElementById( 'leb-tl-pagination-text' );
    var domPagControls  = document.getElementById( 'leb-tl-page-controls' );
    var domTableWrap    = document.getElementById( 'leb-tl-table-wrap' );
    var domSearchInput  = document.getElementById( 'leb-tl-search-input' );
    var domSearchClear  = document.getElementById( 'leb-tl-search-clear' );
    var domSearchWrap   = document.getElementById( 'leb-tl-search-wrap' );

    /* ── AJAX Config (injected by wp_localize_script) ────────── */
    var ajaxUrl = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce   = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    /* ── Helper: format datetime ─────────────────────────────── */
    function lebFormatDate( dateStr ) {
        if ( ! dateStr ) { return '—'; }
        try {
            var d = new Date( dateStr.replace( ' ', 'T' ) );
            return d.toLocaleString( 'en-IN', {
                year   : 'numeric', month  : 'short', day    : 'numeric',
                hour   : 'numeric', minute : '2-digit', hour12 : true,
            } );
        } catch ( e ) {
            return dateStr;
        }
    }

    /* ── Render: Loading overlay ─────────────────────────────── */
    function lebShowLoading() {
        if ( document.getElementById( 'leb-tl-loader' ) ) { return; }
        var overlay = document.createElement( 'div' );
        overlay.id        = 'leb-tl-loader';
        overlay.className = 'leb-loading-overlay';
        overlay.setAttribute( 'aria-hidden', 'true' );
        overlay.innerHTML = '<div class="leb-spinner"></div>';
        if (domTableWrap) domTableWrap.appendChild( overlay );
    }

    function lebHideLoading() {
        var el = document.getElementById( 'leb-tl-loader' );
        if ( el && el.parentNode ) { el.parentNode.removeChild( el ); }
    }

    /* ── Render: Empty state ─────────────────────────────────── */
    function lebRenderEmpty( message ) {
        if (!domCardsList) return;
        domCardsList.innerHTML = [
            '<div class="leb-empty-state">',
            '  <svg class="leb-empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
            '    <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>',
            '    <line x1="8" y1="21" x2="16" y2="21"/>',
            '    <line x1="12" y1="17" x2="12" y2="21"/>',
            '  </svg>',
            '  <p class="leb-empty-state__title">' + lebEscHtml( message ) + '</p>',
            '</div>',
        ].join( '' );
    }

    /* ── Render: Data cards ──────────────────────────────────── */
    function lebRenderCards( items ) {
        if ( ! domCardsList ) return;
        
        if ( ! items || items.length === 0 ) {
            lebRenderEmpty( 'No types found.' );
            lebUpdateBulkBar();
            return;
        }

        var html = '';
        items.forEach( function ( row, index ) {
            var sno        = ( ( lebState.currentPage - 1 ) * lebState.perPage ) + index + 1;
            var editUrl    = editUrlBase + encodeURIComponent( row.id );
            var isSelected = lebState.selectedIds.includes( parseInt( row.id ) );

            html += [
                '<div class="leb-tl-card" data-id="' + row.id + '">',
                '  <div class="leb-tl-checkbox-wrap">',
                '    <input type="checkbox" class="leb-tl-checkbox leb-tl-item-checkbox" value="' + row.id + '"' + ( isSelected ? ' checked' : '' ) + '>',
                '  </div>',
                '  <div class="leb-tl-card-row">',
                '    <span class="leb-tl-card-label">S.No</span>',
                '    <span class="leb-tl-card-value leb-s-no">' + sno + '</span>',
                '  </div>',
                '  <div class="leb-tl-card-row">',
                '    <span class="leb-tl-card-label">Name</span>',
                '    <span class="leb-tl-card-value">' + lebEscHtml( row.name ) + '</span>',
                '  </div>',
                '  <div class="leb-tl-card-row">',
                '    <span class="leb-tl-card-label">Slug</span>',
                '    <span class="leb-tl-card-value">/' + lebEscHtml( row.slug ) + '</span>',
                '  </div>',
                '  <div class="leb-tl-card-row">',
                '    <span class="leb-tl-card-label">Updated</span>',
                '    <span class="leb-tl-card-value">' + lebEscHtml( lebFormatDate( row.updated_at ) ) + '</span>',
                '  </div>',
                '  <div class="leb-tl-card-actions">',
                '    <a href="' + editUrl + '" class="leb-tl-edit-btn">',
                '      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                '        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>',
                '        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
                '      </svg>',
                '      Edit',
                '    </a>',
                '    <button class="leb-tl-delete-btn" data-id="' + row.id + '" data-name="' + row.name + '">',
                '      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                '        <polyline points="3 6 5 6 21 6"/>',
                '        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
                '        <line x1="10" y1="11" x2="10" y2="17"/>',
                '        <line x1="14" y1="11" x2="14" y2="17"/>',
                '      </svg>',
                '      Delete',
                '    </button>',
                '  </div>',
                '</div>',
            ].join( '' );
        } );
        domCardsList.innerHTML = html;

        // Bind Individual Checkboxes
        domCardsList.querySelectorAll( '.leb-tl-item-checkbox' ).forEach( function ( cb ) {
            cb.addEventListener( 'change', function () {
                var id = parseInt( this.value );
                if ( this.checked ) {
                    if ( ! lebState.selectedIds.includes( id ) ) {
                        lebState.selectedIds.push( id );
                    }
                } else {
                    lebState.selectedIds = lebState.selectedIds.filter( function ( sid ) { return sid !== id; } );
                }
                lebUpdateBulkBar();
            } );
        } );

        // Bind Delete Buttons
        domCardsList.querySelectorAll( '.leb-tl-delete-btn' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                var id   = this.getAttribute( 'data-id' );
                var name = this.getAttribute( 'data-name' );
                lebConfirmDelete( id, name );
            } );
        } );

        lebUpdateBulkBar();
    }

    /* ── Bulk Actions Support ────────────────────────────────── */
    window.lebUpdateBulkBar = function () {
        var bulkBar       = document.getElementById( 'leb-tl-bulk-actions' );
        var selectedCount = document.getElementById( 'leb-tl-selected-count' );
        var selectAll     = document.getElementById( 'leb-tl-select-all' );
        if (!domCardsList) return;
        var pageChecks    = domCardsList.querySelectorAll( '.leb-tl-item-checkbox' );
        
        if (bulkBar && selectedCount) {
            if ( lebState.selectedIds.length > 0 ) {
                bulkBar.classList.add( 'leb-active' );
                selectedCount.textContent = lebState.selectedIds.length + ' selected';
            } else {
                bulkBar.classList.remove( 'leb-active' );
            }
        }

        // Sync Select All checkbox
        if (selectAll) {
            if ( pageChecks.length > 0 ) {
                var allChecked = true;
                pageChecks.forEach( function ( cb ) { if ( ! cb.checked ) { allChecked = false; } } );
                selectAll.checked = allChecked;
            } else {
                selectAll.checked = false;
            }
        }
    };

    var domSelectAll = document.getElementById( 'leb-tl-select-all' );
    if (domSelectAll) {
        domSelectAll.addEventListener( 'change', function ( e ) {
            var isChecked  = e.target.checked;
            if (!domCardsList) return;
            var pageChecks = domCardsList.querySelectorAll( '.leb-tl-item-checkbox' );
            
            pageChecks.forEach( function ( cb ) {
                var id = parseInt( cb.value );
                cb.checked = isChecked;
                if ( isChecked ) {
                    if ( ! lebState.selectedIds.includes( id ) ) { lebState.selectedIds.push( id ); }
                } else {
                    lebState.selectedIds = lebState.selectedIds.filter( function ( sid ) { return sid !== id; } );
                }
            } );
            lebUpdateBulkBar();
        } );
    }

    window.lebBulkDelete = function () {
        if ( lebState.selectedIds.length === 0 ) { return; }

        if ( typeof LEB_Confirm === 'undefined' ) {
            if ( confirm( 'Delete ' + lebState.selectedIds.length + ' selected types?' ) ) {
                lebPerformBulkDelete();
            }
            return;
        }

        LEB_Confirm.show( {
            title       : 'Delete Selected?',
            message     : 'Are you sure you want to delete ' + lebState.selectedIds.length + ' selected items? This cannot be undone.',
            confirmText : 'Delete All',
            type        : 'leb-warning',
            onConfirm   : function () {
                lebPerformBulkDelete();
            }
        } );
    };

    function lebPerformBulkDelete() {
        var formData = new FormData();
        formData.append( 'action', 'leb_bulk_delete_types' );
        formData.append( 'nonce',  nonce );
        lebState.selectedIds.forEach( function ( id ) {
            formData.append( 'ids[]', id );
        } );

        lebShowLoading();

        fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebHideLoading();
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Batch deleted.', 'success' );
                    }
                    lebState.selectedIds = [];
                    lebFetchTypes();
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Bulk delete failed.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebHideLoading();
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error during bulk delete.', 'error' );
                }
            } );
    }

    /* ── Render: Pagination ──────────────────────────────────── */
    function lebRenderPagination( total, page, perPage ) {
        var totalPages = Math.max( 1, Math.ceil( total / perPage ) );
        var start      = total === 0 ? 0 : ( page - 1 ) * perPage + 1;
        var end        = Math.min( page * perPage, total );

        if (domPagText) {
            domPagText.textContent = 'Showing ' + start + '–' + end + ' of ' + total;
        }

        var html = '';

        // Previous button.
        html += '<button class="leb-pg-btn" id="leb-pg-prev" aria-label="Previous page"' + ( page <= 1 ? ' disabled' : '' ) + '>';
        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
        html += '</button>';

        // Page number buttons (show a sliding window of 5).
        var windowStart = Math.max( 1, page - 2 );
        var windowEnd   = Math.min( totalPages, windowStart + 4 );
        windowStart     = Math.max( 1, windowEnd - 4 );

        for ( var i = windowStart; i <= windowEnd; i++ ) {
            html += '<button class="leb-pg-btn' + ( i === page ? ' leb-pg-active' : '' ) + '" data-page="' + i + '">' + i + '</button>';
        }

        // Next button.
        html += '<button class="leb-pg-btn" id="leb-pg-next" aria-label="Next page"' + ( page >= totalPages ? ' disabled' : '' ) + '>';
        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>';
        html += '</button>';

        if (domPagControls) {
            domPagControls.innerHTML = html;

            // Bind page buttons.
            domPagControls.querySelectorAll( '.leb-pg-btn[data-page]' ).forEach( function ( btn ) {
                btn.addEventListener( 'click', function () {
                    lebState.currentPage = parseInt( this.getAttribute( 'data-page' ), 10 );
                    lebFetchTypes();
                } );
            } );
        }

        var prevBtn = document.getElementById( 'leb-pg-prev' );
        var nextBtn = document.getElementById( 'leb-pg-next' );

        if ( prevBtn ) {
            prevBtn.addEventListener( 'click', function () {
                if ( lebState.currentPage > 1 ) {
                    lebState.currentPage--;
                    lebFetchTypes();
                }
            } );
        }

        if ( nextBtn ) {
            nextBtn.addEventListener( 'click', function () {
                if ( lebState.currentPage < totalPages ) {
                    lebState.currentPage++;
                    lebFetchTypes();
                }
            } );
        }
    }

    /* ── Deletion Logic ──────────────────────────────────────── */
    function lebConfirmDelete( id, name ) {
        if ( typeof LEB_Confirm === 'undefined' ) {
            // Fallback if global component is not loaded.
            if ( confirm( 'Are you sure you want to delete "' + name + '"?' ) ) {
                lebPerformDelete( id );
            }
            return;
        }

        LEB_Confirm.show( {
            title       : 'Delete Entry?',
            message     : 'Are you sure you want to delete "' + name + '"? This action is irreversible.',
            confirmText : 'Delete Now',
            cancelText  : 'Cancel',
            type        : 'leb-warning',
            onConfirm   : function () {
                lebPerformDelete( id );
            }
        } );
    }

    function lebPerformDelete( id ) {
        var formData = new FormData();
        formData.append( 'action', 'leb_delete_type' );
        formData.append( 'nonce',  nonce );
        formData.append( 'id',     id );

        lebShowLoading();

        fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebHideLoading();
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Deleted successfully.', 'success' );
                    }
                    // Refresh current page.
                    lebFetchTypes();
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Failed to delete.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebHideLoading();
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error. Please try again.', 'error' );
                }
            } );
    }

    /* ── AJAX Fetch ──────────────────────────────────────────── */
    function lebFetchTypes() {
        if ( lebState.isLoading ) { return; }
        lebState.isLoading = true;
        lebShowLoading();

        var formData = new FormData();
        formData.append( 'action',   'leb_get_types' );
        formData.append( 'nonce',    nonce );
        formData.append( 'search',   lebState.searchTerm );
        formData.append( 'page',     lebState.currentPage );
        formData.append( 'per_page', lebState.perPage );

        fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebHideLoading();
                lebState.isLoading = false;

                if ( data.success && data.data ) {
                    var result = data.data;
                    lebState.totalItems = result.total;
                    lebRenderCards( result.items );
                    lebRenderPagination( result.total, lebState.currentPage, lebState.perPage );
                } else {
                    lebRenderEmpty( 'Failed to load types.' );
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( ( data.data && data.data.message ) ? data.data.message : 'Error loading types.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebHideLoading();
                lebState.isLoading = false;
                lebRenderEmpty( 'Network error. Please try again.' );
            } );
    }

    /* ── Search Logic ────────────────────────────────────────── */
    function lebUpdateClearBtn() {
        if (!domSearchInput || !domSearchClear) return;
        if ( domSearchInput.value.length > 0 ) {
            domSearchClear.classList.add( 'leb-clear-visible' );
        } else {
            domSearchClear.classList.remove( 'leb-clear-visible' );
        }
    }

    if (domSearchInput) {
        domSearchInput.addEventListener( 'input', function () {
            lebUpdateClearBtn();
            clearTimeout( lebState.searchTimer );
            var val = this.value.trim();

            // Only fire AJAX when ≥ 2 chars or when cleared.
            if ( val.length === 0 || val.length >= 2 ) {
                lebState.searchTimer = setTimeout( function () {
                    lebState.searchTerm  = val;
                    lebState.currentPage = 1;
                    lebFetchTypes();
                }, 350 );
            }
        } );

        domSearchInput.addEventListener( 'focus', function () {
            if (domSearchWrap) domSearchWrap.classList.add( 'leb-search-focused' );
        } );

        domSearchInput.addEventListener( 'blur', function () {
            if (domSearchWrap) domSearchWrap.classList.remove( 'leb-search-focused' );
        } );
    }

    if (domSearchClear) {
        domSearchClear.addEventListener( 'click', function () {
            domSearchInput.value = '';
            lebUpdateClearBtn();
            lebState.searchTerm  = '';
            lebState.currentPage = 1;
            lebFetchTypes();
            domSearchInput.focus();
        } );
    }

    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' && domSearchInput ) {
            domSearchInput.value = '';
            lebUpdateClearBtn();
            lebState.searchTerm  = '';
            lebState.currentPage = 1;
            lebFetchTypes();
        }
    } );

    /* ── XSS-safe text escaping ──────────────────────────────── */
    function lebEscHtml( text ) {
        var div = document.createElement( 'div' );
        div.appendChild( document.createTextNode( String( text || '' ) ) );
        return div.innerHTML;
    }

    /* ── Bootstrap ───────────────────────────────────────────── */
    // Ensure we are actually on the page that has this feature
    if (domCardsList) {
        lebFetchTypes();
    }

} );
