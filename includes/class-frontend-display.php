<?php
/**
 * Frontend Display Class
 * 
 * Responsible for handling product display logic in the frontend, including content filtering, breadcrumb navigation, Schema.org markup, etc.
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend Display Class
 *
 * @since 1.0.0
 */
class Naibabiji_B2B_Product_Frontend_Display {
    
    /**
     * Singleton instance of the class
     *
     * @since 1.0.0
     * @var Naibabiji_B2B_Product_Frontend_Display
     */
    private static $instance = null;
    
    /**
     * Get the singleton instance of the class
     *
     * @since 1.0.0
     * @return Naibabiji_B2B_Product_Frontend_Display Singleton instance of the class
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
        add_filter( 'the_content', array( $this, 'add_product_content' ) );
        add_action( 'wp_head', array( $this, 'add_schema_markup' ) );
        add_action( 'naibabiji_b2b_product_before_content', array( $this, 'display_breadcrumbs' ) );
        add_action( 'naibabiji_b2b_product_before_content', array( $this, 'inject_product_id_marker' ) );
        add_action( 'wp_footer', array( $this, 'maybe_render_inquiry_modal' ) );
        add_action( 'wp_footer', array( $this, 'render_inquiry_cart_float' ) );
    }

    /**
     * Inject a hidden marker with the product ID for the AI chat widget
     */
    public function inject_product_id_marker( $post_id ) {
        $actual_id = ( is_object( $post_id ) && isset( $post_id->ID ) ) ? $post_id->ID : $post_id;
        echo '<div id="naib-ai-product-context" data-post-id="' . esc_attr( (string)$actual_id ) . '" style="display:none;"></div>';
    }
    
    /**
     * 修改产品查询
     * 使用WordPress核心的pre_get_posts钩子
     *
     * @since 1.0.0
     * @param WP_Query $query 当前查询对象
     */
    /**
     * 为产品内容添加自定义字段显示
     * 使用WordPress核心的the_content过滤器
     * 
     * 注意：现在使用WooCommerce风格的模板，不再在这里添加产品展示内容
     *
     * @since 1.0.0
     * @param string $content 文章内容
     * @return string 过滤后的内容
     */
    public function add_product_content( $content ) {
        // 对于产品页面，现在完全由模板文件控制显示
        // 不再在内容前添加产品展示HTML
        return $content;
    }
    
