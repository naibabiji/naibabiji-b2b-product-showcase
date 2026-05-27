<?php
/**
 * Product Tag Page Template
 * 
 * This template is used to display products with a specific tag
 * Themes can override this template by creating a file with the same name
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

global $wp_query;
$naibabiji_b2b_current_term = get_queried_object();
$naibabiji_b2b_hide_title = get_term_meta($naibabiji_b2b_current_term->term_id, 'naibabiji_b2b_tag_hide_title', true);
$naibabiji_b2b_hide_title = ($naibabiji_b2b_hide_title === '1');
?>

<div class="naibabiji-b2b-products-taxonomy-container">
    <?php
    // Display top SEO content
    if (class_exists('Naibabiji_B2B_Product_Taxonomy_Fields')) {
        Naibabiji_B2B_Product_Taxonomy_Fields::render_seo_content($naibabiji_b2b_current_term->term_id, 'top', 'naibb2pr_product_tag');
    }
    ?>
    
    <?php if (!$naibabiji_b2b_hide_title) : ?>
    <header class="naibabiji-b2b-taxonomy-header">
        <h1 class="naibabiji-b2b-taxonomy-title"><?php single_term_title(); ?></h1>
        
        <?php if (term_description()) : ?>
            <div class="naibabiji-b2b-taxonomy-description">
                <?php echo wp_kses_post(term_description()); ?>
            </div>
        <?php endif; ?>
        
        <div class="naibabiji-b2b-taxonomy-meta">
            <span class="naibabiji-b2b-product-count">
                <?php echo esc_html__('Total', 'naibabiji-b2b-product-showcase'); ?> <?php echo esc_html($wp_query->found_posts); ?> <?php echo esc_html__('products', 'naibabiji-b2b-product-showcase'); ?>
            </span>
        </div>
    </header>
    <?php endif; ?>
    
    <?php
    // Display breadcrumb navigation
    $naibabiji_b2b_breadcrumbs = array();
    
    $naibabiji_b2b_breadcrumbs[] = '<a href="' . esc_url(home_url('/')) . '">' . esc_html__('Home', 'naibabiji-b2b-product-showcase') . '</a>';
    $naibabiji_b2b_breadcrumbs[] = '<a href="' . esc_url(get_post_type_archive_link('naibb2pr_products')) . '">' . esc_html__('Products', 'naibabiji-b2b-product-showcase') . '</a>';
    $naibabiji_b2b_breadcrumbs[] = '<span class="current">' . esc_html($naibabiji_b2b_current_term->name) . '</span>';
    ?>
    
    <nav class="naibabiji-b2b-breadcrumbs" aria-label="<?php echo esc_attr__('Breadcrumb Navigation', 'naibabiji-b2b-product-showcase'); ?>">
        <?php echo wp_kses_post(implode(' <span class="separator">></span> ', $naibabiji_b2b_breadcrumbs)); ?>
    </nav>
    
    <?php if (have_posts()) : ?>
        <?php
        $naibabiji_b2b_grid_columns = (int) Naibabiji_B2B_Settings::get('archive_columns', 3);
        ?>
        <div class="naibabiji-b2b-products-grid naibabiji-b2b-columns-<?php echo esc_attr($naibabiji_b2b_grid_columns); ?>">
            <?php while (have_posts()) : the_post(); ?>
                <?php include NAIBABIJI_B2B_PRODUCT_SHOWCASE_PLUGIN_DIR . 'templates/content-product-card.php'; ?>
            <?php endwhile; ?>
        </div>
        
        <?php
        // Pagination navigation
        the_posts_pagination(array(
            'mid_size' => 2,
            'prev_text' => '&laquo; ' . esc_html__('Previous', 'naibabiji-b2b-product-showcase'),
            'next_text' => esc_html__('Next', 'naibabiji-b2b-product-showcase') . ' &raquo;',
            'before_page_number' => '<span class="meta-nav screen-reader-text">' . esc_html__('Page', 'naibabiji-b2b-product-showcase') . ' </span>',
            'after_page_number' => '<span class="meta-nav screen-reader-text"></span>',
        ));
        ?>
        
    <?php else : ?>
        <div class="naibabiji-b2b-no-products">
            <h2><?php echo esc_html__('No Products', 'naibabiji-b2b-product-showcase'); ?></h2>
            <p><?php echo esc_html__('Sorry, there are no products with this tag yet.', 'naibabiji-b2b-product-showcase'); ?></p>
            <p><a href="<?php echo esc_url(get_post_type_archive_link('naibb2pr_products')); ?>"><?php echo esc_html__('View All Products', 'naibabiji-b2b-product-showcase'); ?></a></p>
        </div>
    <?php endif; ?>
    
    <?php
    // Display bottom SEO content
    if (class_exists('Naibabiji_B2B_Product_Taxonomy_Fields')) {
        Naibabiji_B2B_Product_Taxonomy_Fields::render_seo_content($naibabiji_b2b_current_term->term_id, 'bottom', 'naibb2pr_product_tag');
    }
    ?>
</div>

<?php get_footer(); ?>