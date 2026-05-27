<?php
/**
 * Contact Form Shortcode Class
 *
 * Registers the [naibabiji_b2b_contact_form] shortcode that renders
 * a standalone inline contact form anywhere on the site.
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Contact_Form_Shortcode {

    /**
     * Singleton instance
     *
     * @var Naibabiji_B2B_Contact_Form_Shortcode|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 4.2.0
     * @return Naibabiji_B2B_Contact_Form_Shortcode
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 4.2.0
     */
    private function __construct() {
        add_shortcode('naibabiji_b2b_contact_form', array($this, 'render_contact_form'));
    }

    /**
     * Render the contact form shortcode.
     *
     * Usage:
     *   [naibabiji_b2b_contact_form]
     *   [naibabiji_b2b_contact_form title="Get a Quote"]
     *
     * @since 4.2.0
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_contact_form($atts) {
        $atts = shortcode_atts(array(
            'title' => '',
        ), $atts, 'naibabiji_b2b_contact_form');

        // Reuse backend-configured form fields
        $form_fields = Naibabiji_B2B_Settings::get('inquiry_form_fields', array('name', 'email', 'message'));

        // Ensure required fields are always present
        $required_fields = array('name', 'email', 'message');
        foreach ($required_fields as $field) {
            if (!in_array($field, $form_fields)) {
                $form_fields[] = $field;
            }
        }

        // Reuse backend-configured success message
        $success_msg = Naibabiji_B2B_Settings::get('inquiry_success_msg', __('Thank you! Your message has been sent successfully.', 'naibabiji-b2b-product-showcase'));

        ob_start();
        include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/inquiry-form-inline.php';
        return ob_get_clean();
    }
}
