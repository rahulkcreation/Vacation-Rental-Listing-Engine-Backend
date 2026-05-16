<?php
/**
 * template-helpers.php
 *
 * Contains reusable PHP helper functions for rendering UI components
 * across various admin templates.
 *
 * @package ListingEngineBackend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: Render the status badge rows for a DB card.
 * Extracted to avoid code duplication (used in PHP render + AJAX JSON reconstruction in JS).
 *
 * @param array $status ['exists' => bool, 'rows_complete' => bool].
 */
function leb_render_db_card_statuses(array $status): void
{
    // Table Created status.
    if ($status['exists']) {
        echo '<div class="leb-db-status-row">';
        echo '<span class="leb-db-status-label">' . esc_html__('Table Created:', 'listing-engine-backend') . '</span>';
        echo '<span class="leb-badge leb-badge--success">&#10003; ' . esc_html__('Yes', 'listing-engine-backend') . '</span>';
        echo '</div>';
    } else {
        echo '<div class="leb-db-status-row">';
        echo '<span class="leb-db-status-label">' . esc_html__('Table Created:', 'listing-engine-backend') . '</span>';
        echo '<span class="leb-badge leb-badge--error">&#10007; ' . esc_html__('Not Created', 'listing-engine-backend') . '</span>';
        echo '</div>';
    }

    // Rows Complete status (only meaningful if table exists).
    if ($status['exists']) {
        if ($status['rows_complete']) {
            echo '<div class="leb-db-status-row">';
            echo '<span class="leb-db-status-label">' . esc_html__('Rows Complete:', 'listing-engine-backend') . '</span>';
            echo '<span class="leb-badge leb-badge--success">&#10003; ' . esc_html__('All Present', 'listing-engine-backend') . '</span>';
            echo '</div>';
        } else {
            echo '<div class="leb-db-status-row">';
            echo '<span class="leb-db-status-label">' . esc_html__('Rows Complete:', 'listing-engine-backend') . '</span>';
            echo '<span class="leb-badge leb-badge--warning">&#9888; ' . esc_html__('Missing Rows', 'listing-engine-backend') . '</span>';
            echo '</div>';
        }
    }
}
