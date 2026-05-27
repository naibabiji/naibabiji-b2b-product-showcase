<?php
/**
 * 安全和权限检查工具类
 *
 * 提供统一的安全验证方法，包括nonce验证、权限检查和数据清理
 *
 * @package Naibabiji_B2B_Product_Showcase
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 安全和权限检查工具类
 */
class Naibabiji_B2B_Product_Security {
    
    /**
     * 获取并清理POST数据
     */
    public static function get_post_data( $key, $default = '', $sanitize_function = 'sanitize_text_field', $nonce_action = '', $nonce_name = '_wpnonce' ) {
        $key = sanitize_key( $key );
        
        if ( $key === '' ) {
            return $default;
        }
        
        if ( $nonce_action && isset( $_REQUEST[ $nonce_name ] ) ) {
            $nonce_value = sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_name ] ) );
            if ( ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
                self::log_security_event( 'CRITICAL: Nonce verification failed', array( 'field' => $nonce_name, 'action' => $nonce_action, 'key' => $key ) );
                return $default;
            }
        }
        
        if ( ! isset( $_POST[ $key ] ) ) {
            return $default;
        }
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $value = wp_unslash( $_POST[ $key ] );
        
        if ( is_array( $value ) ) {
            return ( $sanitize_function === 'sanitize_text_field' ) 
                ? self::sanitize_array( $value ) 
                : array_map( $sanitize_function, $value );
        } else {
            return ( function_exists( $sanitize_function ) )
                ? call_user_func( $sanitize_function, $value )
                : sanitize_text_field( $value );
        }
    }
    
    /**
     * 验证AJAX Nonce
     */
    public static function verify_ajax_nonce( $nonce, $action ) {
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * 检查用户是否可以编辑产品
     */
    public static function can_edit_products( $post_id = 0 ) {
        if ( $post_id ) {
            return current_user_can( 'edit_post', $post_id );
        }
        return current_user_can( 'edit_posts' );
    }

    /**
     * 检查用户是否可以管理选项
     */
    public static function can_manage_options() {
        return current_user_can( 'manage_options' );
    }

    /**
     * 清理并验证整数
     */
    public static function sanitize_int( $value, $min = null, $max = null, $default = 0 ) {
        $value = intval( $value );
        if ( null !== $min && $value < $min ) return $default;
        if ( null !== $max && $value > $max ) return $default;
        return $value;
    }

    /**
     * 清理排序字段
     */
    public static function sanitize_orderby( $value, $allowed = array( 'date', 'title', 'rand' ) ) {
        return in_array( $value, $allowed ) ? $value : 'date';
    }

    /**
     * 清理排序方向
     */
    public static function sanitize_order( $value ) {
        $value = strtoupper( $value );
        return ( 'ASC' === $value ) ? 'ASC' : 'DESC';
    }

    /**
     * 递归清理数组
     */
    private static function sanitize_array( $input_array ) {
        if ( ! is_array( $input_array ) ) {
            return sanitize_text_field( $input_array );
        }
        
        $sanitized_array = array();
        foreach ( $input_array as $key => $value ) {
            $key = sanitize_key( $key );
            if ( is_array( $value ) ) {
                $sanitized_array[ $key ] = self::sanitize_array( $value );
            } else {
                $sanitized_array[ $key ] = sanitize_text_field( $value );
            }
        }
        
        return $sanitized_array;
    }
    
    /**
     * 记录安全事件
     * 仅在调试模式下记录，不影响生产环境
     */
    public static function log_security_event( $event, $context = array() ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log( sprintf( "[Naibabiji B2B Security] %s: %s", $event, wp_json_encode( $context ) ) );
        }
    }
}

// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
// 重新启用检查，不影响其他代码
?>