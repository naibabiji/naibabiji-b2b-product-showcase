<?php
/**
 * Bulk Inquiry CSV Handler
 *
 * Provides CSV security utilities and server-side import/export capabilities
 * for bulk inquiry specs management.
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Naibabiji_B2B_Bulk_Inquiry_CSV_Handler {

    /**
     * Sanitize a CSV cell value to prevent CSV injection attacks.
     *
     * If a cell starts with =, +, -, @, tab, or carriage return,
     * Excel may interpret it as a formula.
     *
     * @param string $value The cell value.
     * @return string Sanitized value.
     */
    public static function sanitize_csv_cell( $value ) {
        $dangerous_chars = array( '=', '+', '-', '@', "\t", "\r" );
        $first_char = mb_substr( $value, 0, 1 );
        if ( in_array( $first_char, $dangerous_chars, true ) ) {
            return "'" . $value;
        }
        return $value;
    }

    /**
     * Parse a CSV string into an array of rows.
     *
     * @param string $csv_content Raw CSV content.
     * @return array Array of associative arrays with 'code' and 'description' keys.
     */
    public static function parse_specs_csv( $csv_content ) {
        $rows = array();
        $lines = explode( "\n", str_replace( "\r\n", "\n", $csv_content ) );
        $lines = array_filter( $lines, function( $line ) {
            return trim( $line ) !== '';
        } );

        if ( count( $lines ) < 2 ) {
            return $rows; // Need at least header + one data row
        }

        // Skip header line
        array_shift( $lines );

        foreach ( $lines as $index => $line ) {
            if ( $index >= 1000 ) {
                break; // Max 1000 data rows
            }
            $cols = str_getcsv( $line );
            if ( count( $cols ) >= 2 && trim( $cols[0] ) !== '' && trim( $cols[1] ) !== '' ) {
                $rows[] = array(
                    'code'        => trim( $cols[0] ),
                    'description' => trim( $cols[1] ),
                    'sort_order'  => count( $rows ) + 1,
                );
            }
        }

        return $rows;
    }

    /**
     * Generate CSV content from specs array.
     *
     * @param array $specs Array of spec arrays.
     * @return string CSV content with UTF-8 BOM.
     */
    public static function generate_specs_csv( $specs ) {
        $output = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
        $output .= "Model Code,Spec Description\n";

        foreach ( $specs as $spec ) {
            $code = self::sanitize_csv_cell( isset( $spec['code'] ) ? $spec['code'] : '' );
            $desc = self::sanitize_csv_cell( isset( $spec['description'] ) ? $spec['description'] : '' );
            $output .= '"' . str_replace( '"', '""', $code ) . '","' . str_replace( '"', '""', $desc ) . '"' . "\n";
        }

        return $output;
    }

    /**
     * Validate CSV file upload.
     *
     * @param array $file $_FILES element.
     * @return string|WP_Error File content on success, WP_Error on failure.
     */
    public static function validate_csv_upload( $file ) {
        if ( ! isset( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'no_file', __( 'No file uploaded.', 'naibabiji-b2b-product-showcase' ) );
        }

        // Check extension
        $ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( 'csv' !== $ext ) {
            return new WP_Error( 'invalid_type', __( 'Only .csv files are allowed.', 'naibabiji-b2b-product-showcase' ) );
        }

        // Check file size (max 2MB)
        if ( $file['size'] > 2 * 1024 * 1024 ) {
            return new WP_Error( 'file_too_large', __( 'File too large. Maximum 2MB.', 'naibabiji-b2b-product-showcase' ) );
        }

        // Read content with encoding detection
        $content = file_get_contents( $file['tmp_name'] );
        if ( false === $content ) {
            return new WP_Error( 'read_error', __( 'Failed to read file.', 'naibabiji-b2b-product-showcase' ) );
        }

        // Handle encoding
        $encoding = mb_detect_encoding( $content, array( 'UTF-8', 'GBK', 'GB2312', 'ISO-8859-1' ), true );
        if ( $encoding && 'UTF-8' !== $encoding ) {
            $content = mb_convert_encoding( $content, 'UTF-8', $encoding );
        }

        return $content;
    }
}
