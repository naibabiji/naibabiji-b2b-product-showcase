<?php
/**
 * Bulk Inquiry Meta Fields Handler
 *
 * Handles AJAX endpoints for bulk inquiry specs management.
 * Note: The specs meta box rendering and save_post logic
 * are integrated into class-meta-fields.php.
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Naibabiji_B2B_Bulk_Inquiry_Meta_Fields {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'wp_ajax_naib_import_bulk_specs_csv', array( $this, 'handle_csv_import_ajax' ) );
    }

    /**
     * AJAX handler for server-side CSV import.
     *
     * Accepts raw CSV content and returns parsed specs array,
     * or processes and saves directly for a given product.
     */
    public function handle_csv_import_ajax() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'naibabiji-b2b-product-showcase' ) );
        }

        $nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'naib_bulk_specs_csv_nonce' ) ) {
            wp_send_json_error( __( 'Security check failed.', 'naibabiji-b2b-product-showcase' ) );
        }

        $csv_content = isset( $_POST['csv_content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['csv_content'] ) ) : '';
        if ( empty( $csv_content ) ) {
            wp_send_json_error( __( 'No CSV content provided.', 'naibabiji-b2b-product-showcase' ) );
        }

        $rows = Naibabiji_B2B_Bulk_Inquiry_CSV_Handler::parse_specs_csv( $csv_content );

        if ( empty( $rows ) ) {
            wp_send_json_error( __( 'No valid rows found in CSV.', 'naibabiji-b2b-product-showcase' ) );
        }

        // Optionally save to a specific product
        $product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
        if ( $product_id && 'naibb2pr_products' === get_post_type( $product_id ) ) {
            $mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'append';
            if ( 'replace' !== $mode ) {
                // Append mode: merge with existing specs
                $existing = get_post_meta( $product_id, '_bulk_inquiry_specs', true );
                $existing = is_string( $existing ) ? json_decode( $existing, true ) : array();
                if ( ! is_array( $existing ) ) {
                    $existing = array();
                }
                $rows = array_merge( $existing, $rows );
            }
            // Re-index sort_order
            foreach ( $rows as $i => &$row ) {
                $row['sort_order'] = $i + 1;
            }
            unset( $row );
            update_post_meta( $product_id, '_bulk_inquiry_specs', wp_json_encode( $rows, JSON_UNESCAPED_UNICODE ) );
        }

        wp_send_json_success( array(
            'rows'  => $rows,
            'count' => count( $rows ),
        ) );
    }
}
