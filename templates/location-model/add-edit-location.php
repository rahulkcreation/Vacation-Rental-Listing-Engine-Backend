<?php
/**
 * add-edit-location.php
 *
 * Add / Edit Location form.
 * SVG icon is selected from the WordPress Media Library.
 * All IDs and classes are prefixed with "leb-loc-"
 * to prevent any conflicts with other templates.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine mode.
$leb_loc_action  = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : 'add';
$leb_loc_edit_id = ( $leb_loc_action === 'edit' && isset( $_GET['id'] ) ) ? absint( $_GET['id'] ) : 0;
$leb_loc_is_edit = ( $leb_loc_action === 'edit' && $leb_loc_edit_id > 0 );

$leb_loc_back_url   = admin_url( 'admin.php?page=leb-locations' );
$leb_loc_card_title = $leb_loc_is_edit
    ? esc_html__( 'Edit Location', 'listing-engine-backend' )
    : esc_html__( 'Add New Location', 'listing-engine-backend' );
$leb_loc_btn_label  = $leb_loc_is_edit
    ? esc_html__( 'Update Location', 'listing-engine-backend' )
    : esc_html__( 'Create Location', 'listing-engine-backend' );
?>
<div class="wrap">
    <!-- This hidden h2 and the empty notice container catch WordPress admin notices before they get moved into our custom header. -->
    <h2 class="leb-admin-notice-placeholder"></h2>
    <div id="leb-loc-add-edit" class="leb-global-plugin-wrapper">

    <article class="leb-ae-loc-card">

        <!-- Card Header -->
        <header class="leb-ae-loc-header">
            <a href="<?php echo esc_url( $leb_loc_back_url ); ?>"
               class="leb-ae-loc-back-btn"
               aria-label="<?php esc_attr_e( 'Back to list', 'listing-engine-backend' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
            </a>
            <h2 class="leb-ae-loc-card-title"><?php echo $leb_loc_card_title; // Already escaped above. ?></h2>
        </header>

        <!-- Location Name -->
        <div class="leb-ae-loc-field">
            <label class="leb-ae-loc-label" for="leb-loc-ae-name">
                <?php esc_html_e( 'Location Name', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-loc-ae-name"
                class="leb-ae-loc-input"
                placeholder="<?php esc_attr_e( 'e.g. Dubai, UAE', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- Location Slug -->
        <div class="leb-ae-loc-field">
            <label class="leb-ae-loc-label" for="leb-loc-ae-slug">
                <?php esc_html_e( 'Slug', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-loc-ae-slug"
                class="leb-ae-loc-input"
                placeholder="<?php esc_attr_e( 'e.g. dubai-uae', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- SVG Icon Upload via WP Media Library -->
        <div class="leb-ae-loc-field">
            <label class="leb-ae-loc-label">
                <?php esc_html_e( 'SVG Icon (24×24px, max 1 MB)', 'listing-engine-backend' ); ?>
            </label>

            <!-- Preview Area -->
            <div class="leb-ae-loc-svg-wrap" id="leb-loc-svg-wrap">
                <!-- SVG preview – shown when an icon is selected -->
                <div class="leb-ae-loc-svg-preview-box" id="leb-loc-svg-preview-box" aria-hidden="true">
                    <img id="leb-loc-svg-preview-img" src="" alt="Selected SVG icon" width="24" height="24" style="display:none;">
                    <span class="leb-ae-loc-svg-placeholder" id="leb-loc-svg-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <span><?php esc_html_e( 'No icon selected', 'listing-engine-backend' ); ?></span>
                    </span>
                </div>

                <!-- Hidden field stores attachment URL -->
                <input type="hidden" id="leb-loc-svg-path" name="leb_loc_svg_path" value="">
                <input type="hidden" id="leb-loc-attachment-id" name="leb_loc_attachment_id" value="">

                <!-- Action buttons -->
                <div class="leb-ae-loc-svg-actions">
                    <button type="button" class="leb-ae-loc-svg-select-btn" id="leb-loc-svg-select-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span id="leb-loc-svg-btn-label"><?php esc_html_e( 'Upload / Choose SVG', 'listing-engine-backend' ); ?></span>
                    </button>

                    <button type="button" class="leb-ae-loc-svg-remove-btn" id="leb-loc-svg-remove-btn" style="display:none;" aria-label="<?php esc_attr_e( 'Remove selected icon', 'listing-engine-backend' ); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        <?php esc_html_e( 'Remove', 'listing-engine-backend' ); ?>
                    </button>
                </div>

                <!-- Validation hint -->
                <p class="leb-ae-loc-svg-hint">
                    <?php esc_html_e( 'SVG only • Exactly 24×24 px • Max 1 MB', 'listing-engine-backend' ); ?>
                </p>
            </div>
        </div>

        <!-- Submit -->
        <button type="button" class="leb-ae-loc-submit-btn" id="leb-loc-ae-submit-btn">
            <span class="leb-ae-loc-btn-spinner" aria-hidden="true"></span>
            <span class="leb-ae-loc-btn-label"><?php echo $leb_loc_btn_label; // Already escaped above. ?></span>
        </button>

    </article>

</div><!-- /#leb-loc-add-edit -->


</div>
