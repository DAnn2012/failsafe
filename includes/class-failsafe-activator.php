<?php
/**
 * Plugin Activation/Deactivation Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Activator {
    
    /**
     * Plugin activation
     */
    public static function activate() {
        self::copy_mu_plugin_loader();
        self::set_default_options();
        
        // Create database table for error logs
        FailSafe_Database::create_error_log_table();
        
        // Store the plugin version for future upgrade checks
        update_option('failsafe_version', FAILSAFE_VERSION);
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        self::delete_mu_plugin_loader();
        self::delete_options();
    }

    /**
     * Copy mu-plugin loader
     */
    public static function copy_mu_plugin_loader() {
        // Create mu-plugins directory if it doesn't exist
        $mu_plugins_dir = WPMU_PLUGIN_DIR;
        if (!file_exists($mu_plugins_dir)) {
            wp_mkdir_p($mu_plugins_dir);
        }
        
        // Remove existing mu-plugin loader if it exists (to ensure we have the latest version)
        $destination = $mu_plugins_dir . '/failsafe-mu-loader.php';
        if (file_exists($destination)) {
            wp_delete_file($destination);
        }
        
        // Copy the latest mu-plugin loader
        $source = FAILSAFE_PLUGIN_DIR . 'mu-plugin/failsafe-mu-loader.php';
        
        if (file_exists($source)) {
            $copy_result = copy($source, $destination);
            if (!$copy_result) {
                error_log('FailSafe: Failed to copy MU-plugin loader to mu-plugins directory'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            } else {
                error_log('FailSafe: Successfully installed MU-plugin loader'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        } else {
            error_log('FailSafe: MU-plugin loader source file not found at: ' . $source); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }


    /**
     * Set default options
     */
    public static function set_default_options() {
        // Set default options only if they don't exist (first-time activation)
        $existing_options = get_option('failsafe_options', false);
        
        if ($existing_options === false) {
            // First-time activation - set default options
            $default_options = FailSafe_Options::get_defaults();
            add_option('failsafe_options', $default_options);
            
            // Set redirect flag for first-time activation only
            add_option('failsafe_activation_redirect', true);
        } else {
            // Existing installation - merge any missing default values with existing settings
            $default_options = FailSafe_Options::get_defaults();
            
            // Merge existing options with defaults (existing options take precedence)
            $merged_options = wp_parse_args($existing_options, $default_options);
            
            // Ensure error types array exists and has all required keys
            if (!isset($merged_options['enabled_error_types']) || !is_array($merged_options['enabled_error_types'])) {
                $merged_options['enabled_error_types'] = $default_options['enabled_error_types'];
            } else {
                // Merge error types to ensure all error types are present
                $merged_options['enabled_error_types'] = wp_parse_args(
                    $merged_options['enabled_error_types'], 
                    $default_options['enabled_error_types']
                );
            }
            
            // Ensure recovery_user_roles is an array
            if (!isset($merged_options['recovery_user_roles']) || !is_array($merged_options['recovery_user_roles'])) {
                $merged_options['recovery_user_roles'] = $default_options['recovery_user_roles'];
            }
            
            // Update options with merged values
            update_option('failsafe_options', $merged_options);
        }
    }

    /**
     * Delete mu-plugin loader
     */
    public static function delete_mu_plugin_loader() {
        // Remove mu-plugin loader
        $mu_plugin_file = WPMU_PLUGIN_DIR . '/failsafe-mu-loader.php';
        if (file_exists($mu_plugin_file)) {
            $delete_result = wp_delete_file($mu_plugin_file);
            if ($delete_result) {
                error_log('FailSafe: Successfully removed MU-plugin loader'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            } else {
                error_log('FailSafe: Failed to remove MU-plugin loader'); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }

    /**
     * Delete options
     */
    public static function delete_options() {
        // Clean up any early errors stored in options
        // delete_option('failsafe_early_errors');
        // delete_option('failsafe_pending_errors');
    }
}
