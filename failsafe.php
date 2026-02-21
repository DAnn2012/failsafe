<?php
/**
 * Plugin Name: FailSafe - Fatal Error Recovery
 * Description: Automatically detect & recover from plugin or theme crashes with smart error handling and instant recovery options.
 * Version: 1.0.0
 * Author: Salim Shrestha
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: failsafe-fatal-error-recovery
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin file constant early for use throughout the plugin
if ( ! defined( 'FAILSAFE_PLUGIN_FILE' ) ) {
    define( 'FAILSAFE_PLUGIN_FILE', __FILE__ );
}

// Load the plugin loader using proper path functions
$failsafe_loader_path = plugin_dir_path( __FILE__ ) . 'includes/class-failsafe-loader.php';
if ( ! class_exists( 'FailSafe_Loader' ) && file_exists( $failsafe_loader_path ) ) {
    require_once $failsafe_loader_path;
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