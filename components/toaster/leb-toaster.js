/**
 * leb-toaster.js
 *
 * Global Toaster notification system for the Listing Engine Backend plugin.
 * Exposes the LEB_Toaster object with a public show() method.
 *
 * Usage:
 *   LEB_Toaster.show('Operation completed!', 'success');
 *   LEB_Toaster.show('Something went wrong.', 'error');
 *   LEB_Toaster.show('Check your input.', 'warning');
 *   LEB_Toaster.show('Did you know?', 'info');
 *
 * Types: 'success' | 'error' | 'warning' | 'info'
 * Auto-dismisses after 4 seconds.
 *
 * @package ListingEngineBackend
 */

/* global document */

var LEB_Toaster = ( function () {

    'use strict';

    // ── Private State ──────────────────────────────────────────────
    var _toastEl     = null;   // Cached DOM element.
    var _dismissTimer = null;  // Active auto-dismiss timer.

    // SVG icons for each toast type (inline, no external dependency).
    var _icons = {
        success: '<svg class="leb-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>',
        error:   '<svg class="leb-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>',
        warning: '<svg class="leb-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        info:    '<svg class="leb-toast__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
    };

    // ── Private: DOM Builder ────────────────────────────────────────

    /**
     * Create and inject the toast DOM element (once).
     * The element is reused on subsequent calls.
     *
     * @return {HTMLElement} The toast element.
     */
    function _getOrCreateElement() {
        if ( _toastEl ) {
            return _toastEl;
        }

        _toastEl = document.createElement( 'div' );
        _toastEl.setAttribute( 'role', 'alert' );
        _toastEl.setAttribute( 'aria-live', 'assertive' );
        _toastEl.setAttribute( 'aria-atomic', 'true' );
        _toastEl.className = 'leb-toast';
        _toastEl.innerHTML = [
            '<span class="leb-toast__icon-wrap"></span>',
            '<span class="leb-toast__message"></span>',
            '<button class="leb-toast__close" aria-label="Close notification">&times;</button>',
        ].join( '' );

        // Close on button click.
        _toastEl.querySelector( '.leb-toast__close' ).addEventListener( 'click', function () {
            _hide();
        } );

        document.body.appendChild( _toastEl );
        return _toastEl;
    }

    // ── Private: Hide ───────────────────────────────────────────────

    /**
     * Animate the toast out and then hide it.
     */
    function _hide() {
        if ( ! _toastEl ) {
            return;
        }

        clearTimeout( _dismissTimer );
        _toastEl.classList.add( 'leb-toast--hiding' );

        // Remove after animation completes (300 ms matches CSS duration).
        setTimeout( function () {
            if ( _toastEl ) {
                _toastEl.classList.remove( 'leb-toast--visible', 'leb-toast--hiding' );
                _toastEl.classList.remove(
                    'leb-toast--success',
                    'leb-toast--error',
                    'leb-toast--warning',
                    'leb-toast--info'
                );
                _toastEl.style.display = '';
            }
        }, 320 );
    }

    // ── Public API ──────────────────────────────────────────────────

    /**
     * Display a toast notification.
     *
     * @param {string} message  The text to display.
     * @param {string} [type]   'success' | 'error' | 'warning' | 'info'. Defaults to 'info'.
     * @param {number} [duration] Auto-dismiss delay in milliseconds. Defaults to 4000.
     */
    function show( message, type, duration ) {
        type     = type     || 'info';
        duration = duration || 4000;

        var el = _getOrCreateElement();

        // Clear any running timer so stacking calls reset the countdown.
        clearTimeout( _dismissTimer );

        // Remove previous type classes.
        el.classList.remove(
            'leb-toast--success',
            'leb-toast--error',
            'leb-toast--warning',
            'leb-toast--info',
            'leb-toast--hiding'
        );

        // Apply type class.
        el.classList.add( 'leb-toast--' + type );

        // Set icon.
        var iconWrap = el.querySelector( '.leb-toast__icon-wrap' );
        iconWrap.innerHTML = _icons[ type ] || _icons.info;

        // Set message text (text-only for XSS safety).
        el.querySelector( '.leb-toast__message' ).textContent = message;

        // Show.
        el.style.display = 'flex';

        // Force reflow so the fade-in animation replays.
        void el.offsetWidth;
        el.classList.add( 'leb-toast--visible' );

        // Auto-dismiss.
        _dismissTimer = setTimeout( function () {
            _hide();
        }, duration );
    }

    // ── Return Public Interface ─────────────────────────────────────
    return {
        show:    show,
        hide:    _hide,
        success: function ( m, d ) { show( m, 'success', d ); },
        error:   function ( m, d ) { show( m, 'error',   d ); },
        info:    function ( m, d ) { show( m, 'info',    d ); },
        warning: function ( m, d ) { show( m, 'warning', d ); },
    };

} )();
