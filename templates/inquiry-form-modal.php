<?php
/**
 * Inquiry Form Modal Template
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="naibabiji-b2b-inquiry-modal" class="naib-modal-wrapper">
    <div class="naib-overlay"></div>
    <div class="naib-panel">
        <div class="naib-panel__header">
            <h3><?php esc_html_e('Quick Inquiry', 'naibabiji-b2b-product-showcase'); ?></h3>
            <button type="button" class="naib-panel__close">&times;</button>
        </div>
        <div class="naib-panel__body">
            <div class="naibabiji-b2b-product-context-header">
                <span class="context-label"><?php esc_html_e('Inquiry for:', 'naibabiji-b2b-product-showcase'); ?></span>
                <span id="naib-modal-product-title" class="context-title"></span>
            </div>
            
            <form id="naibabiji-b2b-inquiry-form" class="naib-form-grid">
                <input type="hidden" name="action" value="naib_ai_save_lead">
                <input type="hidden" id="naib-modal-product-id" name="product_id" value="0">
                <input type="hidden" name="source" value="inquiry_form">
                <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('naibabiji_b2b_product_nonce')); ?>">
                
                <?php if (in_array('name', $form_fields)) : ?>
                    <div class="naib-field-group">
                        <label class="naib-label" for="naib_name"><?php esc_html_e('Name', 'naibabiji-b2b-product-showcase'); ?> <span class="required">*</span></label>
                        <input type="text" class="naib-input" id="naib_name" name="name" required placeholder="<?php esc_attr_e('Your name', 'naibabiji-b2b-product-showcase'); ?>">
                    </div>
                <?php endif; ?>

                <?php if (in_array('email', $form_fields)) : ?>
                    <div class="naib-field-group">
                        <label class="naib-label" for="naib_email"><?php esc_html_e('Email', 'naibabiji-b2b-product-showcase'); ?> <span class="required">*</span></label>
                        <input type="email" class="naib-input" id="naib_email" name="email" required placeholder="example@email.com">
                    </div>
                <?php endif; ?>

                <?php if (in_array('whatsapp', $form_fields)) : ?>
                    <div class="naib-field-group">
                        <label class="naib-label" for="naib_whatsapp"><?php esc_html_e('Phone / WhatsApp', 'naibabiji-b2b-product-showcase'); ?></label>
                        <input type="text" class="naib-input" id="naib_whatsapp" name="whatsapp" placeholder="+1 234 567 890">
                    </div>
                <?php endif; ?>

                <?php if (in_array('job_title', $form_fields)) : ?>
                    <div class="naib-field-group">
                        <label class="naib-label" for="naib_job_title"><?php esc_html_e('Job Title', 'naibabiji-b2b-product-showcase'); ?></label>
                        <input type="text" class="naib-input" id="naib_job_title" name="job_title" placeholder="<?php esc_attr_e('e.g. Purchasing Manager', 'naibabiji-b2b-product-showcase'); ?>">
                    </div>
                <?php endif; ?>

                <?php if (in_array('company', $form_fields)) : ?>
                    <div class="naib-field-group">
                        <label class="naib-label" for="naib_company"><?php esc_html_e('Company', 'naibabiji-b2b-product-showcase'); ?></label>
                        <input type="text" class="naib-input" id="naib_company" name="company" placeholder="<?php esc_attr_e('Your company name', 'naibabiji-b2b-product-showcase'); ?>">
                    </div>
                <?php endif; ?>

                <?php if (in_array('country', $form_fields)) : ?>
                    <div class="naib-field-group">
                        <label class="naib-label" for="naib_country"><?php esc_html_e('Country', 'naibabiji-b2b-product-showcase'); ?></label>
                        <input type="text" class="naib-input" id="naib_country" name="country" placeholder="<?php esc_attr_e('e.g. United States', 'naibabiji-b2b-product-showcase'); ?>">
                    </div>
                <?php endif; ?>

                <?php if (in_array('message', $form_fields)) : ?>
                    <div class="naib-field-group naib-field-full">
                        <label class="naib-label" for="naib_message"><?php esc_html_e('Inquiry Message', 'naibabiji-b2b-product-showcase'); ?> <span class="required">*</span></label>
                        <textarea class="naib-textarea" id="naib_message" name="message" required rows="4" placeholder="<?php esc_attr_e('I am interested in this product. Please send me more details...', 'naibabiji-b2b-product-showcase'); ?>"></textarea>
                    </div>
                <?php endif; ?>

                <!-- Honeypot -->
                <div style="display:none !important;">
                    <input type="text" name="naib_hp_field" tabindex="-1" value="">
                </div>

                <div class="naib-form-actions">
                    <button type="submit" class="naib-b2b-btn naib-b2b-btn--primary naibabiji-b2b-submit-button">
                        <span class="btn-text"><?php esc_html_e('Send Inquiry', 'naibabiji-b2b-product-showcase'); ?></span>
                        <span class="btn-spinner" style="display:none;"></span>
                    </button>
                </div>
                
                <div class="naib-form-status" style="display:none;"></div>
            </form>
            
            <div id="naibabiji-b2b-inquiry-success" style="display:none;">
                <div class="success-icon">✓</div>
                <div class="success-message"><?php echo wp_kses_post($success_msg); ?></div>
                <button type="button" class="naib-b2b-btn naib-b2b-btn--secondary naibabiji-b2b-modal-close-btn"><?php esc_html_e('Close', 'naibabiji-b2b-product-showcase'); ?></button>
            </div>
        </div>
    </div>
</div>
