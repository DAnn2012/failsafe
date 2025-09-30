=== FailSafe - WordPress Fatal Error Recovery Plugin ===
Contributors: salimshrestha
Donate link: https://github.com/salimshrestha98/failsafe
Tags: fatal error, error recovery, site recovery, plugin errors, theme errors, error handling, website protection, error monitoring, site maintenance, debugging
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect your WordPress site from fatal errors! Automatically detect & recover from plugin/theme crashes with smart error handling and instant recovery options.

== Description ==

**Never lose your WordPress site to fatal errors again!** FailSafe is the ultimate WordPress error recovery plugin that protects your website from crashes caused by faulty plugins and themes. When disaster strikes, FailSafe automatically detects the problem and provides instant recovery solutions to get your site back online in seconds.

🚨 **Stop Fatal Errors From Breaking Your Site**
WordPress fatal errors can happen anytime - a plugin update goes wrong, theme conflicts arise, or code breaks. FailSafe acts as your site's guardian angel, catching these errors before they crash your website and providing safe recovery options.

🛡️ **Advanced Protection Features**

* **🔍 Smart Error Detection** - Catches fatal PHP errors (E_ERROR, E_PARSE, E_CORE_ERROR) before they break your site
* **⚡ Instant Recovery** - One-click plugin/theme deactivation to restore your site immediately  
* **🎯 Precise Identification** - Automatically identifies which plugin or theme caused the error
* **🖥️ Beautiful Recovery Interface** - Clean, user-friendly error recovery screen with clear options
* **📊 Comprehensive Error Logging** - Detailed error logs with timestamps, file locations, and error types
* **🔒 Secure Hash System** - Military-grade security using SHA-256 hashes for error identification
* **⚙️ Flexible Configuration** - Choose which error types to monitor and how to handle them
* **🚀 MU-Plugin Architecture** - Loads before all other plugins for maximum protection coverage

🔄 **How FailSafe Protects Your Site**

1. **🎯 Continuous Monitoring** - FailSafe runs silently in the background, monitoring for fatal PHP errors
2. **⚡ Lightning-Fast Detection** - Uses PHP shutdown functions to catch errors the moment they occur  
3. **🔍 Smart Analysis** - Automatically identifies which plugin or theme caused the error
4. **📝 Secure Logging** - Creates detailed error logs with unique hash identifications
5. **🛠️ Instant Recovery** - Displays beautiful recovery interface with one-click solutions
6. **✅ Problem Resolution** - Safely deactivates problematic plugins/themes to restore your site

**💡 Real-World Example:**
Your site breaks after a plugin update → FailSafe detects the error → Shows recovery screen → You click "Deactivate Plugin" → Site works again in 5 seconds!

🎯 **Perfect For:**
- **Website Owners** - Protect your business from downtime
- **Developers** - Debug errors faster with detailed logs  
- **Agencies** - Maintain client sites with confidence
- **Beginners** - User-friendly interface requires no technical knowledge

⚙️ **Powerful Admin Dashboard**

* **🎛️ Settings Control Panel** - Fine-tune error monitoring and recovery behavior
* **📊 Error Analytics** - View detailed logs, statistics, and error trends over time
* **🔍 Advanced Filtering** - Search and filter errors by type, date, plugin, or theme
* **📤 Export Capabilities** - Download error logs for external analysis
* **🔐 Enterprise Security** - All actions protected with WordPress nonces and capability checks
* **🎨 Clean Interface** - Intuitive admin design that's easy to navigate

🌐 **Enterprise & Multisite Ready**
FailSafe works flawlessly with WordPress multisite networks, providing centralized error protection across all your sites. Perfect for agencies managing multiple client websites!

== Installation ==

🚀 **Quick & Easy Setup (2 Minutes)**

**Automatic Installation (Recommended):**
1. Go to your WordPress admin → Plugins → Add New
2. Search for "FailSafe"  
3. Click "Install Now" → "Activate"
4. Visit Settings → FailSafe to configure
5. Done! Your site is now protected

**Manual Installation:**
1. Download the plugin zip file
2. Upload to `/wp-content/plugins/` directory
3. Extract the files
4. Activate through WordPress admin → Plugins
5. Configure at Settings → FailSafe

**⚡ Pro Tip:** The MU-plugin loader installs automatically for maximum protection coverage!

== Frequently Asked Questions ==

= 🚀 Does FailSafe slow down my website? =

Absolutely not! FailSafe is ultra-lightweight and only activates when errors occur. Your site runs at full speed with zero performance impact during normal operation.

= 🛡️ What types of errors can FailSafe recover from? =

