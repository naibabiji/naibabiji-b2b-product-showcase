<?php
/**
 * Taxonomy Custom Fields Management Class
 * 
 * Handles custom fields for product taxonomies, including SEO content
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product Taxonomy Custom Fields Management Class
 *
 * @since 1.0.0
 */
class Naibabiji_B2B_Product_Taxonomy_Fields {
    
    /**
     * Initialize and set hooks
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Product category fields - 只在编辑页面显示SEO内容字段
        // 移除新建分类页面的SEO内容字段
        // add_action('naibb2pr_product_category_add_form_fields', array($this, 'add_category_fields'));
        add_action('naibb2pr_product_category_edit_form_fields', array($this, 'edit_category_fields'));
        add_action('edited_naibb2pr_product_category', array($this, 'save_category_fields'));
        add_action('create_naibb2pr_product_category', array($this, 'save_category_fields'));
        
        // Product tag fields - 只在编辑页面显示SEO内容字段
        // 移除新建标签页面的SEO内容字段
        // add_action('naibb2pr_product_tag_add_form_fields', array($this, 'add_tag_fields'));
        add_action('naibb2pr_product_tag_edit_form_fields', array($this, 'edit_tag_fields'));
        add_action('edited_naibb2pr_product_tag', array($this, 'save_tag_fields'));
        add_action('create_naibb2pr_product_tag', array($this, 'save_tag_fields'));
        
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // 数据迁移 - 检查并执行一次性迁移
        add_action('admin_init', array($this, 'check_and_migrate'));
    }
    
    /**
     * Load admin scripts
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook suffix
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'edit-tags.php' === $hook || 'term.php' === $hook ) {
            wp_enqueue_editor();
            wp_enqueue_media();
            add_action('admin_footer', array($this, 'category_image_upload_js'));
        }
    }

    /**
     * Output JS for category image uploader
     */
    public function category_image_upload_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            var frame;
            $('.naibabiji-b2b-upload-image').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $wrapper = $btn.closest('.naibabiji-b2b-category-image-wrapper');
                var $input = $wrapper.find('input[type="hidden"]');
                var $preview = $wrapper.find('img');
                var $remove = $wrapper.find('.naibabiji-b2b-remove-image');

