=== Auto-Backup & One-Click Restore Pro ===
Contributors: saifislam
Donate link: https://devsopu.com
Tags: backup, restore, database, migration, automatic, scheduled, ajax, real-time
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

🛡️ Professional WordPress backup solution with real-time progress, AJAX interface, and advanced email notifications. Developed by Saif Islam.

== Description ==

**Auto-Backup & One-Click Restore Pro** is a comprehensive, modern backup solution that provides complete WordPress site protection with an intuitive, professional interface. Create, manage, and restore backups with real-time progress tracking and advanced automation features.

= 🚀 Key Features =

**Modern AJAX Interface**
* Real-time operations without page reloads
* Live progress tracking with detailed status updates
* Smooth animations and professional design
* Mobile-responsive interface for all devices

**Comprehensive Backup Solution**
* Complete site backups including database, plugins, themes, and media
* Selective backup types - choose what to include
* One-click backup creation and restoration
* Automatic backup validation and integrity checks

**Advanced Automation**
* Scheduled automatic backups (daily, weekly, monthly)
* Smart cleanup with configurable backup retention
* Background processing for non-blocking operations
* WordPress cron system integration

**Professional Email Notifications**
* Event-based notifications (success, failure, restore completion)
* HTML and plain text email formats
* Customizable notification events
* Detailed backup reports and status emails

**Enterprise Security**
* Protected backup directory with .htaccess security
* Comprehensive input validation and sanitization
* CSRF protection on all forms and AJAX operations
* Admin-only access with capability checks

= 🎯 Perfect For =

* **Website Owners** - Reliable site protection with minimal effort
* **Developers** - Professional backup solution for client sites
* **Agencies** - Manage multiple WordPress installations efficiently
* **Businesses** - Enterprise-level backup and disaster recovery

= 📦 What Gets Backed Up =

* **Database** - Complete WordPress database with all content and settings
* **Plugins** - All installed plugins and their configuration files
* **Themes** - Active, inactive, and child themes
* **Media Library** - All uploaded files, images, and documents
* **System Info** - WordPress version, PHP details, and configuration

= ✨ Advanced Features =

**Real-Time Interface**
* Live progress bars with detailed status messages
* Instant data updates without page refreshes
* Professional modal dialogs for confirmations
* Smooth fade animations and visual feedback

**Smart Management**
* Automatic old backup cleanup
* Configurable backup retention (1-50 backups)
* Backup size monitoring and disk space checks
* Comprehensive system information dashboard

**Error Handling & Recovery**
* Comprehensive error handling with user-friendly messages
* Automatic retry mechanisms for failed operations
* Detailed logging for troubleshooting
* Recovery suggestions for common issues

= 🔧 System Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher (PHP 8.0+ recommended)
* ZipArchive PHP extension (required)
* 256MB+ PHP memory limit (512MB+ recommended)
* Write permissions for wp-content/uploads directory
* Sufficient disk space for backup storage

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin dashboard
2. Go to Plugins → Add New
3. Search for "Auto-Backup & One-Click Restore Pro"
4. Click "Install Now" and then "Activate"
5. Navigate to "Backup Pro" in your admin menu

