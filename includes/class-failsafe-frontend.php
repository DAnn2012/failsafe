<?php
/**
 * FailSafe Frontend Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FailSafe_Frontend {
    
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
        
        $pending_errors = $wpdb->get_results(
            "SELECT * FROM $table_name WHERE status = 'pending' ORDER BY error_time DESC LIMIT 5"
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
                    <h3><?php _e('FailSafe Error Detection', 'failsafe'); ?></h3>
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
                                            __('The %s "%s" has caused a fatal error.', 'failsafe'),
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
                                                __('Disable %s', 'failsafe'), 
                                                ucfirst($error->plugin_theme_type)
                                            ); 
                                            ?>
                                        </button>
                                        
                                        <button type="button" 
                                                class="failsafe-btn failsafe-btn-secondary failsafe-dismiss-btn" 
                                                data-error-hash="<?php echo esc_attr($error->error_hash); ?>">
                                            <?php _e('Dismiss', 'failsafe'); ?>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <p><?php _e('An unknown error has occurred.', 'failsafe'); ?></p>
                                    <p class="failsafe-error-message">
                                        <em><?php echo esc_html(wp_trim_words($error->error_message, 15)); ?></em>
                                    </p>
                                    
                                    <div class="failsafe-error-actions">
                                        <button type="button" 
                                                class="failsafe-btn failsafe-btn-secondary failsafe-dismiss-btn" 
                                                data-error-hash="<?php echo esc_attr($error->error_hash); ?>">
                                            <?php _e('Dismiss', 'failsafe'); ?>
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
                            __('This notice is shown by %s to help you recover from fatal errors.', 'failsafe'),
                            '<strong>FailSafe</strong>'
                        ); 
                        ?>
                        <?php if (current_user_can('manage_options')): ?>
                            <a href="<?php echo admin_url('options-general.php?page=failsafe-settings'); ?>">
                                <?php _e('Configure settings', 'failsafe'); ?>
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
                    'processing' => __('Processing...', 'failsafe'),
                    'error' => __('An error occurred. Please try again.', 'failsafe'),
                    'success' => __('Action completed successfully.', 'failsafe')
                )
            ));
        }
    }
    
    /**
     * AJAX handler for disabling plugins from frontend
     */
    public function ajax_disable_plugin_frontend() {
        if (!wp_verify_nonce($_POST['nonce'], 'failsafe_frontend_nonce')) {
            wp_die(__('Security check failed.', 'failsafe'));
        }
        
        $error_hash = sanitize_text_field($_POST['error_hash']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $error = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE error_hash = %s AND status = 'pending'",
            $error_hash
        ));
        
        if (!$error) {
            wp_send_json_error(__('Error not found or already processed.', 'failsafe'));
        }
        
        $success = false;
        $message = '';
        
        if ($error->plugin_theme_type === 'plugin' && $error->plugin_theme_path) {
            if (is_plugin_active($error->plugin_theme_path)) {
                deactivate_plugins($error->plugin_theme_path);
                $success = true;
                $message = sprintf(
                    __('Plugin "%s" has been disabled successfully.', 'failsafe'), 
                    basename($error->plugin_theme_path)
                );
                
                // Log the action
                error_log("FailSafe: Disabled plugin {$error->plugin_theme_path} due to fatal error");
            } else {
                $message = __('Plugin is already disabled.', 'failsafe');
                $success = true;
            }
        } elseif ($error->plugin_theme_type === 'theme' && $error->plugin_theme_path) {
            switch_theme(WP_DEFAULT_THEME);
            $success = true;
            $message = __('Theme has been switched to default successfully.', 'failsafe');
            
            // Log the action
            error_log("FailSafe: Switched theme due to fatal error in {$error->plugin_theme_path}");
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
     * AJAX handler for dismissing errors from frontend
     */
    public function ajax_dismiss_error_frontend() {
        if (!wp_verify_nonce($_POST['nonce'], 'failsafe_frontend_nonce')) {
            wp_die(__('Security check failed.', 'failsafe'));
        }
        
        $error_hash = sanitize_text_field($_POST['error_hash']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'failsafe_error_logs';
        
        $result = $wpdb->update(
            $table_name,
            array('status' => 'dismissed'),
            array('error_hash' => $error_hash, 'status' => 'pending'),
            array('%s'),
            array('%s', '%s')
        );
        
        if ($result !== false) {
            wp_send_json_success(array('message' => __('Error dismissed successfully.', 'failsafe')));
        } else {
            wp_send_json_error(__('Failed to dismiss error.', 'failsafe'));
        }
    }
}