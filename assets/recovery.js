/**
 * FailSafe Recovery Page Scripts
 *
 * These scripts are used for the fatal error recovery interface.
 * This file is loaded inline during fatal errors because WordPress's
 * normal asset loading (wp_enqueue_script) may not be available.
 *
 * @package FailSafe
 */

// Plugin selection functions
function selectAllPlugins() {
    var checkboxes = document.querySelectorAll('.plugin-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = true;
    });
    updateDeactivateButton();
}

function selectNonePlugins() {
    var checkboxes = document.querySelectorAll('.plugin-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = false;
    });
    updateDeactivateButton();
}

function selectCausingPlugin() {
    var checkboxes = document.querySelectorAll('.plugin-checkbox');
    checkboxes.forEach(function(cb) {
        cb.checked = cb.getAttribute('data-causing') === 'true';
    });
    updateDeactivateButton();
}

function selectTheme(themeId) {
    var radio = document.getElementById(themeId);
    if (radio) {
        radio.checked = true;
        updateThemeButton();
        updateThemeCardSelection();
    }
}

function updateDeactivateButton() {
    var checkboxes = document.querySelectorAll('.plugin-checkbox:checked');
    var btn = document.getElementById('deactivateBtn');
    if (btn) {
        btn.disabled = checkboxes.length === 0;
    }
}

function updateThemeButton() {
    var selected = document.querySelector('input[name="failsafe_theme"]:checked');
    var btn = document.getElementById('switchThemeBtn');
    if (btn) {
        btn.disabled = !selected;
    }
}

function updateThemeCardSelection() {
    var themeCards = document.querySelectorAll('.themes-section .card');
    var selectedRadio = document.querySelector('input[name="failsafe_theme"]:checked');

    themeCards.forEach(function(card) {
        card.classList.remove('selected');
    });

    if (selectedRadio) {
        var selectedCard = selectedRadio.closest('.card');
        if (selectedCard) {
            selectedCard.classList.add('selected');
        }
    }
}

function deactivateSelectedPlugins() {
    var form = document.getElementById('pluginForm');
    var checkboxes = document.querySelectorAll('.plugin-checkbox:checked');

    if (checkboxes.length === 0) {
        alert('Please select at least one plugin to deactivate.');
        return;
    }

    if (confirm('Are you sure you want to deactivate ' + checkboxes.length + ' plugin(s)?')) {
        form.submit();
    }
}

function switchSelectedTheme() {
    var form = document.getElementById('themeForm');
    var selected = document.querySelector('input[name="failsafe_theme"]:checked');

    if (!selected) {
        alert('Please select a theme to switch to.');
        return;
    }

    var themeName = selected.closest('.card').querySelector('.item-name').textContent;
    if (confirm('Are you sure you want to switch to the "' + themeName + '" theme?')) {
        form.submit();
    }
}

// Error details accordion toggle function
function toggleErrorDetails() {
    var toggle = document.querySelector('.error-details-toggle');
    var content = document.getElementById('errorDetailsContent');
    var toggleText = document.querySelector('.toggle-text');

    if (content.classList.contains('expanded')) {
        // Collapse
        content.classList.remove('expanded');
        toggle.classList.remove('expanded');
        toggleText.textContent = 'Show original error';
    } else {
        // Expand
        content.classList.add('expanded');
        toggle.classList.add('expanded');
        toggleText.textContent = 'Hide original error';
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Update button states on checkbox change
    var pluginCheckboxes = document.querySelectorAll('.plugin-checkbox');
    pluginCheckboxes.forEach(function(cb) {
        cb.addEventListener('change', updateDeactivateButton);
    });

    // Update button states on theme selection change
    var themeRadios = document.querySelectorAll('input[name="failsafe_theme"]');
    themeRadios.forEach(function(radio) {
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