    /**
     * 获取产品显示内容
     *
     * @since 1.0.0
     * @param int|WP_Post|null $post_id 产品ID或产品对象
     * @return string 产品显示HTML
     */
    private function get_product_display_content( $post_id = null ) {
        $post = get_post( $post_id );
        if ( ! $post || 'naibb2pr_products' !== $post->post_type ) {
            return '';
        }

        $product = new Naibabiji_B2B_Product( $post );
        $output = '';
        
        ob_start();
        do_action( 'naibabiji_b2b_product_before_content', $post );
        $output .= ob_get_clean();
        
        $output .= '<div class="naibabiji-b2b-product-showcase">';
        
        // Product Gallery
        if ( Naibabiji_B2B_Settings::get( 'enable_gallery', 1 ) ) {
            $gallery_html = $this->get_product_gallery_html( $product );
            if ( $gallery_html ) {
                $output .= '<div class="naibabiji-b2b-product-gallery-section">';
                $output .= '<h3>' . esc_html__( 'Product Gallery', 'naibabiji-b2b-product-showcase' ) . '</h3>';
                $output .= $gallery_html;
                $output .= '</div>';
            }
        }
        
        // Short Description
        if ( Naibabiji_B2B_Settings::get( 'enable_short_description', 1 ) ) {
            $excerpt = $product->get_short_description();
            if ( $excerpt ) {
                $output .= '<div class="naibabiji-b2b-product-excerpt">';
                $output .= '<h3>' . esc_html__( 'Product Introduction', 'naibabiji-b2b-product-showcase' ) . '</h3>';
                $output .= wpautop( wp_kses_post( $excerpt ) );
                $output .= '</div>';
            }
        }
        
        // Inquiry Button
        if ( $product->is_inquiry_enabled() ) {
            $inquiry_html = $this->get_inquiry_button_html( $product );
            if ( $inquiry_html ) {
                $output .= '<div class="naibabiji-b2b-product-inquiry-section">';
                $output .= $inquiry_html;
                if (get_option('naibabiji_b2b_ai_enable', false)) {
                    $output .= '<a href="#" class="naibabiji-b2b-ai-support-button">' . esc_html__('Online Support', 'naibabiji-b2b-product-showcase') . '</a>';
                }
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        ob_start();
        do_action('naibabiji_b2b_product_after_content', $post);
        $output .= ob_get_clean();
        
        return $output;
    }
    
    /**
     * 获取产品图集 HTML
     */
    private function get_product_gallery_html($product) {
        $gallery_images = $product->get_gallery_ids();
        
        if (empty($gallery_images)) {
            return '';
        }
        
        $output = '<div class="naibabiji-b2b-product-gallery">';
        
        foreach ($gallery_images as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
            $image_full_url = wp_get_attachment_image_url($image_id, 'full');
            $image_alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);
            
            if ($image_url) {
                $output .= '<div class="naibabiji-b2b-gallery-item">';
                $output .= '<a href="' . esc_url($image_full_url) . '" data-lightbox="product-gallery">';
                $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '">';
                $output .= '</a>';
                $output .= '</div>';
            }
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * 获取询价按钮 HTML
     */
    public function get_inquiry_button_html($product) {
        $inquiry_type = $product->get_inquiry_type();

        // Bulk inquiry — handled by template, not here
        if ($inquiry_type === 'bulk') {
            return '';
        }

        // None — no inquiry button
        if ($inquiry_type === 'none') {
            return '';
        }

        $inquiry_url  = $product->get_inquiry_url();
        $inquiry_text = $product->get_inquiry_text();
        $inquiry_mode = Naibabiji_B2B_Settings::get('inquiry_mode', 'external');

        $output = '<div class="naibabiji-b2b-inquiry-button-container">';

        if ($inquiry_mode === 'form') {
            $output .= '<button type="button" class="naib-b2b-btn naib-b2b-btn--inquiry naibabiji-b2b-inquiry-button naibabiji-b2b-trigger-inquiry-form" data-product-id="' . esc_attr($product->get_id()) . '" data-product-title="' . esc_attr(get_the_title($product->get_id())) . '">';
            $output .= esc_html($inquiry_text);
            $output .= '</button>';
        } else {
            if (empty($inquiry_url)) {
                return '';
            }
            $output .= '<a href="' . esc_url($inquiry_url) . '" class="naib-b2b-btn naib-b2b-btn--inquiry naibabiji-b2b-inquiry-button" target="_blank">';
            $output .= esc_html($inquiry_text);
            $output .= '</a>';
        }

        $output .= '</div>';

        return $output;
    }
    
    /**
     * 添加Schema.org结构化数据标记
     */
    public function add_schema_markup() {
        // 检查是否在产品单页且启用了Schema标记
        if (!is_singular('naibb2pr_products') || !Naibabiji_B2B_Settings::get('enable_schema', true)) {
            return;
        }
        
        // 使用get_queried_object()获取当前查询的对象，而不是直接使用全局$post
        $post = get_queried_object();
        
        // 验证是否为有效的产品文章
        if (!$post || !is_a($post, 'WP_Post') || $post->post_type !== 'naibb2pr_products') {
            return;
        }
        
        $schema = Naibabiji_B2B_Schema_Builder::build_product_schema($post->ID);
        
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * 显示面包屑导航
     * 
     * @param int|WP_Post $post_id 产品ID或产品对象
     */
    public function display_breadcrumbs($post_id) {
        // 检查是否启用了面包屑导航
        if (!Naibabiji_B2B_Settings::get('enable_breadcrumbs', true)) {
            return;
        }
        
        // 确保我们有一个有效的WP_Post对象
        $post = get_post($post_id);
        if (!$post || !is_a($post, 'WP_Post') || $post->post_type !== 'naibb2pr_products') {
            return;
        }
        
        $breadcrumbs = array();
        
        // 首页
        $breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'naibabiji-b2b-product-showcase') . '</a>';
        
        // 产品归档页
        $breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('naibb2pr_products')) . '">' . esc_html__('Products', 'naibabiji-b2b-product-showcase') . '</a>';
        
        // 产品分类
        $categories = get_the_terms($post, 'naibb2pr_product_category');
        if ($categories && !is_wp_error($categories)) {
            $category = $categories[0];
            $category_link = get_term_link($category);
            if (!is_wp_error($category_link)) {
                $breadcrumbs[] = '<a href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</a>';
            }
        }
        
        // 当前产品
        $breadcrumbs[] = '<span class="current">' . esc_html(get_the_title($post)) . '</span>';
        
        echo '<nav class="naibabiji-b2b-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb Navigation', 'naibabiji-b2b-product-showcase') . '">';
        echo wp_kses_post(implode(' <span class="separator">' . esc_html__('>', 'naibabiji-b2b-product-showcase') . '</span> ', $breadcrumbs));
        echo '</nav>';
    }
    
    /**
     * 获取产品网格HTML（用于短代码和归档页）
     */
    public static function get_products_grid_html($args = array()) {
        $defaults = array(
            'posts_per_page' => 8,
            'columns' => 3,
            'category' => '',
            'show_excerpt' => true,
            'show_category' => true,
            'show_view_details' => true,
            'show_inquiry' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = array(
            'post_type' => 'naibb2pr_products',
            'posts_per_page' => $args['posts_per_page'],
            'post_status' => 'publish',
            'orderby' => $args['orderby'],
            'order' => $args['order'],
        );
        
        if (!empty($args['category'])) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for category filtering
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'naibb2pr_product_category',
                    'field' => 'slug',
                    'terms' => $args['category'],
                ),
            );
        }
        
