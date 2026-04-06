<?php
/**
 * add-edit-amenity.php
 *
 * Add / Edit Amenity form.
 * SVG icon is selected from the WordPress Media Library.
 * All IDs and classes are prefixed with "leb-amen" / "leb-ae-amen"
 * to prevent any conflicts with other templates.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine mode.
$leb_amen_action  = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : 'add';
$leb_amen_edit_id = ( $leb_amen_action === 'edit' && isset( $_GET['id'] ) ) ? absint( $_GET['id'] ) : 0;
$leb_amen_is_edit = ( $leb_amen_action === 'edit' && $leb_amen_edit_id > 0 );

$leb_amen_back_url   = admin_url( 'admin.php?page=leb-amenities' );
$leb_amen_card_title = $leb_amen_is_edit
    ? esc_html__( 'Edit Amenity', 'listing-engine-backend' )
    : esc_html__( 'Add New Amenity', 'listing-engine-backend' );
$leb_amen_btn_label  = $leb_amen_is_edit
    ? esc_html__( 'Update Amenity', 'listing-engine-backend' )
    : esc_html__( 'Create Amenity', 'listing-engine-backend' );
?>
<div id="leb-amen-add-edit" class="leb-wrap">

    <article class="leb-ae-amen-card">

        <!-- Card Header -->
        <header class="leb-ae-amen-header">
            <a href="<?php echo esc_url( $leb_amen_back_url ); ?>"
               class="leb-ae-amen-back-btn"
               aria-label="<?php esc_attr_e( 'Back to list', 'listing-engine-backend' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
            </a>
            <h2 class="leb-ae-amen-card-title"><?php echo $leb_amen_card_title; // Already escaped above. ?></h2>
        </header>

        <!-- Amenity Name -->
        <div class="leb-ae-amen-field">
            <label class="leb-ae-amen-label" for="leb-amen-ae-name">
                <?php esc_html_e( 'Amenity Name', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-amen-ae-name"
                class="leb-ae-amen-input"
                placeholder="<?php esc_attr_e( 'e.g. Swimming Pool', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- SVG Icon Upload via WP Media Library -->
        <div class="leb-ae-amen-field">
            <label class="leb-ae-amen-label">
                <?php esc_html_e( 'SVG Icon (24×24px, max 1 MB)', 'listing-engine-backend' ); ?>
            </label>

            <!-- Preview Area -->
            <div class="leb-ae-amen-svg-wrap" id="leb-amen-svg-wrap">
                <!-- SVG preview – shown when an icon is selected -->
                <div class="leb-ae-amen-svg-preview-box" id="leb-amen-svg-preview-box" aria-hidden="true">
                    <img id="leb-amen-svg-preview-img" src="" alt="Selected SVG icon" width="24" height="24" style="display:none;">
                    <span class="leb-ae-amen-svg-placeholder" id="leb-amen-svg-placeholder">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21 15 16 10 5 21"/>
                        </svg>
                        <span><?php esc_html_e( 'No icon selected', 'listing-engine-backend' ); ?></span>
                    </span>
                </div>

                <!-- Hidden field stores attachment URL -->
                <input type="hidden" id="leb-amen-svg-path" name="leb_amen_svg_path" value="">
                <input type="hidden" id="leb-amen-attachment-id" name="leb_amen_attachment_id" value="">

                <!-- Action buttons -->
                <div class="leb-ae-amen-svg-actions">
                    <button type="button" class="leb-ae-amen-svg-select-btn" id="leb-amen-svg-select-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        <span id="leb-amen-svg-btn-label"><?php esc_html_e( 'Upload / Choose SVG', 'listing-engine-backend' ); ?></span>
                    </button>

                    <button type="button" class="leb-ae-amen-svg-remove-btn" id="leb-amen-svg-remove-btn" style="display:none;" aria-label="<?php esc_attr_e( 'Remove selected icon', 'listing-engine-backend' ); ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        <?php esc_html_e( 'Remove', 'listing-engine-backend' ); ?>
                    </button>
                </div>

                <!-- Validation hint -->
                <p class="leb-ae-amen-svg-hint">
                    <?php esc_html_e( 'SVG only • Exactly 24×24 px • Max 1 MB', 'listing-engine-backend' ); ?>
                </p>
            </div>
        </div>

        <!-- Submit -->
        <button type="button" class="leb-ae-amen-submit-btn" id="leb-amen-ae-submit-btn">
            <span class="leb-ae-amen-btn-spinner" aria-hidden="true"></span>
            <span class="leb-ae-amen-btn-label"><?php echo $leb_amen_btn_label; // Already escaped above. ?></span>
        </button>

    </article>

</div><!-- /#leb-amen-add-edit -->

<script>
window.lebAmenAECfg = {
    isEdit: <?php echo $leb_amen_is_edit ? 'true' : 'false'; ?>,
    editId: <?php echo (int) $leb_amen_edit_id; ?>,
    defaultSvgPath: '<?php echo esc_url( LEB_PLUGIN_URL . "assets/images/default-amenity.svg" ); ?>',
    redirectUrl: '<?php echo esc_js( admin_url( 'admin.php?page=leb-amenities' ) ); ?>'
};
</script>