                if (frame) {
                    frame.open();
                    return;
                }
                frame = wp.media({
                    title: '<?php echo esc_js(__('Select Category Image', 'naibabiji-b2b-product-showcase')); ?>',
                    button: { text: '<?php echo esc_js(__('Use this image', 'naibabiji-b2b-product-showcase')); ?>' },
                    multiple: false
                });
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $input.val(attachment.id);
                    $preview.attr('src', attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url).show();
                    $remove.show();
                });
                frame.open();
            });

            $('.naibabiji-b2b-remove-image').on('click', function(e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.naibabiji-b2b-category-image-wrapper');
                $wrapper.find('input[type="hidden"]').val('');
                $wrapper.find('img').attr('src', '').hide();
                $(this).hide();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Check version and run data migration if needed
     *
     * @since 2.0.0
     */
    public function check_and_migrate() {
        $migration_version = get_option('naibabiji_b2b_seo_migration_version', '0');
        
        // If migration has not been run, execute it
        if (version_compare($migration_version, '2.0.0', '<')) {
            $this->migrate_seo_data();
            update_option('naibabiji_b2b_seo_migration_version', '2.0.0');
        }
    }
    
    /**
     * Migrate old SEO data to new structure
     * 
     * Migrates from single content + position to separate top/bottom content
     * Migration strategy (方案B):
     * - position = 'top' → content to top field
     * - position = 'bottom' → content to bottom field
     * - position = 'both' → content to both fields
     *
     * @since 2.0.0
     */
    private function migrate_seo_data() {
        global $wpdb;
        
        // Migrate category SEO data
        $categories = get_terms(array(
            'taxonomy' => 'naibb2pr_product_category',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($categories)) {
            foreach ($categories as $category) {
                $old_content = get_term_meta($category->term_id, 'naibabiji_b2b_category_seo_content', true);
                $old_position = get_term_meta($category->term_id, 'naibabiji_b2b_category_seo_position', true);
                $old_enable = get_term_meta($category->term_id, 'naibabiji_b2b_category_enable_seo', true);
                
                // Skip if no old data exists
                if (empty($old_content)) {
                    continue;
                }
                
                // Set default position if not set
                if (empty($old_position)) {
                    $old_position = 'top';
                }
                
                // Migrate based on position
                if ($old_position === 'top') {
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_seo_content_top', $old_content);
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_enable_seo_top', $old_enable);
                } elseif ($old_position === 'bottom') {
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_seo_content_bottom', $old_content);
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_enable_seo_bottom', $old_enable);
                } elseif ($old_position === 'both') {
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_seo_content_top', $old_content);
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_seo_content_bottom', $old_content);
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_enable_seo_top', $old_enable);
                    update_term_meta($category->term_id, 'naibabiji_b2b_category_enable_seo_bottom', $old_enable);
                }
                
                // Keep old data for backup (don't delete immediately)
                // Users can manually verify and delete later if needed
            }
        }
        
        // Migrate tag SEO data
        $tags = get_terms(array(
            'taxonomy' => 'naibb2pr_product_tag',
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($tags)) {
            foreach ($tags as $tag) {
                $old_content = get_term_meta($tag->term_id, 'naibabiji_b2b_tag_seo_content', true);
                $old_position = get_term_meta($tag->term_id, 'naibabiji_b2b_tag_seo_position', true);
                $old_enable = get_term_meta($tag->term_id, 'naibabiji_b2b_tag_enable_seo', true);
                
                // Skip if no old data exists
                if (empty($old_content)) {
                    continue;
                }
                
                // Set default position if not set
                if (empty($old_position)) {
                    $old_position = 'top';
                }
                
                // Migrate based on position
                if ($old_position === 'top') {
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_seo_content_top', $old_content);
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_enable_seo_top', $old_enable);
                } elseif ($old_position === 'bottom') {
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_seo_content_bottom', $old_content);
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_enable_seo_bottom', $old_enable);
                } elseif ($old_position === 'both') {
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_seo_content_top', $old_content);
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_seo_content_bottom', $old_content);
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_enable_seo_top', $old_enable);
                    update_term_meta($tag->term_id, 'naibabiji_b2b_tag_enable_seo_bottom', $old_enable);
                }
                
                // Keep old data for backup
            }
        }
    }
    
    /**
     * Add fields when creating a category
     *
     * @since 1.0.0
     */
    public function add_category_fields() {
        // 添加nonce字段用于安全验证
        wp_nonce_field('naibabiji_b2b_category_fields_nonce', 'naibabiji_b2b_category_fields_nonce');
        ?>
        <div class="form-field">
            <label for="naibabiji_b2b_category_image"><?php esc_html_e('Category Image', 'naibabiji-b2b-product-showcase'); ?></label>
            <div class="naibabiji-b2b-category-image-wrapper">
                <img id="naibabiji_b2b_category_image_preview" src="" style="max-width:200px; display:none;" />
                <input type="hidden" name="naibabiji_b2b_category_image" id="naibabiji_b2b_category_image" value="" />
                <button type="button" class="button naibabiji-b2b-upload-image"><?php esc_html_e('Upload Image', 'naibabiji-b2b-product-showcase'); ?></button>
                <button type="button" class="button naibabiji-b2b-remove-image" style="display:none;"><?php esc_html_e('Remove Image', 'naibabiji-b2b-product-showcase'); ?></button>
            </div>
            <p class="description"><?php esc_html_e('Upload a category image. Used when archive page is set to categories-only display mode.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>

        <div class="form-field">
            <label for="naibabiji_b2b_category_seo_content"><?php esc_html_e('SEO Content', 'naibabiji-b2b-product-showcase'); ?></label>
            <div id="naibabiji_b2b_category_seo_content_editor">
                <?php
                wp_editor('', 'naibabiji_b2b_category_seo_content', array(
                    'textarea_name' => 'naibabiji_b2b_category_seo_content',
                    'media_buttons' => true,
                    'textarea_rows' => 10,
                    'teeny' => false,
                    'tinymce' => array(
                        'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                        'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
                    ),
                ));
                ?>
            </div>
            <p class="description"><?php esc_html_e('SEO content displayed at the top of the category page, supporting text, images, and links. This helps improve the SEO performance of the page.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="naibabiji_b2b_category_seo_position"><?php esc_html_e('Content Display Position', 'naibabiji-b2b-product-showcase'); ?></label>
            <select name="naibabiji_b2b_category_seo_position" id="naibabiji_b2b_category_seo_position">
                <option value="top"><?php esc_html_e('Page Top (Recommended)', 'naibabiji-b2b-product-showcase'); ?></option>
                <option value="bottom"><?php esc_html_e('Page Bottom', 'naibabiji-b2b-product-showcase'); ?></option>
                <option value="both"><?php esc_html_e('Top and Bottom', 'naibabiji-b2b-product-showcase'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Choose where to display the SEO content on the category page.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="naibabiji_b2b_category_enable_seo"><?php esc_html_e('Enable SEO Content', 'naibabiji-b2b-product-showcase'); ?></label>
            <input type="checkbox" name="naibabiji_b2b_category_enable_seo" id="naibabiji_b2b_category_enable_seo" value="1" checked />
            <p class="description"><?php esc_html_e('Uncheck to temporarily hide SEO content without deleting it.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit category fields
     *
     * @since 1.0.0
     * @param WP_Term $term Current term object
     */
    public function edit_category_fields( $term ) {
        $seo_content_top = get_term_meta( $term->term_id, 'naibabiji_b2b_category_seo_content_top', true );
        $seo_content_bottom = get_term_meta( $term->term_id, 'naibabiji_b2b_category_seo_content_bottom', true );
        $enable_seo_top = get_term_meta( $term->term_id, 'naibabiji_b2b_category_enable_seo_top', true );
        $enable_seo_bottom = get_term_meta( $term->term_id, 'naibabiji_b2b_category_enable_seo_bottom', true );
        $hide_title = get_term_meta( $term->term_id, 'naibabiji_b2b_category_hide_title', true );
        
        // 添加nonce字段用于安全验证
        wp_nonce_field('naibabiji_b2b_category_fields_nonce', 'naibabiji_b2b_category_fields_nonce');
        
        // Set default values
        if ( '' === $enable_seo_top ) {
            $enable_seo_top = '1';
        }
        if ( '' === $enable_seo_bottom ) {
            $enable_seo_bottom = '1';
        }
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <?php esc_html_e('Display Options', 'naibabiji-b2b-product-showcase'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="naibabiji_b2b_category_hide_title" value="1" <?php checked($hide_title, '1'); ?> />
                    <?php esc_html_e('Hide category title', 'naibabiji-b2b-product-showcase'); ?>
                </label>
                <p class="description"><?php esc_html_e('Hide the default category title on the category page for more flexible header design.', 'naibabiji-b2b-product-showcase'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="naibabiji_b2b_category_image"><?php esc_html_e('Category Image', 'naibabiji-b2b-product-showcase'); ?></label>
            </th>
            <td>
                <?php
                $category_image_id = get_term_meta($term->term_id, 'naibabiji_b2b_category_image', true);
                $category_image_url = $category_image_id ? wp_get_attachment_image_url($category_image_id, 'medium') : '';
                ?>
                <div class="naibabiji-b2b-category-image-wrapper">
                    <img id="naibabiji_b2b_category_image_preview" src="<?php echo esc_url($category_image_url); ?>" style="max-width:200px; <?php echo $category_image_url ? '' : 'display:none;'; ?>" />
                    <input type="hidden" name="naibabiji_b2b_category_image" id="naibabiji_b2b_category_image" value="<?php echo esc_attr($category_image_id); ?>" />
                    <button type="button" class="button naibabiji-b2b-upload-image"><?php esc_html_e('Upload Image', 'naibabiji-b2b-product-showcase'); ?></button>
                    <button type="button" class="button naibabiji-b2b-remove-image" style="<?php echo $category_image_url ? '' : 'display:none;'; ?>"><?php esc_html_e('Remove Image', 'naibabiji-b2b-product-showcase'); ?></button>
                </div>
                <p class="description"><?php esc_html_e('Upload a category image. Used when archive page is set to categories-only display mode.', 'naibabiji-b2b-product-showcase'); ?></p>
            </td>
        </tr>

        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="naibabiji_b2b_category_seo_content_top"><?php esc_html_e('SEO Content - Page Top', 'naibabiji-b2b-product-showcase'); ?></label>
            </th>
            <td>
                <div id="naibabiji_b2b_category_seo_content_top_editor">
                    <?php
                    wp_editor($seo_content_top, 'naibabiji_b2b_category_seo_content_top', array(
                        'textarea_name' => 'naibabiji_b2b_category_seo_content_top',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                            'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
                        ),
                    ));
                    ?>
                </div>
                <p class="description"><?php esc_html_e('SEO content displayed at the top of the category page, supporting text, images, and links.', 'naibabiji-b2b-product-showcase'); ?></p>
                <p>
                    <label>
                        <input type="checkbox" name="naibabiji_b2b_category_enable_seo_top" value="1" <?php checked($enable_seo_top, '1'); ?> />
                        <?php esc_html_e('Enable top SEO content', 'naibabiji-b2b-product-showcase'); ?>
                    </label>
                </p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="naibabiji_b2b_category_seo_content_bottom"><?php esc_html_e('SEO Content - Page Bottom', 'naibabiji-b2b-product-showcase'); ?></label>
            </th>
            <td>
                <div id="naibabiji_b2b_category_seo_content_bottom_editor">
                    <?php
                    wp_editor($seo_content_bottom, 'naibabiji_b2b_category_seo_content_bottom', array(
                        'textarea_name' => 'naibabiji_b2b_category_seo_content_bottom',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                            'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
                        ),
                    ));
                    ?>
                </div>
                <p class="description"><?php esc_html_e('SEO content displayed at the bottom of the category page, supporting text, images, and links.', 'naibabiji-b2b-product-showcase'); ?></p>
                <p>
                    <label>
                        <input type="checkbox" name="naibabiji_b2b_category_enable_seo_bottom" value="1" <?php checked($enable_seo_bottom, '1'); ?> />
                        <?php esc_html_e('Enable bottom SEO content', 'naibabiji-b2b-product-showcase'); ?>
                    </label>
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save category fields
     *
     * @since 1.0.0
     * @param int $term_id Term ID
     */
    public function save_category_fields( $term_id ) {
        // 验证nonce
        if (!isset($_POST['naibabiji_b2b_category_fields_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['naibabiji_b2b_category_fields_nonce'])), 'naibabiji_b2b_category_fields_nonce')) {
            return;
        }
        
        // Verify permissions
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }

        // Save category image
        if (isset($_POST['naibabiji_b2b_category_image'])) {
            $image_id = absint($_POST['naibabiji_b2b_category_image']);
            if ($image_id) {
                update_term_meta($term_id, 'naibabiji_b2b_category_image', $image_id);
            } else {
                delete_term_meta($term_id, 'naibabiji_b2b_category_image');
            }
        }

        // Save top SEO content
        if ( isset( $_POST['naibabiji_b2b_category_seo_content_top'] ) ) {
            $seo_content_top = wp_kses_post( wp_unslash( $_POST['naibabiji_b2b_category_seo_content_top'] ) );
            update_term_meta( $term_id, 'naibabiji_b2b_category_seo_content_top', $seo_content_top );
        }
        
        // Save bottom SEO content
        if ( isset( $_POST['naibabiji_b2b_category_seo_content_bottom'] ) ) {
            $seo_content_bottom = wp_kses_post( wp_unslash( $_POST['naibabiji_b2b_category_seo_content_bottom'] ) );
            update_term_meta( $term_id, 'naibabiji_b2b_category_seo_content_bottom', $seo_content_bottom );
        }
        
        // Save top enable status
        $enable_seo_top = isset( $_POST['naibabiji_b2b_category_enable_seo_top'] ) ? '1' : '0';
        update_term_meta( $term_id, 'naibabiji_b2b_category_enable_seo_top', $enable_seo_top );
        
        // Save bottom enable status
        $enable_seo_bottom = isset( $_POST['naibabiji_b2b_category_enable_seo_bottom'] ) ? '1' : '0';
        update_term_meta( $term_id, 'naibabiji_b2b_category_enable_seo_bottom', $enable_seo_bottom );
        
        // Save hide title option
        $hide_title = isset( $_POST['naibabiji_b2b_category_hide_title'] ) ? '1' : '0';
        update_term_meta( $term_id, 'naibabiji_b2b_category_hide_title', $hide_title );
    }
    
    /**
     * Add tag fields
     *
     * @since 1.0.0
     */
    public function add_tag_fields() {
        // 添加nonce字段用于安全验证 - 确保在新建标签时能正确验证
        wp_nonce_field('naibabiji_b2b_tag_fields_nonce', 'naibabiji_b2b_tag_fields_nonce');
        ?>
        <div class="form-field">
            <label for="naibabiji_b2b_tag_seo_content"><?php esc_html_e('SEO Content', 'naibabiji-b2b-product-showcase'); ?></label>
            <div id="naibabiji_b2b_tag_seo_content_editor">
                <?php
                wp_editor('', 'naibabiji_b2b_tag_seo_content', array(
                    'textarea_name' => 'naibabiji_b2b_tag_seo_content',
                    'media_buttons' => true,
                    'textarea_rows' => 10,
                    'teeny' => false,
                    'tinymce' => array(
                        'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                        'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
                    ),
                ));
                ?>
            </div>
            <p class="description"><?php esc_html_e('SEO content displayed at the top of the tag page, supporting text, images, and links. This helps improve the SEO performance of the page.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="naibabiji_b2b_tag_seo_position"><?php esc_html_e('Content Display Position', 'naibabiji-b2b-product-showcase'); ?></label>
            <select name="naibabiji_b2b_tag_seo_position" id="naibabiji_b2b_tag_seo_position">
                <option value="top"><?php esc_html_e('Page Top (Recommended)', 'naibabiji-b2b-product-showcase'); ?></option>
                <option value="bottom"><?php esc_html_e('Page Bottom', 'naibabiji-b2b-product-showcase'); ?></option>
                <option value="both"><?php esc_html_e('Top and Bottom', 'naibabiji-b2b-product-showcase'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Choose where to display the SEO content on the tag page.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="naibabiji_b2b_tag_enable_seo"><?php esc_html_e('Enable SEO Content', 'naibabiji-b2b-product-showcase'); ?></label>
            <input type="checkbox" name="naibabiji_b2b_tag_enable_seo" id="naibabiji_b2b_tag_enable_seo" value="1" checked />
            <p class="description"><?php esc_html_e('Uncheck to temporarily hide SEO content without deleting it.', 'naibabiji-b2b-product-showcase'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit tag fields
     *
     * @since 1.0.0
     * @param WP_Term $term Current tag object
     */
    public function edit_tag_fields( $term ) {
        $seo_content_top = get_term_meta( $term->term_id, 'naibabiji_b2b_tag_seo_content_top', true );
        $seo_content_bottom = get_term_meta( $term->term_id, 'naibabiji_b2b_tag_seo_content_bottom', true );
        $enable_seo_top = get_term_meta( $term->term_id, 'naibabiji_b2b_tag_enable_seo_top', true );
        $enable_seo_bottom = get_term_meta( $term->term_id, 'naibabiji_b2b_tag_enable_seo_bottom', true );
        $hide_title = get_term_meta( $term->term_id, 'naibabiji_b2b_tag_hide_title', true );
        
        // 添加nonce字段用于安全验证
        wp_nonce_field('naibabiji_b2b_tag_fields_nonce', 'naibabiji_b2b_tag_fields_nonce');
        
        // Set default values
        if ( '' === $enable_seo_top ) {
            $enable_seo_top = '1';
        }
        if ( '' === $enable_seo_bottom ) {
            $enable_seo_bottom = '1';
        }
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <?php esc_html_e('Display Options', 'naibabiji-b2b-product-showcase'); ?>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="naibabiji_b2b_tag_hide_title" value="1" <?php checked($hide_title, '1'); ?> />
                    <?php esc_html_e('Hide tag title', 'naibabiji-b2b-product-showcase'); ?>
                </label>
                <p class="description"><?php esc_html_e('Hide the default tag title on the tag page for more flexible header design.', 'naibabiji-b2b-product-showcase'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="naibabiji_b2b_tag_seo_content_top"><?php esc_html_e('SEO Content - Page Top', 'naibabiji-b2b-product-showcase'); ?></label>
            </th>
            <td>
                <div id="naibabiji_b2b_tag_seo_content_top_editor">
                    <?php
                    wp_editor($seo_content_top, 'naibabiji_b2b_tag_seo_content_top', array(
                        'textarea_name' => 'naibabiji_b2b_tag_seo_content_top',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                            'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
                        ),
                    ));
                    ?>
                </div>
                <p class="description"><?php esc_html_e('SEO content displayed at the top of the tag page, supporting text, images, and links.', 'naibabiji-b2b-product-showcase'); ?></p>
                <p>
                    <label>
                        <input type="checkbox" name="naibabiji_b2b_tag_enable_seo_top" value="1" <?php checked($enable_seo_top, '1'); ?> />
                        <?php esc_html_e('Enable top SEO content', 'naibabiji-b2b-product-showcase'); ?>
                    </label>
                </p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="naibabiji_b2b_tag_seo_content_bottom"><?php esc_html_e('SEO Content - Page Bottom', 'naibabiji-b2b-product-showcase'); ?></label>
            </th>
            <td>
                <div id="naibabiji_b2b_tag_seo_content_bottom_editor">
                    <?php
                    wp_editor($seo_content_bottom, 'naibabiji_b2b_tag_seo_content_bottom', array(
                        'textarea_name' => 'naibabiji_b2b_tag_seo_content_bottom',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,blockquote,|,link,unlink,|,undo,redo',
                            'toolbar2' => 'formatselect,|,forecolor,backcolor,|,alignleft,aligncenter,alignright,alignjustify,|,image,media',
                        ),
                    ));
                    ?>
                </div>
                <p class="description"><?php esc_html_e('SEO content displayed at the bottom of the tag page, supporting text, images, and links.', 'naibabiji-b2b-product-showcase'); ?></p>
                <p>
                    <label>
                        <input type="checkbox" name="naibabiji_b2b_tag_enable_seo_bottom" value="1" <?php checked($enable_seo_bottom, '1'); ?> />
                        <?php esc_html_e('Enable bottom SEO content', 'naibabiji-b2b-product-showcase'); ?>
                    </label>
                </p>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save tag fields
     *
     * @since 1.0.0
     * @param int $term_id Tag ID
     */
    public function save_tag_fields( $term_id ) {
        // 验证nonce
        if (!isset($_POST['naibabiji_b2b_tag_fields_nonce']) || 
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['naibabiji_b2b_tag_fields_nonce'])), 'naibabiji_b2b_tag_fields_nonce')) {
            return;
        }
        
        // Verify permissions
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }
        
        // Save top SEO content
        if ( isset( $_POST['naibabiji_b2b_tag_seo_content_top'] ) ) {
            $seo_content_top = wp_kses_post( wp_unslash( $_POST['naibabiji_b2b_tag_seo_content_top'] ) );
            update_term_meta( $term_id, 'naibabiji_b2b_tag_seo_content_top', $seo_content_top );
        }
        
        // Save bottom SEO content
        if ( isset( $_POST['naibabiji_b2b_tag_seo_content_bottom'] ) ) {
            $seo_content_bottom = wp_kses_post( wp_unslash( $_POST['naibabiji_b2b_tag_seo_content_bottom'] ) );
            update_term_meta( $term_id, 'naibabiji_b2b_tag_seo_content_bottom', $seo_content_bottom );
        }
        
        // Save top enable status
        $enable_seo_top = isset( $_POST['naibabiji_b2b_tag_enable_seo_top'] ) ? '1' : '0';
        update_term_meta( $term_id, 'naibabiji_b2b_tag_enable_seo_top', $enable_seo_top );
        
        // Save bottom enable status
        $enable_seo_bottom = isset( $_POST['naibabiji_b2b_tag_enable_seo_bottom'] ) ? '1' : '0';
        update_term_meta( $term_id, 'naibabiji_b2b_tag_enable_seo_bottom', $enable_seo_bottom );
        
        // Save hide title option
        $hide_title = isset( $_POST['naibabiji_b2b_tag_hide_title'] ) ? '1' : '0';
        update_term_meta( $term_id, 'naibabiji_b2b_tag_hide_title', $hide_title );
    }
    
    /**
     * Get category SEO content
     *
     * @since 1.0.0
     * @param int    $term_id  Category ID
     * @param string $position Content display position, default is 'top'
     * @return string Category SEO content
     */
    public static function get_category_seo_content( $term_id, $position = 'top' ) {
        // Get the appropriate content and enable status based on position
        if ( 'top' === $position ) {
            $enable_seo = get_term_meta( $term_id, 'naibabiji_b2b_category_enable_seo_top', true );
            $seo_content = get_term_meta( $term_id, 'naibabiji_b2b_category_seo_content_top', true );
        } else {
            $enable_seo = get_term_meta( $term_id, 'naibabiji_b2b_category_enable_seo_bottom', true );
            $seo_content = get_term_meta( $term_id, 'naibabiji_b2b_category_seo_content_bottom', true );
        }
        
        // Set default enable status
        if ( '' === $enable_seo ) {
            $enable_seo = '1';
        }
        
        // If SEO content is not enabled, return empty
        if ( '1' !== $enable_seo ) {
            return '';
        }
        
        // If no content, return empty
        if ( empty( $seo_content ) ) {
            return '';
        }
        
        return $seo_content;
    }
    
    /**
     * Get tag SEO content
     *
     * @since 1.0.0
     * @param int    $term_id  Tag ID
     * @param string $position Content display position, default is 'top'
     * @return string Tag SEO content
     */
    public static function get_tag_seo_content( $term_id, $position = 'top' ) {
        // Get the appropriate content and enable status based on position
        if ( 'top' === $position ) {
            $enable_seo = get_term_meta( $term_id, 'naibabiji_b2b_tag_enable_seo_top', true );
            $seo_content = get_term_meta( $term_id, 'naibabiji_b2b_tag_seo_content_top', true );
        } else {
            $enable_seo = get_term_meta( $term_id, 'naibabiji_b2b_tag_enable_seo_bottom', true );
            $seo_content = get_term_meta( $term_id, 'naibabiji_b2b_tag_seo_content_bottom', true );
        }
        
        // Set default enable status
        if ( '' === $enable_seo ) {
            $enable_seo = '1';
        }
        
        // If SEO content is not enabled, return empty
        if ( '1' !== $enable_seo ) {
            return '';
        }
        
        // If no content, return empty
        if ( empty( $seo_content ) ) {
            return '';
        }
        
        return $seo_content;
    }
    
    /**
     * Render SEO content (generic method)
     *
     * @since 1.0.0
     * @param int    $term_id  Category or tag ID
     * @param string $position Content display position, default is 'top'
     * @param string $taxonomy Taxonomy name, default is 'naibb2pr_product_category'
     * @return string Rendered SEO content
     */
    public static function render_seo_content( $term_id, $position = 'top', $taxonomy = 'naibb2pr_product_category' ) {
        if ( 'naibb2pr_product_tag' === $taxonomy ) {
            $content = self::get_tag_seo_content( $term_id, $position );
        } else {
            $content = self::get_category_seo_content( $term_id, $position );
        }
        
        if ( ! empty( $content ) ) {
            echo '<div class="naibabiji-b2b-seo-content naibabiji-b2b-seo-' . esc_attr( $position ) . '">';
            // 使用do_shortcode处理所有内容，支持Elementor短代码
            echo do_shortcode( wp_kses_post( $content ) );
            echo '</div>';
        }
        
        return $content;
    }
}

// Initialize class
new Naibabiji_B2B_Product_Taxonomy_Fields();
?>