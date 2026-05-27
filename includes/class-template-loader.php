<?php
/**
 * 模板加载器类
 * 
 * 负责加载产品相关的模板文件，优先使用主题中的模板，
 * 如果主题中没有则使用插件提供的默认模板
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product_Template_Loader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_filter('template_include', array($this, 'template_loader'), 99);
        add_filter('archive_template_hierarchy', array($this, 'add_archive_template_hierarchy'), 10);
        add_filter('single_template_hierarchy', array($this, 'add_single_template_hierarchy'), 10);
    }
    
    /**
     * 模板加载器
     * 使用WordPress核心的template_include过滤器
     */
    public function template_loader($template) {
        if (is_embed()) {
            return $template;
        }
        
        $default_file = $this->get_template_loader_default_file();
        
        if ($default_file) {
            // 首先检查主题中是否有模板文件
            $theme_template = locate_template(array($default_file));
            
            if ($theme_template) {
                // 如果主题中有模板，使用主题模板
                $template = $theme_template;
            } else {
                // 如果主题中没有，使用插件模板
                $plugin_template = NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/' . $default_file;
                if (file_exists($plugin_template)) {
                    $template = $plugin_template;
                }
            }
            
            /**
             * 过滤器：允许开发者修改模板文件路径
             * 
             * @param string $template      当前模板路径
             * @param string $default_file  默认模板文件名
             */
            $template = apply_filters('naibabiji_b2b_product_template_loader', $template, $default_file);
        }
        
        return $template;
    }
    
    /**
     * 获取默认模板文件名
     */
    private function get_template_loader_default_file() {
        if (is_singular('naibb2pr_products')) {
            $default_file = 'single-naibb2pr_products.php';
        } elseif (is_post_type_archive('naibb2pr_products')) {
            $default_file = 'archive-naibb2pr_products.php';
        } elseif (is_tax('naibb2pr_product_category') || is_tax('naibb2pr_product_tag')) {
            $default_file = 'taxonomy-' . get_queried_object()->taxonomy . '.php';
        } else {
            $default_file = '';
        }
        
        return $default_file;
    }
    
    /**
     * 添加归档页模板层级
     */
    public function add_archive_template_hierarchy($templates) {
        if (is_post_type_archive('naibb2pr_products')) {
            array_unshift($templates, 'archive-naibb2pr_products.php');
        }
        return $templates;
    }
    
    /**
     * 添加单页模板层级
     */
    public function add_single_template_hierarchy($templates) {
        if (is_singular('naibb2pr_products')) {
            array_unshift($templates, 'single-naibb2pr_products.php');
        }
        return $templates;
    }
    
    /**
     * 获取模板文件路径
     * 
     * @param string $template_name 模板文件名
     * @param array  $args          传递给模板的参数
     * @param string $template_path 模板路径（相对于主题目录）
     * @param string $default_path  默认路径（插件模板目录）
     */
    public static function get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
        if (!empty($args) && is_array($args)) {
            extract($args); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        }
        
        $cache_key = sanitize_key(implode('-', array('template', $template_name, $template_path, $default_path)));
        $template = (string) wp_cache_get($cache_key, 'naibabiji-b2b-product-showcase');
        
        if (!$template) {
            $template = self::locate_template($template_name, $template_path, $default_path);
            wp_cache_set($cache_key, $template, 'naibabiji-b2b-product-showcase', DAY_IN_SECONDS);
        }
        
        // 允许第三方插件过滤模板文件
        $template = apply_filters('naibabiji_b2b_product_get_template', $template, $template_name, $args, $template_path, $default_path);
        
        if ($template) {
            load_template($template, false, $args);
        }
    }
    
    /**
     * 定位模板文件
     * 
     * @param string $template_name 模板文件名
     * @param string $template_path 模板路径（相对于主题目录）
     * @param string $default_path  默认路径（插件模板目录）
     * @return string
     */
    public static function locate_template($template_name, $template_path = '', $default_path = '') {
        if (!$template_path) {
            $template_path = 'naibabiji-b2b-product-showcase/';
        }
        
        if (!$default_path) {
            $default_path = NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/';
        }
        
        // 在主题中查找模板
        $template = locate_template(
            array(
                trailingslashit($template_path) . $template_name,
                $template_name,
            )
        );
        
        // 如果主题中没有，使用插件默认模板
        if (!$template && file_exists(trailingslashit($default_path) . $template_name)) {
            $template = trailingslashit($default_path) . $template_name;
        }
        
        // 允许第三方插件过滤模板路径
        return apply_filters('naibabiji_b2b_product_locate_template', $template, $template_name, $template_path);
    }
    
    /**
     * 获取模板部分
     * 
     * @param string $slug 模板slug
     * @param string $name 模板名称
     * @param array  $args 传递给模板的参数
     */
    public static function get_template_part($slug, $name = '', $args = array()) {
        $cache_key = sanitize_key(implode('-', array('template-part', $slug, $name)));
        $template = (string) wp_cache_get($cache_key, 'naibabiji-b2b-product-showcase');
        
        if (!$template) {
            if ($name) {
                $template = self::locate_template("{$slug}-{$name}.php");
            }
            
            if (!$template) {
                $template = self::locate_template("{$slug}.php");
            }
            
            wp_cache_set($cache_key, $template, 'naibabiji-b2b-product-showcase', DAY_IN_SECONDS);
        }
        
        // 允许第三方插件过滤模板部分
        $template = apply_filters('naibabiji_b2b_product_get_template_part', $template, $slug, $name, $args);
        
        if ($template) {
            load_template($template, false, $args);
        }
    }
}