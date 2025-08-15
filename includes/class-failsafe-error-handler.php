<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FailSafe Error Handler
 * 
 * This file contains the error handling logic that gets loaded by the MU-plugin.
 * It handles fatal errors that occur both before and after WordPress fully loads.
 */
class FailSafe_Error_Handler {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
        
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        if (!class_exists('FailSafe_Loader') && file_exists(WP_PLUGIN_DIR . '/failsafe/includes/class-failsafe-loader.php')) {
            require_once WP_PLUGIN_DIR . '/failsafe/includes/class-failsafe-loader.php';
        }

        register_shutdown_function(array(__CLASS__, 'handle_fatal_error'));
        
        add_action('muplugins_loaded', array(__CLASS__, 'handle_recovery_action'));

        add_action('wp_loaded', function() {
            if (!has_action('shutdown', array(__CLASS__, 'handle_fatal_error'))) {
                add_action('shutdown', array(__CLASS__, 'handle_fatal_error'));
            }
        });
    }

    /**
     * Error handler for all fatal errors
     * @return void
     */
    public static function handle_fatal_error() {
        $error = error_get_last();
        
        if (!$error || !FailSafe_Options::is_error_type_enabled($error['type'])) {
            return;
        }
        
        $file = $error['file'];
        $line = $error['line'];
        $message = $error['message'];
        
        $error_hash = hash('sha256', $file . ':' . $line . ':' . $message);
        
        $plugin_theme_info = FailSafe_Helpers::detect_plugin_or_theme($file);
        
        if (FailSafe_Options::is_logging_enabled()) {
            self::log_error($error_hash, $error, $plugin_theme_info);
        }
        
        self::show_recovery_message($error, $plugin_theme_info);
    }
    
    /**
     * Handle recovery actions from GET parameters
     */
    public static function handle_recovery_action() {
        if (!isset($_GET['failsafe_action']) || !isset($_GET['failsafe_hash'])) {
            return;
        }
        
        $action = FailSafe_Helpers::sanitize_input($_GET['failsafe_action']);
        $hash = FailSafe_Helpers::sanitize_input($_GET['failsafe_hash']);
        $theme = isset($_GET['failsafe_theme']) ? FailSafe_Helpers::sanitize_input($_GET['failsafe_theme']) : '';
        $plugins = isset($_GET['failsafe_plugins']) ? FailSafe_Helpers::sanitize_input($_GET['failsafe_plugins']) : array();
        
        if ( FailSafe_Options::validate_recovery_hash($hash) ) {
            if ($action === 'deactivate_plugins') {
                self::deactivate_plugins($plugins, $hash);
            } elseif ($action === 'switch_theme') {
                self::switch_theme($theme);
            }
        }
    }

    /**
     * Log error to database
     */
    private static function log_error($error_hash, $error, $plugin_theme_info) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        // Check if error already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE error_hash = %s",
            $error_hash
        ));
        
        if ($existing) {
            // Update timestamp of existing error
            $wpdb->update(
                $table_name,
                array('error_time' => current_time('mysql')),
                array('id' => $existing),
                array('%s'),
                array('%d')
            );
            return;
        }
        
        // Insert new error
        $wpdb->insert(
            $table_name,
            array(
                'error_hash' => $error_hash,
                'error_type' => FailSafe_Helpers::get_error_type_name($error['type']),
                'error_message' => $error['message'],
                'error_file' => $error['file'],
                'error_line' => $error['line'],
                'plugin_theme_path' => $plugin_theme_info['path'],
                'plugin_theme_type' => $plugin_theme_info['type'],
                'error_time' => current_time('mysql'),
                'status' => 'pending'
            ),
            array(
                '%s', // error_hash
                '%s', // error_type
                '%s', // error_message
                '%s', // error_file
                '%d', // error_line
                '%s', // plugin_theme_path
                '%s', // plugin_theme_type
                '%s', // error_time
                '%s'  // status
            )
        );
    }
    
    /**
     * Show comprehensive recovery message with all plugins and themes
     */
    private static function show_recovery_message($error, $plugin_theme_info) {
        // Check if admin-only recovery is enabled
        $options = FailSafe_Options::get();
        $admin_only_recovery = isset($options['admin_only_recovery']) ? $options['admin_only_recovery'] : false;
        
        // If admin-only recovery is enabled and we're not in admin area, don't show recovery interface
        if ($admin_only_recovery && !is_admin()) {
            return;
        }
        
        $failsafe_recovery = get_option('failsafe_recovery', array());
        if ( !isset($failsafe_recovery['hash']) ) {
            $error_hash = hash('sha256', $error['file'] . ':' . $error['line'] . ':' . $error['message'] . ':' . time());
            $failsafe_recovery = $plugin_theme_info ? $plugin_theme_info : array('type' => 'unknown', 'name' => 'Unknown', 'path' => '');
            $failsafe_recovery['hash'] = $error_hash;

            update_option('failsafe_recovery', $failsafe_recovery);
        } else {
            $error_hash = $failsafe_recovery['hash'];
        }
        
        $current_url = FailSafe_Helpers::get_current_url();

        // Prepare variables for comprehensive recovery template
        $causing_item = $plugin_theme_info ? FailSafe_Helpers::escape_html($plugin_theme_info['name']) : 'Unknown Component';
        $causing_type = $plugin_theme_info ? $plugin_theme_info['type'] : 'unknown';

        // Get all active plugins and available themes
        $all_active_plugins = FailSafe_Helpers::get_active_plugins_with_names();
        $available_themes = FailSafe_Helpers::get_available_themes();
        $current_theme = get_option('stylesheet');
        
        // Filter out protected plugins
        $protected_plugins = isset($options['protected_plugins']) ? $options['protected_plugins'] : array();
        $active_plugins = array();
        foreach ($all_active_plugins as $plugin_path => $plugin_name) {
            if (!in_array($plugin_path, $protected_plugins)) {
                $active_plugins[$plugin_path] = $plugin_name;
            }
        }

        // Build base URL for actions
        $base_url = $current_url . (strpos($current_url, '?') !== false ? '&' : '?');
        
        // Check if error details should be shown
        $show_error_details = isset($options['show_error_details']) ? $options['show_error_details'] : true;
        
        include __DIR__ . '/../templates/recovery-message.php';
    }
    
    /**
     * Auto-disable plugin or theme (original behavior)
     */
    private static function switch_theme($theme_slug) {
        $recovery_options = FailSafe_Options::get_recovery_options();

        $type = $recovery_options['type'];
        $path = $recovery_options['path'];
        $redirect_url = '';

        if ($type === 'theme' && $path) {

            $theme_valid = false;
            
            if (function_exists('wp_get_theme')) {
                $theme = wp_get_theme($theme_slug);
                $theme_valid = $theme->exists() && !$theme->errors();
            } else {
                // Fallback check - verify theme directory exists
                $themes_dir = get_theme_root();
                $theme_path = $themes_dir . '/' . $theme_slug;
                $theme_valid = is_dir($theme_path) && file_exists($theme_path . '/style.css');
            }
            
            if ($theme_valid) {
                // Get current theme info for cleanup
                $current_stylesheet = get_option('stylesheet');
                
                // Manually switch theme options in database
                update_option('template', $theme_slug);
                update_option('stylesheet', $theme_slug);
                
                // Clear cached theme mods to prevent issues
                if ($current_stylesheet) {
                    delete_option("theme_mods_$current_stylesheet");
                }
                
                // Update recovery info
                $failsafe_recovery['recovered'] = true;
                $failsafe_recovery['switched_to'] = $theme_slug;
                $failsafe_recovery['recovery_method'] = 'manual_db_switch';
                
                // Redirect to admin to complete the theme switch
                $redirect_url = admin_url();

            }
        }

        update_option('failsafe_recovery', $failsafe_recovery);
        
        header('Location: ' . $redirect_url);
        exit;
    }

    /**
     * Deactivate multiple plugins
     */
    private static function deactivate_plugins($plugins, $hash) {
        if (!is_array($plugins) || empty($plugins)) {
            return;
        }
        
        $active_plugins = get_option('active_plugins', array());
        $deactivated_plugins = array();
        
        foreach ($plugins as $plugin_file) {
            $plugin_file = FailSafe_Helpers::sanitize_input($plugin_file);
            if (in_array($plugin_file, $active_plugins)) {
                $deactivated_plugins[] = $plugin_file;
            }
        }
        
        if (!empty($deactivated_plugins)) {
            // Deactivate the plugins
            if (function_exists('deactivate_plugins')) {
                deactivate_plugins($deactivated_plugins);
            } else {
                // Manual deactivation if function not available
                $remaining_plugins = array_diff($active_plugins, $deactivated_plugins);
                update_option('active_plugins', $remaining_plugins);
            }
            
            // Update recovery info
            $failsafe_recovery = get_option('failsafe_recovery', array());
            $failsafe_recovery['recovered'] = true;
            $failsafe_recovery['deactivated_plugins'] = $deactivated_plugins;
            $failsafe_recovery['recovery_method'] = 'multiple_plugin_deactivation';
            update_option('failsafe_recovery', $failsafe_recovery);
        }
        
        // Redirect to plugins page
        $redirect_url = admin_url('plugins.php');
        header('Location: ' . $redirect_url);
        exit;
    }
}

FailSafe_Error_Handler::get_instance();