<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * FailSafe Helpers
 * 
 * This class contains helper functions for the FailSafe plugin.
 */
class FailSafe_Helpers {

    /**
     * Detect if error is from plugin or theme
     */
    public static function detect_plugin_or_theme($file) {
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
            $plugin_path = self::find_plugin_main_file($file);
            if ($plugin_path) {
                $result['type'] = 'plugin';
                $result['path'] = plugin_basename($plugin_path);
                
                // Try to get plugin name using WordPress functions if available
                if (function_exists('get_plugin_data')) {
                    $plugin_data = get_plugin_data($plugin_path, false, false);
                    $result['name'] = !empty($plugin_data['Name']) ? $plugin_data['Name'] : basename($plugin_path);
                } else {
                    $result['name'] = self::get_plugin_name($plugin_path);
                }
            }
        }
        // Check if it's a theme
        elseif (strpos($file, $themes_path) !== false) {
            $theme_path = self::find_theme_from_file($file);
            if ($theme_path) {
                $result['type'] = 'theme';
                $result['path'] = $theme_path;
                
                // Try to get theme name using WordPress functions if available
                if (function_exists('wp_get_theme')) {
                    $theme = wp_get_theme($theme_path);
                    $result['name'] = $theme->get('Name');
                } else {
                    $result['name'] = $theme_path;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Find the main plugin file
     */
    public static function find_plugin_main_file($file_path) {
        $plugin_dir = dirname($file_path);
        
        // Go up until you reach the 'plugins' directory
        while ($plugin_dir && basename(dirname($plugin_dir)) !== 'plugins') {
            $plugin_dir = dirname($plugin_dir);
        }
        
        // Now scan that directory for all *.php files and check for plugin headers
        $php_files = glob($plugin_dir . '/*.php');
        if (is_array($php_files)) {
            foreach ($php_files as $php_file) {
                $content = file_get_contents($php_file);
                if (preg_match('/Plugin Name:/i', $content)) {
                    return $php_file;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Find theme from file path
     */
    public static function find_theme_from_file($file_path) {
        $themes_path = get_theme_root();
        $relative_path = str_replace($themes_path . '/', '', $file_path);
        $theme_dir = explode('/', $relative_path)[0];
        
        // Check if theme exists
        if (function_exists('wp_get_theme') && wp_get_theme($theme_dir)->exists()) {
            return $theme_dir;
        }
        
        return null;
    }

    /**
     * Get human-readable error type name
     */
    public static function get_error_type_name($error_type) {
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
     * Get active plugins with their names
     */
    public static function get_active_plugins_with_names() {
        $active_plugins = get_option('active_plugins', array());
        $plugins_with_names = array();
        
        foreach ($active_plugins as $plugin_file) {
            if (defined('WP_PLUGIN_DIR')) {
                $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
            } else {
                $plugin_path = $plugin_file;
            }
            
            if (file_exists($plugin_path)) {
                if (function_exists('get_plugin_data')) {
                    $plugin_data = get_plugin_data($plugin_path, false, false);
                    $plugin_name = $plugin_data['Name'] ? $plugin_data['Name'] : basename($plugin_file, '.php');
                } else {
                    $plugin_name = self::get_plugin_name($plugin_path);
                }
                
                $plugins_with_names[$plugin_file] = $plugin_name;
            }
        }
        
        return $plugins_with_names;
    }

    /**
     * Get available themes
     */
    public static function get_available_themes() {
        $themes = array();
        
        if (function_exists('wp_get_themes')) {
            $wp_themes = wp_get_themes();
            foreach ($wp_themes as $theme_slug => $theme_obj) {
                if ($theme_obj->exists() && !$theme_obj->errors()) {
                    $themes[$theme_slug] = $theme_obj->get('Name');
                }
            }
        } else {
            // Fallback method for when wp_get_themes is not available
            $themes_dir = get_theme_root();
            if (is_dir($themes_dir)) {
                $theme_dirs = scandir($themes_dir);
                foreach ($theme_dirs as $dir) {
                    if ($dir !== '.' && $dir !== '..' && is_dir($themes_dir . '/' . $dir)) {
                        $style_css = $themes_dir . '/' . $dir . '/style.css';
                        if (file_exists($style_css)) {
                            $theme_name = self::get_theme_name_from_style($style_css);
                            $themes[$dir] = $theme_name ? $theme_name : $dir;
                        }
                    }
                }
            }
        }
        
        return $themes;
    }
    
    /**
     * Get theme name from style.css file
     */
    public static function get_theme_name_from_style($style_file) {
        $content = file_get_contents($style_file);
        if (preg_match('/Theme Name:\s*(.+)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    
    /**
     * Get plugin name from plugin file
     */
    public static function get_plugin_name($plugin_path) {
        $content = file_get_contents($plugin_path);
        if (preg_match('/Plugin Name:\s*(.+)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        return basename($plugin_path);
    }
    
    /**
     * Get theme name from theme directory
     */
    public static function get_theme_name($theme_path) {
        if (function_exists('wp_get_theme')) {
            $theme = wp_get_theme($theme_path);
            return $theme->get('Name');
        }
        return $theme_path;
    }
    
    /**
     * Simple input sanitization without WordPress functions
     */
    public static function sanitize_input($input) {
        if (is_array($input)) {
            return array_map(array(__CLASS__, 'sanitize_input'), $input);
        }
        // Allow alphanumeric, underscore, hyphen, dot, and forward slash for plugin/theme paths
        return preg_replace('/[^a-zA-Z0-9_.\/-]/', '', $input);
    }
    
    /**
     * Simple HTML escaping without WordPress functions
     */
    public static function escape_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get current URL without WordPress functions
     */
    public static function get_current_url() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove existing failsafe parameters
        $uri = preg_replace('/[?&]failsafe_(action|hash|token)=[^&]*/', '', $uri);
        
        return $protocol . $host . $uri;
    }
}