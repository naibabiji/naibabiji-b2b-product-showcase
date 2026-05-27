<?php
/**
 * Bulk Inquiry Submission Form Template
 *
 * Rendered as a modal/page when customer submits inquiry cart.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable scoped via include, not global
$form_fields = Naibabiji_B2B_Settings::get( 'inquiry_form_fields', array( 'name', 'email', 'message' ) );
?>

<div id="naib-bulk-inquiry-form-modal" class="naib-modal-wrapper" style="display:none;">
    <div class="naib-overlay" style="position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.5); z-index:99991;"></div>
    <div class="naib-panel" style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:var(--naibabiji-b2b-border-radius); z-index:99992; width:520px; max-width:90vw; max-height:85vh; overflow-y:auto; padding:0;">
        <div class="naib-panel__header" style="padding:20px 24px; border-bottom:1px solid #e5e5e5; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:16px;"><?php esc_html_e( 'Submit Bulk Inquiry', 'naibabiji-b2b-product-showcase' ); ?></h3>
            <button type="button" class="naib-bulk-form-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:#666;">&times;</button>
        </div>
        <div class="naib-panel__body" style="padding:24px;">
            <!-- Product Summary -->
            <div id="naib-bulk-form-summary" style="background:#f9f9f9; padding:12px 16px; border-radius:var(--naibabiji-b2b-border-radius); margin-bottom:20px; font-size:13px; color:#555;">
                <p style="margin:0 0 4px; font-weight:600;"><?php esc_html_e( 'Inquiry Products:', 'naibabiji-b2b-product-showcase' ); ?></p>
                <div id="naib-bulk-form-product-list"></div>
            </div>

            <form id="naib-bulk-inquiry-form" class="naib-form-grid">
                <input type="hidden" name="action" value="naib_submit_bulk_inquiry">
                <input type="hidden" name="cart_data" id="naib-bulk-form-cart-data" value="">

                <?php if ( in_array( 'name', $form_fields ) ) : ?>
                    <div class="naib-field-group" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_name" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Name', 'naibabiji-b2b-product-showcase' ); ?> <span style="color:red;">*</span></label>
                        <input type="text" class="naib-input" id="naib_bulk_name" name="name" required placeholder="<?php esc_attr_e( 'Your name', 'naibabiji-b2b-product-showcase' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'email', $form_fields ) ) : ?>
                    <div class="naib-field-group" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_email" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Email', 'naibabiji-b2b-product-showcase' ); ?> <span style="color:red;">*</span></label>
                        <input type="email" class="naib-input" id="naib_bulk_email" name="email" required placeholder="example@email.com" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'whatsapp', $form_fields ) ) : ?>
                    <div class="naib-field-group" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_whatsapp" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Phone / WhatsApp', 'naibabiji-b2b-product-showcase' ); ?></label>
                        <input type="text" class="naib-input" id="naib_bulk_whatsapp" name="whatsapp" placeholder="+1 234 567 890" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'job_title', $form_fields ) ) : ?>
                    <div class="naib-field-group" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_job_title" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Job Title', 'naibabiji-b2b-product-showcase' ); ?></label>
                        <input type="text" class="naib-input" id="naib_bulk_job_title" name="job_title" placeholder="<?php esc_attr_e( 'e.g. Purchasing Manager', 'naibabiji-b2b-product-showcase' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'company', $form_fields ) ) : ?>
                    <div class="naib-field-group" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_company" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Company', 'naibabiji-b2b-product-showcase' ); ?></label>
                        <input type="text" class="naib-input" id="naib_bulk_company" name="company" placeholder="<?php esc_attr_e( 'Your company name', 'naibabiji-b2b-product-showcase' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'country', $form_fields ) ) : ?>
                    <div class="naib-field-group" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_country" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Country', 'naibabiji-b2b-product-showcase' ); ?></label>
                        <input type="text" class="naib-input" id="naib_bulk_country" name="country" placeholder="<?php esc_attr_e( 'e.g. United States', 'naibabiji-b2b-product-showcase' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box;">
                    </div>
                <?php endif; ?>

                <?php if ( in_array( 'message', $form_fields ) ) : ?>
                    <div class="naib-field-group naib-field-full" style="margin-bottom:16px;">
                        <label class="naib-label" for="naib_bulk_message" style="display:block; margin-bottom:4px; font-size:13px; font-weight:500;"><?php esc_html_e( 'Inquiry Note', 'naibabiji-b2b-product-showcase' ); ?></label>
                        <textarea class="naib-textarea" id="naib_bulk_message" name="message" rows="4" placeholder="<?php esc_attr_e( 'Any additional notes...', 'naibabiji-b2b-product-showcase' ); ?>" style="width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); box-sizing:border-box; resize:vertical;"></textarea>
                    </div>
                <?php endif; ?>

                <!-- Honeypot -->
                <div style="display:none !important;">
                    <input type="text" name="naib_hp_field" tabindex="-1" value="">
                </div>

                <div class="naib-form-actions" style="margin-top:20px;">
                    <button type="submit" class="naib-b2b-btn naib-b2b-btn--primary" id="naib-bulk-submit-btn" style="background:var(--naibabiji-b2b-primary-color); color:#fff; border:none; padding:10px 24px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer; font-size:14px; width:100%;">
                        <span class="btn-text"><?php esc_html_e( 'Submit Inquiry', 'naibabiji-b2b-product-showcase' ); ?></span>
                        <span class="btn-spinner" style="display:none;"></span>
                    </button>
                </div>

                <div class="naib-form-status" style="display:none; margin-top:12px; padding:10px; border-radius:var(--naibabiji-b2b-border-radius); font-size:13px;"></div>
            </form>

            <div id="naib-bulk-inquiry-success" style="display:none; text-align:center; padding:20px 0;">
                <div style="font-size:48px; color:#2e7d32; margin-bottom:12px;">&#10003;</div>
                <p style="font-size:16px; font-weight:500; margin:0 0 8px;"><?php esc_html_e( 'Inquiry submitted successfully!', 'naibabiji-b2b-product-showcase' ); ?></p>
                <p style="color:#666; margin:0 0 4px;"><?php esc_html_e( 'We will get back to you via email shortly.', 'naibabiji-b2b-product-showcase' ); ?></p>
                <p id="naib-bulk-inquiry-id" style="color:#999; font-size:12px; margin:8px 0;"></p>
                <button type="button" class="naib-bulk-form-close" style="margin-top:12px; background:#f0f0f0; border:none; padding:8px 20px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer;"><?php esc_html_e( 'Close', 'naibabiji-b2b-product-showcase' ); ?></button>
            </div>
        </div>
    </div>
</div>
