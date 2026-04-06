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
document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    /* ── State ─────────────────────────────────────────────────── */
    var lebAmenState = {
        currentPage : 1,
        perPage     : 10,
        totalItems  : 0,
        searchTerm  : '',
        searchTimer : null,
        isLoading   : false,
        selectedIds : []
    };

    /* ── DOM References ─────────────────────────────────────────── */
    var domCardsList   = document.getElementById( 'leb-amen-cards-list' );
    var domPagText     = document.getElementById( 'leb-amen-pagination-text' );
    var domPagControls = document.getElementById( 'leb-amen-page-controls' );
    var domTableWrap   = document.getElementById( 'leb-amen-table-wrap' );
    var domSearchInput = document.getElementById( 'leb-amen-search-input' );
    var domSearchClear = document.getElementById( 'leb-amen-search-clear' );
    var domSearchWrap  = document.getElementById( 'leb-amen-search-wrap' );

    /* ── AJAX Config ────────────────────────────────────────────── */
    var ajaxUrl = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce   = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    /* ── Helper: format datetime ──────────────────────────────── */
    function lebAmenFormatDate( dateStr ) {
        if ( ! dateStr ) { return '—'; }
        try {
            var d = new Date( dateStr.replace( ' ', 'T' ) );
            return d.toLocaleString( 'en-IN', {
                year : 'numeric', month : 'short', day : 'numeric',
                hour : 'numeric', minute : '2-digit', hour12 : true,
            } );
        } catch ( e ) { return dateStr; }
    }

    /* ── Helper: XSS-safe text escaping ─────────────────────────── */
    function lebAmenEscHtml( text ) {
        var div = document.createElement( 'div' );
        div.appendChild( document.createTextNode( String( text || '' ) ) );
        return div.innerHTML;
    }

    /* ── Render: Loading overlay ─────────────────────────────── */
    function lebAmenShowLoading() {
        if ( document.getElementById( 'leb-amen-loader' ) ) { return; }
        var overlay = document.createElement( 'div' );
        overlay.id        = 'leb-amen-loader';
        overlay.className = 'leb-loading-overlay';
        overlay.setAttribute( 'aria-hidden', 'true' );
        overlay.innerHTML = '<div class="leb-spinner"></div>';
        domTableWrap.appendChild( overlay );
    }

    function lebAmenHideLoading() {
        var el = document.getElementById( 'leb-amen-loader' );
        if ( el ) { el.parentNode.removeChild( el ); }
    }

    /* ── Render: Empty state ──────────────────────────────────── */
    function lebAmenRenderEmpty( message ) {
        domCardsList.innerHTML = [
            '<div class="leb-empty-state">',
            '  <svg class="leb-empty-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
            '    <circle cx="12" cy="12" r="3"/>',
            '    <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4"/>',
            '  </svg>',
            '  <p class="leb-empty-state__title">' + lebAmenEscHtml( message ) + '</p>',
            '</div>',
        ].join( '' );
    }

    /* ── Render: Data cards ───────────────────────────────────── */
    function lebAmenRenderCards( items ) {
        if ( ! items || items.length === 0 ) {
            lebAmenRenderEmpty( 'No amenities found.' );
            lebAmenUpdateBulkBar();
            return;
        }

        var html = '';
        items.forEach( function ( row, index ) {
            var sno        = ( ( lebAmenState.currentPage - 1 ) * lebAmenState.perPage ) + index + 1;
            var editUrl    = '<?php echo esc_js( admin_url( 'admin.php?page=leb-amenities&leb_action=edit&id=' ) ); ?>' + encodeURIComponent( row.id );
            var isSelected = lebAmenState.selectedIds.includes( parseInt( row.id ) );

            /* SVG preview cell – renders the actual SVG if a path is stored */
            var svgCell = '';
            if ( row.svg_path ) {
                svgCell = '<img class="leb-am-svg-preview" src="' + lebAmenEscHtml( row.svg_path ) + '" alt="' + lebAmenEscHtml( row.name ) + ' icon" width="24" height="24" loading="lazy">';
            } else {
                svgCell = '<span class="leb-am-svg-none">—</span>';
            }

            html += [
                '<div class="leb-am-card" data-id="' + row.id + '">',
                '  <div class="leb-am-checkbox-wrap">',
                '    <input type="checkbox" class="leb-am-checkbox leb-am-item-checkbox" value="' + row.id + '"' + ( isSelected ? ' checked' : '' ) + '>',
                '  </div>',
                '  <div class="leb-am-card-row">',
                '    <span class="leb-am-card-label">S.No</span>',
                '    <span class="leb-am-card-value leb-am-s-no">' + sno + '</span>',
                '  </div>',
                '  <div class="leb-am-card-row">',
                '    <span class="leb-am-card-label">Name</span>',
                '    <span class="leb-am-card-value">' + lebAmenEscHtml( row.name ) + '</span>',
                '  </div>',
                '  <div class="leb-am-card-row">',
                '    <span class="leb-am-card-label">SVG Icon</span>',
                '    <span class="leb-am-card-value leb-am-svg-cell">' + svgCell + '</span>',
                '  </div>',
                '  <div class="leb-am-card-row">',
                '    <span class="leb-am-card-label">Updated</span>',
                '    <span class="leb-am-card-value">' + lebAmenEscHtml( lebAmenFormatDate( row.updated_at ) ) + '</span>',
                '  </div>',
                '  <div class="leb-am-card-actions">',
                '    <a href="' + editUrl + '" class="leb-am-edit-btn">',
                '      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                '        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>',
                '        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>',
                '      </svg>',
                '      Edit',
                '    </a>',
                '    <button class="leb-am-delete-btn" data-id="' + row.id + '" data-name="' + lebAmenEscHtml( row.name ) + '">',
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

        /* Bind individual checkboxes */
        domCardsList.querySelectorAll( '.leb-am-item-checkbox' ).forEach( function ( cb ) {
            cb.addEventListener( 'change', function () {
                var id = parseInt( this.value );
                if ( this.checked ) {
                    if ( ! lebAmenState.selectedIds.includes( id ) ) { lebAmenState.selectedIds.push( id ); }
                } else {
                    lebAmenState.selectedIds = lebAmenState.selectedIds.filter( function ( sid ) { return sid !== id; } );
                }
                lebAmenUpdateBulkBar();
            } );
        } );

        /* Bind delete buttons */
        domCardsList.querySelectorAll( '.leb-am-delete-btn' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                lebAmenConfirmDelete( this.getAttribute( 'data-id' ), this.getAttribute( 'data-name' ) );
            } );
        } );

        lebAmenUpdateBulkBar();
    }

    /* ── Bulk Actions Support ────────────────────────────────── */
    window.lebAmenUpdateBulkBar = function () {
        var bulkBar       = document.getElementById( 'leb-amen-bulk-actions' );
        var selectedCount = document.getElementById( 'leb-amen-selected-count' );
        var selectAll     = document.getElementById( 'leb-amen-select-all' );
        var pageChecks    = domCardsList.querySelectorAll( '.leb-am-item-checkbox' );

        if ( lebAmenState.selectedIds.length > 0 ) {
            bulkBar.classList.add( 'leb-active' );
            selectedCount.textContent = lebAmenState.selectedIds.length + ' selected';
        } else {
            bulkBar.classList.remove( 'leb-active' );
        }

        if ( pageChecks.length > 0 ) {
            var allChecked = true;
            pageChecks.forEach( function ( cb ) { if ( ! cb.checked ) { allChecked = false; } } );
            selectAll.checked = allChecked;
        } else {
            selectAll.checked = false;
        }
    };

    document.getElementById( 'leb-amen-select-all' ).addEventListener( 'change', function ( e ) {
        var isChecked  = e.target.checked;
        var pageChecks = domCardsList.querySelectorAll( '.leb-am-item-checkbox' );
        pageChecks.forEach( function ( cb ) {
            var id = parseInt( cb.value );
            cb.checked = isChecked;
            if ( isChecked ) {
                if ( ! lebAmenState.selectedIds.includes( id ) ) { lebAmenState.selectedIds.push( id ); }
            } else {
                lebAmenState.selectedIds = lebAmenState.selectedIds.filter( function ( sid ) { return sid !== id; } );
            }
        } );
        lebAmenUpdateBulkBar();
    } );

    window.lebAmenBulkDelete = function () {
        if ( lebAmenState.selectedIds.length === 0 ) { return; }

        if ( typeof LEB_Confirm === 'undefined' ) {
            if ( confirm( 'Delete ' + lebAmenState.selectedIds.length + ' selected amenities?' ) ) {
                lebAmenPerformBulkDelete();
            }
            return;
        }

        LEB_Confirm.show( {
            title       : 'Delete Selected?',
            message     : 'Are you sure you want to delete ' + lebAmenState.selectedIds.length + ' selected amenities? This cannot be undone.',
            confirmText : 'Delete All',
            type        : 'leb-warning',
            onConfirm   : function () { lebAmenPerformBulkDelete(); }
        } );
    };

    function lebAmenPerformBulkDelete() {
        var formData = new FormData();
        formData.append( 'action', 'leb_amen_bulk_delete_amenities' );
        formData.append( 'nonce',  nonce );
        lebAmenState.selectedIds.forEach( function ( id ) {
            formData.append( 'ids[]', id );
        } );

        lebAmenShowLoading();
        fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebAmenHideLoading();
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) { LEB_Toaster.show( data.data.message || 'Batch deleted.', 'success' ); }
                    lebAmenState.selectedIds = [];
                    lebAmenFetchAmenities();
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) { LEB_Toaster.show( data.data.message || 'Bulk delete failed.', 'error' ); }
                }
            } )
            .catch( function () {
                lebAmenHideLoading();
                if ( typeof LEB_Toaster !== 'undefined' ) { LEB_Toaster.show( 'Network error during bulk delete.', 'error' ); }
            } );
    }

    /* ── Render: Pagination ──────────────────────────────────── */
    function lebAmenRenderPagination( total, page, perPage ) {
        var totalPages = Math.max( 1, Math.ceil( total / perPage ) );
        var start      = total === 0 ? 0 : ( page - 1 ) * perPage + 1;
        var end        = Math.min( page * perPage, total );

        domPagText.textContent = 'Showing ' + start + '–' + end + ' of ' + total;

        var html = '';
        html += '<button class="leb-am-pg-btn" id="leb-amen-pg-prev" aria-label="Previous page"' + ( page <= 1 ? ' disabled' : '' ) + '>';
        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
        html += '</button>';

        var windowStart = Math.max( 1, page - 2 );
        var windowEnd   = Math.min( totalPages, windowStart + 4 );
        windowStart     = Math.max( 1, windowEnd - 4 );

        for ( var i = windowStart; i <= windowEnd; i++ ) {
            html += '<button class="leb-am-pg-btn' + ( i === page ? ' leb-am-pg-active' : '' ) + '" data-page="' + i + '">' + i + '</button>';
        }

        html += '<button class="leb-am-pg-btn" id="leb-amen-pg-next" aria-label="Next page"' + ( page >= totalPages ? ' disabled' : '' ) + '>';
        html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="9 18 15 12 9 6"/></svg>';
        html += '</button>';

        domPagControls.innerHTML = html;

        domPagControls.querySelectorAll( '.leb-am-pg-btn[data-page]' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                lebAmenState.currentPage = parseInt( this.getAttribute( 'data-page' ), 10 );
                lebAmenFetchAmenities();
            } );
        } );

        var prevBtn = document.getElementById( 'leb-amen-pg-prev' );
        var nextBtn = document.getElementById( 'leb-amen-pg-next' );

        if ( prevBtn ) {
            prevBtn.addEventListener( 'click', function () {
                if ( lebAmenState.currentPage > 1 ) { lebAmenState.currentPage--; lebAmenFetchAmenities(); }
            } );
        }
        if ( nextBtn ) {
            nextBtn.addEventListener( 'click', function () {
                if ( lebAmenState.currentPage < totalPages ) { lebAmenState.currentPage++; lebAmenFetchAmenities(); }
            } );
        }
    }

    /* ── Deletion Logic ──────────────────────────────────────── */
    function lebAmenConfirmDelete( id, name ) {
        if ( typeof LEB_Confirm === 'undefined' ) {
            if ( confirm( 'Are you sure you want to delete "' + name + '"?' ) ) { lebAmenPerformDelete( id ); }
            return;
        }
        LEB_Confirm.show( {
            title       : 'Delete Amenity?',
            message     : 'Are you sure you want to delete "' + name + '"? This action is irreversible.',
            confirmText : 'Delete Now',
            cancelText  : 'Cancel',
            type        : 'leb-warning',
            onConfirm   : function () { lebAmenPerformDelete( id ); }
        } );
    }

    function lebAmenPerformDelete( id ) {
        var formData = new FormData();
        formData.append( 'action', 'leb_amen_delete_amenity' );
        formData.append( 'nonce',  nonce );
        formData.append( 'id',     id );

        lebAmenShowLoading();
        fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebAmenHideLoading();
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) { LEB_Toaster.show( data.data.message || 'Deleted successfully.', 'success' ); }
                    lebAmenFetchAmenities();
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) { LEB_Toaster.show( data.data.message || 'Failed to delete.', 'error' ); }
                }
            } )
            .catch( function () {
                lebAmenHideLoading();
                if ( typeof LEB_Toaster !== 'undefined' ) { LEB_Toaster.show( 'Network error. Please try again.', 'error' ); }
            } );
    }

    /* ── AJAX Fetch ──────────────────────────────────────────── */
    function lebAmenFetchAmenities() {
        if ( lebAmenState.isLoading ) { return; }
        lebAmenState.isLoading = true;
        lebAmenShowLoading();

        var formData = new FormData();
        formData.append( 'action',   'leb_amen_get_amenities' );
        formData.append( 'nonce',    nonce );
        formData.append( 'search',   lebAmenState.searchTerm );
        formData.append( 'page',     lebAmenState.currentPage );
        formData.append( 'per_page', lebAmenState.perPage );

        fetch( ajaxUrl, { method: 'POST', body: formData, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebAmenHideLoading();
                lebAmenState.isLoading = false;

                if ( data.success && data.data ) {
                    var result = data.data;
                    lebAmenState.totalItems = result.total;
                    lebAmenRenderCards( result.items );
                    lebAmenRenderPagination( result.total, lebAmenState.currentPage, lebAmenState.perPage );
                } else {
                    lebAmenRenderEmpty( 'Failed to load amenities.' );
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( ( data.data && data.data.message ) ? data.data.message : 'Error loading amenities.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebAmenHideLoading();
                lebAmenState.isLoading = false;
                lebAmenRenderEmpty( 'Network error. Please try again.' );
            } );
    }

    /* ── Search Logic ────────────────────────────────────────── */
    function lebAmenUpdateClearBtn() {
        if ( domSearchInput.value.length > 0 ) {
            domSearchClear.classList.add( 'leb-am-clear-visible' );
        } else {
            domSearchClear.classList.remove( 'leb-am-clear-visible' );
        }
    }

    domSearchInput.addEventListener( 'input', function () {
        lebAmenUpdateClearBtn();
        clearTimeout( lebAmenState.searchTimer );
        var val = this.value.trim();
        if ( val.length === 0 || val.length >= 2 ) {
            lebAmenState.searchTimer = setTimeout( function () {
                lebAmenState.searchTerm  = val;
                lebAmenState.currentPage = 1;
                lebAmenFetchAmenities();
            }, 350 );
        }
    } );

    domSearchClear.addEventListener( 'click', function () {
        domSearchInput.value = '';
        lebAmenUpdateClearBtn();
        lebAmenState.searchTerm  = '';
        lebAmenState.currentPage = 1;
        lebAmenFetchAmenities();
        domSearchInput.focus();
    } );

    domSearchInput.addEventListener( 'focus', function () { domSearchWrap.classList.add( 'leb-am-search-focused' ); } );
    domSearchInput.addEventListener( 'blur',  function () { domSearchWrap.classList.remove( 'leb-am-search-focused' ); } );

    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' ) {
            domSearchInput.value = '';
            lebAmenUpdateClearBtn();
            lebAmenState.searchTerm  = '';
            lebAmenState.currentPage = 1;
            lebAmenFetchAmenities();
        }
    } );

    /* ── Bootstrap ───────────────────────────────────────────── */
    lebAmenFetchAmenities();

} );
</script>
