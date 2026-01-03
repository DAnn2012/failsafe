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
        add_action('admin_init', array($this, 'handle_download_action'));
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
            esc_html__('FailSafe Settings', 'failsafe-fatal-error-recovery'),
            esc_html__('FailSafe', 'failsafe-fatal-error-recovery'),
            'manage_options',
            'failsafe-fatal-error-recovery',
            array($this, 'settings_page')
        );

        add_management_page(
            esc_html__('FailSafe Error Logs', 'failsafe-fatal-error-recovery'),
            esc_html__('FailSafe Errors', 'failsafe-fatal-error-recovery'),
            'manage_options',
            'failsafe-fatal-error-recovery-errors',
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
            if (isset($_GET['page']) && $_GET['page'] === 'failsafe-fatal-error-recovery') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
            
            if (wp_doing_ajax() || is_network_admin()) {
                return;
            }
            
            // Redirect to settings page
            wp_safe_redirect(admin_url('options-general.php?page=failsafe-fatal-error-recovery&welcome=1'));
            exit;
        }
    }
    
    /**
     * Handle download action early before any output
     */
    public function handle_download_action() {
        if (isset($_GET['page']) && $_GET['page'] === 'failsafe-fatal-error-recovery-errors' && 
            isset($_GET['action']) && $_GET['action'] === 'download' && 
            isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'failsafe_download_logs')) {
            $this->download_error_logs();
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
        
        // Sanitize new settings
        $sanitized['delete_data_on_uninstall'] = isset($options['delete_data_on_uninstall']) && $options['delete_data_on_uninstall'];
        $sanitized['admin_only_recovery'] = isset($options['admin_only_recovery']) && $options['admin_only_recovery'];
        $sanitized['show_error_details'] = isset($options['show_error_details']) && $options['show_error_details'];
        
        // Sanitize protected plugins list
        if (isset($options['protected_plugins']) && is_array($options['protected_plugins'])) {
            $sanitized['protected_plugins'] = array();
            foreach ($options['protected_plugins'] as $plugin) {
                $plugin = sanitize_text_field($plugin);
                if (!empty($plugin)) {
                    $sanitized['protected_plugins'][] = $plugin;
                }
            }
        } else {
            $sanitized['protected_plugins'] = array();
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
                        <p><?php esc_html_e('Protect your website from fatal errors with intelligent error recovery', 'failsafe-fatal-error-recovery'); ?></p>
                    </div>
                </div>
                <div class="failsafe-status-indicator <?php echo $enabled ? 'enabled' : 'disabled'; ?>">
                    <span class="status-dot"></span>
                    <span class="status-text"><?php echo $enabled ? esc_html__('Active', 'failsafe-fatal-error-recovery') : esc_html__('Inactive', 'failsafe-fatal-error-recovery'); ?></span>
                </div>
            </div>

            <?php if (isset($_GET['welcome']) && $_GET['welcome'] == '1'): // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
            <div class="failsafe-welcome-message">
                <div class="failsafe-welcome-content">
                    <h2><?php esc_html_e('Welcome to FailSafe!', 'failsafe-fatal-error-recovery'); ?></h2>
                    <p><?php esc_html_e('Thank you for installing FailSafe. Your website is now protected from fatal errors. Configure the settings below to customize the protection level.', 'failsafe-fatal-error-recovery'); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="post" class="failsafe-settings-form" id="failsafe-settings-form">
                
                <div class="failsafe-settings-container">
                    <!-- Main Settings Card -->
                    <div class="failsafe-settings-card failsafe-main-card">
                        <div class="failsafe-card-header">
                            <h2><?php esc_html_e('FailSafe Settings', 'failsafe-fatal-error-recovery'); ?></h2>
                            <p><?php esc_html_e('Configure the main FailSafe settings to protect your website from fatal errors.', 'failsafe-fatal-error-recovery'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <?php 
                            $main_options = get_option('failsafe_options', array());
                            $enable_checked = isset($main_options['enable_failsafe']) ? $main_options['enable_failsafe'] : true;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="enable_failsafe" class="failsafe-setting-title"><?php esc_html_e('Enable FailSafe', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php esc_html_e('Enable or disable FailSafe error protection for your website', 'failsafe-fatal-error-recovery'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="enable_failsafe" name="failsafe_options[enable_failsafe]" value="1" <?php checked(1, $enable_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $enable_checked ? esc_html__('Enabled', 'failsafe-fatal-error-recovery') : esc_html__('Disabled', 'failsafe-fatal-error-recovery'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Types Card -->
                    <div class="failsafe-settings-card">
                        <div class="failsafe-card-header">
                            <h2><?php esc_html_e('Error Types to Monitor', 'failsafe-fatal-error-recovery'); ?></h2>
                            <p><?php esc_html_e('Select which types of errors should trigger the FailSafe system.', 'failsafe-fatal-error-recovery'); ?></p>
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
                                    <div class="failsafe-error-type-item <?php echo esc_attr($error_class); ?>">
                                        <div class="failsafe-error-type-header">
                                            <label for="error_type_<?php echo esc_attr($error_code); ?>" class="failsafe-error-type-label">
                                                <input type="checkbox" 
                                                       id="error_type_<?php echo esc_attr($error_code); ?>" 
                                                       name="failsafe_options[enabled_error_types][<?php echo esc_attr($error_code); ?>]" 
                                                       value="1" 
                                                       <?php checked(1, $checked, true); ?> />
                                                <span class="failsafe-checkbox-custom"></span>
                                                <span class="failsafe-error-type-name"><?php echo esc_html($error_name); ?></span>
                                            </label>
                                        </div>
                                        <div class="failsafe-error-type-description">
                                            <?php echo esc_html($this->get_error_description($error_code)); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Behavior Settings Card -->
                    <div class="failsafe-settings-card">
                        <div class="failsafe-card-header">
                            <h2><?php esc_html_e('Behavior Settings', 'failsafe-fatal-error-recovery'); ?></h2>
                            <p><?php esc_html_e('Configure additional behavior settings for error handling and logging.', 'failsafe-fatal-error-recovery'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <?php 
                            $log_checked = isset($options['log_errors']) ? $options['log_errors'] : true;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="log_errors" class="failsafe-setting-title"><?php esc_html_e('Keep log of errors', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php esc_html_e('Maintain a detailed log of all detected errors for analysis and troubleshooting', 'failsafe-fatal-error-recovery'); ?></p>
                                    <?php if (isset($options['log_errors']) && $options['log_errors']): ?>
                                        <div class="failsafe-setting-link">
                                            <a href="<?php echo esc_url(admin_url('tools.php?page=failsafe-fatal-error-recovery-errors')); ?>" class="failsafe-inline-link" target="_blank">
                                                <span class="dashicons dashicons-list-view"></span>
                                                <?php esc_html_e('View Error Logs', 'failsafe-fatal-error-recovery'); ?>
                                            </a>
                                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('tools.php?page=failsafe-fatal-error-recovery-errors&action=download'), 'failsafe_download_logs')); ?>" class="failsafe-inline-link" style="margin-left: 15px;" target="_blank">
                                                <span class="dashicons dashicons-download"></span>
                                                <?php esc_html_e('Download Logs', 'failsafe-fatal-error-recovery'); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="log_errors" name="failsafe_options[log_errors]" value="1" <?php checked(1, $log_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $log_checked ? esc_html__('Enabled', 'failsafe-fatal-error-recovery') : esc_html__('Disabled', 'failsafe-fatal-error-recovery'); ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label class="failsafe-setting-title"><?php esc_html_e('Recovery Access Roles', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php esc_html_e('Select which user roles can see recovery messages and deactivate plugins/themes', 'failsafe-fatal-error-recovery'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                <?php
                                    $selected_roles = isset($options['recovery_user_roles']) ? $options['recovery_user_roles'] : array('administrator');
                                    if (!is_array($selected_roles)) {
                                        $selected_roles = array('administrator');
                                    }
                                    
                                    global $wp_roles;
                                    $all_roles = $wp_roles->roles;
                                    
                                    echo '<select name="failsafe_options[recovery_user_roles][]" multiple class="failsafe-choices-select" id="recoveryRoles" data-placeholder="' . esc_attr__('Select user roles...', 'failsafe-fatal-error-recovery') . '">';
                                    foreach ($all_roles as $role_key => $role_info) {
                                        $selected = in_array($role_key, $selected_roles);
                                        printf(
                                            '<option value="%s" %s>%s</option>',
                                            esc_attr($role_key),
                                            selected(true, $selected, false),
                                            esc_html($role_info['name'])
                                        );
                                    }
                                    echo '</select>';
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings Card -->
                    <div class="failsafe-settings-card">
                        <div class="failsafe-card-header">
                            <h2><?php esc_html_e('Advanced Settings', 'failsafe-fatal-error-recovery'); ?></h2>
                            <p><?php esc_html_e('Configure advanced behavior and security options for the FailSafe plugin.', 'failsafe-fatal-error-recovery'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <?php 
                            $admin_only_checked = isset($options['admin_only_recovery']) ? $options['admin_only_recovery'] : false;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="admin_only_recovery" class="failsafe-setting-title"><?php esc_html_e('Admin-only error recovery interface', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php esc_html_e('Show error recovery interface only in admin area. When enabled, frontend users will not see recovery options.', 'failsafe-fatal-error-recovery'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="admin_only_recovery" name="failsafe_options[admin_only_recovery]" value="1" <?php checked(1, $admin_only_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $admin_only_checked ? esc_html_e('Admin Only', 'failsafe-fatal-error-recovery') : esc_html_e('All Users', 'failsafe-fatal-error-recovery'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <?php 
                            $show_details_checked = isset($options['show_error_details']) ? $options['show_error_details'] : true;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="show_error_details" class="failsafe-setting-title"><?php esc_html_e('Show detailed error messages', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php esc_html_e('Display actual error messages in the recovery interface. Disable this to show generic error messages for security.', 'failsafe-fatal-error-recovery'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="show_error_details" name="failsafe_options[show_error_details]" value="1" <?php checked(1, $show_details_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $show_details_checked ? esc_html_e('Show Details', 'failsafe-fatal-error-recovery') : esc_html_e('Hide Details', 'failsafe-fatal-error-recovery'); ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label class="failsafe-setting-title"><?php esc_html_e('Lock plugins', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php echo wp_kses_post('Select plugins that should be prevented from being disabled during error recovery. <br><strong>Caution!</strong> These plugins will not appear in recovery options and you won\'t be able to disable them.', 'failsafe-fatal-error-recovery'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <?php
                                    $protected_plugins = isset($options['protected_plugins']) ? $options['protected_plugins'] : array();
                                    if (!is_array($protected_plugins)) {
                                        $protected_plugins = array();
                                    }
                                    
                                    if (!function_exists('get_plugins')) {
                                        require_once ABSPATH . 'wp-admin/includes/plugin.php';
                                    }
                                    $all_plugins = get_plugins();
                                    ?>
                                    <select name="failsafe_options[protected_plugins][]" multiple class="failsafe-choices-select" id="protectedPlugins" data-placeholder="<?php esc_attr_e('Select plugins to lock...', 'failsafe-fatal-error-recovery'); ?>">
                                        <?php foreach ($all_plugins as $plugin_file => $plugin_data): ?>
                                            <option value="<?php echo esc_attr($plugin_file); ?>" <?php selected(true, in_array($plugin_file, $protected_plugins)); ?>>
                                                <?php echo esc_html($plugin_data['Name']) . ' (v' . esc_html($plugin_data['Version']) . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Data Management Settings Card -->
                    <div class="failsafe-settings-card">
                        <div class="failsafe-card-header">
                            <h2><?php esc_html_e('Data Management', 'failsafe-fatal-error-recovery'); ?></h2>
                            <p><?php esc_html_e('Configure how FailSafe handles data storage and cleanup.', 'failsafe-fatal-error-recovery'); ?></p>
                        </div>
                        <div class="failsafe-card-body">
                            <?php 
                            $delete_data_checked = isset($options['delete_data_on_uninstall']) ? $options['delete_data_on_uninstall'] : false;
                            ?>
                            <div class="failsafe-setting-row">
                                <div class="failsafe-setting-info">
                                    <label for="delete_data_on_uninstall" class="failsafe-setting-title"><?php esc_html_e('Delete all data on plugin uninstall', 'failsafe-fatal-error-recovery'); ?></label>
                                    <p class="failsafe-setting-description"><?php esc_html_e('When enabled, all FailSafe settings and error logs will be permanently deleted when the plugin is uninstalled. This cannot be undone.', 'failsafe-fatal-error-recovery'); ?></p>
                                </div>
                                <div class="failsafe-setting-control">
                                    <div class="failsafe-toggle-wrapper">
                                        <label class="failsafe-toggle">
                                            <input type="checkbox" id="delete_data_on_uninstall" name="failsafe_options[delete_data_on_uninstall]" value="1" <?php checked(1, $delete_data_checked, true); ?> />
                                            <span class="failsafe-toggle-slider"></span>
                                        </label>
                                        <span class="failsafe-toggle-label"><?php echo $delete_data_checked ? esc_html_e('Delete Data', 'failsafe-fatal-error-recovery') : esc_html_e('Keep Data', 'failsafe-fatal-error-recovery'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="failsafe-save-section">
                    <div class="failsafe-action-buttons">
                        <div class="failsafe-save-button" style="text-align: right;">
                            <?php submit_button(__('Save Settings', 'failsafe-fatal-error-recovery'), 'primary', 'submit', false); ?>
                        </div>
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
            E_ERROR => esc_html_e('Fatal run-time errors that cannot be recovered from', 'failsafe-fatal-error-recovery'),
            E_CORE_ERROR => esc_html_e('Fatal errors that occur during PHP startup', 'failsafe-fatal-error-recovery'),
            E_PARSE => esc_html_e('Compile-time parse errors in PHP syntax', 'failsafe-fatal-error-recovery'),
            E_COMPILE_ERROR => esc_html_e('Fatal compile-time errors in PHP code', 'failsafe-fatal-error-recovery'),
            E_USER_ERROR => esc_html_e('User-generated error messages', 'failsafe-fatal-error-recovery'),
            E_RECOVERABLE_ERROR => esc_html_e('Catchable fatal errors that can be recovered', 'failsafe-fatal-error-recovery')
        );
        
        return isset($descriptions[$error_code]) ? $descriptions[$error_code] : '';
    }
    
    /**
     * Error logs page
     */
    public function error_logs_page() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        // Handle dismiss action
        if (isset($_GET['action']) && isset($_GET['error_id'])) {
            $error_id = intval($_GET['error_id']);
            
            if ($_GET['action'] === 'dismiss' && isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dismiss_error_' . $error_id)) {
                $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $table_name,
                    array('status' => 'dismissed'),
                    array('id' => $error_id),
                    array('%s'),
                    array('%d')
                );
                
                echo '<div class="notice notice-success"><p>' . esc_html_e('Error dismissed.', 'failsafe-fatal-error-recovery') . '</p></div>';
            }
        }
        
        // Get error logs
        $errors = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY error_time DESC LIMIT 50") // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <?php if (empty($errors)): ?>
                <p><?php esc_html_e('No errors logged yet.', 'failsafe-fatal-error-recovery'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Time', 'failsafe-fatal-error-recovery'); ?></th>
                            <th><?php esc_html_e('Type', 'failsafe-fatal-error-recovery'); ?></th>
                            <th><?php esc_html_e('Message', 'failsafe-fatal-error-recovery'); ?></th>
                            <th><?php esc_html_e('File', 'failsafe-fatal-error-recovery'); ?></th>
                            <th><?php esc_html_e('Plugin/Theme', 'failsafe-fatal-error-recovery'); ?></th>
                            <th><?php esc_html_e('Status', 'failsafe-fatal-error-recovery'); ?></th>
                            <th><?php esc_html_e('Actions', 'failsafe-fatal-error-recovery'); ?></th>
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
                                        <?php esc_html_e('Unknown', 'failsafe-fatal-error-recovery'); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-<?php echo esc_attr($error->status); ?>">
                                        <?php echo esc_html(ucfirst($error->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($error->status === 'pending'): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('action' => 'dismiss', 'error_id' => $error->id)), 'dismiss_error_' . $error->id)); ?>" 
                                           class="button button-small">
                                            <?php esc_html_e('Dismiss', 'failsafe-fatal-error-recovery'); ?>
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
        if (strpos($hook, 'failsafe-fatal-error-recovery') !== false) {
            wp_enqueue_style('failsafe-admin', FAILSAFE_PLUGIN_URL . 'build/style.css', array(), FAILSAFE_VERSION);
            wp_enqueue_style('choices-css', FAILSAFE_PLUGIN_URL . 'assets/choices.min.css', array(), '10.2.0');
            wp_enqueue_script('choices-js', FAILSAFE_PLUGIN_URL . 'assets/choices.min.js', array(), '10.2.0', true);
            wp_enqueue_script('failsafe-admin', FAILSAFE_PLUGIN_URL . 'assets/admin.js', array('jquery', 'choices-js'), FAILSAFE_VERSION, true);
            
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'failsafe_nonce') || !current_user_can('manage_options')) {
            wp_die(esc_html__('Security check failed.', 'failsafe-fatal-error-recovery'));
        }
        
        $error_hash = isset($_POST['error_hash']) ? sanitize_text_field(wp_unslash($_POST['error_hash'])) : '';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $error = $wpdb->get_row($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT * FROM {$table_name} WHERE error_hash = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $error_hash
        ));
        
        if (!$error) {
            wp_send_json_error(esc_html__('Error not found.', 'failsafe-fatal-error-recovery'));
        }
        
        $success = false;
        $message = '';
        
        if ($error->plugin_theme_type === 'plugin' && $error->plugin_theme_path) {
            if (is_plugin_active($error->plugin_theme_path)) {
                deactivate_plugins($error->plugin_theme_path);
                $success = true;
                /* translators: %s: Plugin filename */
                $message = sprintf(__('Plugin %s has been disabled.', 'failsafe-fatal-error-recovery'), basename($error->plugin_theme_path));
            } else {
                $message = esc_html__('Plugin is already disabled.', 'failsafe-fatal-error-recovery');
            }
        } elseif ($error->plugin_theme_type === 'theme' && $error->plugin_theme_path) {
            switch_theme(WP_DEFAULT_THEME);
            $success = true;
            $message = esc_html__('Theme has been switched to default.', 'failsafe-fatal-error-recovery');
        }
        
        if ($success) {
            // Update error status
            $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'failsafe_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(array('message' => esc_html__('Security check failed.', 'failsafe-fatal-error-recovery')));
        }
        
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below by sanitize_options()
        $options = isset( $_POST['failsafe_options'] ) ? map_deep( wp_unslash( $_POST['failsafe_options'] ), 'sanitize_text_field' ) : array();

        $sanitized_options = $this->sanitize_options( $options );
        
        // Update the options
        update_option('failsafe_options', $sanitized_options);
        
		wp_send_json_success(array(
			'message' => esc_html__('Settings saved successfully!', 'failsafe-fatal-error-recovery'),
			'options' => $sanitized_options
		));
    }
    
    /**
     * Show MU-plugin status notice
     */
    public function show_mu_plugin_status() {
        // Only show on FailSafe plugin pages
        $screen = get_current_screen();
        if (!$screen || (strpos($screen->id, 'failsafe-fatal-error-recovery') === false && $screen->id !== 'plugins')) {
            return;
        }

        $failsafe_recovery = get_option('failsafe_recovery', array());

        if ( !empty($failsafe_recovery) && isset($failsafe_recovery['recovered']) && $failsafe_recovery['recovered'] ) {
            $message = '';
            
            // Check if plugins were deactivated
            if (isset($failsafe_recovery['deactivated_plugins']) && !empty($failsafe_recovery['deactivated_plugins'])) {
                $deactivated_plugin_names = array();
                
                foreach ($failsafe_recovery['deactivated_plugins'] as $plugin_path) {
                    // Get plugin name from the path
                    $plugin_name = basename($plugin_path, '.php');
                    
                    // Try to get the actual plugin name if possible
                    if (function_exists('get_plugin_data')) {
                        $plugin_full_path = FailSafe_Helpers::get_plugins_dir() . '/' . $plugin_path;
                        $plugin_data      = get_plugin_data( $plugin_full_path );
                        if (!empty($plugin_data['Name'])) {
                            $plugin_name = $plugin_data['Name'];
                        }
                    }
                    
                    $deactivated_plugin_names[] = $plugin_name;
                }
                
                if (count($deactivated_plugin_names) === 1) {
                    $message = sprintf('Plugin "%s" has been successfully deactivated.', $deactivated_plugin_names[0]);
                } else {
                    $message = sprintf('The following plugins have been successfully deactivated: %s', implode(', ', $deactivated_plugin_names));
                }
            } 
            // Check if theme was switched
            elseif (isset($failsafe_recovery['switched_to'])) {
                $theme_name = $failsafe_recovery['switched_to'];
                
                // Try to get the actual theme name if possible
                if (function_exists('wp_get_theme')) {
                    $theme = wp_get_theme($failsafe_recovery['switched_to']);
                    if ($theme->exists()) {
                        $theme_name = $theme->get('Name');
                    }
                }
                
                $message = sprintf('Theme has been successfully switched to "%s".', $theme_name);
            }
            // Fallback to original behavior for other recovery methods
            elseif (isset($failsafe_recovery['type']) && isset($failsafe_recovery['name'])) {
                $message = sprintf('The %s %s has been successfully disabled.', $failsafe_recovery['type'], $failsafe_recovery['name']);
            }
            
            if (!empty($message)) {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php echo esc_html($message); ?>
                    </p>
                </div>
                <?php
            }

            delete_option('failsafe_recovery');
        }
    }
    
    /**
     * Download error logs as CSV file
     */
    private function download_error_logs() {
        global $wpdb;
        
        // Clean any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        // Get all error logs
        $errors = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY error_time DESC") // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
        
        if (empty($errors)) {
            wp_die(esc_html__('No error logs to download.', 'failsafe-fatal-error-recovery'));
        }
        
        // Set headers for CSV download
        $filename = 'failsafe-error-logs-' . gmdate('Y-m-d-H-i-s') . '.csv';
        
        // Ensure no output has been sent
        if (headers_sent()) {
            wp_die(esc_html__('Headers already sent. Cannot download file.', 'failsafe-fatal-error-recovery'));
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Pragma: no-cache');
        
        // Initialize WP_Filesystem
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        
        // Generate CSV content
        $csv_content = '';
        
        // Add CSV header
        $header_row = array(
            'ID',
            'Error Hash',
            'Error Type',
            'Error Message', 
            'Error File',
            'Error Line',
            'Plugin/Theme Path',
            'Plugin/Theme Type',
            'Error Time',
            'Status'
        );
        $csv_content .= '"' . implode('","', array_map('str_replace', array('"'), array('""'), $header_row)) . '"' . "\n";
        
        // Add error data
        foreach ($errors as $error) {
            $row = array(
                $error->id,
                $error->error_hash,
                $error->error_type,
                $error->error_message,
                $error->error_file,
                $error->error_line,
                $error->plugin_theme_path,
                $error->plugin_theme_type,
                $error->error_time,
                $error->status
            );
            $csv_content .= '"' . implode('","', array_map('str_replace', array('"'), array('""'), $row)) . '"' . "\n";
        }
        
        // Output CSV content
        echo $csv_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }
}