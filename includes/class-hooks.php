<?php
/**
 * 钩子管理类
 * 
 * 提供插件的动作钩子和过滤器钩子，供开发者扩展功能
 * 
 * @package Naibabiji_B2B_Product_Showcase
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product_Hooks {
    
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
     * 初始化钩子
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * 初始化钩子
     */
    private function init_hooks() {
        // 产品相关钩子
        add_action('naibabiji_b2b_before_product_content', array($this, 'before_product_content'));
        add_action('naibabiji_b2b_after_product_content', array($this, 'after_product_content'));
        add_action('naibabiji_b2b_product_meta_saved', array($this, 'product_meta_saved'), 10, 2);
        
        // 查询相关钩子
        add_filter('naibabiji_b2b_product_query_args', array($this, 'modify_product_query_args'));
        add_filter('naibabiji_b2b_product_card_html', array($this, 'modify_product_card_html'), 10, 2);
        
        // 设置相关钩子
        add_filter('naibabiji_b2b_default_settings', array($this, 'modify_default_settings'));
        add_action('naibabiji_b2b_settings_saved', array($this, 'settings_saved'));
        
        // 模板相关钩子
        add_filter('naibabiji_b2b_template_path', array($this, 'modify_template_path'), 10, 2);
        add_action('naibabiji_b2b_before_template_load', array($this, 'before_template_load'));
        add_action('naibabiji_b2b_after_template_load', array($this, 'after_template_load'));
        
        // 搜索相关钩子
        add_action('pre_get_posts', array($this, 'include_products_in_search'));

        // 产品排序钩子
        add_action('pre_get_posts', array($this, 'handle_product_sorting'));
        
        // 摘要长度过滤器 - 控制搜索结果页面的摘要长度
        add_filter('excerpt_length', array($this, 'custom_excerpt_length'), 999);
        
        // 检测并处理特定主题的摘要长度
        $this->handle_theme_specific_excerpt();
    }
    
    /**
     * 产品内容前的钩子
     * 
     * @param int $product_id 产品ID
     */
    public function before_product_content($product_id) {
        /**
         * 在产品内容显示前执行
         * 
         * @param int $product_id 产品ID
         */
        do_action('naibabiji_b2b_product_content_start', $product_id);
    }
    
    /**
     * 产品内容后的钩子
     * 
     * @param int $product_id 产品ID
     */
    public function after_product_content($product_id) {
        /**
         * 在产品内容显示后执行
         * 
         * @param int $product_id 产品ID
         */
        do_action('naibabiji_b2b_product_content_end', $product_id);
    }
    
    /**
     * 产品元数据保存后的钩子
     * 
     * @param int $post_id 文章ID
     * @param array $meta_data 元数据
     */
    public function product_meta_saved($post_id, $meta_data) {
        // 可以在这里添加自定义逻辑
        // 例如：清除缓存、发送通知等
        
        /**
         * 产品元数据保存后执行
         * 
         * @param int $post_id 文章ID
         * @param array $meta_data 保存的元数据
         */
        do_action('naibabiji_b2b_after_product_meta_saved', $post_id, $meta_data);
    }
    
    /**
     * 修改产品查询参数
     * 
     * @param array $args 查询参数
     * @return array 修改后的查询参数
     */
    public function modify_product_query_args($args) {
        /**
         * 过滤产品查询参数
         * 
         * @param array $args 查询参数
         */
        return apply_filters('naibabiji_b2b_filter_product_query_args', $args);
    }
    
    /**
     * 修改产品卡片HTML
     * 
     * @param string $html 产品卡片HTML
     * @param int $product_id 产品ID
     * @return string 修改后的HTML
     */
    public function modify_product_card_html($html, $product_id) {
        /**
         * 过滤产品卡片HTML
         * 
         * @param string $html 产品卡片HTML
         * @param int $product_id 产品ID
         */
        return apply_filters('naibabiji_b2b_filter_product_card_html', $html, $product_id);
    }
    
    /**
     * 修改默认设置
     * 
     * @param array $settings 默认设置
     * @return array 修改后的设置
     */
    public function modify_default_settings($settings) {
        /**
         * 过滤默认设置
         * 
         * @param array $settings 默认设置数组
         */
        return apply_filters('naibabiji_b2b_filter_default_settings', $settings);
    }
    
    /**
     * 设置保存后的钩子
     * 
     * @param array $settings 保存的设置
     */
    public function settings_saved($settings) {
        // 清除相关缓存
        $this->clear_plugin_cache($settings);
        
        /**
         * 设置保存后执行
         * 
         * @param array $settings 保存的设置
         */
        do_action('naibabiji_b2b_after_settings_saved', $settings);
    }
    
    /**
     * 修改模板路径
     * 
     * @param string $template_path 模板路径
     * @param string $template_name 模板名称
     * @return string 修改后的模板路径
     */
    public function modify_template_path($template_path, $template_name) {
        /**
         * 过滤模板路径
         * 
         * @param string $template_path 模板路径
         * @param string $template_name 模板名称
         */
        return apply_filters('naibabiji_b2b_filter_template_path', $template_path, $template_name);
    }
    
    /**
     * 模板加载前的钩子
     * 
     * @param string $template_name 模板名称
     */
    public function before_template_load($template_name) {
        /**
         * 模板加载前执行
         * 
         * @param string $template_name 模板名称
         */
        do_action('naibabiji_b2b_template_load_start', $template_name);
    }
    
    /**
     * 模板加载后的钩子
     * 
     * @param string $template_name 模板名称
     */
    public function after_template_load($template_name) {
        /**
         * 模板加载后执行
         * 
         * @param string $template_name 模板名称
         */
        do_action('naibabiji_b2b_template_load_end', $template_name);
    }
    
    /**
     * 清除插件缓存
     */
    private function clear_plugin_cache($settings) {
        $should_flush_object_cache = apply_filters('naibabiji_b2b_flush_object_cache', false, $settings);
        if ($should_flush_object_cache && function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }

        $should_flush_rewrite = apply_filters('naibabiji_b2b_flush_rewrite_rules', false, $settings);
        if ($should_flush_rewrite) {
            flush_rewrite_rules(false);
        }

        /**
         * Allow third-party integrations to clear their caches when settings are saved.
         *
         * @since 1.1.0
         * @param array $settings Saved settings.
         */
        do_action('naibabiji_b2b_clear_external_cache', $settings);
    }
    
    /**
     * 获取所有可用的钩子列表
     * 
     * @return array 钩子列表
     */
     public static function get_available_hooks() {
         return array(
             'actions' => array(
                 'naibabiji_b2b_before_product_content' => __('Before product content', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_after_product_content' => __('After product content', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_product_content_start' => __('Product content start', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_product_content_end' => __('Product content end', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_product_meta_saved' => __('After product meta saved', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_after_product_meta_saved' => __('After product meta save completed', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_settings_saved' => __('After settings saved', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_after_settings_saved' => __('After settings save completed', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_before_template_load' => __('Before template load', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_template_load_start' => __('Template load start', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_after_template_load' => __('After template load', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_template_load_end' => __('Template load end', 'naibabiji-b2b-product-showcase'),
             ),
             'filters' => array(
                 'naibabiji_b2b_product_query_args' => __('Product query arguments', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_filter_product_query_args' => __('Filter product query arguments', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_product_card_html' => __('Product card HTML', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_filter_product_card_html' => __('Filter product card HTML', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_default_settings' => __('Default settings', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_filter_default_settings' => __('Filter default settings', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_template_path' => __('Template path', 'naibabiji-b2b-product-showcase'),
                 'naibabiji_b2b_filter_template_path' => __('Filter template path', 'naibabiji-b2b-product-showcase'),
             ),
         );
     }
    
    /**
     * 注册自定义钩子
     * 
     * @param string $hook_name 钩子名称
     * @param callable $callback 回调函数
     * @param int $priority 优先级
     * @param int $accepted_args 接受的参数数量
     */
     public static function register_custom_hook($hook_name, $callback, $priority = 10, $accepted_args = 1) {
         if (strpos($hook_name, 'naibabiji_b2b_') === 0) {
             add_action($hook_name, $callback, $priority, $accepted_args);
         }
     }
     
     /**
      * 注册自定义过滤器
      * 
      * @param string $filter_name 过滤器名称
      * @param callable $callback 回调函数
      * @param int $priority 优先级
      * @param int $accepted_args 接受的参数数量
      */
     public static function register_custom_filter($filter_name, $callback, $priority = 10, $accepted_args = 1) {
         if (strpos($filter_name, 'naibabiji_b2b_') === 0) {
             add_filter($filter_name, $callback, $priority, $accepted_args);
         }
     }
     
    /**
     * 将产品包含在WordPress默认搜索结果中
     * 
     * @param WP_Query $query 查询对象
     */
    public function include_products_in_search($query) {
        // 只修改前台的搜索查询
        if ($query->is_search() && !is_admin()) {
            // 获取当前搜索的文章类型
            $post_types = $query->get('post_type');
            
            // 如果post_types为空或者是'post'，则添加'naibb2pr_products'
            if (empty($post_types)) {
                $post_types = array('post', 'naibb2pr_products');
            } elseif (is_string($post_types) && $post_types === 'post') {
                $post_types = array('post', 'naibb2pr_products');
            } elseif (is_array($post_types) && !in_array('naibb2pr_products', $post_types)) {
                $post_types[] = 'naibb2pr_products';
            }
            
            // 设置查询的文章类型
            $query->set('post_type', $post_types);
        }
    }

    /**
     * Handle product sorting on archive, category, and tag pages
     *
     * Applies the admin-configured default sort order to the main query
     *
     * @param WP_Query $query
     */
    public function handle_product_sorting($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if (!is_post_type_archive('naibb2pr_products')
            && !is_tax('naibb2pr_product_category')
            && !is_tax('naibb2pr_product_tag')) {
            return;
        }

        // In categories-only mode, minimize the product query on the main archive page since products aren't displayed
        if (is_post_type_archive('naibb2pr_products')) {
            $display_mode = get_option('naibabiji_b2b_product_archive_display_mode', 'default');
            if ($display_mode === 'categories_only') {
                $query->set('posts_per_page', 1);
                return;
            }
        }

        $sort = get_option('naibabiji_b2b_product_default_product_sort', 'date-desc');
        $parts = explode('-', $sort);
        $orderby = isset($parts[0]) ? $parts[0] : 'date';
        $order   = isset($parts[1]) ? strtoupper($parts[1]) : 'DESC';

        $allowed_orderby = array('date', 'title');
        if (!in_array($orderby, $allowed_orderby, true)) {
            $orderby = 'date';
        }
        if (!in_array($order, array('ASC', 'DESC'), true)) {
            $order = 'DESC';
        }

        $query->set('orderby', $orderby);
        $query->set('order', $order);
    }

    /**
     * 自定义摘要长度
     * 
     * 此函数通过WordPress的excerpt_length过滤器控制摘要长度
     * 优先级设置为999，确保它会覆盖主题中的任何其他摘要长度设置
     * 特别处理了Blocksy和Astra主题，它们使用自定义方式处理摘要长度
     * 
     * @param int $length 默认摘要长度
     * @return int 自定义摘要长度
     */
    public function custom_excerpt_length($length) {
        // 获取插件设置中的摘要长度，默认为20个单词
        $custom_length = get_option('naibabiji_b2b_product_excerpt_length', 20);
        
        // 确保摘要长度是一个有效的正整数
        $custom_length = absint($custom_length);
        if ($custom_length < 1) {
            $custom_length = 20; // 如果设置无效，使用默认值20
        }
        
        return $custom_length;
    }
    
    /**
     * 处理特定主题的摘要长度设置
     * 
     * 某些主题（如Blocksy）使用自定义方式处理摘要长度，
     * 这个方法专门处理这些主题，确保我们的摘要长度设置能够生效
     */
    private function handle_theme_specific_excerpt() {
        // 获取插件设置中的摘要长度
        $custom_length = get_option('naibabiji_b2b_product_excerpt_length', 20);
        $custom_length = absint($custom_length);
        if ($custom_length < 1) {
            $custom_length = 20;
        }
        
        $theme = wp_get_theme();
        $theme_name = $theme->get('Name');
        
        // 处理Blocksy主题
        if (strpos(strtolower($theme_name), 'blocksy') !== false) {
            // Blocksy主题在excerpt.php中定义了blocksy_excerpt_length函数，硬编码返回300
            // 我们需要直接覆盖这个函数
            if (function_exists('blocksy_excerpt_length')) {
                // 移除原有的函数
                remove_filter('excerpt_length', 'blocksy_excerpt_length');
                
                // 添加我们自己的过滤器，优先级设为最高
                add_filter('excerpt_length', array($this, 'custom_excerpt_length'), 0);
                
                // 覆盖Blocksy主题的摘要长度设置
                add_filter('blocksy:excerpt:length', function() use ($custom_length) {
                    return $custom_length;
                }, 999);
                
                // 直接覆盖blocksy_trim_excerpt函数的行为
                add_filter('blocksy:excerpt:output', function($text) {
                    // 在搜索结果页面应用我们的摘要长度
                    if (is_search()) {
                        $custom_length = get_option('naibabiji_b2b_product_excerpt_length', 20);
                        $custom_length = absint($custom_length);
                        if ($custom_length < 1) {
                            $custom_length = 20;
                        }
                        
                        // 使用wp_trim_words重新截取摘要
                        $text = wp_trim_words(get_the_excerpt(), $custom_length, '...');
                    }
                    return $text;
                }, 999);
            }
        }
        
        // Astra主题相关代码已移除
    }
}