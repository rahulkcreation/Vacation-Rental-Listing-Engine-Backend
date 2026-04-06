<?php
/**
 * Add / Edit Property – Premium Form Template
 *
 * Provides: WP Media Library image upload, drag-and-drop reorder,
 * custom multi/single-select dropdowns, amenity checkbox grid,
 * stepper inputs, responsive multi-date calendar, and status selector.
 *
 * @package ListingEngineBackend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine mode (add vs edit).
$listing_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
$is_edit    = ( $listing_id > 0 );
$back_url   = admin_url( 'admin.php?page=leb-properties' );
?>

<div class="leb-aep-wrap" id="leb-prop-form-wrap" role="main">
    <form id="propertyForm" novalidate>
        <!-- Hidden ID field for edit mode -->
        <input type="hidden" id="leb-prop-field-id" value="<?php echo esc_attr( $listing_id ); ?>">

        <!-- ══════════════════════════════════════════
             SECTION 1: STICKY NAVIGATION BAR
        ══════════════════════════════════════════ -->
        <nav class="leb-aep-nav" aria-label="Navigation">
            <div class="leb-aep-nav__left">
                <a href="<?php echo esc_url( $back_url ); ?>" class="leb-aep-back-btn" aria-label="Back to Listings" id="leb-prop-form-back">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                    </svg>
                </a>
                <span class="leb-aep-nav__heading">Back to Listings</span>
            </div>
            
        </nav>

        <!-- ══════════════════════════════════════════
             SECTION 2: PROPERTY TITLE
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <label for="propertyTitle" class="leb-aep-label">
                Property Title <span class="leb-aep-req" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="propertyTitle"
                class="leb-aep-input"
                placeholder="e.g. Luxurious Beachfront Villa"
                aria-required="true"
            >
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 3: PROPERTY IMAGES (WP MEDIA)
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <label class="leb-aep-label">Property Images</label>
            <div class="leb-aep-upload-area" id="uploadArea" role="button" tabindex="0" aria-label="Click to open Media Library and select images">
                <div class="leb-aep-upload-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                    </svg>
                </div>
                <div class="leb-aep-upload-text">Click to upload or drag &amp; drop</div>
                <div class="leb-aep-upload-subtext">PNG, JPG, WEBP · up to 5 MB each · max 10 images</div>
            </div>
            <div class="leb-aep-image-grid" id="imagePreviewGrid"></div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 4: DESCRIPTION
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <label for="propertyDescription" class="leb-aep-label">Description</label>
            <textarea
                id="propertyDescription"
                class="leb-aep-textarea"
                placeholder="Describe your property, nearby attractions, unique features…"
                aria-required="true"
            ></textarea>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 5: PROPERTY DETAILS (STEPPER GRID)
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <div class="leb-aep-section-heading">Property Details</div>
            <div class="leb-aep-details-grid">

                <div class="leb-aep-detail-field">
                    <label for="guests" class="leb-aep-detail-label">Guests</label>
                    <input type="number" id="guests" class="leb-aep-detail-input" min="1" step="1" placeholder="0">
                </div>

                <div class="leb-aep-detail-field">
                    <label for="bedrooms" class="leb-aep-detail-label">Bedrooms</label>
                    <input type="number" id="bedrooms" class="leb-aep-detail-input" min="0" step="1" placeholder="0">
                </div>

                <div class="leb-aep-detail-field">
                    <label for="beds" class="leb-aep-detail-label">Beds</label>
                    <input type="number" id="beds" class="leb-aep-detail-input" min="0" step="1" placeholder="0">
                </div>

                <div class="leb-aep-detail-field">
                    <label for="bathrooms" class="leb-aep-detail-label">Bathrooms</label>
                    <input type="number" id="bathrooms" class="leb-aep-detail-input" min="0" step="1" placeholder="0">
                </div>

                <div class="leb-aep-detail-field">
                    <label for="pricePerNight" class="leb-aep-detail-label">Price (₹/night)</label>
                    <input type="number" id="pricePerNight" class="leb-aep-detail-input" min="0" step="0.01" placeholder="0.00">
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 6: DROPDOWNS (AMENITIES · LOCATION · TYPE)
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <div class="leb-aep-dropdowns-grid">

                <!-- Amenities Multi-Select (spans full width on 2-col grid) -->
                <div class="leb-aep-select-wrap leb-aep-select-wrap--full" id="amenitiesSelectWrapper">
                    <label class="leb-aep-label">Amenities</label>
                    <div class="leb-aep-select-trigger" id="amenitiesTrigger"
                         tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-label="Select amenities">
                        <span class="leb-aep-selected-tags" id="amenitiesSelectedTags">
                            <span class="leb-aep-placeholder">Select amenities…</span>
                        </span>
                        <svg class="leb-aep-select-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                    <div class="leb-aep-select-dropdown" id="amenitiesDropdown" role="listbox" aria-label="Amenities options">
                        <div class="leb-aep-select-loading">Loading amenities…</div>
                    </div>
                </div>

                <!-- Location Single-Select -->
                <div class="leb-aep-select-wrap" id="locationSelectWrapper">
                    <label class="leb-aep-label">Location</label>
                    <div class="leb-aep-select-trigger" id="locationTrigger"
                         tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-label="Select location">
                        <span id="locationSelectedDisplay">
                            <span class="leb-aep-placeholder">Select location…</span>
                        </span>
                        <svg class="leb-aep-select-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                    <div class="leb-aep-select-dropdown" id="locationDropdown" role="listbox" aria-label="Location options">
                        <div class="leb-aep-select-loading">Loading locations…</div>
                    </div>
                </div>

                <!-- Property Type Single-Select -->
                <div class="leb-aep-select-wrap" id="propertyTypeSelectWrapper">
                    <label class="leb-aep-label">Property Type</label>
                    <div class="leb-aep-select-trigger" id="propertyTypeTrigger"
                         tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-label="Select property type">
                        <span id="propertyTypeSelectedDisplay">
                            <span class="leb-aep-placeholder">Select type…</span>
                        </span>
                        <svg class="leb-aep-select-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </div>
                    <div class="leb-aep-select-dropdown" id="propertyTypeDropdown" role="listbox" aria-label="Property type options">
                        <div class="leb-aep-select-loading">Loading types…</div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 7: BLOCKED DATES CALENDAR
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <label class="leb-aep-label">
                Block Dates
                <span class="leb-aep-label-hint">(Click dates to mark unavailable)</span>
            </label>

            <div class="leb-aep-calendar-wrap">

                <!-- Desktop dual-month nav -->
                <div class="leb-aep-cal-nav" id="calendarNavBar">
                    <button type="button" class="leb-aep-cal-nav-btn" id="prevMonthDesktop" aria-label="Previous month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <span class="leb-aep-cal-nav-title" id="calendarNavTitle"></span>
                    <button type="button" class="leb-aep-cal-nav-btn" id="nextMonthDesktop" aria-label="Next month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

                <!-- Mobile single-month nav -->
                <div class="leb-aep-cal-nav-mobile" id="mobileCalendarNav">
                    <button type="button" class="leb-aep-cal-nav-btn" id="prevMonthMobile" aria-label="Previous month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <span class="leb-aep-cal-mobile-title" id="mobileMonthTitle"></span>
                    <button type="button" class="leb-aep-cal-nav-btn" id="nextMonthMobile" aria-label="Next month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>

                <!-- Calendar months rendered by JS -->
                <div class="leb-aep-cal-months" id="calendarMonthsWrapper"></div>

                <!-- Selected date chips (hidden until dates chosen) -->
                <div class="leb-aep-dates-summary" id="selectedDatesSummary">
                    <div class="leb-aep-dates-count" id="selectedDatesCount">0 date(s) selected</div>
                    <div class="leb-aep-dates-chips" id="selectedDatesChips"></div>
                </div>

            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 8: LISTING STATUS
        ══════════════════════════════════════════ -->
        <div class="leb-aep-section">
            <label class="leb-aep-label">Listing Status</label>
            <div class="leb-aep-select-wrap leb-aep-select-wrap--status" id="statusSelectWrapper">
                <div class="leb-aep-select-trigger" id="statusTrigger"
                     tabindex="0" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-label="Select listing status">
                    <span id="statusSelectedDisplay" class="leb-aep-status-display">
                        <span class="leb-aep-placeholder">Select status…</span>
                    </span>
                    <svg class="leb-aep-select-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                    </svg>
                </div>
                <div class="leb-aep-select-dropdown" id="statusDropdown" role="listbox">
                    <div class="leb-aep-status-option" data-value="pending" role="option">
                        <span class="leb-aep-status-dot leb-aep-status-dot--pending">&#9203;</span>
                        <span>Pending</span>
                    </div>
                    <div class="leb-aep-status-option" data-value="published" role="option">
                        <span class="leb-aep-status-dot leb-aep-status-dot--published">&#9989;</span>
                        <span>Published</span>
                    </div>
                    <div class="leb-aep-status-option" data-value="rejected" role="option">
                        <span class="leb-aep-status-dot leb-aep-status-dot--rejected">&#10060;</span>
                        <span>Rejected</span>
                    </div>
                    <div class="leb-aep-status-option selected" data-value="draft" role="option">
                        <span class="leb-aep-status-dot leb-aep-status-dot--draft">&#128221;</span>
                        <span>Draft</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════
             SECTION 9: SUBMIT BUTTON
        ══════════════════════════════════════════ -->
        <button type="submit" class="leb-aep-submit-btn" id="submitBtn">
            <?php echo $is_edit ? 'Update Listing' : 'Publish Listing'; ?>
        </button>

    </form>
</div>
