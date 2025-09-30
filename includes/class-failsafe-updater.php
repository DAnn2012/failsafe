<?php
/**
 * Plugin Update Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Updater {
    
    /**
     * Check for plugin updates and handle migrations
     */
    public static function check_for_updates() {
        $current_version = get_option('failsafe_version', '0.0.0');
        
        if (version_compare($current_version, FAILSAFE_VERSION, '<')) {
            // Plugin has been updated, run any necessary migrations
            self::handle_plugin_update($current_version, FAILSAFE_VERSION);
            
            // Update the stored version
            update_option('failsafe_version', FAILSAFE_VERSION);
        }
    }
    
    /**
     * Handle plugin updates
     */
    private static function handle_plugin_update($old_version, $new_version) {
        // Get existing options
        $existing_options = get_option('failsafe_options', array());
        
        // Define current defaults
        $default_options = FailSafe_Options::get_defaults();
        
        // Merge existing options with new defaults (existing take precedence)
        $updated_options = wp_parse_args($existing_options, $default_options);
        
        // Ensure error types array is properly structured
        if (!isset($updated_options['enabled_error_types']) || !is_array($updated_options['enabled_error_types'])) {
            $updated_options['enabled_error_types'] = $default_options['enabled_error_types'];
        } else {
            $updated_options['enabled_error_types'] = wp_parse_args(
                $updated_options['enabled_error_types'], 
                $default_options['enabled_error_types']
            );
        }
        
        // Ensure recovery_user_roles is properly structured
        if (!isset($updated_options['recovery_user_roles']) || !is_array($updated_options['recovery_user_roles'])) {
            $updated_options['recovery_user_roles'] = $default_options['recovery_user_roles'];
        }
        
        // Update the options
        update_option('failsafe_options', $updated_options);
        
        error_log("FailSafe: Plugin updated from version {$old_version} to {$new_version}. Settings preserved and merged with new defaults."); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
    }
}
