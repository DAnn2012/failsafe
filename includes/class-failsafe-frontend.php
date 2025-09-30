<?php
/**
 * FailSafe Frontend Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Frontend {
    private $pending_errors;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_head', array($this, 'check_pending_errors'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('wp_ajax_failsafe_disable_plugin_frontend', array($this, 'ajax_disable_plugin_frontend'));
        add_action('wp_ajax_nopriv_failsafe_disable_plugin_frontend', array($this, 'ajax_disable_plugin_frontend'));
        add_action('wp_ajax_failsafe_dismiss_error_frontend', array($this, 'ajax_dismiss_error_frontend'));
        add_action('wp_ajax_nopriv_failsafe_dismiss_error_frontend', array($this, 'ajax_dismiss_error_frontend'));
    }
    
    /**
     * Check for pending errors and show notice
     */
    public function check_pending_errors() {
        $options = get_option('failsafe_options', array());
        
        if (!isset($options['show_frontend_notice']) || !$options['show_frontend_notice']) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $pending_errors = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare("SELECT * FROM {$table_name} WHERE status = %s ORDER BY error_time DESC LIMIT 5", 'pending') // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        );
        
        if (!empty($pending_errors)) {
            add_action('wp_footer', array($this, 'display_error_notice'));
            
            // Store errors for display
            $this->pending_errors = $pending_errors;
        }
    }
    
    /**
     * Display error notice
     */
    public function display_error_notice() {
        if (empty($this->pending_errors)) {
            return;
        }
        
        ?>
        <div id="failsafe-error-notice" class="failsafe-notice">
            <div class="failsafe-notice-content">
                <div class="failsafe-notice-header">
                    <h3><?php esc_html_e('FailSafe Error Detection', 'failsafe'); ?></h3>
                    <button type="button" class="failsafe-notice-close" aria-label="<?php esc_attr_e('Close', 'failsafe'); ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                
                <div class="failsafe-notice-body">
                    <?php foreach ($this->pending_errors as $error): ?>
                        <div class="failsafe-error-item" data-error-hash="<?php echo esc_attr($error->error_hash); ?>">
                            <div class="failsafe-error-details">
                                <strong><?php echo esc_html($error->error_type); ?></strong>
                                
                                <?php if ($error->plugin_theme_path): ?>
                                    <p>
                                        <?php 
                                        printf(
                                            /* translators: 1: Plugin/theme type, 2: Plugin/theme name */
                                            esc_html_e('The %1$s "%2$s" has caused a fatal error.', 'failsafe'),
                                            esc_html($error->plugin_theme_type),
                                            esc_html(basename($error->plugin_theme_path))
                                        ); 
                                        ?>
                                    </p>
                                    
                                    <p class="failsafe-error-message">
                                        <em><?php echo esc_html(wp_trim_words($error->error_message, 15)); ?></em>
                                    </p>
                                    
                                    <div class="failsafe-error-actions">
                                        <button type="button" 
                                                class="failsafe-btn failsafe-btn-danger failsafe-disable-btn" 
                                                data-error-hash="<?php echo esc_attr($error->error_hash); ?>">
                                            <?php 
                                            printf(
                                                /* translators: %s: Plugin/theme type */
                                                esc_html_e('Disable %s', 'failsafe'), 
                                                esc_html(ucfirst($error->plugin_theme_type))
                                            ); 
                                            ?>
                                        </button>
                                        
                                        <button type="button" 
                                                class="failsafe-btn failsafe-btn-secondary failsafe-dismiss-btn" 
                                                data-error-hash="<?php echo esc_attr($error->error_hash); ?>">
                                            <?php esc_html_e('Dismiss', 'failsafe'); ?>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <p><?php esc_html_e('An unknown error has occurred.', 'failsafe'); ?></p>
                                    <p class="failsafe-error-message">
                                        <em><?php echo esc_html(wp_trim_words($error->error_message, 15)); ?></em>
                                    </p>
                                    
                                    <div class="failsafe-error-actions">
                                        <button type="button" 
                                                class="failsafe-btn failsafe-btn-secondary failsafe-dismiss-btn" 
                                                data-error-hash="<?php echo esc_attr($error->error_hash); ?>">
                                            <?php esc_html_e('Dismiss', 'failsafe'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="failsafe-notice-footer">
                    <p class="failsafe-notice-info">
                        <?php 
                        printf(
                            /* translators: %s: Plugin name */
                            esc_html_e('This notice is shown by %s to help you recover from fatal errors.', 'failsafe'),
                            '<strong>FailSafe</strong>'
                        ); 
                        ?>
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?php echo esc_url(admin_url('options-general.php?page=failsafe-settings')); ?>">
                                <?php esc_html_e('Configure settings', 'failsafe'); ?>
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div id="failsafe-notice-overlay" class="failsafe-overlay"></div>
        <?php
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_frontend_scripts() {
        if (!empty($this->pending_errors)) {
            wp_enqueue_style('failsafe-frontend', FAILSAFE_PLUGIN_URL . 'assets/frontend.css', array(), FAILSAFE_VERSION);
            wp_enqueue_script('failsafe-frontend', FAILSAFE_PLUGIN_URL . 'assets/frontend.js', array('jquery'), FAILSAFE_VERSION, true);
            
            wp_localize_script('failsafe-frontend', 'failsafe_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('failsafe_frontend_nonce'),
                'strings' => array(
                    'processing' => esc_html_e('Processing...', 'failsafe'),
                    'error' => esc_html_e('An error occurred. Please try again.', 'failsafe'),
                    'success' => esc_html_e('Action completed successfully.', 'failsafe')
                )
            ));
        }
    }
    
    /**
     * AJAX handler for disabling plugins from frontend
     */
    public function ajax_disable_plugin_frontend() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing
        if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'failsafe_frontend_nonce')) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            wp_die(esc_html__('Security check failed.', 'failsafe'));
        }
        $error_hash = isset($_POST['error_hash']) ? sanitize_text_field(wp_unslash($_POST['error_hash'])) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $error = $wpdb->get_row($wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT * FROM {$table_name} WHERE error_hash = %s AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $error_hash,
            'pending'
        ));
        
        if (!$error) {
            wp_send_json_error(esc_html__('Error not found or already processed.', 'failsafe'));
        }
        
        $success = false;
        $message = '';
        
        if ($error->plugin_theme_type === 'plugin' && $error->plugin_theme_path) {
            if (is_plugin_active($error->plugin_theme_path)) {
                deactivate_plugins($error->plugin_theme_path);
                $success = true;
                $message = sprintf(
                    /* translators: %s: Plugin filename */
                    esc_html__('Plugin "%s" has been disabled successfully.', 'failsafe'), 
                    basename($error->plugin_theme_path)
                );
                
                // Log the action
                error_log("FailSafe: Disabled plugin {$error->plugin_theme_path} due to fatal error"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            } else {
                $message = esc_html__('Plugin is already disabled.', 'failsafe');
                $success = true;
            }
        } elseif ($error->plugin_theme_type === 'theme' && $error->plugin_theme_path) {
            switch_theme(WP_DEFAULT_THEME);
            $success = true;
            $message = esc_html__('Theme has been switched to default successfully.', 'failsafe');
            
            // Log the action
            error_log("FailSafe: Switched theme due to fatal error in {$error->plugin_theme_path}"); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
     * AJAX handler for dismissing errors from frontend
     */
    public function ajax_dismiss_error_frontend() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(wp_unslash($_POST['nonce']), 'failsafe_frontend_nonce')) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            wp_die(esc_html__('Security check failed.', 'failsafe'));
        }
        
        $error_hash = isset($_POST['error_hash']) ? sanitize_text_field(wp_unslash($_POST['error_hash'])) : '';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $result = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $table_name,
            array('status' => 'dismissed'),
            array('error_hash' => $error_hash, 'status' => 'pending'),
            array('%s'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => esc_html__('Error dismissed successfully.', 'failsafe')));
        } else {
            wp_send_json_error(esc_html__('Failed to dismiss error.', 'failsafe'));
        }
    }
}