<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings_obj = new ABR_Settings();
$settings = $settings_obj->get_settings();

settings_errors('abr_messages');
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Auto-Backup & Restore Pro Settings', ABR_TEXT_DOMAIN); ?></h1>

    <!-- Settings Instructions -->
    <div class="abr-instructions-panel">
        <h3>⚙️ <?php _e('Configure Your Backup Settings', ABR_TEXT_DOMAIN); ?></h3>
        <div class="abr-instructions-grid">
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">🔄</div>
                <h4><?php _e('Automatic Backups', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Enable automatic backups to protect your site continuously. Choose daily, weekly, or monthly frequency based on how often your content changes.', ABR_TEXT_DOMAIN); ?></p>
            </div>
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">📦</div>
                <h4><?php _e('Backup Content', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Select what to include: Database (posts, pages, settings), Plugins, Themes, and Uploads (media files). All are recommended for complete protection.', ABR_TEXT_DOMAIN); ?></p>
            </div>
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">📧</div>
                <h4><?php _e('Email Notifications', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Get notified about backup status via email. Choose which events to receive notifications for and set your preferred email format.', ABR_TEXT_DOMAIN); ?></p>
            </div>
            <div class="abr-instruction-item">
                <div class="abr-instruction-icon">🗂️</div>
                <h4><?php _e('Backup Management', ABR_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Set maximum number of backups to keep. Older backups are automatically deleted to save server space when limit is reached.', ABR_TEXT_DOMAIN); ?></p>
            </div>
        </div>
    </div>

    <?php
    // Check if running on localhost
    $server_name = $_SERVER['SERVER_NAME'] ?? '';
    $is_localhost = (strpos($server_name, 'localhost') !== false ||
        strpos($server_name, '127.0.0.1') !== false ||
        strpos($server_name, '::1') !== false ||
        strpos($server_name, '.local') !== false ||
        strpos($server_name, '.test') !== false);

    if ($is_localhost): ?>
        <div class="notice notice-info">
            <p><strong>🏠 <?php _e('Localhost Development Notice:', ABR_TEXT_DOMAIN); ?></strong>
                <?php _e('Email sending is typically not available on localhost. The plugin will save emails to files for testing purposes. Check: ', ABR_TEXT_DOMAIN); ?>
                <code>/wp-content/uploads/abr-backups/emails/</code></p>
        </div>
    <?php endif; ?>

    <div class="abr-settings-container">
        <form method="post" action="">
            <?php wp_nonce_field('abr_settings_action', 'abr_settings_nonce'); ?>

            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Automatic Backups', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="auto_backup_enabled" value="1" <?php checked($settings['auto_backup_enabled']); ?>>
                            <?php _e('Enable automatic backups', ABR_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description"><?php _e('When enabled, backups will be created automatically based on the frequency setting below.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Backup Frequency', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="backup_frequency">
                            <option value="daily" <?php selected($settings['backup_frequency'], 'daily'); ?>><?php _e('Daily', ABR_TEXT_DOMAIN); ?></option>
                            <option value="weekly" <?php selected($settings['backup_frequency'], 'weekly'); ?>><?php _e('Weekly', ABR_TEXT_DOMAIN); ?></option>
                            <option value="monthly" <?php selected($settings['backup_frequency'], 'monthly'); ?>><?php _e('Monthly', ABR_TEXT_DOMAIN); ?></option>
                        </select>
                        <p class="description"><?php _e('How often automatic backups should be created.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Maximum Backups', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="number" name="max_backups" value="<?php echo esc_attr($settings['max_backups']); ?>" min="1" max="50" class="small-text">
                        <p class="description"><?php _e('Maximum number of backups to keep. Older backups will be automatically deleted.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Backup Content', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="backup_types[]" value="plugins" <?php checked(in_array('plugins', $settings['backup_types'])); ?>>
                                <?php _e('Plugins', ABR_TEXT_DOMAIN); ?>
                            </label><br>

                            <label>
                                <input type="checkbox" name="backup_types[]" value="themes" <?php checked(in_array('themes', $settings['backup_types'])); ?>>
                                <?php _e('Themes', ABR_TEXT_DOMAIN); ?>
                            </label><br>

                            <label>
                                <input type="checkbox" name="backup_types[]" value="uploads" <?php checked(in_array('uploads', $settings['backup_types'])); ?>>
                                <?php _e('Uploads', ABR_TEXT_DOMAIN); ?>
                            </label><br>

                            <label>
                                <input type="checkbox" name="backup_types[]" value="database" <?php checked(in_array('database', $settings['backup_types'])); ?>>
                                <?php _e('Database', ABR_TEXT_DOMAIN); ?>
                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Select what content to include in backups.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php _e('Email Notifications', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="email_notifications" value="1" <?php checked($settings['email_notifications']); ?>>
                            <?php _e('Send email notifications', ABR_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description"><?php _e('Receive email notifications when backups are created, restored, or when errors occur.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr class="email-settings-row">
                    <th scope="row"><?php _e('Notification Email', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="email" name="notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text" required>
                        <p class="description"><?php _e('Email address to receive backup notifications.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr class="email-settings-row">
                    <th scope="row"><?php _e('Email Events', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox" name="email_on_backup_success" value="1" <?php checked(isset($settings['email_on_backup_success']) ? $settings['email_on_backup_success'] : true); ?>>
                                <?php _e('Backup Success', ABR_TEXT_DOMAIN); ?>
                            </label><br>

                            <label>
                                <input type="checkbox" name="email_on_backup_failure" value="1" <?php checked(isset($settings['email_on_backup_failure']) ? $settings['email_on_backup_failure'] : true); ?>>
                                <?php _e('Backup Failure', ABR_TEXT_DOMAIN); ?>
                            </label><br>

                            <label>
                                <input type="checkbox" name="email_on_restore_success" value="1" <?php checked(isset($settings['email_on_restore_success']) ? $settings['email_on_restore_success'] : true); ?>>
                                <?php _e('Restore Success', ABR_TEXT_DOMAIN); ?>
                            </label><br>

                            <label>
                                <input type="checkbox" name="email_on_restore_failure" value="1" <?php checked(isset($settings['email_on_restore_failure']) ? $settings['email_on_restore_failure'] : true); ?>>
                                <?php _e('Restore Failure', ABR_TEXT_DOMAIN); ?>
                            </label>
                        </fieldset>
                        <p class="description"><?php _e('Choose which events should trigger email notifications.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr class="email-settings-row">
                    <th scope="row"><?php _e('Email Template', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="email_format">
                            <option value="html" <?php selected(isset($settings['email_format']) ? $settings['email_format'] : 'html', 'html'); ?>><?php _e('HTML Format', ABR_TEXT_DOMAIN); ?></option>
                            <option value="plain" <?php selected(isset($settings['email_format']) ? $settings['email_format'] : 'html', 'plain'); ?>><?php _e('Plain Text', ABR_TEXT_DOMAIN); ?></option>
                        </select>
                        <p class="description"><?php _e('Choose email format for notifications.', ABR_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>

                <tr class="email-settings-row">
                    <th scope="row"><?php _e('Test Email', ABR_TEXT_DOMAIN); ?></th>
                    <td>
                        <button type="button" id="abr-test-email-btn" class="button button-secondary">📧 <?php _e('Send Test Email', ABR_TEXT_DOMAIN); ?></button>
                        <p class="description"><?php _e('Send a test email to verify your email configuration. On localhost, emails will be saved to files for testing.', ABR_TEXT_DOMAIN); ?></p>
                        <div id="abr-email-test-result" style="margin-top: 10px;"></div>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Save Settings', ABR_TEXT_DOMAIN), 'primary', 'abr_save_settings'); ?>
        </form>
    </div>

    <div class="abr-info-box">
        <h3><?php _e('System Information', ABR_TEXT_DOMAIN); ?></h3>
        <table class="widefat">
            <tr>
                <td><strong><?php _e('Backup Directory:', ABR_TEXT_DOMAIN); ?></strong></td>
                <td><?php echo esc_html(ABR_BACKUP_DIR); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('Directory Writable:', ABR_TEXT_DOMAIN); ?></strong></td>
                <td><?php echo is_writable(ABR_BACKUP_DIR) ? '<span style="color: green;">' . __('Yes', ABR_TEXT_DOMAIN) . '</span>' : '<span style="color: red;">' . __('No', ABR_TEXT_DOMAIN) . '</span>'; ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('PHP Memory Limit:', ABR_TEXT_DOMAIN); ?></strong></td>
                <td><?php echo ini_get('memory_limit'); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('PHP Max Execution Time:', ABR_TEXT_DOMAIN); ?></strong></td>
                <td><?php echo ini_get('max_execution_time'); ?> <?php _e('seconds', ABR_TEXT_DOMAIN); ?></td>
            </tr>
            <tr>
                <td><strong><?php _e('ZipArchive Available:', ABR_TEXT_DOMAIN); ?></strong></td>
                <td><?php echo class_exists('ZipArchive') ? '<span style="color: green;">' . __('Yes', ABR_TEXT_DOMAIN) . '</span>' : '<span style="color: red;">' . __('No', ABR_TEXT_DOMAIN) . '</span>'; ?></td>
            </tr>
        </table>
    </div>

    <div class="abr-brand-footer"></div>
</div>
