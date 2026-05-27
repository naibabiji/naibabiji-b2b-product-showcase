<?php
/**
 * 自定义文章类型和分类法定义
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom post type
 *
 * @since 1.0.0
 */
function naibabiji_b2b_register_post_type() {
    $labels = array(
        'name'                  => __( 'Products', 'naibabiji-b2b-product-showcase' ),
        'singular_name'         => __( 'Product', 'naibabiji-b2b-product-showcase' ),
        'menu_name'             => __( 'Product Management', 'naibabiji-b2b-product-showcase' ),
        'name_admin_bar'        => __( 'Product', 'naibabiji-b2b-product-showcase' ),
        'add_new'               => __( 'Add New Product', 'naibabiji-b2b-product-showcase' ),
        'add_new_item'          => __( 'Add New Product', 'naibabiji-b2b-product-showcase' ),
        'new_item'              => __( 'New Product', 'naibabiji-b2b-product-showcase' ),
        'edit_item'             => __( 'Edit Product', 'naibabiji-b2b-product-showcase' ),
        'view_item'             => __( 'View Product', 'naibabiji-b2b-product-showcase' ),
        'all_items'             => __( 'All Products', 'naibabiji-b2b-product-showcase' ),
        'search_items'          => __( 'Search Products', 'naibabiji-b2b-product-showcase' ),
        'not_found'             => __( 'No Products Found', 'naibabiji-b2b-product-showcase' ),
        'not_found_in_trash'    => __( 'No Products Found in Trash', 'naibabiji-b2b-product-showcase' ),
        'featured_image'        => __( 'Product Thumbnail', 'naibabiji-b2b-product-showcase' ),
        'set_featured_image'    => __( 'Set Product Thumbnail', 'naibabiji-b2b-product-showcase' ),
        'remove_featured_image' => __( 'Remove Product Thumbnail', 'naibabiji-b2b-product-showcase' ),
        'use_featured_image'    => __( 'Use as Product Thumbnail', 'naibabiji-b2b-product-showcase' ),
    );
    
    $args = array(
        'label'                 => __( 'Products', 'naibabiji-b2b-product-showcase' ),
        'description'           => __( 'B2B Product Showcase', 'naibabiji-b2b-product-showcase' ),
        'labels'                => $labels,
        'supports'              => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 20,
        'menu_icon'             => 'dashicons-products',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => 'products',
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
        'show_in_rest'          => true,
        'rewrite'               => array(
            'slug'       => 'products',
            'with_front' => false,
        ),
    );
    
    register_post_type( 'naibb2pr_products', $args );
}

/**
 * Register product taxonomies
 *
 * @since 1.0.0
 */
function naibabiji_b2b_register_taxonomies() {
    // Ensure post type is registered
    if ( ! post_type_exists( 'naibb2pr_products' ) ) {
        return;
    }
    
    // Register product categories
    $category_labels = array(
        'name'              => __( 'Product Categories', 'naibabiji-b2b-product-showcase' ),
        'singular_name'     => __( 'Product Category', 'naibabiji-b2b-product-showcase' ),
        'search_items'      => __( 'Search Product Categories', 'naibabiji-b2b-product-showcase' ),
        'all_items'         => __( 'All Product Categories', 'naibabiji-b2b-product-showcase' ),
        'parent_item'       => __( 'Parent Product Category', 'naibabiji-b2b-product-showcase' ),
        'parent_item_colon' => __( 'Parent Product Category:', 'naibabiji-b2b-product-showcase' ),
        'edit_item'         => __( 'Edit Product Category', 'naibabiji-b2b-product-showcase' ),
        'update_item'       => __( 'Update Product Category', 'naibabiji-b2b-product-showcase' ),
        'add_new_item'      => __( 'Add New Product Category', 'naibabiji-b2b-product-showcase' ),
        'new_item_name'     => __( 'New Product Category Name', 'naibabiji-b2b-product-showcase' ),
        'menu_name'         => __( 'Product Categories', 'naibabiji-b2b-product-showcase' ),
    );

    $category_args = array(
        'hierarchical'      => true,
        'labels'            => $category_labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'product-category' ),
        'show_in_rest'      => true,
    );

    register_taxonomy( 'naibb2pr_product_category', array( 'naibb2pr_products' ), $category_args );

    // Register product tags
    $tag_labels = array(
        'name'                       => __( 'Product Tags', 'naibabiji-b2b-product-showcase' ),
        'singular_name'              => __( 'Product Tag', 'naibabiji-b2b-product-showcase' ),
        'search_items'               => __( 'Search Product Tags', 'naibabiji-b2b-product-showcase' ),
        'popular_items'              => __( 'Popular Product Tags', 'naibabiji-b2b-product-showcase' ),
        'all_items'                  => __( 'All Product Tags', 'naibabiji-b2b-product-showcase' ),
        'edit_item'                  => __( 'Edit Product Tag', 'naibabiji-b2b-product-showcase' ),
        'update_item'                => __( 'Update Product Tag', 'naibabiji-b2b-product-showcase' ),
        'add_new_item'               => __( 'Add New Product Tag', 'naibabiji-b2b-product-showcase' ),
        'new_item_name'              => __( 'New Product Tag Name', 'naibabiji-b2b-product-showcase' ),
        'separate_items_with_commas' => __( 'Separate Product Tags with commas', 'naibabiji-b2b-product-showcase' ),
        'add_or_remove_items'        => __( 'Add or remove Product Tags', 'naibabiji-b2b-product-showcase' ),
        'choose_from_most_used'      => __( 'Choose from the most used Product Tags', 'naibabiji-b2b-product-showcase' ),
        'not_found'                  => __( 'No Product Tags found', 'naibabiji-b2b-product-showcase' ),
        'menu_name'                  => __( 'Product Tags', 'naibabiji-b2b-product-showcase' ),
    );

    $tag_args = array(
        'hierarchical'          => false,
        'labels'                => $tag_labels,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'query_var'             => true,
        'rewrite'               => array( 'slug' => 'product-tag' ),
        'show_in_rest'          => true,
    );

    register_taxonomy( 'naibb2pr_product_tag', array( 'naibb2pr_products' ), $tag_args );
}