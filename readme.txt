=== Auto-Backup & One-Click Restore ===
Contributors: saifislam
Tags: backup, restore, database, plugins, themes, uploads, automatic, scheduled
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Complete backup and restore solution for WordPress. Create full site backups including database, plugins, themes, and uploads with one click.

== Description ==

Auto-Backup & One-Click Restore is a comprehensive backup solution for WordPress that allows you to create complete backups of your website and restore them with just one click. The plugin backs up your database, plugins, themes, and uploads directory, ensuring your entire site is protected.

= Key Features =

* **One-Click Full Backup** - Create complete site backups with a single click
* **Automatic Scheduled Backups** - Set up daily, weekly, or monthly automatic backups
* **Complete Site Restore** - Restore your entire site from any backup
* **Selective Backup Types** - Choose what to backup: database, plugins, themes, uploads
* **Email Notifications** - Get notified when backups are created or when errors occur
* **Backup Management** - View, manage, and delete old backups
* **Security** - Backup directory is protected from direct access
* **Clean Interface** - User-friendly admin interface with backup statistics

= What Gets Backed Up =

* **Database** - Complete WordPress database with all your content
* **Plugins** - All installed plugins and their files
* **Themes** - All themes including active and inactive ones
* **Uploads** - Media library and all uploaded files
* **Site Information** - WordPress version, PHP version, and other site details

= Perfect For =

* Website owners who want reliable backup solution
* Developers working on client sites
* Anyone who needs to migrate WordPress sites
* Site administrators managing multiple WordPress installations

= Requirements =

* WordPress 5.0 or higher
* PHP 7.4 or higher
* ZipArchive PHP extension
* Sufficient disk space for backups
* Write permissions for wp-content/uploads directory

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/auto-backup-restore` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to 'Backup & Restore' in your WordPress admin menu.
4. Configure your backup settings in the Settings page.
5. Create your first backup by clicking 'Create Backup Now'.

== Frequently Asked Questions ==

= How large can my backups be? =

Backup size depends on your site content. The plugin can handle sites of various sizes, but very large sites (over 1GB) may require server configuration adjustments for memory and execution time limits.

= Where are backups stored? =

Backups are stored in `/wp-content/uploads/abr-backups/` directory. This directory is protected from direct access for security.

= Can I restore individual components? =

Currently, the plugin restores complete backups. Individual component restoration may be added in future versions.

= How do automatic backups work? =

When enabled, the plugin uses WordPress cron system to create backups automatically based on your chosen frequency (daily, weekly, or monthly).

= Is it safe to restore a backup? =

Yes, but always test on a staging site first. Restoration will overwrite current files and database, so make sure you have a recent backup before restoring.

= What happens to old backups? =

The plugin automatically deletes old backups based on your "Maximum Backups" setting to save disk space.

== Screenshots ==

1. Main backup page showing available backups and statistics
2. Settings page with automatic backup configuration
3. Backup creation in progress
4. System information and requirements check

== Changelog ==

= 1.0.0 =
* Initial release
* Full site backup and restore functionality
* Automatic scheduled backups
* Email notifications
* Backup management interface
* Security features for backup directory

== Upgrade Notice ==

= 1.0.0 =
Initial release of Auto-Backup & One-Click Restore plugin.

== Support ==

For support, feature requests, or bug reports, please visit the plugin's support forum or contact the developer.

== Privacy Policy ==

This plugin does not collect or transmit any personal data. All backups are stored locally on your server. Email notifications (if enabled) only contain backup status information and are sent to the configured email address.