<?php
/**
 * type-management.php
 *
 * Types List view – search, mobile-card table, and pagination.
 * Matches the design in screen/main-screen.html exactly.
 *
 * Loaded by leb_render_type_management_page() in the main plugin file.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<style>
    /* ── Type Management – List Screen ──────────────────────────────
       Scoped to #leb-type-list to prevent conflicts with other templates.
    ─────────────────────────────────────────────────────────────── */

    #leb-type-list {
        font-family: var(--leb-font-family);
        color:       var(--leb-text-color);
        max-width:   1200px;
        margin:      20px auto;
        padding:     0 16px;
    }

    /* ── Header ─────────────────────────────────────────────────── */
    #leb-type-list .leb-tl-header {
        display:          flex;
        align-items:      center;
        justify-content:  space-between;
        flex-wrap:        wrap;
        gap:              16px;
        background-color: var(--leb-white);
        padding:          1rem 1.25rem;
        border-radius:    var(--leb-radius-lg);
        box-shadow:       var(--leb-card-shadow);
        margin-bottom:    24px;
    }

    #leb-type-list .leb-tl-header-left {
        display:     flex;
        align-items: center;
        gap:         14px;
    }

    #leb-type-list .leb-tl-icon-box {
        width:         48px;
        height:        48px;
        background:    linear-gradient(135deg, var(--leb-primary-color), var(--leb-primary-dark));
        border-radius: var(--leb-radius-lg);
        display:       flex;
        align-items:   center;
        justify-content: center;
        box-shadow:    0 6px 20px var(--leb-primary-glow);
        flex-shrink:   0;
    }

    #leb-type-list .leb-tl-icon-box svg {
        width:  24px;
        height: 24px;
        color:  var(--leb-white);
    }

    #leb-type-list .leb-tl-page-title {
        font-size:      var(--leb-font-size-2xl);
        font-weight:    700;
        color:          var(--leb-secondary-color);
        letter-spacing: -0.03em;
        line-height:    1.2;
        margin:         0;
    }

    #leb-type-list .leb-tl-add-btn {
        display:          inline-flex;
        align-items:      center;
        gap:              6px;
        background-color: var(--leb-primary-color);
        color:            var(--leb-white);
        border:           none;
        border-radius:    var(--leb-radius-pill);
        padding:          0.55rem 1.1rem;
        font-size:        var(--leb-font-size-sm);
        font-weight:      600;
        font-family:      var(--leb-font-family);
        cursor:           pointer;
        box-shadow:       0 4px 14px var(--leb-primary-glow);
        transition:       transform var(--leb-transition-fast), opacity var(--leb-transition-fast);
        text-decoration:  none;
    }

    #leb-type-list .leb-tl-add-btn:hover {
        opacity:   0.9;
        transform: translateY(-1px);
    }

    #leb-type-list .leb-tl-add-btn svg {
        width:  16px;
        height: 16px;
        flex-shrink: 0;
    }

    /* ── Search Bar ─────────────────────────────────────────────── */
    #leb-type-list .leb-tl-search-wrap {
        display:          flex;
        align-items:      center;
        gap:              10px;
        background-color: var(--leb-white);
        border:           2px solid var(--leb-border-color);
        border-radius:    var(--leb-radius-lg);
        padding:          6px 8px 6px 18px;
        box-shadow:       var(--leb-card-shadow);
        margin-bottom:    20px;
        transition:       border-color var(--leb-transition-normal);
    }

    #leb-type-list .leb-tl-search-wrap.leb-search-focused {
        border-color: var(--leb-primary-color);
    }

    #leb-type-list .leb-tl-search-icon {
        width:       20px;
        height:      20px;
        flex-shrink: 0;
        color:       var(--leb-text-muted);
        transition:  color var(--leb-transition-normal);
    }

    #leb-type-list .leb-tl-search-wrap.leb-search-focused .leb-tl-search-icon {
        color: var(--leb-primary-color);
    }

    #leb-type-list .leb-tl-search-input {
        flex:        1;
        border:      none;
        outline:     none;
        font-size:   var(--leb-font-size-md);
        font-family: var(--leb-font-family);
        color:       var(--leb-text-color);
        background:  transparent;
        padding:     8px 0;
    }

    #leb-type-list .leb-tl-search-input::placeholder {
        color: var(--leb-text-muted);
    }

    #leb-type-list .leb-tl-search-clear {
        display:       none;
        align-items:   center;
        justify-content: center;
        width:         32px;
        height:        32px;
        border:        none;
        background:    #f1f3f5;
        border-radius: var(--leb-radius-sm);
        color:         var(--leb-text-muted);
        cursor:        pointer;
        flex-shrink:   0;
        transition:    background-color var(--leb-transition-fast);
    }

    #leb-type-list .leb-tl-search-clear:hover {
        background-color: var(--leb-border-default);
    }

    #leb-type-list .leb-tl-search-clear svg {
        width:  16px;
        height: 16px;
    }

    #leb-type-list .leb-tl-search-clear.leb-clear-visible {
        display: flex;
    }

    /* ── Table Section ──────────────────────────────────────────── */
    #leb-type-list .leb-tl-table-wrap {
        background:    var(--leb-white);
        border-radius: var(--leb-radius-xl);
        box-shadow:    var(--leb-card-shadow);
        border:        1px solid var(--leb-border-color);
        overflow:      hidden;
        position:      relative;
        min-height:    180px;
    }

    /* Cards list container */
    #leb-type-list .leb-tl-cards-list {
        padding: 10px;
        display: flex;
        flex-direction: column;
        gap:    0;
    }

    /* Individual card row */
    #leb-type-list .leb-tl-card {
        margin:        6px;
        padding:       12px 16px;
        border:        1px solid var(--leb-border-color);
        border-radius: var(--leb-radius-md);
        display:       flex;
        flex-direction: column;
        gap:           6px;
    }

    #leb-type-list .leb-tl-card-row {
        display:         flex;
        flex-direction:  row;
        justify-content: space-between;
        align-items:     center;
        width:           100%;
        gap:             12px;
    }

    #leb-type-list .leb-tl-card-label {
        font-size:   var(--leb-font-size-xs);
        font-weight: 600;
        color:       var(--leb-text-muted);
        flex-shrink: 0;
        min-width:   70px;
    }

    #leb-type-list .leb-tl-card-value {
        font-size:   var(--leb-font-size-sm);
        font-weight: 400;
        color:       var(--leb-text-color);
        text-align:  right;
        word-break:  break-all;
    }

    /* Card action row */
    #leb-type-list .leb-tl-card-actions {
        margin-top:  6px;
        padding-top: 8px;
        border-top:  1px solid var(--leb-border-color);
        display:     flex;
        justify-content: flex-end;
    }

    #leb-type-list .leb-tl-edit-btn {
        display:       inline-flex;
        align-items:   center;
        gap:           5px;
        background:    var(--leb-primary-color);
        color:         var(--leb-white);
        border:        none;
        border-radius: var(--leb-radius-pill);
        padding:       5px 18px;
        font-size:     var(--leb-font-size-xs);
        font-weight:   600;
        font-family:   var(--leb-font-family);
        cursor:        pointer;
        transition:    opacity var(--leb-transition-fast);
        text-decoration: none;
    }

    #leb-type-list .leb-tl-edit-btn:hover {
        opacity: 0.85;
    }

    #leb-type-list .leb-tl-edit-btn svg {
        width:  14px;
        height: 14px;
    }

    /* ── Pagination Bar ─────────────────────────────────────────── */
    #leb-type-list .leb-tl-pagination {
        display:         flex;
        align-items:     center;
        justify-content: space-between;
        flex-wrap:       wrap;
        gap:             12px;
        padding:         14px 20px;
        border-top:      1px solid var(--leb-border-color);
    }

    #leb-type-list .leb-tl-pagination-text {
        font-size: var(--leb-font-size-sm);
        color:     var(--leb-text-muted);
    }

    #leb-type-list .leb-tl-page-controls {
        display:     flex;
        align-items: center;
        gap:         4px;
    }

    #leb-type-list .leb-pg-btn {
        display:         flex;
        align-items:     center;
        justify-content: center;
        width:           34px;
        height:          34px;
        border:          1px solid var(--leb-border-color);
        border-radius:   var(--leb-radius-sm);
        background:      var(--leb-white);
        color:           var(--leb-text-muted);
        font-size:       var(--leb-font-size-sm);
        font-weight:     600;
        font-family:     var(--leb-font-family);
        cursor:          pointer;
        transition:      all var(--leb-transition-fast);
    }

    #leb-type-list .leb-pg-btn:hover:not(:disabled) {
        border-color: var(--leb-primary-color);
        color:        var(--leb-primary-color);
    }

    #leb-type-list .leb-pg-btn.leb-pg-active {
        background:   var(--leb-primary-color);
        border-color: var(--leb-primary-color);
        color:        var(--leb-white);
    }

    #leb-type-list .leb-pg-btn:disabled {
        opacity: 0.4;
        cursor:  not-allowed;
    }

    #leb-type-list .leb-pg-btn svg {
        width:  16px;
        height: 16px;
    }

    /* ── Responsive ─────────────────────────────────────────────── */
    @media (max-width: 480px) {
        #leb-type-list .leb-tl-page-title {
            font-size: 1.4rem;
        }

        #leb-type-list .leb-tl-header-right {
            flex-grow: 1;
        }

        #leb-type-list .leb-tl-add-btn {
            width:           100%;
            justify-content: center;
        }
    }
