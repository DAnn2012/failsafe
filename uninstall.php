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
$options = get_option('failsafe_options', array());
if (!isset($options['delete_data_on_uninstall']) || !$options['delete_data_on_uninstall']) {
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
$table_name = $wpdb->prefix . 'failsafe_error_logs';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Remove MU-plugin loader if it exists
$mu_plugin_file = WPMU_PLUGIN_DIR . '/failsafe-mu-loader.php';
if (file_exists($mu_plugin_file)) {
    unlink($mu_plugin_file);
}

// Clean up any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_failsafe_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_failsafe_%'");
