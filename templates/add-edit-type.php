<?php
/**
 * add-edit-type.php
 *
 * Add / Edit Type form.
 * Matches the design in screen/add-new.html.
 *
 * - Mode is determined by the URL parameter `leb_action`:
 *   - `leb_action=add`  → blank form, Create button.
 *   - `leb_action=edit` → pre-filled form (via AJAX on load), Update button.
 * - Submission handled via AJAX (leb_create_type / leb_update_type).
 *
 * Loaded by leb_render_type_management_page() in the main plugin file.
 *
 * @package ListingEngineBackend
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine mode.
$leb_action  = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : 'add';
$leb_edit_id = ( $leb_action === 'edit' && isset( $_GET['id'] ) ) ? absint( $_GET['id'] ) : 0;
$leb_is_edit = ( $leb_action === 'edit' && $leb_edit_id > 0 );

$leb_back_url   = admin_url( 'admin.php?page=leb-types' );
$leb_card_title = $leb_is_edit
    ? esc_html__( 'Edit Type', 'listing-engine-backend' )
    : esc_html__( 'Add New Type', 'listing-engine-backend' );
$leb_btn_label  = $leb_is_edit
    ? esc_html__( 'Update Type', 'listing-engine-backend' )
    : esc_html__( 'Create Type', 'listing-engine-backend' );
?>
<style>
    /* ── Add / Edit Type Form ────────────────────────────────────────
       Scoped to #leb-add-edit-type to prevent conflicts.
    ─────────────────────────────────────────────────────────────── */

    #leb-add-edit-type {
        font-family: var(--leb-font-family);
        color:       var(--leb-text-color);
        display:     flex;
        justify-content: center;
        padding:     2rem 1rem;
        min-height:  80vh;
        align-items: flex-start;
    }

    /* ── Card ─────────────────────────────────────────────────── */
    #leb-add-edit-type .leb-ae-card {
        background:    var(--leb-white);
        width:         100%;
        max-width:     520px;
        border-radius: var(--leb-radius-md);
        box-shadow:    var(--leb-card-shadow);
        border:        1px solid var(--leb-border-color);
        padding:       2rem;
        display:       flex;
        flex-direction: column;
        gap:           1.5rem;
    }

    /* ── Card Header ──────────────────────────────────────────── */
    #leb-add-edit-type .leb-ae-header {
        display:       flex;
        align-items:   center;
        gap:           0.75rem;
        border-bottom: 1px solid var(--leb-border-default);
        padding-bottom: 1rem;
    }

    #leb-add-edit-type .leb-ae-back-btn {
        width:         36px;
        height:        36px;
        display:       flex;
        align-items:   center;
        justify-content: center;
        background:    var(--leb-bg-light);
        border:        1px solid var(--leb-border-default);
        border-radius: 50%;
        cursor:        pointer;
        color:         var(--leb-secondary-color);
        flex-shrink:   0;
        transition:    background-color var(--leb-transition-fast);
        text-decoration: none;
    }

    #leb-add-edit-type .leb-ae-back-btn:hover {
        background-color: var(--leb-border-default);
    }

    #leb-add-edit-type .leb-ae-back-btn svg {
        width:  18px;
        height: 18px;
    }

    #leb-add-edit-type .leb-ae-card-title {
        font-size:   var(--leb-font-size-xl);
        font-weight: 600;
        color:       var(--leb-secondary-color);
        margin:      0;
        line-height: 1.3;
    }

    /* ── Form Fields ──────────────────────────────────────────── */
    #leb-add-edit-type .leb-ae-field {
        display:        flex;
        flex-direction: column;
        gap:            0.5rem;
    }

    #leb-add-edit-type .leb-ae-label {
        font-size:   var(--leb-font-size-sm);
        font-weight: 600;
        color:       var(--leb-secondary-color);
    }

    #leb-add-edit-type .leb-ae-input {
        width:            100%;
        min-height:       46px;
        border:           1px solid var(--leb-border-default);
        border-radius:    var(--leb-radius);
        background:       var(--leb-bg-light);
        padding:          0.65rem 1rem;
        font-size:        var(--leb-font-size-md);
        font-family:      var(--leb-font-family);
        color:            var(--leb-text-color);
        outline:          none;
        transition:       border-color var(--leb-transition-normal), box-shadow var(--leb-transition-normal);
    }

    #leb-add-edit-type .leb-ae-input::placeholder {
        color: var(--leb-text-muted);
    }

    #leb-add-edit-type .leb-ae-input:hover {
        background: var(--leb-white);
    }

    #leb-add-edit-type .leb-ae-input:focus {
        border-color:     var(--leb-primary-color);
        background:       var(--leb-white);
        box-shadow:       var(--leb-focus-shadow);
    }

    /* ── Submit Button ────────────────────────────────────────── */
    #leb-add-edit-type .leb-ae-submit-btn {
        width:            100%;
        min-height:       48px;
        background:       var(--leb-primary-color);
        color:            var(--leb-white);
        border:           none;
        border-radius:    var(--leb-radius);
        padding:          0.75rem 1rem;
        margin-top:       0.5rem;
        font-size:        var(--leb-font-size-lg);
        font-weight:      600;
        font-family:      var(--leb-font-family);
        cursor:           pointer;
        display:          flex;
        align-items:      center;
        justify-content:  center;
        gap:              8px;
        transition:       opacity var(--leb-transition-fast), transform var(--leb-transition-fast);
    }

    #leb-add-edit-type .leb-ae-submit-btn:hover {
        opacity:   0.9;
        transform: translateY(-1px);
    }

    #leb-add-edit-type .leb-ae-submit-btn:active {
        transform: translateY(0);
        opacity:   1;
    }

    #leb-add-edit-type .leb-ae-submit-btn:disabled {
        opacity: 0.65;
        cursor:  not-allowed;
        transform: none;
    }

    /* Spinner inside button */
    #leb-add-edit-type .leb-ae-btn-spinner {
        width:        18px;
        height:       18px;
        border:       2px solid rgba(255,255,255,0.4);
        border-top-color: var(--leb-white);
        border-radius: 50%;
        animation:    lebSpin 0.65s linear infinite;
        display:      none;
    }

    #leb-add-edit-type .leb-ae-submit-btn.leb-loading .leb-ae-btn-spinner {
        display: block;
    }
