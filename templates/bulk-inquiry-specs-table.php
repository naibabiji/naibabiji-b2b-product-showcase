<?php
/**
 * Bulk Inquiry Specs Table Template
 *
 * Rendered on single product pages when _inquiry_type === 'bulk'.
 * Variables available:
 *   $product — Naibabiji_B2B_Product instance
 *   $specs   — array of spec items
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $specs ) ) : ?>
    <div class="naib-no-specs-notice" style="padding:24px; text-align:center; background:#f9f9f9; border:1px dashed #ddd; border-radius:var(--naibabiji-b2b-border-radius); margin:20px 0;">
        <p style="color:#666; margin:0;"><?php esc_html_e( 'No specs available at the moment. Please contact us for more information.', 'naibabiji-b2b-product-showcase' ); ?></p>
    </div>
    <?php return;
endif;
?>

<div class="naib-bulk-specs-section" style="margin:24px 0;">
    <h3 style="margin:0 0 12px; font-size:18px;"><?php esc_html_e( 'Available Specs', 'naibabiji-b2b-product-showcase' ); ?></h3>
    <p style="color:#666; margin:0 0 16px; font-size:13px;"><?php esc_html_e( 'Select the specs you need and enter quantity.', 'naibabiji-b2b-product-showcase' ); ?></p>

    <div class="naib-bulk-specs-table-wrapper" style="overflow-x:auto;">
        <table class="naib-bulk-specs-table" style="width:100%; border-collapse:collapse; font-size:14px;">
            <thead>
                <tr style="background:#f5f5f5;">
                    <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #e0e0e0;"><?php esc_html_e( 'Model Code', 'naibabiji-b2b-product-showcase' ); ?></th>
                    <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #e0e0e0;"><?php esc_html_e( 'Spec Description', 'naibabiji-b2b-product-showcase' ); ?></th>
                    <th style="padding:10px 12px; text-align:center; border-bottom:2px solid #e0e0e0; width:100px;"><?php esc_html_e( 'Quantity', 'naibabiji-b2b-product-showcase' ); ?></th>
                    <th style="padding:10px 12px; text-align:center; border-bottom:2px solid #e0e0e0; width:80px;"><?php esc_html_e( 'Action', 'naibabiji-b2b-product-showcase' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- foreach loop variables, not globals
                foreach ( $specs as $index => $spec ) : ?>
                    <tr class="naib-spec-row" style="border-bottom:1px solid #f0f0f0;">
                        <td style="padding:10px 12px; font-weight:500;"><?php echo esc_html( $spec['code'] ?? '' ); ?></td>
                        <td style="padding:10px 12px; color:#555;"><?php echo esc_html( $spec['description'] ?? '' ); ?></td>
                        <td style="padding:8px 12px; text-align:center;">
                            <input type="number" class="naib-spec-qty" value="0" min="0" max="99999"
                                style="width:70px; padding:6px 8px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); text-align:center;"
                                data-code="<?php echo esc_attr( $spec['code'] ?? '' ); ?>"
                                data-description="<?php echo esc_attr( $spec['description'] ?? '' ); ?>">
                        </td>
                        <td style="padding:8px 12px; text-align:center;">
                            <button type="button" class="naib-spec-add-btn"
                                style="background:var(--naibabiji-b2b-primary-color); color:#fff; border:none; padding:6px 14px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer; font-size:12px; white-space:nowrap;"
                                data-code="<?php echo esc_attr( $spec['code'] ?? '' ); ?>"
                                data-description="<?php echo esc_attr( $spec['description'] ?? '' ); ?>">
                                <?php esc_html_e( 'Add', 'naibabiji-b2b-product-showcase' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display:flex; align-items:center; justify-content:space-between; margin-top:16px; flex-wrap:wrap; gap:12px;">
        <span class="naib-specs-selected-count" style="font-size:13px; color:#666;"><?php esc_html_e( 'Selected: 0 specs', 'naibabiji-b2b-product-showcase' ); ?></span>
        <div>
            <button type="button" class="naib-bulk-add-all-btn" style="background:var(--naibabiji-b2b-primary-color); color:#fff; border:none; padding:10px 20px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer; font-size:14px; margin-right:8px;">
                <?php esc_html_e( 'Add All to Cart', 'naibabiji-b2b-product-showcase' ); ?>
            </button>
            <button type="button" class="naib-bulk-submit-direct-btn" style="background:#fff; color:var(--naibabiji-b2b-primary-color); border:1px solid var(--naibabiji-b2b-primary-color); padding:10px 20px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer; font-size:14px;">
                <?php esc_html_e( 'Submit Inquiry Directly', 'naibabiji-b2b-product-showcase' ); ?>
            </button>
        </div>
    </div>
</div>
