<?php
/**
 * Inline Contact Form Template
 *
 * Used by the [naibabiji_b2b_contact_form] shortcode to render
 * a standalone contact form embedded directly in page content.
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="naibabiji-contact-form-wrapper">
    <form id="naibabiji-b2b-contact-form" class="naibabiji-contact-form naib-form-grid">
        <input type="hidden" name="action" value="naib_ai_save_lead">
        <input type="hidden" name="product_id" value="0">
        <input type="hidden" name="source" value="contact_form">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('naibabiji_b2b_product_nonce')); ?>">

        <?php if (in_array('name', $form_fields)) : ?>
            <div class="naib-field-group">
                <label class="naib-label" for="naib_cf_name"><?php esc_html_e('Name', 'naibabiji-b2b-product-showcase'); ?> <span class="required">*</span></label>
                <input type="text" class="naib-input" id="naib_cf_name" name="name" required placeholder="<?php esc_attr_e('Your name', 'naibabiji-b2b-product-showcase'); ?>">
            </div>
        <?php endif; ?>

        <?php if (in_array('email', $form_fields)) : ?>
            <div class="naib-field-group">
                <label class="naib-label" for="naib_cf_email"><?php esc_html_e('Email', 'naibabiji-b2b-product-showcase'); ?> <span class="required">*</span></label>
                <input type="email" class="naib-input" id="naib_cf_email" name="email" required placeholder="example@email.com">
            </div>
        <?php endif; ?>

        <?php if (in_array('whatsapp', $form_fields)) : ?>
            <div class="naib-field-group">
                <label class="naib-label" for="naib_cf_whatsapp"><?php esc_html_e('Phone / WhatsApp', 'naibabiji-b2b-product-showcase'); ?></label>
                <input type="text" class="naib-input" id="naib_cf_whatsapp" name="whatsapp" placeholder="+1 234 567 890">
            </div>
        <?php endif; ?>

        <?php if (in_array('job_title', $form_fields)) : ?>
            <div class="naib-field-group">
                <label class="naib-label" for="naib_cf_job_title"><?php esc_html_e('Job Title', 'naibabiji-b2b-product-showcase'); ?></label>
                <input type="text" class="naib-input" id="naib_cf_job_title" name="job_title" placeholder="<?php esc_attr_e('e.g. Purchasing Manager', 'naibabiji-b2b-product-showcase'); ?>">
            </div>
        <?php endif; ?>

        <?php if (in_array('company', $form_fields)) : ?>
            <div class="naib-field-group">
                <label class="naib-label" for="naib_cf_company"><?php esc_html_e('Company', 'naibabiji-b2b-product-showcase'); ?></label>
                <input type="text" class="naib-input" id="naib_cf_company" name="company" placeholder="<?php esc_attr_e('Your company name', 'naibabiji-b2b-product-showcase'); ?>">
            </div>
        <?php endif; ?>

        <?php if (in_array('country', $form_fields)) : ?>
            <div class="naib-field-group">
                <label class="naib-label" for="naib_cf_country"><?php esc_html_e('Country', 'naibabiji-b2b-product-showcase'); ?></label>
                <input type="text" class="naib-input" id="naib_cf_country" name="country" placeholder="<?php esc_attr_e('e.g. United States', 'naibabiji-b2b-product-showcase'); ?>">
            </div>
        <?php endif; ?>

        <?php if (in_array('message', $form_fields)) : ?>
            <div class="naib-field-group naib-field-full">
                <label class="naib-label" for="naib_cf_message"><?php esc_html_e('Message', 'naibabiji-b2b-product-showcase'); ?> <span class="required">*</span></label>
                <textarea class="naib-textarea" id="naib_cf_message" name="message" required rows="5" placeholder="<?php esc_attr_e('How can we help you?', 'naibabiji-b2b-product-showcase'); ?>"></textarea>
            </div>
        <?php endif; ?>

        <!-- Honeypot -->
        <div style="display:none !important;">
            <input type="text" name="naib_hp_field" tabindex="-1" value="">
        </div>

        <?php
        /**
         * Fires inside the contact form, before the submit button.
         * Useful for injecting captcha widgets.
         *
         * @since 4.2.0
         */
        do_action('naibabiji_contact_form_before_submit');
        ?>

        <div class="naib-form-actions">
            <button type="submit" class="naib-b2b-btn naib-b2b-btn--primary naibabiji-b2b-contact-submit-button">
                <span class="btn-text"><?php esc_html_e('Send Message', 'naibabiji-b2b-product-showcase'); ?></span>
                <span class="btn-spinner" style="display:none;"></span>
            </button>
        </div>

        <div class="naib-form-status" style="display:none;"></div>
    </form>

    <div id="naibabiji-b2b-contact-success" style="display:none;">
        <div class="naibabiji-contact-success-icon">&#10003;</div>
        <div class="naibabiji-contact-success-message"><?php echo wp_kses_post($success_msg); ?></div>
    </div>
</div>
