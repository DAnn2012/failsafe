<?php
/**
 * Loader Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Loader {
    public static function load() {
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-admin.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-frontend.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-error-handler.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-activator.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-updater.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-options.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-database.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/early-handler.php';
    }
}
