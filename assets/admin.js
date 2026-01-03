/* FailSafe Admin JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    // Handle toggle switches with label updates
    $(document).on('change', '.failsafe-toggle input[type="checkbox"]', function() {
        var $toggle = $(this);
        var $label = $toggle.closest('.failsafe-toggle-wrapper').find('.failsafe-toggle-label');
        var isChecked = $toggle.is(':checked');
        
        // Update label text with smooth transition
        $label.fadeOut(150, function() {
            $(this).text(isChecked ? 'Enabled' : 'Disabled').fadeIn(150);
        });
        
        // Update status indicator if it's the main failsafe toggle
        if ($toggle.attr('id') === 'enable_failsafe') {
            var $statusIndicator = $('.failsafe-status-indicator');
            var $statusText = $statusIndicator.find('.status-text');
            
            $statusIndicator.removeClass('enabled disabled').addClass(isChecked ? 'enabled' : 'disabled');
            $statusText.fadeOut(150, function() {
                $(this).text(isChecked ? 'Active' : 'Inactive').fadeIn(150);
            });
        }
    });
    
    // Smooth card hover effects
    $('.failsafe-settings-card').hover(
        function() {
            $(this).addClass('hovered');
        },
        function() {
            $(this).removeClass('hovered');
        }
    );
    
    // Toggle error details
    $(document).on('click', '.failsafe-error-details-toggle', function(e) {
        e.preventDefault();
        
        var $toggle = $(this);
        var $details = $toggle.closest('td').find('.failsafe-error-details');
        
        $details.toggleClass('show');
        
        if ($details.hasClass('show')) {
            $toggle.text('Hide Details');
        } else {
            $toggle.text('Show Details');
        }
    });
    
    // Handle bulk actions for error logs
    $(document).on('click', '.failsafe-bulk-action', function(e) {
        e.preventDefault();
        
        var action = $(this).data('action');
        var $checkedItems = $('.failsafe-error-checkbox:checked');
        
        if ($checkedItems.length === 0) {
            alert('Please select at least one error to perform this action.');
            return;
        }
        
        var errorIds = [];
        $checkedItems.each(function() {
            errorIds.push($(this).val());
        });
        
        var confirmMessage = '';
        switch (action) {
            case 'dismiss':
                confirmMessage = 'Are you sure you want to dismiss the selected errors?';
                break;
            case 'delete':
                confirmMessage = 'Are you sure you want to permanently delete the selected errors?';
                break;
            default:
                return;
        }
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).html(originalText + ' <span class="failsafe-spinner"></span>');
        
        $.ajax({
            url: failsafe_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'failsafe_bulk_action',
                bulk_action: action,
                error_ids: errorIds,
                nonce: failsafe_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show updated results
                    location.reload();
                } else {
                    alert(response.data || 'An error occurred while processing the request.');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('An error occurred while processing the request.');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Select all checkboxes
    $(document).on('change', '.failsafe-select-all', function() {
        var isChecked = $(this).is(':checked');
        $('.failsafe-error-checkbox').prop('checked', isChecked);
        updateBulkActionButtons();
    });
    
    // Update bulk action button states
    $(document).on('change', '.failsafe-error-checkbox', function() {
        updateBulkActionButtons();
        
        // Update select all checkbox
        var totalCheckboxes = $('.failsafe-error-checkbox').length;
        var checkedCheckboxes = $('.failsafe-error-checkbox:checked').length;
        
        $('.failsafe-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
    });
    
    function updateBulkActionButtons() {
        var checkedCount = $('.failsafe-error-checkbox:checked').length;
        $('.failsafe-bulk-action').prop('disabled', checkedCount === 0);
    }
    
    // Settings form AJAX submission
    $('#failsafe-settings-form').on('submit', function(e) {
        e.preventDefault(); // Always prevent default form submission
        
        var $form = $(this);
        var hasErrorTypeSelected = false;

        
        $('input[name*="enabled_error_types"]:checked').each(function() {
            hasErrorTypeSelected = true;
            return false; // break
        });
        
        if (!hasErrorTypeSelected) {
            showNotification('Please select at least one error type to monitor.', 'error');
            return false;
        }
        
        // Add loading state to save button
        var $saveButton = $('.failsafe-save-button');
        var originalText = $saveButton.val();
        $saveButton.addClass('loading').val('Saving Settings...');
        
        // Prepare form data
        var formData = $form.serialize();
        formData += '&action=failsafe_save_settings&nonce=' + failsafe_ajax.nonce;
        
        // Submit via AJAX
        $.ajax({
            url: failsafe_ajax.ajax_url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Update status indicator if main toggle was changed
                    updateStatusIndicator();
                } else {
                    showNotification(response.data.message || 'Failed to save settings.', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('An error occurred while saving settings. Please try again.', 'error');
                console.error('AJAX Error:', error);
            },
            complete: function() {
                // Restore button state
                $saveButton.removeClass('loading').val(originalText);
            }
        });
    });
    
    // Function to show notifications
    function showNotification(message, type) {
        // Remove existing notifications
        $('.failsafe-notification').remove();
        
        var notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notification = $('<div class="notice ' + notificationClass + ' is-dismissible failsafe-notification"><p>' + message + '</p></div>');
        
        // Insert notification at the top of the settings page
        $('.failsafe-settings-wrap').prepend(notification);
        
        // Auto-dismiss success notifications after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                notification.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to top to show notification
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
    // Function to update status indicator
    function updateStatusIndicator() {
        var isEnabled = $('#enable_failsafe').is(':checked');
        var $statusIndicator = $('.failsafe-status-indicator');
        var $statusText = $statusIndicator.find('.status-text');
        
        $statusIndicator.removeClass('enabled disabled').addClass(isEnabled ? 'enabled' : 'disabled');
        $statusText.text(isEnabled ? 'Active' : 'Inactive');
    }
    
    // Test error button (for debugging)
    $(document).on('click', '.failsafe-test-error', function(e) {
        e.preventDefault();
        
        if (!confirm('This will trigger a test fatal error. Are you sure you want to continue?')) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).html('Triggering Error... <span class="failsafe-spinner"></span>');
        
        $.ajax({
            url: failsafe_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'failsafe_test_error',
                nonce: failsafe_ajax.nonce
            },
            success: function(response) {
                // This shouldn't happen if the error is triggered correctly
                alert('Test completed. Check error logs.');
                $button.prop('disabled', false).text(originalText);
            },
            error: function() {
                // This is expected if a fatal error is triggered
                alert('Test error triggered. Please check the error logs and frontend.');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Clear all logs button
    $(document).on('click', '.failsafe-clear-logs', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to clear all error logs? This action cannot be undone.')) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).html('Clearing... <span class="failsafe-spinner"></span>');
        
        $.ajax({
            url: failsafe_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'failsafe_clear_logs',
                nonce: failsafe_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'An error occurred while clearing logs.');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('An error occurred while clearing logs.');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Auto-refresh error logs every 30 seconds if on error logs page
    if (window.location.href.indexOf('failsafe-fatal-error-recovery-errors') !== -1) {
        setInterval(function() {
            // Only refresh if there are pending errors
            if ($('.status-pending').length > 0) {
                location.reload();
            }
        }, 30000);
    }
    
    // Initialize tooltips if available
    if (typeof $.fn.tooltip === 'function') {
        $('[data-tooltip]').tooltip();
    }
    
    // Handle role selection validation and styling
    $(document).on('change', 'input[name="failsafe_options[recovery_user_roles][]"]', function() {
        var $checkbox = $(this);
        var $roleItem = $checkbox.closest('.failsafe-role-item');
        
        // Update visual state
        if ($checkbox.is(':checked')) {
            $roleItem.addClass('selected');
        } else {
            $roleItem.removeClass('selected');
        }
        
        var checkedRoles = $('input[name="failsafe_options[recovery_user_roles][]"]:checked');
        
        // Ensure at least one role is always selected
        if (checkedRoles.length === 0) {
            // Re-check administrator if nothing is selected
            var $adminCheckbox = $('input[name="failsafe_options[recovery_user_roles][]"][value="administrator"]');
            $adminCheckbox.prop('checked', true);
            $adminCheckbox.closest('.failsafe-role-item').addClass('selected');
            
            // Show warning
            if (typeof wp !== 'undefined' && wp.data) {
                wp.data.dispatch('core/notices').createWarningNotice(
                    'At least one user role must be selected. Administrator role has been automatically selected.',
                    { id: 'failsafe-role-warning', isDismissible: true }
                );
            } else {
                alert('At least one user role must be selected for recovery access.');
            }
        }
    });
    
    // Initialize role item states on page load
    $('input[name="failsafe_options[recovery_user_roles][]"]:checked').each(function() {
        $(this).closest('.failsafe-role-item').addClass('selected');
    });
    
    // Auto-dismiss welcome message after 10 seconds
    if ($('.failsafe-welcome-message').length) {
        setTimeout(function() {
            $('.failsafe-welcome-message').fadeOut(500);
        }, 10000);
    }
    
    // Add smooth scroll to settings sections when coming from welcome
    if (window.location.href.indexOf('welcome=1') !== -1) {
        setTimeout(function() {
            $('html, body').animate({
                scrollTop: $('.failsafe-main-card').offset().top - 100
            }, 1000);
        }, 500);
    }
    
    // Initialize bulk action buttons state
    updateBulkActionButtons();
    
    // Initialize Choices.js for multiselect fields
    if (typeof Choices !== 'undefined') {
        const protectedPlugins = document.getElementById('protectedPlugins');
        const recoveryRoles = document.getElementById('recoveryRoles');
        
        if (protectedPlugins) {
            new Choices(protectedPlugins, {
                removeItemButton: true,
                searchEnabled: true,
                placeholderValue: protectedPlugins.getAttribute('data-placeholder'),
                classNames: {
                    containerOuter: 'choices failsafe-choices',
                    containerInner: 'choices__inner failsafe-choices__inner'
                }
            });
        }
        
        if (recoveryRoles) {
            new Choices(recoveryRoles, {
                removeItemButton: true,
                searchEnabled: true,
                placeholderValue: recoveryRoles.getAttribute('data-placeholder'),
                classNames: {
                    containerOuter: 'choices failsafe-choices',
                    containerInner: 'choices__inner failsafe-choices__inner'
                }
            });
        }
    }
});