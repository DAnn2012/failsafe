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
        add_action('wp_loaded', array(FailSafe_Updater::class, 'check_for_updates'));
    }
    
    /**
     * Initialize classes
     */
    private function init_classes() {
        if (is_admin()) {
            $this->admin = new FailSafe_Admin();
        } else {
            $this->frontend = new FailSafe_Frontend();
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('failsafe', false, dirname(FAILSAFE_PLUGIN_BASENAME) . '/languages');
    }
}