<?php
/**
 * add-edit-type.php
 *
 * Add / Edit Type form.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Determine mode.
$leb_action  = isset( $_GET['leb_action'] ) ? sanitize_text_field( wp_unslash( $_GET['leb_action'] ) ) : 'add';
$leb_edit_id = ( $leb_action === 'edit' && isset( $_GET['id'] ) ) ? absint( $_GET['id'] ) : 0;
$leb_is_edit = ( $leb_action === 'edit' && $leb_edit_id > 0 );

$leb_back_url   = admin_url( 'admin.php?page=leb-types' );
$leb_card_title = $leb_is_edit
    ? esc_html__( 'Edit Type', 'listing-engine-backend' )
    : esc_html__( 'Add New Type', 'listing-engine-backend' );
$leb_btn_label  = $leb_is_edit
    ? esc_html__( 'Update Type', 'listing-engine-backend' )
    : esc_html__( 'Create Type', 'listing-engine-backend' );
?>
<div id="leb-add-edit-type" class="leb-wrap">

    <article class="leb-ae-card">

        <!-- Card Header -->
        <header class="leb-ae-header">
            <a href="<?php echo esc_url( $leb_back_url ); ?>"
               class="leb-ae-back-btn"
               aria-label="<?php esc_attr_e( 'Back to list', 'listing-engine-backend' ); ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <line x1="19" y1="12" x2="5" y2="12"/>
                    <polyline points="12 19 5 12 12 5"/>
                </svg>
            </a>
            <h2 class="leb-ae-card-title"><?php echo $leb_card_title; // Already escaped above. ?></h2>
        </header>

        <!-- Type Name -->
        <div class="leb-ae-field">
            <label class="leb-ae-label" for="leb-ae-name">
                <?php esc_html_e( 'Type Name', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-ae-name"
                class="leb-ae-input"
                placeholder="<?php esc_attr_e( 'e.g. Apartment', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- Slug -->
        <div class="leb-ae-field">
            <label class="leb-ae-label" for="leb-ae-slug">
                <?php esc_html_e( 'Slug', 'listing-engine-backend' ); ?>
            </label>
            <input
                type="text"
                id="leb-ae-slug"
                class="leb-ae-input"
                placeholder="<?php esc_attr_e( 'e.g. apartment', 'listing-engine-backend' ); ?>"
                autocomplete="off"
                required
            >
        </div>

        <!-- Submit -->
        <button type="button" class="leb-ae-submit-btn" id="leb-ae-submit-btn">
            <span class="leb-ae-btn-spinner" aria-hidden="true"></span>
            <span class="leb-ae-btn-label"><?php echo $leb_btn_label; // Already escaped above. ?></span>
        </button>

    </article>

</div><!-- /#leb-add-edit-type -->

<script>
window.lebTypeAECfg = {
    isEdit: <?php echo $leb_is_edit ? 'true' : 'false'; ?>,
    editId: <?php echo (int) $leb_edit_id; ?>,
    redirectUrl: '<?php echo esc_js( admin_url( 'admin.php?page=leb-types' ) ); ?>'
};
</script>
