<?php
/**
 * Settings Helper Class
 * 
 * Centralizes all get_option calls with proper defaults.
 * 
 * @package Naibabiji_B2B_Product_Showcase
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Settings {

    /**
     * Get a plugin option with a default value.
     * 
     * @param string $key The option key (without the plugin prefix).
     * @param mixed $default Optional. Default value if option is not set.
     * @return mixed
     */
    public static function get($key, $default = null) {
        $option_name = 'naibabiji_b2b_product_' . $key;
        
        // Define internal defaults if not provided
        if (null === $default) {
            $default = self::get_default($key);
        }

        $value = get_option($option_name, $default);

        // Auto-append px to border-radius if it's just a number
        if ('border_radius' === $key && is_numeric($value)) {
            $value .= 'px';
        }

        // Special handling for numeric values that might be stored as strings
        if (in_array($key, array('thumbnail_width', 'thumbnail_height', 'products_per_page', 'limit'))) {
            return absint($value);
        }

        return $value;
    }

    /**
     * Get default value for a specific setting.
     * 
     * @param string $key
     * @return mixed
     */
    public static function get_default($key) {
        $defaults = array(
            'button_color'             => '#0A7AFF',
            'button_hover_color'       => '#085FCC',
            'border_radius'            => '8px',
            'content_width'            => '1200px',
            'thumbnail_width'          => 300,
            'thumbnail_height'         => 300,
            'inquiry_button_text'      => __('Get Quote', 'naibabiji-b2b-product-showcase'),
            'products_per_page'        => 12,
            'enable_gallery'           => 1,
            'enable_inquiry_button'    => 1,
            'enable_short_description' => 1,
            'default_inquiry_url'      => '',
            'schema_currency'          => 'USD',
            'excerpt_length'           => 20,
            'archive_columns'          => 3,
            'archive_display_mode'     => 'default',
            'default_product_sort'     => 'date-desc',
            'archive_hide_title'       => false,
            'archive_seo_content_top'  => '',
            'archive_enable_seo_top'   => true,
            'archive_seo_content_bottom' => '',
            'archive_enable_seo_bottom' => true,
            'default_inquiry_type'        => 'standard',
            'inquiry_redirect_enabled'   => false,
            'inquiry_redirect_url'       => '',
        );

        return isset($defaults[$key]) ? $defaults[$key] : '';
    }
}
