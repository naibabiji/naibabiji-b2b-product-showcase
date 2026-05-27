<?php
/**
 * AJAX处理器类
 * 
 * 处理插件的AJAX请求
 * 
 * @package Naibabiji_B2B_Product_Showcase
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product_Ajax_Handlers {
    
    /**
     * 单例实例
     */
    private static $instance = null;
    
    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 初始化AJAX处理器
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 管理员AJAX钩子
        add_action('wp_ajax_naibabiji_b2b_get_gallery_images', array($this, 'get_gallery_images'));
        add_action('wp_ajax_naibabiji_b2b_save_gallery_order', array($this, 'save_gallery_order'));
        add_action('wp_ajax_naibabiji_b2b_reset_settings', array($this, 'reset_settings'));
        add_action('wp_ajax_naibabiji_b2b_save_settings', array($this, 'save_settings'));
        
        // 前端AJAX钩子（登录用户）
        add_action('wp_ajax_naibabiji_b2b_load_more_products', array($this, 'load_more_products'));
        add_action('wp_ajax_naibabiji_b2b_filter_products', array($this, 'filter_products'));
        
        // 前端AJAX钩子（未登录用户）
        add_action('wp_ajax_nopriv_naibabiji_b2b_load_more_products', array($this, 'load_more_products'));
        add_action('wp_ajax_nopriv_naibabiji_b2b_filter_products', array($this, 'filter_products'));
    }
    
    /**
     * 获取图册图片信息
     */
     public function get_gallery_images() {
         // 验证nonce
         $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
         if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_gallery_nonce')) {
             wp_die(esc_html__('Security verification failed', 'naibabiji-b2b-product-showcase'));
         }
         
        // 检查权限
        if (!Naibabiji_B2B_Product_Security::can_edit_products()) {
            wp_die(esc_html__('Insufficient permissions', 'naibabiji-b2b-product-showcase'));
        }
        
        $ids = Naibabiji_B2B_Product_Security::get_post_data('ids', array(), 'sanitize_text_field', 'nonce', 'naibabiji_b2b_gallery_nonce');
        if (!is_array($ids)) {
            $ids = array();
        } else {
            $ids = array_map('intval', $ids);
        }
        
        if (empty($ids)) {
            wp_send_json_error(__('No image ID provided', 'naibabiji-b2b-product-showcase'));
        }
        
        $images = array();
        
        foreach ($ids as $id) {
            $attachment = get_post($id);
            
            if ($attachment && $attachment->post_type === 'attachment') {
                $images[] = array(
                    'id' => $id,
                    'title' => $attachment->post_title,
                    'alt' => get_post_meta($id, '_wp_attachment_image_alt', true),
                    'url' => wp_get_attachment_url($id),
                    'sizes' => array(
                        'thumbnail' => wp_get_attachment_image_src($id, 'thumbnail'),
                        'medium' => wp_get_attachment_image_src($id, 'medium'),
                        'large' => wp_get_attachment_image_src($id, 'large'),
                        'full' => wp_get_attachment_image_src($id, 'full'),
                    )
                );
            }
        }
        
        wp_send_json_success($images);
    }
    
    /**
     * 保存图册排序
     */
     public function save_gallery_order() {
         // 验证nonce
         $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
         if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_gallery_nonce')) {
             wp_die(esc_html__('Security verification failed', 'naibabiji-b2b-product-showcase'));
         }
         
        // 检查权限
        if (!Naibabiji_B2B_Product_Security::can_edit_products()) {
            wp_die(esc_html__('Insufficient permissions', 'naibabiji-b2b-product-showcase'));
        }
        
        $post_id = Naibabiji_B2B_Product_Security::get_post_data('post_id', 0, 'intval', 'nonce', 'naibabiji_b2b_gallery_nonce');
        $order = Naibabiji_B2B_Product_Security::get_post_data('order', array(), 'sanitize_text_field', 'nonce', 'naibabiji_b2b_gallery_nonce');
        
        if (!is_array($order)) {
            $order = array();
        } else {
            $order = array_map('intval', $order);
        }
        
        if (!$post_id || empty($order)) {
            wp_send_json_error(__('Invalid parameters', 'naibabiji-b2b-product-showcase'));
        }
        
        // 验证当前用户是否可以编辑此特定产品
        if (!Naibabiji_B2B_Product_Security::can_edit_products($post_id)) {
            wp_die(esc_html__('You do not have permission to edit this product', 'naibabiji-b2b-product-showcase'));
        }
        
        // 保存新的排序
        $gallery_ids = array_map('intval', $order);
        
        // 1. Update the flat key (for extra safety/backward compat)
        update_post_meta($post_id, '_naibabiji_b2b_product_gallery', $gallery_ids);
        
        // 2. Update the grouped meta (new standard)
        $product_data = get_post_meta($post_id, '_naibabiji_b2b_product_data', true);
        if (!is_array($product_data)) {
            $product_data = array();
        }
        $product_data['gallery'] = $gallery_ids;
        update_post_meta($post_id, '_naibabiji_b2b_product_data', $product_data);
        
        wp_send_json_success(__('Gallery order saved', 'naibabiji-b2b-product-showcase'));
    }
    
    /**
     * 重置设置
     */
     public function reset_settings() {
         // 验证nonce
         $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
         if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_reset_settings_nonce')) {
             wp_die(esc_html__('Security verification failed', 'naibabiji-b2b-product-showcase'));
         }
         
         // 检查权限
         if (!Naibabiji_B2B_Product_Security::can_manage_options()) {
             wp_die(esc_html__('Insufficient permissions', 'naibabiji-b2b-product-showcase'));
         }
         
        // 获取默认设置（与已注册的设置键名保持一致）
        $default_options = array(
            'naibabiji_b2b_product_products_per_page' => 12,
            'naibabiji_b2b_product_inquiry_button_text' => __('Get Quote', 'naibabiji-b2b-product-showcase'),
            'naibabiji_b2b_product_default_inquiry_url' => '',
            'naibabiji_b2b_product_enable_breadcrumbs' => 1,
            'naibabiji_b2b_product_enable_schema' => 1,
            'naibabiji_b2b_product_excerpt_length' => 20,
            'naibabiji_b2b_product_button_color' => '#0A7AFF',
            'naibabiji_b2b_product_button_hover_color' => '#085FCC',
        );
         
         // 重置所有设置
         foreach ($default_options as $option_name => $default_value) {
             update_option($option_name, $default_value);
         }
         
         wp_send_json_success(__('Settings reset to defaults', 'naibabiji-b2b-product-showcase'));
     }
     
     /**
      * 保存设置
      */
     public function save_settings() {
         // 验证nonce
         $nonce = Naibabiji_B2B_Product_Security::get_post_data('naibabiji_b2b_settings_nonce', '');
         if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_settings_nonce')) {
             wp_send_json_error(__('Security verification failed', 'naibabiji-b2b-product-showcase'));
             return;
         }
         
         // 检查权限
         if (!Naibabiji_B2B_Product_Security::can_manage_options()) {
             wp_send_json_error(__('Insufficient permissions', 'naibabiji-b2b-product-showcase'));
             return;
         }
         
         // 获取并验证设置
        $inquiry_button_text = Naibabiji_B2B_Product_Security::get_post_data('inquiry_button_text', '', 'sanitize_text_field', 'naibabiji_b2b_settings_nonce', 'naibabiji_b2b_settings_nonce');
        $default_inquiry_url = Naibabiji_B2B_Product_Security::get_post_data('default_inquiry_url', '', 'esc_url_raw', 'naibabiji_b2b_settings_nonce', 'naibabiji_b2b_settings_nonce');
         
         // 保存设置
         update_option('naibabiji_b2b_product_inquiry_button_text', $inquiry_button_text);
         update_option('naibabiji_b2b_product_default_inquiry_url', $default_inquiry_url);
         
         // 触发设置保存后的钩子
         do_action('naibabiji_b2b_settings_saved', array(
             'inquiry_button_text' => $inquiry_button_text,
             'default_inquiry_url' => $default_inquiry_url
         ));
         
         wp_send_json_success(__('Settings saved', 'naibabiji-b2b-product-showcase'));
     }
    
    /**
     * 加载更多产品
     */
     public function load_more_products() {
         // 验证nonce
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_product_nonce')) {
             wp_send_json_error(__('Security verification failed', 'naibabiji-b2b-product-showcase'));
             return;
         }
         
         // 验证和清理输入数据
         $page = Naibabiji_B2B_Product_Security::get_post_data('page', 1, 'intval', 'nonce', 'naibabiji_b2b_product_nonce');
         $page = Naibabiji_B2B_Product_Security::sanitize_int($page, 1, 100, 1);
          
         $posts_per_page = Naibabiji_B2B_Product_Security::get_post_data('posts_per_page', 12, 'intval', 'nonce', 'naibabiji_b2b_product_nonce');
         $posts_per_page = Naibabiji_B2B_Product_Security::sanitize_int($posts_per_page, 1, 50, 12);
          
         $category = Naibabiji_B2B_Product_Security::get_post_data('category', '', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
         $tag = Naibabiji_B2B_Product_Security::get_post_data('tag', '', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
         
         $args = array(
             'post_type' => 'naibb2pr_products',
             'post_status' => 'publish',
             'posts_per_page' => $posts_per_page,
             'paged' => $page,
         );
         
         // 添加分类筛选
         if (!empty($category)) {
             $args['tax_query'][] = array(
                 'taxonomy' => 'naibb2pr_product_category',
                 'field' => 'slug',
                 'terms' => $category,
             );
         }
         
         // 添加标签筛选
         if (!empty($tag)) {
             $args['tax_query'][] = array(
                 'taxonomy' => 'naibb2pr_product_tag',
                 'field' => 'slug',
                 'terms' => $tag,
             );
         }
         
         $query = new WP_Query($args);
         
         if ($query->have_posts()) {
             $html = '';
             
             while ($query->have_posts()) {
                 $query->the_post();
                 
                // 使用模板部分而不是调用不存在的方法
                ob_start();
                include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/content-product-card.php';
                $html .= ob_get_clean();
             }
             
             wp_reset_postdata();
             
             wp_send_json_success(array(
                 'html' => $html,
                 'has_more' => $page < $query->max_num_pages,
                 'current_page' => $page,
                 'max_pages' => $query->max_num_pages,
             ));
         } else {
            wp_send_json_error(__('No more products', 'naibabiji-b2b-product-showcase'));
         }
     }
     
     /**
      * 筛选产品
      */
     public function filter_products() {
         // 验证nonce
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_product_nonce')) {
             wp_send_json_error(array('message' => __('Security check failed', 'naibabiji-b2b-product-showcase')));
             return;
         }
         
         // 使用安全类获取筛选参数
         $category = Naibabiji_B2B_Product_Security::get_post_data('category', '', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
         $tag = Naibabiji_B2B_Product_Security::get_post_data('tag', '', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
         $search = Naibabiji_B2B_Product_Security::get_post_data('search', '', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
          
         // 验证排序参数
         $orderby_raw = Naibabiji_B2B_Product_Security::get_post_data('orderby', 'date', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
         $orderby = Naibabiji_B2B_Product_Security::sanitize_orderby($orderby_raw, array('date', 'title', 'menu_order', 'rand'));
          
         $order_raw = Naibabiji_B2B_Product_Security::get_post_data('order', 'DESC', 'sanitize_text_field', 'nonce', 'naibabiji_b2b_product_nonce');
         $order = Naibabiji_B2B_Product_Security::sanitize_order($order_raw);
          
         $per_page = Naibabiji_B2B_Product_Security::get_post_data('per_page', 10, 'intval', 'nonce', 'naibabiji_b2b_product_nonce');
         $posts_per_page = Naibabiji_B2B_Product_Security::sanitize_int($per_page, 1, 50, 10);
         
         $args = array(
             'post_type' => 'naibb2pr_products',
             'post_status' => 'publish',
             'posts_per_page' => $posts_per_page,
             'orderby' => $orderby,
             'order' => $order,
         );
         
         // 添加搜索
         if (!empty($search)) {
             $args['s'] = $search;
         }
         
         // 添加分类筛选
         if (!empty($category)) {
             $args['tax_query'][] = array(
                 'taxonomy' => 'naibb2pr_product_category',
                 'field' => 'slug',
                 'terms' => $category,
             );
         }
         
         // 添加标签筛选
         if (!empty($tag)) {
             $args['tax_query'][] = array(
                 'taxonomy' => 'naibb2pr_product_tag',
                 'field' => 'slug',
                 'terms' => $tag,
             );
         }
         
         $query = new WP_Query($args);
         
         if ($query->have_posts()) {
             $html = '';
             
             while ($query->have_posts()) {
                 $query->the_post();
                 
                // 使用模板部分而不是调用不存在的方法
                ob_start();
                include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/content-product-card.php';
                $html .= ob_get_clean();
             }
             
             wp_reset_postdata();
             
             wp_send_json_success(array(
                 'html' => $html,
                 'found_posts' => $query->found_posts,
                 'max_pages' => $query->max_num_pages,
             ));
         } else {
            wp_send_json_success(array(
                'html' => '<div class="naibabiji-b2b-no-products"><h3>' . __('No matching products found', 'naibabiji-b2b-product-showcase') . '</h3><p>' . __('Please try different filters.', 'naibabiji-b2b-product-showcase') . '</p></div>',
                 'found_posts' => 0,
                 'max_pages' => 0,
             ));
         }
     }
     
     /**
      * 获取产品统计信息
      */
     public function get_product_stats() {
         // 验证nonce
        $nonce = Naibabiji_B2B_Product_Security::get_post_data('nonce', '');
        if (!Naibabiji_B2B_Product_Security::verify_ajax_nonce($nonce, 'naibabiji_b2b_product_nonce')) {
             wp_send_json_error(array('message' => __('Security check failed', 'naibabiji-b2b-product-showcase')));
             return;
         }
         
         // 检查权限
         if (!Naibabiji_B2B_Product_Security::can_edit_products()) {
             wp_send_json_error(array('message' => __('You do not have permission to access this data', 'naibabiji-b2b-product-showcase')));
             return;
         }
         
         // 获取产品统计
         $total_products = wp_count_posts('naibb2pr_products');
         $published_products = $total_products->publish;
         $draft_products = $total_products->draft;
         
         // 获取分类统计
         $categories = get_terms(array(
             'taxonomy' => 'naibb2pr_product_category',
             'hide_empty' => false,
         ));
         $total_categories = count($categories);
         
         // 获取标签统计
         $tags = get_terms(array(
             'taxonomy' => 'naibb2pr_product_tag',
             'hide_empty' => false,
         ));
         $total_tags = count($tags);
         
         // 获取最近添加的产品
         $recent_products = get_posts(array(
             'post_type' => 'naibb2pr_products',
             'numberposts' => 5,
             'post_status' => 'publish',
         ));
         
         $recent_products_data = array();
         foreach ($recent_products as $product) {
             $recent_products_data[] = array(
                 'id' => $product->ID,
                 'title' => $product->post_title,
                 'date' => get_the_date('Y-m-d H:i', $product->ID),
                 'edit_link' => get_edit_post_link($product->ID),
             );
         }
         
         wp_send_json_success(array(
             'total_products' => $published_products,
             'draft_products' => $draft_products,
             'total_categories' => $total_categories,
             'total_tags' => $total_tags,
             'recent_products' => $recent_products_data,
         ));
     }
}