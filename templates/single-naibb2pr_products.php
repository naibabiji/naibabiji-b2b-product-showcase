<?php
/**
 * Single Product Page Template
 * 
 * This template is used to display detailed information for a single product
 * Themes can override this template by creating single-naibb2pr_products.php in the theme directory
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header(); ?>

<div class="naibabiji-b2b-product-single-container">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('naibabiji-b2b-product-single'); ?>>
            
            <?php
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
            $product = new Naibabiji_B2B_Product(get_the_ID());
            /**
             * Hook before product content
             * 
             * @hooked B2B_Product_Frontend_Display::display_breadcrumbs - 10
             */
            do_action('naibabiji_b2b_product_before_content', $product->get_id());
            ?>
            
            <div class="naibabiji-b2b-product-main woocommerce-style">
                <div class="naibabiji-b2b-product-images">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="naibabiji-b2b-product-featured-image">
                            <div class="naibabiji-b2b-featured-image-wrapper">
                                <?php the_post_thumbnail('large', array('class' => 'naibabiji-b2b-featured-image')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php
                    // Display product gallery thumbnails
                    $naibabiji_b2b_gallery_images = $product->get_gallery_ids();
                    if (!empty($naibabiji_b2b_gallery_images) && Naibabiji_B2B_Settings::get('enable_gallery', 1)) :
                    ?>
                        <div class="naibabiji-b2b-gallery-thumbnails">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="naibabiji-b2b-gallery-thumb active" data-image="<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>">
                                    <?php the_post_thumbnail('thumbnail', array('class' => 'naibabiji-b2b-thumb-image')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php foreach ($naibabiji_b2b_gallery_images as $naibabiji_b2b_image_id) : ?>
                                <?php
                                $naibabiji_b2b_thumb_url = wp_get_attachment_image_url($naibabiji_b2b_image_id, 'thumbnail');
                                $naibabiji_b2b_large_url = wp_get_attachment_image_url($naibabiji_b2b_image_id, 'large');
                                $naibabiji_b2b_image_alt = get_post_meta($naibabiji_b2b_image_id, '_wp_attachment_image_alt', true);
                                ?>
                                <?php if ($naibabiji_b2b_thumb_url) : ?>
                                    <div class="naibabiji-b2b-gallery-thumb" data-image="<?php echo esc_url($naibabiji_b2b_large_url); ?>">
                                        <img src="<?php echo esc_url($naibabiji_b2b_thumb_url); ?>" alt="<?php echo esc_attr($naibabiji_b2b_image_alt); ?>" class="naibabiji-b2b-thumb-image">
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="naibabiji-b2b-product-summary">
                    <div class="naibabiji-b2b-product-header">
                        <h1 class="naibabiji-b2b-product-title"><?php the_title(); ?></h1>
                        
                        <?php 
                        $naibabiji_b2b_excerpt = $product->get_short_description();
                        if ($naibabiji_b2b_excerpt && Naibabiji_B2B_Settings::get('enable_short_description', 1)) : 
                        ?>
                            <div class="naibabiji-b2b-product-short-description">
                                <?php
                                // Normalize line endings and ensure standalone inline elements
                                // (e.g. <strong>) on their own lines get proper paragraph breaks.
                                $naibabiji_b2b_formatted = str_replace( "\r\n", "\n", $naibabiji_b2b_excerpt );
                                $naibabiji_b2b_formatted = str_replace( "\r", "\n", $naibabiji_b2b_formatted );
                                // Convert single newlines between content to double newlines
                                // so wpautop() creates proper <p> tags for each block.
                                $naibabiji_b2b_formatted = preg_replace( '/\n(?=[^\n])/', "\n\n", $naibabiji_b2b_formatted );
                                echo wp_kses_post( wpautop( $naibabiji_b2b_formatted ) );
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php
                    // Display product categories and tags
                    $naibabiji_b2b_categories = get_the_terms(get_the_ID(), 'naibb2pr_product_category');
                    $naibabiji_b2b_tags = get_the_terms(get_the_ID(), 'naibb2pr_product_tag');
                    ?>
                    
                    <?php if (Naibabiji_B2B_Settings::get('show_meta', true)) : ?>
                        <div class="naibabiji-b2b-product-meta">
                            <?php if ($naibabiji_b2b_categories && !is_wp_error($naibabiji_b2b_categories)) : ?>
                                <span class="naibabiji-b2b-product-categories">
                                    <?php
                                    $naibabiji_b2b_category_links = array();
                                    foreach ($naibabiji_b2b_categories as $naibabiji_b2b_category) {
                                        $naibabiji_b2b_category_link = get_term_link($naibabiji_b2b_category);
                                        if (!is_wp_error($naibabiji_b2b_category_link)) {
                                            $naibabiji_b2b_category_links[] = '<a href="' . esc_url($naibabiji_b2b_category_link) . '">' . esc_html($naibabiji_b2b_category->name) . '</a>';
                                        }
                                    }
                                    echo wp_kses_post(implode(', ', $naibabiji_b2b_category_links));
                                    ?>
                                </span>
                            <?php endif; ?>
                            
                            <?php if ($naibabiji_b2b_tags && !is_wp_error($naibabiji_b2b_tags)) : ?>
                                <span class="naibabiji-b2b-product-tags">
                                    <?php
                                    $naibabiji_b2b_tag_links = array();
                                    foreach ($naibabiji_b2b_tags as $naibabiji_b2b_tag) {
                                        $naibabiji_b2b_tag_link = get_term_link($naibabiji_b2b_tag);
                                        if (!is_wp_error($naibabiji_b2b_tag_link)) {
                                            $naibabiji_b2b_tag_links[] = '<a href="' . esc_url($naibabiji_b2b_tag_link) . '">' . esc_html($naibabiji_b2b_tag->name) . '</a>';
                                        }
                                    }
                                    echo wp_kses_post(implode(', ', $naibabiji_b2b_tag_links));
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                        
                        <?php
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable scoped via include, not global
                        $inquiry_type = $product->get_inquiry_type();

                        // Bulk inquiry: render specs table
                        if ($inquiry_type === 'bulk') :
                            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-local variable scoped via include, not global
                            $specs = $product->get_bulk_specs();
                            include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/bulk-inquiry-specs-table.php';
                        // Standard inquiry: render standard button
                        elseif ($inquiry_type === 'standard' && $product->is_inquiry_enabled()) :
                        ?>
                            <div class="naibabiji-b2b-product-inquiry">
                                <h3 class="naibabiji-b2b-inquiry-title" style="margin: 0 0 16px 0; font-size: 18px; color: var(--naibabiji-b2b-text-color);"><?php echo esc_html__('Contact Us for Pricing', 'naibabiji-b2b-product-showcase'); ?></h3>
                                <p style="margin: 0 0 20px 0; color: var(--naibabiji-b2b-text-light); font-size: 14px;">
                                    <?php echo esc_html__('Interested in this product? Contact us now for detailed pricing and product information.', 'naibabiji-b2b-product-showcase'); ?>
                                </p>
                                <?php echo wp_kses_post(Naibabiji_B2B_Product_Frontend_Display::get_instance()->get_inquiry_button_html($product)); ?>
                            </div>
                        <?php endif; ?>
                </div>
            </div>
            
            <!-- Full-width product details area -->
            <?php if (trim(get_the_content())) : ?>
            <div class="naibabiji-b2b-product-content-wrapper">
                <div class="naibabiji-b2b-product-content">
                    <h2 class="naibabiji-b2b-product-content-title"><?php echo esc_html__('Product Details', 'naibabiji-b2b-product-showcase'); ?></h2>
                    <div class="naibabiji-b2b-product-description">
                        <?php the_content(); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            /**
             * Hook after product content
             */
            do_action('naibabiji_b2b_product_after_content', $post);
            ?>
            
        </article>
        
        <?php
        // Display related products
        $naibabiji_b2b_category_ids = wp_get_post_terms(get_the_ID(), 'naibb2pr_product_category', array('fields' => 'ids'));
        
        $naibabiji_b2b_related_args = array(
            'post_type'      => 'naibb2pr_products',
            'posts_per_page' => 4,
            'post__not_in'   => array(get_the_ID()),
            'orderby'        => 'rand',
        );
        
        // Only add tax_query if categories exist
        if (!empty($naibabiji_b2b_category_ids) && !is_wp_error($naibabiji_b2b_category_ids)) {
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Necessary for related products by category
            $naibabiji_b2b_related_args['tax_query'] = array(
                array(
                    'taxonomy' => 'naibb2pr_product_category',
                    'field' => 'term_id',
                    'terms' => $naibabiji_b2b_category_ids,
                ),
            );
        }
        
        $naibabiji_b2b_related_products = get_posts($naibabiji_b2b_related_args);
        
        // Exclude current product
        $naibabiji_b2b_filtered_products = array();
        foreach ($naibabiji_b2b_related_products as $naibabiji_b2b_product) {
            if ($naibabiji_b2b_product->ID !== get_the_ID()) {
                $naibabiji_b2b_filtered_products[] = $naibabiji_b2b_product;
                if (count($naibabiji_b2b_filtered_products) >= 4) {
                    break;
                }
            }
        }
        
        if ($naibabiji_b2b_filtered_products) :
        ?>
            <section class="naibabiji-b2b-related-products">
                <h2 class="naibabiji-b2b-section-title"><?php echo esc_html__('Related Products', 'naibabiji-b2b-product-showcase'); ?></h2>
                <div class="naibabiji-b2b-products-grid naibabiji-b2b-columns-4">
                    <?php foreach ($naibabiji_b2b_filtered_products as $naibabiji_b2b_related_product) : ?>
                        <?php
                        $post = $naibabiji_b2b_related_product;
                        setup_postdata($post);
                        ?>
                        <div class="naibabiji-b2b-product-card">
                            <?php if (has_post_thumbnail()) : ?>
                                <div class="naibabiji-b2b-product-thumbnail">
                                    <a href="<?php the_permalink(); ?>">
                                        <?php the_post_thumbnail('medium'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <div class="naibabiji-b2b-product-info">
                                <h3 class="naibabiji-b2b-product-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h3>
                                
                                <?php if (has_excerpt()) : ?>
                                    <div class="naibabiji-b2b-product-excerpt">
                                        <?php 
                                        $naibabiji_b2b_excerpt_length = Naibabiji_B2B_Settings::get('excerpt_length', 20);
                                        ?>
                                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), $naibabiji_b2b_excerpt_length)); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            </section>
        <?php endif; ?>

        <?php
        /**
         * Hook after related products
         */
        do_action('naibabiji_b2b_product_after_related_products', $post);
        ?>

    <?php endwhile; ?>
</div>

<?php get_footer(); ?>