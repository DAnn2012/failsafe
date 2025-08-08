<?php
/**
 * FailSafe MU-Plugin Loader
 * 
 * This file is automatically copied to mu-plugins when FailSafe is activated.
 * It loads the FailSafe error handler before any other plugins or themes.
 * 
 * Plugin Name: FailSafe Loader
 * Description: Early loader for FailSafe error handling system.
 * Version: 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if FailSafe plugin exists and is active
$failsafe_plugin_dir = WP_PLUGIN_DIR . '/failsafe/';
$failsafe_handler_file = $failsafe_plugin_dir . 'includes/early-handler.php';

if (file_exists($failsafe_handler_file)) {
    // Check if plugin is active
    $active_plugins = get_option('active_plugins', array());
    if (in_array('failsafe/failsafe.php', $active_plugins)) {
        // Load the early error handler
        require_once $failsafe_handler_file;
    }
}