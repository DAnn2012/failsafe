<?php
/**
 * Loader Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Loader {
    public static function load() {
        $plugin_file = defined( 'FAILSAFE_PLUGIN_FILE' ) ? FAILSAFE_PLUGIN_FILE : dirname( __DIR__, 1 ) . '/failsafe.php';

        if ( ! defined( 'FAILSAFE_VERSION' ) ) {
            define( 'FAILSAFE_VERSION', '1.0.0' );
        }
        if ( ! defined( 'FAILSAFE_PLUGIN_FILE' ) ) {
            define( 'FAILSAFE_PLUGIN_FILE', $plugin_file );
        }
        if ( ! defined( 'FAILSAFE_PLUGIN_DIR' ) ) {
            define( 'FAILSAFE_PLUGIN_DIR', plugin_dir_path( $plugin_file ) );
        }
        if ( ! defined( 'FAILSAFE_PLUGIN_URL' ) ) {
            define( 'FAILSAFE_PLUGIN_URL', plugin_dir_url( $plugin_file ) );
        }
        if ( ! defined( 'FAILSAFE_PLUGIN_BASENAME' ) ) {
            define( 'FAILSAFE_PLUGIN_BASENAME', plugin_basename( $plugin_file ) );
        }

        require_once FAILSAFE_PLUGIN_DIR . 'includes/helpers/class-failsafe-helpers.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-admin.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-frontend.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-activator.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-updater.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-options.php';
        require_once FAILSAFE_PLUGIN_DIR . 'includes/class-failsafe-database.php';
    }
}

FailSafe_Loader::load();
