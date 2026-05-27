<?php
/**
 * Data Migration Class
 * 
 * Handles the transition of legacy flat metadata to the new grouped structure in v2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Naibabiji_B2B_Data_Migration {
    
    /**
     * Initialize migration hooks
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'check_for_migration'));
        add_action('wp_ajax_naibabiji_b2b_run_migration', array(__CLASS__, 'ajax_run_migration'));
    }
    
    /**
     * Check if migration is needed
     */
    public static function check_for_migration() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $current_db_version = get_option('naibabiji_b2b_db_version', '1.0.0');
        
        if (version_compare($current_db_version, '2.1.0', '<')) {
            // Add a notice indicating migration is available/needed
            add_action('admin_notices', array(__CLASS__, 'render_migration_notice'));
        }
    }
    
    /**
     * Render migration notice
     */
    public static function render_migration_notice() {
        $screen = get_current_screen();
        if ($screen->id !== 'settings_page_naibabiji-b2b-product-showcase') {
            return;
        }
        ?>
        <div class="notice notice-info is-dismissible naibabiji-b2b-migration-notice">
            <p>
                <strong><?php esc_html_e('Naibabiji B2B Product Showcase Update', 'naibabiji-b2b-product-showcase'); ?></strong><br>
                <?php esc_html_e('We have upgraded the data structure for better performance. Please run the migration to update your existing products to the new format.', 'naibabiji-b2b-product-showcase'); ?>
            </p>
            <p>
                <button type="button" id="naibabiji-b2b-run-migration" class="button button-primary">
                    <?php esc_html_e('Run Migration Now', 'naibabiji-b2b-product-showcase'); ?>
                </button>
                <span class="spinner" style="float: none; margin: 0 10px;"></span>
                <span class="migration-status"></span>
            </p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#naibabiji-b2b-run-migration').on('click', function() {
                const $btn = $(this);
                const $spinner = $btn.siblings('.spinner');
                const $status = $btn.siblings('.migration-status');
                
                $btn.prop('disabled', true);
                $spinner.addClass('is-active');
                $status.text('<?php esc_html_e('Migrating products...', 'naibabiji-b2b-product-showcase'); ?>');
                
                $.post(ajaxurl, {
                    action: 'naibabiji_b2b_run_migration',
                    nonce: '<?php echo esc_attr(wp_create_nonce('naibabiji_b2b_migration_nonce')); ?>'
                }, function(response) {
                    $spinner.removeClass('is-active');
                    if (response.success) {
                        $status.css('color', 'green').text(response.data.message);
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        $status.css('color', 'red').text(response.data.message || '<?php esc_html_e('Migration failed.', 'naibabiji-b2b-product-showcase'); ?>');
                        $btn.prop('disabled', false);
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for running migration
     */
    public static function ajax_run_migration() {
        check_ajax_referer('naibabiji_b2b_migration_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized access.', 'naibabiji-b2b-product-showcase')));
        }
        
        $result = self::perform_migration();
        
        if ($result['success']) {
            update_option('naibabiji_b2b_db_version', '2.1.0');
            // translators: %d is the number of products updated
            wp_send_json_success(array('message' => sprintf(__('Migration successful! %d products updated.', 'naibabiji-b2b-product-showcase'), $result['count'])));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * Perform the actual migration
     */
    public static function perform_migration() {
        $products = get_posts(array(
            'post_type' => 'naibb2pr_products',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));
        
        $count = 0;
        foreach ($products as $product) {
            $product_id = $product->ID;
            
            // Check if already migrated
            $existing_data = get_post_meta($product_id, '_naibabiji_b2b_product_data', true);
            if (!empty($existing_data) && is_array($existing_data)) {
                continue;
            }
            
            // Gather old data
            $old_gallery = get_post_meta($product_id, '_naibabiji_b2b_product_gallery', true);
            if (is_string($old_gallery) && !empty($old_gallery)) {
                $old_gallery = array_map('absint', explode(',', $old_gallery));
            } elseif (!is_array($old_gallery)) {
                $old_gallery = array();
            }

            $old_data = array(
                'short_description' => get_post_meta($product_id, '_naibabiji_b2b_product_short_description', true),
                'gallery'           => $old_gallery,
                'inquiry_url'       => get_post_meta($product_id, '_naibabiji_b2b_product_inquiry_url', true),
                'inquiry_text'      => get_post_meta($product_id, '_naibabiji_b2b_product_inquiry_text', true),
                'enable_inquiry'    => get_post_meta($product_id, '_naibabiji_b2b_product_enable_inquiry', true),
                'sku'               => get_post_meta($product_id, '_naibabiji_b2b_product_sku', true),
                'price'             => get_post_meta($product_id, '_naibabiji_b2b_product_price', true),
            );
            
            // Handle some defaults/types
            if ($old_data['enable_inquiry'] === '') {
                $old_data['enable_inquiry'] = true; // Default was true in most templates
            } else {
                $old_data['enable_inquiry'] = (bool)$old_data['enable_inquiry'];
            }
            
            // Save new grouped data
            update_post_meta($product_id, '_naibabiji_b2b_product_data', $old_data);
            $count++;
        }
        
        return array('success' => true, 'count' => $count);
    }
}
