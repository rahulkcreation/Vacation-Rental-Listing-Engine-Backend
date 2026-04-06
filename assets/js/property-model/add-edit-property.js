/**
 * add-edit-property.js
 *
 * Handles the premium Add / Edit Property form.
 * - WP Media Library image upload + drag-reorder
 * - Custom multi-select (amenities) & single-select (location, type, status)
 * - Responsive dual/single-month blocked-dates calendar
 * - AJAX save (create or update) with toaster & confirmation feedback
 *
 * All DOM selectors match the leb-aep- scoped HTML template.
 *
 * @package ListingEngineBackend
 */
(function () {
    'use strict';

    /* ══════════════════════════════════════════════════════════════
       STATE
    ══════════════════════════════════════════════════════════════ */
    const state = {
        id:           0,
        images:       [],               // [{id, url, sort_order}]
        blockedDates: new Set(),        // Set<'YYYY-MM-DD'>
        amenityIds:   new Set(),        // Set<Number>
        typeId:       '',
        locationId:   '',
        status:       'draft',
        calBase:      new Date(),       // first day shown
        isMobile:     window.innerWidth < 992,
        mobileOffset: 0,
    };
    state.calBase.setDate(1);

    /* name tables for calendar */
    const MONTH_NAMES = ['January','February','March','April','May','June',
                         'July','August','September','October','November','December'];
    const DAY_NAMES   = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

    /* amenity id→name lookup (populated when amenities load) */
    const amenityNames = {};

    /* ══════════════════════════════════════════════════════════════
       DOM REFERENCES
    ══════════════════════════════════════════════════════════════ */
    const $ = (id) => document.getElementById(id);

    const DOM = {
        form:           $('propertyForm'),
        propId:         $('leb-prop-field-id'),
        title:          $('propertyTitle'),
        description:    $('propertyDescription'),
        guests:         $('guests'),
        bedrooms:       $('bedrooms'),
        beds:           $('beds'),
        bathrooms:      $('bathrooms'),
        price:          $('pricePerNight'),
        imageGrid:      $('imagePreviewGrid'),
        uploadArea:     $('uploadArea'),
        submitBtn:      $('submitBtn'),
        // Amenities
        amenTrigger:    $('amenitiesTrigger'),
        amenDropdown:   $('amenitiesDropdown'),
        amenTags:       $('amenitiesSelectedTags'),
        // Location
        locTrigger:     $('locationTrigger'),
        locDropdown:    $('locationDropdown'),
        locDisplay:     $('locationSelectedDisplay'),
        // Type
        typeTrigger:    $('propertyTypeTrigger'),
        typeDropdown:   $('propertyTypeDropdown'),
        typeDisplay:    $('propertyTypeSelectedDisplay'),
        // Status
        statusTrigger:  $('statusTrigger'),
        statusDropdown: $('statusDropdown'),
        statusDisplay:  $('statusSelectedDisplay'),
        // Calendar
        calNavBar:      $('calendarNavBar'),
        calNavTitle:    $('calendarNavTitle'),
        calMobileNav:   $('mobileCalendarNav'),
        calMobileTitle: $('mobileMonthTitle'),
        calMonths:      $('calendarMonthsWrapper'),
        prevDesktop:    $('prevMonthDesktop'),
        nextDesktop:    $('nextMonthDesktop'),
        prevMobile:     $('prevMonthMobile'),
        nextMobile:     $('nextMonthMobile'),
        datesSummary:   $('selectedDatesSummary'),
        datesCount:     $('selectedDatesCount'),
        datesChips:     $('selectedDatesChips'),
    };

    /* ══════════════════════════════════════════════════════════════
       INIT
    ══════════════════════════════════════════════════════════════ */
    document.addEventListener('DOMContentLoaded', function () {
        state.id = DOM.propId ? (parseInt(DOM.propId.value, 10) || 0) : 0;

        blockDecimals();
        bindImageUpload();
        bindDropdownTriggers();
        renderCalendar();
        bindCalendarNav();
        bindFormSubmit();

        window.addEventListener('resize', handleResize);

        if (state.id > 0) {
            Promise.all([loadTypes(), loadLocations(), loadAmenities()]).then(fetchListing);
        } else {
            loadTypes();
            loadLocations();
            loadAmenities();
        }
    });

    /* ══════════════════════════════════════════════════════════════
       NUMERIC INPUT: block decimals on integer fields
    ══════════════════════════════════════════════════════════════ */
    function blockDecimals() {
        ['guests','bedrooms','beds','bathrooms'].forEach(function (id) {
            const el = $(id);
            if (!el) return;
            el.addEventListener('keydown', function (e) {
                if (e.key === '.' || e.key === ',') e.preventDefault();
            });
            el.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        });
    }

    /* ══════════════════════════════════════════════════════════════
       LOOKUP LOADERS (AJAX)
    ══════════════════════════════════════════════════════════════ */
    function loadTypes() {
        return new Promise(function (resolve) {
            jQuery.post(LEB_Ajax.ajax_url, { action: 'leb_listing_get_types_all', nonce: LEB_Ajax.nonce }, function (res) {
                if (res.success) {
                    DOM.typeDropdown.innerHTML = '';
                    (res.data.items || []).forEach(function (t) {
                        const div = buildSingleOption(t.id, t.name, function () {
                            state.typeId = t.id;
                            DOM.typeDisplay.innerHTML = '<span class="leb-aep-selected-single">' + esc(t.name) + '</span>';
                            highlightSelected(DOM.typeDropdown, t.id);
                            closeDropdown(DOM.typeTrigger, DOM.typeDropdown);
                        });
                        DOM.typeDropdown.appendChild(div);
                    });
                }
                resolve();
            }).fail(resolve);
        });
    }

    function loadLocations() {
        return new Promise(function (resolve) {
            jQuery.post(LEB_Ajax.ajax_url, { action: 'leb_listing_get_locations_all', nonce: LEB_Ajax.nonce }, function (res) {
                if (res.success) {
                    DOM.locDropdown.innerHTML = '';
                    (res.data.items || []).forEach(function (l) {
                        const div = buildSingleOption(l.id, l.name, function () {
                            state.locationId = l.id;
                            DOM.locDisplay.innerHTML = '<span class="leb-aep-selected-single">' + esc(l.name) + '</span>';
                            highlightSelected(DOM.locDropdown, l.id);
                            closeDropdown(DOM.locTrigger, DOM.locDropdown);
                        });
                        DOM.locDropdown.appendChild(div);
                    });
                }
                resolve();
            }).fail(resolve);
        });
    }

    function loadAmenities() {
        return new Promise(function (resolve) {
            jQuery.post(LEB_Ajax.ajax_url, { action: 'leb_listing_get_amenities_all', nonce: LEB_Ajax.nonce }, function (res) {
                if (res.success) {
                    DOM.amenDropdown.innerHTML = '';
                    (res.data.items || []).forEach(function (a) {
                        amenityNames[a.id] = a.name;
                        const div = document.createElement('div');
                        div.className = 'leb-aep-select-option';
                        div.dataset.value = a.id;
                        div.setAttribute('role', 'option');
                        div.innerHTML = '<span class="leb-aep-checkbox"></span><span>' + esc(a.name) + '</span>';
                        div.addEventListener('click', function (e) {
                            e.stopPropagation();
                            const id = parseInt(a.id, 10);
                            if (state.amenityIds.has(id)) {
                                state.amenityIds.delete(id);
                                div.classList.remove('selected');
                                div.querySelector('.leb-aep-checkbox').textContent = '';
                            } else {
                                state.amenityIds.add(id);
                                div.classList.add('selected');
                                div.querySelector('.leb-aep-checkbox').textContent = '✓';
                            }
                            renderAmenityTags();
                        });
                        DOM.amenDropdown.appendChild(div);
                    });
                }
                resolve();
            }).fail(resolve);
        });
    }

    /* ══════════════════════════════════════════════════════════════
       DROPDOWN HELPERS
    ══════════════════════════════════════════════════════════════ */
    function buildSingleOption(value, label, onClick) {
        const div = document.createElement('div');
        div.className = 'leb-aep-select-option leb-aep-single-option';
        div.dataset.value = value;
        div.setAttribute('role', 'option');
        div.textContent = label;
        div.addEventListener('click', function (e) { e.stopPropagation(); onClick(); });
        return div;
    }

    function highlightSelected(dropdown, value) {
        dropdown.querySelectorAll('.leb-aep-single-option').forEach(function (el) {
            el.classList.toggle('selected', String(el.dataset.value) === String(value));
        });
    }

    function closeDropdown(trigger, dropdown) {
        trigger.classList.remove('open');
        trigger.setAttribute('aria-expanded', 'false');
        dropdown.classList.remove('open');
    }

    function closeAllDropdowns() {
        [
            [DOM.amenTrigger, DOM.amenDropdown],
            [DOM.locTrigger,  DOM.locDropdown],
            [DOM.typeTrigger, DOM.typeDropdown],
            [DOM.statusTrigger, DOM.statusDropdown],
        ].forEach(function (pair) { closeDropdown(pair[0], pair[1]); });
    }

    function toggleDropdown(trigger, dropdown) {
        const isOpen = dropdown.classList.contains('open');
        closeAllDropdowns();
        if (!isOpen) {
            trigger.classList.add('open');
            trigger.setAttribute('aria-expanded', 'true');
            dropdown.classList.add('open');
        }
    }

    function bindDropdownTriggers() {
        [
            [DOM.amenTrigger, DOM.amenDropdown],
            [DOM.locTrigger,  DOM.locDropdown],
            [DOM.typeTrigger, DOM.typeDropdown],
            [DOM.statusTrigger, DOM.statusDropdown],
        ].forEach(function (pair) {
            pair[0].addEventListener('click', function (e) {
                e.stopPropagation();
                toggleDropdown(pair[0], pair[1]);
            });
            pair[0].addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); pair[0].click(); }
            });
        });

        // Status options
        DOM.statusDropdown.querySelectorAll('.leb-aep-status-option').forEach(function (opt) {
            opt.addEventListener('click', function (e) {
                e.stopPropagation();
                state.status = opt.dataset.value;
                DOM.statusDropdown.querySelectorAll('.leb-aep-status-option').forEach(function (o) { o.classList.remove('selected'); });
                opt.classList.add('selected');
                DOM.statusDisplay.innerHTML = opt.innerHTML;
                closeDropdown(DOM.statusTrigger, DOM.statusDropdown);
            });
        });

        // Close on outside click
        document.addEventListener('click', function () { closeAllDropdowns(); });
    }

    /* ══════════════════════════════════════════════════════════════
       AMENITY TAGS
    ══════════════════════════════════════════════════════════════ */
    function renderAmenityTags() {
        DOM.amenTags.innerHTML = '';
        if (state.amenityIds.size === 0) {
            DOM.amenTags.innerHTML = '<span class="leb-aep-placeholder">Select amenities…</span>';
            return;
        }
        state.amenityIds.forEach(function (id) {
            const name = amenityNames[id] || ('Amenity ' + id);
            const tag = document.createElement('span');
            tag.className = 'leb-aep-tag';
            tag.innerHTML = esc(name) + '<span class="leb-aep-tag-remove" data-id="' + id + '">&times;</span>';
            tag.querySelector('.leb-aep-tag-remove').addEventListener('click', function (e) {
                e.stopPropagation();
                const rid = parseInt(this.dataset.id, 10);
                state.amenityIds.delete(rid);
                // uncheck in dropdown
                const opt = DOM.amenDropdown.querySelector('[data-value="' + rid + '"]');
                if (opt) { opt.classList.remove('selected'); opt.querySelector('.leb-aep-checkbox').textContent = ''; }
                renderAmenityTags();
            });
            DOM.amenTags.appendChild(tag);
        });
    }

    /* ══════════════════════════════════════════════════════════════
       IMAGE UPLOAD (WP MEDIA)
    ══════════════════════════════════════════════════════════════ */
    function bindImageUpload() {
        if (!DOM.uploadArea) return;

        /* Click → wp.media frame */
        DOM.uploadArea.addEventListener('click', openMediaLibrary);
        DOM.uploadArea.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openMediaLibrary(); }
        });

        /* Drag & drop */
        DOM.uploadArea.addEventListener('dragover', function (e) {
            e.preventDefault(); this.classList.add('drag-over');
        });
        DOM.uploadArea.addEventListener('dragleave', function () { this.classList.remove('drag-over'); });
        DOM.uploadArea.addEventListener('drop', function (e) {
            e.preventDefault(); this.classList.remove('drag-over');
            // drag-drop not available in WP media; just open the frame
            openMediaLibrary();
        });
    }

    function openMediaLibrary() {
        if (typeof wp === 'undefined' || !wp.media) { LEB_Toaster.error('Media library unavailable.'); return; }
        const frame = wp.media({
            title:   'Select Property Images',
            button:  { text: 'Add Images' },
            multiple: true,
            library:  { type: 'image' },
        });
        frame.on('select', function () {
            const attachments = frame.state().get('selection').toJSON();
            attachments.forEach(function (att) {
                if (state.images.length >= 10) return;
                if (state.images.some(function (img) { return img.id === att.id; })) return;
                state.images.push({
                    id:         att.id,
                    url:        (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url,
                    sort_order: state.images.length,
                });
            });
            renderImages();
        });
        frame.open();
    }

    function renderImages() {
        DOM.imageGrid.innerHTML = '';
        state.images.forEach(function (img, idx) {
            const item = document.createElement('div');
            item.className = 'leb-aep-image-item';
            item.draggable = true;
            item.dataset.index = idx;

            const imgEl = document.createElement('img');
            imgEl.src = img.url;
            imgEl.alt = 'Property image ' + (idx + 1);
            item.appendChild(imgEl);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'leb-aep-image-remove';
            btn.setAttribute('aria-label', 'Remove image');
            btn.innerHTML = '&times;';
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                state.images.splice(idx, 1);
                state.images.forEach(function (img, i) { img.sort_order = i; });
                renderImages();
            });
            item.appendChild(btn);
            DOM.imageGrid.appendChild(item);
        });
        bindDragDrop();
    }

    function bindDragDrop() {
        let dragIdx = null;
        DOM.imageGrid.querySelectorAll('.leb-aep-image-item').forEach(function (item) {
            item.addEventListener('dragstart', function (e) {
                dragIdx = parseInt(item.dataset.index, 10);
                e.dataTransfer.effectAllowed = 'move';
            });
            item.addEventListener('dragover', function (e) { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; });
            item.addEventListener('drop', function (e) {
                e.preventDefault();
                const dropIdx = parseInt(item.dataset.index, 10);
                if (dragIdx === null || dragIdx === dropIdx) return;
                const tmp = state.images.splice(dragIdx, 1)[0];
                state.images.splice(dropIdx, 0, tmp);
                state.images.forEach(function (img, i) { img.sort_order = i; });
                renderImages();
            });
        });
    }

    /* ══════════════════════════════════════════════════════════════
       CALENDAR
    ══════════════════════════════════════════════════════════════ */
    function renderCalendar() {
        DOM.calMonths.innerHTML = '';
        const baseY = state.calBase.getFullYear();
        const baseM = state.calBase.getMonth();

        if (state.isMobile) {
            const d = new Date(baseY, baseM + state.mobileOffset, 1);
            DOM.calMonths.innerHTML = buildMonthHTML(d.getFullYear(), d.getMonth(), true);
            DOM.calMobileTitle.textContent = MONTH_NAMES[d.getMonth()] + ' ' + d.getFullYear();
        } else {
            const nextY = baseM === 11 ? baseY + 1 : baseY;
            const nextM = baseM === 11 ? 0 : baseM + 1;
            DOM.calMonths.innerHTML = buildMonthHTML(baseY, baseM, true) + buildMonthHTML(nextY, nextM, true);
            DOM.calNavTitle.textContent = MONTH_NAMES[baseM] + ' ' + baseY + ' — ' + MONTH_NAMES[nextM] + ' ' + nextY;
        }

        // Bind day clicks
        DOM.calMonths.querySelectorAll('.leb-aep-cal-day:not(.leb-aep-cal-day--disabled):not(.leb-aep-cal-day--empty)').forEach(function (cell) {
            cell.addEventListener('click', function () {
                const key = cell.dataset.date;
                if (state.blockedDates.has(key)) { state.blockedDates.delete(key); }
                else { state.blockedDates.add(key); }
                renderCalendar();
                renderDatesSummary();
            });
        });

        renderDatesSummary();
    }

    function buildMonthHTML(year, month, active) {
        const firstDay    = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const now         = new Date(); now.setHours(0, 0, 0, 0);

        let html = '<div class="leb-aep-cal-month' + (active ? ' active' : '') + '" data-year="' + year + '" data-month="' + month + '">';
        html += '<div class="leb-aep-cal-month-header">' + MONTH_NAMES[month] + ' ' + year + '</div>';

        // Day names
        html += '<div class="leb-aep-cal-day-names">';
        DAY_NAMES.forEach(function (d) { html += '<div class="leb-aep-cal-day-name">' + d + '</div>'; });
        html += '</div>';

        // Days
        html += '<div class="leb-aep-cal-days">';
        for (let i = 0; i < firstDay; i++) {
            html += '<div class="leb-aep-cal-day leb-aep-cal-day--empty"></div>';
        }
        for (let d = 1; d <= daysInMonth; d++) {
            const date    = new Date(year, month, d);
            const key     = dateKey(year, month, d);
            const isToday = (date.getFullYear() === now.getFullYear() && date.getMonth() === now.getMonth() && date.getDate() === now.getDate());
            const isPast  = date < now;
            const isSel   = state.blockedDates.has(key);
            let cls       = 'leb-aep-cal-day';
            if (isToday) cls += ' leb-aep-cal-day--today';
            if (isPast)  cls += ' leb-aep-cal-day--disabled';
            if (isSel)   cls += ' leb-aep-cal-day--selected';
            const tab = isPast ? 'tabindex="-1"' : 'tabindex="0"';
            html += '<div class="' + cls + '" data-date="' + key + '" ' + tab + ' role="gridcell">' + d + '</div>';
        }
        html += '</div></div>';
        return html;
    }

    function dateKey(y, m, d) {
        return y + '-' + String(m + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
    }

    function formatDate(key) {
        const [y, m, d] = key.split('-');
        return new Date(parseInt(y), parseInt(m) - 1, parseInt(d))
            .toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function renderDatesSummary() {
        if (state.blockedDates.size === 0) {
            DOM.datesSummary.style.display = 'none';
            return;
        }
        DOM.datesSummary.style.display = 'block';
        DOM.datesCount.textContent = state.blockedDates.size + ' date(s) blocked';
        DOM.datesChips.innerHTML = '';

        const sorted = Array.from(state.blockedDates).sort();
        sorted.forEach(function (key) {
            const chip = document.createElement('span');
            chip.className = 'leb-aep-date-chip';
            chip.innerHTML = formatDate(key) + '<span class="leb-aep-chip-remove" data-date="' + key + '">&times;</span>';
            chip.querySelector('.leb-aep-chip-remove').addEventListener('click', function () {
                state.blockedDates.delete(this.dataset.date);
                renderCalendar();
            });
            DOM.datesChips.appendChild(chip);
        });

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'leb-aep-clear-all-btn';
        clearBtn.textContent = 'Clear All';
        clearBtn.addEventListener('click', function () {
            state.blockedDates.clear();
            renderCalendar();
        });
        DOM.datesChips.appendChild(clearBtn);
    }

    function bindCalendarNav() {
        DOM.prevDesktop.addEventListener('click', function () {
            state.calBase.setMonth(state.calBase.getMonth() - 1);
            renderCalendar();
        });
        DOM.nextDesktop.addEventListener('click', function () {
            state.calBase.setMonth(state.calBase.getMonth() + 1);
            renderCalendar();
        });
        DOM.prevMobile.addEventListener('click', function () {
            state.mobileOffset--;
            renderCalendar();
        });
        DOM.nextMobile.addEventListener('click', function () {
            state.mobileOffset++;
            renderCalendar();
        });
    }

    function handleResize() {
        const wasMobile = state.isMobile;
        state.isMobile = window.innerWidth < 992;
        if (wasMobile !== state.isMobile) {
            state.mobileOffset = 0;
            renderCalendar();
        }
    }

    /* ══════════════════════════════════════════════════════════════
       EDIT MODE – FETCH EXISTING DATA
    ══════════════════════════════════════════════════════════════ */
    function fetchListing() {
        jQuery.post(LEB_Ajax.ajax_url, { action: 'leb_listing_get_listing', nonce: LEB_Ajax.nonce, id: state.id }, function (res) {
            if (!res.success) {
                LEB_Toaster.error((res.data && res.data.message) || 'Failed to load property.');
                return;
            }
            const d = res.data.listing;

            /* Scalar fields */
            if (DOM.title)       DOM.title.value       = d.title       || '';
            if (DOM.description) DOM.description.value = d.description || '';
            if (DOM.guests)      DOM.guests.value      = d.guests      || '';
            if (DOM.bedrooms)    DOM.bedrooms.value     = d.bedroom     || '';
            if (DOM.beds)        DOM.beds.value         = d.bed         || '';
            if (DOM.bathrooms)   DOM.bathrooms.value    = d.bathroom    || '';
            if (DOM.price)       DOM.price.value        = d.price       || '';

            /* Type */
            if (d.type) {
                state.typeId = d.type;
                const opt = DOM.typeDropdown.querySelector('[data-value="' + d.type + '"]');
                if (opt) { highlightSelected(DOM.typeDropdown, d.type); DOM.typeDisplay.innerHTML = '<span class="leb-aep-selected-single">' + esc(opt.textContent) + '</span>'; }
            }

            /* Location */
            if (d.location) {
                state.locationId = d.location;
                const opt = DOM.locDropdown.querySelector('[data-value="' + d.location + '"]');
                if (opt) { highlightSelected(DOM.locDropdown, d.location); DOM.locDisplay.innerHTML = '<span class="leb-aep-selected-single">' + esc(opt.textContent) + '</span>'; }
            }

            /* Status */
            if (d.status) {
                state.status = d.status;
                const opt = DOM.statusDropdown.querySelector('[data-value="' + d.status + '"]');
                if (opt) {
                    DOM.statusDropdown.querySelectorAll('.leb-aep-status-option').forEach(function (o) { o.classList.remove('selected'); });
                    opt.classList.add('selected');
                    DOM.statusDisplay.innerHTML = opt.innerHTML;
                }
            }

            /* Images */
            if (d.images) {
                try {
                    const imgs = typeof d.images === 'string' ? JSON.parse(d.images) : d.images;
                    state.images = imgs.map(function (img, i) {
                        return { id: img.attachment_id || 0, url: img.image_url, sort_order: img.sort_order || i };
                    });
                    renderImages();
                } catch (e) { console.error('Image parse error', e); }
            }

            /* Amenities */
            if (d.amenities) {
                let ids = [];
                try {
                    ids = JSON.parse(d.amenities);
                    if (!Array.isArray(ids)) ids = [ids];
                } catch { ids = String(d.amenities).split(','); }
                state.amenityIds = new Set(ids.filter(Boolean).map(Number));
                // Sync checkboxes
                DOM.amenDropdown.querySelectorAll('.leb-aep-select-option').forEach(function (opt) {
                    if (state.amenityIds.has(parseInt(opt.dataset.value, 10))) {
                        opt.classList.add('selected');
                        opt.querySelector('.leb-aep-checkbox').textContent = '✓';
                    }
                });
                renderAmenityTags();
            }

            /* Blocked dates */
            if (d.blocked_dates) {
                try {
                    const dates = typeof d.blocked_dates === 'string' ? JSON.parse(d.blocked_dates) : d.blocked_dates;
                    state.blockedDates = new Set(dates.map(function (bd) { return bd.blocked_date || bd; }));
                    renderCalendar();
                } catch (e) { console.error('Date parse error', e); }
            }
        });
    }

    /* ══════════════════════════════════════════════════════════════
       FORM SUBMIT
    ══════════════════════════════════════════════════════════════ */
    function bindFormSubmit() {
        DOM.form.addEventListener('submit', function (e) {
            e.preventDefault();

            /* Basic validation */
            const title = DOM.title.value.trim();
            if (!title) { DOM.title.classList.add('error'); LEB_Toaster.error('Property title is required.'); return; }
            DOM.title.classList.remove('error');

            if (!state.typeId) { LEB_Toaster.error('Please select a property type.'); return; }
            if (state.images.length === 0) { LEB_Toaster.error('Please add at least one image.'); return; }

            const guests = parseInt(DOM.guests.value, 10);
            if (!guests || guests < 1) { DOM.guests.classList.add('error'); LEB_Toaster.error('Number of guests must be at least 1.'); return; }
            DOM.guests.classList.remove('error');

            const price = parseFloat(DOM.price.value);
            if (!price || price <= 0) { DOM.price.classList.add('error'); LEB_Toaster.error('Please enter a valid price per night.'); return; }
            DOM.price.classList.remove('error');

            DOM.submitBtn.disabled = true;
            DOM.submitBtn.textContent = 'Saving…';

            const payload = {
                action:        'leb_listing_save_listing',
                nonce:         LEB_Ajax.nonce,
                id:            state.id,
                title:         title,
                description:   DOM.description.value.trim(),
                guests:        DOM.guests.value,
                bedroom:       DOM.bedrooms.value,
                bed:           DOM.beds.value,
                bathroom:      DOM.bathrooms.value,
                price:         DOM.price.value,
                type:          state.typeId,
                location:      state.locationId,
                status:        state.status,
                amenities:     Array.from(state.amenityIds),
                images:        state.images,
                blocked_dates: Array.from(state.blockedDates),
            };

            jQuery.post(LEB_Ajax.ajax_url, payload, function (res) {
                DOM.submitBtn.disabled = false;
                DOM.submitBtn.textContent = state.id > 0 ? 'Update Listing' : 'Publish Listing';
                if (res.success) {
                    LEB_Toaster.success('Property saved successfully!');
                    setTimeout(function () {
                        window.location.href = res.data.redirect_url;
                    }, 600);
                } else {
                    LEB_Toaster.error((res.data && res.data.message) || 'Error saving property.');
                }
            }).fail(function () {
                DOM.submitBtn.disabled = false;
                DOM.submitBtn.textContent = state.id > 0 ? 'Update Listing' : 'Publish Listing';
                LEB_Toaster.error('Network error. Please try again.');
            });
        });
    }

    /* ══════════════════════════════════════════════════════════════
       UTILITY
    ══════════════════════════════════════════════════════════════ */
    const ESC_MAP = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
    function esc(str) {
        if (!str) return '';
        return String(str).replace(/[&<>"']/g, function (s) { return ESC_MAP[s]; });
    }

})();