= Manual Installation =

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/auto-backup-restore/` directory
3. Activate through the 'Plugins' menu in WordPress
4. Go to "Backup Pro" to start using the plugin

= Initial Setup =

1. **Configure Settings** - Go to Settings tab to configure automation
2. **Set Email Notifications** - Enable email alerts for backup events
3. **Create First Backup** - Click "🚀 Create Backup Now" to test
4. **Enable Automation** - Set up scheduled backups for continuous protection

== Frequently Asked Questions ==

= How does the real-time interface work? =

The plugin uses AJAX technology to provide a seamless experience. All operations (backup, restore, delete) happen without page reloads, with live progress tracking and instant data updates.

= What makes this plugin "Pro"? =

This professional version includes advanced features like real-time AJAX interface, comprehensive email notifications, modern design, enhanced security, and professional-grade error handling.

= How large can my backups be? =

The plugin can handle sites of various sizes. For very large sites (1GB+), you may need to adjust server settings (memory limit, execution time). The plugin includes system requirements checking.

= Where are backups stored? =

Backups are stored in `/wp-content/uploads/abr-backups/` with .htaccess protection. The directory is secured against direct access for enhanced security.

= Can I restore individual components? =

Currently, the plugin performs complete site restoration. This ensures data consistency and prevents conflicts between different backup components.

= How do automatic backups work? =

The plugin integrates with WordPress cron system to create backups automatically. You can choose daily, weekly, or monthly frequency based on your site's update frequency.

= Is it safe to restore a backup? =

Yes, but always test on a staging site first. The plugin includes safety warnings and confirmation dialogs. Restoration overwrites current files and database.

= What happens to old backups? =

The plugin automatically manages backup retention based on your "Maximum Backups" setting (1-50). Older backups are automatically deleted to save disk space.

= Can I get email notifications? =

Yes! The plugin includes advanced email notification system with customizable events, HTML/plain text formats, and detailed backup reports.

= Does this work with large sites? =

Yes, the plugin is optimized for sites of all sizes. It includes memory management, progress tracking, and can handle large databases and file collections.

== Screenshots ==

1. **Modern Dashboard** - Professional interface with statistics and real-time progress
2. **Advanced Settings** - Comprehensive configuration with modern form design
3. **Real-Time Progress** - Live backup creation with detailed status updates
4. **Email Notifications** - Professional email alerts with detailed reports
5. **Backup Management** - Clean table interface with one-click actions
6. **System Information** - Comprehensive server and plugin status dashboard

== Changelog ==

= 1.2.0 - 2024 =
**Major Update: Professional Interface & Real-Time Operations**

**New Features:**
* 🎨 Complete interface redesign with modern, professional styling
* ⚡ Real-time AJAX operations - no more page reloads
* 📊 Live progress tracking with detailed status messages
* 📧 Advanced email notification system with HTML/plain text options
* 🔔 Custom modal dialogs replacing standard browser alerts
* 📱 Fully responsive design for mobile and tablet devices
* 📚 Comprehensive instruction panels and safety guidelines
* 🎯 Smart data refresh with smooth animations

**Improvements:**
* 🔒 Enhanced security with better validation and CSRF protection
* 🛡️ Improved error handling with user-friendly messages
* 🚀 Performance optimizations for faster operations
* 🎛️ Better settings management with validation
* 📈 Professional progress bars with shine animations
* 🔧 Enhanced system information display
* 📋 Improved backup file validation and integrity checks

**Developer Features:**
* Clean, maintainable code following WordPress standards
* Comprehensive error logging and debugging
* Proper internationalization (i18n) support
* Extensible architecture for custom modifications

= 1.0.0 - 2024 =
**Initial Release**
* ✅ Core backup and restore functionality
* ✅ Automatic scheduled backups
* ✅ Basic email notifications
* ✅ Backup management interface
* ✅ Security features for backup directory

== Upgrade Notice ==

= 1.2.0 =
Major update with professional interface, real-time AJAX operations, and advanced email notifications. Significant improvements to user experience and security. Recommended for all users.

= 1.0.0 =
Initial release of Auto-Backup & One-Click Restore Pro. Professional WordPress backup solution.

== Support & Development ==

**Developer:** [Saif Islam](https://devsopu.com)
**Professional Support:** Available for custom requirements and enterprise installations
**Documentation:** Comprehensive guides included within the plugin interface
**Updates:** Regular feature enhancements and security updates

== Privacy & Security ==

**Privacy Compliant:**
* No external data transmission
* All backups stored locally on your server
* No user tracking or analytics
* Email notifications contain only backup status information

**Security Features:**
* Protected backup directory with .htaccess security
* Comprehensive input validation and sanitization
* CSRF protection on all operations
* Admin-only access controls
* Secure file path validation

== Technical Specifications ==

**Architecture:**
* Object-oriented PHP design
* WordPress coding standards compliant
* AJAX-powered interface
* Responsive CSS framework
* Professional error handling

**Compatibility:**
* WordPress 5.0+ (tested up to 6.4)
* PHP 7.4+ (8.0+ recommended)
* MySQL 5.6+ / MariaDB 10.0+
* All modern browsers supported
* Mobile and tablet responsive

**Performance:**
* Optimized for large sites
* Background processing capabilities
* Memory-efficient operations
* Progressive backup creation
* Smart resource management

---

🛡️ **Protect your WordPress site with Auto-Backup & One-Click Restore Pro - Professional backup solution by Saif Islam**
