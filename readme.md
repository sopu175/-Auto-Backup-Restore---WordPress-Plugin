# Auto Backup Restore Plugin

The Auto Backup Restore plugin for WordPress allows you to create, manage, and restore backups of your WordPress site, including plugins, themes, uploads, and the database.

## Features

- Create backups of plugins, themes, uploads, and the database.
- Schedule automatic backups (daily, weekly, monthly).
- Restore backups easily from the admin panel.
- Manage backup settings and view existing backups.

## Installation

1. Upload the `auto-backup-restore` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the 'Backup Settings' page under the 'Settings' menu to configure the plugin.

## Usage

### Creating a Backup

1. Go to the 'Backup Settings' page.
2. Click on 'Add New Backup Setting'.
3. Fill in the backup name and interval (daily, weekly, monthly).
4. Click 'Add Backup' to save the settings.

### Restoring a Backup

1. Go to the 'Backup Settings' page.
2. Find the backup you want to restore in the 'Current Backup Settings' table.
3. Click on the 'Restore' link next to the backup.

### Deleting a Backup

1. Go to the 'Backup Settings' page.
2. Find the backup you want to delete in the 'Current Backup Settings' table.
3. Click on the 'Delete' link next to the backup.

## Hooks and Filters

### Actions

- `abr_scheduled_backup`: Triggered to create a scheduled backup.

### Filters

- `abr_backup_options`: Filter the backup options before saving.

## License

This plugin is licensed under the GPLv2 or later.

## Changelog

### 1.0.0

- Initial release.

## Support

For support, please visit the [support forum](https://wordpress.org/support/plugin/auto-backup-restore).