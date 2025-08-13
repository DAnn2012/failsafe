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

// Define plugin constants
define('FAILSAFE_VERSION', '1.0.0');
define('FAILSAFE_PLUGIN_FILE', __FILE__);
define('FAILSAFE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FAILSAFE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FAILSAFE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once FAILSAFE_PLUGIN_DIR . 'includes/class-loader.php';
FailSafe_Loader::load();

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