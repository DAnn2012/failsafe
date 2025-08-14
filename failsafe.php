<?php
/**
 * Plugin Name: FailSafe
 * Description: Advanced fatal error recovery system that catches fatal errors and provides safe recovery options.
 * Version: 1.0.0
 * Author: Salim Shrestha
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Text Domain: failsafe
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('FailSafe_Loader') && file_exists(WP_PLUGIN_DIR . '/failsafe/includes/class-failsafe-loader.php')) {
    require_once WP_PLUGIN_DIR . '/failsafe/includes/class-failsafe-loader.php';
}

/**
 * Main plugin initialization
 */
function failsafe_init() {
    return FailSafe::get_instance();
}

// Initialize the plugin
failsafe_init();

// Activation hook
register_activation_hook(__FILE__, array('FailSafe_Activator', 'activate'));

// Deactivation hook  
register_deactivation_hook(__FILE__, array('FailSafe_Activator', 'deactivate'));