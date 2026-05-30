<?php
/**
 * Uninstall handler
 *
 * Cleans up all plugin data when the plugin is deleted via WordPress admin.
 * This file is only loaded by WordPress when the plugin is fully deleted,
 * not on deactivation.
 *
 * @package Naibabiji_B2B_Product_Showcase
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Clear scheduled cron events
$timestamp = wp_next_scheduled('naibabiji_b2b_weekly_cleanup');
if ($timestamp) {
    wp_unschedule_event($timestamp, 'naibabiji_b2b_weekly_cleanup');
}

// Drop custom leads table
global $wpdb;
$table = $wpdb->prefix . 'naibb2pr_ai_leads';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query("DROP TABLE IF EXISTS `{$table}`");

// Delete all plugin options (naibabiji_b2b_*)
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM $wpdb->options WHERE option_name LIKE %s",
        'naibabiji_b2b_%'
    )
);

// Clean up rate-limit transients
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM $wpdb->options WHERE option_name LIKE %s OR option_name LIKE %s",
        '_transient_naib%',
        '_transient_timeout_naib%'
    )
);

// Flush object cache
wp_cache_flush();
