/* FailSafe Admin JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
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
    
    // Settings form validation
    $('form[action="options.php"]').on('submit', function(e) {
        var hasErrorTypeSelected = false;
        
        $('input[name*="enabled_error_types"]:checked').each(function() {
            hasErrorTypeSelected = true;
            return false; // break
        });
        
        if (!hasErrorTypeSelected) {
            e.preventDefault();
            alert('Please select at least one error type to monitor.');
            return false;
        }
    });
    
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
    if (window.location.href.indexOf('failsafe-errors') !== -1) {
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
    
    // Initialize bulk action buttons state
    updateBulkActionButtons();
});