/**
 * leb-confirmation.js
 *
 * Global JavaScript component for displaying premium confirmation modals.
 *
 * @package ListingEngineBackend
 * @version 1.2.0
 */

(function (window, document) {
    'use strict';

    /**
     * LEB_Confirm Singleton
     */
    var LEB_Confirm = {
        overlay: null,
        modal: null,
        onConfirm: null,
        onCancel: null,

        /**
         * Initialize the modal HTML structure once.
         */
        init: function () {
            if (this.overlay) return;

            var html = [
                '<div class="leb-confirm-overlay" id="leb-confirm-overlay">',
                '  <div class="leb-confirm-modal">',
                '    <div class="leb-confirm-icon-box leb-warning" id="leb-confirm-icon">',
                '        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">',
                '            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>',
                '            <line x1="12" y1="9" x2="12" y2="13"/>',
                '            <line x1="12" y1="17" x2="12" y2="17.01"/>',
                '        </svg>',
                '    </div>',
                '    <h2 class="leb-confirm-title" id="leb-confirm-title">Confirm Action</h2>',
                '    <p class="leb-confirm-message" id="leb-confirm-message">Are you sure you want to proceed?</p>',
                '    <div class="leb-confirm-footer">',
                '      <button class="leb-confirm-btn leb-confirm-btn-cancel" id="leb-confirm-cancel">Cancel</button>',
                '      <button class="leb-confirm-btn leb-confirm-btn-confirm" id="leb-confirm-ok">Delete Entry</button>',
                '    </div>',
                '  </div>',
                '</div>'
            ].join('');

            var temp = document.createElement('div');
            temp.innerHTML = html;
            this.overlay = temp.firstChild;
            document.body.appendChild(this.overlay);

            // Bind events.
            var self = this;
            document.getElementById('leb-confirm-cancel').onclick = function () { self.hide(); if (self.onCancel) self.onCancel(); };
            document.getElementById('leb-confirm-ok').onclick     = function () { self.hide(); if (self.onConfirm) self.onConfirm(); };

            // Hide on outside click.
            this.overlay.onclick = function (e) {
                if (e.target === self.overlay) {
                    self.hide();
                    if (self.onCancel) self.onCancel();
                }
            };
        },

        /**
         * Display the confirmation modal.
         *
         * @param {Object} settings { title, message, confirmText, cancelText, onConfirm, onCancel, type }
         */
        show: function (settings) {
            this.init();

            settings = settings || {};

            var titleEl   = document.getElementById('leb-confirm-title');
            var messageEl = document.getElementById('leb-confirm-message');
            var okBtn     = document.getElementById('leb-confirm-ok');
            var cancelBtn = document.getElementById('leb-confirm-cancel');
            var iconBox   = document.getElementById('leb-confirm-icon');

            if (titleEl)   titleEl.textContent   = settings.title   || 'Confirm Action';
            if (messageEl) messageEl.textContent = settings.message || 'Are you sure?';
            if (okBtn)     okBtn.textContent     = settings.confirmText || 'Confirm';
            if (cancelBtn) cancelBtn.textContent = settings.cancelText  || 'Cancel';

            this.onConfirm = settings.onConfirm || null;
            this.onCancel  = settings.onCancel  || null;

            // Handle styling based on type (warning, info, etc.)
            if (iconBox) {
                iconBox.className = 'leb-confirm-icon-box ' + (settings.type || 'leb-warning');
            }

            // Show.
            this.overlay.classList.add('leb-active');
            document.body.style.overflow = 'hidden'; // Prevent scroll.
        },

        /**
         * Hide the modal.
         */
        hide: function () {
            if (this.overlay) {
                this.overlay.classList.remove('leb-active');
            }
            document.body.style.overflow = ''; // Restore scroll.
        }
    };

    // Expose globally.
    window.LEB_Confirm = LEB_Confirm;

})(window, document);
