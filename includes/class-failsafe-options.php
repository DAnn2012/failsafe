<?php
/**
 * Plugin Options Manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Options {
    
    /**
     * Option name in WordPress options table
     */
    const OPTION_NAME = 'failsafe_options';
    
    /**
     * Get plugin options
     */
    public static function get() {
        return get_option(self::OPTION_NAME, array());
    }
    
    /**
     * Update plugin options
     */
    public static function update($options) {
        return update_option(self::OPTION_NAME, $options);
    }
    
    /**
     * Get a specific option value
     */
    public static function get_option($key, $default = null) {
        $options = self::get();
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update a specific option value
     */
    public static function update_option($key, $value) {
        $options = self::get();
        $options[$key] = $value;
        return self::update($options);
    }
    
    /**
     * Get default options
     */
    public static function get_defaults() {
        return array(
            'enable_failsafe' => true,
            'enabled_error_types' => array(
                E_ERROR => true,
                E_PARSE => true,
                E_CORE_ERROR => true,
                E_COMPILE_ERROR => true,
                E_USER_ERROR => false,
                E_RECOVERABLE_ERROR => false
            ),
            'log_errors' => false,
            'recovery_user_roles' => array('administrator'),
            'delete_data_on_uninstall' => true,
            'protected_plugins' => array(),
            'admin_only_recovery' => true,
            'show_error_details' => false
        );
    }
    
    /**
     * Check if failsafe is enabled
     */
    public static function is_enabled() {
        return self::get_option('enable_failsafe', true);
    }
    
    /**
     * Check if a specific error type is enabled
     */
    public static function is_error_type_enabled($error_type) {
        $enabled_types = self::get_option('enabled_error_types', array());
        return isset($enabled_types[$error_type]) && $enabled_types[$error_type];
    }
    
    /**
     * Check if error logging is enabled
     */
    public static function is_logging_enabled() {
        return self::get_option('log_errors', false);
    }
    
    /**
     * Get recovery user roles
     */
    public static function get_recovery_user_roles() {
        return self::get_option('recovery_user_roles', array('administrator'));
    }

    /**
     * Get recovery options
     */
    public static function get_recovery_options() {
        return get_option('failsafe_recovery', array());
    }

    public static function validate_recovery_hash( $hash ) {
        $recovery_options = self::get_recovery_options();

        if ( ! isset( $recovery_options['hash'] ) || $recovery_options['hash'] !== $hash ) {
            return false;
        }

        if ( isset( $recovery_options['expires'] ) && time() > $recovery_options['expires'] ) {
            delete_option( 'failsafe_recovery' );
            return false;
        }

        return true;
    }

    public static function invalidate_recovery_hash() {
        delete_option( 'failsafe_recovery' );
    }
}
