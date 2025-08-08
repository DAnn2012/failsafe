# FailSafe - WordPress Fatal Error Recovery Plugin

FailSafe is an advanced WordPress plugin that provides comprehensive fatal error recovery capabilities. It catches fatal errors from plugins and themes, and offers safe recovery options to prevent your website from going down.

## Features

- **Early Error Detection**: Catches fatal errors before they crash your site
- **Smart Recovery**: Automatically detects which plugin or theme caused the error
- **User-Friendly Interface**: Shows frontend notices with clear recovery options
- **Configurable Error Types**: Choose which types of errors to monitor
- **Comprehensive Logging**: Keep detailed logs of all detected errors
- **Auto-Disable Option**: Automatically disable problematic plugins/themes
- **MU-Plugin Loader**: Loads before other plugins for maximum protection
- **Hash-Based Security**: Uses secure hashes to identify and resolve specific errors

## Installation

1. Upload the `failsafe` folder to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > FailSafe to configure the plugin
4. The MU-plugin loader will be automatically installed for early error detection

## Configuration

### Error Types
Configure which types of errors should trigger FailSafe:
- **E_ERROR**: Fatal run-time errors
- **E_PARSE**: Parse errors
- **E_CORE_ERROR**: Fatal errors in PHP core
- **E_COMPILE_ERROR**: Fatal compile-time errors
- **E_USER_ERROR**: User-generated error messages
- **E_RECOVERABLE_ERROR**: Catchable fatal errors

### Behavior Settings
- **Show Frontend Notice**: Display recovery options on the frontend instead of auto-disabling
- **Auto Disable**: Automatically disable problematic plugins/themes without user confirmation
- **Log Errors**: Keep detailed logs of all detected errors

## How It Works

1. **Detection**: FailSafe monitors your site for fatal errors using PHP's shutdown function
2. **Identification**: When an error occurs, it identifies the specific plugin or theme responsible
3. **Logging**: The error is logged with a unique hash for identification
4. **Recovery Options**: 
   - **Manual Recovery**: Shows a recovery message below WordPress fatal error with clickable link
   - **Frontend Notice**: Shows a user-friendly modal notice with options to disable or dismiss (for runtime errors)
   - **Auto-Disable**: Automatically disables the problematic plugin/theme (when enabled)
5. **Resolution**: Once resolved, the error is marked as handled in the system

## Fatal Error Recovery Process

### When Auto-Disable is Disabled (Default)
1. **Fatal Error Occurs**: WordPress shows its default fatal error message
2. **FailSafe Recovery Message**: Below the error, FailSafe displays:
   ```
   FailSafe Recovery
   The plugin "Plugin Name" has caused a fatal error.
   [Click here to disable it]
   ```
3. **Manual Recovery**: User clicks the link to disable the problematic plugin/theme
4. **Success Page**: Shows confirmation and links to return to site or admin
5. **Site Restored**: The problematic component is disabled and site is accessible again

### When Auto-Disable is Enabled
- FailSafe immediately disables the problematic plugin/theme
- No user intervention required
- Error is logged for review

## Frontend Recovery Interface

When a fatal error is detected and frontend notices are enabled, users will see:
- Clear identification of the problematic plugin/theme
- Error details and message
- Options to disable the plugin/theme or dismiss the notice
- Secure hash-based verification for safety

## Admin Interface

### Settings Page
- Configure error types to monitor
- Set behavior preferences
- View plugin statistics

### Error Logs Page
- View all detected errors
- See resolution status
- Dismiss or manage errors
- Export error data

## MU-Plugin Loader

FailSafe includes an MU-plugin loader that:
- Loads before all other plugins and themes
- Catches errors that occur during early WordPress initialization
- Automatically disables problematic plugins before they can crash the site
- Stores early errors for processing by the main plugin

## Security Features

- **Nonce Verification**: All AJAX requests are protected with WordPress nonces
- **Capability Checks**: Admin functions require proper user capabilities
- **Hash-Based Identification**: Secure SHA-256 hashes prevent tampering
- **Sanitized Input**: All user input is properly sanitized and validated

## Database Schema

FailSafe creates a custom table `wp_failsafe_error_logs` to store error information:
- Unique error hashes for identification
- Complete error details and context
- Plugin/theme identification
- Resolution status tracking
- Timestamps for analysis

## Compatibility

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **Multisite**: Compatible
- **Themes**: Works with any theme
- **Plugins**: Compatible with all plugins

## Troubleshooting

### Plugin Not Working
1. Check that the MU-plugin loader exists in `/wp-content/mu-plugins/`
2. Verify plugin is activated in WordPress admin
3. Check error logs for any PHP errors
4. Ensure proper file permissions

### False Positives
1. Adjust error type settings to be more specific
2. Use the dismiss function for non-critical errors
3. Check error logs to identify patterns

### Performance Impact
FailSafe is designed to be lightweight:
- Only activates during actual errors
- Minimal database queries
- Efficient error detection algorithms
- No impact on normal site operation

## Development

### File Structure
```
failsafe/
├── failsafe.php                 # Main plugin file
├── includes/
│   ├── class-failsafe.php       # Main plugin class
│   ├── class-failsafe-admin.php # Admin interface
│   ├── class-failsafe-frontend.php # Frontend interface
│   └── class-failsafe-error-handler.php # Error handling
├── mu-plugin/
│   └── failsafe-loader.php      # MU-plugin loader
├── assets/
│   ├── frontend.css             # Frontend styles
│   ├── frontend.js              # Frontend JavaScript
│   ├── admin.css                # Admin styles
│   └── admin.js                 # Admin JavaScript
├── languages/                   # Translation files
└── README.md                    # This file
```

### Hooks and Filters
FailSafe provides several hooks for customization:
- `failsafe_before_error_handling`: Fired before error processing
- `failsafe_after_error_logging`: Fired after error is logged
- `failsafe_auto_disable_plugin`: Filter to modify auto-disable behavior
- `failsafe_frontend_notice_content`: Filter to customize notice content

## Changelog

### 1.0.0
- Initial release
- Full class-based architecture
- MU-plugin loader system
- Frontend recovery interface
- Comprehensive admin settings
- Error logging and management
- Hash-based security system

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, feature requests, or bug reports, please contact the plugin developer or create an issue in the plugin repository.