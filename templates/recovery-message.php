<?php
// Prepare variables for template
$is_plugin = $plugin_theme_info['type'] === 'plugin';
$item_name = self::escape_html($plugin_theme_info['name']);
$recovery_url = $current_url . (strpos($current_url, '?') !== false ? '&' : '?') . 
               'failsafe_action=disable&failsafe_hash=' . urlencode($error_hash);

if (!$is_plugin) {
    $available_themes = self::get_available_themes();
    $current_theme = get_option('stylesheet');
    $has_themes = !empty($available_themes);
}
?>

<div style="background: #fff; border: 1px solid #ddd; padding: 20px; margin: 20px; border-radius: 5px; font-family: Arial, sans-serif; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
    <h3 style="color: #d63384; margin-top: 0;">FailSafe Recovery</h3>
    
    <?php if ($is_plugin): ?>
        <p>The plugin <strong><?= $item_name ?></strong> has caused a fatal error.</p>
        <p style="margin: 15px 0;">
            <a href="<?= self::escape_html($recovery_url) ?>" 
               style="background: #d63384; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
                Click here to disable it
            </a>
        </p>
    <?php else: ?>
        <p>The theme <strong><?= $item_name ?></strong> has caused a fatal error.</p>
        <p>Please select a theme to switch to:</p>
        
        <?php if ($has_themes): ?>
            <div style="margin: 15px 0;">
                <form method="get" style="margin: 0;">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if (!in_array($key, ['failsafe_action', 'failsafe_hash', 'failsafe_theme'])): ?>
                            <input type="hidden" name="<?= self::escape_html($key) ?>" value="<?= self::escape_html($value) ?>" />
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <input type="hidden" name="failsafe_action" value="switch_theme" />
                    <input type="hidden" name="failsafe_hash" value="<?= self::escape_html($error_hash) ?>" />
                    
                    <select name="failsafe_theme" style="padding: 8px; margin-right: 10px; border: 1px solid #ddd; border-radius: 3px; font-size: 14px;">
                        <option value="">-- Select a theme --</option>
                        <?php foreach ($available_themes as $theme_slug => $theme_name): ?>
                            <?php if ($theme_slug !== $current_theme): ?>
                                <option value="<?= self::escape_html($theme_slug) ?>"><?= self::escape_html($theme_name) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    
                    <input type="submit" value="Switch Theme" 
                           style="background: #d63384; color: white; padding: 8px 16px; border: none; border-radius: 3px; cursor: pointer; font-size: 14px;" />
                </form>
            </div>
        <?php else: ?>
            <p style="margin: 15px 0;">
                <a href="<?= self::escape_html($recovery_url) ?>" 
                   style="background: #d63384; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">
                    No other themes available - Click to disable theme
                </a>
            </p>
        <?php endif; ?>
    <?php endif; ?>
    
    <p style="font-size: 12px; color: #666; margin-bottom: 0;">
        This message is provided by FailSafe plugin to help you recover from fatal errors.
    </p>
</div>