</style>

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
document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    /* ── State ────────────────────────────────────────────────── */
    var lebState = {
        currentPage : 1,
        perPage     : 10,
        totalItems  : 0,
        searchTerm  : '',
        searchTimer : null,
        isLoading   : false,
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
        domTableWrap.appendChild( overlay );
    }

    function lebHideLoading() {
        var el = document.getElementById( 'leb-tl-loader' );
        if ( el ) { el.parentNode.removeChild( el ); }
    }

    /* ── Render: Empty state ─────────────────────────────────── */
    function lebRenderEmpty( message ) {
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
        if ( ! items || items.length === 0 ) {
            lebRenderEmpty( 'No types found.' );
            return;
        }

        var html = '';
        items.forEach( function ( row ) {
            var editUrl = '<?php echo esc_js( admin_url( 'admin.php?page=leb-types&leb_action=edit&id=' ) ); ?>' + encodeURIComponent( row.id );
            html += [
                '<div class="leb-tl-card">',
                '  <div class="leb-tl-card-row">',
                '    <span class="leb-tl-card-label">ID</span>',
                '    <span class="leb-tl-card-value">' + lebEscHtml( row.id ) + '</span>',
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
                '  </div>',
                '</div>',
            ].join( '' );
        } );
        domCardsList.innerHTML = html;
    }

    /* ── Render: Pagination ──────────────────────────────────── */
    function lebRenderPagination( total, page, perPage ) {
        var totalPages = Math.max( 1, Math.ceil( total / perPage ) );
        var start      = total === 0 ? 0 : ( page - 1 ) * perPage + 1;
        var end        = Math.min( page * perPage, total );

        domPagText.textContent = 'Showing ' + start + '–' + end + ' of ' + total;

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

        domPagControls.innerHTML = html;

        // Bind page buttons.
        domPagControls.querySelectorAll( '.leb-pg-btn[data-page]' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                lebState.currentPage = parseInt( this.getAttribute( 'data-page' ), 10 );
                lebFetchTypes();
            } );
        } );

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
        if ( domSearchInput.value.length > 0 ) {
            domSearchClear.classList.add( 'leb-clear-visible' );
        } else {
            domSearchClear.classList.remove( 'leb-clear-visible' );
        }
    }

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

    domSearchClear.addEventListener( 'click', function () {
        domSearchInput.value = '';
        lebUpdateClearBtn();
        lebState.searchTerm  = '';
        lebState.currentPage = 1;
        lebFetchTypes();
        domSearchInput.focus();
    } );

    domSearchInput.addEventListener( 'focus', function () {
        domSearchWrap.classList.add( 'leb-search-focused' );
    } );

    domSearchInput.addEventListener( 'blur', function () {
        domSearchWrap.classList.remove( 'leb-search-focused' );
    } );

    document.addEventListener( 'keydown', function ( e ) {
        if ( e.key === 'Escape' ) {
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
    lebFetchTypes();

} );
</script>
