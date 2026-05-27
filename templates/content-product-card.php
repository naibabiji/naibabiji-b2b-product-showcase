<?php
/**
 * Product Card Template
 * 
 * Used to display a single product card, supports AJAX loading
 */

if (!defined('ABSPATH')) {
    exit;
}

// Data Handling - Provided by Frontend_Display or manually instantiated
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$naibabiji_b2b_current_id = get_the_ID();
if ( ! isset( $product ) || ! is_a( $product, 'Naibabiji_B2B_Product' ) || $product->get_id() !== $naibabiji_b2b_current_id ) {
    $naibabiji_b2b_product_id = $naibabiji_b2b_current_id;
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
    $product = new Naibabiji_B2B_Product( $naibabiji_b2b_product_id );
} else {
    $naibabiji_b2b_product_id = $product->get_id();
}

// 确保摘要和分类显示变量存在
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$naibabiji_b2b_show_excerpt = isset($show_excerpt) ? (bool) $show_excerpt : true;
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
$naibabiji_b2b_show_category = isset($show_category) ? (bool) $show_category : true;
?>

<div class="naibabiji-b2b-product-card">
    <?php if (has_post_thumbnail($naibabiji_b2b_product_id)) : ?>
        <div class="naibabiji-b2b-product-thumbnail">
            <a href="<?php echo esc_url(get_permalink($naibabiji_b2b_product_id)); ?>">
                <?php echo get_the_post_thumbnail($naibabiji_b2b_product_id, 'medium'); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <div class="naibabiji-b2b-product-info">
        <h6 class="naibabiji-b2b-product-title">
            <a href="<?php echo esc_url(get_permalink($naibabiji_b2b_product_id)); ?>"><?php echo esc_html(get_the_title($naibabiji_b2b_product_id)); ?></a>
        </h6>
        
        <?php if ($naibabiji_b2b_show_excerpt) : ?>
            <div class="naibabiji-b2b-product-excerpt">
                <?php 
                $naibabiji_b2b_excerpt_length = Naibabiji_B2B_Settings::get('excerpt_length', 20);
                $naibabiji_b2b_excerpt = $product->get_short_description();
                ?>
                <p><?php echo esc_html(wp_trim_words($naibabiji_b2b_excerpt, $naibabiji_b2b_excerpt_length)); ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($naibabiji_b2b_show_category) : 
            $naibabiji_b2b_product_categories = get_the_terms($naibabiji_b2b_product_id, 'naibb2pr_product_category');
            if ($naibabiji_b2b_product_categories && !is_wp_error($naibabiji_b2b_product_categories)) :
                $naibabiji_b2b_primary_category = $naibabiji_b2b_product_categories[0];
                $naibabiji_b2b_primary_category_link = get_term_link($naibabiji_b2b_primary_category, 'naibb2pr_product_category');
                if (!is_wp_error($naibabiji_b2b_primary_category_link)) :
            ?>
                <div class="naibabiji-b2b-product-meta">
                    <span class="naibabiji-b2b-product-category">
                        <?php echo esc_html__('Category:', 'naibabiji-b2b-product-showcase'); ?>
                        <a href="<?php echo esc_url($naibabiji_b2b_primary_category_link); ?>">
                            <?php echo esc_html($naibabiji_b2b_primary_category->name); ?>
                        </a>
                    </span>
                </div>
            <?php
                endif;
            endif;
        endif; ?>
        
        <div class="naibabiji-b2b-product-actions">
            <div class="naibabiji-b2b-button-group">
                <a href="<?php echo esc_url(get_permalink($naibabiji_b2b_product_id)); ?>" class="naib-b2b-btn naib-b2b-btn--primary naibabiji-b2b-view-details-button">
                    <span class="naibabiji-b2b-button-icon">→</span><?php echo esc_html__('View Details', 'naibabiji-b2b-product-showcase'); ?>
                </a>
            </div>
        </div>
    </div>
</div>