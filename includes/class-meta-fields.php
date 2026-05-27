<?php
/**
 * Product Custom Fields Management Class
 *
 * Responsible for managing product custom fields, including product gallery, inquiry button, etc.
 * Uses WordPress core Meta Box API
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product Custom Fields Management Class
 *
 * @since 1.0.0
 */
class Naibabiji_B2B_Product_Meta_Fields {
    
    /**
     * Singleton instance of the class
     *
     * @since 1.0.0
     * @var Naibabiji_B2B_Product_Meta_Fields
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance of the class
     *
     * @since 1.0.0
     * @return Naibabiji_B2B_Product_Meta_Fields Singleton instance of the class
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor, sets up hooks
     *
     * @since 1.0.0
     */
    private function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_fields' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_head', array( $this, 'force_show_excerpt_box' ) );
        add_filter( 'default_hidden_meta_boxes', array( $this, 'show_short_description_meta_box' ), 10, 2 );
        
        // Add excerpt support immediately, rather than through the init hook
        $this->add_excerpt_support();
    }
    
    /**
     * Add Meta Box
     * 
     * Uses WordPress core function add_meta_box()
     *
     * @since 1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'naibabiji_b2b_product_short_description_box',
            esc_html__( 'Product Short Description', 'naibabiji-b2b-product-showcase' ),
            array( $this, 'render_short_description_meta_box' ),
            'naibb2pr_products',
            'normal',
            'high',
            null,
            array(
                '__back_compat_meta_box' => false,
                '__block_editor_compatible_meta_box' => true,
            )
        );
        
        add_meta_box(
            'naibabiji_b2b_product_gallery',
            esc_html__( 'Product Gallery', 'naibabiji-b2b-product-showcase' ),
            array( $this, 'render_gallery_meta_box' ),
            'naibb2pr_products',
            'normal',
            'high',
            null,
            array(
                '__back_compat_meta_box' => false,
                '__block_editor_compatible_meta_box' => true,
            )
        );
        
        add_meta_box(
            'naibabiji_b2b_product_inquiry',
            esc_html__( 'Product Data & Inquiry', 'naibabiji-b2b-product-showcase' ),
            array( $this, 'render_inquiry_meta_box' ),
            'naibb2pr_products',
            'side',
            'default',
            null,
            array(
                '__back_compat_meta_box' => false,
                '__block_editor_compatible_meta_box' => true,
            )
        );

        add_meta_box(
            'naibabiji_b2b_bulk_inquiry_specs',
            esc_html__( 'Specs Management (Bulk Inquiry)', 'naibabiji-b2b-product-showcase' ),
            array( $this, 'render_bulk_specs_meta_box' ),
            'naibb2pr_products',
            'normal',
            'default',
            null,
            array(
                '__back_compat_meta_box' => false,
                '__block_editor_compatible_meta_box' => true,
            )
        );

        // AI writing tips box — only shown when AI Customer Service is enabled
        if ( get_option( 'naibabiji_b2b_ai_enable', false ) ) {
            add_meta_box(
                'naibabiji_b2b_product_ai_tips',
                '🤖 ' . esc_html__( 'AI Writing Tips', 'naibabiji-b2b-product-showcase' ),
                array( $this, 'render_ai_tips_meta_box' ),
                'naibb2pr_products',
                'side',
                'low'
            );
        }
    }
    /**
     * Render AI Writing Tips Meta Box (read-only, no form fields)
     *
     * @since 3.2.0
     * @param WP_Post $post Current post object being edited
     */
    public function render_ai_tips_meta_box( $post ) {
        $ai_settings_url = admin_url( 'edit.php?post_type=naibb2pr_products&page=naibabiji-b2b-ai-settings' );
        ?>
        <style>
            #naibabiji_b2b_product_ai_tips .naib-ai-tips-list { margin: 0; padding: 0; list-style: none; }
            #naibabiji_b2b_product_ai_tips .naib-ai-tips-list li { padding: 7px 0; border-bottom: 1px solid #f0f0f0; font-size: 12px; line-height: 1.5; }
            #naibabiji_b2b_product_ai_tips .naib-ai-tips-list li:last-child { border-bottom: none; }
            #naibabiji_b2b_product_ai_tips .naib-ai-tips-list li strong { display: block; color: #1d2327; }
            #naibabiji_b2b_product_ai_tips .naib-ai-tips-list li span { color: #646970; }
            #naibabiji_b2b_product_ai_tips .naib-tips-note { font-size: 11px; color: #50575e; background: #f6f7f7; border-radius: var(--naibabiji-b2b-border-radius); padding: 7px 9px; margin: 10px 0 0; }
        </style>
        <p style="font-size:12px; color:#646970; margin: 4px 0 8px;">
            <?php esc_html_e( 'AI automatically reads these fields to answer customer questions:', 'naibabiji-b2b-product-showcase' ); ?>
        </p>
        <ul class="naib-ai-tips-list">
            <li>
                <strong>📌 <?php esc_html_e( 'Product Title', 'naibabiji-b2b-product-showcase' ); ?></strong>
                <span><?php esc_html_e( 'Use precise industry terms, e.g. "Ball Valve DN50 PN16 SS304"', 'naibabiji-b2b-product-showcase' ); ?></span>
            </li>
            <li>
                <strong>📝 <?php esc_html_e( 'Short Description', 'naibabiji-b2b-product-showcase' ); ?></strong>
                <span><?php esc_html_e( 'AI\'s primary source. Include: spec, use case, top 2–3 advantages.', 'naibabiji-b2b-product-showcase' ); ?></span>
            </li>
            <li>
                <strong>📄 <?php esc_html_e( 'Details (first ~500 words)', 'naibabiji-b2b-product-showcase' ); ?></strong>
                <span><?php esc_html_e( 'Put tech parameters & certifications at the top.', 'naibabiji-b2b-product-showcase' ); ?></span>
            </li>
            <li>
                <strong>🏷️ <?php esc_html_e( 'SKU & Price', 'naibabiji-b2b-product-showcase' ); ?></strong>
                <span><?php esc_html_e( 'Filled in = AI quotes directly, boosts inquiries.', 'naibabiji-b2b-product-showcase' ); ?></span>
            </li>
        </ul>
        <p class="naib-tips-note">
            💡 <?php esc_html_e( 'Good descriptions = better AI + better SEO.', 'naibabiji-b2b-product-showcase' ); ?>
            <br><a href="<?php echo esc_url( $ai_settings_url ); ?>" style="font-size:11px;">
                <?php esc_html_e( 'Edit Company Profile & FAQ →', 'naibabiji-b2b-product-showcase' ); ?>
            </a>
        </p>
        <?php
    }

    /**
     * Render Bulk Inquiry Specs Meta Box
     */
    public function render_bulk_specs_meta_box( $post ) {
        $specs = get_post_meta( $post->ID, '_bulk_inquiry_specs', true );
        if ( is_string( $specs ) ) {
            $specs = json_decode( $specs, true );
        }
        if ( ! is_array( $specs ) ) {
            $specs = array();
        }
        ?>
        <div class="naib-bulk-specs-container">
            <div class="naib-bulk-specs-toolbar" style="margin-bottom:12px;">
                <button type="button" class="button naib-bulk-specs-import-csv"><?php esc_html_e( 'Import CSV', 'naibabiji-b2b-product-showcase' ); ?></button>
                <button type="button" class="button naib-bulk-specs-export-csv"><?php esc_html_e( 'Export CSV', 'naibabiji-b2b-product-showcase' ); ?></button>
                <span class="naib-import-mode" style="margin-left:8px; display:none;">
                    <label><input type="radio" name="naib_csv_mode" value="append" checked> <?php esc_html_e( 'Append', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <label style="margin-left:8px;"><input type="radio" name="naib_csv_mode" value="replace"> <?php esc_html_e( 'Replace', 'naibabiji-b2b-product-showcase' ); ?></label>
                </span>
                <input type="file" class="naib-csv-file-input" accept=".csv" style="display:none;">
            </div>

            <table class="naib-bulk-specs-table widefat" style="width:100%; max-width:700px;">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th><?php esc_html_e( 'Model Code', 'naibabiji-b2b-product-showcase' ); ?> <span style="color:red;">*</span></th>
                        <th><?php esc_html_e( 'Spec Description', 'naibabiji-b2b-product-showcase' ); ?> <span style="color:red;">*</span></th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody class="naib-bulk-specs-tbody">
                    <?php foreach ( $specs as $index => $spec ) : ?>
                        <tr class="naib-spec-row">
                            <td class="naib-spec-handle" style="cursor:move;">&#x2261;</td>
                            <td><input type="text" name="naib_bulk_specs[code][]" value="<?php echo esc_attr( $spec['code'] ?? '' ); ?>" class="widefat" required></td>
                            <td><input type="text" name="naib_bulk_specs[description][]" value="<?php echo esc_attr( $spec['description'] ?? '' ); ?>" class="widefat" required></td>
                            <td><button type="button" class="button naib-spec-remove">&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <button type="button" class="button naib-bulk-specs-add" style="margin-top:8px;"><?php esc_html_e( '+ Add Spec', 'naibabiji-b2b-product-showcase' ); ?></button>
            <p class="description" style="margin-top:8px;"><?php esc_html_e( 'Spec description will be displayed to customers. Please fill in clearly. Drag rows to reorder.', 'naibabiji-b2b-product-showcase' ); ?></p>

            <!-- Persistent hidden textarea for Gutenberg compatibility.
                 Updated by JS on every spec change; serialized by block editor's REST API save.
                 DO NOT remove this field — save_meta_fields() depends on it. -->
            <textarea name="naib_bulk_specs_json" id="naib_bulk_specs_json" style="display:none;"><?php echo esc_textarea( wp_json_encode( $specs, JSON_UNESCAPED_UNICODE ) ); ?></textarea>

            <!-- CSV Preview Modal -->
            <div class="naib-csv-preview-modal" style="display:none;">
                <div class="naib-csv-preview-backdrop" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:100000;"></div>
                <div class="naib-csv-preview-content" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:24px;z-index:100001;max-width:600px;width:90%;max-height:80vh;overflow-y:auto;border-radius:var(--naibabiji-b2b-border-radius);">
                    <h3><?php esc_html_e( 'CSV Import Preview', 'naibabiji-b2b-product-showcase' ); ?></h3>
                    <div class="naib-csv-preview-table"></div>
                    <p class="naib-csv-preview-summary"></p>
                    <div style="margin-top:12px;">
                        <button type="button" class="button button-primary naib-csv-confirm"><?php esc_html_e( 'Confirm Import', 'naibabiji-b2b-product-showcase' ); ?></button>
                        <button type="button" class="button naib-csv-cancel"><?php esc_html_e( 'Cancel', 'naibabiji-b2b-product-showcase' ); ?></button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var $tbody = $('.naib-bulk-specs-tbody');
            var $specsJson = $('#naib_bulk_specs_json');

            /**
             * Sync the table rows into the hidden textarea as JSON.
             * Called on every add/remove/drag/CSV-import so the field is
             * always up-to-date, regardless of save path (classic POST or
             * Gutenberg REST API).
             */
            function syncSpecsJson() {
                var specs = [];
                $tbody.find('tr.naib-spec-row').each(function() {
                    var code = $(this).find('input[name="naib_bulk_specs[code][]"]').val().trim();
                    var desc = $(this).find('input[name="naib_bulk_specs[description][]"]').val().trim();
                    if (code && desc) {
                        specs.push({ code: code, description: desc, sort_order: specs.length + 1 });
                    }
                });
                $specsJson.val(JSON.stringify(specs));
            }

            // Make rows sortable
            $tbody.sortable({ handle: '.naib-spec-handle', axis: 'y', stop: syncSpecsJson });

            // Add new row
            $('.naib-bulk-specs-add').on('click', function() {
                var html = '<tr class="naib-spec-row">' +
                    '<td class="naib-spec-handle" style="cursor:move;">&#x2261;</td>' +
                    '<td><input type="text" name="naib_bulk_specs[code][]" value="" class="widefat" required></td>' +
                    '<td><input type="text" name="naib_bulk_specs[description][]" value="" class="widefat" required></td>' +
                    '<td><button type="button" class="button naib-spec-remove">&times;</button></td>' +
                    '</tr>';
                $tbody.append(html);
                syncSpecsJson();
            });

            // Remove row
            $tbody.on('click', '.naib-spec-remove', function() {
                $(this).closest('tr').remove();
                syncSpecsJson();
            });

            // Also sync on input change (debounced by the field blur)
            $tbody.on('change input', 'input', function() {
                syncSpecsJson();
            });

            // Classic editor fallback: also sync on form submit
            $('form#post').on('submit', function() {
                var val = $('input[name="naib_inquiry_type"]:checked').val();
                if (val !== 'bulk') {
                    $specsJson.val('');
                    return;
                }
                syncSpecsJson();
            });

            // CSV Import
            $('.naib-bulk-specs-import-csv').on('click', function() {
                $('.naib-csv-file-input').click();
            });

            $('.naib-csv-file-input').on('change', function() {
                var file = this.files[0];
                if (!file) return;

                if (file.size > 2 * 1024 * 1024) {
                    alert('<?php esc_html_e( 'File too large. Max 2MB.', 'naibabiji-b2b-product-showcase' ); ?>');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    var text = e.target.result;
                    var lines = text.split(/\r?\n/).filter(function(l) { return l.trim(); });
                    if (lines.length < 2) {
                        alert('<?php esc_html_e( 'CSV must have header and at least one data row.', 'naibabiji-b2b-product-showcase' ); ?>');
                        return;
                    }
                    if (lines.length > 1001) {
                        alert('<?php esc_html_e( 'CSV has too many rows. Max 1000 data rows.', 'naibabiji-b2b-product-showcase' ); ?>');
                        return;
                    }

                    // RFC 4180 simple CSV parser — handles quoted fields with commas
                    function parseCSVLine(line) {
                        var cols = [], current = '', inQuotes = false;
                        for (var c = 0; c < line.length; c++) {
                            var ch = line[c];
                            if (inQuotes) {
                                if (ch === '"') {
                                    if (c + 1 < line.length && line[c + 1] === '"') {
                                        current += '"'; c++;
                                    } else {
                                        inQuotes = false;
                                    }
                                } else { current += ch; }
                            } else {
                                if (ch === '"') { inQuotes = true; }
                                else if (ch === ',') { cols.push(current.trim()); current = ''; }
                                else { current += ch; }
                            }
                        }
                        cols.push(current.trim());
                        return cols;
                    }

                    var parsed = [];
                    for (var i = 1; i < lines.length; i++) {
                        var cols = parseCSVLine(lines[i]);
                        if (cols.length >= 2 && cols[0] && cols[1]) {
                            parsed.push({ code: cols[0], description: cols[1] });
                        }
                    }

                    if (parsed.length === 0) {
                        alert('<?php esc_html_e( 'No valid rows found in CSV.', 'naibabiji-b2b-product-showcase' ); ?>');
                        return;
                    }

                    // Show preview
                    var previewHtml = '<table class="widefat"><thead><tr><th><?php esc_html_e( 'Model Code', 'naibabiji-b2b-product-showcase' ); ?></th><th><?php esc_html_e( 'Spec Description', 'naibabiji-b2b-product-showcase' ); ?></th></tr></thead><tbody>';
                    parsed.forEach(function(row) {
                        previewHtml += '<tr><td>' + $('<div>').text(row.code).html() + '</td><td>' + $('<div>').text(row.description).html() + '</td></tr>';
                    });
                    previewHtml += '</tbody></table>';
                    $('.naib-csv-preview-table').html(previewHtml);
                    $('.naib-csv-preview-summary').text('<?php esc_html_e( 'Total rows:', 'naibabiji-b2b-product-showcase' ); ?> ' + parsed.length);
                    $('.naib-csv-preview-modal').data('parsed', parsed).show();
                    $('.naib-import-mode').show();
                };
                reader.readAsText(file, 'UTF-8');
            });

            // Confirm CSV import
            $('.naib-csv-confirm').on('click', function() {
                var parsed = $('.naib-csv-preview-modal').data('parsed');
                var mode = $('input[name="naib_csv_mode"]:checked').val();
                if (!parsed) return;

                if (mode === 'replace') {
                    $tbody.empty();
                }

                parsed.forEach(function(row) {
                    var html = '<tr class="naib-spec-row">' +
                        '<td class="naib-spec-handle" style="cursor:move;">&#x2261;</td>' +
                        '<td><input type="text" name="naib_bulk_specs[code][]" value="' + $('<div>').text(row.code).html() + '" class="widefat" required></td>' +
                        '<td><input type="text" name="naib_bulk_specs[description][]" value="' + $('<div>').text(row.description).html() + '" class="widefat" required></td>' +
                        '<td><button type="button" class="button naib-spec-remove">&times;</button></td>' +
                        '</tr>';
                    $tbody.append(html);
                });

                $('.naib-csv-preview-modal').hide();
                $('.naib-import-mode').hide();
                $('.naib-csv-file-input').val('');
                syncSpecsJson();
            });

            // Cancel CSV import
            $('.naib-csv-cancel, .naib-csv-preview-backdrop').on('click', function() {
                $('.naib-csv-preview-modal').hide();
                $('.naib-import-mode').hide();
                $('.naib-csv-file-input').val('');
            });

            // CSV Export
            $('.naib-bulk-specs-export-csv').on('click', function() {
                var csv = '<?php esc_html_e( 'Model Code,Spec Description', 'naibabiji-b2b-product-showcase' ); ?>\n';
                $tbody.find('tr.naib-spec-row').each(function() {
                    var code = $(this).find('input[name="naib_bulk_specs[code][]"]').val();
                    var desc = $(this).find('input[name="naib_bulk_specs[description][]"]').val();
                    if (code || desc) {
                        csv += '"' + (code || '').replace(/"/g, '""') + '","' + (desc || '').replace(/"/g, '""') + '"\n';
                    }
                });
                var blob = new Blob(['﻿' + csv], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'bulk-specs-export.csv';
                a.click();
                URL.revokeObjectURL(url);
            });
        });
        </script>
        <?php
    }

    /**
     * Render Product Short Description Meta Box
     *
     * @since 1.0.0
     * @param WP_Post $post Current post object being edited
     */
    public function render_short_description_meta_box( $post ) {
        // Use the model to handle data retrieval with fallbacks
        $product = new Naibabiji_B2B_Product( $post );
        $short_description = $product->get_short_description();
        ?>
        <table class="form-table naibabiji-b2b-short-desc-wrapper">
            <tr>
                <td>
                    <label for="naibabiji_b2b_product_short_description"><?php esc_html_e( 'Product Short Description:', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <?php
                    // 确保保存时有可用的nonce
                    wp_nonce_field( 'naibabiji_b2b_product_meta_nonce', 'naibabiji_b2b_product_meta_nonce' );
                    ?>
                    <div class="naibabiji-b2b-short-desc-editor">
                        <?php
                        // 使用TinyMCE富文本编辑器
                        wp_editor(
                            $short_description,
                            'naibabiji_b2b_product_short_description',
                            array(
                                'textarea_name'  => 'naibabiji_b2b_product_short_description',
                                'textarea_rows'  => 10,
                                'editor_height'  => 220,
                                'media_buttons'  => false,
                                'teeny'          => false,
                                'drag_drop_upload' => false,
                                'quicktags'      => array(
                                    'buttons' => 'strong,em,del,ul,ol,li,link'
                                ),
                                'tinymce'        => array(
                                    'toolbar1' => 'bold,italic,underline,forecolor,bullist,numlist,link,unlink,removeformat',
                                    'toolbar2' => '',
                                    'statusbar' => false,
                                    'branding'  => false,
                                ),
                            )
                        );
                        ?>
                    </div>
                    <p class="description"><?php esc_html_e( 'Short description is used for display in product listing pages and search results, recommended to keep within 100-200 characters.', 'naibabiji-b2b-product-showcase' ); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Render Product Gallery Meta Box
     *
     * @since 1.0.0
     * @param WP_Post $post Current post object being edited
     */
    public function render_gallery_meta_box( $post ) {
        wp_nonce_field( 'naibabiji_b2b_product_meta_nonce', 'naibabiji_b2b_product_meta_nonce' );
        
        // Use the model to handle data retrieval with fallbacks
        $product = new Naibabiji_B2B_Product( $post );
        $gallery_images = $product->get_gallery_ids();
        ?>
        <div class="naibabiji-b2b-product-gallery-container">
            <div class="naibabiji-b2b-gallery-images" id="naibabiji-b2b-gallery-images">
                <?php foreach ( $gallery_images as $image_id ) : ?>
                    <div class="naibabiji-b2b-gallery-item" data-id="<?php echo esc_attr( $image_id ); ?>">
                        <?php echo wp_get_attachment_image( $image_id, 'thumbnail' ); ?>
                        <button type="button" class="naibabiji-b2b-remove-image" title="<?php esc_attr_e( 'Remove Image', 'naibabiji-b2b-product-showcase' ); ?>">×</button>
                        <input type="hidden" name="naibabiji_b2b_product_gallery[]" value="<?php echo esc_attr( $image_id ); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button" id="naibabiji-b2b-add-gallery-images"><?php esc_html_e( 'Add Images', 'naibabiji-b2b-product-showcase' ); ?></button>
            <p class="description"><?php esc_html_e( 'Select multiple images to create a product gallery. Images will be displayed in the order you arrange them.', 'naibabiji-b2b-product-showcase' ); ?></p>
        </div>
        <?php
        // 添加内联样式
        wp_add_inline_style(
            'naibabiji-b2b-product-showcase-admin-style',
            '.naibabiji-b2b-product-gallery-container {
                margin: 10px 0;
            }
            .naibabiji-b2b-gallery-images {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
                margin-bottom: 15px;
            }
            .naibabiji-b2b-gallery-item {
                position: relative;
                border: 2px solid #ddd;
                border-radius: var(--naibabiji-b2b-border-radius);
                overflow: hidden;
            }
            .naibabiji-b2b-gallery-item img {
                display: block;
                width: 100px;
                height: 100px;
                object-fit: cover;
            }
            .naibabiji-b2b-remove-image {
                position: absolute;
                top: 5px;
                right: 5px;
                background: rgba(255, 0, 0, 0.8);
                color: white;
                border: none;
                border-radius: 50%;
                width: 20px;
                height: 20px;
                cursor: pointer;
                font-size: 12px;
                line-height: 1;
            }
            .naibabiji-b2b-remove-image:hover {
                background: rgba(255, 0, 0, 1);
            }'
        );
    }
    
    /**
     * 渲染询价设置Meta Box
     *
     * @since 1.0.0
     * @param WP_Post $post 当前编辑的文章对象
     */
    public function render_inquiry_meta_box( $post ) {
        // Use the model to handle data retrieval with fallbacks
        $product = new Naibabiji_B2B_Product( $post );
        $product_data = $product->get_raw_data();

        $inquiry_url    = $product_data['inquiry_url'] ?? '';
        $inquiry_text   = $product_data['inquiry_text'] ?? '';
        $sku            = $product_data['sku'] ?? '';
        $price          = $product_data['price'] ?? '';
        $inquiry_type   = $product->get_inquiry_type();

        // 获取全局设置的默认值
        $default_inquiry_url = Naibabiji_B2B_Settings::get('default_inquiry_url', '');
        $default_inquiry_text = Naibabiji_B2B_Settings::get('inquiry_button_text', esc_html__( 'Get Quote', 'naibabiji-b2b-product-showcase' ));
        $default_inquiry_type = Naibabiji_B2B_Settings::get('default_inquiry_type', 'standard');
        ?>
        <table class="form-table">
            <tr>
                <td>
                    <label for="naibabiji_b2b_product_sku"><?php esc_html_e( 'Product SKU:', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <input type="text" id="naibabiji_b2b_product_sku" name="naibabiji_b2b_product_sku"
                           value="<?php echo esc_attr( $sku ); ?>" class="widefat"
                           placeholder="SKU-<?php echo esc_attr($post->ID); ?>">
                </td>
            </tr>
            <tr>
                <td>
                    <label for="naibabiji_b2b_product_price"><?php esc_html_e( 'Product Price:', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <input type="text" id="naibabiji_b2b_product_price" name="naibabiji_b2b_product_price"
                           value="<?php echo esc_attr( $price ); ?>" class="widefat"
                           placeholder="<?php esc_attr_e( 'e.g. 99.99 (Leave empty for No Price)', 'naibabiji-b2b-product-showcase' ); ?>">
                    <p class="description"><?php esc_html_e( 'Used for Google Structured Data. If empty, the Offer schema will be omitted.', 'naibabiji-b2b-product-showcase' ); ?></p>
                </td>
            </tr>
            <tr>
                <td>
                    <hr>
                    <label style="display:block; margin-bottom:8px; font-weight:600;"><?php esc_html_e( 'Inquiry Type:', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <label style="display:block; margin-bottom:4px;">
                        <input type="radio" name="naib_inquiry_type" value="none" <?php checked( $inquiry_type, 'none' ); ?>>
                        <?php esc_html_e( 'None — Disable inquiry for this product', 'naibabiji-b2b-product-showcase' ); ?>
                    </label>
                    <label style="display:block; margin-bottom:4px;">
                        <input type="radio" name="naib_inquiry_type" value="standard" <?php checked( $inquiry_type, 'standard' ); ?>>
                        <?php esc_html_e( 'Standard — Single-product inquiry', 'naibabiji-b2b-product-showcase' ); ?>
                    </label>
                    <div class="naib-standard-mode-hint" style="margin:4px 0 8px 20px; padding:6px 10px; background:#f0f6fc; border-left:3px solid #0A7AFF; font-size:12px; color:#555; display:none;">
                        <?php
                        $global_mode = Naibabiji_B2B_Settings::get('inquiry_mode', 'external');
                        if ($global_mode === 'form') :
                        ?>
                            <span class="dashicons dashicons-feedback" style="font-size:14px; vertical-align:middle; margin-right:4px;"></span>
                            <?php esc_html_e('Global inquiry mode: Built-in Inquiry Form. The inquiry button will open a popup form.', 'naibabiji-b2b-product-showcase'); ?>
                        <?php else : ?>
                            <span class="dashicons dashicons-admin-links" style="font-size:14px; vertical-align:middle; margin-right:4px;"></span>
                            <?php esc_html_e('Global inquiry mode: External Link. The inquiry button will redirect to the inquiry URL.', 'naibabiji-b2b-product-showcase'); ?>
                        <?php endif; ?>
                    </div>
                    <label style="display:block; margin-bottom:4px;">
                        <input type="radio" name="naib_inquiry_type" value="bulk" <?php checked( $inquiry_type, 'bulk' ); ?>>
                        <?php esc_html_e( 'Bulk — Multi-spec batch inquiry', 'naibabiji-b2b-product-showcase' ); ?>
                    </label>
                </td>
            </tr>
            <tr class="naib-standard-inquiry-fields">
                <td>
                    <label for="naibabiji_b2b_product_inquiry_text"><?php esc_html_e( 'Button Text:', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <input type="text" id="naibabiji_b2b_product_inquiry_text" name="naibabiji_b2b_product_inquiry_text"
                           value="<?php echo esc_attr( $inquiry_text ); ?>" class="widefat"
                           placeholder="<?php echo esc_attr( $default_inquiry_text ); ?>">
                </td>
            </tr>
            <tr class="naib-standard-inquiry-fields">
                <td>
                    <label for="naibabiji_b2b_product_inquiry_url"><?php esc_html_e( 'Inquiry Link:', 'naibabiji-b2b-product-showcase' ); ?></label>
                    <input type="url" id="naibabiji_b2b_product_inquiry_url" name="naibabiji_b2b_product_inquiry_url"
                           value="<?php echo esc_attr( $inquiry_url ); ?>" class="widefat"
                           placeholder="<?php echo esc_attr( $default_inquiry_url ); ?>">
                    <p class="description"><?php esc_html_e( 'Link to inquiry form. Fallbacks to global settings if empty.', 'naibabiji-b2b-product-showcase' ); ?></p>
                </td>
            </tr>
        </table>
        <script>
        jQuery(document).ready(function($) {
            var $radios = $('input[name="naib_inquiry_type"]');
            var $standardFields = $('.naib-standard-inquiry-fields');
            var $standardModeHint = $('.naib-standard-mode-hint');
            var $bulkSpecsBox = $('#naibabiji_b2b_bulk_inquiry_specs');

            function toggleFields() {
                var val = $radios.filter(':checked').val();
                if (val === 'standard') {
                    $standardFields.show();
                    $standardModeHint.show();
                } else {
                    $standardFields.hide();
                    $standardModeHint.hide();
                }
                if (val === 'bulk') {
                    if ($bulkSpecsBox.length) $bulkSpecsBox.show();
                } else {
                    if ($bulkSpecsBox.length) $bulkSpecsBox.hide();
                }
            }

            $radios.on('change', toggleFields);
            toggleFields();
        });
        </script>
        <?php
    }
    

    
    /**
     * 保存自定义字段
     * 
     * 使用WordPress核心功能update_post_meta()
     *
     * @since 1.0.0
     * @param int $post_id 当前编辑的文章ID
     */
    public function save_meta_fields( $post_id ) {
        // 验证nonce
        if ( ! isset( $_POST['naibabiji_b2b_product_meta_nonce'] ) || 
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['naibabiji_b2b_product_meta_nonce'] ) ), 'naibabiji_b2b_product_meta_nonce' ) ) {
            return;
        }
        
        // 检查用户权限
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // 检查是否为自动保存
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // 检查文章类型
        if ( get_post_type( $post_id ) !== 'naibb2pr_products' ) {
            return;
        }
        
        // Initialize product data array
        $product_data = array();
        
        // 保存产品简短描述
        if ( isset( $_POST['naibabiji_b2b_product_short_description'] ) ) {
            $short_description = wp_kses_post( wp_unslash( $_POST['naibabiji_b2b_product_short_description'] ) );
            $product_data['short_description'] = $short_description;
            update_post_meta( $post_id, '_naibabiji_b2b_product_short_description', $short_description );
            
            // Sync with post_excerpt
            remove_action( 'save_post', array( $this, 'save_meta_fields' ) );
            wp_update_post( array( 'ID' => $post_id, 'post_excerpt' => $short_description ) );
            add_action( 'save_post', array( $this, 'save_meta_fields' ) );
        }
        
        // 保存产品图册
        if ( isset( $_POST['naibabiji_b2b_product_gallery'] ) ) {
            $gallery_ids = array_map( 'absint', wp_unslash( $_POST['naibabiji_b2b_product_gallery'] ) );
            $product_data['gallery'] = $gallery_ids;
            update_post_meta( $post_id, '_naibabiji_b2b_product_gallery', $gallery_ids );
        } else {
            $product_data['gallery'] = array();
            update_post_meta( $post_id, '_naibabiji_b2b_product_gallery', array() );
        }
        
        // 保存 SKU
        $sku = isset( $_POST['naibabiji_b2b_product_sku'] ) ? sanitize_text_field( wp_unslash( $_POST['naibabiji_b2b_product_sku'] ) ) : '';
        $product_data['sku'] = $sku;
        update_post_meta( $post_id, '_naibabiji_b2b_product_sku', $sku );

        // 保存 Price
        $price = isset( $_POST['naibabiji_b2b_product_price'] ) ? sanitize_text_field( wp_unslash( $_POST['naibabiji_b2b_product_price'] ) ) : '';
        $product_data['price'] = $price;
        update_post_meta( $post_id, '_naibabiji_b2b_product_price', $price );

        // 保存询价设置
        $inquiry_url = isset( $_POST['naibabiji_b2b_product_inquiry_url'] ) ? esc_url_raw( wp_unslash( $_POST['naibabiji_b2b_product_inquiry_url'] ) ) : '';
        $inquiry_text = isset( $_POST['naibabiji_b2b_product_inquiry_text'] ) ? sanitize_text_field( wp_unslash( $_POST['naibabiji_b2b_product_inquiry_text'] ) ) : '';
        $inquiry_type = isset( $_POST['naib_inquiry_type'] ) ? sanitize_text_field( wp_unslash( $_POST['naib_inquiry_type'] ) ) : Naibabiji_B2B_Settings::get('default_inquiry_type', 'standard');

        $product_data['inquiry_url'] = $inquiry_url;
        $product_data['inquiry_text'] = $inquiry_text;
        $product_data['inquiry_type'] = $inquiry_type;

        update_post_meta( $post_id, '_naibabiji_b2b_product_inquiry_url', $inquiry_url );
        update_post_meta( $post_id, '_naibabiji_b2b_product_inquiry_text', $inquiry_text );
        update_post_meta( $post_id, '_inquiry_type', $inquiry_type );

        /*
         * Specs handling — intentional cleanup:
         * When switching from bulk to standard/none, _bulk_inquiry_specs is
         * deleted to keep postmeta clean. This is by design: stale spec data
         * serves no purpose on a non-bulk product. If the admin switches back
         * to bulk, they re-enter specs (or re-import CSV).
         */
        if ( $inquiry_type === 'bulk' && isset( $_POST['naib_bulk_specs_json'] ) ) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON decoded then per-field sanitized in loop below
            $specs = json_decode( wp_unslash( $_POST['naib_bulk_specs_json'] ), true );
            if ( is_array( $specs ) ) {
                // Sanitize each spec entry before saving
                $sanitized = array();
                foreach ( $specs as $spec ) {
                    if ( ! is_array( $spec ) ) continue;
                    $sanitized[] = array(
                        'code'        => isset( $spec['code'] ) ? sanitize_text_field( $spec['code'] ) : '',
                        'description' => isset( $spec['description'] ) ? sanitize_text_field( $spec['description'] ) : '',
                        'sort_order'  => isset( $spec['sort_order'] ) ? absint( $spec['sort_order'] ) : 0,
                    );
                }
                update_post_meta( $post_id, '_bulk_inquiry_specs', wp_json_encode( $sanitized, JSON_UNESCAPED_UNICODE ) );
            }
        } else {
            delete_post_meta( $post_id, '_bulk_inquiry_specs' );
        }

        // Update grouped meta
        update_post_meta( $post_id, '_naibabiji_b2b_product_data', $product_data );

        // Clean up old flat meta keys to prevent redundancy (Optional, but cleaner)
        // For now, we leave them or delete them if we are confident in migration.
        // delete_post_meta( $post_id, '_naibabiji_b2b_product_short_description' );
        // ...
    }
    
    /**
     * 加载管理后台脚本
     *
     * @since 1.0.0
     * @param string $hook 当前管理页面的钩子后缀
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            global $post_type;
            if ( 'naibb2pr_products' === $post_type ) {
                if ( function_exists( 'wp_enqueue_editor' ) ) {
                    wp_enqueue_editor();
                }
                wp_enqueue_media();
                wp_enqueue_script( 'jquery-ui-sortable' );
            }
        }
    }
    /**
     * 确保产品简介元框默认显示
     *
     * @since 1.0.0
     * @param array    $hidden   默认隐藏的元框ID数组
     * @param WP_Screen $screen  当前屏幕对象
     * @return array
     */
    public function show_short_description_meta_box( $hidden, $screen ) {
        if ( isset( $screen->post_type ) && 'naibb2pr_products' === $screen->post_type ) {
            $index = array_search( 'naibabiji_b2b_product_short_description_box', $hidden, true );
            if ( false !== $index ) {
                unset( $hidden[ $index ] );
            }
        }
        return $hidden;
    }
    
    /**
     * 添加产品编辑页面的自定义样式
     *
     * @since 1.0.0
     */
    public function force_show_excerpt_box() {
        global $post_type, $pagenow;
        if ( 'naibb2pr_products' === $post_type && in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
            // 添加内联样式
            wp_add_inline_style(
                'naibabiji-b2b-product-showcase-admin-style',
                '.naibabiji-b2b-short-desc-wrapper td {
                    padding: 15px 0;
                }
                .naibabiji-b2b-short-desc-editor .wp-editor-wrap {
                    max-width: 100%;
                }
                .naibabiji-b2b-short-desc-editor .wp-editor-area {
                    width: 100%;
                }'
            );
        }
    }
     
     /**
      * 确保产品文章类型支持摘要
      *
      * @since 1.0.0
      */
     public function add_excerpt_support() {
         add_post_type_support( 'naibb2pr_products', 'excerpt' );
     }

}