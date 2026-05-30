<?php
/**
 * Admin Settings Page Class
 * 
 * Adds a "Product Showcase" settings page under the WordPress Settings menu
 * Uses WordPress core Settings API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product_Admin_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_backward_compat_redirects'));
    }
    
    /**
     * Add admin menu
     * Uses WordPress core function add_options_page()
     */
    public function add_admin_menu() {
        // Top-level menu: B2B Showcase
        add_menu_page(
            __('B2B Showcase', 'naibabiji-b2b-product-showcase'),
            __('B2B Showcase', 'naibabiji-b2b-product-showcase'),
            'manage_options',
            'naibabiji-b2b-showcase',
            array($this, 'render_dashboard_page'),
            'dashicons-store',
            21
        );

        // Submenu: Dashboard
        add_submenu_page(
            'naibabiji-b2b-showcase',
            __('Dashboard', 'naibabiji-b2b-product-showcase'),
            __('Dashboard', 'naibabiji-b2b-product-showcase'),
            'manage_options',
            'naibabiji-b2b-showcase',
            array($this, 'render_dashboard_page')
        );

        // Submenu: Shortcode Generator (migrated from Products menu)
        add_submenu_page(
            'naibabiji-b2b-showcase',
            __('Shortcode Generator', 'naibabiji-b2b-product-showcase'),
            __('Shortcode Generator', 'naibabiji-b2b-product-showcase'),
            'manage_options',
            'naibabiji-b2b-shortcode-generator',
            array($this, 'render_shortcode_generator_page')
        );

        // Submenu: Settings (migrated from Settings menu, AI settings merged in)
        add_submenu_page(
            'naibabiji-b2b-showcase',
            __('Settings', 'naibabiji-b2b-product-showcase'),
            __('Settings', 'naibabiji-b2b-product-showcase'),
            'manage_options',
            'naibabiji-b2b-showcase-settings',
            array($this, 'render_settings_page')
        );

        // Submenu: Help (extracted from Settings Help tab)
        add_submenu_page(
            'naibabiji-b2b-showcase',
            __('Help', 'naibabiji-b2b-product-showcase'),
            __('Help', 'naibabiji-b2b-product-showcase'),
            'manage_options',
            'naibabiji-b2b-help',
            array($this, 'render_help_page')
        );

    }
    
    /**
     * Register settings
     * Uses WordPress core Settings API
     */
    public function register_settings() {
        // Register settings group
        // Appearance
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_button_color', array(
            'type' => 'string',
            'default' => '#0A7AFF',
            'sanitize_callback' => array($this, 'sanitize_hex_color_strict')
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_button_hover_color', array(
            'type' => 'string',
            'default' => '#085FCC',
            'sanitize_callback' => array($this, 'sanitize_hex_color_strict')
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_button_text', array(
            'type' => 'string',
            'default' => __('Get Quote', 'naibabiji-b2b-product-showcase'),
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_default_inquiry_url', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'esc_url_raw'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_mode', array(
            'type' => 'string',
            'default' => 'external',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_default_inquiry_type', array(
            'type' => 'string',
            'default' => 'standard',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_form_fields', array(
            'type' => 'array',
            'default' => array('name', 'email', 'message'),
            'sanitize_callback' => array($this, 'sanitize_array')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_success_msg', array(
            'type' => 'string',
            'default' => __('Thank you! Your inquiry has been sent successfully.', 'naibabiji-b2b-product-showcase'),
            'sanitize_callback' => 'sanitize_textarea_field'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_redirect_enabled', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_redirect_url', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => array($this, 'sanitize_redirect_url')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_enable_breadcrumbs', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_enable_schema', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_show_meta', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_excerpt_length', array(
            'type' => 'integer',
            'default' => 20,
            'sanitize_callback' => 'absint'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_columns', array(
            'type' => 'integer',
            'default' => 3,
            'sanitize_callback' => array($this, 'sanitize_grid_columns')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_default_product_sort', array(
            'type' => 'string',
            'default' => 'date-desc',
            'sanitize_callback' => array($this, 'sanitize_product_sort')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_display_mode', array(
            'type' => 'string',
            'default' => 'default',
            'sanitize_callback' => array($this, 'sanitize_archive_display_mode')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_content_width', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => array($this, 'sanitize_content_width')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_border_radius', array(
            'type' => 'string',
            'default' => '8px',
            'sanitize_callback' => array($this, 'sanitize_border_radius')
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_schema_currency', array(
            'type' => 'string',
            'default' => 'USD',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        // Archive Page SEO Content Settings
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_hide_title', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_seo_content_top', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'wp_kses_post'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_enable_seo_top', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_seo_content_bottom', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'wp_kses_post'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_enable_seo_bottom', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));

        // AI Customer Service — registered under separate group for dedicated settings page
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_enable', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_license_key', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_manager_url', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'esc_url_raw'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_service_profile', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_faqs', array(
            'type' => 'string',
            'default' => '',
            'sanitize_callback' => 'sanitize_textarea_field'
        ));
        
        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_float_position', array(
            'type' => 'string',
            'default' => 'bottom-right',
            'sanitize_callback' => 'sanitize_text_field'
        ));

        register_setting('naibabiji_b2b_product_settings', 'naibabiji_b2b_ai_float_offset', array(
            'type' => 'string',
            'default' => '30px',
            'sanitize_callback' => array($this, 'sanitize_css_size')
        ));

        // AI settings sections & fields (registered under dedicated page slugs)
        add_settings_section(
            'naibabiji_b2b_ai_connection_section',
            '',
            array($this, 'ai_connection_section_callback'),
            'naibabiji_b2b_ai_connection'
        );

        add_settings_field(
            'ai_enable',
            __('Enable AI Customer Service', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_enable_callback'),
            'naibabiji_b2b_ai_connection',
            'naibabiji_b2b_ai_connection_section'
        );

        add_settings_field(
            'ai_license_key',
            __('AI License Key', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_license_key_callback'),
            'naibabiji_b2b_ai_connection',
            'naibabiji_b2b_ai_connection_section'
        );

        add_settings_field(
            'ai_manager_url',
            __('AI Manager API URL', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_manager_url_callback'),
            'naibabiji_b2b_ai_connection',
            'naibabiji_b2b_ai_connection_section'
        );

        add_settings_section(
            'naibabiji_b2b_ai_knowledge_section',
            '',
            array($this, 'ai_knowledge_section_callback'),
            'naibabiji_b2b_ai_knowledge'
        );

        add_settings_field(
            'ai_service_profile',
            __('Company Profile', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_service_profile_callback'),
            'naibabiji_b2b_ai_knowledge',
            'naibabiji_b2b_ai_knowledge_section'
        );

        add_settings_field(
            'ai_faqs',
            __('FAQ Knowledge Base', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_faqs_callback'),
            'naibabiji_b2b_ai_knowledge',
            'naibabiji_b2b_ai_knowledge_section'
        );
        
        // AI Display Section
        add_settings_section(
            'naibabiji_b2b_ai_display_section',
            '',
            array($this, 'ai_display_section_callback'),
            'naibabiji_b2b_ai_display'
        );

        add_settings_field(
            'ai_float_position',
            __('Floating Widget Position', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_float_position_callback'),
            'naibabiji_b2b_ai_display',
            'naibabiji_b2b_ai_display_section'
        );

        add_settings_field(
            'ai_float_offset',
            __('Floating Widget Offset', 'naibabiji-b2b-product-showcase'),
            array($this, 'ai_float_offset_callback'),
            'naibabiji_b2b_ai_display',
            'naibabiji_b2b_ai_display_section'
        );

        
        // Add settings sections
        
        add_settings_section(
            'naibabiji_b2b_product_appearance_section',
            __('Appearance Settings', 'naibabiji-b2b-product-showcase'),
            array($this, 'appearance_section_callback'),
            'naibabiji_b2b_product_settings'
        );
        
        add_settings_section(
            'naibabiji_b2b_product_inquiry_section',
            __('Inquiry Settings', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_section_callback'),
            'naibabiji_b2b_product_settings'
        );
        
        add_settings_section(
            'naibabiji_b2b_product_seo_section',
            __('SEO Settings', 'naibabiji-b2b-product-showcase'),
            array($this, 'seo_section_callback'),
            'naibabiji_b2b_product_settings'
        );

        add_settings_section(
            'naibabiji_b2b_product_archive_seo_section',
            __('Archive Page SEO Content', 'naibabiji-b2b-product-showcase'),
            array($this, 'archive_seo_section_callback'),
            'naibabiji_b2b_product_settings'
        );

        // Add settings fields

        add_settings_field(
            'button_color',
            __('Primary Button Color', 'naibabiji-b2b-product-showcase'),
            array($this, 'button_color_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_appearance_section'
        );
        
        add_settings_field(
            'button_hover_color',
            __('Button Hover Color', 'naibabiji-b2b-product-showcase'),
            array($this, 'button_hover_color_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_appearance_section'
        );

        add_settings_field(
            'archive_columns',
            __('Archive Grid Columns', 'naibabiji-b2b-product-showcase'),
            array($this, 'archive_columns_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_appearance_section'
        );

        add_settings_field(
            'content_width',
            __('Content Area Width', 'naibabiji-b2b-product-showcase'),
            array($this, 'content_width_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_appearance_section'
        );

        add_settings_field(
            'border_radius',
            __('Global Border Radius (px)', 'naibabiji-b2b-product-showcase'),
            array($this, 'border_radius_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_appearance_section'
        );
        
        add_settings_field(
            'show_meta',
            __('Display Product Meta', 'naibabiji-b2b-product-showcase'),
            array($this, 'show_meta_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_seo_section'
        );


        
        add_settings_field(
            'inquiry_button_text',
            __('Default Inquiry Button Text', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_button_text_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );
        
        add_settings_field(
            'default_inquiry_url',
            __('Default Inquiry Page URL', 'naibabiji-b2b-product-showcase'),
            array($this, 'default_inquiry_url_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'inquiry_mode',
            __('Inquiry Mode', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_mode_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'default_inquiry_type',
            __('Default Inquiry Type for New Products', 'naibabiji-b2b-product-showcase'),
            array($this, 'default_inquiry_type_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'inquiry_form_fields',
            __('Form Fields to Display', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_form_fields_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'inquiry_success_msg',
            __('Submission Success Message', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_success_msg_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'inquiry_redirect_enabled',
            __('Redirect After Submission', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_redirect_enabled_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'inquiry_redirect_url',
            __('Redirect URL', 'naibabiji-b2b-product-showcase'),
            array($this, 'inquiry_redirect_url_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_inquiry_section'
        );

        add_settings_field(
            'enable_breadcrumbs',
            __('Enable Breadcrumbs', 'naibabiji-b2b-product-showcase'),
            array($this, 'enable_breadcrumbs_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_seo_section'
        );
        
        add_settings_field(
            'enable_schema',
            __('Enable Schema.org Structured Data', 'naibabiji-b2b-product-showcase'),
            array($this, 'enable_schema_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_seo_section'
        );
        
        add_settings_field(
            'excerpt_length',
            __('Product Excerpt Length', 'naibabiji-b2b-product-showcase'),
            array($this, 'excerpt_length_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_seo_section'
        );

        add_settings_field(
            'schema_currency',
            __('Structured Data Currency', 'naibabiji-b2b-product-showcase'),
            array($this, 'schema_currency_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_seo_section'
        );

        // Archive Page SEO Content Fields
        add_settings_field(
            'archive_hide_title',
            __('Hide Archive Title', 'naibabiji-b2b-product-showcase'),
            array($this, 'archive_hide_title_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_archive_seo_section'
        );

        add_settings_field(
            'archive_display_mode',
            __('Archive Display Content', 'naibabiji-b2b-product-showcase'),
            array($this, 'archive_display_mode_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_archive_seo_section'
        );

        add_settings_field(
            'default_product_sort',
            __('Default Product Sorting', 'naibabiji-b2b-product-showcase'),
            array($this, 'default_orderby_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_archive_seo_section'
        );

        add_settings_field(
            'archive_seo_content_top',
            __('SEO Content - Page Top', 'naibabiji-b2b-product-showcase'),
            array($this, 'archive_seo_content_top_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_archive_seo_section'
        );

        add_settings_field(
            'archive_seo_content_bottom',
            __('SEO Content - Page Bottom', 'naibabiji-b2b-product-showcase'),
            array($this, 'archive_seo_content_bottom_callback'),
            'naibabiji_b2b_product_settings',
            'naibabiji_b2b_product_archive_seo_section'
        );

    }
    
    /**
     * Handle backward compatibility redirects from old admin URLs.
     */
    public function handle_backward_compat_redirects() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Simple page-param redirect, no data is processed
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

        // Old Settings URL → new Settings page
        if ('naibabiji-b2b-product-showcase' === $page) {
            wp_safe_redirect(admin_url('admin.php?page=naibabiji-b2b-showcase-settings'));
            exit;
        }

        // Old AI Customer Service URL → new Settings page, AI tab
        if ('naibabiji-b2b-ai-settings' === $page) {
            wp_safe_redirect(admin_url('admin.php?page=naibabiji-b2b-showcase-settings#ai'));
            exit;
        }
    }

    /**
     * Render Dashboard page.
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'naibabiji-b2b-product-showcase'));
        }

        $total_posts = wp_count_posts('naibb2pr_products');
        $published   = isset($total_posts->publish) ? (int) $total_posts->publish : 0;
        $draft       = isset($total_posts->draft) ? (int) $total_posts->draft : 0;

        global $wpdb;
        $leads_table = $wpdb->prefix . 'naibb2pr_ai_leads';

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $pending_inquiries = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM `{$leads_table}` WHERE status = %s", 'pending')
        );
        $this_month_leads = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$leads_table}` WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $recent = $wpdb->get_results(
            "SELECT id, user_contact, inquiry_type, lead_source, inquiry_data, created_at
             FROM `{$leads_table}`
             ORDER BY created_at DESC
             LIMIT 5"
        );
        // phpcs:enable
        ?>
        <div class="wrap">
            <div class="naib-page-header">
                <div>
                    <h1><?php esc_html_e('Dashboard', 'naibabiji-b2b-product-showcase'); ?></h1>
                    <p class="naib-subtitle"><?php esc_html_e('Overview of your B2B product catalog and inquiries.', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
                <div class="naib-page-actions">
                    <a href="<?php echo esc_url(admin_url('post-new.php?post_type=naibb2pr_products')); ?>" class="naib-btn naib-btn--primary">
                        + <?php esc_html_e('Add Product', 'naibabiji-b2b-product-showcase'); ?>
                    </a>
                </div>
            </div>

            <div class="naib-stats-grid">
                <div class="naib-stat-card">
                    <p class="naib-stat-value"><?php echo esc_html($published + $draft); ?></p>
                    <p class="naib-stat-label"><?php esc_html_e('Total Products', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
                <div class="naib-stat-card">
                    <p class="naib-stat-value"><?php echo esc_html($published); ?></p>
                    <p class="naib-stat-label"><?php esc_html_e('Published', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
                <div class="naib-stat-card">
                    <p class="naib-stat-value"><?php echo esc_html($pending_inquiries); ?></p>
                    <p class="naib-stat-label"><?php esc_html_e('Pending Inquiries', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
                <div class="naib-stat-card">
                    <p class="naib-stat-value"><?php echo esc_html($this_month_leads); ?></p>
                    <p class="naib-stat-label"><?php esc_html_e('This Month', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
            </div>

            <div class="naib-card">
                <div class="naib-section-header">
                    <h2><?php esc_html_e('Recent Inquiries', 'naibabiji-b2b-product-showcase'); ?></h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=naibabiji-b2b-ai-leads')); ?>" class="naib-btn naib-btn--secondary">
                        <?php esc_html_e('View All', 'naibabiji-b2b-product-showcase'); ?> →
                    </a>
                </div>
                <?php if (!empty($recent)) : ?>
                    <div class="naib-table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Contact', 'naibabiji-b2b-product-showcase'); ?></th>
                                    <th><?php esc_html_e('Type', 'naibabiji-b2b-product-showcase'); ?></th>
                                    <th><?php esc_html_e('Source', 'naibabiji-b2b-product-showcase'); ?></th>
                                    <th><?php esc_html_e('Date', 'naibabiji-b2b-product-showcase'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $lead) :
                                    $display_name = $lead->user_contact;
                                    if (preg_match('/^Name:\s*(.+)/im', $lead->user_contact, $m)) {
                                        $display_name = trim($m[1]);
                                    }
                                    $type = !empty($lead->inquiry_type) ? $lead->inquiry_type : 'single';
                                    $source = !empty($lead->lead_source) ? $lead->lead_source : 'ai_chat';
                                    ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($display_name); ?></strong></td>
                                        <td>
                                            <?php if ('bulk' === $type) : ?>
                                                <span class="naib-pill naib-pill--orange"><?php esc_html_e('Bulk', 'naibabiji-b2b-product-showcase'); ?></span>
                                            <?php else : ?>
                                                <span class="naib-pill naib-pill--green"><?php esc_html_e('Standard', 'naibabiji-b2b-product-showcase'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ('inquiry_form' === $source) : ?>
                                                <span class="naib-pill naib-pill--blue"><?php esc_html_e('Inquiry', 'naibabiji-b2b-product-showcase'); ?></span>
                                            <?php elseif ('contact_form' === $source) : ?>
                                                <span class="naib-pill naib-pill--green"><?php esc_html_e('Contact', 'naibabiji-b2b-product-showcase'); ?></span>
                                            <?php else : ?>
                                                <span class="naib-pill naib-pill--gray"><?php esc_html_e('AI Chat', 'naibabiji-b2b-product-showcase'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($lead->created_at); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="naib-empty-state">
                        <div class="naib-empty-state__icon">📬</div>
                        <p class="naib-empty-state__text"><?php esc_html_e('No inquiries yet', 'naibabiji-b2b-product-showcase'); ?></p>
                        <p class="naib-empty-state__hint"><?php esc_html_e('When customers submit inquiries, they will appear here.', 'naibabiji-b2b-product-showcase'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render standalone Help page.
     */
    public function render_help_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'naibabiji-b2b-product-showcase'));
        }

        $images_url = NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_URL . 'assets/images/';
        ?>
        <div class="wrap">
            <div class="naib-page-header">
                <div>
                    <h1><?php esc_html_e('Help & Guides', 'naibabiji-b2b-product-showcase'); ?></h1>
                    <p class="naib-subtitle"><?php esc_html_e('Learn how to get the most out of B2B Showcase.', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
            </div>

            <!-- Getting Started Guide -->
            <div class="naib-card" style="margin-bottom:32px;">
                <div class="naib-section-header">
                    <h2><?php esc_html_e('Getting Started', 'naibabiji-b2b-product-showcase'); ?></h2>
                </div>
                <p style="margin:0 0 20px; font-size:14px; color:#50575e;">
                    <?php esc_html_e('Follow these 5 steps to set up your B2B product catalog. Each step takes only a minute or two.', 'naibabiji-b2b-product-showcase'); ?>
                </p>

                <ol class="naib-getting-started" style="margin:0; padding:0; list-style:none;">

                    <!-- Step 1: Appearance Settings -->
                    <li class="naib-gs-step" style="display:flex; gap:16px; padding:20px 0; border-top:1px solid #e5e5e5;">
                        <span style="flex-shrink:0; width:32px; height:32px; background:#2271b1; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px;">1</span>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 6px; font-size:15px;"><?php esc_html_e('Configure Appearance', 'naibabiji-b2b-product-showcase'); ?></h3>
                            <p style="margin:0 0 8px; color:#50575e; font-size:13px;">
                                <?php esc_html_e('Match the plugin colors and layout to your theme. Set the primary button color, content area width, and global border radius so product cards blend seamlessly with your site design.', 'naibabiji-b2b-product-showcase'); ?>
                            </p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=naibabiji-b2b-showcase-settings#appearance')); ?>" class="naib-btn naib-btn--primary" style="font-size:12px;" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Go to Appearance Settings →', 'naibabiji-b2b-product-showcase'); ?>
                            </a>
                        </div>
                    </li>

                    <!-- Step 2: Inquiry Mode -->
                    <li class="naib-gs-step" style="display:flex; gap:16px; padding:20px 0; border-top:1px solid #e5e5e5;">
                        <span style="flex-shrink:0; width:32px; height:32px; background:#2271b1; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px;">2</span>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 6px; font-size:15px;"><?php esc_html_e('Set Global Inquiry Mode', 'naibabiji-b2b-product-showcase'); ?></h3>
                            <p style="margin:0 0 8px; color:#50575e; font-size:13px;">
                                <?php esc_html_e('Choose how customers send inquiries. "External Link" redirects to your contact page or WhatsApp. "Built-in Inquiry Form" opens a popup modal on your site. You can also set the default inquiry type (Standard or Bulk) for new products.', 'naibabiji-b2b-product-showcase'); ?>
                            </p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=naibabiji-b2b-showcase-settings#inquiry')); ?>" class="naib-btn naib-btn--primary" style="font-size:12px;" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Go to Inquiry Settings →', 'naibabiji-b2b-product-showcase'); ?>
                            </a>
                        </div>
                    </li>

                    <!-- Step 3: Product Categories -->
                    <li class="naib-gs-step" style="display:flex; gap:16px; padding:20px 0; border-top:1px solid #e5e5e5;">
                        <span style="flex-shrink:0; width:32px; height:32px; background:#2271b1; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px;">3</span>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 6px; font-size:15px;"><?php esc_html_e('Create Product Categories', 'naibabiji-b2b-product-showcase'); ?></h3>
                            <p style="margin:0 0 8px; color:#50575e; font-size:13px;">
                                <?php esc_html_e('Organize your products into categories (e.g. "Valves", "Fittings", "Pumps"). Each category gets its own page with SEO content areas and can be styled individually. Add category images for the grid display mode.', 'naibabiji-b2b-product-showcase'); ?>
                            </p>
                            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=naibb2pr_product_category&post_type=naibb2pr_products')); ?>" class="naib-btn naib-btn--primary" style="font-size:12px;" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Manage Categories →', 'naibabiji-b2b-product-showcase'); ?>
                            </a>
                        </div>
                    </li>

                    <!-- Step 4: Publish First Product -->
                    <li class="naib-gs-step" style="display:flex; gap:16px; padding:20px 0; border-top:1px solid #e5e5e5;">
                        <span style="flex-shrink:0; width:32px; height:32px; background:#2271b1; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px;">4</span>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 6px; font-size:15px;"><?php esc_html_e('Publish Your First Product', 'naibabiji-b2b-product-showcase'); ?></h3>
                            <p style="margin:0 0 8px; color:#50575e; font-size:13px;">
                                <?php esc_html_e('The product edit screen has important fields below the content editor. Scroll down or pull up the bottom panel to access: Product Short Description, Product Gallery, and Specs Management.', 'naibabiji-b2b-product-showcase'); ?>
                            </p>
                            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=naibb2pr_products')); ?>" class="naib-btn naib-btn--primary" style="font-size:12px; margin-bottom:16px;">
                                <?php esc_html_e('Add New Product →', 'naibabiji-b2b-product-showcase'); ?>
                            </a>

                            <!-- Animated GIF: pull up meta boxes -->
                            <div style="margin-top:12px; border:1px solid #dcdcde; border-radius:4px; overflow:hidden; max-width:720px;">
                                <div style="background:#f6f7f7; padding:8px 12px; font-size:12px; font-weight:600; color:#3c434a; border-bottom:1px solid #dcdcde;">
                                    <?php esc_html_e('How to find product fields below the editor', 'naibabiji-b2b-product-showcase'); ?>
                                </div>
                                <a href="<?php echo esc_url($images_url . 'guide-pull-up-meta-boxes.gif'); ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?php echo esc_url($images_url . 'guide-pull-up-meta-boxes.gif'); ?>"
                                         alt="<?php esc_attr_e('Pull up the bottom panel to reveal product fields', 'naibabiji-b2b-product-showcase'); ?>"
                                         style="display:block; width:100%; height:auto;" />
                                </a>
                            </div>

                            <!-- Static annotated image: editor → frontend mapping -->
                            <div style="margin-top:16px; border:1px solid #dcdcde; border-radius:4px; overflow:hidden; max-width:720px;">
                                <div style="background:#f6f7f7; padding:8px 12px; font-size:12px; font-weight:600; color:#3c434a; border-bottom:1px solid #dcdcde;">
                                    <?php esc_html_e('Each field location and how it appears on the frontend', 'naibabiji-b2b-product-showcase'); ?>
                                </div>
                                <a href="<?php echo esc_url($images_url . 'guide-editor-mapping.png'); ?>" target="_blank" rel="noopener noreferrer">
                                    <img src="<?php echo esc_url($images_url . 'guide-editor-mapping.png'); ?>"
                                         alt="<?php esc_attr_e('Editor fields mapped to frontend product page', 'naibabiji-b2b-product-showcase'); ?>"
                                         style="display:block; width:100%; height:auto;" />
                                </a>
                            </div>
                        </div>
                    </li>

                    <!-- Step 5: Navigation Menu -->
                    <li class="naib-gs-step" style="display:flex; gap:16px; padding:20px 0; border-top:1px solid #e5e5e5;">
                        <span style="flex-shrink:0; width:32px; height:32px; background:#2271b1; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px;">5</span>
                        <div style="flex:1;">
                            <h3 style="margin:0 0 6px; font-size:15px;"><?php esc_html_e('Add Products to Navigation Menu', 'naibabiji-b2b-product-showcase'); ?></h3>
                            <p style="margin:0 0 8px; color:#50575e; font-size:13px;">
                                <?php esc_html_e('Make it easy for visitors to find your product catalog. Add the Products archive page and individual product category pages to your site\'s main navigation menu.', 'naibabiji-b2b-product-showcase'); ?>
                            </p>
                            <ul style="margin:0 0 8px; padding:0 0 0 16px; color:#50575e; font-size:13px;">
                                <li><?php esc_html_e('Go to Appearance → Menus', 'naibabiji-b2b-product-showcase'); ?></li>
                                <li><?php esc_html_e('Under "Product Categories" or "Custom Links", add your product pages', 'naibabiji-b2b-product-showcase'); ?></li>
                                <li><?php esc_html_e('For the product archive, add a Custom Link: /products/', 'naibabiji-b2b-product-showcase'); ?></li>
                            </ul>
                            <a href="<?php echo esc_url(admin_url('nav-menus.php')); ?>" class="naib-btn naib-btn--primary" style="font-size:12px;" target="_blank" rel="noopener noreferrer">
                                <?php esc_html_e('Go to Menus →', 'naibabiji-b2b-product-showcase'); ?>
                            </a>
                        </div>
                    </li>

                </ol>
            </div>

            <!-- Existing reference cards -->
            <div class="naib-help-grid">
                <div class="naib-help-card">
                    <h3><?php esc_html_e('Product Archive Page', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Your products are automatically displayed at:', 'naibabiji-b2b-product-showcase'); ?></p>
                    <code><?php echo esc_url(home_url('/products/')); ?></code>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('Shortcode Generator', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Use the visual shortcode generator to easily create product display shortcodes.', 'naibabiji-b2b-product-showcase'); ?></p>
                    <p><?php esc_html_e('Navigate to: B2B Showcase → Shortcode Generator', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('Category & Tag SEO Content', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Each product category and tag can have custom SEO content at the top and bottom of the page.', 'naibabiji-b2b-product-showcase'); ?></p>
                    <p><?php esc_html_e('Navigate to: Products → Categories (or Tags) → Edit → SEO content fields', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('AI Customer Service', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Enable AI-powered customer service chat on your product pages. Configure license key, knowledge base, and display position.', 'naibabiji-b2b-product-showcase'); ?></p>
                    <p><?php esc_html_e('Navigate to: B2B Showcase → Settings → AI tab', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('Inquiry System Overview', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('The plugin has two layers of inquiry configuration:', 'naibabiji-b2b-product-showcase'); ?></p>
                    <p><strong><?php esc_html_e('Inquiry Type (per product):', 'naibabiji-b2b-product-showcase'); ?></strong></p>
                    <ul>
                        <li><strong><?php esc_html_e('None', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('Disables inquiry for this product.', 'naibabiji-b2b-product-showcase'); ?></li>
                        <li><strong><?php esc_html_e('Standard', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('Single-product inquiry. Controlled by Inquiry Mode (External Link or Built-in Form).', 'naibabiji-b2b-product-showcase'); ?></li>
                        <li><strong><?php esc_html_e('Bulk', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('Multi-spec batch inquiry with selection table and shared cart.', 'naibabiji-b2b-product-showcase'); ?></li>
                    </ul>
                    <p><strong><?php esc_html_e('Inquiry Mode (Settings → Inquiry tab):', 'naibabiji-b2b-product-showcase'); ?></strong> <?php esc_html_e('Applies only to Standard-type products. External Link redirects to a URL; Built-in Form opens a popup.', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('Bulk Inquiry Workflow', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><strong><?php esc_html_e('Admin Setup:', 'naibabiji-b2b-product-showcase'); ?></strong></p>
                    <ol>
                        <li><?php esc_html_e('Edit a product → set Inquiry Type to Bulk.', 'naibabiji-b2b-product-showcase'); ?></li>
                        <li><?php esc_html_e('Add model codes and descriptions in the Specs Management meta box, or import via CSV.', 'naibabiji-b2b-product-showcase'); ?></li>
                        <li><?php esc_html_e('Drag-and-drop to reorder. CSV format: first row "Model Code,Spec Description", max 1000 rows.', 'naibabiji-b2b-product-showcase'); ?></li>
                    </ol>
                    <p><strong><?php esc_html_e('Customer Flow:', 'naibabiji-b2b-product-showcase'); ?></strong></p>
                    <ol>
                        <li><?php esc_html_e('Visitor selects specs, enters quantity, clicks Add to Cart.', 'naibabiji-b2b-product-showcase'); ?></li>
                        <li><?php esc_html_e('Cart persists across products (localStorage). Browse and add specs from multiple products.', 'naibabiji-b2b-product-showcase'); ?></li>
                        <li><?php esc_html_e('Open cart sidebar → Submit Inquiry → fill contact details → submit all at once.', 'naibabiji-b2b-product-showcase'); ?></li>
                    </ol>
                    <p class="description"><?php esc_html_e('Rate limit: 5 minutes / 3 submissions per IP.', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('Contact Form Shortcode', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Embed a standalone contact form on any page:', 'naibabiji-b2b-product-showcase'); ?></p>
                    <code>[naibabiji_b2b_contact_form]</code>
                    <p style="margin-top:6px;"><?php esc_html_e('With custom title:', 'naibabiji-b2b-product-showcase'); ?> <code>[naibabiji_b2b_contact_form title="Get a Quote"]</code></p>
                </div>

                <div class="naib-help-card">
                    <h3><?php esc_html_e('Template Override', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Override templates by placing files in your theme directory. The plugin checks your theme first.', 'naibabiji-b2b-product-showcase'); ?></p>
                    <p><strong><?php esc_html_e('Page Templates (theme root):', 'naibabiji-b2b-product-showcase'); ?></strong></p>
                    <ul>
                        <li><code>single-naibb2pr_products.php</code></li>
                        <li><code>archive-naibb2pr_products.php</code></li>
                        <li><code>taxonomy-naibb2pr_product_category.php</code></li>
                        <li><code>taxonomy-naibb2pr_product_tag.php</code></li>
                    </ul>
                    <p><strong><?php esc_html_e('Partial Templates (theme root or naibabiji-b2b-product-showcase/):', 'naibabiji-b2b-product-showcase'); ?></strong></p>
                    <ul>
                        <li><code>content-product-card.php</code></li>
                        <li><code>inquiry-form-modal.php</code></li>
                        <li><code>inquiry-form-inline.php</code></li>
                        <li><code>bulk-inquiry-form.php</code></li>
                        <li><code>bulk-inquiry-specs-table.php</code></li>
                    </ul>
                </div>
                <div class="naib-help-card">
                    <h3><?php esc_html_e('Bug Report & Feedback', 'naibabiji-b2b-product-showcase'); ?></h3>
                    <p><?php esc_html_e('Found a bug or have a suggestion? We would love to hear from you.', 'naibabiji-b2b-product-showcase'); ?></p>
                    <p><strong><?php esc_html_e('WeChat:', 'naibabiji-b2b-product-showcase'); ?></strong> vv15_zhi</p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Redirect old AI Customer Service page URL to Settings > AI tab.
     */
    /**
     * Render AI Settings tab content (used inside the main Settings form).
     */
    public function render_ai_settings_tab() {
        $is_enabled = get_option('naibabiji_b2b_ai_enable', false);
        ?>
        <?php if (!$is_enabled) : ?>
            <div class="naib-notice naib-notice--info">
                <p><?php esc_html_e('AI Customer Service is currently disabled. Fill in the settings below and check "Enable AI Customer Service" to activate.', 'naibabiji-b2b-product-showcase'); ?></p>
            </div>
        <?php endif; ?>

        <div class="naib-card" style="margin-bottom:20px;">
            <p style="margin:0;">
                <strong><?php esc_html_e('Need help?', 'naibabiji-b2b-product-showcase'); ?></strong>
                <?php esc_html_e('Visit the AI Manager to get your API key, view usage, and manage your AI service.', 'naibabiji-b2b-product-showcase'); ?>
                <a href="https://linghang.quhenet.com/ai-manager/" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Open AI Manager →', 'naibabiji-b2b-product-showcase'); ?>
                </a>
            </p>
        </div>

        <div class="naib-step-card">
            <div class="naib-step-card__header">
                <span class="naib-step-card__number">1</span>
                <h2 class="naib-step-card__title"><?php esc_html_e('Connection Setup', 'naibabiji-b2b-product-showcase'); ?></h2>
            </div>
            <div class="naib-step-card__body">
                <?php do_settings_sections('naibabiji_b2b_ai_connection'); ?>
            </div>
        </div>

        <div class="naib-step-card">
            <div class="naib-step-card__header">
                <span class="naib-step-card__number">2</span>
                <h2 class="naib-step-card__title"><?php esc_html_e('Knowledge Base', 'naibabiji-b2b-product-showcase'); ?></h2>
            </div>
            <div class="naib-step-card__body">
                <?php do_settings_sections('naibabiji_b2b_ai_knowledge'); ?>
            </div>
        </div>

        <div class="naib-step-card">
            <div class="naib-step-card__header">
                <span class="naib-step-card__number">3</span>
                <h2 class="naib-step-card__title"><?php esc_html_e('Display & Positioning', 'naibabiji-b2b-product-showcase'); ?></h2>
            </div>
            <div class="naib-step-card__body">
                <?php do_settings_sections('naibabiji_b2b_ai_display'); ?>
            </div>
        </div>

        <div class="naib-notice naib-notice--info">
            <strong><?php esc_html_e('How AI Reads Your Products', 'naibabiji-b2b-product-showcase'); ?></strong>
            <p style="margin-top:6px;"><?php esc_html_e('When a visitor chats with the AI on a product page, the AI automatically receives: Product Title, SKU & Price; Short Description; first ~500 words of Product Detail; Company Profile & FAQ from this page.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        <?php
    }

    /**
     * Redirect old AI Customer Service page URL to Settings > AI tab.
     */
    public function render_ai_settings_redirect() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'naibabiji-b2b-product-showcase'));
        }
        wp_safe_redirect(admin_url('admin.php?page=naibabiji-b2b-showcase-settings&tab=ai'));
        exit;
    }

    /**
     * Render AI Customer Service settings page (backward compat — redirects to Settings).
     */
    public function render_ai_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'naibabiji-b2b-product-showcase'));
        }
        $is_enabled = get_option('naibabiji_b2b_ai_enable', false);
        ?>
        <div class="wrap">
            <h1>🤖 <?php echo esc_html__('AI Customer Service', 'naibabiji-b2b-product-showcase'); ?></h1>
            <p class="description" style="font-size:14px; margin-bottom:20px;">
                <?php esc_html_e('Configure the AI chat widget for your product pages. Customers can ask questions in real time and get instant answers based on your product and company knowledge.', 'naibabiji-b2b-product-showcase'); ?>
            </p>
            
            <div class="notice notice-warning inline" style="margin-bottom:20px; padding:15px;">
                <p style="font-weight:600; margin-top:0; margin-bottom:10px;">ℹ️ <?php esc_html_e('AI Service Beta Notice', 'naibabiji-b2b-product-showcase'); ?></p>
                <p style="margin-top:0; margin-bottom:10px;">
                    <?php esc_html_e('AI service is currently in beta. For a demo experience, visit:', 'naibabiji-b2b-product-showcase'); ?> 
                    <a href="https://linghang.quhenet.com/products/linghang-wordpress-theme/" target="_blank" style="color:#2271b1; text-decoration:underline;">linghang.quhenet.com/products/linghang-wordpress-theme/</a>
                </p>
                <p style="margin-top:0; margin-bottom:10px; font-weight:500;"><?php esc_html_e('Test API Keys (use any of these for testing):', 'naibabiji-b2b-product-showcase'); ?></p>
                <div style="background:#f0f0f1; border:1px solid #c3c4c7; border-radius:var(--naibabiji-b2b-border-radius); padding:12px; font-family:monospace; font-size:13px;">
                    <ol style="list-style:decimal inside; margin:0; padding:0; display:flex; flex-wrap:wrap; gap:8px 20px;">
                        <li>NB-JO4F8A1E</li>
                        <li>NB-OTXLODQZ</li>
                        <li>NB-ZPQ2NRYX</li>
                        <li>NB-2ZPIJEO2</li>
                        <li>NB-578TTG9I</li>
                        <li>NB-BHDR93LW</li>
                        <li>NB-PKBO6DSN</li>
                        <li>NB-QWG9CYAY</li>
                        <li>NB-UDDZL6RQ</li>
                        <li>NB-I3P3ECAR</li>
                        <li>NB-MUMV05JM</li>
                        <li>NB-1APZIZL1</li>
                        <li>NB-DGDWTL6I</li>
                        <li>NB-6JW6VJ0Y</li>
                        <li>NB-TBGOWCFV</li>
                        <li>NB-E9JWMIR9</li>
                        <li>NB-CEQNQYZD</li>
                        <li>NB-G1HENCCS</li>
                        <li>NB-JRLSYP96</li>
                        <li>NB-HNFSGORT</li>
                        <li>NB-VZYLAZEP</li>
                        <li>NB-SI6PJFQP</li>
                        <li>NB-KR2XQPNP</li>
                        <li>NB-L706S3DU</li>
                        <li>NB-ZDBYP08Z</li>
                        <li>NB-T0WSFWGY</li>
                        <li>NB-YL9NVZKX</li>
                        <li>NB-W6JVOY6T</li>
                        <li>NB-KAKWLNQ7</li>
                        <li>NB-FHSHDVTQ</li>
                        <li>NB-C2XQORUU</li>
                        <li>NB-UBPAC62C</li>
                        <li>NB-DP95FHD0</li>
                        <li>NB-QUKU7UUI</li>
                        <li>NB-LYARTT1O</li>
                        <li>NB-IUCVRRNE</li>
                        <li>NB-ZEUXVB8F</li>
                        <li>NB-WXVSKQZE</li>
                        <li>NB-P2L2L1UZ</li>
                        <li>NB-D0DJPNCV</li>
                        <li>NB-KKTEP8FY</li>
                        <li>NB-CDLYBGY5</li>
                        <li>NB-EJHAVJ0E</li>
                        <li>NB-IZO5U9ED</li>
                        <li>NB-AULZXEDM</li>
                    </ol>
                </div>
                <p style="margin-top:10px; margin-bottom:0; font-style:italic; color:#646970;">
                    <?php esc_html_e('API URL: https://api.quhenet.com Plugin feedback: WeChat vv15_zhi', 'naibabiji-b2b-product-showcase'); ?>
                </p>
            </div>

            <?php if (!$is_enabled): ?>
            <div class="notice notice-info inline" style="margin-bottom:20px;">
                <p><?php esc_html_e('AI Customer Service is currently disabled. Fill in the settings below and check "Enable AI Customer Service" to activate.', 'naibabiji-b2b-product-showcase'); ?></p>
            </div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php settings_fields('naibabiji_b2b_ai_settings'); ?>

                <!-- Step 1: Connection -->
                <div style="background:#fff; border:1px solid #ccd0d4; border-radius:var(--naibabiji-b2b-border-radius); padding:0; margin-bottom:24px; overflow:hidden;">
                    <div style="background:#f6f7f7; border-bottom:1px solid #ccd0d4; padding:14px 20px; display:flex; align-items:center; gap:12px;">
                        <span style="background:#2271b1; color:#fff; border-radius:50%; width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; font-size:13px; flex-shrink:0;">1</span>
                        <h2 style="margin:0; font-size:15px;"><?php esc_html_e('Connection Setup', 'naibabiji-b2b-product-showcase'); ?></h2>
                    </div>
                    <div style="padding:4px 20px 20px;">
                        <?php do_settings_sections('naibabiji_b2b_ai_connection'); ?>
                    </div>
                </div>

                <!-- Step 2: Knowledge Base -->
                <div style="background:#fff; border:1px solid #ccd0d4; border-radius:var(--naibabiji-b2b-border-radius); padding:0; margin-bottom:24px; overflow:hidden;">
                    <div style="background:#f6f7f7; border-bottom:1px solid #ccd0d4; padding:14px 20px; display:flex; align-items:center; gap:12px;">
                        <span style="background:#2271b1; color:#fff; border-radius:50%; width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; font-size:13px; flex-shrink:0;">2</span>
                        <h2 style="margin:0; font-size:15px;"><?php esc_html_e('Knowledge Base', 'naibabiji-b2b-product-showcase'); ?></h2>
                    </div>
                    <div style="padding:4px 20px 20px;">
                        <?php do_settings_sections('naibabiji_b2b_ai_knowledge'); ?>
                    </div>
                </div>

                <!-- Step 3: Display & Positioning -->
                <div style="background:#fff; border:1px solid #ccd0d4; border-radius:var(--naibabiji-b2b-border-radius); padding:0; margin-bottom:24px; overflow:hidden;">
                    <div style="background:#f6f7f7; border-bottom:1px solid #ccd0d4; padding:14px 20px; display:flex; align-items:center; gap:12px;">
                        <span style="background:#2271b1; color:#fff; border-radius:50%; width:26px; height:26px; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; font-size:13px; flex-shrink:0;">3</span>
                        <h2 style="margin:0; font-size:15px;"><?php esc_html_e('Display & Positioning', 'naibabiji-b2b-product-showcase'); ?></h2>
                    </div>
                    <div style="padding:4px 20px 20px;">
                        <?php do_settings_sections('naibabiji_b2b_ai_display'); ?>
                    </div>
                </div>

                <?php submit_button(__('Save AI Settings', 'naibabiji-b2b-product-showcase')); ?>
            </form>

            <!-- How It Works info box -->
            <div style="background:#f0f6fc; border:1px solid #72aee6; border-radius:var(--naibabiji-b2b-border-radius); padding:16px 20px; margin-top:8px;">
                <h3 style="margin-top:0;">💡 <?php esc_html_e('How AI Reads Your Products', 'naibabiji-b2b-product-showcase'); ?></h3>
                <p style="margin-bottom:8px;"><?php esc_html_e('When a visitor chats with the AI on a product page, the AI automatically receives:', 'naibabiji-b2b-product-showcase'); ?></p>
                <ul style="margin:0; padding-left:20px;">
                    <li><strong><?php esc_html_e('Product Title, SKU & Price', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('always included (currency unit taken from SEO settings)', 'naibabiji-b2b-product-showcase'); ?></li>
                    <li><strong><?php esc_html_e('Short Description', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('AI\'s primary source for "what is this product" questions', 'naibabiji-b2b-product-showcase'); ?></li>
                    <li><strong><?php esc_html_e('Product Detail (first ~500 words)', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('place key specs at the top', 'naibabiji-b2b-product-showcase'); ?></li>
                    <li><strong><?php esc_html_e('Company Profile & FAQ', 'naibabiji-b2b-product-showcase'); ?></strong> — <?php esc_html_e('from this page (Step 2 above)', 'naibabiji-b2b-product-showcase'); ?></li>
                </ul>
                <p style="margin:10px 0 0; color:#50575e; font-size:13px;">
                    <?php esc_html_e('Tip: Writing good Short Descriptions = better AI answers AND better SEO rankings. Two benefits, one effort.', 'naibabiji-b2b-product-showcase'); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_shortcode_generator_page() {

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'naibabiji-b2b-product-showcase'));
        }

        $categories = get_terms(array(
            'taxonomy' => 'naibb2pr_product_category',
            'hide_empty' => false,
        ));
        ?>
        <div class="wrap naibabiji-b2b-generator-wrap">
            <div class="naib-page-header">
                <div>
                    <h1><?php echo esc_html__('Shortcode Generator', 'naibabiji-b2b-product-showcase'); ?></h1>
                    <p class="naib-subtitle"><?php echo esc_html__('Configure options to generate a shortcode for displaying products on any page or post.', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
            </div>

            <div class="naib-generator-container">
                <!-- Left: Configuration -->
                <div class="naib-card naib-generator-config">
                    <div class="naib-section-header">
                        <h2><?php esc_html_e('Configuration', 'naibabiji-b2b-product-showcase'); ?></h2>
                    </div>
                    <div class="naib-form-group">
                        <label class="naib-label" for="gen_limit"><?php esc_html_e('Number of Products', 'naibabiji-b2b-product-showcase'); ?></label>
                        <input type="number" id="gen_limit" value="8" min="1" max="100" />
                        <p class="naib-description"><?php esc_html_e('Total products to display.', 'naibabiji-b2b-product-showcase'); ?></p>
                    </div>
                    <div class="naib-form-group">
                        <label class="naib-label" for="gen_columns"><?php esc_html_e('Columns', 'naibabiji-b2b-product-showcase'); ?></label>
                        <select id="gen_columns">
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3" selected>3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6</option>
                        </select>
                    </div>
                    <div class="naib-form-group">
                        <label class="naib-label" for="gen_category"><?php esc_html_e('Filter by Category', 'naibabiji-b2b-product-showcase'); ?></label>
                        <select id="gen_category">
                            <option value=""><?php esc_html_e('All Categories', 'naibabiji-b2b-product-showcase'); ?></option>
                            <?php if (!is_wp_error($categories) && !empty($categories)): ?>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat->slug); ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="naib-form-group">
                        <label class="naib-label"><?php esc_html_e('Display Options', 'naibabiji-b2b-product-showcase'); ?></label>
                        <label style="display:block; margin-bottom:6px;"><input type="checkbox" id="gen_show_excerpt" checked /> <?php esc_html_e('Show Excerpt', 'naibabiji-b2b-product-showcase'); ?></label>
                        <label style="display:block; margin-bottom:6px;"><input type="checkbox" id="gen_show_category" checked /> <?php esc_html_e('Show Category', 'naibabiji-b2b-product-showcase'); ?></label>
                        <label style="display:block; margin-bottom:6px;"><input type="checkbox" id="gen_show_view_details" checked /> <?php esc_html_e('Show "View Details" Button', 'naibabiji-b2b-product-showcase'); ?></label>
                        <label style="display:block; margin-bottom:6px;"><input type="checkbox" id="gen_show_inquiry" checked /> <?php esc_html_e('Show "Inquiry" Button', 'naibabiji-b2b-product-showcase'); ?></label>
                    </div>
                </div>

                <!-- Right: Result -->
                <div class="naib-card naib-generator-result">
                    <div class="naib-section-header">
                        <h2><?php esc_html_e('Generated Shortcode', 'naibabiji-b2b-product-showcase'); ?></h2>
                    </div>
                    <textarea id="naibabiji-b2b-generated-shortcode" readonly></textarea>
                    <button type="button" class="naib-btn naib-btn--primary copy-to-clipboard" data-target="#naibabiji-b2b-generated-shortcode" style="width:100%; justify-content:center;">
                        <?php esc_html_e('Copy Shortcode', 'naibabiji-b2b-product-showcase'); ?>
                    </button>
                    <div style="margin-top:16px; font-size:13px; color:var(--naib-text-secondary);">
                        <strong><?php esc_html_e('How to use:', 'naibabiji-b2b-product-showcase'); ?></strong>
                        <p style="margin:4px 0 0;"><?php esc_html_e('Copy the shortcode and paste it into any editor or page builder text module.', 'naibabiji-b2b-product-showcase'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        // Check user permission
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to access this page.', 'naibabiji-b2b-product-showcase'));
        }
        
        // Enqueue color picker assets for this page
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Settings', 'naibabiji-b2b-product-showcase'); ?></h1>

            <nav class="naib-tabs">
                <a href="#appearance" class="naib-tab active"><?php esc_html_e('Appearance', 'naibabiji-b2b-product-showcase'); ?></a>
                <a href="#inquiry" class="naib-tab"><?php esc_html_e('Inquiry', 'naibabiji-b2b-product-showcase'); ?></a>
                <a href="#ai" class="naib-tab"><?php esc_html_e('AI', 'naibabiji-b2b-product-showcase'); ?></a>
                <a href="#seo" class="naib-tab"><?php esc_html_e('SEO', 'naibabiji-b2b-product-showcase'); ?></a>
                <a href="#archive-content" class="naib-tab"><?php esc_html_e('Archive Content', 'naibabiji-b2b-product-showcase'); ?></a>
            </nav>

            <form method="post" action="options.php">
                <?php settings_fields('naibabiji_b2b_product_settings'); ?>

                <!-- Tab: Appearance -->
                <div id="tab-appearance" class="naib-settings-tab active">
                    <div class="naib-card">
                        <div class="naib-section-header">
                            <h2><?php esc_html_e('Appearance Settings', 'naibabiji-b2b-product-showcase'); ?></h2>
                        </div>
                        <p class="naib-description"><?php esc_html_e('Customize colors and layout for product listings and details.', 'naibabiji-b2b-product-showcase'); ?></p>
                        <table class="form-table">
                            <?php do_settings_fields('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_appearance_section'); ?>
                        </table>
                    </div>
                </div>

                <!-- Tab: Inquiry -->
                <div id="tab-inquiry" class="naib-settings-tab">
                    <div class="naib-card">
                        <div class="naib-section-header">
                            <h2><?php esc_html_e('Inquiry Settings', 'naibabiji-b2b-product-showcase'); ?></h2>
                        </div>
                        <p class="naib-description"><?php esc_html_e('Configure product inquiry related settings.', 'naibabiji-b2b-product-showcase'); ?></p>
                        <table class="form-table">
                            <?php do_settings_fields('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_inquiry_section'); ?>
                        </table>
                    </div>
                </div>

                <!-- Tab: AI -->
                <div id="tab-ai" class="naib-settings-tab">
                    <?php $this->render_ai_settings_tab(); ?>
                </div>

                <!-- Tab: SEO -->
                <div id="tab-seo" class="naib-settings-tab">
                    <div class="naib-card">
                        <div class="naib-section-header">
                            <h2><?php esc_html_e('SEO Settings', 'naibabiji-b2b-product-showcase'); ?></h2>
                        </div>
                        <p class="naib-description"><?php esc_html_e('Configure SEO related features.', 'naibabiji-b2b-product-showcase'); ?></p>
                        <table class="form-table">
                            <?php do_settings_fields('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_seo_section'); ?>
                        </table>
                    </div>
                </div>

                <!-- Tab: Archive Content -->
                <div id="tab-archive-content" class="naib-settings-tab">
                    <div class="naib-card">
                        <div class="naib-section-header">
                            <h2><?php esc_html_e('Archive Page SEO Content', 'naibabiji-b2b-product-showcase'); ?></h2>
                        </div>
                        <p class="naib-description"><?php esc_html_e('Add custom content to the top and bottom of the product archive page. This helps improve SEO and user engagement.', 'naibabiji-b2b-product-showcase'); ?></p>
                        <table class="form-table">
                            <?php do_settings_fields('naibabiji_b2b_product_settings', 'naibabiji_b2b_product_archive_seo_section'); ?>
                        </table>
                    </div>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        
        <?php
    }
    
    // 设置区域回调函数
    
    public function inquiry_section_callback() {
    }
    
    public function seo_section_callback() {
    }

    public function archive_seo_section_callback() {
    }

    public function archive_hide_title_callback() {
        $value = (bool) get_option('naibabiji_b2b_product_archive_hide_title', false);
        echo '<label><input type="checkbox" name="naibabiji_b2b_product_archive_hide_title" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Hide the default "Products" title on the archive page', 'naibabiji-b2b-product-showcase') . '</label>';
        echo '<p class="description">' . esc_html__('Useful when you want to design your own header using the SEO content area below.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function archive_seo_content_top_callback() {
        $content = get_option('naibabiji_b2b_product_archive_seo_content_top', '');
        $enabled = (bool) get_option('naibabiji_b2b_product_archive_enable_seo_top', true);

        wp_editor($content, 'naibabiji_b2b_product_archive_seo_content_top', array(
            'textarea_name' => 'naibabiji_b2b_product_archive_seo_content_top',
            'media_buttons' => true,
            'textarea_rows' => 8,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
            ),
        ));
        echo '<p class="description">' . esc_html__('Content displayed at the top of the product archive page, supporting text, images, and links.', 'naibabiji-b2b-product-showcase') . '</p>';
        echo '<p><label><input type="checkbox" name="naibabiji_b2b_product_archive_enable_seo_top" value="1" ' . checked($enabled, true, false) . ' /> ' . esc_html__('Enable top SEO content', 'naibabiji-b2b-product-showcase') . '</label></p>';
    }

    public function archive_seo_content_bottom_callback() {
        $content = get_option('naibabiji_b2b_product_archive_seo_content_bottom', '');
        $enabled = (bool) get_option('naibabiji_b2b_product_archive_enable_seo_bottom', true);

        wp_editor($content, 'naibabiji_b2b_product_archive_seo_content_bottom', array(
            'textarea_name' => 'naibabiji_b2b_product_archive_seo_content_bottom',
            'media_buttons' => true,
            'textarea_rows' => 8,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
            ),
        ));
        echo '<p class="description">' . esc_html__('Content displayed at the bottom of the product archive page, supporting text, images, and links.', 'naibabiji-b2b-product-showcase') . '</p>';
        echo '<p><label><input type="checkbox" name="naibabiji_b2b_product_archive_enable_seo_bottom" value="1" ' . checked($enabled, true, false) . ' /> ' . esc_html__('Enable bottom SEO content', 'naibabiji-b2b-product-showcase') . '</label></p>';
    }
    
    // 设置字段回调函数
    public function appearance_section_callback() {
    }
    
    public function button_color_callback() {
        $value = get_option('naibabiji_b2b_product_button_color', '#0A7AFF');
        echo '<input type="text" name="naibabiji_b2b_product_button_color" value="' . esc_attr($value) . '" class="regular-text naibabiji-color-field" data-default-color="#0A7AFF" />';
        echo '<p class="description">' . esc_html__('Affects "View Details" and "Inquiry" buttons.', 'naibabiji-b2b-product-showcase') . '</p>';
        // Initialize color picker
        echo '<script>jQuery(function($){$(".naibabiji-color-field").wpColorPicker();});</script>';
    }
    
    public function button_hover_color_callback() {
        $value = get_option('naibabiji_b2b_product_button_hover_color', '#085FCC');
        echo '<input type="text" name="naibabiji_b2b_product_button_hover_color" value="' . esc_attr($value) . '" class="regular-text naibabiji-color-field" data-default-color="#085FCC" />';
    }

    public function archive_display_mode_callback() {
        $value = get_option('naibabiji_b2b_product_archive_display_mode', 'default');
        $options = array(
            'default'        => __('Default — show category filters and products', 'naibabiji-b2b-product-showcase'),
            'categories_only' => __('Categories only — show category cards in a grid', 'naibabiji-b2b-product-showcase'),
            'products_only'   => __('Products only — hide category filters', 'naibabiji-b2b-product-showcase'),
        );
        echo '<select name="naibabiji_b2b_product_archive_display_mode">';
        foreach ($options as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Choose what to display on the product archive page. Categories-only mode requires category images to be set.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function default_orderby_callback() {
        $value = get_option('naibabiji_b2b_product_default_product_sort', 'date-desc');
        $options = array(
            'date-desc'  => __('Newest', 'naibabiji-b2b-product-showcase'),
            'date-asc'   => __('Oldest', 'naibabiji-b2b-product-showcase'),
            'title-asc'  => __('Title A-Z', 'naibabiji-b2b-product-showcase'),
            'title-desc' => __('Title Z-A', 'naibabiji-b2b-product-showcase'),
        );
        echo '<select name="naibabiji_b2b_product_default_product_sort">';
        foreach ($options as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Default sort order for the product archive, category, and tag pages.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function archive_columns_callback() {
        $value = get_option('naibabiji_b2b_product_archive_columns', 3);
        echo '<select name="naibabiji_b2b_product_archive_columns">';
        for ($i = 1; $i <= 6; $i++) {
            echo '<option value="' . esc_attr($i) . '" ' . selected($value, $i, false) . '>' . esc_html($i) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Number of columns to display on the product archive, category, and tag pages.', 'naibabiji-b2b-product-showcase') . '</p>';
    }
    
    public function content_width_callback() {
        $value = get_option('naibabiji_b2b_product_content_width', '');
        echo '<input type="text" name="naibabiji_b2b_product_content_width" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g. 100% or 1400px" />';
        echo '<p class="description">' . esc_html__('Set the maximum width for product content areas (single, taxonomy, archive pages). Leave empty to use theme default. Examples: 100%, 1400px, 90vw', 'naibabiji-b2b-product-showcase') . '</p>';
echo '<p class="description" style="color:#856404; background:#fff3cd; padding:8px 12px; border-left:4px solid #ffc107; margin-top:8px;">' . esc_html__('Note: The actual displayed width may be limited by your theme\'s container max-width setting. If the set width doesn\'t take effect, please check your theme\'s layout/container settings.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function border_radius_callback() {
        $value = get_option('naibabiji_b2b_product_border_radius', '8px');
        echo '<input type="text" name="naibabiji_b2b_product_border_radius" value="' . esc_attr($value) . '" class="regular-text" placeholder="e.g. 8px or 0.5rem" />';
        echo '<p class="description">' . esc_html__('Set the global border radius for buttons, product cards, and images. Supports all CSS units (px, rem, %, etc.). Examples: 8px, 0.5rem, 50%, 10px 10px 0 0', 'naibabiji-b2b-product-showcase') . '</p>';
    }
    
    public function show_meta_callback() {
        $value = (bool) get_option('naibabiji_b2b_product_show_meta', true);
        echo '<label><input type="checkbox" name="naibabiji_b2b_product_show_meta" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Show the category and tag information block on product pages', 'naibabiji-b2b-product-showcase') . '</label>';
    }

    public function inquiry_button_text_callback() {
        $value = get_option('naibabiji_b2b_product_inquiry_button_text', __('Get Quote', 'naibabiji-b2b-product-showcase'));
        echo '<input type="text" name="naibabiji_b2b_product_inquiry_button_text" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('This setting can be overridden for individual products', 'naibabiji-b2b-product-showcase') . '</p>';
    }
    
    public function default_inquiry_url_callback() {
        $value = get_option('naibabiji_b2b_product_default_inquiry_url', '');
        echo '<div class="naib-b2b-external-only">';
        echo '<input type="url" name="naibabiji_b2b_product_default_inquiry_url" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://blog.naibabiji.com/contact" />';
        echo '<p class="description">' . esc_html__('This default link will be used when a product has no inquiry link set', 'naibabiji-b2b-product-showcase') . '</p>';
        echo '</div>';
    }

    public function inquiry_mode_callback() {
        $value = get_option('naibabiji_b2b_product_inquiry_mode', 'external');
        ?>
        <select name="naibabiji_b2b_product_inquiry_mode" id="naibabiji_b2b_product_inquiry_mode">
            <option value="external" <?php selected($value, 'external'); ?>><?php esc_html_e('External Link — Redirect to inquiry URL', 'naibabiji-b2b-product-showcase'); ?></option>
            <option value="form" <?php selected($value, 'form'); ?>><?php esc_html_e('Built-in Inquiry Form — Popup modal on click', 'naibabiji-b2b-product-showcase'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('This setting applies only to products with Inquiry Type = Standard. Bulk inquiry products always use the built-in bulk inquiry form.', 'naibabiji-b2b-product-showcase'); ?></p>
        <?php
    }

    public function default_inquiry_type_callback() {
        $value = get_option('naibabiji_b2b_product_default_inquiry_type', 'standard');
        ?>
        <select name="naibabiji_b2b_product_default_inquiry_type" id="naibabiji_b2b_product_default_inquiry_type">
            <option value="none" <?php selected($value, 'none'); ?>><?php esc_html_e('None — Disable inquiry', 'naibabiji-b2b-product-showcase'); ?></option>
            <option value="standard" <?php selected($value, 'standard'); ?>><?php esc_html_e('Standard — Single-product inquiry', 'naibabiji-b2b-product-showcase'); ?></option>
            <option value="bulk" <?php selected($value, 'bulk'); ?>><?php esc_html_e('Bulk — Multi-spec batch inquiry', 'naibabiji-b2b-product-showcase'); ?></option>
        </select>
        <p class="description"><?php esc_html_e('When creating a new product, the inquiry type will default to this value. You can still change it for each product individually.', 'naibabiji-b2b-product-showcase'); ?></p>
        <?php
    }

    public function inquiry_form_fields_callback() {
        $value = get_option('naibabiji_b2b_product_inquiry_form_fields', array('name', 'email', 'message'));
        if (!is_array($value)) $value = array();
        
        $fields = array(
            'name'      => __('Name', 'naibabiji-b2b-product-showcase') . ' (' . __('Required', 'naibabiji-b2b-product-showcase') . ')',
            'email'     => __('Email', 'naibabiji-b2b-product-showcase') . ' (' . __('Required', 'naibabiji-b2b-product-showcase') . ')',
            'whatsapp'  => __('Phone / WhatsApp', 'naibabiji-b2b-product-showcase'),
            'job_title'  => __('Job Title', 'naibabiji-b2b-product-showcase'),
            'company'   => __('Company', 'naibabiji-b2b-product-showcase'),
            'country'   => __('Country', 'naibabiji-b2b-product-showcase'),
            'message'   => __('Inquiry Message', 'naibabiji-b2b-product-showcase') . ' (' . __('Required', 'naibabiji-b2b-product-showcase') . ')',
        );
        
        echo '<div class="naib-b2b-form-only">';
        foreach ($fields as $key => $label) {
            $is_required_field = in_array($key, array('name', 'email', 'message'));
            $checked = in_array($key, $value) || $is_required_field;
            $disabled = $is_required_field ? 'disabled' : '';

            echo '<label style="display:block; margin-bottom:5px;">';
            echo '<input type="checkbox" name="naibabiji_b2b_product_inquiry_form_fields[]" value="' . esc_attr($key) . '" ' . checked($checked, true, false) . ' ' . esc_attr($disabled) . ' /> ';
            echo esc_html($label);
            if ($is_required_field) {
                echo '<input type="hidden" name="naibabiji_b2b_product_inquiry_form_fields[]" value="' . esc_attr($key) . '" />';
            }
            echo '</label>';
        }
        echo '</div>';
    }

    public function inquiry_success_msg_callback() {
        $value = get_option('naibabiji_b2b_product_inquiry_success_msg', __('Thank you! Your inquiry has been sent successfully.', 'naibabiji-b2b-product-showcase'));
        echo '<div class="naib-b2b-form-only">';
        echo '<textarea name="naibabiji_b2b_product_inquiry_success_msg" rows="3" class="regular-text">' . esc_textarea($value) . '</textarea>';
        echo '</div>';
    }

    public function inquiry_redirect_enabled_callback() {
        $value = (bool) get_option('naibabiji_b2b_product_inquiry_redirect_enabled', false);
        echo '<div class="naib-b2b-form-only">';
        echo '<label><input type="checkbox" id="naibabiji_b2b_product_inquiry_redirect_enabled" name="naibabiji_b2b_product_inquiry_redirect_enabled" value="1" ' . checked($value, true, false) . ' /> ';
        echo esc_html__('Redirect to a custom page after successful form submission (for Google Ads conversion tracking)', 'naibabiji-b2b-product-showcase') . '</label>';
        echo '<p class="description">' . esc_html__('When enabled, the success message will be replaced by a page redirect.', 'naibabiji-b2b-product-showcase') . '</p>';
        echo '</div>';
    }

    public function inquiry_redirect_url_callback() {
        $value = get_option('naibabiji_b2b_product_inquiry_redirect_url', '');
        echo '<div class="naib-b2b-form-only">';
        echo '<input type="text" name="naibabiji_b2b_product_inquiry_redirect_url" value="' . esc_attr($value) . '" class="regular-text naib-b2b-redirect-url" placeholder="' . esc_attr(home_url('/thank-you')) . '" />';
        echo '<p class="description">' . esc_html__('Enter the full URL of the thank-you/conversion page. Supports relative paths like /thank-you.', 'naibabiji-b2b-product-showcase') . '</p>';
        echo '</div>';
    }

    public function enable_breadcrumbs_callback() {
        $value = get_option('naibabiji_b2b_product_enable_breadcrumbs', true);
        echo '<label><input type="checkbox" name="naibabiji_b2b_product_enable_breadcrumbs" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Display breadcrumb navigation on product pages', 'naibabiji-b2b-product-showcase') . '</label>';
    }
    
    public function enable_schema_callback() {
        $value = get_option('naibabiji_b2b_product_enable_schema', true);
        echo '<label><input type="checkbox" name="naibabiji_b2b_product_enable_schema" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Add Schema.org structured data markup', 'naibabiji-b2b-product-showcase') . '</label>';
        echo '<p class="description">' . esc_html__('Helps search engines better understand and index your products. Important: Please ensure you provide a correct SKU and Price for each product. To prevent SEO errors, if no price is specified, the "Offer" section will be automatically omitted—this is standard for B2B products.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function schema_currency_callback() {
        $value = get_option('naibabiji_b2b_product_schema_currency', 'USD');
        echo '<input type="text" name="naibabiji_b2b_product_schema_currency" value="' . esc_attr($value) . '" class="small-text" placeholder="USD" />';
        echo '<p class="description">' . esc_html__('Currency code for structured data (e.g. USD, EUR, CNY). Used in the "offers" property to satisfy Google requirements.', 'naibabiji-b2b-product-showcase') . '</p>';
    }
    
    public function excerpt_length_callback() {
        $value = get_option('naibabiji_b2b_product_excerpt_length', 20);
        echo '<input type="number" name="naibabiji_b2b_product_excerpt_length" value="' . esc_attr($value) . '" class="small-text" min="1" max="100" step="1" />';
        echo '<p class="description">' . esc_html__('Number of words to show in product excerpts on archive and search pages', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    /**
     * AI Section and Field Callbacks
     */
    public function ai_section_callback() {
        echo '<p>' . esc_html__('Configure industrial-grade AI customer service powered by DeepSeek.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_connection_section_callback() {
        echo '<p>' . esc_html__('Connect your AI Manager instance and activate your license key.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_knowledge_section_callback() {
        echo '<p>' . esc_html__('Help the AI understand your business. The more detail you provide, the better it can answer customer questions.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_enable_callback() {
        $value = get_option('naibabiji_b2b_ai_enable', false);
        echo '<label><input type="checkbox" name="naibabiji_b2b_ai_enable" value="1" ' . checked($value, true, false) . ' /> ' . esc_html__('Enable the floating AI chat widget on product pages', 'naibabiji-b2b-product-showcase') . '</label>';
    }

    public function ai_license_key_callback() {
        $value = get_option('naibabiji_b2b_ai_license_key', '');
        echo '<div style="display:flex; gap:10px; align-items:center;">';
        echo '<input type="text" id="naib_ai_license_key" name="naibabiji_b2b_ai_license_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="NB-XXXX-XXXX" />';
        echo '<button type="button" id="naib_ai_verify_btn" class="button">' . esc_html__('Verify License', 'naibabiji-b2b-product-showcase') . '</button>';
        echo '<button type="button" id="naib_ai_unbind_btn" class="button button-link-delete" style="display:none; color: #dc3232;">' . esc_html__('Deactivate License', 'naibabiji-b2b-product-showcase') . '</button>';
        echo '<span id="naib_ai_verify_status" style="margin-left:10px; font-weight:bold;"></span>';
        
        // Usage Info Container
        echo '<div id="naib_ai_usage_container" style="margin-top:15px; display:none; max-width: 400px; padding: 15px; background: #f9f9f9; border: 1px solid #e5e5e5; border-radius: var(--naibabiji-b2b-border-radius);">';
        echo '<h4 style="margin: 0 0 10px 0;">' . esc_html__('Service Usage', 'naibabiji-b2b-product-showcase') . '</h4>';
        echo '<div style="margin-bottom: 5px; display: flex; justify-content: space-between;">';
        echo '<span>' . esc_html__('Tokens/Requests:', 'naibabiji-b2b-product-showcase') . '</span>';
        echo '<span id="naib_ai_usage_text">0 / 0</span>';
        echo '</div>';
        echo '<div style="background: #eee; height: 10px; border-radius: var(--naibabiji-b2b-border-radius); overflow: hidden; margin-bottom: 10px;">';
        echo '<div id="naib_ai_usage_bar" style="background: #2271b1; width: 0%; height: 100%; transition: width 0.5s;"></div>';
        echo '</div>';
        echo '<div style="font-size: 12px; color: #666;">';
        echo '<span>' . esc_html__('Expires:', 'naibabiji-b2b-product-showcase') . ' </span>';
        echo '<span id="naib_ai_expiry_text">N/A</span>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '<p class="description">' . esc_html__('Your unique license key for the AI service.', 'naibabiji-b2b-product-showcase') . '</p>';

        ?>
        <?php
    }

    public function ai_manager_url_callback() {
        $value = get_option('naibabiji_b2b_ai_manager_url', '');
        echo '<input type="url" name="naibabiji_b2b_ai_manager_url" value="' . esc_attr($value) . '" class="regular-text" placeholder="https://manager.yourdomain.com" />';
        echo '<p class="description">' . esc_html__('The URL of your AI Manager instance.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_service_profile_callback() {
        $value = get_option('naibabiji_b2b_ai_service_profile', '');
        $placeholder = __(
            "Example:\nWe are [Company Name], a B2B supplier specializing in [industry/product category], serving over [X] customers across [X] countries.\nCore Products: [List your product lines]\nCertifications: [ISO 9001 / CE / RoHS / etc.]\nLead Time: Typically [X] business days; supports bulk and FCL orders.\nPayment: T/T, L/C, PayPal\nContact: sales@yourdomain.com",
            'naibabiji-b2b-product-showcase'
        );
        echo '<textarea name="naibabiji_b2b_ai_service_profile" rows="8" cols="50" class="large-text" placeholder="' . esc_attr($placeholder) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('This tells the AI who you are. Include company background, key capabilities, certifications, lead times, and payment terms.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_faqs_callback() {
        $value = get_option('naibabiji_b2b_ai_faqs', '');
        $placeholder = __(
            "One Q&A per line, format: Q: [Question] | A: [Answer]\n\nExample:\nQ: What is your minimum order quantity? | A: MOQ is 100 units. Samples accepted from 1 unit.\nQ: Do you support OEM/ODM? | A: Yes. OEM/ODM available with drawings. Lead time 30 days.\nQ: What certifications do you have? | A: ISO 9001, CE, and RoHS certified.\nQ: What are your payment terms? | A: T/T 30% deposit, 70% before shipment. L/C also accepted.",
            'naibabiji-b2b-product-showcase'
        );
        echo '<textarea name="naibabiji_b2b_ai_faqs" rows="10" cols="50" class="large-text" placeholder="' . esc_attr($placeholder) . '">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">' . esc_html__('Add your most common customer questions. The AI will use these to answer inquiries accurately. Format: Q: [Question] | A: [Answer], one per line.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_display_section_callback() {
        echo '<p>' . esc_html__('Control where the floating AI chat icon appears on your product pages when no inquiry button is present.', 'naibabiji-b2b-product-showcase') . '</p>';
    }

    public function ai_float_position_callback() {
        $value = get_option('naibabiji_b2b_ai_float_position', 'bottom-right');
        $options = array(
            'bottom-right' => __('Bottom Right', 'naibabiji-b2b-product-showcase'),
            'bottom-left'  => __('Bottom Left', 'naibabiji-b2b-product-showcase'),
            'right-center' => __('Right Center', 'naibabiji-b2b-product-showcase'),
            'left-center'  => __('Left Center', 'naibabiji-b2b-product-showcase'),
        );
        echo '<select name="naibabiji_b2b_ai_float_position">';
        foreach ($options as $key => $label) {
            echo '<option value="' . esc_attr($key) . '" ' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public function ai_float_offset_callback() {
        $value = get_option('naibabiji_b2b_ai_float_offset', '30px');
        echo '<input type="text" name="naibabiji_b2b_ai_float_offset" value="' . esc_attr($value) . '" class="small-text" placeholder="30px" />';
        echo '<p class="description">' . esc_html__('Distance from the edge. Include CSS unit (e.g., 30px, 5%, 2em).', 'naibabiji-b2b-product-showcase') . '</p>';
    }
    
    /**
     * Strict HEX color sanitizer: returns default if invalid
     */
    public function sanitize_hex_color_strict( $color ) {
        $color = trim( (string) $color );
        // Allow 3 or 6 hex, with leading #
        if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
            return strtoupper($color);
        }
        return '#0A7AFF';
    }

    /**
     * Sanitize grid columns: must be 1-6
     */
    public function sanitize_grid_columns($value) {
        $value = absint($value);
        if ($value < 1) $value = 1;
        if ($value > 6) $value = 6;
        return $value;
    }
    
    public function sanitize_archive_display_mode($value) {
        $allowed = array('default', 'categories_only', 'products_only');
        return in_array($value, $allowed, true) ? $value : 'default';
    }

    public function sanitize_product_sort($value) {
        $allowed = array('date-desc', 'date-asc', 'title-asc', 'title-desc');
        return in_array($value, $allowed, true) ? $value : 'date-desc';
    }

    public function sanitize_content_width($value) {
        $value = trim($value);
        if (empty($value)) {
            return '';
        }
        if (preg_match('/^(\d+(?:\.\d+)?)(%|px|vw|em|rem)$/', $value)) {
            return $value;
        }
        return '';
    }

    public function sanitize_css_size($value) {
        $value = trim((string) $value);
        if ($this->is_valid_css_size_token($value)) {
            return $value;
        }
        return '30px';
    }

    public function sanitize_border_radius($value) {
        $value = trim((string) $value);
        $parts = preg_split('/\s+/', $value);
        if (!$parts || count($parts) > 4) {
            return '8px';
        }
        foreach ($parts as $part) {
            if (!$this->is_valid_css_size_token($part)) {
                return '8px';
            }
        }
        return implode(' ', $parts);
    }

    private function is_valid_css_size_token($value) {
        return '0' === $value || (bool) preg_match('/^\d+(?:\.\d+)?(?:px|rem|em|%|vh|vw)$/i', $value);
    }

    public function sanitize_array($value) {
        if (!is_array($value)) {
            return array();
        }
        return array_map('sanitize_text_field', $value);
    }

    /**
     * Sanitize redirect URL: supports absolute URLs and relative paths.
     */
    public function sanitize_redirect_url($value) {
        $value = trim($value);
        if (empty($value)) {
            return '';
        }
        // Relative path starting with /
        if (strpos($value, '/') === 0 && strpos($value, '//') !== 0) {
            return sanitize_text_field($value);
        }
        // Absolute or protocol-relative URL
        return esc_url_raw($value);
    }
}
