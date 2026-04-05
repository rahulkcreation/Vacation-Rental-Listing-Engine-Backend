<?php
/**
 * database-page.php
 *
 * Database Management screen.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// On initial PHP render get the status of all tables.
global $wpdb;
$leb_types_table = $wpdb->prefix . 'ls_types';
$leb_status      = leb_check_table_status( $leb_types_table );
?>
<div id="leb-database-page" class="leb-wrap">

    <!-- ── Page Header ───────────────────────────────────── -->
    <div class="leb-db-header">
        <div class="leb-db-icon-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <ellipse cx="12" cy="5"  rx="9" ry="3"/>
                <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/>
                <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
            </svg>
        </div>
        <h1 class="leb-db-page-title"><?php esc_html_e( 'Database Management', 'listing-engine-backend' ); ?></h1>
    </div>

    <!-- ── Cards Grid ─────────────────────────────────────── -->
    <div class="leb-db-grid" id="leb-db-grid">

        <!-- Types Table Card (initial PHP render – JS updates on refresh) -->
        <div class="leb-db-card" id="leb-db-card-ls_types">
            <h2 class="leb-db-card-title"><?php esc_html_e( 'Types Table', 'listing-engine-backend' ); ?></h2>

            <div class="leb-db-statuses" id="leb-db-statuses-ls_types">
                <?php
                // Render initial status.
                leb_render_db_card_statuses( $leb_status );
                ?>
            </div>

            <div class="leb-db-actions">
                <!-- Refresh button -->
                <button
                    class="leb-db-btn leb-db-btn--refresh"
                    id="leb-db-refresh-ls_types"
                    data-table-key="ls_types"
                    aria-label="<?php esc_attr_e( 'Refresh status', 'listing-engine-backend' ); ?>">
                    <span class="leb-db-card-spin" aria-hidden="true"></span>
                    <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="23 4 23 10 17 10"/>
                        <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/>
                    </svg>
                    <span class="leb-db-btn-label"><?php esc_html_e( 'Refresh', 'listing-engine-backend' ); ?></span>
                </button>

                <!-- Create/Repair button -->
                <button
                    class="leb-db-btn leb-db-btn--repair"
                    id="leb-db-repair-ls_types"
                    data-table-key="ls_types"
                    aria-label="<?php esc_attr_e( 'Create or repair table', 'listing-engine-backend' ); ?>">
                    <span class="leb-db-card-spin" aria-hidden="true"></span>
                    <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <polyline points="22 11.08 22 12 12 22l-10-10 6.21-6.21"/>
                        <path d="M12 2l3.09 3.09L19 1l-3 3z"/>
                    </svg>
                    <span class="leb-db-btn-label"><?php esc_html_e( 'Create / Repair', 'listing-engine-backend' ); ?></span>
                </button>
            </div>
        </div>

    </div><!-- /.leb-db-grid -->

</div><!-- /#leb-database-page -->

<?php
/**
 * Helper: Render the status badge rows for a DB card.
 * Extracted to avoid code duplication (used in PHP render + AJAX JSON reconstruction in JS).
 *
 * @param array $status ['exists' => bool, 'rows_complete' => bool].
 */
function leb_render_db_card_statuses( array $status ): void {
    // Table Created status.
    if ( $status['exists'] ) {
        echo '<div class="leb-db-status-row">';
        echo '<span class="leb-db-status-label">' . esc_html__( 'Table Created:', 'listing-engine-backend' ) . '</span>';
        echo '<span class="leb-badge leb-badge--success">&#10003; ' . esc_html__( 'Yes', 'listing-engine-backend' ) . '</span>';
        echo '</div>';
    } else {
        echo '<div class="leb-db-status-row">';
        echo '<span class="leb-db-status-label">' . esc_html__( 'Table Created:', 'listing-engine-backend' ) . '</span>';
        echo '<span class="leb-badge leb-badge--error">&#10007; ' . esc_html__( 'Not Created', 'listing-engine-backend' ) . '</span>';
        echo '</div>';
    }

    // Rows Complete status (only meaningful if table exists).
    if ( $status['exists'] ) {
        if ( $status['rows_complete'] ) {
            echo '<div class="leb-db-status-row">';
            echo '<span class="leb-db-status-label">' . esc_html__( 'Rows Complete:', 'listing-engine-backend' ) . '</span>';
            echo '<span class="leb-badge leb-badge--success">&#10003; ' . esc_html__( 'All Present', 'listing-engine-backend' ) . '</span>';
            echo '</div>';
        } else {
            echo '<div class="leb-db-status-row">';
            echo '<span class="leb-db-status-label">' . esc_html__( 'Rows Complete:', 'listing-engine-backend' ) . '</span>';
            echo '<span class="leb-badge leb-badge--warning">&#9888; ' . esc_html__( 'Missing Rows', 'listing-engine-backend' ) . '</span>';
            echo '</div>';
        }
    }
}
?>

