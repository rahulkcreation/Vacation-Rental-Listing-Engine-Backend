/**
 * add-edit-property.js
 *
 * Client-side logic for the Add / Edit Property form.
 * Handles: WP Media Library image upload + drag-reorder,
 * stepper inputs, amenity fetch & selection, type/location
 * dropdown population, calendar-based date blocking,
 * form validation, and AJAX save (create/update).
 *
 * @package ListingEngineBackend
 */
(function () {
    'use strict';

    /* ═══════════════════════════════════════════════════════════
     * STATE
     * ═══════════════════════════════════════════════════════════ */
    const state = {
        id:           0,        // listing_id (0 = new)
        images:       [],       // [{id, url, sort_order}]
        blockedDates: [],       // ['YYYY-MM-DD', …]
        amenityIds:   new Set(),
        calMonth:     new Date().getMonth(),
        calYear:      new Date().getFullYear(),
    };

    /* ═══════════════════════════════════════════════════════════
     * CACHED DOM
     * ═══════════════════════════════════════════════════════════ */
    const F = {
        id:          () => document.getElementById('leb-prop-field-id'),
        title:       () => document.getElementById('leb-prop-field-title'),
        description: () => document.getElementById('leb-prop-field-description'),
        guests:      () => document.getElementById('leb-prop-field-guests'),
        bedroom:     () => document.getElementById('leb-prop-field-bedroom'),
        bed:         () => document.getElementById('leb-prop-field-bed'),
        bathroom:    () => document.getElementById('leb-prop-field-bathroom'),
        price:       () => document.getElementById('leb-prop-field-price'),
        type:        () => document.getElementById('leb-prop-field-type'),
        location:    () => document.getElementById('leb-prop-field-location'),
        imgGrid:     () => document.getElementById('leb-prop-images-grid'),
        amenities:   () => document.getElementById('leb-prop-amenities-grid'),
        calGrid:     () => document.getElementById('leb-prop-cal-grid'),
        calTitle:    () => document.getElementById('leb-prop-cal-title'),
        blockedList: () => document.getElementById('leb-prop-blocked-list'),
    };

    /* ═══════════════════════════════════════════════════════════
     * INIT
     * ═══════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', function () {
        const idField = F.id();
        state.id = idField ? parseInt(idField.value, 10) || 0 : 0;

        // If editing, fetch existing data only after lookups settle.
        if (state.id > 0) {
            Promise.all([loadTypes(), loadLocations(), loadAmenities()]).then(function() {
                fetchListingData();
            });
        } else {
            loadTypes();
            loadLocations();
            loadAmenities();
        }
    });

    /* ═══════════════════════════════════════════════════════════
     * LOOKUP DATA LOADERS
     * ═══════════════════════════════════════════════════════════ */
    function loadTypes() {
        return new Promise(function(resolve) {
            jQuery.post(LEB_Ajax.ajax_url, {
                action: 'leb_listing_get_types_all',
                nonce:  LEB_Ajax.nonce,
            }, function (res) {
                if (res.success) {
                    const sel = F.type();
                    (res.data.items || []).forEach(function (t) {
                        const opt = document.createElement('option');
                        opt.value = t.id;
                        opt.textContent = t.name;
                        sel.appendChild(opt);
                    });
                }
                resolve();
            }).fail(resolve);
        });
    }

    function loadLocations() {
        return new Promise(function(resolve) {
            jQuery.post(LEB_Ajax.ajax_url, {
                action: 'leb_listing_get_locations_all',
                nonce:  LEB_Ajax.nonce,
            }, function (res) {
                if (res.success) {
                    const sel = F.location();
                    (res.data.items || []).forEach(function (l) {
                        const opt = document.createElement('option');
                        opt.value = l.id;
                        opt.textContent = l.name;
                        sel.appendChild(opt);
                    });
                }
                resolve();
            }).fail(resolve);
        });
    }

    function loadAmenities() {
        return new Promise(function(resolve) {
            jQuery.post(LEB_Ajax.ajax_url, {
                action: 'leb_listing_get_amenities_all',
                nonce:  LEB_Ajax.nonce,
            }, function (res) {
                if (res.success) {
                    const grid = F.amenities();
                    grid.innerHTML = '';
                    (res.data.items || []).forEach(function (a) {
                        const icon = a.svg_path
                            ? '<img src="' + escHtml(a.svg_path) + '" alt="">'
                            : '';
                        const div = document.createElement('label');
                        div.className = 'leb-prop-form-amenity-item';
                        div.innerHTML = '<input type="checkbox" class="leb-prop-form-amenity-check" value="' + a.id + '">'
                            + icon
                            + '<span>' + escHtml(a.name) + '</span>';
                        grid.appendChild(div);
                    });

                    // Click / change handler.
                    grid.addEventListener('change', function (e) {
                        if (!e.target.classList.contains('leb-prop-form-amenity-check')) return;
                        const item = e.target.closest('.leb-prop-form-amenity-item');
                        const id = parseInt(e.target.value, 10);
                        if (e.target.checked) {
                            state.amenityIds.add(id);
                            item.classList.add('leb-prop-form-amenity-item--selected');
                        } else {
                            state.amenityIds.delete(id);
                            item.classList.remove('leb-prop-form-amenity-item--selected');
                        }
                    });
                }
                resolve();
            }).fail(resolve);
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * EDIT MODE – FETCH EXISTING DATA
     * ═══════════════════════════════════════════════════════════ */
    function fetchListingData() {
        jQuery.post(LEB_Ajax.ajax_url, {
            action: 'leb_listing_get_listing',
            nonce:  LEB_Ajax.nonce,
            id:     state.id,
        }, function (res) {
            if (!res.success) {
                LEB_Toaster.error(res.data?.message || 'Failed to load property.');
                return;
            }
            const d = res.data.listing;

            // Basic fields.
            F.title().value       = d.title || '';
            F.description().value = d.description || '';
            F.guests().value      = d.guests || 0;
            F.bedroom().value     = d.bedroom || 0;
            F.bed().value         = d.bed || 0;
            F.bathroom().value    = d.bathroom || 0;
            F.price().value       = d.price || '';

            // Dropdowns.
            if (d.type)     F.type().value = d.type;
            if (d.location) F.location().value = d.location;

            // Status radio.
            if (d.status) {
                const radio = document.querySelector('input[name="leb_prop_status"][value="' + d.status + '"]');
                if (radio) radio.checked = true;
            }

            // Images.
            if (d.images) {
                try {
                    const imgData = typeof d.images === 'string' ? JSON.parse(d.images) : d.images;
                    state.images = imgData.map(function (img, i) {
                        return { id: img.attachment_id || 0, url: img.image_url, sort_order: img.sort_order || i };
                    });
                    renderImages();
                } catch (e) { console.error('Image parse error', e); }
            }

            // Amenities.
            if (d.amenities) { 
                let amens = [];
                try {
                    // Try parsing as JSON array first
                    amens = JSON.parse(d.amenities);
                    if (!Array.isArray(amens)) {
                        amens = [amens];
                    }
                } catch (e) {
                    // Fallback: it might be a comma-separated string
                    amens = String(d.amenities).split(',');
                }
                state.amenityIds = new Set(amens.filter(x => x).map(Number));
                syncAmenityCheckboxes();
            }

            // Blocked dates.
            if (d.blocked_dates) {
                try {
                    const dateData = typeof d.blocked_dates === 'string' ? JSON.parse(d.blocked_dates) : d.blocked_dates;
                    state.blockedDates = dateData.map(function (bd) { return bd.blocked_date || bd; });
                    renderBlockedChips();
                    renderCalendar();
                } catch (e) { console.error('Date parse error', e); }
            }
        });
    }

    function syncAmenityCheckboxes() {
        const checks = document.querySelectorAll('.leb-prop-form-amenity-check');
        checks.forEach(function (cb) {
            const id = parseInt(cb.value, 10);
            cb.checked = state.amenityIds.has(id);
            cb.closest('.leb-prop-form-amenity-item').classList.toggle('leb-prop-form-amenity-item--selected', cb.checked);
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * STEPPER INPUTS
     * ═══════════════════════════════════════════════════════════ */
    function bindSteppers() {
        document.querySelectorAll('.leb-prop-form-stepper').forEach(function (stepper) {
            const input = stepper.querySelector('.leb-prop-form-stepper__value');
            stepper.querySelector('.leb-prop-form-stepper__btn--minus').addEventListener('click', function () {
                let v = parseInt(input.value, 10) || 0;
                input.value = Math.max(0, v - 1);
            });
            stepper.querySelector('.leb-prop-form-stepper__btn--plus').addEventListener('click', function () {
                let v = parseInt(input.value, 10) || 0;
                input.value = v + 1;
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * IMAGE UPLOAD (WP MEDIA LIBRARY)
     * ═══════════════════════════════════════════════════════════ */
    function bindImageUpload() {
        const btn = document.getElementById('leb-prop-add-images');
        if (!btn) return;

        btn.addEventListener('click', function () {
            const frame = wp.media({
                title: 'Select Property Images',
                button: { text: 'Add Images' },
                multiple: true,
                library: { type: 'image' },
            });

            frame.on('select', function () {
                const attachments = frame.state().get('selection').toJSON();
                attachments.forEach(function (att) {
                    if (state.images.length >= 10) return;
                    // Avoid duplicates.
                    if (state.images.some(function (img) { return img.id === att.id; })) return;
                    state.images.push({
                        id: att.id,
                        url: att.sizes?.medium?.url || att.url,
                        sort_order: state.images.length,
                    });
                });
                renderImages();
            });
            frame.open();
        });
    }

    function renderImages() {
        const grid = F.imgGrid();
        grid.innerHTML = '';

        state.images.forEach(function (img, idx) {
            const div = document.createElement('div');
            div.className = 'leb-prop-form-image-item';
            div.draggable = true;
            div.dataset.index = idx;

            div.innerHTML = '<img src="' + escHtml(img.url) + '" alt="">'
                + (idx === 0 ? '<span class="leb-prop-form-image-item__badge">Cover</span>' : '')
                + '<button type="button" class="leb-prop-form-image-remove" data-index="' + idx + '">'
                +     '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                + '</button>';

            grid.appendChild(div);
        });

        // Remove handler.
        grid.querySelectorAll('.leb-prop-form-image-remove').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                const i = parseInt(btn.dataset.index, 10);
                state.images.splice(i, 1);
                renderImages();
            });
        });

        // Drag-and-drop reorder.
        bindImageDragDrop();
    }

    function bindImageDragDrop() {
        const grid = F.imgGrid();
        let dragIdx = null;

        grid.querySelectorAll('.leb-prop-form-image-item').forEach(function (item) {
            item.addEventListener('dragstart', function (e) {
                dragIdx = parseInt(item.dataset.index, 10);
                item.classList.add('leb-prop-form-image-item--dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', function () {
                item.classList.remove('leb-prop-form-image-item--dragging');
                dragIdx = null;
            });

            item.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
            });

            item.addEventListener('drop', function (e) {
                e.preventDefault();
                const dropIdx = parseInt(item.dataset.index, 10);
                if (dragIdx === null || dragIdx === dropIdx) return;
                // Swap.
                const tmp = state.images[dragIdx];
                state.images.splice(dragIdx, 1);
                state.images.splice(dropIdx, 0, tmp);
                // Update sort_order.
                state.images.forEach(function (img, i) { img.sort_order = i; });
                renderImages();
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * CALENDAR – DATE BLOCKING
     * ═══════════════════════════════════════════════════════════ */
    function bindCalendar() {
        document.getElementById('leb-prop-cal-prev').addEventListener('click', function () {
            state.calMonth--;
            if (state.calMonth < 0) { state.calMonth = 11; state.calYear--; }
            renderCalendar();
        });

        document.getElementById('leb-prop-cal-next').addEventListener('click', function () {
            state.calMonth++;
            if (state.calMonth > 11) { state.calMonth = 0; state.calYear++; }
            renderCalendar();
        });

        renderCalendar();
    }

    function renderCalendar() {
        const grid  = F.calGrid();
        const title = F.calTitle();
        const year  = state.calYear;
        const month = state.calMonth;

        const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        title.textContent = months[month] + ' ' + year;

        const firstDay   = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const today      = new Date();
        today.setHours(0,0,0,0);

        let html = '';

        // Empty leading cells.
        for (let i = 0; i < firstDay; i++) {
            html += '<span class="leb-prop-form-cal-day leb-prop-form-cal-day--empty"></span>';
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const dateObj  = new Date(year, month, d);
            const dateStr  = formatDate(dateObj);
            const isPast   = dateObj < today;
            const isToday  = dateObj.getTime() === today.getTime();
            const isBlocked = state.blockedDates.includes(dateStr);

            let cls = 'leb-prop-form-cal-day';
            if (isPast)    cls += ' leb-prop-form-cal-day--past';
            if (isToday)   cls += ' leb-prop-form-cal-day--today';
            if (isBlocked) cls += ' leb-prop-form-cal-day--blocked';

            html += '<button type="button" class="' + cls + '" data-date="' + dateStr + '">' + d + '</button>';
        }

        grid.innerHTML = html;

        // Click to toggle.
        grid.querySelectorAll('.leb-prop-form-cal-day:not(.leb-prop-form-cal-day--empty):not(.leb-prop-form-cal-day--past)').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const dt = btn.dataset.date;
                const idx = state.blockedDates.indexOf(dt);
                if (idx >= 0) {
                    state.blockedDates.splice(idx, 1);
                } else {
                    state.blockedDates.push(dt);
                }
                renderCalendar();
                renderBlockedChips();
            });
        });
    }

    function renderBlockedChips() {
        const list = F.blockedList();
        list.innerHTML = '';
        state.blockedDates.sort().forEach(function (dt) {
            const chip = document.createElement('span');
            chip.className = 'leb-prop-form-blocked-chip';
            chip.innerHTML = dt
                + '<button type="button" class="leb-prop-form-blocked-chip__remove" data-date="' + dt + '">'
                +     '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>'
                + '</button>';
            list.appendChild(chip);
        });

        list.querySelectorAll('.leb-prop-form-blocked-chip__remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const dt = btn.dataset.date;
                const idx = state.blockedDates.indexOf(dt);
                if (idx >= 0) state.blockedDates.splice(idx, 1);
                renderCalendar();
                renderBlockedChips();
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
     * SAVE / SUBMIT
     * ═══════════════════════════════════════════════════════════ */
    function bindSaveButtons() {
        document.getElementById('leb-prop-form-save-draft').addEventListener('click', function () {
            save('draft');
        });

        document.getElementById('leb-prop-form-submit').addEventListener('click', function () {
            // Use the currently selected radio value, defaulting to 'published'.
            const statusRadio = document.querySelector('input[name="leb_prop_status"]:checked');
            save(statusRadio ? statusRadio.value : 'published');
        });
    }

    function save(status) {
        // Validation.
        const title = (F.title().value || '').trim();
        if (!title) {
            F.title().classList.add('leb-prop-form-input--error');
            F.title().focus();
            LEB_Toaster.error('Property title is required.');
            return;
        }
        F.title().classList.remove('leb-prop-form-input--error');

        const payload = {
            action:       state.id > 0 ? 'leb_listing_update_listing' : 'leb_listing_create_listing',
            nonce:        LEB_Ajax.nonce,
            title:        title,
            description:  (F.description().value || '').trim(),
            price:        parseInt(F.price().value, 10) || 0,
            guests:       parseInt(F.guests().value, 10) || 0,
            bedroom:      parseInt(F.bedroom().value, 10) || 0,
            bed:          parseInt(F.bed().value, 10) || 0,
            bathroom:     parseInt(F.bathroom().value, 10) || 0,
            type:         parseInt(F.type().value, 10) || 0,
            location:     parseInt(F.location().value, 10) || 0,
            status:       status,
            amenities:    Array.from(state.amenityIds).join(','),
            images:       JSON.stringify(state.images.map(function (img) { return { attachment_id: img.id, image_url: img.url, sort_order: img.sort_order }; })),
            dates:        JSON.stringify(state.blockedDates),
        };

        if (state.id > 0) {
            payload.id = state.id;
        }

        // Disable buttons.
        setBusy(true);

        jQuery.post(LEB_Ajax.ajax_url, payload, function (res) {
            setBusy(false);
            if (res.success) {
                LEB_Toaster.success(res.data.message || 'Property saved.');
                // If newly created, redirect to edit mode.
                if (!state.id && res.data.id) {
                    window.location.href = window.location.href.split('?')[0]
                        + '?page=leb-properties&leb_action=edit&id=' + res.data.id;
                }
            } else {
                LEB_Toaster.error(res.data?.message || 'Save failed.');
            }
        }).fail(function () {
            setBusy(false);
            LEB_Toaster.error('Network error. Please try again.');
        });
    }

    function setBusy(busy) {
        const btns = document.querySelectorAll('#leb-prop-form-save-draft, #leb-prop-form-submit');
        btns.forEach(function (b) { b.disabled = busy; });
    }

    /* ═══════════════════════════════════════════════════════════
     * HELPERS
     * ═══════════════════════════════════════════════════════════ */
    function formatDate(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function escHtml(str) {
        if (!str) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
        return String(str).replace(/[&<>"']/g, function (c) { return map[c]; });
    }

})();
