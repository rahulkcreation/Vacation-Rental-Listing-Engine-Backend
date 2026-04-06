document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    /* ── Config ───────────────────────────────────────────── */
    var cfg      = window.lebTypeAECfg || {};
    var isEdit   = cfg.isEdit || false;
    var editId   = cfg.editId || 0;
    var redirect = cfg.redirectUrl || '';
    
    var ajaxUrl  = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce    = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    /* ── DOM ─────────────────────────────────────────────── */
    var domName      = document.getElementById( 'leb-ae-name' );
    var domSlug      = document.getElementById( 'leb-ae-slug' );
    var domSubmitBtn = document.getElementById( 'leb-ae-submit-btn' );

    if ( ! domName || ! domSubmitBtn ) return;

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
                        if ( redirect ) window.location.href = redirect;
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
