<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatal Error Recovery</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }
        
        .recovery-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            width: 100%;
            overflow: hidden;
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 1px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            color: white;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
            margin: 12px !important;
        }
        
        .error-info {
            background: #fff5f5;
            border-left: 4px solid #ff6b6b;
            padding: 20px;
            margin: 0;
        }
        
        .error-info h3 {
            color: #c53030;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .error-info p {
            color: #744444;
            line-height: 1.5;
            margin: 0 !important;
            
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title::before {
            content: '';
            width: 4px;
            height: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 2px;
        }
        
        .section.plugins-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .section.themes-section {
            background: #f0fff4;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }
        
        .card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
        }
        
        .card.causing-error {
            background: #fff5f5;
            border-color: #ff6b6b;
        }
        
        .card.causing-error::after {
            content: '⚠️ Causing Error';
            position: absolute;
            top: -8px;
            right: 12px;
            background: #ff6b6b;
            color: white;
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: 500;
        }
        
        .card.selected {
            border-color: #68d391;
            background: #f0fff4;
        }
        
        .item-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 16px;
        }
        
        .input-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .custom-input {
            position: relative;
            display: inline-block;
            width: 20px;
            height: 20px;
        }
        
        .custom-input input {
            opacity: 0;
            width: 100%;
            height: 100%;
            position: absolute;
            cursor: pointer;
        }
        
        .input-mark {
            position: absolute;
            top: 0;
            left: 0;
            height: 20px;
            width: 20px;
            background: white;
            border: 2px solid #cbd5e0;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .custom-input:hover .input-mark {
            border-color: #667eea;
        }
        
        .custom-input input:checked ~ .input-mark {
            background: #667eea;
            border-color: #667eea;
        }
        
        .input-mark::after {
            content: "";
            position: absolute;
            display: none;
            left: 5px;
            top: 0px;
            width: 6px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        
        .custom-input input:checked ~ .input-mark::after {
            display: block;
        }
        
        .input-label {
            font-size: 14px;
            color: #4a5568;
            cursor: pointer;
            user-select: none;
        }
        
        /* Radio button specific styles */
        .custom-input input[type="radio"] ~ .input-mark {
            border-radius: 50%;
        }
        
        .custom-input input[type="radio"]:checked ~ .input-mark::after {
            left: 4px;
            top: 4px;
            width: 8px;
            height: 8px;
            border: none;
            background: white;
            border-radius: 50%;
            transform: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
        }
        
        .btn {
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #51cf66, #40c057);
            color: white;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn:disabled:hover {
            transform: none;
            box-shadow: none;
        }
        
        .select-all-controls {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .select-btn {
            padding: 8px 16px;
            background: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            color: #4a5568;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .select-btn:hover {
            background: #e2e8f0;
            border-color: #a0aec0;
        }
        
        .footer-info {
            background: #f7fafc;
            padding: 20px;
            text-align: center;
            color: #718096;
            font-size: 14px;
            border-top: 1px solid #e2e8f0;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="recovery-container">
        <div class="header">
            <h1>Fatal Error Recovery</h1>
            <p>A Fatal Error has been detected. Use the options below to recover your site.</p>
        </div>
        
        <?php if ($plugin_theme_info): ?>
        <div class="error-info">
            <h3>Error Source Detected</h3>
            <p>The <?= $causing_type ?> <strong><?= $causing_item ?></strong> appears to have caused this fatal error.</p>
        </div>
        <?php endif; ?>
        
        <div class="content">
            <!-- Plugins Section -->
            <div class="section plugins-section">
                <h2 class="section-title">Deactivate Plugins</h2>
                
                <?php if (!empty($active_plugins)): ?>
                <form id="pluginForm" method="get" action="">
                    <!-- Preserve existing GET parameters -->
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if (!in_array($key, ['failsafe_action', 'failsafe_plugins'])): ?>
                            <input type="hidden" name="<?= FailSafe_Helpers::escape_html($key) ?>" value="<?= FailSafe_Helpers::escape_html($value) ?>" />
                        <?php endif; ?>
                    <?php endforeach; ?>
                    
                    <input type="hidden" name="failsafe_action" value="deactivate_plugins" />
                    <input type="hidden" name="failsafe_hash" value="<?= FailSafe_Helpers::escape_html($error_hash) ?>" />
                    
                    <div class="select-all-controls">
                        <button type="button" class="select-btn" onclick="selectAllPlugins()">Select All</button>
                        <button type="button" class="select-btn" onclick="selectNonePlugins()">Select None</button>
                        <?php if ($plugin_theme_info && $plugin_theme_info['type'] === 'plugin'): ?>
                        <button type="button" class="select-btn" onclick="selectCausingPlugin()">Select Causing Plugin Only</button>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid">
                        <?php foreach ($active_plugins as $plugin_path => $plugin_name): ?>
                            <?php if ($plugin_path !== 'failsafe/failsafe.php'): ?>
                                <div class="card <?= ($plugin_theme_info && $plugin_theme_info['type'] === 'plugin' && $plugin_theme_info['path'] === $plugin_path) ? 'causing-error' : '' ?>">
                                    <div class="input-wrapper">
                                        <label class="custom-input">
                                            <input type="checkbox" 
                                                    name="failsafe_plugins[]" 
                                                    value="<?= Failsafe_Helpers::escape_html($plugin_path) ?>"
                                                    <?= ($plugin_theme_info && $plugin_theme_info['type'] === 'plugin' && $plugin_theme_info['path'] === $plugin_path) ? 'checked' : '' ?>
                                                    class="plugin-checkbox"
                                                    data-causing="<?= ($plugin_theme_info && $plugin_theme_info['type'] === 'plugin' && $plugin_theme_info['path'] === $plugin_path) ? 'true' : 'false' ?>" />
                                            <span class="input-mark"></span>
                                        </label>
                                        <span class="input-label item-name"><?= Failsafe_Helpers::escape_html($plugin_name) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </form>
                <?php else: ?>
                <p style="color: #718096; font-style: italic;">No active plugins found.</p>
                <?php endif; ?>
                <?php if (!empty($active_plugins)): ?>
                    <button type="button" class="btn btn-danger" onclick="deactivateSelectedPlugins()" id="deactivateBtn" disabled>
                        Deactivate Selected Plugins
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Themes Section -->
            <div class="section themes-section">
                <h2 class="section-title">Switch Theme</h2>
                
                <?php if (!empty($available_themes)): ?>
                <form id="themeForm" method="get" action="">
                    <input type="hidden" name="failsafe_action" value="switch_theme" />
                    <input type="hidden" name="failsafe_hash" value="<?= Failsafe_Helpers::escape_html($error_hash) ?>" />
                    
                    <div class="grid">
                        <?php foreach ($available_themes as $theme_slug => $theme_name): ?>
                            <?php if ($theme_slug !== $current_theme): ?>
                            <div class="card" onclick="selectTheme('theme_<?= Failsafe_Helpers::escape_html($theme_slug) ?>')">
                                <div class="input-wrapper">
                                    <label class="custom-input">
                                        <input type="radio" 
                                               id="theme_<?= Failsafe_Helpers::escape_html($theme_slug) ?>" 
                                               name="failsafe_theme" 
                                               value="<?= Failsafe_Helpers::escape_html($theme_slug) ?>" />
                                        <span class="input-mark"></span>
                                    </label>
                                    <span class="input-label item-name"><?= Failsafe_Helpers::escape_html($theme_name) ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </form>
                <?php else: ?>
                <p style="color: #718096; font-style: italic;">No alternative themes available.</p>
                <?php endif; ?>
                <?php if (!empty($available_themes)): ?>
                    <button type="button" class="btn btn-success" onclick="switchSelectedTheme()" id="switchThemeBtn" disabled>
                        Switch to Selected Theme
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="footer-info">
            This recovery interface is provided by the <strong>FailSafe</strong> plugin to help you recover from fatal errors.
        </div>
    </div>
    
    <script>
        // Plugin selection functions
        function selectAllPlugins() {
            const checkboxes = document.querySelectorAll('.plugin-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
            updateDeactivateButton();
        }
        
        function selectNonePlugins() {
            const checkboxes = document.querySelectorAll('.plugin-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
            updateDeactivateButton();
        }
        
        function selectCausingPlugin() {
            const checkboxes = document.querySelectorAll('.plugin-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = cb.getAttribute('data-causing') === 'true';
            });
            updateDeactivateButton();
        }
        
        function selectTheme(themeId) {
            const radio = document.getElementById(themeId);
            if (radio) {
                radio.checked = true;
                updateThemeButton();
                updateThemeCardSelection();
            }
        }
        
        function updateDeactivateButton() {
            const checkboxes = document.querySelectorAll('.plugin-checkbox:checked');
            const btn = document.getElementById('deactivateBtn');
            if (btn) {
                btn.disabled = checkboxes.length === 0;
            }
        }
        
        function updateThemeButton() {
            const selected = document.querySelector('input[name="failsafe_theme"]:checked');
            const btn = document.getElementById('switchThemeBtn');
            if (btn) {
                btn.disabled = !selected;
            }
        }
        
        function updateThemeCardSelection() {
            const themeCards = document.querySelectorAll('.themes-section .card');
            const selectedRadio = document.querySelector('input[name="failsafe_theme"]:checked');
            
            themeCards.forEach(card => {
                card.classList.remove('selected');
            });
            
            if (selectedRadio) {
                const selectedCard = selectedRadio.closest('.card');
                if (selectedCard) {
                    selectedCard.classList.add('selected');
                }
            }
        }
        
        function deactivateSelectedPlugins() {
            const form = document.getElementById('pluginForm');
            const checkboxes = document.querySelectorAll('.plugin-checkbox:checked');
            
            if (checkboxes.length === 0) {
                alert('Please select at least one plugin to deactivate.');
                return;
            }
            
            if (confirm(`Are you sure you want to deactivate ${checkboxes.length} plugin(s)?`)) {
                form.submit();
            }
        }
        
        function switchSelectedTheme() {
            const form = document.getElementById('themeForm');
            const selected = document.querySelector('input[name="failsafe_theme"]:checked');
            
            if (!selected) {
                alert('Please select a theme to switch to.');
                return;
            }
            
            const themeName = selected.closest('.card').querySelector('.item-name').textContent;
            if (confirm(`Are you sure you want to switch to the "${themeName}" theme?`)) {
                form.submit();
            }
        }
        
        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Update button states on checkbox change
            const pluginCheckboxes = document.querySelectorAll('.plugin-checkbox');
            pluginCheckboxes.forEach(cb => {
                cb.addEventListener('change', updateDeactivateButton);
            });
            
            // Update button states on theme selection change
            const themeRadios = document.querySelectorAll('input[name="failsafe_theme"]');
            themeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    updateThemeButton();
                    updateThemeCardSelection();
                });
            });
            
            // Initial button state updates
            updateDeactivateButton();
            updateThemeButton();
            updateThemeCardSelection();
        });
    </script>
</body>
</html>
