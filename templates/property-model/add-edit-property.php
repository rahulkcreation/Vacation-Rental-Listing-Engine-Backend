<?php
/**
 * Add / Edit Property – Full-screen Modal Form
 *
 * Provides: image upload via WP Media Library, drag-and-drop reorder,
 * custom dropdowns for type/location, amenity checkbox grid,
 * stepper inputs, calendar-based date blocking, and status selector.
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

<div class="leb-prop-form-wrap" id="leb-prop-form-wrap">

    <!-- ─── Top Bar ──────────────────────────────────────── -->
    <div class="leb-prop-form-topbar">
        <a href="<?php echo esc_url( $back_url ); ?>" class="leb-prop-form-back" id="leb-prop-form-back">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            <span>Back to Listings</span>
        </a>
        <h2 class="leb-prop-form-topbar__title"><?php echo $is_edit ? 'Edit Property' : 'Add New Property'; ?></h2>
        <div class="leb-prop-form-topbar__actions">
            <button type="button" class="leb-prop-form-btn leb-prop-form-btn--outline" id="leb-prop-form-save-draft">Save as Draft</button>
            <button type="button" class="leb-prop-form-btn leb-prop-form-btn--primary" id="leb-prop-form-submit">
                <?php echo $is_edit ? 'Update Property' : 'Publish Property'; ?>
            </button>
        </div>
    </div>

    <!-- ─── Form Body ────────────────────────────────────── -->
    <form id="leb-prop-form" autocomplete="off">
        <!-- Hidden ID field for edit mode -->
        <input type="hidden" id="leb-prop-field-id" value="<?php echo esc_attr( $listing_id ); ?>">

        <div class="leb-prop-form-grid">

            <!-- ═══ LEFT COLUMN ═══ -->
            <div class="leb-prop-form-col leb-prop-form-col--main">

                <!-- ── Basic Info Card ─────────────────────── -->
                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Basic Information</h3>

                    <div class="leb-prop-form-field">
                        <label class="leb-prop-form-label" for="leb-prop-field-title">Property Title <span class="leb-prop-form-req">*</span></label>
                        <input type="text" id="leb-prop-field-title" class="leb-prop-form-input" placeholder="e.g. Luxurious Beachfront Villa">
                    </div>

                    <div class="leb-prop-form-field">
                        <label class="leb-prop-form-label" for="leb-prop-field-description">Description</label>
                        <textarea id="leb-prop-field-description" class="leb-prop-form-textarea" rows="5" placeholder="Describe the property, surrounding area, unique features…"></textarea>
                    </div>

                    <div class="leb-prop-form-row leb-prop-form-row--4">
                        <!-- Guests stepper -->
                        <div class="leb-prop-form-field">
                            <label class="leb-prop-form-label">Guests</label>
                            <div class="leb-prop-form-stepper" data-target="leb-prop-field-guests">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--minus">−</button>
                                <input type="number" id="leb-prop-field-guests" class="leb-prop-form-stepper__value" value="0" min="0">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--plus">+</button>
                            </div>
                        </div>
                        <!-- Bedrooms stepper -->
                        <div class="leb-prop-form-field">
                            <label class="leb-prop-form-label">Bedrooms</label>
                            <div class="leb-prop-form-stepper" data-target="leb-prop-field-bedroom">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--minus">−</button>
                                <input type="number" id="leb-prop-field-bedroom" class="leb-prop-form-stepper__value" value="0" min="0">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--plus">+</button>
                            </div>
                        </div>
                        <!-- Beds stepper -->
                        <div class="leb-prop-form-field">
                            <label class="leb-prop-form-label">Beds</label>
                            <div class="leb-prop-form-stepper" data-target="leb-prop-field-bed">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--minus">−</button>
                                <input type="number" id="leb-prop-field-bed" class="leb-prop-form-stepper__value" value="0" min="0">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--plus">+</button>
                            </div>
                        </div>
                        <!-- Bathrooms stepper -->
                        <div class="leb-prop-form-field">
                            <label class="leb-prop-form-label">Bathrooms</label>
                            <div class="leb-prop-form-stepper" data-target="leb-prop-field-bathroom">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--minus">−</button>
                                <input type="number" id="leb-prop-field-bathroom" class="leb-prop-form-stepper__value" value="0" min="0">
                                <button type="button" class="leb-prop-form-stepper__btn leb-prop-form-stepper__btn--plus">+</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Images Card ─────────────────────────── -->
                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Property Images</h3>
                    <p class="leb-prop-form-card__desc">Upload up to 10 images. Drag to reorder. The first image is the thumbnail.</p>

                    <div class="leb-prop-form-images" id="leb-prop-images-grid">
                        <!-- Image previews injected by JS -->
                    </div>

                    <button type="button" class="leb-prop-form-btn leb-prop-form-btn--outline leb-prop-form-btn--full" id="leb-prop-add-images">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                        Add Images
                    </button>
                </div>

                <!-- ── Amenities Card ──────────────────────── -->
                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Amenities</h3>
                    <div class="leb-prop-form-amenities" id="leb-prop-amenities-grid">
                        <!-- Amenity checkboxes loaded via JS -->
                        <div class="leb-prop-form-amenities-loading">Loading amenities…</div>
                    </div>
                </div>

            </div><!-- END Left Column -->

            <!-- ═══ RIGHT COLUMN (Sidebar) ═══ -->
            <div class="leb-prop-form-col leb-prop-form-col--side">

                <!-- ── Price & Categorisation ───────────────── -->
                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Pricing</h3>
                    <div class="leb-prop-form-field">
                        <label class="leb-prop-form-label" for="leb-prop-field-price">Price per Night (₹)</label>
                        <input type="number" id="leb-prop-field-price" class="leb-prop-form-input" placeholder="e.g. 4500" min="0">
                    </div>
                </div>

                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Category</h3>

                    <!-- Type dropdown -->
                    <div class="leb-prop-form-field">
                        <label class="leb-prop-form-label">Property Type</label>
                        <div class="leb-prop-form-select-wrap">
                            <select id="leb-prop-field-type" class="leb-prop-form-select">
                                <option value="">Select type…</option>
                            </select>
                        </div>
                    </div>

                    <!-- Location dropdown -->
                    <div class="leb-prop-form-field">
                        <label class="leb-prop-form-label">Location</label>
                        <div class="leb-prop-form-select-wrap">
                            <select id="leb-prop-field-location" class="leb-prop-form-select">
                                <option value="">Select location…</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ── Status ───────────────────────────────── -->
                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Status</h3>
                    <div class="leb-prop-form-status-group" id="leb-prop-status-group">
                        <label class="leb-prop-form-status-option">
                            <input type="radio" name="leb_prop_status" value="draft" checked> Draft
                        </label>
                        <label class="leb-prop-form-status-option">
                            <input type="radio" name="leb_prop_status" value="pending"> Pending
                        </label>
                        <label class="leb-prop-form-status-option">
                            <input type="radio" name="leb_prop_status" value="published"> Published
                        </label>
                        <label class="leb-prop-form-status-option">
                            <input type="radio" name="leb_prop_status" value="rejected"> Rejected
                        </label>
                    </div>
                </div>

                <!-- ── Block Dates ──────────────────────────── -->
                <div class="leb-prop-form-card">
                    <h3 class="leb-prop-form-card__title">Block Dates</h3>
                    <p class="leb-prop-form-card__desc">Select dates to block availability.</p>

                    <div class="leb-prop-form-calendar" id="leb-prop-calendar">
                        <div class="leb-prop-form-calendar__nav">
                            <button type="button" class="leb-prop-form-calendar__arrow" id="leb-prop-cal-prev">&lsaquo;</button>
                            <span class="leb-prop-form-calendar__month" id="leb-prop-cal-title">January 2026</span>
                            <button type="button" class="leb-prop-form-calendar__arrow" id="leb-prop-cal-next">&rsaquo;</button>
                        </div>
                        <div class="leb-prop-form-calendar__head">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        <div class="leb-prop-form-calendar__grid" id="leb-prop-cal-grid">
                            <!-- Day cells injected by JS -->
                        </div>
                    </div>

                    <div class="leb-prop-form-blocked-list" id="leb-prop-blocked-list">
                        <!-- Blocked date chips rendered by JS -->
                    </div>
                </div>

            </div><!-- END Right Column -->

        </div><!-- END Grid -->
    </form>

</div>
