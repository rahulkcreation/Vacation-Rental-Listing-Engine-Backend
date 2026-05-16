document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    /* ── Config ──────────────────────────────────────────────── */
    var cfg = window.lebLocAECfg || {};
    var isEdit   = cfg.isEdit || false;
    var editId   = cfg.editId || 0;
    var defaultSvgPath = cfg.defaultSvgPath || '';
    var redirectUrl = cfg.redirectUrl || '';
    
    var ajaxUrl  = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce    = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    /* ── DOM ─────────────────────────────────────────────────── */
    var domName          = document.getElementById( 'leb-loc-ae-name' );
    var domSlug          = document.getElementById( 'leb-loc-ae-slug' );
    var domSvgPath       = document.getElementById( 'leb-loc-svg-path' );
    var domAttachmentId  = document.getElementById( 'leb-loc-attachment-id' );
    var domPreviewImg    = document.getElementById( 'leb-loc-svg-preview-img' );
    var domPlaceholder   = document.getElementById( 'leb-loc-svg-placeholder' );
    var domSelectBtn     = document.getElementById( 'leb-loc-svg-select-btn' );
    var domRemoveBtn     = document.getElementById( 'leb-loc-svg-remove-btn' );
    var domBtnLabel      = document.getElementById( 'leb-loc-svg-btn-label' );
    var domSubmitBtn     = document.getElementById( 'leb-loc-ae-submit-btn' );

    if (!domName || !domSubmitBtn) return;

    /* ── Slug Auto-generation ────────────────────────────────── */
    if ( domName && domSlug && ! isEdit ) {
        domName.addEventListener( 'input', function () {
            var val = this.value.trim().toLowerCase();
            val = val.replace( /[^a-z0-9]+/g, '-' ).replace( /^-+|-+$/g, '' );
            domSlug.value = val;
        } );
    }

    /* ── Media Library Integration ───────────────────────────── */
    var lebLocMediaFrame = null;

    if (domSelectBtn) {
        domSelectBtn.addEventListener( 'click', function () {
            // Open or re-open the WP media frame.
            if ( ! lebLocMediaFrame ) {
                if (typeof wp === 'undefined' || !wp.media) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'WordPress Media Library is not loaded.', 'error' );
                    }
                    return;
                }
                
                lebLocMediaFrame = wp.media( {
                    title    : 'Select 24×24 Icon',
                    button   : { text: 'Use This Icon' },
                    library  : { type: ['image/svg+xml', 'image/webp'] },
                    multiple : false
                } );

                lebLocMediaFrame.on( 'select', function () {
                    var attachment = lebLocMediaFrame.state().get( 'selection' ).first().toJSON();

                    // Basic client-side size check (1 MB = 1,048,576 bytes).
                    if ( attachment.filesizeInBytes && attachment.filesizeInBytes > 1048576 ) {
                        if ( typeof LEB_Toaster !== 'undefined' ) {
                            LEB_Toaster.show( 'Icon file size must not exceed 1 MB.', 'error' );
                        }
                        return;
                    }

                    // Update hidden fields.
                    if (domSvgPath) domSvgPath.value      = attachment.url;
                    if (domAttachmentId) domAttachmentId.value = attachment.id;

                    // Update preview.
                    if (domPreviewImg) {
                        domPreviewImg.src      = attachment.url;
                        domPreviewImg.style.display = 'block';
                    }
                    if (domPlaceholder) domPlaceholder.style.display = 'none';

                    // Toggle button labels.
                    if (domBtnLabel) domBtnLabel.textContent = 'Change Icon';
                    if (domRemoveBtn) domRemoveBtn.style.display = 'inline-flex';
                } );
            }

            lebLocMediaFrame.open();
        } );
    }

    if (domRemoveBtn) {
        domRemoveBtn.addEventListener( 'click', function () {
            if (domSvgPath) domSvgPath.value      = '';
            if (domAttachmentId) domAttachmentId.value = '';
            if (domPreviewImg) {
                domPreviewImg.src     = '';
                domPreviewImg.style.display  = 'none';
            }
            if (domPlaceholder) domPlaceholder.style.display = 'flex';
            if (domBtnLabel) domBtnLabel.textContent      = 'Upload / Choose Icon';
            domRemoveBtn.style.display   = 'none';
        } );
    }

    /* ── Pre-fill in Edit mode ───────────────────────────────── */
    if ( isEdit && editId ) {
        var fd = new FormData();
        fd.append( 'action', 'leb_loc_get_location' );
        fd.append( 'nonce',  nonce );
        fd.append( 'id',     editId );

        fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                if ( data.success && data.data && data.data.location ) {
                    var location = data.data.location;
                    domName.value = location.name;
                    if (domSlug) domSlug.value = location.slug;

                    /* If a saved SVG path exists, it might be JSON string like '{"path": "...", "attachment_id": "..."}' */
                    /* Or just the path if we stored it that way. Let's handle both. */
                    var savedSvg = location.svg_path;
                    var svgUrl = '';
                    var attId  = '';

                    try {
                        var parsed = JSON.parse(savedSvg);
                        if (parsed && typeof parsed === 'object') {
                            svgUrl = parsed.path || '';
                            attId  = parsed.attachment_id || '';
                        } else {
                            svgUrl = savedSvg;
                        }
                    } catch (e) {
                        svgUrl = savedSvg;
                    }

                    if ( svgUrl ) {
                        if (domSvgPath) domSvgPath.value = svgUrl;
                        if (domAttachmentId) domAttachmentId.value = attId;
                        if (domPreviewImg) {
                            domPreviewImg.src = svgUrl;
                            domPreviewImg.onerror = function() { this.onerror = null; this.src = defaultSvgPath; };
                            domPreviewImg.style.display = 'block';
                        }
                        if (domPlaceholder) domPlaceholder.style.display = 'none';
                        if (domBtnLabel) domBtnLabel.textContent = 'Change Icon';
                        if (domRemoveBtn) domRemoveBtn.style.display = 'inline-flex';
                    }
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'Could not load location data.', 'error' );
                    }
                }
            } )
            .catch( function () {
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error while loading location.', 'error' );
                }
            } );
    }

    /* ── Submit ──────────────────────────────────────────────── */
    function lebLocSetBtnLoading( loading ) {
        domSubmitBtn.disabled = loading;
        if ( loading ) {
            domSubmitBtn.classList.add( 'leb-loading' );
        } else {
            domSubmitBtn.classList.remove( 'leb-loading' );
        }
    }

    domSubmitBtn.addEventListener( 'click', function () {
        var name          = domName.value.trim();
        var slug          = domSlug ? domSlug.value.trim() : '';
        var svgUrl        = domSvgPath ? domSvgPath.value.trim() : '';
        var attachmentId  = domAttachmentId ? domAttachmentId.value.trim() : '';

        if ( ! name ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Location Name is required.', 'warning' );
            }
            domName.focus();
            return;
        }

        if ( ! slug ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Slug is required.', 'warning' );
            }
            if (domSlug) domSlug.focus();
            return;
        }

        if ( ! svgUrl ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Icon is required.', 'warning' );
            }
            return;
        }

        lebLocSetBtnLoading( true );

        var fd = new FormData();
        fd.append( 'nonce',         nonce );
        fd.append( 'name',          name );
        fd.append( 'slug',          slug );
        fd.append( 'svg_path',      svgUrl );
        fd.append( 'attachment_id', attachmentId );

        if ( isEdit ) {
            fd.append( 'action', 'leb_loc_update_location' );
            fd.append( 'id',     editId );
        } else {
            fd.append( 'action', 'leb_loc_create_location' );
        }

        fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebLocSetBtnLoading( false );
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Done!', 'success' );
                    }
                    setTimeout( function () {
                        if (redirectUrl) window.location.href = redirectUrl;
                    }, 1200 );
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( ( data.data && data.data.message ) ? data.data.message : 'Error saving location.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebLocSetBtnLoading( false );
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error. Please try again.', 'error' );
                }
            } );
    } );

} );
