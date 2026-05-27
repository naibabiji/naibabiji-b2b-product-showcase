<?php
/**
 * Plugin Name: B2B Product Catalog – No E-Commerce, Global RFQ & Bulk Quote
 * Plugin URI: https://blog.naibabiji.com
 * Description: Lightweight B2B product catalog plugin without e-commerce. Showcase products, collect global RFQs and bulk quote requests with multilingual support – built for manufacturers, exporters, and wholesalers.
 * Version: 5.1.2
 * Author: Naibabiji
 * Text Domain: naibabiji-b2b-product-showcase
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Naibabiji_B2B_Product_Showcase
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugin constants
 */
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION', '5.1.2');
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_AUTHOR', 'Naibabiji');
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_URI', 'https://blog.naibabiji.com');
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_FILE', __FILE__);
define('NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 *
 * @since 1.0.0
 */
class Naibabiji_B2B_Product_Showcase
{

    /**
     * Singleton instance
     *
     * @var Naibabiji_B2B_Product_Showcase|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return Naibabiji_B2B_Product_Showcase
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        $this->init_hooks();
        $this->load_dependencies();
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        register_activation_hook(NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * Load dependency files
     *
     * @since 1.0.0
     */
    private function load_dependencies()
    {
        $files = array(
            'includes/class-security.php',
            'includes/class-taxonomy-fields.php',
            'includes/class-meta-fields.php',
            'includes/class-settings-helper.php',
            'includes/class-schema-builder.php',
            'includes/class-admin-settings.php',
            'includes/class-frontend-display.php',
            'includes/class-product-model.php',
            'includes/class-data-migration.php',
            'includes/class-shortcodes.php',
            'includes/class-template-loader.php',
            'includes/class-ajax-handlers.php',
            'includes/class-hooks.php',
            'includes/class-ai-client-handler-v2.php',
            'includes/class-leads-handler.php',
            'includes/class-contact-form-shortcode.php',
            'includes/class-bulk-inquiry-meta-fields.php',
            'includes/class-bulk-inquiry-csv-handler.php',
            'includes/post-types.php',
        );

        foreach ($files as $file) {
            if (file_exists(NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . $file)) {
                require_once NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . $file;
            }
        }
    }

    /**
     * Plugin activation
     *
     * @since 1.0.0
     */
    public function activate()
    {
        require_once NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'includes/post-types.php';
            
        // Register post type first (required for menu)
        naibabiji_b2b_register_post_type();
            
        // Create leads table
        Naibabiji_B2B_AI_Leads_Handler::create_table();
            
        $this->set_default_options();
            
        flush_rewrite_rules();
            
        update_option('naibabiji_b2b_product_showcase_activated', true);
    }

    /**
     * Plugin deactivation
     *
     * @since 1.0.0
     */
    public function deactivate()
    {
        // Clear scheduled cleanup events
        $timestamp = wp_next_scheduled('naibabiji_b2b_weekly_cleanup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'naibabiji_b2b_weekly_cleanup');
        }
        
        flush_rewrite_rules();
    }

    /**
     * WordPress init
     *
     * @since 1.0.0
     */
    public function init()
    {
        $components = array(
            'Naibabiji_B2B_Product_Meta_Fields',
            'Naibabiji_B2B_Product_Frontend_Display',
            'Naibabiji_B2B_Product_Shortcodes',
            'Naibabiji_B2B_Product_Template_Loader',
            'Naibabiji_B2B_Product_Ajax_Handlers',
            'Naibabiji_B2B_Product_Hooks',
            'Naibabiji_B2B_Product_Admin_Settings',
            'Naibabiji\B2B\Ai\AiClientHandler',
            'Naibabiji_B2B_AI_Leads_Handler',
            'Naibabiji_B2B_Contact_Form_Shortcode',
            'Naibabiji_B2B_Bulk_Inquiry_Meta_Fields',
        );

        foreach ($components as $component) {
            if (class_exists($component)) {
                $component::get_instance();
            }
        }

        if (class_exists('Naibabiji_B2B_Data_Migration')) {
            Naibabiji_B2B_Data_Migration::init();
        }

        if (get_option('naibabiji_b2b_product_showcase_activated')) {
            delete_option('naibabiji_b2b_product_showcase_activated');
            flush_rewrite_rules();
        }

        $current_version = get_option('naibabiji_b2b_product_showcase_version', '0');
        if (version_compare($current_version, NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION, '<')) {
            // Run database upgrades if needed
            $this->upgrade_database($current_version);
            update_option('naibabiji_b2b_product_showcase_version', NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION);
        }
    }

    /**
     * Admin init
     *
     * @since 1.0.10
     */
    public function admin_init()
    {
        // Check if table exists and create if not (for users who upgraded from old versions)
        $this->check_and_create_leads_table();
    }

    /**
     * Database upgrade routine for existing installations
     *
     * @since 4.0.1
     * @param string $old_version The previous version number
     */
    private function upgrade_database($old_version) {
        // Always ensure the leads table exists
        $this->check_and_create_leads_table();

        // 5.0.0: Add inquiry_type and inquiry_data columns for bulk inquiry
        if (version_compare($old_version, '5.0.0', '<')) {
            $this->upgrade_to_5_0_0();
        }
    }

    /**
     * Upgrade to 5.0.0 — add bulk inquiry columns to leads table
     */
    private function upgrade_to_5_0_0() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'naibb2pr_ai_leads';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Version-controlled schema upgrade; table name is internally constructed, not user input
        $columns = $wpdb->get_col("SHOW COLUMNS FROM `{$table_name}`", 0);
        if (!is_array($columns)) {
            $columns = array();
        }

        if (!in_array('inquiry_type', $columns, true)) {
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `inquiry_type` VARCHAR(20) DEFAULT 'single' COMMENT 'Inquiry type: single/bulk'");
        }

        if (!in_array('inquiry_data', $columns, true)) {
            $wpdb->query("ALTER TABLE `{$table_name}` ADD COLUMN `inquiry_data` LONGTEXT NULL COMMENT 'Bulk inquiry data (JSON)'");
        }

        $index_exists = $wpdb->get_var("SHOW INDEX FROM `{$table_name}` WHERE Key_name = 'idx_inquiry_type'");
        if (empty($index_exists)) {
            $wpdb->query("ALTER TABLE `{$table_name}` ADD INDEX `idx_inquiry_type` (`inquiry_type`)");
        }
        // phpcs:enable
    }

    /**
     * Check if leads table exists and create it if not
     * Safe to run multiple times (idempotent)
     *
     * @since 4.0.1
     */
    private function check_and_create_leads_table() {
        if (class_exists('Naibabiji_B2B_AI_Leads_Handler')) {
            Naibabiji_B2B_AI_Leads_Handler::create_table();
        }
    }

    /**
     * Frontend scripts and styles
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {
        if (!$this->should_enqueue_frontend_assets()) {
            return;
        }

        $force_reload = get_option('naibabiji_b2b_product_force_reload', false);
        $version = $force_reload ? NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION . '.' . time() : NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION;

        wp_enqueue_style(
            'naibabiji-b2b-product-showcase-style',
            NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $version
        );

        if (get_option('naibabiji_b2b_ai_enable', false) && is_singular('naibb2pr_products')) {
            wp_enqueue_style(
                'naibabiji-b2b-ai-chat-style',
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/css/ai-chat.css',
                array(),
                $version
            );
        }

        $btn_color = sanitize_hex_color(Naibabiji_B2B_Settings::get('button_color'));
        if (!$btn_color) {
            $btn_color = '#0A7AFF';
        }
        $btn_hover = sanitize_hex_color(Naibabiji_B2B_Settings::get('button_hover_color'));
        if (!$btn_hover) {
            $btn_hover = '#085FCC';
        }
        $content_width = $this->sanitize_css_size_value(Naibabiji_B2B_Settings::get('content_width'), '');
        $border_radius = $this->sanitize_css_size_list(Naibabiji_B2B_Settings::get('border_radius'), '8px');
        $ai_float_offset = $this->sanitize_css_size_value(get_option('naibabiji_b2b_ai_float_offset', '30px'), '30px');

        $btn_color_rgb = $this->hex_to_rgb($btn_color);
        $btn_hover_rgb = $this->hex_to_rgb($btn_hover);

        $dynamic_css = "
:root {
    --naibabiji-b2b-primary-color: {$btn_color};
    --naibabiji-b2b-primary-hover: {$btn_hover};
    --naibabiji-b2b-primary-rgb: {$btn_color_rgb};
    --naibabiji-b2b-primary-hover-rgb: {$btn_hover_rgb};
    --naibabiji-b2b-border-radius: {$border_radius};
    --ai-primary: {$btn_color};
    --ai-indigo: {$btn_hover};
    --ai-primary-rgb: {$btn_color_rgb};
    --ai-border-radius: {$border_radius};
    --ai-float-position: " . esc_attr(get_option('naibabiji_b2b_ai_float_position', 'bottom-right')) . ";
    --ai-float-offset: {$ai_float_offset};
}
.naibabiji-b2b-view-details-button,
.naibabiji-b2b-inquiry-button,
.naibabiji-b2b-category-button,
.page-numbers,
.naibabiji-b2b-seo-content,
.naibabiji-b2b-no-products,
.naibabiji-b2b-product-inquiry,
.naibabiji-b2b-related-products,
.naibabiji-b2b-gallery-thumb {
    border-radius: var(--naibabiji-b2b-border-radius) !important;
}
.naibabiji-b2b-view-details-button,
.naibabiji-b2b-inquiry-button,
.naibabiji-b2b-category-button {
    background-color: {$btn_color};
    border-color: {$btn_color};
    color: #ffffff;
}
.naibabiji-b2b-view-details-button:hover,
.naibabiji-b2b-inquiry-button:hover,
.naibabiji-b2b-category-button:hover,
.naibabiji-b2b-view-details-button:focus,
.naibabiji-b2b-inquiry-button:focus,
.naibabiji-b2b-category-button:focus {
    background-color: {$btn_hover};
    border-color: {$btn_hover};
    color: #ffffff;
}
.naibabiji-b2b-product-card {
    border-radius: var(--naibabiji-b2b-border-radius) !important;
    overflow: hidden;
}
.naibabiji-b2b-product-card:hover {
    border-color: {$btn_color};
}
.naibabiji-b2b-product-thumbnail {
    border-radius: var(--naibabiji-b2b-border-radius) var(--naibabiji-b2b-border-radius) 0 0 !important;
}
.naibabiji-b2b-product-card img,
.naibabiji-b2b-gallery-item img,
.naibabiji-b2b-featured-image,
.naibabiji-b2b-thumb-image,
.naibabiji-b2b-product-featured-image,
.naibabiji-b2b-featured-image-wrapper,
.naibabiji-b2b-seo-content img {
    border-radius: var(--naibabiji-b2b-border-radius) !important;
}";

        if (!empty($content_width)) {
            $dynamic_css .= "
.naibabiji-b2b-product-single-container {
    max-width: {$content_width};
}
.naibabiji-b2b-products-taxonomy-container,
.naibabiji-b2b-products-archive-container {
    max-width: {$content_width};
}";
        }

        wp_add_inline_style('naibabiji-b2b-product-showcase-style', $dynamic_css);

        wp_enqueue_script(
            'naibabiji-b2b-product-showcase-script',
            NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $version,
            true
        );

        if (get_option('naibabiji_b2b_ai_enable', false) && is_singular('naibb2pr_products')) {
            wp_enqueue_script(
                'naibabiji-b2b-ai-chat-script',
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/js/ai-chat.js',
                array('jquery'),
                $version,
                true
            );

            wp_localize_script('naibabiji-b2b-ai-chat-script', 'naibabiji_ai_chat_vars', array(
                'current_post_id' => is_singular('naibb2pr_products') ? get_queried_object_id() : 0,
                'float_position' => get_option('naibabiji_b2b_ai_float_position', 'bottom-right'),
                'ajax_url' => admin_url('admin-ajax.php'),
                'i18n' => array(
                    'title' => __('Online Customer Service (AI)', 'naibabiji-b2b-product-showcase'),
                    'welcome' => __('Hello! Is there anything I can help you with?', 'naibabiji-b2b-product-showcase'),
                    'lead_intro' => __('Sorry, I cannot fully answer your question. Please leave your contact information and we will get back to you as soon as possible:', 'naibabiji-b2b-product-showcase'),
                    'contact_placeholder' => __('Email/WeChat/Mobile/WhatsApp', 'naibabiji-b2b-product-showcase'),
                    'message_placeholder' => __('Your specific needs...', 'naibabiji-b2b-product-showcase'),
                    'submit_btn' => __('Submit', 'naibabiji-b2b-product-showcase'),
                    'input_placeholder' => __('Input your question...', 'naibabiji-b2b-product-showcase'),
                    'send_btn' => __('Send', 'naibabiji-b2b-product-showcase'),
                )
            ));
        }

        wp_localize_script(
            'naibabiji-b2b-product-showcase-script',
            'naibabiji_b2b_product_showcase',
            array(
            'ajax_url' => admin_url('admin-ajax.php'),
        )
        );

        // Localize strings for the inline contact form (only when shortcode is present)
        wp_localize_script(
            'naibabiji-b2b-product-showcase-script',
            'naibabiji_b2b_contact_form_i18n',
            array(
                'invalid_email' => __('Please enter a valid email address.', 'naibabiji-b2b-product-showcase'),
                'rate_limit'   => __('Please wait a moment before sending another message.', 'naibabiji-b2b-product-showcase'),
                'network_error' => __('Network error. Please try again later.', 'naibabiji-b2b-product-showcase'),
                'retry_failed' => __('Security verification failed. Please reload the page and try again.', 'naibabiji-b2b-product-showcase'),
            )
        );

        if ($force_reload) {
            delete_option('naibabiji_b2b_product_force_reload');
        }

        // Bulk inquiry assets — load on all product pages and any frontend page
        // where the inquiry cart float is rendered (form modal is always in wp_footer).
        if ($this->should_enqueue_frontend_assets()) {
            wp_enqueue_script(
                'naibabiji-b2b-bulk-inquiry',
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/js/bulk-inquiry.js',
                array('jquery'),
                $version,
                true
            );

            wp_localize_script('naibabiji-b2b-bulk-inquiry', 'naib_bulk_inquiry', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('naibabiji_b2b_bulk_inquiry_nonce'),
                'i18n'     => array(
                    'cart_empty'     => __('Your inquiry cart is empty', 'naibabiji-b2b-product-showcase'),
                    'added_success'  => __('Added {count} specs to cart', 'naibabiji-b2b-product-showcase'),
                    'submit_success' => __('Inquiry submitted successfully!', 'naibabiji-b2b-product-showcase'),
                    'network_error'  => __('Network error. Please try again.', 'naibabiji-b2b-product-showcase'),
                    'submit_failed'  => __('Submission failed. Please try again.', 'naibabiji-b2b-product-showcase'),
                    'quantity_error' => __('Quantity must be greater than 0', 'naibabiji-b2b-product-showcase'),
                    'required_field' => __('This field is required', 'naibabiji-b2b-product-showcase'),
                    'selected_count'  => __('Selected: {count} specs', 'naibabiji-b2b-product-showcase'),
                ),
            ));

            wp_enqueue_style(
                'naibabiji-b2b-bulk-inquiry',
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/css/bulk-inquiry.css',
                array(),
                $version
            );
        }
    }

    /**
     * Determine whether frontend assets should be loaded.
     *
     * @since 1.1.0
     * @return bool
     */
    private function should_enqueue_frontend_assets()
    {
        if (is_admin()) {
            return false;
        }

        if (
        is_singular('naibb2pr_products') ||
        is_post_type_archive('naibb2pr_products') ||
        is_tax(array('naibb2pr_product_category', 'naibb2pr_product_tag'))
        ) {
            return true;
        }

        if (is_singular()) {
            $post = get_post();
            if ($post) {
                $content = $post->post_content;
                if (has_shortcode($content, 'naibabiji_b2b_products') || has_shortcode($content, 'naibabiji_b2b_product_categories') || has_shortcode($content, 'naibabiji_b2b_contact_form')) {
                    return true;
                }

                if (function_exists('has_block') && (
                has_block('naibabiji-b2b/product-grid', $post) ||
                has_block('naibabiji-b2b/category-grid', $post)
                )) {
                    return true;
                }
            }
        }

        if ($this->is_shortcode_in_widgets(array('naibabiji_b2b_products', 'naibabiji_b2b_product_categories', 'naibabiji_b2b_contact_form'))) {
            return true;
        }

        /**
         * Filters whether the frontend assets should be enqueued.
         *
         * @since 1.1.0
         * @param bool $enqueue Whether to enqueue assets. Default false.
         */
        return (bool)apply_filters('naibabiji_b2b_should_enqueue_frontend_assets', false);
    }

    /**
     * Check if shortcode exists in widgets
     *
     * @since 1.1.1
     * @param array $shortcodes Shortcodes to search for
     * @return bool
     */
    private function is_shortcode_in_widgets($shortcodes)
    {
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Safe to use for reading sidebar widgets
        global $wp_registered_sidebars;
        $sidebars_widgets = get_option('sidebars_widgets', array());

        if (empty($sidebars_widgets)) {
            return false;
        }

        foreach ($sidebars_widgets as $sidebar_id => $widget_ids) {
            if ('wp_inactive_widgets' === $sidebar_id || empty($widget_ids)) {
                continue;
            }

            if (!is_array($widget_ids)) {
                continue;
            }

            foreach ($widget_ids as $widget_id) {
                if (preg_match('/^(.+?)-(\d+)$/', $widget_id, $matches)) {
                    $widget_type = $matches[1];
                    $widget_instance_id = $matches[2];

                    $widget_instances = get_option('widget_' . $widget_type, array());

                    if (!isset($widget_instances[$widget_instance_id])) {
                        continue;
                    }

                    $widget_data = $widget_instances[$widget_instance_id];

                    if (!is_array($widget_data)) {
                        continue;
                    }

                    if (isset($widget_data['text']) && is_string($widget_data['text'])) {
                        foreach ($shortcodes as $shortcode) {
                            if (has_shortcode($widget_data['text'], $shortcode)) {
                                return true;
                            }
                        }
                    }

                    if (isset($widget_data['content']) && is_string($widget_data['content'])) {
                        foreach ($shortcodes as $shortcode) {
                            if (has_shortcode($widget_data['content'], $shortcode)) {
                                return true;
                            }
                        }
                    }

                    foreach ($widget_data as $field_value) {
                        if (is_string($field_value)) {
                            foreach ($shortcodes as $shortcode) {
                                if (has_shortcode($field_value, $shortcode)) {
                                    return true;
                                }
                            }
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Admin scripts and styles
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook suffix
     */
    public function admin_enqueue_scripts($hook)
    {
        // Guard against null $hook in some WordPress contexts (PHP 8.1+)
        if (null === $hook || '' === $hook) {
            return;
        }

        $product_screens = array('post.php', 'post-new.php', 'edit.php');
        $is_product_page = in_array($hook, $product_screens, true);
        $is_settings_page = ('settings_page_naibabiji-b2b-product-showcase' === $hook);
        $is_generator_page = (strpos($hook, 'naibabiji-b2b-shortcode-generator') !== false);

        // Detect B2B Showcase pages via GET param (more reliable than hook name)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page detection, no data is processed
        $admin_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $is_b2b_showcase = (
            strpos($admin_page, 'naibabiji-b2b') === 0 ||
            strpos($hook, 'naibabiji-b2b') !== false
        );

        global $post_type;
        if (!$is_product_page && 'naibb2pr_products' === $post_type) {
            $is_product_page = true;
        }

        if ($is_product_page || $is_settings_page || $is_generator_page || $is_b2b_showcase) {
            wp_enqueue_style(
                'naibabiji-b2b-product-showcase-admin-style',
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION
            );

            // Inject brand accent color as CSS variables for Stripe-inspired admin UI
            $accent = sanitize_hex_color(get_option('naibabiji_b2b_product_button_color', '#0A7AFF'));
            if (!$accent) {
                $accent = '#0A7AFF';
            }
            $accent_hover = $this->adjust_hex_brightness($accent, -10);
            $accent_light = $this->adjust_hex_brightness($accent, 88);
            $border_radius = $this->sanitize_css_size_list(Naibabiji_B2B_Settings::get('border_radius'), '8px');
            wp_add_inline_style('naibabiji-b2b-product-showcase-admin-style', "
                :root {
                    --naib-accent: {$accent};
                    --naib-accent-hover: {$accent_hover};
                    --naib-accent-light: {$accent_light};
                    --naibabiji-b2b-border-radius: {$border_radius};
                }
            ");

            wp_enqueue_script(
                'naibabiji-b2b-product-showcase-admin-script',
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery'),
                NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION,
                true
            );

            wp_localize_script(
                'naibabiji-b2b-product-showcase-admin-script',
                'naibabiji_b2b_product_showcase_admin',
                array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('naibabiji_b2b_product_admin_nonce'),
                'strings' => array(
                    'confirm_delete' => __('Are you sure you want to delete this item?', 'naibabiji-b2b-product-showcase'),
                    'default_inquiry_text' => __('Get Quote', 'naibabiji-b2b-product-showcase'),
                ),
            )
            );
        }
    }

    /**
     * Sanitize a single CSS size token before outputting inline styles.
     *
     * @param string $value   Raw size value.
     * @param string $default Fallback value.
     * @return string
     */
    private function sanitize_css_size_value($value, $default) {
        $value = trim((string) $value);
        return $this->is_valid_css_size_token($value) ? $value : $default;
    }

    /**
     * Sanitize a 1-4 value CSS size list, used for border-radius.
     *
     * @param string $value   Raw size list.
     * @param string $default Fallback value.
     * @return string
     */
    private function sanitize_css_size_list($value, $default) {
        $parts = preg_split('/\s+/', trim((string) $value));
        if (!$parts || count($parts) > 4) {
            return $default;
        }
        foreach ($parts as $part) {
            if (!$this->is_valid_css_size_token($part)) {
                return $default;
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Check a CSS size token against a small allowlist of safe units.
     *
     * @param string $value CSS size token.
     * @return bool
     */
    private function is_valid_css_size_token($value) {
        return '0' === $value || (bool) preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|%|vh|vw)$/i', $value);
    }

    /**
     * Adjust hex color brightness by a percentage.
     *
     * @param string $hex       Hex color string (with or without #).
     * @param int    $percent   Positive lightens, negative darkens. Range roughly -100 to 100.
     * @return string Hex color (with #).
     */
    private function adjust_hex_brightness($hex, $percent) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = max(0, min(255, hexdec(substr($hex, 0, 2)) + (int) (255 * $percent / 100)));
        $g = max(0, min(255, hexdec(substr($hex, 2, 2)) + (int) (255 * $percent / 100)));
        $b = max(0, min(255, hexdec(substr($hex, 4, 2)) + (int) (255 * $percent / 100)));
        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    /**
     * Convert hex color to RGB string (e.g. "255, 255, 255")
     *
     * @param string $hex Hex color string (with or without #).
     * @return string RGB values separated by commas.
     */
    private function hex_to_rgb($hex) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "$r, $g, $b";
    }

    /**
     * Set default options
     *
     * @since 1.0.0
     */
    private function set_default_options()
    {
        $default_options = array(
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
            'inquiry_button_text' => __('Get Quote', 'naibabiji-b2b-product-showcase'),
            'products_per_page' => 12,
            'enable_gallery' => 1,
            'enable_inquiry_button' => 1,
            'enable_short_description' => 1,
        );

        foreach ($default_options as $key => $value) {
            $option_name = 'naibabiji_b2b_product_' . $key;
            if (false === get_option($option_name)) {
                add_option($option_name, $value);
            }
        }
    }

// AJAX handling has been moved to class-ajax-handlers.php for centralized management
}

// Register hooks
add_action('init', 'naibabiji_b2b_register_post_type', 10);
add_action('init', 'naibabiji_b2b_register_taxonomies', 20);

/**
 * Get plugin main instance
 *
 * @since 1.0.0
 * @return Naibabiji_B2B_Product_Showcase Plugin instance
 */
function naibabiji_b2b_product_showcase()
{
    return Naibabiji_B2B_Product_Showcase::get_instance();
}

// Initialize plugin
naibabiji_b2b_product_showcase();

/**
 * Add "Help" link to plugin row meta
 *
 * @param string[] $links Existing links
 * @param string   $file  Plugin basename
 * @return string[]
 */
add_filter('plugin_row_meta', 'naibabiji_b2b_product_row_meta', 10, 2);
function naibabiji_b2b_product_row_meta($links, $file)
{
    if ($file === NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_BASENAME) {
        $links[] = '<a href="https://blog.naibabiji.com/files/wordpress-plugins/naibabiji-b2b-product-showcase.html" target="_blank" rel="noopener noreferrer">' . __('Help', 'naibabiji-b2b-product-showcase') . '</a>';
    }
    return $links;
}

/**
 * Add Settings link to plugin action links
 *
 * @param string[] $links Existing links
 * @return string[]
 */
add_filter('plugin_action_links_' . NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_BASENAME, 'naibabiji_b2b_product_action_links');
function naibabiji_b2b_product_action_links($links)
{
    $settings_link = '<a href="' . admin_url('admin.php?page=naibabiji-b2b-showcase-settings') . '">' . __('Settings', 'naibabiji-b2b-product-showcase') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Check if Linghang theme meets minimum version for template compatibility.
 *
 * This notice is intentionally not dismissible — a version mismatch between
 * theme and plugin templates can cause fatal errors on the product pages.
 *
 * @since 5.0.0
 */
function naibabiji_b2b_check_theme_compatibility() {
    $theme = wp_get_theme('linghang');
    if (!$theme->exists()) {
        return;
    }

    $min_version = '2.2.0';
    $current     = $theme->get('Version');

    if (version_compare($current, $min_version, '>=')) {
        return;
    }

    printf(
        '<div class="notice notice-error">
            <p><strong>%s</strong> %s</p>
        </div>',
        esc_html__('B2B Product Showcase — Action Required:', 'naibabiji-b2b-product-showcase'),
        sprintf(
            /* translators: 1: current theme version, 2: required theme version */
            esc_html__('Your Linghang theme (v%1$s) must be updated to v%2$s or later. The theme ships template overrides that are incompatible with older versions — leaving the theme outdated may cause product pages to break.', 'naibabiji-b2b-product-showcase'),
            esc_html($current),
            esc_html($min_version)
        )
    );
}
add_action('admin_notices', 'naibabiji_b2b_check_theme_compatibility');
