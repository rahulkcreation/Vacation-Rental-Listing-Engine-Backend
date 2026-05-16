document.addEventListener( 'DOMContentLoaded', function () {
    'use strict';

    /* ── Config ──────────────────────────────────────────────── */
    var cfg = window.lebAmenAECfg || {};
    var isEdit   = cfg.isEdit || false;
    var editId   = cfg.editId || 0;
    var defaultSvgPath = cfg.defaultSvgPath || '';
    var redirectUrl = cfg.redirectUrl || '';
    
    var ajaxUrl  = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.ajax_url : '';
    var nonce    = ( typeof LEB_Ajax !== 'undefined' ) ? LEB_Ajax.nonce   : '';

    /* ── DOM ─────────────────────────────────────────────────── */
    var domName          = document.getElementById( 'leb-amen-ae-name' );
    var domSvgPath       = document.getElementById( 'leb-amen-svg-path' );
    var domAttachmentId  = document.getElementById( 'leb-amen-attachment-id' );
    var domPreviewImg    = document.getElementById( 'leb-amen-svg-preview-img' );
    var domPlaceholder   = document.getElementById( 'leb-amen-svg-placeholder' );
    var domSelectBtn     = document.getElementById( 'leb-amen-svg-select-btn' );
    var domRemoveBtn     = document.getElementById( 'leb-amen-svg-remove-btn' );
    var domBtnLabel      = document.getElementById( 'leb-amen-svg-btn-label' );
    var domSubmitBtn     = document.getElementById( 'leb-amen-ae-submit-btn' );

    if (!domName || !domSubmitBtn) return;

    /* ── Media Library Integration ───────────────────────────── */
    var lebAmenMediaFrame = null;

    if (domSelectBtn) {
        domSelectBtn.addEventListener( 'click', function () {
            // Open or re-open the WP media frame.
            if ( ! lebAmenMediaFrame ) {
                if (typeof wp === 'undefined' || !wp.media) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'WordPress Media Library is not loaded.', 'error' );
                    }
                    return;
                }
                
                lebAmenMediaFrame = wp.media( {
                    title    : 'Select 24×24 Icon',
                    button   : { text: 'Use This Icon' },
                    library  : { type: ['image/svg+xml', 'image/webp'] },
                    multiple : false
                } );

                lebAmenMediaFrame.on( 'select', function () {
                    var attachment = lebAmenMediaFrame.state().get( 'selection' ).first().toJSON();

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

            lebAmenMediaFrame.open();
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
        fd.append( 'action', 'leb_amen_get_amenity' );
        fd.append( 'nonce',  nonce );
        fd.append( 'id',     editId );

        fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                if ( data.success && data.data && data.data.amenity ) {
                    var amenity = data.data.amenity;
                    domName.value = amenity.name;

                    // If a saved SVG path exists, show it in the preview.
                    if ( amenity.svg_path ) {
                        if (domSvgPath) domSvgPath.value = amenity.svg_path;
                        if (domPreviewImg) {
                            domPreviewImg.src = amenity.svg_path;
                            domPreviewImg.onerror = function() { this.onerror = null; this.src = defaultSvgPath; };
                            domPreviewImg.style.display = 'block';
                        }
                        if (domPlaceholder) domPlaceholder.style.display = 'none';
                        if (domBtnLabel) domBtnLabel.textContent = 'Change Icon';
                        if (domRemoveBtn) domRemoveBtn.style.display = 'inline-flex';
                    }
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( 'Could not load amenity data.', 'error' );
                    }
                }
            } )
            .catch( function () {
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error while loading amenity.', 'error' );
                }
            } );
    }

    /* ── Submit ──────────────────────────────────────────────── */
    function lebAmenSetBtnLoading( loading ) {
        domSubmitBtn.disabled = loading;
        if ( loading ) {
            domSubmitBtn.classList.add( 'leb-loading' );
        } else {
            domSubmitBtn.classList.remove( 'leb-loading' );
        }
    }

    domSubmitBtn.addEventListener( 'click', function () {
        var name          = domName.value.trim();
        var svgPath       = domSvgPath ? domSvgPath.value.trim() : '';
        var attachmentId  = domAttachmentId ? domAttachmentId.value.trim() : '';

        if ( ! name ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Amenity Name is required.', 'warning' );
            }
            domName.focus();
            return;
        }

        if ( ! svgPath ) {
            if ( typeof LEB_Toaster !== 'undefined' ) {
                LEB_Toaster.show( 'Icon is required.', 'warning' );
            }
            return;
        }

        lebAmenSetBtnLoading( true );

        var fd = new FormData();
        fd.append( 'nonce',         nonce );
        fd.append( 'name',          name );
        fd.append( 'svg_path',      svgPath );
        fd.append( 'attachment_id', attachmentId );

        if ( isEdit ) {
            fd.append( 'action', 'leb_amen_update_amenity' );
            fd.append( 'id',     editId );
        } else {
            fd.append( 'action', 'leb_amen_create_amenity' );
        }

        fetch( ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' } )
            .then( function ( r ) { return r.json(); } )
            .then( function ( data ) {
                lebAmenSetBtnLoading( false );
                if ( data.success ) {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( data.data.message || 'Done!', 'success' );
                    }
                    // Redirect back to amenities list after short delay.
                    setTimeout( function () {
                        if (redirectUrl) window.location.href = redirectUrl;
                    }, 1200 );
                } else {
                    if ( typeof LEB_Toaster !== 'undefined' ) {
                        LEB_Toaster.show( ( data.data && data.data.message ) ? data.data.message : 'Error saving amenity.', 'error' );
                    }
                }
            } )
            .catch( function () {
                lebAmenSetBtnLoading( false );
                if ( typeof LEB_Toaster !== 'undefined' ) {
                    LEB_Toaster.show( 'Network error. Please try again.', 'error' );
                }
            } );
    } );

} );
