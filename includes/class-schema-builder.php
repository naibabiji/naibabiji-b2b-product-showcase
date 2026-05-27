<?php
/**
 * Schema Builder Class
 * 
 * Handles generation of JSON-LD structured data for products.
 * 
 * @package Naibabiji_B2B_Product_Showcase
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Schema_Builder {

    /**
     * Build the full Product schema for a given post.
     * 
     * @param int|WP_Post $post_id
     * @return array
     */
    public static function build_product_schema($post_id) {
        $post = get_post($post_id);
        if (!$post) return array();

        $product = new Naibabiji_B2B_Product($post);

        $schema = array(
            '@context' => 'https://schema.org/',
            '@type' => 'Product',
            'name' => esc_html($post->post_title),
            'image' => get_the_post_thumbnail_url($post->ID, 'full'),
            'description' => wp_strip_all_tags($product->get_short_description()),
            'sku' => esc_attr($product->get_sku()),
        );

        $price = $product->get_price();
        if (!empty($price)) {
            $schema['offers'] = self::build_offer_schema($product);
        }

        return apply_filters('naibabiji_b2b_product_schema', $schema, $post->ID);
    }

    /**
     * Build the Offer schema for a product.
     * 
     * @param Naibabiji_B2B_Product $product
     * @return array
     */
    public static function build_offer_schema($product) {
        $currency = Naibabiji_B2B_Settings::get('schema_currency', 'USD');

        $offer = array(
            '@type' => 'Offer',
            'url' => esc_url($product->get_inquiry_url()),
            'availability' => 'https://schema.org/InStock',
            'price' => esc_attr($product->get_price()),
            'priceCurrency' => esc_attr($currency),
        );

        return apply_filters('naibabiji_b2b_product_offer_schema', $offer, $product->get_id());
    }
}
