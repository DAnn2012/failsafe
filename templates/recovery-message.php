<?php
/**
 * FailSafe Recovery Message Template
 *
 * EMERGENCY RECOVERY CONTEXT:
 * This template is displayed during fatal error recovery and operates in a unique context:
 *
 * 1. EXECUTION TIME: Runs at 'muplugins_loaded' hook (priority 1), before WordPress
 *    initializes sessions, authentication, nonces, or asset loading systems.
 *
 * 2. ASSET LOADING: Cannot use wp_enqueue_style/wp_enqueue_script because the WordPress
 *    asset system may not be available during a fatal error. Assets are loaded directly
 *    using readfile() with source files clearly documented.
 *
 * 3. SECURITY: Uses cryptographic hash-based authentication instead of nonces because:
 *    - WordPress nonce system is not initialized yet
 *    - User authentication system is not available
 *    - Site may be completely broken, admin needs recovery access
 *    - Hash provides sufficient security for emergency recovery (SHA-256, unique, validated)
 *
 * 4. TRANSLATIONS: Uses __() wrappers for i18n readiness. Note that plugin textdomain
 *    may not be loaded at muplugins_loaded, so strings may display in English during
 *    recovery. This is acceptable for an emergency recovery interface.
 *
 * This is an emergency recovery system designed to work even when WordPress core is broken.
 *
 * @package FailSafe
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the assets directory path.
$failsafe_assets_dir = defined( 'FAILSAFE_PLUGIN_DIR' ) ? FAILSAFE_PLUGIN_DIR . 'assets/' : dirname( __DIR__ ) . '/assets/';
$failsafe_css_file   = $failsafe_assets_dir . 'recovery.css';
$failsafe_js_file    = $failsafe_assets_dir . 'recovery.js';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e( 'Fatal Error Recovery', 'failsafe-fatal-error-recovery' ); ?></title>
    <style type="text/css">
    <?php
	/**
	 * Emergency recovery CSS loaded directly (not via wp_enqueue)
	 * This is necessary because during fatal errors, WordPress's asset
	 * loading system may not be available. Source: assets/recovery.css
	 */
	if ( file_exists( $failsafe_css_file ) ) {
		readfile( $failsafe_css_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Emergency recovery template; WP_Filesystem unavailable during fatal errors.
	}
	?>
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="header">
            <h1><?php esc_html_e( 'Fatal Error Recovery', 'failsafe-fatal-error-recovery' ); ?></h1>
            <p><?php esc_html_e( 'A Fatal Error has been detected. Use the options below to recover your site.', 'failsafe-fatal-error-recovery' ); ?></p>
        </div>

        <?php if ( $plugin_theme_info ) : ?>
        <div class="error-info">
            <h3><?php esc_html_e( 'Error Source Detected', 'failsafe-fatal-error-recovery' ); ?></h3>
            <p>
                <?php
                echo wp_kses(
                    sprintf(
                        /* translators: 1: Component type (plugin/theme), 2: Component name wrapped in <strong> tags */
                        __( 'The %1$s %2$s appears to have caused this fatal error.', 'failsafe-fatal-error-recovery' ),
                        esc_html( $causing_type ),
                        '<strong>' . esc_html( $causing_item ) . '</strong>'
                    ),
                    array( 'strong' => array() )
                );
                ?>
            </p>

            <?php if ( $show_error_details ) : ?>
                <!-- Error Details Accordion -->
                <div class="error-details-accordion">
                    <button class="error-details-toggle" onclick="toggleErrorDetails()" type="button">
                        <span class="toggle-icon">&#9654;</span>
                        <span class="toggle-text"><?php esc_html_e( 'Show original error', 'failsafe-fatal-error-recovery' ); ?></span>
                    </button>
                    <div class="error-details-content" id="errorDetailsContent">
                        <div class="error-details-inner">
                            <h4><?php esc_html_e( 'Error Details:', 'failsafe-fatal-error-recovery' ); ?></h4>
                            <div class="error-detail-item">
                                <strong><?php esc_html_e( 'Type:', 'failsafe-fatal-error-recovery' ); ?></strong> <?php echo esc_html( FailSafe_Helpers::get_error_type_name( $error['type'] ) ); ?>
                            </div>
                            <div class="error-detail-item">
                                <strong><?php esc_html_e( 'Message:', 'failsafe-fatal-error-recovery' ); ?></strong> <?php echo esc_html( $error['message'] ); ?>
                            </div>
                            <div class="error-detail-item">
                                <strong><?php esc_html_e( 'File:', 'failsafe-fatal-error-recovery' ); ?></strong> <?php echo esc_html( $error['file'] ); ?>
                            </div>
                            <div class="error-detail-item">
                                <strong><?php esc_html_e( 'Line:', 'failsafe-fatal-error-recovery' ); ?></strong> <?php echo esc_html( $error['line'] ); ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="content">
            <!-- Plugins Section -->
            <div class="section plugins-section">
                <h2 class="section-title"><?php esc_html_e( 'Deactivate Plugins', 'failsafe-fatal-error-recovery' ); ?></h2>

                <?php if ( ! empty( $active_plugins ) ) : ?>
                <form id="pluginForm" method="get" action="">
                    <input type="hidden" name="failsafe_action" value="deactivate_plugins" />
                    <input type="hidden" name="failsafe_hash" value="<?php echo esc_attr( $error_hash ); ?>" />

                    <div class="select-all-controls">
                        <button type="button" class="select-btn" onclick="selectAllPlugins()"><?php esc_html_e( 'Select All', 'failsafe-fatal-error-recovery' ); ?></button>
                        <button type="button" class="select-btn" onclick="selectNonePlugins()"><?php esc_html_e( 'Select None', 'failsafe-fatal-error-recovery' ); ?></button>
                        <?php if ( $plugin_theme_info && 'plugin' === $plugin_theme_info['type'] ) : ?>
                        <button type="button" class="select-btn" onclick="selectCausingPlugin()"><?php esc_html_e( 'Select Causing Plugin Only', 'failsafe-fatal-error-recovery' ); ?></button>
                        <?php endif; ?>
                    </div>

                    <div class="grid">
                        <?php foreach ( $active_plugins as $failsafe_plugin_path => $failsafe_plugin_name ) : ?>
                            <?php if ( 'failsafe-fatal-error-recovery/failsafe.php' !== $failsafe_plugin_path ) : ?>
                                <?php
                                $failsafe_is_causing = $plugin_theme_info && 'plugin' === $plugin_theme_info['type'] && $plugin_theme_info['path'] === $failsafe_plugin_path;
                                ?>
                                <div class="card <?php echo esc_attr( $failsafe_is_causing ? 'causing-error' : '' ); ?>">
                                    <div class="input-wrapper">
                                        <label class="custom-input">
                                            <input type="checkbox"
                                                    name="failsafe_plugins[]"
                                                    value="<?php echo esc_attr( $failsafe_plugin_path ); ?>"
                                                    <?php checked( $failsafe_is_causing ); ?>
                                                    class="plugin-checkbox"
                                                    data-causing="<?php echo esc_attr( $failsafe_is_causing ? 'true' : 'false' ); ?>" />
                                            <span class="input-mark"></span>
                                        </label>
                                        <span class="input-label item-name"><?php echo esc_html( $failsafe_plugin_name ); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </form>
                <?php else : ?>
                <p style="color: #718096; font-style: italic;"><?php esc_html_e( 'No active plugins found.', 'failsafe-fatal-error-recovery' ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $active_plugins ) ) : ?>
                    <button type="button" class="btn btn-danger" onclick="deactivateSelectedPlugins()" id="deactivateBtn" disabled>
                        <?php esc_html_e( 'Deactivate Selected Plugins', 'failsafe-fatal-error-recovery' ); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Themes Section -->
            <div class="section themes-section">
                <h2 class="section-title"><?php esc_html_e( 'Switch Theme', 'failsafe-fatal-error-recovery' ); ?></h2>

                <?php if ( ! empty( $available_themes ) ) : ?>
                <form id="themeForm" method="get" action="">
                    <input type="hidden" name="failsafe_action" value="switch_theme" />
                    <input type="hidden" name="failsafe_hash" value="<?php echo esc_attr( $error_hash ); ?>" />

                    <div class="grid">
                        <?php foreach ( $available_themes as $failsafe_theme_slug => $failsafe_theme_name ) : ?>
                            <?php if ( $failsafe_theme_slug !== $current_theme ) : ?>
                            <div class="card" onclick="selectTheme('theme_<?php echo esc_attr( $failsafe_theme_slug ); ?>')">
                                <div class="input-wrapper">
                                    <label class="custom-input">
                                        <input type="radio"
                                               id="theme_<?php echo esc_attr( $failsafe_theme_slug ); ?>"
                                               name="failsafe_theme"
                                               value="<?php echo esc_attr( $failsafe_theme_slug ); ?>" />
                                        <span class="input-mark"></span>
                                    </label>
                                    <span class="input-label item-name"><?php echo esc_html( $failsafe_theme_name ); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </form>
                <?php else : ?>
                <p style="color: #718096; font-style: italic;"><?php esc_html_e( 'No alternative themes available.', 'failsafe-fatal-error-recovery' ); ?></p>
                <?php endif; ?>
                <?php if ( ! empty( $available_themes ) ) : ?>
                    <button type="button" class="btn btn-success" onclick="switchSelectedTheme()" id="switchThemeBtn" disabled>
                        <?php esc_html_e( 'Switch to Selected Theme', 'failsafe-fatal-error-recovery' ); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-info">
            <?php
            echo wp_kses(
                sprintf(
                    /* translators: %s: Plugin name wrapped in <strong> tags */
                    __( 'This recovery interface is provided by the %s plugin to help you recover from fatal errors.', 'failsafe-fatal-error-recovery' ),
                    '<strong>FailSafe</strong>'
                ),
                array( 'strong' => array() )
            );
            ?>
        </div>
    </div>

    <script type="text/javascript">
    <?php
	/**
	 * Emergency recovery JS loaded directly (not via wp_enqueue)
	 * This is necessary because during fatal errors, WordPress's asset
	 * loading system may not be available. Source: assets/recovery.js
	 */
	if ( file_exists( $failsafe_js_file ) ) {
		readfile( $failsafe_js_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Emergency recovery template; WP_Filesystem unavailable during fatal errors.
	}
	?>
    </script>
</body>
</html>
