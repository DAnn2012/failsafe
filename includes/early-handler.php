<?php
/**
 * FailSafe Early Error Handler
 * 
 * This file contains the early error handling logic that gets loaded by the MU-plugin.
 * It handles fatal errors that occur before WordPress fully loads.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Early error handler for critical errors
 */
class FailSafe_Early_Handler {
    
    /**
     * Initialize early error handling
     */
    public static function init() {
        // Register early shutdown function
        register_shutdown_function(array(__CLASS__, 'handle_early_fatal_error'));
        
        add_action( 'muplugins_loaded', function() {
            if (is_admin() && file_exists(ABSPATH . 'wp-admin/includes/plugin.php')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            } else {
                return;
            }

            // Handle recovery actions first
            self::handle_recovery_action();
        });
    }
    
    /**
     * Handle recovery actions from GET parameters
     */
    public static function handle_recovery_action() {
        // Check if this is a recovery request
        if (!isset($_GET['failsafe_action']) || !isset($_GET['failsafe_hash'])) {
            return;
        }
        
        $action = self::sanitize_input($_GET['failsafe_action']);
        $hash = self::sanitize_input($_GET['failsafe_hash']);
        $theme = isset($_GET['failsafe_theme']) ? self::sanitize_input($_GET['failsafe_theme']) : '';
        
        self::process_recovery($action, $hash, $theme);
    }
    
    /**
     * Process recovery using WordPress functions
     */
    private static function process_recovery($action, $hash, $theme = '') {
        $failsafe_recovery = get_option('failsafe_recovery', array());
        if ( isset($failsafe_recovery['hash']) && $failsafe_recovery['hash'] === $hash ) {
            self::disable_plugin_theme($failsafe_recovery, $theme);
        }
    }
    
    public static function handle_early_fatal_error() {
        $error = error_get_last();
        
        if (!$error) {
            return;
        }
        
        // Only handle fatal errors
        if (!in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            return;
        }
        
        // Get FailSafe options
        $options = get_option('failsafe_options', array());
        $auto_disable = isset($options['auto_disable']) && $options['auto_disable'];
        
        $file = $error['file'];
        
        // Normalize path separators
        $file = str_replace('\\', '/', $file);
        $plugins_path = str_replace('\\', '/', WP_PLUGIN_DIR);
        $themes_path = str_replace('\\', '/', get_theme_root());
        
        $plugin_theme_info = null;
        
        // Detect plugin errors
        if (strpos($file, $plugins_path) !== false) {
            $plugin_path = self::find_plugin_main_file($file);
            
            if ($plugin_path && function_exists('is_plugin_active') && is_plugin_active(plugin_basename($plugin_path))) {
                $plugin_theme_info = array(
                    'type' => 'plugin',
                    'path' => plugin_basename($plugin_path),
                    'name' => self::get_plugin_name($plugin_path)
                );
            }
        }
        // Detect theme errors
        elseif (strpos($file, $themes_path) !== false) {
            $theme_path = self::find_theme_from_file($file);
            if ($theme_path) {
                $plugin_theme_info = array(
                    'type' => 'theme',
                    'path' => $theme_path,
                    'name' => self::get_theme_name($theme_path)
                );
            }
        }
        
        if ($plugin_theme_info) {
            if ('plugin' === $plugin_theme_info['type'] && $auto_disable) {
                // Auto-disable behavior (original logic)
                self::disable_plugin_theme($plugin_theme_info);
            } else {
                // Show recovery message instead
                self::show_recovery_message($error, $plugin_theme_info);
            }
        }
    }
    
