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
        add_action('admin_init', array($this, 'handle_activation_redirect'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_failsafe_disable_plugin', array($this, 'ajax_disable_plugin'));
        add_action('wp_ajax_failsafe_save_settings', array($this, 'ajax_save_settings'));
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
     * Handle activation redirect
     */
    public function handle_activation_redirect() {
        if (get_option('failsafe_activation_redirect', false)) {
            delete_option('failsafe_activation_redirect');
            
            // Don't redirect if we're already on the settings page or doing an AJAX request
            if (isset($_GET['page']) && $_GET['page'] === 'failsafe-settings') {
                return;
            }
            
            if (wp_doing_ajax() || is_network_admin()) {
                return;
            }
            
            // Redirect to settings page
            wp_safe_redirect(admin_url('options-general.php?page=failsafe-settings&welcome=1'));
            exit;
        }
    }
    
    /**
     * Get available error types
     */
    private function get_error_types() {
        return array(
            E_ERROR => 'E_ERROR (Fatal Error)',
            E_CORE_ERROR => 'E_CORE_ERROR (Core Error)',
            E_PARSE => 'E_PARSE (Parse Error)', 
            E_COMPILE_ERROR => 'E_COMPILE_ERROR (Compile Error)',
            E_USER_ERROR => 'E_USER_ERROR (User Error)',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR (Recoverable Error)'
        );
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
        
        $sanitized['enable_failsafe'] = isset($options['enable_failsafe']) && $options['enable_failsafe'];
        $sanitized['log_errors'] = isset($options['log_errors']) && $options['log_errors'];
        
        // Sanitize user roles
        if (isset($options['recovery_user_roles']) && is_array($options['recovery_user_roles'])) {
            global $wp_roles;
            $all_roles = array_keys($wp_roles->roles);
            $sanitized['recovery_user_roles'] = array();
            
            foreach ($options['recovery_user_roles'] as $role) {
                if (in_array($role, $all_roles)) {
                    $sanitized['recovery_user_roles'][] = sanitize_text_field($role);
                }
            }
            
            // Ensure at least administrator role is selected
            if (empty($sanitized['recovery_user_roles'])) {
                $sanitized['recovery_user_roles'] = array('administrator');
            }
        } else {
            $sanitized['recovery_user_roles'] = array('administrator');
        }
        
        return $sanitized;
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $options = get_option('failsafe_options', array());
        $enabled = isset($options['enable_failsafe']) ? $options['enable_failsafe'] : true;
        ?>
        <div class="wrap failsafe-settings-wrap">
            <div class="failsafe-header">
                <div class="failsafe-header-content">
                    <div class="failsafe-header-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L13.09 8.26L22 9L13.09 9.74L12 16L10.91 9.74L2 9L10.91 8.26L12 2Z" fill="currentColor"/>
                            <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="2" fill="none"/>
                        </svg>
                    </div>
                    <div class="failsafe-header-text">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                        <p><?php _e('Protect your website from fatal errors with intelligent error recovery', 'failsafe'); ?></p>
                    </div>
                </div>
                <div class="failsafe-status-indicator <?php echo $enabled ? 'enabled' : 'disabled'; ?>">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php echo $enabled ? __('Active', 'failsafe') : __('Inactive', 'failsafe'); ?></span>
                </div>
            </div>

            <?php if (isset($_GET['welcome']) && $_GET['welcome'] == '1'): ?>
            <div class="failsafe-welcome-message">
                <div class="failsafe-welcome-content">
                    <h2><?php _e('Welcome to FailSafe!', 'failsafe'); ?></h2>
                    <p><?php _e('Thank you for installing FailSafe. Your website is now protected from fatal errors. Configure the settings below to customize the protection level.', 'failsafe'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" class="failsafe-settings-form" id="failsafe-settings-form">
                
                <div class="failsafe-settings-container">
                    <!-- Main Settings Card -->
                    <div class="failsafe-settings-card failsafe-main-card">
                        <div class="failsafe-card-header">
                            <h2><?php _e('FailSafe Settings', 'failsafe'); ?></h2>
                            <p><?php _e('Configure the main FailSafe settings to protect your website from fatal errors.', 'failsafe'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <?php 
                            $main_options = get_option('failsafe_options', array());
                            $enable_checked = isset($main_options['enable_failsafe']) ? $main_options['enable_failsafe'] : true;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="enable_failsafe" class="failsafe-setting-title"><?php _e('Enable FailSafe', 'failsafe'); ?></label>
                                    <p class="failsafe-setting-description"><?php _e('Enable or disable FailSafe error protection for your website', 'failsafe'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="enable_failsafe" name="failsafe_options[enable_failsafe]" value="1" <?php checked(1, $enable_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $enable_checked ? __('Enabled', 'failsafe') : __('Disabled', 'failsafe'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Types Card -->
                    <div class="failsafe-settings-card">
                        <div class="failsafe-card-header">
                            <h2><?php _e('Error Types to Monitor', 'failsafe'); ?></h2>
                            <p><?php _e('Select which types of errors should trigger the FailSafe system.', 'failsafe'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <div class="failsafe-error-types-grid">
                                <?php
                                $error_types = $this->get_error_types();
                                $enabled_types = isset($options['enabled_error_types']) ? $options['enabled_error_types'] : array();
                                
                                foreach ($error_types as $error_code => $error_name):
                                    $checked = isset($enabled_types[$error_code]) && $enabled_types[$error_code];
                                    $error_class = $this->get_error_severity_class($error_code);
                                ?>
                                    <div class="failsafe-error-type-item <?php echo $error_class; ?>">
                                        <div class="failsafe-error-type-header">
                                            <label for="error_type_<?php echo $error_code; ?>" class="failsafe-error-type-label">
                                                <input type="checkbox" 
                                                       id="error_type_<?php echo $error_code; ?>" 
                                                       name="failsafe_options[enabled_error_types][<?php echo $error_code; ?>]" 
                                                       value="1" 
                                                       <?php checked(1, $checked, true); ?> />
                                                <span class="failsafe-checkbox-custom"></span>
                                                <span class="failsafe-error-type-name"><?php echo esc_html($error_name); ?></span>
                                            </label>
                                        </div>
                                        <div class="failsafe-error-type-description">
                                            <?php echo $this->get_error_description($error_code); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Behavior Settings Card -->
                    <div class="failsafe-settings-card">
                        <div class="failsafe-card-header">
                            <h2><?php _e('Behavior Settings', 'failsafe'); ?></h2>
                            <p><?php _e('Configure additional behavior settings for error handling and logging.', 'failsafe'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <?php 
                            $log_checked = isset($options['log_errors']) ? $options['log_errors'] : true;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="log_errors" class="failsafe-setting-title"><?php _e('Keep log of errors', 'failsafe'); ?></label>
                                    <p class="failsafe-setting-description"><?php _e('Maintain a detailed log of all detected errors for analysis and troubleshooting', 'failsafe'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="log_errors" name="failsafe_options[log_errors]" value="1" <?php checked(1, $log_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $log_checked ? __('Enabled', 'failsafe') : __('Disabled', 'failsafe'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label class="failsafe-setting-title"><?php _e('Recovery Access Roles', 'failsafe'); ?></label>
                                    <p class="failsafe-setting-description"><?php _e('Select which user roles can see recovery messages and deactivate plugins/themes', 'failsafe'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                <?php
                                    $selected_roles = isset($options['recovery_user_roles']) ? $options['recovery_user_roles'] : array('administrator');
                                    if (!is_array($selected_roles)) {
                                        $selected_roles = array('administrator');
                                    }
                                    
                                    global $wp_roles;
                                    $all_roles = $wp_roles->roles;
                                    
                                    echo '<div class="failsafe-user-roles-wrapper">';
                                    foreach ($all_roles as $role_key => $role_info) {
                                        $checked = in_array($role_key, $selected_roles);
                                        printf(
                                            '<label class="failsafe-role-item">
                                                <input type="checkbox" name="failsafe_options[recovery_user_roles][]" value="%s" %s />
                                                <span class="failsafe-role-checkbox"></span>
                                                <span class="failsafe-role-name">%s</span>
                                            </label>',
                                            esc_attr($role_key),
                                            checked(true, $checked, false),
                                            esc_html($role_info['name'])
                                        );
                                    }
                                    echo '</div>';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="failsafe-save-section">
                    <div class="failsafe-action-buttons">
                        <div class="failsafe-save-button">
                            <?php submit_button(__('Save Settings', 'failsafe'), 'primary', 'submit', false); ?>
                        </div>
                        <a href="<?php echo admin_url('tools.php?page=failsafe-errors'); ?>" class="button button-secondary failsafe-view-logs-button">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('View Error Logs', 'failsafe'); ?>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Get error severity class for styling
     */
    private function get_error_severity_class($error_code) {
        switch ($error_code) {
            case E_ERROR:
            case E_CORE_ERROR:
                return 'severity-critical';
            case E_PARSE:
            case E_COMPILE_ERROR:
                return 'severity-high';
            case E_USER_ERROR:
                return 'severity-medium';
            case E_RECOVERABLE_ERROR:
                return 'severity-low';
            default:
                return 'severity-medium';
        }
    }
    
    /**
     * Get error description
     */
    private function get_error_description($error_code) {
        $descriptions = array(
            E_ERROR => __('Fatal run-time errors that cannot be recovered from', 'failsafe'),
            E_CORE_ERROR => __('Fatal errors that occur during PHP startup', 'failsafe'),
            E_PARSE => __('Compile-time parse errors in PHP syntax', 'failsafe'),
            E_COMPILE_ERROR => __('Fatal compile-time errors in PHP code', 'failsafe'),
            E_USER_ERROR => __('User-generated error messages', 'failsafe'),
            E_RECOVERABLE_ERROR => __('Catchable fatal errors that can be recovered', 'failsafe')
        );
        
        return isset($descriptions[$error_code]) ? $descriptions[$error_code] : '';
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
            wp_enqueue_style('failsafe-admin', FAILSAFE_PLUGIN_URL . 'build/style.css', array(), FAILSAFE_VERSION);
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
     * AJAX handler for saving settings
     */
    public function ajax_save_settings() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'failsafe_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'failsafe')));
        }
        
        // Get the posted options
        $options = isset($_POST['failsafe_options']) ? $_POST['failsafe_options'] : array();
        
        // Sanitize the options using the existing method
        $sanitized_options = $this->sanitize_options($options);
        
        // Update the options
        $updated = update_option('failsafe_options', $sanitized_options);
        
        if ($updated !== false) {
            wp_send_json_success(array(
                'message' => __('Settings saved successfully!', 'failsafe'),
                'options' => $sanitized_options
            ));
        } else {
            wp_send_json_error(array('message' => __('Failed to save settings. Please try again.', 'failsafe')));
        }
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