<?php
if (!defined('ABSPATH')) {
    exit;
}

$backup_files = abr_get_backup_files();
$backup_dir_size = abr_get_backup_dir_size();
$is_writable = abr_is_backup_dir_writable();

// Debug info (remove in production)
if (defined('WP_DEBUG') && WP_DEBUG) {
    echo '<!-- Debug Info: ';
    echo 'Backup Dir: ' . ABR_BACKUP_DIR . ' ';
    echo 'Dir Exists: ' . (file_exists(ABR_BACKUP_DIR) ? 'Yes' : 'No') . ' ';
    echo 'Is Writable: ' . ($is_writable ? 'Yes' : 'No') . ' ';
    echo 'Files Count: ' . count($backup_files) . ' ';
    echo '-->';
}

// Display messages
if (isset($_GET['message'])) {
    $message_type = sanitize_text_field($_GET['message']);
    $message_details = isset($_GET['details']) ? sanitize_text_field(urldecode($_GET['details'])) : '';

    $allowed_types = array('success', 'error');
    if (in_array($message_type, $allowed_types)) {
        $class = $message_type === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . esc_attr($class) . ' is-dismissible"><p>' . esc_html($message_details) . '</p></div>';
    }
}

settings_errors('abr_messages');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Auto-Backup & One-Click Restore Pro', ABR_TEXT_DOMAIN); ?></h1>

    <!-- Instructions Panel -->
    <div class="abr-instructions-panel">
        <h3>📚 <?php _e('How to Use Auto Backup & Restore Pro', ABR_TEXT_DOMAIN); ?></h3>
        <div class="abr-instructions-grid">
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">🚀</div>
                <h4><?php _e('Create Backup', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Click "Create Backup Now" to create a complete backup of your website including database, plugins, themes, and media files.', ABR_TEXT_DOMAIN); ?></p>
            </div>
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">🔄</div>
                <h4><?php _e('Restore Backup', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Click "Restore" next to any backup to restore your website to that point in time. This will overwrite current files and database.', ABR_TEXT_DOMAIN); ?></p>
            </div>
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">⚙️</div>
                <h4><?php _e('Configure Settings', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Go to Settings to enable automatic backups, configure email notifications, and customize what gets backed up.', ABR_TEXT_DOMAIN); ?></p>
            </div>
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">📧</div>
                <h4><?php _e('Email Alerts', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Enable email notifications in Settings to receive alerts when backups are created, restored, or if any errors occur.', ABR_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>

    <?php if (!$is_writable): ?>
        <div class="notice notice-error">
            <p><strong><?php _e('⚠️ Important:', ABR_TEXT_DOMAIN); ?></strong> <?php printf(__('Backup directory is not writable: %s. Please contact your hosting provider to fix file permissions.', ABR_TEXT_DOMAIN), ABR_BACKUP_DIR); ?></p>
        </div>
    <?php endif; ?>

    <div class="abr-stats" id="abr-stats-container">
        <div class="abr-stat-box">
            <h3><?php _e('Total Backups', ABR_TEXT_DOMAIN); ?></h3>
            <span class="abr-stat-number"><?php echo count($backup_files); ?></span>
        </div>
        <div class="abr-stat-box">
            <h3><?php _e('Total Size', ABR_TEXT_DOMAIN); ?></h3>
            <span class="abr-stat-number"><?php echo size_format($backup_dir_size); ?></span>
        </div>
    </div>

    <!-- Safety Notice -->
    <div class="abr-safety-notice">
        <h4>���️ <?php _e('Important Safety Information', ABR_TEXT_DOMAIN); ?></h4>
        <ul>
            <li><strong><?php _e('Test Before Restore:', ABR_TEXT_DOMAIN); ?></strong> <?php _e('Always test restores on a staging site first before applying to production.', ABR_TEXT_DOMAIN); ?></li>
            <li><strong><?php _e('Backup Before Restore:', ABR_TEXT_DOMAIN); ?></strong> <?php _e('Create a fresh backup before restoring an older one to have a fallback option.', ABR_TEXT_DOMAIN); ?></li>
            <li><strong><?php _e('Check Disk Space:', ABR_TEXT_DOMAIN); ?></strong> <?php _e('Ensure sufficient disk space is available before creating or restoring backups.', ABR_TEXT_DOMAIN); ?></li>
            <li><strong><?php _e('Regular Backups:', ABR_TEXT_DOMAIN); ?></strong> <?php _e('Enable automatic backups in Settings for continuous protection.', ABR_TEXT_DOMAIN); ?></li>
        </ul>
    </div>

    <div class="abr-actions">
        <button id="abr-create-backup-btn" class="button button-primary button-large">
            🚀 <?php _e('Create Backup Now', ABR_TEXT_DOMAIN); ?>
        </button>

        <a href="<?php echo esc_url(admin_url('admin.php?page=abr-settings')); ?>" class="button button-secondary"><?php _e('Settings', ABR_TEXT_DOMAIN); ?></a>

        <button type="button" class="button button-secondary abr-refresh-btn">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Refresh', ABR_TEXT_DOMAIN); ?>
        </button>

        <div id="abr-progress" class="abr-progress" style="display: none;">
            <div class="abr-progress-text">
                <span id="abr-progress-message"><?php _e('Preparing backup...', ABR_TEXT_DOMAIN); ?></span>
                <span id="abr-progress-percent">0%</span>
            </div>
            <div class="abr-progress-bar">
                <div id="abr-progress-fill" class="abr-progress-fill"></div>
            </div>
        </div>
    </div>

    <h2><?php _e('Available Backups', ABR_TEXT_DOMAIN); ?></h2>

    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div class="notice notice-info">
            <p><strong><?php _e('Debug Info:', ABR_TEXT_DOMAIN); ?></strong><br>
                <?php _e('Backup Directory:', ABR_TEXT_DOMAIN); ?> <code><?php echo esc_html(ABR_BACKUP_DIR); ?></code><br>
                <?php _e('Directory Exists:', ABR_TEXT_DOMAIN); ?> <?php echo file_exists(ABR_BACKUP_DIR) ? '✓ Yes' : '✗ No'; ?><br>
                <?php _e('Is Writable:', ABR_TEXT_DOMAIN); ?> <?php echo $is_writable ? '✓ Yes' : '✗ No'; ?><br>
                <?php _e('Files Found:', ABR_TEXT_DOMAIN); ?> <?php echo count($backup_files); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if (empty($backup_files)): ?>
        <div class="notice notice-info">
            <p><?php _e('No backups found. Create your first backup using the button above.', ABR_TEXT_DOMAIN); ?></p>
            <?php if (!$is_writable): ?>
                <p><strong><?php _e('Note:', ABR_TEXT_DOMAIN); ?></strong> <?php printf(__('Backup directory is not writable: %s', ABR_TEXT_DOMAIN), ABR_BACKUP_DIR); ?></p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped" id="abr-backup-table">
            <thead>
            <tr>
                <th scope="col" class="manage-column"><?php _e('Backup Name', ABR_TEXT_DOMAIN); ?></th>
                <th scope="col" class="manage-column"><?php _e('Date Created', ABR_TEXT_DOMAIN); ?></th>
                <th scope="col" class="manage-column"><?php _e('Size', ABR_TEXT_DOMAIN); ?></th>
                <th scope="col" class="manage-column"><?php _e('Actions', ABR_TEXT_DOMAIN); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if (is_array($backup_files) && count($backup_files) > 0): ?>
                <?php foreach ($backup_files as $backup): ?>
                    <?php if (isset($backup['name']) && isset($backup['formatted_date']) && isset($backup['formatted_size'])): ?>
                        <tr>
                            <td><strong><?php echo esc_html($backup['name']); ?></strong></td>
                            <td><?php echo esc_html($backup['formatted_date']); ?></td>
                            <td><?php echo esc_html($backup['formatted_size']); ?></td>
                            <td>
                                <button type="button"
                                        class="button button-secondary abr-restore-btn"
                                        data-backup-file="<?php echo esc_attr($backup['name']); ?>">
                                    <?php _e('Restore', ABR_TEXT_DOMAIN); ?>
                                </button>

                                <button type="button"
                                        class="button button-link-delete abr-delete-btn"
                                        data-backup-file="<?php echo esc_attr($backup['name']); ?>">
                                    <?php _e('Delete', ABR_TEXT_DOMAIN); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4"><?php _e('No backup files found in the backup directory.', ABR_TEXT_DOMAIN); ?></td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="abr-brand-footer"></div>
</div>
