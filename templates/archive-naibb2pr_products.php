<?php
/**
 * Product Archive Page Template
 * 
 * This template is used to display the product list page (/naibb2pr_products/)
 * Themes can override this template by creating archive-naibb2pr_products.php in the theme directory
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$naibabiji_b2b_hide_title = (bool) Naibabiji_B2B_Settings::get('archive_hide_title', false);
$naibabiji_b2b_seo_top = Naibabiji_B2B_Settings::get('archive_seo_content_top', '');
$naibabiji_b2b_enable_seo_top = (bool) Naibabiji_B2B_Settings::get('archive_enable_seo_top', true);
$naibabiji_b2b_seo_bottom = Naibabiji_B2B_Settings::get('archive_seo_content_bottom', '');
$naibabiji_b2b_enable_seo_bottom = (bool) Naibabiji_B2B_Settings::get('archive_enable_seo_bottom', true);
$naibabiji_b2b_display_mode = Naibabiji_B2B_Settings::get('archive_display_mode', 'default');
$naibabiji_b2b_grid_columns = (int) Naibabiji_B2B_Settings::get('archive_columns', 3);
?>

<div class="naibabiji-b2b-products-archive-container">
    <?php
    // Display top SEO content
    if ($naibabiji_b2b_enable_seo_top && !empty($naibabiji_b2b_seo_top)) : ?>
        <div class="naibabiji-b2b-seo-content naibabiji-b2b-seo-top">
            <?php echo do_shortcode(wp_kses_post($naibabiji_b2b_seo_top)); ?>
        </div>
    <?php endif; ?>

    <?php if (!$naibabiji_b2b_hide_title) : ?>
    <header class="naibabiji-b2b-archive-header">
        <h1 class="naibabiji-b2b-archive-title">
            <?php
            if (is_tax()) {
                single_term_title();
            } else {
                echo esc_html__('Products', 'naibabiji-b2b-product-showcase');
            }
            ?>
        </h1>

        <?php if (is_tax() && term_description()) : ?>
            <div class="naibabiji-b2b-archive-description">
                <?php echo wp_kses_post(term_description()); ?>
            </div>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <?php
    // Display product category filters (only in default mode)
    if ($naibabiji_b2b_display_mode === 'default') :
        $naibabiji_b2b_categories = get_terms(array(
            'taxonomy' => 'naibb2pr_product_category',
            'hide_empty' => true,
            'parent' => 0,
        ));

        if ($naibabiji_b2b_categories && !is_wp_error($naibabiji_b2b_categories)) :
        ?>
            <div class="naibabiji-b2b-product-filters">
                <h3><?php echo esc_html__('Categories:', 'naibabiji-b2b-product-showcase'); ?></h3>
                <div class="naibabiji-b2b-category-buttons">
                    <a href="<?php echo esc_url(get_post_type_archive_link('naibb2pr_products')); ?>"
                       class="naibabiji-b2b-category-button <?php echo !is_tax() ? 'active' : ''; ?>">
                        <?php echo esc_html__('All', 'naibabiji-b2b-product-showcase'); ?>
                    </a>
                    <?php foreach ($naibabiji_b2b_categories as $naibabiji_b2b_category) : ?>
                        <?php
                        $naibabiji_b2b_category_link = get_term_link($naibabiji_b2b_category);
                        if (is_wp_error($naibabiji_b2b_category_link)) {
                            continue;
                        }
                        ?>
                        <a href="<?php echo esc_url($naibabiji_b2b_category_link); ?>"
                           class="naibabiji-b2b-category-button">
                            <?php echo esc_html($naibabiji_b2b_category->name); ?>
                            <span class="count">(<?php echo esc_html($naibabiji_b2b_category->count); ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($naibabiji_b2b_display_mode === 'categories_only') : ?>
        <?php
        // Categories-only mode: display category cards in a grid
        $naibabiji_b2b_categories = get_terms(array(
            'taxonomy' => 'naibb2pr_product_category',
            'hide_empty' => true,
            'parent' => 0,
        ));

        if ($naibabiji_b2b_categories && !is_wp_error($naibabiji_b2b_categories)) :
        ?>
            <div class="naibabiji-b2b-categories-grid naibabiji-b2b-columns-<?php echo esc_attr($naibabiji_b2b_grid_columns); ?>">
                <?php foreach ($naibabiji_b2b_categories as $naibabiji_b2b_category) :
                    $naibabiji_b2b_category_link = get_term_link($naibabiji_b2b_category);
                    if (is_wp_error($naibabiji_b2b_category_link)) {
                        continue;
                    }
                    $naibabiji_b2b_category_image_id = get_term_meta($naibabiji_b2b_category->term_id, 'naibabiji_b2b_category_image', true);
                    $naibabiji_b2b_category_image_url = $naibabiji_b2b_category_image_id ? wp_get_attachment_image_url($naibabiji_b2b_category_image_id, 'medium') : '';
                ?>
                    <div class="naibabiji-b2b-category-card">
                        <a href="<?php echo esc_url($naibabiji_b2b_category_link); ?>" class="naibabiji-b2b-category-card-link">
                            <div class="naibabiji-b2b-category-thumbnail">
                                <?php if ($naibabiji_b2b_category_image_url) : ?>
                                    <img src="<?php echo esc_url($naibabiji_b2b_category_image_url); ?>" alt="<?php echo esc_attr($naibabiji_b2b_category->name); ?>" />
                                <?php else : ?>
                                    <div class="naibabiji-b2b-category-no-image">
                                        <span><?php echo esc_html__('No Image', 'naibabiji-b2b-product-showcase'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="naibabiji-b2b-category-card-info">
                                <h6 class="naibabiji-b2b-category-card-title"><?php echo esc_html($naibabiji_b2b_category->name); ?></h6>
                                <span class="naibabiji-b2b-category-card-count"><?php echo esc_html($naibabiji_b2b_category->count); ?> <?php echo esc_html__('products', 'naibabiji-b2b-product-showcase'); ?></span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="naibabiji-b2b-no-products">
                <h2><?php echo esc_html__('No Categories Found', 'naibabiji-b2b-product-showcase'); ?></h2>
                <p><?php echo esc_html__('No product categories have been created yet.', 'naibabiji-b2b-product-showcase'); ?></p>
            </div>
        <?php endif; ?>
    <?php else : ?>
        <?php if (have_posts()) : ?>
            <div class="naibabiji-b2b-products-grid naibabiji-b2b-columns-<?php echo esc_attr($naibabiji_b2b_grid_columns); ?>">
                <?php while (have_posts()) : the_post(); ?>
                    <?php include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/content-product-card.php'; ?>
                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => '&laquo; ' . esc_html__('Previous', 'naibabiji-b2b-product-showcase'),
                'next_text' => esc_html__('Next', 'naibabiji-b2b-product-showcase') . ' &raquo;',
                'before_page_number' => '<span class="meta-nav screen-reader-text">' . esc_html__('Page', 'naibabiji-b2b-product-showcase') . ' </span>',
                'after_page_number' => '<span class="meta-nav screen-reader-text"></span>',
            ));
            ?>

        <?php else : ?>
            <div class="naibabiji-b2b-no-products">
                <h2><?php echo esc_html__('No Products Found', 'naibabiji-b2b-product-showcase'); ?></h2>
                <p><?php echo esc_html__('Sorry, no products were found.', 'naibabiji-b2b-product-showcase'); ?></p>

                <?php if (is_tax()) : ?>
                    <p><a href="<?php echo esc_url(get_post_type_archive_link('naibb2pr_products')); ?>"><?php echo esc_html__('View All Products', 'naibabiji-b2b-product-showcase'); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php
    // Display bottom SEO content
    if ($naibabiji_b2b_enable_seo_bottom && !empty($naibabiji_b2b_seo_bottom)) : ?>
        <div class="naibabiji-b2b-seo-content naibabiji-b2b-seo-bottom">
            <?php echo do_shortcode(wp_kses_post($naibabiji_b2b_seo_bottom)); ?>
        </div>
    <?php endif; ?>
</div>

<?php get_footer();
