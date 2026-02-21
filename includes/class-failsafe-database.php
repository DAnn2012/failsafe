<?php
/**
 * Database Operations Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Database {
    
    /**
     * Create error log table
     */
    public static function create_error_log_table() {
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
     * Get table name for error logs
     */
    public static function get_error_log_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'failsafe_error_logs';
    }
    
    /**
     * Check if error log table exists
     */
    public static function table_exists() {
        global $wpdb;
        $table_name = self::get_error_log_table_name();
        return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name; // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }
    
    /**
     * Drop error log table
     */
    public static function drop_error_log_table() {
        global $wpdb;
        $table_name = self::get_error_log_table_name();
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS `" . esc_sql( $table_name ) . "`" );
    }
}
