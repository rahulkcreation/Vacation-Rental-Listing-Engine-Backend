<?php

/**
 * database-page.php
 *
 * Database Management screen.
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

// On initial PHP render get the status of all tables.
global $wpdb;

$leb_types_table       = $wpdb->prefix . 'ls_types';
$leb_status            = leb_check_table_status($leb_types_table);

$leb_amenities_table   = $wpdb->prefix . 'ls_amenities';
$leb_amen_status       = leb_check_table_status($leb_amenities_table);

$leb_locations_table   = $wpdb->prefix . 'ls_location';
$leb_loc_status        = leb_check_table_status($leb_locations_table);

$leb_listings_table    = $wpdb->prefix . 'ls_property';
$leb_listings_status   = leb_check_table_status($leb_listings_table);

$leb_img_table         = $wpdb->prefix . 'ls_img';
$leb_img_status        = leb_check_table_status($leb_img_table);

$leb_block_date_table  = $wpdb->prefix . 'ls_block_date';
$leb_block_date_status = leb_check_table_status($leb_block_date_table);
?>

<div class="wrap">
    <!-- This hidden h2 and the empty notice container catch WordPress admin notices before they get moved into our custom header. -->
    <h2 class="leb-admin-notice-placeholder"></h2>
    <div id="leb-database-page" class="leb-global-plugin-wrapper">

        <!-- ── Page Header ───────────────────────────────────── -->
        <div class="leb-db-header">
            <div class="leb-db-icon-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <ellipse cx="12" cy="5" rx="9" ry="3" />
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3" />
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5" />
                </svg>
            </div>
            <h1 class="leb-db-page-title"><?php esc_html_e('Database Management', 'listing-engine-backend'); ?></h1>
        </div>

        <!-- ── Cards Grid ─────────────────────────────────────── -->
        <div class="leb-db-grid" id="leb-db-grid">

            <!-- Types Table Card (initial PHP render – JS updates on refresh) -->
            <div class="leb-db-card" id="leb-db-card-ls_types">
                <h2 class="leb-db-card-title"><?php esc_html_e('Types Table', 'listing-engine-backend'); ?></h2>

                <div class="leb-db-statuses" id="leb-db-statuses-ls_types">
                    <?php
                    // Render initial status.
                    leb_render_db_card_statuses($leb_status);
                    ?>
                </div>

                <div class="leb-db-actions">
                    <!-- Refresh button -->
                    <button
                        class="leb-db-btn leb-db-btn--refresh"
                        id="leb-db-refresh-ls_types"
                        data-table-key="ls_types"
                        aria-label="<?php esc_attr_e('Refresh status', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Refresh', 'listing-engine-backend'); ?></span>
                    </button>

                    <!-- Create/Repair button -->
                    <button
                        class="leb-db-btn leb-db-btn--repair"
                        id="leb-db-repair-ls_types"
                        data-table-key="ls_types"
                        aria-label="<?php esc_attr_e('Create or repair table', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Create / Repair', 'listing-engine-backend'); ?></span>
                    </button>
                </div>
            </div><!-- end Types card -->

            <!-- Amenities Table Card (initial PHP render – JS updates on refresh) -->
            <?php
            $leb_amenities_table  = $wpdb->prefix . 'ls_amenities';
            $leb_amen_status      = leb_check_table_status($leb_amenities_table);
            ?>
            <div class="leb-db-card" id="leb-db-card-ls_amenities">
                <h2 class="leb-db-card-title"><?php esc_html_e('Amenities Table', 'listing-engine-backend'); ?></h2>

                <div class="leb-db-statuses" id="leb-db-statuses-ls_amenities">
                    <?php leb_render_db_card_statuses($leb_amen_status); ?>
                </div>

                <div class="leb-db-actions">
                    <!-- Refresh button -->
                    <button
                        class="leb-db-btn leb-db-btn--refresh"
                        id="leb-db-refresh-ls_amenities"
                        data-table-key="ls_amenities"
                        aria-label="<?php esc_attr_e('Refresh amenities table status', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Refresh', 'listing-engine-backend'); ?></span>
                    </button>

                    <!-- Create/Repair button -->
                    <button
                        class="leb-db-btn leb-db-btn--repair"
                        id="leb-db-repair-ls_amenities"
                        data-table-key="ls_amenities"
                        aria-label="<?php esc_attr_e('Create or repair amenities table', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Create / Repair', 'listing-engine-backend'); ?></span>
                    </button>
                </div>
            </div><!-- end Amenities card -->

            <!-- Locations Table Card -->
            <div class="leb-db-card" id="leb-db-card-ls_location">
                <h2 class="leb-db-card-title"><?php esc_html_e('Locations Table', 'listing-engine-backend'); ?></h2>

                <div class="leb-db-statuses" id="leb-db-statuses-ls_location">
                    <?php leb_render_db_card_statuses($leb_loc_status); ?>
                </div>

                <div class="leb-db-actions">
                    <button
                        class="leb-db-btn leb-db-btn--refresh"
                        id="leb-db-refresh-ls_location"
                        data-table-key="ls_location"
                        aria-label="<?php esc_attr_e('Refresh locations table status', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Refresh', 'listing-engine-backend'); ?></span>
                    </button>

                    <button
                        class="leb-db-btn leb-db-btn--repair"
                        id="leb-db-repair-ls_location"
                        data-table-key="ls_location"
                        aria-label="<?php esc_attr_e('Create or repair locations table', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Create / Repair', 'listing-engine-backend'); ?></span>
                    </button>
                </div>
            </div><!-- end Locations card -->

            <!-- Listings Table Card -->
            <div class="leb-db-card" id="leb-db-card-ls_property">
                <h2 class="leb-db-card-title"><?php esc_html_e('Property Table', 'listing-engine-backend'); ?></h2>

                <div class="leb-db-statuses" id="leb-db-statuses-ls_property">
                    <?php leb_render_db_card_statuses($leb_listings_status); ?>
                </div>

                <div class="leb-db-actions">
                    <button
                        class="leb-db-btn leb-db-btn--refresh"
                        id="leb-db-refresh-ls_property"
                        data-table-key="ls_property"
                        aria-label="<?php esc_attr_e('Refresh listings table status', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Refresh', 'listing-engine-backend'); ?></span>
                    </button>

                    <button
                        class="leb-db-btn leb-db-btn--repair"
                        id="leb-db-repair-ls_property"
                        data-table-key="ls_property"
                        aria-label="<?php esc_attr_e('Create or repair listings table', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Create / Repair', 'listing-engine-backend'); ?></span>
                    </button>
                </div>
            </div><!-- end Listings card -->

            <!-- Images Table Card -->
            <div class="leb-db-card" id="leb-db-card-ls_img">
                <h2 class="leb-db-card-title"><?php esc_html_e('Images Table', 'listing-engine-backend'); ?></h2>

                <div class="leb-db-statuses" id="leb-db-statuses-ls_img">
                    <?php leb_render_db_card_statuses($leb_img_status); ?>
                </div>

                <div class="leb-db-actions">
                    <button
                        class="leb-db-btn leb-db-btn--refresh"
                        id="leb-db-refresh-ls_img"
                        data-table-key="ls_img"
                        aria-label="<?php esc_attr_e('Refresh images table status', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Refresh', 'listing-engine-backend'); ?></span>
                    </button>

                    <button
                        class="leb-db-btn leb-db-btn--repair"
                        id="leb-db-repair-ls_img"
                        data-table-key="ls_img"
                        aria-label="<?php esc_attr_e('Create or repair images table', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Create / Repair', 'listing-engine-backend'); ?></span>
                    </button>
                </div>
            </div><!-- end Images card -->

            <!-- Block Dates Table Card -->
            <div class="leb-db-card" id="leb-db-card-ls_block_date">
                <h2 class="leb-db-card-title"><?php esc_html_e('Block Dates Table', 'listing-engine-backend'); ?></h2>

                <div class="leb-db-statuses" id="leb-db-statuses-ls_block_date">
                    <?php leb_render_db_card_statuses($leb_block_date_status); ?>
                </div>

                <div class="leb-db-actions">
                    <button
                        class="leb-db-btn leb-db-btn--refresh"
                        id="leb-db-refresh-ls_block_date"
                        data-table-key="ls_block_date"
                        aria-label="<?php esc_attr_e('Refresh block dates table status', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="23 4 23 10 17 10" />
                            <path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10" />
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Refresh', 'listing-engine-backend'); ?></span>
                    </button>

                    <button
                        class="leb-db-btn leb-db-btn--repair"
                        id="leb-db-repair-ls_block_date"
                        data-table-key="ls_block_date"
                        aria-label="<?php esc_attr_e('Create or repair block dates table', 'listing-engine-backend'); ?>">
                        <span class="leb-db-card-spin" aria-hidden="true"></span>
                        <svg class="leb-db-btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        <span class="leb-db-btn-label"><?php esc_html_e('Create / Repair', 'listing-engine-backend'); ?></span>
                    </button>
                </div>
            </div><!-- end Block Dates card -->

        </div><!-- /.leb-db-grid -->

    </div><!-- /#leb-database-page -->
</div>