    /**
     * Find the main plugin file
     */
    private static function find_plugin_main_file($file_path) {
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
     * Store error information for later processing
     */
    private static function store_early_error($error, $type, $path) {
        $early_errors = get_option('failsafe_early_errors', array());
        
        $error_data = array(
            'error_type' => $error['type'],
            'error_message' => $error['message'],
            'error_file' => $error['file'],
            'error_line' => $error['line'],
            'plugin_theme_type' => $type,
            'plugin_theme_path' => $path,
            'error_time' => current_time('mysql'),
            'processed' => false
        );
        
        $early_errors[] = $error_data;
        
        // Keep only last 10 early errors
        if (count($early_errors) > 10) {
            $early_errors = array_slice($early_errors, -10);
        }
        
        update_option('failsafe_early_errors', $early_errors);
    }
    
    /**
     * Show recovery message after fatal error
     */
    private static function show_recovery_message($error, $plugin_theme_info) {     
        $failsafe_recovery = get_option('failsafe_recovery', array());
        if ( !isset($failsafe_recovery['hash']) ) {
            $error_hash = hash('sha256', $error['file'] . ':' . $error['line'] . ':' . $error['message'] . ':' . time());
            $failsafe_recovery = $plugin_theme_info;
            $failsafe_recovery['hash'] = $error_hash;

            update_option('failsafe_recovery', $failsafe_recovery);
        } else {
            $error_hash = $failsafe_recovery['hash'];
        }
        
        $current_url = self::get_current_url();
        
        include __DIR__ . '/../templates/recovery-message.php';
    }
    
    
    /**
     * Auto-disable plugin or theme (original behavior)
     */
    private static function disable_plugin_theme($failsafe_recovery, $theme_slug = '') {
        $type = $failsafe_recovery['type'];
        $path = $failsafe_recovery['path'];
        $redirect_url = '';

        if ($type === 'plugin' && $path) {
            $active_plugins = get_option('active_plugins', []);
            $plugin_file = $path;

            if (in_array($plugin_file, $active_plugins)) {
                deactivate_plugins($path);
                $failsafe_recovery['recovered'] = true;
            }

            $redirect_url = admin_url('plugins.php');

        } elseif ($type === 'theme' && $path) {

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
     * Get available themes
     */
    private static function get_available_themes() {
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
    private static function get_theme_name_from_style($style_file) {
        $content = file_get_contents($style_file);
        if (preg_match('/Theme Name:\s*(.+)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
    
    
    /**
     * Get plugin name from plugin file
     */
    private static function get_plugin_name($plugin_path) {
        $content = file_get_contents($plugin_path);
        if (preg_match('/Plugin Name:\s*(.+)/i', $content, $matches)) {
            return trim($matches[1]);
        }
        return basename($plugin_path);
    }
    
    /**
     * Get theme name from theme directory
     */
    private static function get_theme_name($theme_path) {
        if (function_exists('wp_get_theme')) {
            $theme = wp_get_theme($theme_path);
            return $theme->get('Name');
        }
        return $theme_path;
    }
    
    /**
     * Find theme from file path
     */
    private static function find_theme_from_file($file_path) {
        $themes_path = str_replace('\\', '/', get_theme_root());
        $relative_path = str_replace($themes_path . '/', '', $file_path);
        $theme_dir = explode('/', $relative_path)[0];
        
        // Check if theme exists
        if (function_exists('wp_get_theme') && wp_get_theme($theme_dir)->exists()) {
            return $theme_dir;
        }
        
        return null;
    }
    
    /**
     * Simple input sanitization without WordPress functions
     */
    private static function sanitize_input($input) {
        // Allow alphanumeric, underscore, hyphen, and forward slash for theme names
        return preg_replace('/[^a-zA-Z0-9_\/-]/', '', $input);
    }
    
    /**
     * Simple HTML escaping without WordPress functions
     */
    private static function escape_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Get current URL without WordPress functions
     */
    private static function get_current_url() {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove existing failsafe parameters
        $uri = preg_replace('/[?&]failsafe_(action|hash|token)=[^&]*/', '', $uri);
        
        return $protocol . $host . $uri;
    }
}

// Initialize early error handling
FailSafe_Early_Handler::init();