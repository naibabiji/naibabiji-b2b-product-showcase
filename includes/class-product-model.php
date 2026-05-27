<?php
/**
 * Product Model Class
 * 
 * Centralizes all product-specific data access and logic.
 * 
 * @package Naibabiji_B2B_Product_Showcase
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product {

    /**
     * @var int Post ID
     */
    private $id;

    /**
     * @var array Grouped product data
     */
    private $data = array();

    /**
     * Constructor
     * 
     * @param int|WP_Post $post
     */
    public function __construct($post) {
        $this->id = (is_a($post, 'WP_Post')) ? $post->ID : absint($post);
        $this->load_data();
    }

    /**
     * Load product data from meta
     */
    private function load_data() {
        // Try loading from grouped key first
        $grouped_data = get_post_meta($this->id, '_naibabiji_b2b_product_data', true);
        
        if (is_array($grouped_data)) {
            $this->data = $grouped_data;
            // Ensure gallery is an array even in grouped data
            if (isset($this->data['gallery'])) {
                $this->data['gallery'] = $this->maybe_convert_gallery_to_array($this->data['gallery']);
            }
        } else {
            // Backward compatibility: load from flat keys
            $this->data = array(
                'short_description' => get_post_meta($this->id, '_naibabiji_b2b_product_short_description', true),
                'gallery'           => $this->maybe_convert_gallery_to_array(get_post_meta($this->id, '_naibabiji_b2b_product_gallery', true)),
                'inquiry_url'       => get_post_meta($this->id, '_naibabiji_b2b_product_inquiry_url', true),
                'inquiry_text'      => get_post_meta($this->id, '_naibabiji_b2b_product_inquiry_text', true),
                'enable_inquiry'    => get_post_meta($this->id, '_naibabiji_b2b_product_enable_inquiry', true),
                'inquiry_type'      => get_post_meta($this->id, '_inquiry_type', true),
                'sku'               => get_post_meta($this->id, '_naibabiji_b2b_product_sku', true),
                'price'             => get_post_meta($this->id, '_naibabiji_b2b_product_price', true),
            );
        }
    }

    /**
     * Maybe convert gallery data to array
     * 
     * Handles cases where gallery is stored as a comma-separated string
     * 
     * @param mixed $gallery
     * @return array
     */
    private function maybe_convert_gallery_to_array($gallery) {
        if (is_array($gallery)) {
            return array_map('absint', $gallery);
        }
        
        if (is_string($gallery) && !empty($gallery)) {
            return array_map('absint', explode(',', $gallery));
        }
        
        return array();
    }

    /**
     * Get Product ID
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Get SKU
     */
    public function get_sku() {
        return !empty($this->data['sku']) ? $this->data['sku'] : 'SKU-' . $this->id;
    }

    /**
     * Get Price
     */
    public function get_price() {
        return !empty($this->data['price']) ? $this->data['price'] : '';
    }

    /**
     * Get Short Description
     */
    public function get_short_description() {
        $desc = !empty($this->data['short_description']) ? $this->data['short_description'] : '';
        if (empty($desc)) {
            $post = get_post($this->id);
            $desc = $post ? $post->post_excerpt : '';
        }
        return $desc;
    }

    /**
     * Get Gallery Images IDs
     */
    public function get_gallery_ids() {
        if (!isset($this->data['gallery'])) {
            return array();
        }
        return $this->maybe_convert_gallery_to_array($this->data['gallery']);
    }

    /**
     * Get Inquiry URL
     */
    public function get_inquiry_url() {
        $url = !empty($this->data['inquiry_url']) ? $this->data['inquiry_url'] : '';
        if (empty($url)) {
            $url = Naibabiji_B2B_Settings::get('default_inquiry_url', '');
        }
        return $url;
    }

    /**
     * Get Inquiry Button Text
     */
    public function get_inquiry_text() {
        $text = !empty($this->data['inquiry_text']) ? $this->data['inquiry_text'] : '';
        if (empty($text)) {
            $text = Naibabiji_B2B_Settings::get('inquiry_button_text', __('Get Quote', 'naibabiji-b2b-product-showcase'));
        }
        return $text;
    }

    /**
     * Check if inquiry is enabled for this product
     */
    public function is_inquiry_enabled() {
        // Default to enabled if not set or legacy
        if (!isset($this->data['enable_inquiry']) || $this->data['enable_inquiry'] === '') {
            return true;
        }
        return (bool) $this->data['enable_inquiry'];
    }

    /**
     * Get inquiry type for this product
     * @return string 'none' | 'standard' | 'bulk'
     */
    public function get_inquiry_type() {
        $type = isset($this->data['inquiry_type']) ? $this->data['inquiry_type'] : '';
        if ($type === '') {
            // Backward compat: read old enable_inquiry field
            $old = isset($this->data['enable_inquiry']) ? $this->data['enable_inquiry'] : '';
            if ($old === '0') {
                return 'none';
            }
            if ($old === '') {
                // New product with no saved value — use global default
                return Naibabiji_B2B_Settings::get('default_inquiry_type', 'standard');
            }
            // Old product with enable_inquiry=1 → standard
            return 'standard';
        }
        return $type;
    }

    /**
     * Get bulk inquiry specs (only for bulk-type products)
     * @return array
     */
    public function get_bulk_specs() {
        $specs = get_post_meta($this->id, '_bulk_inquiry_specs', true);
        if (is_string($specs)) {
            $decoded = json_decode($specs, true);
            return is_array($decoded) ? $decoded : array();
        }
        return is_array($specs) ? $specs : array();
    }

    /**
     * Get all data (for debugging or migration)
     */
    public function get_raw_data() {
        return $this->data;
    }
}
