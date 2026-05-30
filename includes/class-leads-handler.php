<?php
/**
 * Handler for AI Leads/Inquiries
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_AI_Leads_Handler {

    private static $instance = null;
    private static $table_name;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'naibb2pr_ai_leads';

        add_action('admin_menu', array($this, 'add_leads_menu'));
        add_action('wp_ajax_naib_ai_save_lead', array($this, 'handle_save_lead_ajax'));
        add_action('wp_ajax_nopriv_naib_ai_save_lead', array($this, 'handle_save_lead_ajax'));

        // Bulk inquiry AJAX handlers
        add_action('wp_ajax_naib_submit_bulk_inquiry', array($this, 'handle_bulk_inquiry_ajax'));
        add_action('wp_ajax_nopriv_naib_submit_bulk_inquiry', array($this, 'handle_bulk_inquiry_ajax'));
        add_action('wp_ajax_naib_get_lead_detail', array($this, 'handle_get_lead_detail_ajax'));

        // Cleanup hooks
        add_action('wp_ajax_naib_cleanup_leads', array($this, 'handle_cleanup_ajax'));
        add_action('init', array($this, 'schedule_cleanup_event'));
        add_action('naibabiji_b2b_weekly_cleanup', array($this, 'run_cleanup_for_cron'));
    }

    /**
     * Get visitor IP for lightweight anti-spam checks.
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            return sanitize_text_field(trim($ips[0]));
        }
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '0.0.0.0';
    }

    /**
     * Apply simple per-IP rate limiting to frontend submissions.
     */
    private function check_rate_limit($prefix, $limit, $window) {
        $rate_key   = $prefix . md5($this->get_client_ip());
        $rate_count = (int) get_transient($rate_key);
        if ($rate_count >= $limit) {
            return false;
        }
        set_transient($rate_key, $rate_count + 1, $window);
        return true;
    }

    /**
     * Clear cached lead lists for all built-in filters.
     */
    private function clear_leads_cache() {
        $filters = array('', 'ai_chat', 'inquiry_form', 'contact_form');
        foreach ($filters as $filter) {
            wp_cache_delete('naibabiji_b2b_ai_leads_list_' . md5($filter));
        }
        wp_cache_delete('naibabiji_b2b_ai_leads_list');
    }

    /**
     * Extract a labelled value from the stored contact string.
     */
    private function extract_contact_field($contact, $label) {
        if (preg_match('/^' . preg_quote($label, '/') . ':\s*(.+)$/mi', $contact, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }

    /**
     * Validate required frontend inquiry/contact form fields server-side.
     */
    private function validate_contact_submission($source, $contact, $message) {
        if (!in_array($source, array('inquiry_form', 'contact_form'), true)) {
            return true;
        }

        $name = Naibabiji_B2B_Product_Security::get_post_data('name', '', 'sanitize_text_field');
        if ('' === $name) {
            $name = $this->extract_contact_field($contact, 'Name');
        }
        $email = Naibabiji_B2B_Product_Security::get_post_data('email', '', 'sanitize_email');
        if ('' === $email) {
            $email = sanitize_email($this->extract_contact_field($contact, 'Email'));
        }

        if ('' === $name) {
            return new WP_Error('missing_name', __('Name is required', 'naibabiji-b2b-product-showcase'));
        }
        if ('' === $email || !is_email($email)) {
            return new WP_Error('invalid_email', __('Please enter a valid email address', 'naibabiji-b2b-product-showcase'));
        }
        if ('' === trim($message)) {
            return new WP_Error('missing_message', __('Message is required', 'naibabiji-b2b-product-showcase'));
        }

        return true;
    }

    /**
     * Create the leads table
     */
    public static function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'naibb2pr_ai_leads';

        $sql = "CREATE TABLE $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_contact varchar(255) NOT NULL,
            user_message text,
            product_id bigint(20),
            chat_history longtext,
            lead_source varchar(50) DEFAULT 'ai_chat',
            page_title varchar(255) DEFAULT '',
            inquiry_type varchar(20) DEFAULT 'single',
            inquiry_data longtext,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_inquiry_type (inquiry_type)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * AJAX handler for frontend lead submission
     */
    public function handle_save_lead_ajax() {
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!wp_verify_nonce($nonce, 'naibabiji_b2b_product_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Honeypot check — bots fill hidden fields, humans don't
        $hp_field = Naibabiji_B2B_Product_Security::get_post_data('naib_hp_field', '');
        if (!empty($hp_field)) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!$this->check_rate_limit('naib_inquiry_rate_', 5, 5 * MINUTE_IN_SECONDS)) {
            wp_send_json_error('Too many submissions. Please try again later.');
            return;
        }

        /**
         * Filter to allow captcha plugins to perform additional validation.
         * Return false to block submission, or true to allow.
         *
         * @since 4.2.0
         * @param bool  $is_valid Whether the submission passes validation. Default true.
         * @param array $_POST    The raw POST data.
         */
        $is_valid = apply_filters('naibabiji_contact_form_validate', true, $_POST);
        if (!$is_valid) {
            wp_send_json_error('Validation failed');
            return;
        }

        $contact     = Naibabiji_B2B_Product_Security::get_post_data('contact', '', 'sanitize_textarea_field');
        if (empty($contact)) {
            wp_send_json_error('Contact info is required');
            return;
        }

        $source      = Naibabiji_B2B_Product_Security::get_post_data('source', 'ai_chat');
        $message     = Naibabiji_B2B_Product_Security::get_post_data('message', '', 'sanitize_textarea_field');
        $validation  = $this->validate_contact_submission($source, $contact, $message);
        if (is_wp_error($validation)) {
            wp_send_json_error($validation->get_error_message());
            return;
        }

        $history_raw = Naibabiji_B2B_Product_Security::get_post_data('history', '[]');
        // JSON decode and sanitize history array
        $history     = is_string($history_raw) ? json_decode($history_raw, true) : $history_raw;
        if (!is_array($history)) {
            $history = [];
        } else {
            // Sanitize each history item
            $history = array_map(function($h) {
                return [
                    'role'    => isset($h['role']) ? sanitize_text_field($h['role']) : '',
                    'content' => isset($h['content']) ? sanitize_text_field($h['content']) : ''
                ];
            }, $history);
        }

        $data = [
            'contact'    => $contact,
            'message'    => $message,
            'product_id' => (int) Naibabiji_B2B_Product_Security::get_post_data('product_id', 0, 'absint'),
            'history'    => $history,
            'source'     => $source,
            'page_title' => Naibabiji_B2B_Product_Security::get_post_data('page_title', ''),
        ];

        $result = $this->save_lead($data);

        if ($result) {
            $response = array('id' => $result);
            $redirect_url = $this->get_redirect_url();
            if ($redirect_url) {
                $response['redirect_url'] = $redirect_url;
            }
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Failed to save inquiry');
        }
    }

    /**
     * AJAX handler for bulk inquiry submission
     */
    public function handle_bulk_inquiry_ajax() {
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!wp_verify_nonce($nonce, 'naibabiji_b2b_bulk_inquiry_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Honeypot check
        $hp_field = Naibabiji_B2B_Product_Security::get_post_data('naib_hp_field', '');
        if (!empty($hp_field)) {
            wp_send_json_error('Security check failed');
            return;
        }

        // Rate limiting — 5 minutes / 3 submissions per IP
        if (!$this->check_rate_limit('naib_bulk_inquiry_rate_', 3, 5 * MINUTE_IN_SECONDS)) {
            wp_send_json_error('Too many submissions. Please try again later.');
            return;
        }

        $is_valid = apply_filters('naibabiji_contact_form_validate', true, $_POST);
        if (!$is_valid) {
            wp_send_json_error('Validation failed');
            return;
        }

        // Build rich contact string from form fields (same format as existing leads)
        $contact_parts = array();
        $form_fields = Naibabiji_B2B_Settings::get('inquiry_form_fields', array('name', 'email', 'message'));

        if (in_array('name', $form_fields, true)) {
            $name = Naibabiji_B2B_Product_Security::get_post_data('name', '');
            if ('' === $name) {
                wp_send_json_error('Name is required');
                return;
            }
            $contact_parts[] = 'Name: ' . $name;
        }
        if (in_array('email', $form_fields, true)) {
            $email = Naibabiji_B2B_Product_Security::get_post_data('email', '', 'sanitize_email');
            if ('' === $email || !is_email($email)) {
                wp_send_json_error('Please enter a valid email address');
                return;
            }
            $contact_parts[] = 'Email: ' . $email;
        }
        $whatsapp = Naibabiji_B2B_Product_Security::get_post_data('whatsapp', '');
        if (in_array('whatsapp', $form_fields) && !empty($whatsapp)) {
            $contact_parts[] = 'WhatsApp: ' . $whatsapp;
        }
        $job_title = Naibabiji_B2B_Product_Security::get_post_data('job_title', '');
        if (in_array('job_title', $form_fields) && !empty($job_title)) {
            $contact_parts[] = 'Job Title: ' . $job_title;
        }
        $company = Naibabiji_B2B_Product_Security::get_post_data('company', '');
        if (in_array('company', $form_fields) && !empty($company)) {
            $contact_parts[] = 'Company: ' . $company;
        }
        $country = Naibabiji_B2B_Product_Security::get_post_data('country', '');
        if (in_array('country', $form_fields) && !empty($country)) {
            $contact_parts[] = 'Country: ' . $country;
        }

        $user_contact = implode("\n", $contact_parts);
        if (empty($user_contact)) {
            wp_send_json_error('Contact info is required');
            return;
        }

        $user_message = Naibabiji_B2B_Product_Security::get_post_data('message', '', 'sanitize_textarea_field');
        // Parse cart data from POST — cart_data is JSON, each field sanitized after decode
        $cart_data_raw = Naibabiji_B2B_Product_Security::get_post_data('cart_data', '', 'strval');
        $cart_data = json_decode($cart_data_raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($cart_data)) {
            wp_send_json_error('Invalid inquiry data format');
            return;
        }

        if (empty($cart_data['items'])) {
            wp_send_json_error('Inquiry cart is empty');
            return;
        }

        // Build inquiry_data JSON
        $products = array();
        $total_specs = 0;
        foreach ($cart_data['items'] as $item) {
            $product_id = isset($item['product_id']) ? absint($item['product_id']) : 0;
            $specs = isset($item['specs']) ? $item['specs'] : array();
            $filtered_specs = array();
            foreach ($specs as $spec) {
                $qty = isset($spec['quantity']) ? absint($spec['quantity']) : 0;
                if ($qty <= 0) continue;
                $filtered_specs[] = array(
                    'code'        => sanitize_text_field($spec['code'] ?? ''),
                    'description' => sanitize_text_field($spec['description'] ?? ''),
                    'quantity'    => $qty,
                );
            }
            if (empty($filtered_specs)) continue;

            $products[] = array(
                'product_id'    => $product_id,
                'product_name'  => sanitize_text_field($item['product_name'] ?? ''),
                'product_url'   => esc_url_raw($item['product_url'] ?? ''),
                'product_image' => esc_url_raw($item['product_image'] ?? ''),
                'specs'         => $filtered_specs,
            );
            $total_specs += count($filtered_specs);
        }

        if (empty($products)) {
            wp_send_json_error('No valid specs in inquiry cart');
            return;
        }

        $inquiry_data = array(
            'products'       => $products,
            'total_products' => count($products),
            'total_specs'    => $total_specs,
        );

        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- $wpdb->insert() is the standard WP data insertion API
        $inserted = $wpdb->insert(
            self::$table_name,
            array(
                'user_contact'  => $user_contact,
                'user_message'  => $user_message,
                'product_id'    => null,
                'chat_history'  => '',
                'lead_source'   => 'inquiry_form',
                'page_title'    => Naibabiji_B2B_Product_Security::get_post_data('page_title', ''),
                'inquiry_type'  => 'bulk',
                'inquiry_data'  => wp_json_encode($inquiry_data, JSON_UNESCAPED_UNICODE),
                'created_at'    => current_time('mysql'),
            )
        );

        if ($inserted) {
            $lead_id = $wpdb->insert_id;
            $this->clear_leads_cache();
            $this->send_bulk_inquiry_email($user_contact, $user_message, $inquiry_data, $lead_id);
            $response = array('id' => $lead_id);
            $redirect_url = $this->get_redirect_url();
            if ($redirect_url) {
                $response['redirect_url'] = $redirect_url;
            }
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Failed to save inquiry');
        }
    }

    /**
     * AJAX handler for loading lead detail (admin)
     */
    public function handle_get_lead_detail_ajax() {
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!wp_verify_nonce($nonce, 'naib_lead_detail_nonce')) {
            wp_send_json_error('Security check failed');
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }

        $lead_id = (int) Naibabiji_B2B_Product_Security::get_post_data('lead_id', 0, 'absint');
        if (!$lead_id) {
            wp_send_json_error('Missing lead ID');
            return;
        }
        global $wpdb;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $wpdb->prepare() is used with %d; table name from class constant is safe
        $lead = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . self::$table_name . " WHERE id = %d",
            $lead_id
        ));
        // phpcs:enable

        if (!$lead) {
            wp_send_json_error('Not found');
            return;
        }

        $inquiry_data = null;
        if ($lead->inquiry_type === 'bulk' && !empty($lead->inquiry_data)) {
            $inquiry_data = json_decode($lead->inquiry_data, true);
        }

        // Return only fields needed for display, not the full DB row
        wp_send_json_success(array(
            'lead'         => array(
                'id'            => (int) $lead->id,
                'user_contact'  => $lead->user_contact,
                'user_message'  => $lead->user_message,
                'product_id'    => (int) $lead->product_id,
                'lead_source'   => $lead->lead_source,
                'inquiry_type'  => $lead->inquiry_type,
                'page_title'    => $lead->page_title,
                'created_at'    => $lead->created_at,
            ),
            'inquiry_data' => $inquiry_data,
        ));
    }

    /**
     * Send bulk inquiry email notification
     */
    private function send_bulk_inquiry_email($user_contact, $user_message, $inquiry_data, $lead_id) {
        $admin_email = get_option('admin_email');
        $site_name   = get_bloginfo('name');

        $message = "New Bulk Inquiry\n\n";
        $message .= "Customer Info:\n";
        $message .= $user_contact . "\n\n";

        $message .= "Inquiry Products:\n";
        foreach ($inquiry_data['products'] as $index => $product) {
            $message .= sprintf("【Product %d】%s\n", $index + 1, $product['product_name']);
            foreach ($product['specs'] as $spec) {
                $message .= sprintf("  - %s (%s) × %d\n",
                    $spec['code'],
                    $spec['description'],
                    $spec['quantity']
                );
            }
            $message .= "\n";
        }

        if (!empty($user_message)) {
            $message .= "Customer Note:\n" . $user_message . "\n\n";
        }

        $message .= sprintf(
            "View Details: %s\n",
            admin_url('admin.php?page=naibabiji-b2b-ai-leads')
        );

        $subject = sprintf("[%s] New Bulk Inquiry", $site_name);
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Save a new lead
     */
    public function save_lead($data) {
        global $wpdb;
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table operation
        $inserted = $wpdb->insert(
            self::$table_name,
            array(
                'user_contact' => sanitize_textarea_field($data['contact']),
                'user_message' => sanitize_textarea_field($data['message']),
                'product_id'   => isset($data['product_id']) ? absint($data['product_id']) : 0,
                'chat_history' => isset($data['history']) ? wp_json_encode($data['history'], JSON_UNESCAPED_UNICODE) : '',
                'lead_source'  => isset($data['source']) ? sanitize_text_field($data['source']) : 'ai_chat',
                'page_title'   => isset($data['page_title']) ? sanitize_text_field($data['page_title']) : '',
                'created_at'   => current_time('mysql')
            )
        );

        if ($inserted) {
            $this->clear_leads_cache();
            $this->trigger_notification($data);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Trigger notification for new lead
     */
    private function trigger_notification($data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        $source = isset($data['source']) ? $data['source'] : 'ai_chat';
        if ($source === 'inquiry_form') {
            $source_label = __('Inquiry Form', 'naibabiji-b2b-product-showcase');
        } elseif ($source === 'contact_form') {
            $source_label = __('Contact Form', 'naibabiji-b2b-product-showcase');
        } else {
            $source_label = __('AI Chat', 'naibabiji-b2b-product-showcase');
        }

        // For contact form, use page title instead of product name
        if ($source === 'contact_form') {
            $context_name = isset($data['page_title']) ? $data['page_title'] : __('General', 'naibabiji-b2b-product-showcase');
            $context_label = __('Page', 'naibabiji-b2b-product-showcase');
            $subject = sprintf("[%s] %s: %s", $site_name, __('New Message', 'naibabiji-b2b-product-showcase'), $context_name);
        } else {
            $context_name = $data['product_id'] ? get_the_title($data['product_id']) : __('None/General', 'naibabiji-b2b-product-showcase');
            $context_label = __('Product', 'naibabiji-b2b-product-showcase');
            $subject = sprintf("[%s] %s: %s", $site_name, __('New Inquiry', 'naibabiji-b2b-product-showcase'), $context_name);
        }
        
        // Field mapping style content
        $message = sprintf(
            "%s:\n\n" .
            "[%s]: %s\n" .
            "[%s]: %s\n" .
            "[%s]: %s\n" .
            "[%s]: %s\n" .
            "[%s]: %d\n\n" .
            "%s: %s",
            $source === 'contact_form'
                ? __('You have received a new message', 'naibabiji-b2b-product-showcase')
                : __('You have received a new inquiry', 'naibabiji-b2b-product-showcase'),
            __('naib_b2b_source', 'naibabiji-b2b-product-showcase'), $source_label,
            $source === 'contact_form'
                ? __('naib_b2b_page', 'naibabiji-b2b-product-showcase')
                : __('naib_b2b_product', 'naibabiji-b2b-product-showcase'), $context_name,
            __('naib_b2b_contact', 'naibabiji-b2b-product-showcase'), $data['contact'],
            __('naib_b2b_message', 'naibabiji-b2b-product-showcase'), $data['message'],
            __('naib_b2b_product_id', 'naibabiji-b2b-product-showcase'), $data['product_id'],
            __('View detail in dashboard', 'naibabiji-b2b-product-showcase'), admin_url('admin.php?page=naibabiji-b2b-ai-leads')
        );

        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Get the redirect URL if redirect is enabled and a URL is configured.
     *
     * @return string Empty string if redirect is disabled or URL is not set.
     */
    private function get_redirect_url() {
        $enabled = Naibabiji_B2B_Settings::get('inquiry_redirect_enabled', false);
        if (!$enabled) {
            return '';
        }
        $url = Naibabiji_B2B_Settings::get('inquiry_redirect_url', '');
        if (empty($url)) {
            return '';
        }
        // Support relative paths by prepending home_url.
        // Skip protocol-relative URLs (//example.com/...) and absolute URLs.
        if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0 && strpos($url, '/') === 0) {
            $url = home_url($url);
        }
        return $url;
    }

    /**
     * Add admin menu for leads
     */
    public function add_leads_menu() {
        add_submenu_page(
            'naibabiji-b2b-showcase',
            __('Inquiries', 'naibabiji-b2b-product-showcase'),
            __('Inquiries', 'naibabiji-b2b-product-showcase'),
            'manage_options',
            'naibabiji-b2b-ai-leads',
            array($this, 'render_leads_page')
        );
    }

    /**
     * Schedule cleanup event
     */
    public function schedule_cleanup_event() {
        if (!wp_next_scheduled('naibabiji_b2b_weekly_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'naibabiji_b2b_weekly_cleanup');
        }
    }

    /**
     * Wrapper method for cron cleanup - does not return anything
     * This is required because WordPress action callbacks should not return values
     *
     * @since 4.0.0
     * @return void
     */
    public function run_cleanup_for_cron() {
        $this->cleanup_old_leads();
    }

    /**
     * Cleanup old leads (can be called manually or via cron)
     *
     * @param int $days Number of days to keep records
     * @return int|false Number of rows deleted or false on error
     */
    public function cleanup_old_leads($days = 90) {
        global $wpdb;
        
        // Sanitize input
        $days = absint($days);
        if ($days < 1) {
            $days = 90;
        }
        
        $days  = absint($days);
        $table = esc_sql(self::$table_name);
        
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is static. %i requires WP 6.2+.
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)", $days));

        if (false !== $deleted) {
            $this->clear_leads_cache();
        }

        return $deleted;
    }

    /**
     * AJAX handler for cleanup action
     */
    public function handle_cleanup_ajax() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            return;
        }
        
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!wp_verify_nonce($nonce, 'naib_cleanup_leads')) {
            wp_send_json_error('Security check failed');
            return;
        }

        $days = (int) Naibabiji_B2B_Product_Security::get_post_data('days', 90, 'absint');
        $deleted = $this->cleanup_old_leads($days);
        
        wp_send_json_success(array(
            'message' => sprintf(
                /* translators: %d: number of records deleted */
                esc_html__('Cleanup completed. %d records deleted.', 'naibabiji-b2b-product-showcase'),
                $deleted
            ),
            'deleted' => $deleted
        ));
    }

    /**
     * Render the leads list page
     */
    public function render_leads_page() {
        global $wpdb;
        
        // Handle cleanup action
        $cleanup_nonce = Naibabiji_B2B_Product_Security::get_post_data('naib_cleanup_nonce', '');
        if (isset($_POST['naib_cleanup_action']) && wp_verify_nonce($cleanup_nonce, 'naib_cleanup_leads')) {
            $days = (int) Naibabiji_B2B_Product_Security::get_post_data('cleanup_days', 90, 'absint');
            $deleted_count = $this->cleanup_old_leads($days);
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 sprintf(
                     /* translators: %d: number of days */
                     esc_html__('Cleanup completed. Records older than %d days have been removed.', 'naibabiji-b2b-product-showcase'),
                     absint($days)
                 ) . '</p></div>';
            
            // Clear cache after cleanup
            $this->clear_leads_cache();
        }
        
        $source_filter = isset($_GET['source_filter']) ? sanitize_text_field(wp_unslash($_GET['source_filter'])) : '';
        if (!in_array($source_filter, array('', 'ai_chat', 'inquiry_form', 'contact_form'), true)) {
            $source_filter = '';
        }

        // Try to get leads from cache first
        $cache_key = 'naibabiji_b2b_ai_leads_list_' . md5($source_filter);
        $leads = wp_cache_get($cache_key);
        
        if (false === $leads) {
            $where_clause = "1=1";
            if ($source_filter) {
                $where_clause .= $wpdb->prepare(" AND lead_source = %s", $source_filter);
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            $leads = $wpdb->get_results("SELECT * FROM " . esc_sql(self::$table_name) . " WHERE $where_clause ORDER BY created_at DESC LIMIT 100");
            // Cache for 5 minutes
            wp_cache_set($cache_key, $leads, '', 300);
        }
        ?>
        <div class="wrap">
            <div class="naib-page-header">
                <div>
                    <h1><?php esc_html_e('Inquiries', 'naibabiji-b2b-product-showcase'); ?></h1>
                    <p class="naib-subtitle"><?php esc_html_e('View and manage customer inquiries from all sources.', 'naibabiji-b2b-product-showcase'); ?></p>
                </div>
            </div>

            <!-- Cleanup Box -->
            <div class="naib-card" style="max-width:600px;">
                <div class="naib-section-header">
                    <h2><?php esc_html_e('Cleanup Old Records', 'naibabiji-b2b-product-showcase'); ?></h2>
                </div>
                <p><?php esc_html_e('Remove inquiry records older than a specified number of days to free up database space. This includes AI chat logs, product inquiry forms, and contact form submissions.', 'naibabiji-b2b-product-showcase'); ?></p>
                <form method="post" action="">
                    <?php wp_nonce_field('naib_cleanup_leads', 'naib_cleanup_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="cleanup_days"><?php esc_html_e('Days to keep', 'naibabiji-b2b-product-showcase'); ?></label>
                            </th>
                            <td>
                                <input name="cleanup_days" type="number" id="cleanup_days" value="90" min="7" max="365" class="small-text" />
                                <p class="description"><?php esc_html_e('Records older than this will be permanently deleted.', 'naibabiji-b2b-product-showcase'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="naib_cleanup_action" class="button button-secondary" value="<?php esc_attr_e('Run Cleanup Now', 'naibabiji-b2b-product-showcase'); ?>" 
                               onclick="return confirm('<?php esc_html_e('Are you sure you want to delete old records? This action cannot be undone.', 'naibabiji-b2b-product-showcase'); ?>')" />
                    </p>
                </form>
            </div>

            <!-- Filter -->
            <div class="naib-action-bar">
                <form method="get" action="" class="naib-filter-group">
                    <input type="hidden" name="page" value="naibabiji-b2b-ai-leads" />
                    <select name="source_filter">
                        <option value=""><?php esc_html_e('All Sources', 'naibabiji-b2b-product-showcase'); ?></option>
                        <option value="ai_chat" <?php selected(isset($_GET['source_filter']) && $_GET['source_filter'] === 'ai_chat'); ?>><?php esc_html_e('AI Chat', 'naibabiji-b2b-product-showcase'); ?></option>
                        <option value="inquiry_form" <?php selected(isset($_GET['source_filter']) && $_GET['source_filter'] === 'inquiry_form'); ?>><?php esc_html_e('Inquiry Form', 'naibabiji-b2b-product-showcase'); ?></option>
                        <option value="contact_form" <?php selected(isset($_GET['source_filter']) && $_GET['source_filter'] === 'contact_form'); ?>><?php esc_html_e('Contact Form', 'naibabiji-b2b-product-showcase'); ?></option>
                    </select>
                    <input type="submit" class="button" value="<?php esc_attr_e('Filter', 'naibabiji-b2b-product-showcase'); ?>" />
                </form>
            </div>

            <!-- Leads Table -->
            <div class="naib-table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Source', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Type', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Contact', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Message', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Products', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Context', 'naibabiji-b2b-product-showcase'); ?></th>
                        <th><?php esc_html_e('Actions', 'naibabiji-b2b-product-showcase'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leads): ?>
                        <?php foreach ($leads as $lead): ?>
                            <?php
                            $source = isset($lead->lead_source) ? $lead->lead_source : 'ai_chat';
                            $inquiry_type = isset($lead->inquiry_type) ? $lead->inquiry_type : 'single';
                            $display_name = esc_html($lead->user_contact);
                            if (in_array($source, array('inquiry_form', 'contact_form')) && preg_match('/^Name:\s*(.+)/i', $lead->user_contact, $m)) {
                                $display_name = esc_html(trim($m[1]));
                            }

                            // Parse product count for bulk inquiries
                            $product_info = '—';
                            if ($inquiry_type === 'bulk' && !empty($lead->inquiry_data)) {
                                $inq_data = json_decode($lead->inquiry_data, true);
                                if (is_array($inq_data)) {
                                    $pcount = isset($inq_data['total_products']) ? absint($inq_data['total_products']) : 0;
                                    $scount = isset($inq_data['total_specs']) ? absint($inq_data['total_specs']) : 0;
                                    $product_info = sprintf('%d %s<br>%d %s',
                                        $pcount, __('products', 'naibabiji-b2b-product-showcase'),
                                        $scount, __('specs', 'naibabiji-b2b-product-showcase'));
                                }
                            } elseif ($lead->product_id) {
                                $edit_link = get_edit_post_link($lead->product_id);
                                $product_info = $edit_link
                                    ? '<a href="' . esc_url($edit_link) . '" target="_blank">' . esc_html(get_the_title($lead->product_id)) . '</a>'
                                    : esc_html(get_the_title($lead->product_id));
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($lead->created_at); ?></td>
                                <td>
                                    <?php
                                    if ($source === 'inquiry_form') {
                                        echo '<span class="naib-pill naib-pill--blue">' . esc_html__('Inquiry', 'naibabiji-b2b-product-showcase') . '</span>';
                                    } elseif ($source === 'contact_form') {
                                        echo '<span class="naib-pill naib-pill--green">' . esc_html__('Contact', 'naibabiji-b2b-product-showcase') . '</span>';
                                    } else {
                                        echo '<span class="naib-pill naib-pill--gray">' . esc_html__('AI Chat', 'naibabiji-b2b-product-showcase') . '</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($inquiry_type === 'bulk'): ?>
                                        <span class="naib-pill naib-pill--orange"><?php esc_html_e('Bulk', 'naibabiji-b2b-product-showcase'); ?></span>
                                    <?php elseif ($source === 'ai_chat'): ?>
                                        <span class="naib-text-secondary">—</span>
                                    <?php else: ?>
                                        <span class="naib-pill naib-pill--green"><?php esc_html_e('Standard', 'naibabiji-b2b-product-showcase'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($display_name); ?></strong></td>
                                <td title="<?php echo esc_attr($lead->user_message); ?>"><?php echo esc_html(wp_trim_words($lead->user_message, 30)); ?></td>
                                <td><?php echo wp_kses_post($product_info); ?></td>
                                <td>
                                    <?php if ($source === 'contact_form' && !empty($lead->page_title)): ?>
                                        <span class="naib-context-page"><?php echo esc_html($lead->page_title); ?></span>
                                    <?php elseif ($source === 'contact_form'): ?>
                                        <span class="naib-text-secondary">—</span>
                                    <?php elseif ($lead->product_id): ?>
                                        <?php $edit_link = get_edit_post_link($lead->product_id); ?>
                                        <?php if ($edit_link): ?>
                                            <a href="<?php echo esc_url($edit_link); ?>" target="_blank">
                                                <?php echo esc_html(get_the_title($lead->product_id)); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo esc_html(get_the_title($lead->product_id)); ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="naib-text-secondary">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($source === 'ai_chat'): ?>
                                        <button type="button" class="button view-chat-log" data-history='<?php echo esc_attr($lead->chat_history); ?>'>
                                            <?php esc_html_e('Chat Log', 'naibabiji-b2b-product-showcase'); ?>
                                        </button>
                                    <?php elseif ($inquiry_type === 'bulk'): ?>
                                        <button type="button" class="button button-primary view-lead-detail-ajax"
                                            data-lead-id="<?php echo esc_attr($lead->id); ?>">
                                            <?php esc_html_e('Detail', 'naibabiji-b2b-product-showcase'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="button button-primary view-lead-detail"
                                            data-contact="<?php echo esc_attr($lead->user_contact); ?>"
                                            data-message="<?php echo esc_attr($lead->user_message); ?>"
                                            data-source="<?php echo esc_attr($source); ?>"
                                            data-context="<?php echo esc_attr($source === 'contact_form' ? $lead->page_title : ($lead->product_id ? get_the_title($lead->product_id) : '')); ?>"
                                            data-date="<?php echo esc_attr($lead->created_at); ?>">
                                            <?php esc_html_e('Detail', 'naibabiji-b2b-product-showcase'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8"><?php esc_html_e('No inquiries found.', 'naibabiji-b2b-product-showcase'); ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div><!-- .naib-table-wrapper -->
        </div>

        <!-- Detail Modal -->
        <div id="naib-lead-detail-modal" style="display:none;">
            <div class="naib-modal-backdrop"></div>
            <div class="naib-modal-panel">
                <button type="button" class="naib-modal-close">&times;</button>
                <h2 id="naib-lead-detail-heading"></h2>
                <table class="naib-detail-table">
                    <tr>
                        <th><?php esc_html_e('Source', 'naibabiji-b2b-product-showcase'); ?></th>
                        <td id="naib-detail-source"></td>
                    </tr>
                    <tr>
                        <th id="naib-detail-context-label"><?php esc_html_e('Product', 'naibabiji-b2b-product-showcase'); ?></th>
                        <td id="naib-detail-context"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Contact Info', 'naibabiji-b2b-product-showcase'); ?></th>
                        <td id="naib-detail-contact"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Message', 'naibabiji-b2b-product-showcase'); ?></th>
                        <td id="naib-detail-message"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Date', 'naibabiji-b2b-product-showcase'); ?></th>
                        <td id="naib-detail-date"></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Lead detail nonce for AJAX loading -->
        <script>var naib_lead_detail_nonce = "<?php echo esc_js(wp_create_nonce('naib_lead_detail_nonce')); ?>";</script>

        <script>
            jQuery(document).ready(function($) {
                function escapeHtml(value) {
                    return $('<div>').text(value || '').html();
                }

                $('.view-chat-log').click(function() {
                    var history = $(this).data('history');
                    var logHtml = '<div style="background:#f1f1f1; padding:20px; border-radius:var(--naibabiji-b2b-border-radius); max-height:400px; overflow-y:auto;">';
                    if (history && history.length) {
                        history.forEach(function(msg) {
                            var role = msg.role === 'user' ? '<?php esc_html_e('Customer', 'naibabiji-b2b-product-showcase'); ?>' : '<?php esc_html_e('AI', 'naibabiji-b2b-product-showcase'); ?>';
                            logHtml += '<p><strong>' + role + ':</strong> ' + escapeHtml(msg.content) + '</p>';
                        });
                    } else {
                        logHtml += '<p><?php esc_html_e('No log available.', 'naibabiji-b2b-product-showcase'); ?></p>';
                    }
                    logHtml += '</div>';
                    var win = window.open('', 'ChatLog', 'width=600,height=500');
                    win.document.body.innerHTML = '<h2><?php esc_html_e('Full Chat Log', 'naibabiji-b2b-product-showcase'); ?></h2>' + logHtml;
                });

                $('.view-lead-detail').click(function() {
                    var btn = $(this);
                    var source = btn.data('source');
                    var sourceLabels = {
                        inquiry_form: '<?php esc_html_e('Inquiry Form', 'naibabiji-b2b-product-showcase'); ?>',
                        contact_form: '<?php esc_html_e('Contact Form', 'naibabiji-b2b-product-showcase'); ?>'
                    };
                    var contactRaw = btn.data('contact');
                    var contactHtml = escapeHtml(contactRaw).replace(/^(Name|Email|WhatsApp|Company|Country):\s*/gim, function(match, label) {
                        return '<strong>' + label + ':</strong> ';
                    }).replace(/\n/g, '<br>');
                    var contextLabel = source === 'contact_form'
                        ? '<?php esc_html_e('Page', 'naibabiji-b2b-product-showcase'); ?>'
                        : '<?php esc_html_e('Product', 'naibabiji-b2b-product-showcase'); ?>';

                    $('#naib-lead-detail-heading').text(sourceLabels[source] || 'Detail');
                    $('#naib-detail-source').text(sourceLabels[source] || source);
                    $('#naib-detail-context-label').text(contextLabel);
                    $('#naib-detail-context').text(btn.data('context') || '\u2014');
                    $('#naib-detail-contact').html(contactHtml);
                    $('#naib-detail-message').text(btn.data('message') || '');
                    $('#naib-detail-date').text(btn.data('date'));
                    $('#naib-lead-detail-modal').show();
                });

                $('.naib-modal-close, .naib-modal-backdrop').on('click', function() {
                    $('#naib-lead-detail-modal').hide();
                });
                $(document).on('keydown', function(e) {
                    if (e.key === 'Escape') $('#naib-lead-detail-modal').hide();
                });

                // Bulk inquiry detail — AJAX loaded
                $('.view-lead-detail-ajax').on('click', function() {
                    var leadId = $(this).data('lead-id');
                    $.post(ajaxurl, {
                        action: 'naib_get_lead_detail',
                        lead_id: leadId,
                        nonce: naib_lead_detail_nonce
                    }, function(response) {
                        if (response.success) {
                            var data = response.data;
                            var lead = data.lead;
                            var contactHtml = escapeHtml(lead.user_contact || '').replace(/^(Name|Email|WhatsApp|Company|Country):\s*/gim, function(match, label) {
                                return '<strong>' + label + ':</strong> ';
                            }).replace(/\n/g, '<br>');

                            var sourceLabels = {
                                inquiry_form: '<?php esc_html_e('Inquiry Form', 'naibabiji-b2b-product-showcase'); ?>',
                                contact_form: '<?php esc_html_e('Contact Form', 'naibabiji-b2b-product-showcase'); ?>',
                                ai_chat: '<?php esc_html_e('AI Chat', 'naibabiji-b2b-product-showcase'); ?>'
                            };
                            var source = lead.lead_source || 'ai_chat';

                            var productHtml = '';
                            if (data.inquiry_data && data.inquiry_data.products) {
                                productHtml = '<div style="background:#f6f7f7;padding:12px 14px;border-radius:var(--naibabiji-b2b-border-radius);margin-bottom:8px;">';
                                data.inquiry_data.products.forEach(function(p, i) {
                                    productHtml += '<p style="margin:0 0 8px;"><strong>' + (i + 1) + '. ' + $('<span>').text(p.product_name).html() + '</strong></p>';
                                    if (p.specs) {
                                        p.specs.forEach(function(s) {
                                            productHtml += '<p style="margin:0 0 4px 16px;font-size:13px;">- ' + $('<span>').text(s.code).html() + ' (' + $('<span>').text(s.description).html() + ') &times; ' + s.quantity + '</p>';
                                        });
                                    }
                                });
                                productHtml += '</div>';
                            }

                            $('#naib-lead-detail-heading').text('<?php esc_html_e('Bulk Inquiry Detail', 'naibabiji-b2b-product-showcase'); ?> #' + lead.id);
                            $('#naib-detail-source').text(sourceLabels[source] || source);
                            $('#naib-detail-context-label').text('<?php esc_html_e('Products', 'naibabiji-b2b-product-showcase'); ?>');
                            $('#naib-detail-context').html(productHtml);
                            $('#naib-detail-contact').html(contactHtml);
                            $('#naib-detail-message').text(lead.user_message || '');
                            $('#naib-detail-date').text(lead.created_at);
                            $('#naib-lead-detail-modal').show();
                        }
                    });
                });
            });
        </script>
        <?php
    }
}