FailSafe handles all major PHP fatal errors including:
- Plugin conflicts and crashes
- Theme errors and compatibility issues  
- Parse errors from code updates
- Memory limit errors
- Function redeclaration errors

*Note: Server-level issues and WordPress core corruption require manual intervention.*

= 🔒 Is it safe to let FailSafe automatically disable plugins? =

Yes! FailSafe uses enterprise-grade security:
- SHA-256 hash verification for error identification
- WordPress capability checks for user permissions
- Secure nonce verification for all actions
- Only affects the specific problematic plugin/theme

= 🔌 Does FailSafe work with my caching plugin? =

FailSafe is compatible with ALL major caching plugins including:
- WP Rocket, W3 Total Cache, WP Super Cache
- LiteSpeed Cache, Autoptimize, and more
- Uses MU-plugin architecture for maximum compatibility

= 🎨 Can I customize the error recovery screen? =

Yes! FailSafe provides developer hooks and filters:
- Custom recovery message templates
- Branded error screens for agencies
- Advanced configuration options
- Full documentation available

= 💼 Does FailSafe work on multisite networks? =

Perfect for multisite! FailSafe provides network-wide protection and centralized error management across all your sites.

== Screenshots ==

1. 🎛️ FailSafe settings dashboard - Configure error monitoring and recovery options
2. 📊 Comprehensive error logs with filtering and export capabilities  
3. ⚡ Beautiful frontend recovery interface with one-click solutions
4. 🛡️ Fatal error recovery screen showing plugin deactivation options
5. 📈 Error statistics and analytics for site health monitoring

== Changelog ==

= 1.0.0 - 2025-01-01 =
🎉 **Initial Public Release**

**🚀 Core Features:**
* Complete class-based plugin architecture
* Advanced MU-plugin loader system for early error detection
* Beautiful, responsive recovery interface
* Comprehensive admin settings panel
* Detailed error logging and management system
* Military-grade SHA-256 hash-based security
* Real-time error monitoring and detection
* One-click plugin/theme deactivation recovery
* Multisite network compatibility
* Developer hooks and filters for customization

**🛡️ Security Features:**
* WordPress nonce verification for all actions
* Capability-based permission checks
* Secure hash validation for error identification
* Protected plugin exclusion system

**📊 Admin Features:**
* Intuitive settings configuration
* Advanced error log filtering and search
* Error statistics and analytics
* Export capabilities for external analysis
* Clean, modern admin interface design

== Upgrade Notice ==

= 1.0.0 =
🎉 Welcome to FailSafe v1.0.0! The ultimate WordPress error recovery solution is here. Install now to protect your site from fatal errors and ensure maximum uptime. Your website's guardian angel awaits!

== Technical Details ==

**💻 System Requirements**
* WordPress 5.0+ (tested up to 6.8)
* PHP 7.4+ (PHP 8.0+ recommended)
* MySQL 5.6+ or MariaDB equivalent
* 1MB available disk space

**🗄️ Database**
FailSafe creates a secure custom table `wp_failsafe_error_logs` for error storage. All data is encrypted and stored locally on your server.

**🔧 Developer Hooks & Filters**
* `failsafe_before_error_handling` - Customize pre-error processing
* `failsafe_after_error_logging` - Hook into post-logging actions
* `failsafe_auto_disable_plugin` - Control auto-disable behavior
* `failsafe_frontend_notice_content` - Customize recovery messages
* `failsafe_error_detection_types` - Modify monitored error types

**🌐 Compatibility**
* ✅ All major hosting providers (WP Engine, SiteGround, Bluehost, etc.)
* ✅ All caching plugins (WP Rocket, W3TC, LiteSpeed, etc.)  
* ✅ All page builders (Elementor, Divi, Beaver Builder, etc.)
* ✅ Multisite networks and subdirectory installations
* ✅ WPML and multilingual setups

== Support & Documentation ==

📚 **Get Help:**
* 📖 [Complete Documentation](https://github.com/salimshrestha98/failsafe/wiki)
* 🐛 [Report Issues](https://github.com/salimshrestha98/failsafe/issues)
* 💬 [Community Support](https://wordpress.org/support/plugin/failsafe/)
* 📧 Direct developer support via GitHub

== Privacy & Security ==

**🔒 Your Data Stays Private:**
* All error logs stored locally in YOUR database
* Zero external data transmission or tracking
* No third-party services or analytics
* GDPR compliant - you control all data

**📊 What We Log:**
* Error type and timestamp (for debugging)
* File path and line number (for identification)
* Plugin/theme information (for recovery)
* Error messages (technical details only)

**Purpose:** All logged data is used exclusively for error recovery and site maintenance.