<script>
document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    if ( typeof LEB_Ajax === 'undefined' ) {
        console.warn( 'LEB_Ajax is not defined. AJAX functionality may be broken. Check if assets are enqueued correctly.' );
    }

    var ajaxUrl = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce   = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    if ( typeof LEB_Toaster === 'undefined' ) {
        console.warn( 'LEB_Toaster is not defined. UI notifications will not be shown.' );
    }

    /* ── Badge HTML builder (mirrors PHP leb_render_db_card_statuses) ── */
    function lebBuildStatusHtml( tableData ) {
        var html = '';

        // Table Exists.
        html += '<div class="leb-db-status-row">';
        html += '<span class="leb-db-status-label">Table Created:</span>';
        if ( tableData.exists ) {
            html += '<span class="leb-badge leb-badge--success">&#10003; Yes</span>';
        } else {
            html += '<span class="leb-badge leb-badge--error">&#10007; Not Created</span>';
        }
        html += '</div>';

        // Rows Complete (only if table exists).
        if ( tableData.exists ) {
            html += '<div class="leb-db-status-row">';
            html += '<span class="leb-db-status-label">Rows Complete:</span>';
            if ( tableData.rows_complete ) {
                html += '<span class="leb-badge leb-badge--success">&#10003; All Present</span>';
            } else {
                html += '<span class="leb-badge leb-badge--warning">&#9888; Missing Rows</span>';
            }
            html += '</div>';
        }

        return html;
    }

    /* ── Generic: set button loading state ──────────────────── */
    function lebSetBtnLoading( btn, loading ) {
        btn.disabled = loading;
        if ( loading ) {
            btn.classList.add( 'leb-btn-loading' );
        } else {
            btn.classList.remove( 'leb-btn-loading' );
        }
    }

    /* ── Refresh Handler ─────────────────────────────────────── */
    document.querySelectorAll( '.leb-db-btn--refresh' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            var tableKey    = this.getAttribute( 'data-table-key' );
            var statusEl    = document.getElementById( 'leb-db-statuses-' + tableKey );

            lebSetBtnLoading( btn, true );

            var fd = new FormData();
            fd.append( 'action', 'leb_db_status' );
            fd.append( 'nonce',  nonce );

            fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
                .then( function ( r ) { return r.json(); } )
                .then( function ( data ) {
                    lebSetBtnLoading( btn, false );

                    if ( data.success && data.data && data.data.tables ) {
                        data.data.tables.forEach( function ( t ) {
                            if ( t.key === tableKey && statusEl ) {
                                statusEl.innerHTML = lebBuildStatusHtml( t );
                            }
                        } );
                        if ( typeof LEB_Toaster !== 'undefined' ) {
                            LEB_Toaster.show( 'Status refreshed.', 'info' );
                        }
                    } else {
                        if ( typeof LEB_Toaster !== 'undefined' ) {
                            LEB_Toaster.show( 'Could not retrieve status.', 'error' );
                        }
                    }
                } )
                .catch( function () {
                    lebSetBtnLoading( btn, false );
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'Network error. Please try again.', 'error' );
                    }
                } );
        } );
    } );

    /* ── Create / Repair Handler ─────────────────────────────── */
    document.querySelectorAll( '.leb-db-btn--repair' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            var tableKey    = this.getAttribute( 'data-table-key' );
            var statusEl    = document.getElementById( 'leb-db-statuses-' + tableKey );
            var refreshBtn  = document.getElementById( 'leb-db-refresh-' + tableKey );

            lebSetBtnLoading( btn, true );
            if ( refreshBtn ) { refreshBtn.disabled = true; }

            var fd = new FormData();
            fd.append( 'action',    'leb_db_create_repair' );
            fd.append( 'nonce',     nonce );
            fd.append( 'table_key', tableKey );

            fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
                .then( function ( r ) { return r.json(); } )
                .then( function ( data ) {
                    lebSetBtnLoading( btn, false );
                    if ( refreshBtn ) { refreshBtn.disabled = false; }

                    if ( data.success ) {
                        if ( typeof LEB_Toaster !== 'undefined' ) {
                            LEB_Toaster.show( data.data.message || 'Table created / repaired.', 'success' );
                        }
                        // Trigger a status refresh to update the card.
                        if ( refreshBtn ) { refreshBtn.click(); }
                    } else {
                        if ( typeof LEB_Toaster !== 'undefined' ) {
                            LEB_Toaster.show( ( data.data && data.data.message ) ? data.data.message : 'Operation failed.', 'error' );
                        }
                    }
                } )
                .catch( function () {
                    lebSetBtnLoading( btn, false );
                    if ( refreshBtn ) { refreshBtn.disabled = false; }
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'Network error. Please try again.', 'error' );
                    }
                } );
        } );
    } );

} );
</script>
