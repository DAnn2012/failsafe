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

// Use WP_PLUGIN_DIR for proper path resolution (supports custom plugin directories).
$failsafe_plugin_dir   = WP_PLUGIN_DIR . '/failsafe-fatal-error-recovery/';
$failsafe_handler_file = $failsafe_plugin_dir . 'includes/class-failsafe-error-handler.php';
$failsafe_main_file    = 'failsafe-fatal-error-recovery/failsafe.php';

if ( file_exists( $failsafe_handler_file ) ) {
    $failsafe_active_plugins = get_option( 'active_plugins', array() );
    if ( in_array( $failsafe_main_file, $failsafe_active_plugins, true ) ) {
        if ( ! defined( 'FAILSAFE_PLUGIN_DIR' ) ) {
            define( 'FAILSAFE_PLUGIN_DIR', $failsafe_plugin_dir );
        }
        if ( ! defined( 'FAILSAFE_PLUGIN_URL' ) ) {
            define( 'FAILSAFE_PLUGIN_URL', plugins_url( '/', $failsafe_plugin_dir . 'failsafe.php' ) );
        }
        require_once $failsafe_handler_file;
    }
}