</style>

<div id="leb-add-edit-type" class="leb-wrap">

    <article class="leb-ae-card">

        <!-- Card Header -->
        <header class="leb-ae-header">
            <a href="<?php echo esc_url( $leb_back_url ); ?>"
               class="leb-ae-back-btn"
               aria-label="<?php esc_attr_e( 'Back to list', 'listing-engine-backend' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
            </a>
            <h2 class="leb-ae-card-title"><?php echo $leb_card_title; // Already escaped above. ?></h2>
        </header>

        <!-- Type Name -->
        <div class="leb-ae-field">
            <label class="leb-ae-label" for="leb-ae-name">
                <?php esc_html_e( 'Type Name', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-ae-name"
                class="leb-ae-input"
                placeholder="<?php esc_attr_e( 'e.g. Apartment', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- Slug -->
        <div class="leb-ae-field">
            <label class="leb-ae-label" for="leb-ae-slug">
                <?php esc_html_e( 'Slug', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-ae-slug"
                class="leb-ae-input"
                placeholder="<?php esc_attr_e( 'e.g. apartment', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- Submit -->
        <button type="button" class="leb-ae-submit-btn" id="leb-ae-submit-btn">
            <span class="leb-ae-btn-spinner" aria-hidden="true"></span>
            <span class="leb-ae-btn-label"><?php echo $leb_btn_label; // Already escaped above. ?></span>
        </button>

    </article>

</div><!-- /#leb-add-edit-type -->

<script>
document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    /* ── Config ───────────────────────────────────────────── */
    var isEdit   = <?php echo $leb_is_edit ? 'true' : 'false'; ?>;
    var editId   = <?php echo (int) $leb_edit_id; ?>;
    var ajaxUrl  = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce    = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    /* ── DOM ─────────────────────────────────────────────── */
    var domName      = document.getElementById( 'leb-ae-name' );
    var domSlug      = document.getElementById( 'leb-ae-slug' );
    var domSubmitBtn = document.getElementById( 'leb-ae-submit-btn' );

    /* ── Slug generator: converts name → lowercase slug ── */
    var slugGenTimer = null;

    function lebGenerateSlug( name ) {
        return name
            .toLowerCase()
            .trim()
            .replace( /[^a-z0-9\s-]/g, '' )    // Remove non-alphanumeric.
            .replace( /\s+/g, '-' )             // Spaces → hyphens.
            .replace( /-+/g, '-' );             // Collapse multiple hyphens.
    }

    domName.addEventListener( 'input', function () {
        clearTimeout( slugGenTimer );
        // Only auto-fill slug if the user hasn't manually edited it.
        if ( ! domSlug.dataset.manualEdit ) {
            slugGenTimer = setTimeout( function () {
                domSlug.value = lebGenerateSlug( domName.value );
            }, 200 );
        }
    } );

    domSlug.addEventListener( 'input', function () {
        // Convert to lowercase in real time.
        var pos = this.selectionStart;
        this.value = this.value.toLowerCase();
        this.setSelectionRange( pos, pos );
        // Mark as manually edited so auto-gen stops overwriting.
        this.dataset.manualEdit = '1';
    } );

    /* ── Pre-fill in Edit mode ───────────────────────────── */
    if ( isEdit && editId ) {
        var fd = new FormData();
        fd.append( 'action', 'leb_get_type' );
        fd.append( 'nonce',  nonce );
        fd.append( 'id',     editId );

        fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                if ( data.success && data.data && data.data.type ) {
                    var type = data.data.type;
                    domName.value = type.name;
                    domSlug.value = type.slug;
                    // Mark slug as manually set so auto-gen doesn't overwrite.
                    domSlug.dataset.manualEdit = '1';
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'Could not load type data.', 'error' );
                    }
                }
            } )
            .catch( function () {
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error while loading type.', 'error' );
                }
            } );
    }

    /* ── Submit ──────────────────────────────────────────── */
    function lebSetBtnLoading( loading ) {
        domSubmitBtn.disabled = loading;
        if ( loading ) {
            domSubmitBtn.classList.add( 'leb-loading' );
        } else {
            domSubmitBtn.classList.remove( 'leb-loading' );
        }
    }

    domSubmitBtn.addEventListener( 'click', function () {
        var name = domName.value.trim();
        var slug = domSlug.value.trim().toLowerCase();

        if ( ! name ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Type Name is required.', 'warning' );
            }
            domName.focus();
            return;
        }

        if ( ! slug ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Slug is required.', 'warning' );
            }
            domSlug.focus();
            return;
        }

        lebSetBtnLoading( true );

        var fd = new FormData();
        fd.append( 'nonce', nonce );
        fd.append( 'name',  name );
        fd.append( 'slug',  slug );

        if ( isEdit ) {
            fd.append( 'action', 'leb_update_type' );
            fd.append( 'id',     editId );
        } else {
            fd.append( 'action', 'leb_create_type' );
        }

        fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebSetBtnLoading( false );
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Done!', 'success' );
                    }
                    // Redirect back to list after short delay.
                    setTimeout( function () {
                        window.location.href = '<?php echo esc_js( admin_url( 'admin.php?page=leb-types' ) ); ?>';
                    }, 1200 );
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( ( data.data && data.data.message ) ? data.data.message : 'Error saving type.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebSetBtnLoading( false );
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error. Please try again.', 'error' );
                }
            } );
    } );

} );
</script>
