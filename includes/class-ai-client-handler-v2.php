<?php
/**
 * AI Client Handler for B2B Product Showcase (SPEC v2 Aligned)
 */

namespace Naibabiji\B2B\Ai;

if (!defined('ABSPATH')) {
    exit;
}

class AiClientHandler {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_ajax_naib_ai_chat', [$this, 'handle_chat_ajax']);
        add_action('wp_ajax_nopriv_naib_ai_chat', [$this, 'handle_chat_ajax']);
        add_action('wp_ajax_naib_ai_get_nonce', [$this, 'handle_get_nonce_ajax']);
        add_action('wp_ajax_nopriv_naib_ai_get_nonce', [$this, 'handle_get_nonce_ajax']);
        add_action('wp_ajax_naib_ai_verify_license', [$this, 'handle_verify_ajax']);
        add_action('wp_ajax_naib_ai_unbind_license', [$this, 'handle_unbind_ajax']);
    }

    /**
     * Handle AJAX request for license verification from admin
     */
    public function handle_verify_ajax() {
        check_ajax_referer('naibabiji_b2b_admin_verify_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Forbidden']);
        }

        $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
        $request_id = wp_generate_uuid4();
        $params = [
            'site_url' => get_site_url()
        ];
        $response   = $this->call_manager_v2($params, '/v1/verify', $request_id, $license_key);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
                'error'   => ['code' => $response->get_error_code()]
            ]);
        }

        wp_send_json_success($response);
    }

    /**
     * Handle Manual Unbind (Deactivation)
     */
    public function handle_unbind_ajax() {
        check_ajax_referer('naibabiji_b2b_admin_verify_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $license_key = get_option('naibabiji_b2b_ai_license_key', '');
        if (empty($license_key)) {
            wp_send_json_error(['message' => 'No license found to unbind']);
        }

        $request_id = wp_generate_uuid4();
        $params = ['site_url' => get_site_url()];
        $response = $this->call_manager_v2($params, '/v1/unbind', $request_id, $license_key);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => $response->get_error_message(),
                'error'   => ['code' => $response->get_error_code()]
            ]);
        }

        wp_send_json_success($response);
    }

    /**
     * Handle AJAX request to get a fresh nonce for frontend use.
     * This endpoint bypasses page cache issues since it's a POST to admin-ajax.php.
     */
    public function handle_get_nonce_ajax() {
        // Use a session-based approach: WordPress creates nonce based on current user session,
        // so each visitor gets their own valid nonce without needing page re-render.
        wp_send_json_success([
            'nonce'    => wp_create_nonce('naibabiji_b2b_product_nonce'),
            'timestamp' => time(),
        ]);
    }

    /**
     * Handle AJAX request from the frontend chat widget
     */
    public function handle_chat_ajax() {
        check_ajax_referer('naibabiji_b2b_product_nonce', 'nonce');

        $message     = isset($_POST['message']) ? sanitize_text_field(wp_unslash($_POST['message'])) : '';
        $post_id     = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $request_id  = isset($_POST['request_id']) ? sanitize_text_field(wp_unslash($_POST['request_id'])) : '';
        $history_raw = isset($_POST['history']) ? map_deep(wp_unslash($_POST['history']), 'sanitize_text_field') : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- History is further sanitized and length-limited in sanitize_history().
        if (!is_array($history_raw)) {
            $history_raw = [];
        }
        $history    = $this->sanitize_history($history_raw);


        if (empty($message) || empty($request_id) || !$this->is_valid_uuid($request_id)) {
            wp_send_json_error(['message' => __('Invalid request', 'naibabiji-b2b-product-showcase')]);
        }

        // 1. Build SPEC v2 Payload
        $payload = [
            'request_id' => $request_id,
            'context'    => $this->build_context_v2($post_id),
            'messages'   => $this->prepare_messages($message, $history)
        ];

        // 2. Call Manager with SPEC v2 HMAC
        $response = $this->call_manager_v2($payload, '/v1/chat', $request_id);

        if (is_wp_error($response)) {
            $error_code = (string) $response->get_error_code();
            if ($error_code === 'rate_limited' || $error_code === 'api_error_429') {
                wp_send_json_success([
                    'status'  => 'fallback',
                    'message' => __('Our AI service is busy right now. Please leave your contact details and we will get back to you soon.', 'naibabiji-b2b-product-showcase'),
                    'action'  => 'contact_form',
                ]);
            }
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        wp_send_json_success($response);
    }

    private function build_context_v2($post_id) {
        if (!$post_id) {
            return ['general' => 'Browsing catalog'];
        }

        $post = get_post($post_id);
        if (!$post) return [];

        $product = new \Naibabiji_B2B_Product($post_id);
        
        $categories = get_the_terms($post_id, 'naibb2pr_product_category');
        $cat_names  = ($categories && !is_wp_error($categories)) ? wp_list_pluck($categories, 'name') : [];

        return [
            'product_id'          => (string)$post_id,
            'product_title'       => $post->post_title,
            'product_description' => wp_strip_all_tags($product->get_short_description()),
            'product_full_details'=> mb_substr(wp_strip_all_tags(apply_filters('naibabiji_b2b_product_the_content', $post->post_content)), 0, 1000),
            'product_sku'         => $product->get_sku(),
            'product_price'       => $product->get_price(),
            'product_currency'    => get_option('naibabiji_b2b_product_schema_currency', 'USD'),
            'product_categories'  => implode(', ', $cat_names),
            'service_profile'     => get_option('naibabiji_b2b_ai_service_profile', ''),
            'faqs'                => get_option('naibabiji_b2b_ai_faqs', ''),
            'language'            => get_locale()
        ];
    }

    private function prepare_messages($query, $history) {
        $messages = [];
        foreach ($history as $h) {
            $messages[] = [
                'role'    => isset($h['role']) ? $h['role'] : '',
                'content' => isset($h['content']) ? $h['content'] : ''
            ];
        }
        $messages[] = ['role' => 'user', 'content' => $query];
        return $messages;
    }

    private function sanitize_history($history_raw) {
        if (!is_array($history_raw)) {
            return [];
        }

        $history = [];
        foreach ($history_raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $role = isset($item['role']) ? sanitize_text_field($item['role']) : '';
            $content = isset($item['content']) ? sanitize_textarea_field($item['content']) : '';
            if ($role === '' || $content === '') {
                continue;
            }
            if (!in_array($role, ['user', 'assistant', 'system'], true)) {
                $role = 'user';
            }
            $history[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, 5000),
            ];
        }

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        return $history;
    }

    private function is_valid_uuid($value) {
        return (bool) preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i', $value);
    }

    /**
     * Get the real visitor IP from CDN headers.
     */
    private function get_visitor_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_CONNECTING_IP']));
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            return sanitize_text_field(trim($ips[0]));
        }
        return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
    }

    private function call_manager_v2($body, $path = '/v1/chat', $request_id = '', $license_key_override = '') {
        $license_key = !empty($license_key_override) ? $license_key_override : get_option('naibabiji_b2b_ai_license_key');
        $manager_url = get_option('naibabiji_b2b_ai_manager_url');

        if (empty($license_key) || empty($manager_url)) {
            return new \WP_Error('config_missing', 'AI config missing');
        }

        $url       = trailingslashit($manager_url) . 'wp-json/naibb-ai-manager' . $path;
        $timestamp = (string)time();
        $nonce     = wp_generate_password(12, false);
        $raw_body  = json_encode($body ?: (object)[]); 

        // SPEC v2 Signature logic
        $body_sha256 = hash('sha256', $raw_body);
        $string_to_sign = "POST\n{$path}\n{$timestamp}\n{$nonce}\n{$body_sha256}";
        $signature = hash_hmac('sha256', $string_to_sign, $license_key);

        $response = wp_remote_post($url, [
            'headers' => [
                'Content-Type'    => 'application/json',
                'X-License-Key'   => $license_key,
                'X-Timestamp'     => $timestamp,
                'X-Nonce'         => $nonce,
                'X-Signature'     => $signature,
                'X-Request-Id'    => $request_id,
                'X-Client-IP'     => $this->get_visitor_ip(),
            ],
            'body'    => $raw_body,
            'timeout' => 30,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code < 200 || $code >= 300) {
            $err_msg  = $body['error']['message'] ?? 'Manager error (' . $code . ')';
            $err_code = $body['error']['code'] ?? 'api_error_' . $code;
            return new \WP_Error($err_code, $err_msg);
        }

        return $body;
    }
}
