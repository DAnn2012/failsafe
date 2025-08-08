<?php
/**
 * FailSafe Error Handler Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Error_Handler {
    
    /**
     * Initialize error handling
     */
    public function init() {
        // Only register if not in admin and not during AJAX requests
        if (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) {
            register_shutdown_function(array($this, 'handle_fatal_error'));
        }
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if (!$error) {
            return;
        }
        
        $options = get_option('failsafe_options', array());
        $enabled_types = isset($options['enabled_error_types']) ? $options['enabled_error_types'] : array();
        
        // Check if this error type should be handled
        if (!isset($enabled_types[$error['type']]) || !$enabled_types[$error['type']]) {
            return;
        }
        
        // Only handle specific error types
        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR])) {
            return;
        }
        
        $file = $error['file'];
        $line = $error['line'];
        $message = $error['message'];
        
        // Create error hash for identification
        $error_hash = $this->create_error_hash($file, $line, $message);
        
        // Detect if error is from plugin or theme
        $plugin_theme_info = $this->detect_plugin_or_theme($file);
        
        // Log the error
        if (isset($options['log_errors']) && $options['log_errors']) {
            $this->log_error($error_hash, $error, $plugin_theme_info);
        }
        
        // Handle based on settings
        if (isset($options['auto_disable']) && $options['auto_disable']) {
            $this->auto_disable_plugin_theme($plugin_theme_info);
        } elseif (isset($options['show_frontend_notice']) && $options['show_frontend_notice']) {
            // Error will be shown on frontend via the frontend class
            return;
        } else {
            // Default behavior - auto disable
            $this->auto_disable_plugin_theme($plugin_theme_info);
        }
    }
    
    /**
     * Create unique hash for error identification
     */
    private function create_error_hash($file, $line, $message) {
        return hash('sha256', $file . ':' . $line . ':' . $message);
    }
    
    /**
     * Detect if error is from plugin or theme
     */
    private function detect_plugin_or_theme($file) {
        $result = array(
            'type' => null,
            'path' => null,
            'name' => null
        );
        
        // Normalize path separators
        $file = str_replace('\\', '/', $file);
        $plugins_path = str_replace('\\', '/', WP_PLUGIN_DIR);
        $themes_path = str_replace('\\', '/', get_theme_root());
        
        // Check if it's a plugin
        if (strpos($file, $plugins_path) !== false) {
            $plugin_path = $this->find_plugin_main_file($file);
            if ($plugin_path) {
                $result['type'] = 'plugin';
                $result['path'] = plugin_basename($plugin_path);
                $plugin_data = get_plugin_data($plugin_path, false, false);
                $result['name'] = !empty($plugin_data['Name']) ? $plugin_data['Name'] : basename($plugin_path);
            }
        }
        // Check if it's a theme
        elseif (strpos($file, $themes_path) !== false) {
            $theme_path = $this->find_theme_from_file($file);
            if ($theme_path) {
                $result['type'] = 'theme';
                $result['path'] = $theme_path;
                $theme = wp_get_theme($theme_path);
                $result['name'] = $theme->get('Name');
            }
        }
        
        return $result;
    }
    
    /**
     * Find the main plugin file (adapted from original code)
     */
    private function find_plugin_main_file($file_path) {
        $plugin_dir = dirname($file_path);
        
        // Go up until you reach the 'plugins' directory
        while ($plugin_dir && basename(dirname($plugin_dir)) !== 'plugins') {
            $plugin_dir = dirname($plugin_dir);
        }
        
        // Now scan that directory for all *.php files and check for plugin headers
        $php_files = glob($plugin_dir . '/*.php');
        if (is_array($php_files)) {
            foreach ($php_files as $php_file) {
                $plugin_data = get_plugin_data($php_file, false, false);
                if (!empty($plugin_data['Name'])) {
                    return $php_file;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Find theme from file path
     */
    private function find_theme_from_file($file_path) {
        $themes_path = get_theme_root();
        $relative_path = str_replace($themes_path . '/', '', $file_path);
        $theme_dir = explode('/', $relative_path)[0];
        
        // Check if theme exists
        if (wp_get_theme($theme_dir)->exists()) {
            return $theme_dir;
        }
        
        return null;
    }
    
    /**
     * Log error to database
     */
    private function log_error($error_hash, $error, $plugin_theme_info) {
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
                'error_type' => $this->get_error_type_name($error['type']),
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
     * Get human-readable error type name
     */
    private function get_error_type_name($error_type) {
        $error_types = array(
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_USER_ERROR => 'E_USER_ERROR',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR'
        );
        
        return isset($error_types[$error_type]) ? $error_types[$error_type] : 'UNKNOWN_ERROR';
    }
    
    /**
     * Automatically disable plugin or theme
     */
    private function auto_disable_plugin_theme($plugin_theme_info) {
        if (!$plugin_theme_info['type'] || !$plugin_theme_info['path']) {
            return;
        }
        
        if ($plugin_theme_info['type'] === 'plugin') {
            if (is_plugin_active($plugin_theme_info['path'])) {
                deactivate_plugins($plugin_theme_info['path']);
                error_log("FailSafe: Auto-disabled plugin {$plugin_theme_info['path']} due to fatal error");
                
                // Update error status in database
                $this->update_error_status_by_path($plugin_theme_info['path'], 'auto_resolved');
            }
        } elseif ($plugin_theme_info['type'] === 'theme') {
            $current_theme = get_option('stylesheet');
            if ($current_theme === $plugin_theme_info['path']) {
                switch_theme(WP_DEFAULT_THEME);
                error_log("FailSafe: Auto-switched theme due to fatal error in {$plugin_theme_info['path']}");
                
                // Update error status in database
                $this->update_error_status_by_path($plugin_theme_info['path'], 'auto_resolved');
            }
        }
    }
    
    /**
     * Update error status by plugin/theme path
     */
    private function update_error_status_by_path($path, $status) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $wpdb->update(
            $table_name,
            array('status' => $status),
            array('plugin_theme_path' => $path, 'status' => 'pending'),
            array('%s'),
            array('%s', '%s')
        );
    }
}