<?php
/**
 * Main FailSafe Plugin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin version
     */
    public $version = FAILSAFE_VERSION;
    
    /**
     * Admin instance
     */
    public $admin;
    
    /**
     * Frontend instance
     */
    public $frontend;
    
    /**
     * Error handler instance
     */
    public $error_handler;
    
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
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
        $this->init_classes();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('wp_loaded', array($this, 'init_error_handling'));
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        if (is_admin()) {
            $this->admin = new FailSafe_Admin();
        }
        
        if (!is_admin()) {
            $this->frontend = new FailSafe_Frontend();
        }
        
        $this->error_handler = new FailSafe_Error_Handler();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('failsafe', false, dirname(FAILSAFE_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Initialize error handling
     */
    public function init_error_handling() {
        $this->error_handler->init();
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create mu-plugins directory if it doesn't exist
        $mu_plugins_dir = WPMU_PLUGIN_DIR;
        if (!file_exists($mu_plugins_dir)) {
            wp_mkdir_p($mu_plugins_dir);
        }
        
        // Remove existing mu-plugin loader if it exists (to ensure we have the latest version)
        $destination = $mu_plugins_dir . '/failsafe-loader.php';
        if (file_exists($destination)) {
            unlink($destination);
        }
        
        // Copy the latest mu-plugin loader
        $source = FAILSAFE_PLUGIN_DIR . 'mu-plugin/failsafe-loader.php';
        
        if (file_exists($source)) {
            $copy_result = copy($source, $destination);
            if (!$copy_result) {
                error_log('FailSafe: Failed to copy MU-plugin loader to mu-plugins directory');
            } else {
                error_log('FailSafe: Successfully installed MU-plugin loader');
            }
        } else {
            error_log('FailSafe: MU-plugin loader source file not found at: ' . $source);
        }
        
        // Set default options
        $default_options = array(
            'enabled_error_types' => array(
                E_ERROR => true,
                E_PARSE => true,
                E_CORE_ERROR => true,
                E_COMPILE_ERROR => true,
                E_USER_ERROR => false,
                E_RECOVERABLE_ERROR => false
            ),
            'show_frontend_notice' => true,
            'auto_disable' => false,
            'log_errors' => true
        );
        
        add_option('failsafe_options', $default_options);
        
        // Create database table for error logs
        self::create_error_log_table();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Remove mu-plugin loader
        $mu_plugin_file = WPMU_PLUGIN_DIR . '/failsafe-loader.php';
        if (file_exists($mu_plugin_file)) {
            $delete_result = unlink($mu_plugin_file);
            if ($delete_result) {
                error_log('FailSafe: Successfully removed MU-plugin loader');
            } else {
                error_log('FailSafe: Failed to remove MU-plugin loader from mu-plugins directory');
            }
        }
        
        // Clean up any early errors stored in options
        delete_option('failsafe_early_errors');
        delete_option('failsafe_pending_errors');
    }
    
    /**
     * Create error log table
     */
    private static function create_error_log_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            error_hash varchar(64) NOT NULL,
            error_type varchar(50) NOT NULL,
            error_message text NOT NULL,
            error_file varchar(500) NOT NULL,
            error_line int(11) NOT NULL,
            plugin_theme_path varchar(500) DEFAULT NULL,
            plugin_theme_type varchar(20) DEFAULT NULL,
            error_time datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'pending',
            PRIMARY KEY (id),
            UNIQUE KEY error_hash (error_hash)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get plugin options
     */
    public function get_options() {
        return get_option('failsafe_options', array());
    }
    
    /**
     * Update plugin options
     */
    public function update_options($options) {
        return update_option('failsafe_options', $options);
    }
}