        $products = new WP_Query($query_args);
        
        if (!$products->have_posts()) {
            return '<p class="naibabiji-b2b-no-products">' . __('No products available.', 'naibabiji-b2b-product-showcase') . '</p>';
        }
        
        $output = '<div class="naibabiji-b2b-products-grid naibabiji-b2b-columns-' . esc_attr($args['columns']) . '">';
        
        // 通过模板渲染产品卡片，避免重复HTML实现
        // 修复变量名不匹配问题，传递正确的变量名
        $show_excerpt = (bool) $args['show_excerpt'];
        $show_category = (bool) $args['show_category'];
        $show_view_details = (bool) $args['show_view_details'];
        $show_inquiry = (bool) $args['show_inquiry'];
        
        while ($products->have_posts()) {
            $products->the_post();
            
            // Instantiate Product model
            $product = new Naibabiji_B2B_Product(get_the_ID());
            
            ob_start();
            include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/content-product-card.php';
            $output .= ob_get_clean();
        }
        
        $output .= '</div>';
        
        wp_reset_postdata();
        
        return $output;
    }
    
    /**
     * 获取单个产品卡片HTML
     */
    /**
     * Render the inquiry form modal in the footer if in form mode
     */
    public function maybe_render_inquiry_modal() {
        if (Naibabiji_B2B_Settings::get('inquiry_mode', 'external') !== 'form') {
            return;
        }

        // Only render on relevant pages
        if (!is_singular('naibb2pr_products') && 
            !is_post_type_archive('naibb2pr_products') && 
            !is_tax(array('naibb2pr_product_category', 'naibb2pr_product_tag'))) {
            
            // Still check if shortcode or block might be used (simplified check)
            $post = get_post();
            $has_shortcode = $post && (has_shortcode($post->post_content, 'naibabiji_b2b_products'));
            if (!$has_shortcode) {
                return;
            }
        }

        $form_fields = Naibabiji_B2B_Settings::get('inquiry_form_fields', array('name', 'email', 'message'));
        $success_msg = Naibabiji_B2B_Settings::get('inquiry_success_msg', __('Thank you! Your inquiry has been sent successfully.', 'naibabiji-b2b-product-showcase'));
        
        include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/inquiry-form-modal.php';
    }

    /**
     * Render inquiry cart float icon on all pages.
     * Injected via wp_footer. Only visible when localStorage has cart data.
     */
    public function render_inquiry_cart_float() {
        // Include the bulk inquiry form modal (hidden by default)
        include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/bulk-inquiry-form.php';
        ?>
        <div id="naib-inquiry-cart-float" class="naib-inquiry-cart-icon" style="display:none; position:fixed; top:100px; right:20px; z-index:9998; background:var(--naibabiji-b2b-primary-color); color:#fff; border-radius:50%; width:56px; height:56px; text-align:center; cursor:pointer; box-shadow:0 2px 12px rgba(0,0,0,0.2); line-height:56px; font-size:20px;">
            <span id="naib-cart-count" style="position:absolute; top:-6px; right:-6px; background:#d63638; color:#fff; border-radius:50%; width:22px; height:22px; line-height:22px; font-size:11px; text-align:center;">0</span>
            &#128722;
        </div>

        <div id="naib-inquiry-cart-sidebar" class="naib-cart-sidebar" style="display:none; position:fixed; top:0; right:0; width:360px; max-width:90vw; height:100%; background:#fff; z-index:99990; box-shadow:-2px 0 20px rgba(0,0,0,0.15); overflow-y:auto; font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            <div class="naib-cart-header" style="padding:20px; border-bottom:1px solid #e5e5e5; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:16px;"><?php esc_html_e('My Inquiry Cart', 'naibabiji-b2b-product-showcase'); ?></h3>
                <button type="button" class="naib-cart-close" style="background:none; border:none; font-size:24px; cursor:pointer; color:#666;">&times;</button>
            </div>
            <div class="naib-cart-body" style="padding:20px;" id="naib-cart-body">
                <p class="naib-cart-empty" style="text-align:center; color:#999; padding:40px 0;"><?php esc_html_e('Your inquiry cart is empty.', 'naibabiji-b2b-product-showcase'); ?></p>
                <div class="naib-cart-items" style="display:none;"></div>
                <div class="naib-cart-footer" style="display:none; border-top:1px solid #e5e5e5; padding-top:16px; margin-top:16px;">
                    <p class="naib-cart-summary" style="font-size:13px; color:#666; margin:0 0 12px;"></p>
                    <button type="button" class="naib-cart-clear" style="background:#f0f0f0; border:none; padding:8px 16px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer; margin-right:8px; font-size:13px;"><?php esc_html_e('Clear Cart', 'naibabiji-b2b-product-showcase'); ?></button>
                    <button type="button" class="naib-cart-submit" style="background:var(--naibabiji-b2b-primary-color); color:#fff; border:none; padding:8px 16px; border-radius:var(--naibabiji-b2b-border-radius); cursor:pointer; font-size:13px;"><?php esc_html_e('Submit Inquiry', 'naibabiji-b2b-product-showcase'); ?></button>
                </div>
            </div>
        </div>
        <div id="naib-cart-backdrop" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.3); z-index:99980;" class="naib-cart-backdrop"></div>

        <script>
        (function() {
            var cartKey = 'naib_inquiry_cart';
            var $float = document.getElementById('naib-inquiry-cart-float');
            var $count = document.getElementById('naib-cart-count');
            var $sidebar = document.getElementById('naib-inquiry-cart-sidebar');
            var $backdrop = document.getElementById('naib-cart-backdrop');
            var $cartBody = document.getElementById('naib-cart-body');
            var $cartItems = document.querySelector('.naib-cart-items');
            var $cartEmpty = document.querySelector('.naib-cart-empty');
            var $cartFooter = document.querySelector('.naib-cart-footer');
            var $cartSummary = document.querySelector('.naib-cart-summary');

            function getCart() {
                try {
                    var raw = localStorage.getItem(cartKey);
                    var cart = JSON.parse(raw);
                    return (cart && cart.items && cart.items.length > 0) ? cart : null;
                } catch(e) { return null; }
            }

            function saveCart(cart) {
                cart.updated_at = new Date().toISOString();
                localStorage.setItem(cartKey, JSON.stringify(cart));
            }

            function renderCart() {
                var cart = getCart();
                if (!cart) {
                    $float.style.display = 'none';
                    hideSidebar();
                    return;
                }

                var totalProducts = cart.items.length;
                var totalSpecs = 0;
                cart.items.forEach(function(p) { totalSpecs += (p.specs ? p.specs.length : 0); });

                if (totalSpecs === 0) {
                    $float.style.display = 'none';
                    hideSidebar();
                    return;
                }

                $float.style.display = 'block';
                $count.textContent = totalProducts;

                // Render sidebar content
                var html = '';
                cart.items.forEach(function(product, pi) {
                    html += '<div class="naib-cart-product" style="margin-bottom:16px; padding-bottom:12px; border-bottom:1px solid #f0f0f0;">';
                    html += '<p style="font-weight:600; margin:0 0 8px; font-size:14px;">' + encodeHTML(product.product_name || 'Product #' + product.product_id) + '</p>';
                    (product.specs || []).forEach(function(spec, si) {
                        html += '<div class="naib-cart-spec" style="display:flex; align-items:center; gap:8px; margin-bottom:6px; font-size:13px;">';
                        html += '<span style="flex:1;">' + encodeHTML(spec.code) + ' <span style="color:#666;">(' + encodeHTML(spec.description) + ')</span></span>';
                        html += '<input type="number" class="naib-cart-qty" data-product="' + pi + '" data-spec="' + si + '" value="' + (spec.quantity || 0) + '" min="0" style="width:58px; padding:2px 4px; border:1px solid #ddd; border-radius:var(--naibabiji-b2b-border-radius); text-align:center;">';
                        html += '<button type="button" class="naib-cart-spec-remove" data-product="' + pi + '" data-spec="' + si + '" style="background:none; border:none; color:#d63638; cursor:pointer; font-size:16px;">&times;</button>';
                        html += '</div>';
                    });
                    html += '<button type="button" class="naib-cart-product-remove" data-product="' + pi + '" style="font-size:11px; color:#999; background:none; border:none; cursor:pointer;"><?php esc_html_e('Remove product', 'naibabiji-b2b-product-showcase'); ?></button>';
                    html += '</div>';
                });

                $cartItems.innerHTML = html;
                $cartItems.style.display = 'block';
                $cartEmpty.style.display = 'none';
                $cartFooter.style.display = 'block';
                $cartSummary.textContent = totalProducts + ' <?php esc_html_e('products,', 'naibabiji-b2b-product-showcase'); ?> ' + totalSpecs + ' <?php esc_html_e('specs', 'naibabiji-b2b-product-showcase'); ?>';

                // Bind events
                bindCartEvents();
            }

            function encodeHTML(str) {
                var div = document.createElement('div');
                div.appendChild(document.createTextNode(str));
                return div.innerHTML;
            }

            function showSidebar() { $sidebar.style.display = 'block'; $backdrop.style.display = 'block'; renderCart(); }
            function hideSidebar() { $sidebar.style.display = 'none'; $backdrop.style.display = 'none'; }

            function bindCartEvents() {
                $cartItems.querySelectorAll('.naib-cart-qty').forEach(function(input) {
                    input.removeEventListener('change', handleQtyChange);
                    input.addEventListener('change', handleQtyChange);
                });
                $cartItems.querySelectorAll('.naib-cart-spec-remove').forEach(function(btn) {
                    btn.removeEventListener('click', handleSpecRemove);
                    btn.addEventListener('click', handleSpecRemove);
                });
                $cartItems.querySelectorAll('.naib-cart-product-remove').forEach(function(btn) {
                    btn.removeEventListener('click', handleProductRemove);
                    btn.addEventListener('click', handleProductRemove);
                });
            }

            function handleQtyChange() {
                var cart = getCart(); if (!cart) return;
                var pi = parseInt(this.dataset.product);
                var si = parseInt(this.dataset.spec);
                var qty = parseInt(this.value) || 0;
                if (qty < 0) qty = 0;
                if (cart.items[pi] && cart.items[pi].specs && cart.items[pi].specs[si]) {
                    cart.items[pi].specs[si].quantity = qty;
                }
                saveCart(cart);
                renderCart();
            }

            function handleSpecRemove() {
                var cart = getCart(); if (!cart) return;
                var pi = parseInt(this.dataset.product);
                var si = parseInt(this.dataset.spec);
                if (cart.items[pi] && cart.items[pi].specs) {
                    cart.items[pi].specs.splice(si, 1);
                    if (cart.items[pi].specs.length === 0) {
                        cart.items.splice(pi, 1);
                    }
                }
                if (cart.items.length === 0) {
                    localStorage.removeItem(cartKey);
                    $float.style.display = 'none';
                    hideSidebar();
                    return;
                }
                saveCart(cart);
                renderCart();
            }

            function handleProductRemove() {
                var cart = getCart(); if (!cart) return;
                var pi = parseInt(this.dataset.product);
                cart.items.splice(pi, 1);
                if (cart.items.length === 0) {
                    localStorage.removeItem(cartKey);
                    $float.style.display = 'none';
                    hideSidebar();
                    return;
                }
                saveCart(cart);
                renderCart();
            }

            // Event listeners — all use null guards to prevent IIFE crash
            if ($float) {
                $float.addEventListener('click', function() { showSidebar(); });
            }
            var cartClose = document.querySelector('.naib-cart-close');
            if (cartClose) cartClose.addEventListener('click', function() { hideSidebar(); });
            if ($backdrop) $backdrop.addEventListener('click', function() { hideSidebar(); });

            var cartClear = document.querySelector('.naib-cart-clear');
            if (cartClear) cartClear.addEventListener('click', function() {
                localStorage.removeItem(cartKey);
                $float.style.display = 'none';
                hideSidebar();
            });

            // Submit inquiry button — prefer in-page modal, fall back to navigation
            var cartSubmit = document.querySelector('.naib-cart-submit');
            if (cartSubmit) cartSubmit.addEventListener('click', function() {
                console.log('[BulkInquiry:Cart] Submit button clicked');
                var cart = getCart();
                console.log('[BulkInquiry:Cart] Cart data:', cart);
                if (!cart || !cart.items || !cart.items.length) {
                    console.warn('[BulkInquiry:Cart] Cart is empty, aborting');
                    return;
                }

                console.log('[BulkInquiry:Cart] naib_bulk_open_form type:', typeof window.naib_bulk_open_form);
                console.log('[BulkInquiry:Cart] Modal element:', document.getElementById('naib-bulk-inquiry-form-modal'));

                // Best path: open the modal directly (bulk-inquiry.js is loaded)
                if (typeof window.naib_bulk_open_form === 'function') {
                    console.log('[BulkInquiry:Cart] Calling naib_bulk_open_form...');
                    window.naib_bulk_open_form(cart);
                    console.log('[BulkInquiry:Cart] naib_bulk_open_form returned');
                    hideSidebar();
                    return;
                }

                console.log('[BulkInquiry:Cart] naib_bulk_open_form not available, falling back to navigation');
                // Fallback: navigate to the first product page that has a valid URL
                for (var i = 0; i < cart.items.length; i++) {
                    var url = cart.items[i].product_url;
                    console.log('[BulkInquiry:Cart] Item ' + i + ' product_url:', url);
                    if (url) {
                        var hashIdx = url.indexOf('#');
                        if (hashIdx !== -1) url = url.substring(0, hashIdx);
                        console.log('[BulkInquiry:Cart] Navigating to:', url + '#bulk-inquiry');
                        window.location.href = url + '#bulk-inquiry';
                        return;
                    }
                }

                var pid = cart.items[0] && cart.items[0].product_id;
                console.log('[BulkInquiry:Cart] No URL found, trying product_id:', pid);
                if (pid) {
                    window.location.href = window.location.origin + '/?p=' + pid + '#bulk-inquiry';
                }
            });

            function updateFloatBadge() {
                var cart = getCart();
                if (!cart || !cart.items || cart.items.length === 0) {
                    $float.style.display = 'none';
                    $count.textContent = '0';
                    return;
                }
                var hasAny = false;
                var totalProducts = cart.items.length;
                cart.items.forEach(function(p) {
                    if (p.specs && p.specs.length > 0) hasAny = true;
                });
                if (hasAny) {
                    $float.style.display = 'block';
                    $count.textContent = totalProducts;
                } else {
                    $float.style.display = 'none';
                    $count.textContent = '0';
                }
            }

            // Listen for cart updates from bulk-inquiry.js
            document.addEventListener('naib_cart_updated', function() {
                updateFloatBadge();
                if ($sidebar.style.display === 'block') renderCart();
            });

            // Initialize on load
            updateFloatBadge();
            if ($float.style.display === 'block') {
                // Pre-render sidebar content so it's ready when opened
                renderCart();
            }

            // AI float position detection
            var aiPosition = document.body.style.getPropertyValue('--ai-float-position').trim() || 'bottom-right';
            if (aiPosition === 'top-right') {
                $float.style.top = '80px';
            }
        })();
        </script>
        <?php
    }
}