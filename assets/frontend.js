/* FailSafe Frontend JavaScript */

jQuery(document).ready(function($) {
    'use strict';
    
    // Close notice when clicking overlay or close button
    $(document).on('click', '.failsafe-overlay, .failsafe-notice-close', function(e) {
        e.preventDefault();
        closeFailSafeNotice();
    });
    
    // Prevent closing when clicking inside the notice
    $(document).on('click', '.failsafe-notice', function(e) {
        e.stopPropagation();
    });
    
    // Handle disable plugin/theme button
    $(document).on('click', '.failsafe-disable-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var errorHash = $btn.data('error-hash');
        var $errorItem = $btn.closest('.failsafe-error-item');
        
        if (!errorHash) {
            showErrorMessage('Invalid error hash.');
            return;
        }
        
        // Disable button and show loading state
        $btn.prop('disabled', true).addClass('loading');
        
        $.ajax({
            url: failsafe_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'failsafe_disable_plugin_frontend',
                error_hash: errorHash,
                nonce: failsafe_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message, $errorItem);
                    
                    // Remove the error item after a delay
                    setTimeout(function() {
                        $errorItem.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if there are any more errors
                            if ($('.failsafe-error-item').length === 0) {
                                closeFailSafeNotice();
                            }
                        });
                    }, 2000);
                    
                } else {
                    showErrorMessage(response.data || 'An error occurred.');
                    $btn.prop('disabled', false).removeClass('loading');
                }
            },
            error: function() {
                showErrorMessage(failsafe_frontend.strings.error);
                $btn.prop('disabled', false).removeClass('loading');
            }
        });
    });
    
    // Handle dismiss error button
    $(document).on('click', '.failsafe-dismiss-btn', function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        var errorHash = $btn.data('error-hash');
        var $errorItem = $btn.closest('.failsafe-error-item');
        
        if (!errorHash) {
            showErrorMessage('Invalid error hash.');
            return;
        }
        
        // Disable button and show loading state
        $btn.prop('disabled', true).addClass('loading');
        
        $.ajax({
            url: failsafe_frontend.ajax_url,
            type: 'POST',
            data: {
                action: 'failsafe_dismiss_error_frontend',
                error_hash: errorHash,
                nonce: failsafe_frontend.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data.message, $errorItem);
                    
                    // Remove the error item after a delay
                    setTimeout(function() {
                        $errorItem.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if there are any more errors
                            if ($('.failsafe-error-item').length === 0) {
                                closeFailSafeNotice();
                            }
                        });
                    }, 1500);
                    
                } else {
                    showErrorMessage(response.data || 'An error occurred.');
                    $btn.prop('disabled', false).removeClass('loading');
                }
            },
            error: function() {
                showErrorMessage(failsafe_frontend.strings.error);
                $btn.prop('disabled', false).removeClass('loading');
            }
        });
    });
    
    // Escape key to close notice
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && $('#failsafe-error-notice').is(':visible')) {
            closeFailSafeNotice();
        }
    });
    
    /**
     * Close the FailSafe notice
     */
    function closeFailSafeNotice() {
        $('#failsafe-error-notice, #failsafe-notice-overlay').fadeOut(300, function() {
            $(this).remove();
        });
        
        // Re-enable body scrolling
        $('body').removeClass('failsafe-no-scroll');
    }
    
    /**
     * Show error message
     */
    function showErrorMessage(message, $container) {
        var $errorDiv = $('<div class="failsafe-error-message" style="background: #f8d7da; border-color: #f5c6cb; color: #721c24;">' + 
                         escapeHtml(message) + '</div>');
        
        if ($container) {
            $container.find('.failsafe-error-details').append($errorDiv);
        } else {
            $('.failsafe-notice-body').prepend($errorDiv);
        }
        
        // Remove after 5 seconds
        setTimeout(function() {
            $errorDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Show success message
     */
    function showSuccessMessage(message, $container) {
        var $successDiv = $('<div class="failsafe-success-message">' + escapeHtml(message) + '</div>');
        
        if ($container) {
            $container.find('.failsafe-error-details').append($successDiv);
        } else {
            $('.failsafe-notice-body').prepend($successDiv);
        }
        
        // Remove after 3 seconds
        setTimeout(function() {
            $successDiv.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Prevent body scrolling when notice is shown
    if ($('#failsafe-error-notice').length > 0) {
        $('body').addClass('failsafe-no-scroll');
    }
});

// Add CSS for preventing body scroll
jQuery(document).ready(function($) {
    if (!$('#failsafe-no-scroll-css').length) {
        $('head').append('<style id="failsafe-no-scroll-css">.failsafe-no-scroll { overflow: hidden; }</style>');
    }
});