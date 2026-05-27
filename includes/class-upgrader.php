<?php
/**
 * 插件升级处理类
 *
 * 处理插件版本升级和数据迁移
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Product_Upgrader {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', array($this, 'check_version'));
    }
    
    /**
     * 检查版本并执行升级
     */
    public function check_version() {
        // 获取当前版本号
        $current_version = get_option('naibabiji_b2b_product_showcase_version', '1.0.0');
        
        if (version_compare($current_version, NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION, '<')) {
            $this->upgrade($current_version, NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION);
        }
    }
    
    /**
     * 执行升级流程
     */
    private function upgrade($old_version, $new_version) {
        global $wpdb;
        
        // 触发升级前的钩子
        do_action('naibabiji_b2b_product_before_upgrade', $old_version, $new_version);
        
        // 1.0.0 -> 1.0.1 升级
        if (version_compare($old_version, '1.0.1', '<')) {
            $this->upgrade_to_1_0_1();
        }
        
        // 1.0.1 -> 1.1.0 升级
        if (version_compare($old_version, '1.1.0', '<')) {
            $this->upgrade_to_1_1_0();
        }
        
        // 更新版本号
        update_option('naibabiji_b2b_product_showcase_version', NAIBABIJI_B2B_PRODUCT_SHOWCASE_VERSION);
        
        // 触发升级后的钩子
        do_action('naibabiji_b2b_product_after_upgrade', $old_version, $new_version);
        
        // 刷新重写规则
        flush_rewrite_rules();
    }
    
    /**
     * 升级到1.0.1版本
     */
    private function upgrade_to_1_0_1() {
        // 添加默认选项
        add_option('naibabiji_b2b_product_enable_schema', 1);
        add_option('naibabiji_b2b_product_enable_breadcrumbs', 1);
    }
    
    /**
     * 升级到1.1.0版本
     */
    private function upgrade_to_1_1_0() {
        global $wpdb;
        
        // 添加缓存和API选项
        add_option('naibabiji_b2b_product_enable_cache', 1);
        add_option('naibabiji_b2b_product_cache_lifetime', 3600);
        add_option('naibabiji_b2b_product_enable_api', 0);
        add_option('naibabiji_b2b_product_api_key', wp_generate_password(32, false));
        
        // 为所有产品添加新的元数据字段
        $products = get_posts(array(
            'post_type' => 'naibb2pr_products',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($products as $product) {
            add_post_meta($product->ID, '_naibabiji_b2b_product_views', 0, true);
            add_post_meta($product->ID, '_naibabiji_b2b_product_inquiry_count', 0, true);
        }
        
        // 创建缓存表
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}naibabiji_b2b_product_cache (
            cache_key varchar(255) NOT NULL,
            cache_value longtext NOT NULL,
            expiration bigint(20) NOT NULL,
            PRIMARY KEY  (cache_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}