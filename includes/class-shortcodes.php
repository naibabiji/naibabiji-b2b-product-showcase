<?php
/**
 * 短代码管理类
 * 
 * 提供[naibabiji_b2b_products]短代码用于在任意页面显示产品网格
 * 
 * @package Naibabiji_B2B_Product_Showcase
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product_Shortcodes {
    
    private static $instance = null;

    /**
     * Default shortcode attributes
     * 
     * @var array
     */
    private $defaults = array(
        'limit'             => 8,
        'columns'           => 3,
        'category'          => '',
        'show_excerpt'      => 'true',
        'show_category'     => 'true',
        'show_view_details' => 'true',
        'show_inquiry'      => 'true',
        'orderby'           => 'date',
        'order'             => 'DESC',
    );
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // 直接注册短代码，不需要等待init钩子
        $this->register_shortcodes();
    }
    
    /**
     * 注册短代码
     * 使用WordPress核心功能add_shortcode()
     */
    public function register_shortcodes() {
        add_shortcode('naibabiji_b2b_products', array($this, 'products_shortcode'));
        add_shortcode('naibabiji_b2b_product_categories', array($this, 'product_categories_shortcode'));
    }
    
    /**
     * 产品展示短代码
     * 
     * 用法示例:
     * [naibabiji_b2b_products]
     * [naibabiji_b2b_products limit="6" columns="2"]
     * [naibabiji_b2b_products category="electronics" limit="8" columns="4"]
     * [naibabiji_b2b_products show_excerpt="true" show_category="true" show_view_details="true" show_inquiry="true"]
     */
    public function products_shortcode($atts, $content = null) {
        $atts = shortcode_atts($this->defaults, $atts, 'naibabiji_b2b_products');
        
        // 转换字符串为布尔值
        $show_excerpt = filter_var($atts['show_excerpt'], FILTER_VALIDATE_BOOLEAN);
        $show_category = filter_var($atts['show_category'], FILTER_VALIDATE_BOOLEAN);
        $show_view_details = filter_var($atts['show_view_details'], FILTER_VALIDATE_BOOLEAN);
        $show_inquiry = filter_var($atts['show_inquiry'], FILTER_VALIDATE_BOOLEAN);
        
        // 验证和清理参数
        $limit = absint($atts['limit']);
        $columns = absint($atts['columns']);
        $columns = max(1, min(6, $columns)); // 限制列数在1-6之间
        
        $args = array(
            'posts_per_page' => $limit,
            'columns' => $columns,
            'category' => sanitize_text_field($atts['category']),
            'show_excerpt' => $show_excerpt,
            'show_category' => $show_category,
            'show_view_details' => $show_view_details,
            'show_inquiry' => $show_inquiry,
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
        );
        
        return Naibabiji_B2B_Product_Frontend_Display::get_products_grid_html($args);
    }
    
    /**
     * 产品分类展示短代码
     * 
     * 用法示例:
     * [naibabiji_b2b_product_categories]
     * [naibabiji_b2b_product_categories limit="6" columns="3"]
     * [naibabiji_b2b_product_categories parent="0"] // 只显示顶级分类
     */
    public function product_categories_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'limit' => 0, // 0表示显示所有
            'columns' => 3,
            'parent' => '', // 父分类ID，空表示所有分类
            'hide_empty' => 'true',
            'show_count' => 'true',
        ), $atts, 'naibabiji_b2b_product_categories');
        
        // 转换字符串为布尔值
        $hide_empty = filter_var($atts['hide_empty'], FILTER_VALIDATE_BOOLEAN);
        $show_count = filter_var($atts['show_count'], FILTER_VALIDATE_BOOLEAN);
        
        // 验证和清理参数
        $limit = absint($atts['limit']);
        $columns = absint($atts['columns']);
        $columns = max(1, min(6, $columns)); // 限制列数在1-6之间
        
        $args = array(
            'taxonomy' => 'naibb2pr_product_category',
            'hide_empty' => $hide_empty,
            'number' => $limit > 0 ? $limit : '',
        );
        
        if (!empty($atts['parent'])) {
            $args['parent'] = absint($atts['parent']);
        }
        
        $categories = get_terms($args);
        
        if (empty($categories) || is_wp_error($categories)) {
            return '<p class="naibabiji-b2b-no-categories">' . __('No product categories available.', 'naibabiji-b2b-product-showcase') . '</p>';
        }
        
        $output = '<div class="naibabiji-b2b-product-categories naibabiji-b2b-columns-' . esc_attr($columns) . '">';
        
        foreach ($categories as $category) {
            $output .= $this->get_category_card_html($category, $show_count);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 获取分类卡片HTML
     */
     private function get_category_card_html($category, $show_count = true) {
         $output = '<div class="naibabiji-b2b-category-card">';
         
         // 分类缩略图（如果有的话）
         $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
         if ($thumbnail_id) {
             $term_link = get_term_link($category);
             if (is_wp_error($term_link)) {
                 $term_link = '';
             }
             $output .= '<div class="naibabiji-b2b-category-thumbnail">';
             if ($term_link) {
                 $output .= '<a href="' . esc_url($term_link) . '">';
             }
             $output .= wp_get_attachment_image($thumbnail_id, 'medium');
             if ($term_link) {
                 $output .= '</a>';
             }
             $output .= '</div>';
         }
         
         $output .= '<div class="naibabiji-b2b-category-info">';
         
         // 分类名称
         $output .= '<h3 class="naibabiji-b2b-category-title">';
         $term_link = isset($term_link) ? $term_link : get_term_link($category);
         if (!is_wp_error($term_link) && $term_link) {
             $output .= '<a href="' . esc_url($term_link) . '">' . esc_html($category->name) . '</a>';
         } else {
             $output .= esc_html($category->name);
         }
         $output .= '</h3>';
         
         // 分类描述
         if (!empty($category->description)) {
             $output .= '<div class="naibabiji-b2b-category-description">';
             $output .= '<p>' . esc_html($category->description) . '</p>';
             $output .= '</div>';
         }
         
         // 产品数量
         if ($show_count) {
             $output .= '<div class="naibabiji-b2b-category-count">';
             // translators: %d: Number of products in the category
             $output .= sprintf(__('Total %d products', 'naibabiji-b2b-product-showcase'), $category->count);
             $output .= '</div>';
         }
         
         $output .= '</div>'; // .naibabiji-b2b-category-info
         $output .= '</div>'; // .naibabiji-b2b-category-card
         
         return $output;
     }
}