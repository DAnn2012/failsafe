<?php
/**
 * FailSafe Uninstall Script
 *
 * This file is called when the plugin is uninstalled (deleted).
 * It will clean up all plugin data if the setting is enabled.
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to delete data on uninstall
$failsafe_options = get_option('failsafe_options', array());
if (!isset($failsafe_options['delete_data_on_uninstall']) || !$failsafe_options['delete_data_on_uninstall']) {
    // User doesn't want to delete data, exit
    return;
}

global $wpdb;

// Delete plugin options
delete_option('failsafe_options');
delete_option('failsafe_version');
delete_option('failsafe_recovery');
delete_option('failsafe_activation_redirect');

// Delete error logs table
$failsafe_table_name = esc_sql( $wpdb->prefix . 'failsafe_error_logs' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS `{$failsafe_table_name}`" );

// Remove MU-plugin loader if it exists
$failsafe_mu_plugin_file = WPMU_PLUGIN_DIR . '/failsafe-mu-loader.php';
if (file_exists($failsafe_mu_plugin_file)) {
    wp_delete_file($failsafe_mu_plugin_file);
}

// Clean up any transients
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_failsafe_' ) . '%' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like( '_transient_timeout_failsafe_' ) . '%' ) );
