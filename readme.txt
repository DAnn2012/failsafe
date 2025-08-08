=== FailSafe ===
Contributors: yourusername
Donate link: https://example.com/donate
Tags: error recovery, fatal error, failsafe, error handling, site recovery, plugin errors, theme errors
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced fatal error recovery system that catches fatal errors and provides safe recovery options for WordPress sites.

== Description ==

FailSafe is a comprehensive WordPress plugin designed to protect your website from fatal errors caused by plugins and themes. When a fatal error occurs, FailSafe provides intelligent recovery options to get your site back online quickly and safely.

= Key Features =

* **Early Error Detection** - Catches fatal errors before they crash your site
* **Smart Recovery** - Automatically detects which plugin or theme caused the error
* **User-Friendly Interface** - Shows clear recovery options with detailed error information
* **Configurable Error Types** - Choose which types of errors to monitor (E_ERROR, E_PARSE, etc.)
* **Comprehensive Logging** - Keep detailed logs of all detected errors for analysis
* **Auto-Disable Option** - Automatically disable problematic plugins/themes
* **MU-Plugin Loader** - Loads before other plugins for maximum protection
* **Hash-Based Security** - Uses secure hashes to identify and resolve specific errors

= How It Works =

1. **Detection**: FailSafe monitors your site for fatal errors using PHP's shutdown function
2. **Identification**: When an error occurs, it identifies the specific plugin or theme responsible
3. **Logging**: The error is logged with a unique hash for identification
4. **Recovery**: Provides multiple recovery options:
   - Manual recovery with clickable links
   - Frontend notices for runtime errors
   - Automatic disabling when configured
5. **Resolution**: Once resolved, errors are marked as handled

= Error Recovery Process =

When a fatal error occurs:
- WordPress shows its default fatal error message
- FailSafe displays a recovery message below with options to disable the problematic plugin/theme
- Users can click to safely disable the component causing the issue
- Site is restored to working condition immediately

= Admin Features =

* **Settings Page** - Configure error types and behavior preferences
* **Error Logs Page** - View, manage, and export error data
* **Statistics** - Monitor plugin performance and error trends
* **Security** - All actions protected with WordPress nonces and capability checks

= Multisite Compatible =

FailSafe works seamlessly with WordPress multisite installations, providing network-wide error protection.

== Installation ==

1. Upload the `failsafe` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > FailSafe to configure the plugin
4. The MU-plugin loader will be automatically installed for early error detection

== Frequently Asked Questions ==

= Does FailSafe affect site performance? =

No, FailSafe is designed to be lightweight and only activates during actual errors. It has minimal impact on normal site operation.

= Can FailSafe recover from any fatal error? =

FailSafe can recover from most plugin and theme-related fatal errors. It cannot recover from server-level issues or corrupted WordPress core files.

= Is it safe to automatically disable plugins? =

Yes, FailSafe uses secure hash-based verification and WordPress capability checks to ensure only authorized actions are performed.

= Does FailSafe work with caching plugins? =

Yes, FailSafe is compatible with all major caching plugins and includes its own MU-plugin loader for early error detection.

= Can I customize the recovery interface? =

Yes, FailSafe provides several hooks and filters for customization. See the documentation for details.

== Screenshots ==

1. FailSafe settings page with error type configuration
2. Error logs page showing detected errors and resolution status
3. Frontend recovery notice with disable options
4. Fatal error recovery message with clickable recovery link

== Changelog ==

= 1.0.0 =
* Initial release
* Full class-based architecture
* MU-plugin loader system
* Frontend recovery interface
* Comprehensive admin settings
* Error logging and management
* Hash-based security system

== Upgrade Notice ==

= 1.0.0 =
Initial release of FailSafe. Install to protect your WordPress site from fatal errors.

== Technical Details ==

= System Requirements =
* WordPress 5.0 or higher
* PHP 7.4 or higher
* MySQL 5.6 or higher

= Database =
FailSafe creates a custom table `wp_failsafe_error_logs` to store error information securely.

= Hooks and Filters =
* `failsafe_before_error_handling` - Fired before error processing
* `failsafe_after_error_logging` - Fired after error is logged
* `failsafe_auto_disable_plugin` - Filter to modify auto-disable behavior
* `failsafe_frontend_notice_content` - Filter to customize notice content

== Support ==

For support, documentation, and updates, visit the plugin homepage or contact the developer.

== Privacy Policy ==

FailSafe stores error logs locally in your WordPress database. No data is transmitted to external servers. Error logs contain technical information about PHP errors and may include file paths and error messages. This data is used solely for error recovery and site maintenance purposes.
