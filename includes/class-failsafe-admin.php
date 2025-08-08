<?php
/**
 * FailSafe Admin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_failsafe_disable_plugin', array($this, 'ajax_disable_plugin'));
        add_action('admin_notices', array($this, 'show_mu_plugin_status'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('FailSafe Settings', 'failsafe'),
            __('FailSafe', 'failsafe'),
            'manage_options',
            'failsafe-settings',
            array($this, 'settings_page')
        );
        
        add_management_page(
            __('FailSafe Error Logs', 'failsafe'),
            __('FailSafe Errors', 'failsafe'),
            'manage_options',
            'failsafe-errors',
            array($this, 'error_logs_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('failsafe_settings', 'failsafe_options', array($this, 'sanitize_options'));
        
        add_settings_section(
            'failsafe_error_types',
            __('Error Types to Monitor', 'failsafe'),
            array($this, 'error_types_section_callback'),
            'failsafe-settings'
        );
        
        add_settings_section(
            'failsafe_behavior',
            __('Behavior Settings', 'failsafe'),
            array($this, 'behavior_section_callback'),
            'failsafe-settings'
        );
        
        // Error type fields
        $error_types = $this->get_error_types();
        foreach ($error_types as $error_code => $error_name) {
            add_settings_field(
                'error_type_' . $error_code,
                $error_name,
                array($this, 'error_type_field_callback'),
                'failsafe-settings',
                'failsafe_error_types',
                array('error_code' => $error_code, 'error_name' => $error_name)
            );
        }
        
        // Behavior fields
        add_settings_field(
            'show_frontend_notice',
            __('Show Frontend Notice', 'failsafe'),
            array($this, 'checkbox_field_callback'),
            'failsafe-settings',
            'failsafe_behavior',
            array('field' => 'show_frontend_notice', 'description' => __('Show error notice on frontend instead of immediately disabling', 'failsafe'))
        );
        
        add_settings_field(
            'auto_disable',
            __('Auto Disable', 'failsafe'),
            array($this, 'checkbox_field_callback'),
            'failsafe-settings',
            'failsafe_behavior',
            array('field' => 'auto_disable', 'description' => __('Automatically disable problematic plugins/themes without user confirmation', 'failsafe'))
        );
        
        add_settings_field(
            'log_errors',
            __('Log Errors', 'failsafe'),
            array($this, 'checkbox_field_callback'),
            'failsafe-settings',
            'failsafe_behavior',
            array('field' => 'log_errors', 'description' => __('Keep a log of all detected errors', 'failsafe'))
        );
    }
    
    /**
     * Get available error types
     */
    private function get_error_types() {
        return array(
            E_ERROR => 'E_ERROR (Fatal Error)',
            E_PARSE => 'E_PARSE (Parse Error)', 
            E_CORE_ERROR => 'E_CORE_ERROR (Core Error)',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR (Compile Error)',
            E_USER_ERROR => 'E_USER_ERROR (User Error)',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR (Recoverable Error)'
        );
    }
    
    /**
     * Section callbacks
     */
    public function error_types_section_callback() {
        echo '<p>' . __('Select which types of errors should trigger the FailSafe system.', 'failsafe') . '</p>';
    }
    
    public function behavior_section_callback() {
        echo '<p>' . __('Configure how FailSafe behaves when errors are detected.', 'failsafe') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function error_type_field_callback($args) {
        $options = get_option('failsafe_options', array());
        $enabled_types = isset($options['enabled_error_types']) ? $options['enabled_error_types'] : array();
        $checked = isset($enabled_types[$args['error_code']]) && $enabled_types[$args['error_code']];
        
        printf(
            '<input type="checkbox" id="error_type_%s" name="failsafe_options[enabled_error_types][%s]" value="1" %s />',
            $args['error_code'],
            $args['error_code'],
            checked(1, $checked, false)
        );
    }
    
    public function checkbox_field_callback($args) {
        $options = get_option('failsafe_options', array());
        $checked = isset($options[$args['field']]) && $options[$args['field']];
        
        printf(
            '<input type="checkbox" id="%s" name="failsafe_options[%s]" value="1" %s />',
            $args['field'],
            $args['field'],
            checked(1, $checked, false)
        );
        
        if (isset($args['description'])) {
            printf('<p class="description">%s</p>', $args['description']);
        }
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($options) {
        $sanitized = array();
        
        if (isset($options['enabled_error_types'])) {
            $sanitized['enabled_error_types'] = array();
            foreach ($options['enabled_error_types'] as $error_code => $value) {
                $sanitized['enabled_error_types'][(int)$error_code] = (bool)$value;
            }
        }
        
        $sanitized['show_frontend_notice'] = isset($options['show_frontend_notice']) && $options['show_frontend_notice'];
        $sanitized['auto_disable'] = isset($options['auto_disable']) && $options['auto_disable'];
        $sanitized['log_errors'] = isset($options['log_errors']) && $options['log_errors'];
        
        return $sanitized;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('failsafe_settings');
                do_settings_sections('failsafe-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Error logs page
     */
    public function error_logs_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        // Handle actions
        if (isset($_GET['action']) && isset($_GET['error_id'])) {
            $error_id = intval($_GET['error_id']);
            
            if ($_GET['action'] === 'dismiss' && wp_verify_nonce($_GET['_wpnonce'], 'dismiss_error_' . $error_id)) {
                $wpdb->update(
                    $table_name,
                    array('status' => 'dismissed'),
                    array('id' => $error_id),
                    array('%s'),
                    array('%d')
                );
                
                echo '<div class="notice notice-success"><p>' . __('Error dismissed.', 'failsafe') . '</p></div>';
            }
        }
        
        // Get error logs
        $errors = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY error_time DESC LIMIT 50"
        );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (empty($errors)): ?>
                <p><?php _e('No errors logged yet.', 'failsafe'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Time', 'failsafe'); ?></th>
                            <th><?php _e('Type', 'failsafe'); ?></th>
                            <th><?php _e('Message', 'failsafe'); ?></th>
                            <th><?php _e('File', 'failsafe'); ?></th>
                            <th><?php _e('Plugin/Theme', 'failsafe'); ?></th>
                            <th><?php _e('Status', 'failsafe'); ?></th>
                            <th><?php _e('Actions', 'failsafe'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errors as $error): ?>
                            <tr>
                                <td><?php echo esc_html($error->error_time); ?></td>
                                <td><?php echo esc_html($error->error_type); ?></td>
                                <td><?php echo esc_html(wp_trim_words($error->error_message, 10)); ?></td>
                                <td><?php echo esc_html(basename($error->error_file)); ?></td>
                                <td>
                                    <?php if ($error->plugin_theme_path): ?>
                                        <?php echo esc_html($error->plugin_theme_type . ': ' . basename($error->plugin_theme_path)); ?>
                                    <?php else: ?>
                                        <?php _e('Unknown', 'failsafe'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-<?php echo esc_attr($error->status); ?>">
                                        <?php echo esc_html(ucfirst($error->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($error->status === 'pending'): ?>
                                        <a href="<?php echo wp_nonce_url(add_query_arg(array('action' => 'dismiss', 'error_id' => $error->id)), 'dismiss_error_' . $error->id); ?>" 
                                           class="button button-small">
                                            <?php _e('Dismiss', 'failsafe'); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'failsafe') !== false) {
            wp_enqueue_style('failsafe-admin', FAILSAFE_PLUGIN_URL . 'assets/admin.css', array(), FAILSAFE_VERSION);
            wp_enqueue_script('failsafe-admin', FAILSAFE_PLUGIN_URL . 'assets/admin.js', array('jquery'), FAILSAFE_VERSION, true);
            
            wp_localize_script('failsafe-admin', 'failsafe_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('failsafe_nonce')
            ));
        }
    }
    
    /**
     * AJAX handler for disabling plugins
     */
    public function ajax_disable_plugin() {
        if (!wp_verify_nonce($_POST['nonce'], 'failsafe_nonce') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed.', 'failsafe'));
        }
        
        $error_hash = sanitize_text_field($_POST['error_hash']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $error = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE error_hash = %s",
            $error_hash
        ));
        
        if (!$error) {
            wp_send_json_error(__('Error not found.', 'failsafe'));
        }
        
        $success = false;
        $message = '';
        
        if ($error->plugin_theme_type === 'plugin' && $error->plugin_theme_path) {
            if (is_plugin_active($error->plugin_theme_path)) {
                deactivate_plugins($error->plugin_theme_path);
                $success = true;
                $message = sprintf(__('Plugin %s has been disabled.', 'failsafe'), basename($error->plugin_theme_path));
            } else {
                $message = __('Plugin is already disabled.', 'failsafe');
            }
        } elseif ($error->plugin_theme_type === 'theme' && $error->plugin_theme_path) {
            switch_theme(WP_DEFAULT_THEME);
            $success = true;
            $message = __('Theme has been switched to default.', 'failsafe');
        }
        
        if ($success) {
            // Update error status
            $wpdb->update(
                $table_name,
                array('status' => 'resolved'),
                array('id' => $error->id),
                array('%s'),
                array('%d')
            );
        }
        
        wp_send_json_success(array('message' => $message));
    }
    
    /**
     * Show MU-plugin status notice
     */
    public function show_mu_plugin_status() {
        // Only show on FailSafe plugin pages
        $screen = get_current_screen();
        if (!$screen || (strpos($screen->id, 'failsafe') === false && $screen->id !== 'plugins')) {
            return;
        }
        
        $mu_plugin_file = WPMU_PLUGIN_DIR . '/failsafe-loader.php';
        $mu_plugin_exists = file_exists($mu_plugin_file);
        
        if (!$mu_plugin_exists) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('FailSafe Warning:', 'failsafe'); ?></strong>
                    <?php _e('The MU-plugin loader is not installed. Early error protection may not work properly.', 'failsafe'); ?>
                    <a href="<?php echo admin_url('plugins.php'); ?>" class="button button-small" style="margin-left: 10px;">
                        <?php _e('Deactivate and Reactivate Plugin', 'failsafe'); ?>
                    </a>
                </p>
            </div>
            <?php
        } elseif ($screen->id === 'settings_page_failsafe-settings') {
            ?>
            <div class="notice notice-success">
                <p>
                    <strong><?php _e('FailSafe Status:', 'failsafe'); ?></strong>
                    <?php _e('MU-plugin loader is active and providing early error protection.', 'failsafe'); ?>
                </p>
            </div>
            <?php
        }

        $failsafe_recovery = get_option('failsafe_recovery', array());

        if ( !empty($failsafe_recovery) && isset($failsafe_recovery['recovered']) && $failsafe_recovery['recovered'] ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong><?php printf('The %s %s has been successfully disabled.', $failsafe_recovery['type'], $failsafe_recovery['name']); ?></strong>
                </p>
            </div>
            <?php

            delete_option('failsafe_recovery');
        }
    }
}