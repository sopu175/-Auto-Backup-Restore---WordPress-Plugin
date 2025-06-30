<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings_obj = new ABR_Settings();
$settings = $settings_obj->get_settings();

settings_errors('abr_messages');
?>

<div class="wrap">
   <h1 class="wp-heading-inline"><?php _e('Auto-Backup & Restore Pro Settings', 'auto-backup-restore'); ?></h1>

   <!-- Settings Instructions -->
   <div class="abr-instructions-panel">
      <h3>⚙️ <?php _e('Configure Your Backup Settings', 'auto-backup-restore'); ?></h3>
      <div class="abr-instructions-grid">
         <div class="abr-instruction-item">
            <div class="abr-instruction-icon">🔄</div>
            <h4><?php _e('Automatic Backups', 'auto-backup-restore'); ?></h4>
            <p><?php _e('Enable automatic backups to protect your site continuously. Choose daily, weekly, or monthly frequency based on how often your content changes.', 'auto-backup-restore'); ?>
            </p>
         </div>
         <div class="abr-instruction-item">
            <div class="abr-instruction-icon">📦</div>
            <h4><?php _e('Backup Content', 'auto-backup-restore'); ?></h4>
            <p><?php _e('Select what to include: Database (posts, pages, settings), Plugins, Themes, and Uploads (media files). All are recommended for complete protection.', 'auto-backup-restore'); ?>
            </p>
         </div>
         <div class="abr-instruction-item">
            <div class="abr-instruction-icon">📧</div>
            <h4><?php _e('Email Notifications', 'auto-backup-restore'); ?></h4>
            <p><?php _e('Get notified about backup status via email. Choose which events to receive notifications for and set your preferred email format.', 'auto-backup-restore'); ?>
            </p>
         </div>
         <div class="abr-instruction-item">
            <div class="abr-instruction-icon">🗂️</div>
            <h4><?php _e('Backup Management', 'auto-backup-restore'); ?></h4>
            <p><?php _e('Set maximum number of backups to keep. Older backups are automatically deleted to save server space when limit is reached.', 'auto-backup-restore'); ?>
            </p>
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
      <p><strong>🏠 <?php _e('Localhost Development Notice:', 'auto-backup-restore'); ?></strong>
         <?php _e('Email sending is typically not available on localhost. The plugin will save emails to files for testing purposes. Check: ', 'auto-backup-restore'); ?>
         <code>/wp-content/uploads/abr-backups/emails/</code>
      </p>
   </div>
   <?php endif; ?>

   <div class="abr-settings-container">
      <form method="post" action="">
         <?php wp_nonce_field('abr_settings_action', 'abr_settings_nonce'); ?>

         <table class="form-table" role="presentation">
            <tr>
               <th scope="row"><?php _e('Automatic Backups', 'auto-backup-restore'); ?></th>
               <td>
                  <label>
                     <input type="checkbox" name="auto_backup_enabled" value="1"
                        <?php checked($settings['auto_backup_enabled']); ?>>
                     <?php _e('Enable automatic backups', 'auto-backup-restore'); ?>
                  </label>
                  <p class="description">
                     <?php _e('When enabled, backups will be created automatically based on the frequency setting below.', 'auto-backup-restore'); ?>
                  </p>
               </td>
            </tr>

            <tr>
               <th scope="row"><?php _e('Backup Frequency', 'auto-backup-restore'); ?></th>
               <td>
                  <select name="backup_frequency">
                     <option value="daily" <?php selected($settings['backup_frequency'], 'daily'); ?>>
                        <?php _e('Daily', 'auto-backup-restore'); ?></option>
                     <option value="weekly" <?php selected($settings['backup_frequency'], 'weekly'); ?>>
                        <?php _e('Weekly', 'auto-backup-restore'); ?></option>
                     <option value="monthly" <?php selected($settings['backup_frequency'], 'monthly'); ?>>
                        <?php _e('Monthly', 'auto-backup-restore'); ?></option>
                  </select>
                  <p class="description">
                     <?php _e('How often automatic backups should be created.', 'auto-backup-restore'); ?>
                  </p>
               </td>
            </tr>

            <tr>
               <th scope="row"><?php _e('Maximum Backups', 'auto-backup-restore'); ?></th>
               <td>
                  <input type="number" name="max_backups" value="<?php echo esc_attr($settings['max_backups']); ?>"
                     min="1" max="50" class="small-text">
                  <p class="description">
                     <?php _e('Maximum number of backups to keep. Older backups will be automatically deleted.', 'auto-backup-restore'); ?>
                  </p>
               </td>
            </tr>

            <tr>
               <th scope="row"><?php _e('Backup Content', 'auto-backup-restore'); ?></th>
               <td>
                  <fieldset>
                     <label>
                        <input type="checkbox" name="backup_types[]" value="plugins"
                           <?php checked(in_array('plugins', $settings['backup_types'])); ?>>
                        <?php _e('Plugins', 'auto-backup-restore'); ?>
                     </label><br>

                     <label>
                        <input type="checkbox" name="backup_types[]" value="themes"
                           <?php checked(in_array('themes', $settings['backup_types'])); ?>>
                        <?php _e('Themes', 'auto-backup-restore'); ?>
                     </label><br>

                     <label>
                        <input type="checkbox" name="backup_types[]" value="uploads"
                           <?php checked(in_array('uploads', $settings['backup_types'])); ?>>
                        <?php _e('Uploads', 'auto-backup-restore'); ?>
                     </label><br>

                     <label>
                        <input type="checkbox" name="backup_types[]" value="database"
                           <?php checked(in_array('database', $settings['backup_types'])); ?>>
                        <?php _e('Database', 'auto-backup-restore'); ?>
                     </label>
                  </fieldset>
                  <p class="description">
                     <?php _e('Select what content to include in backups.', 'auto-backup-restore'); ?></p>
               </td>
            </tr>

            <tr>
               <th scope="row"><?php _e('Email Notifications', 'auto-backup-restore'); ?></th>
               <td>
                  <label>
                     <input type="checkbox" name="email_notifications" value="1"
                        <?php checked($settings['email_notifications']); ?>>
                     <?php _e('Send email notifications', 'auto-backup-restore'); ?>
                  </label>
                  <p class="description">
                     <?php _e('Receive email notifications when backups are created, restored, or when errors occur.', 'auto-backup-restore'); ?>
                  </p>
               </td>
            </tr>

            <tr class="email-settings-row">
               <th scope="row"><?php _e('Notification Email', 'auto-backup-restore'); ?></th>
               <td>
                  <input type="email" name="notification_email"
                     value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text" required>
                  <p class="description">
                     <?php _e('Email address to receive backup notifications.', 'auto-backup-restore'); ?>
                  </p>
               </td>
            </tr>

            <tr class="email-settings-row">
               <th scope="row"><?php _e('Email Events', 'auto-backup-restore'); ?></th>
               <td>
                  <fieldset>
                     <label>
                        <input type="checkbox" name="email_on_backup_success" value="1"
                           <?php checked(isset($settings['email_on_backup_success']) ? $settings['email_on_backup_success'] : true); ?>>
                        <?php _e('Backup Success', 'auto-backup-restore'); ?>
                     </label><br>

                     <label>
                        <input type="checkbox" name="email_on_backup_failure" value="1"
                           <?php checked(isset($settings['email_on_backup_failure']) ? $settings['email_on_backup_failure'] : true); ?>>
                        <?php _e('Backup Failure', 'auto-backup-restore'); ?>
                     </label><br>

                     <label>
                        <input type="checkbox" name="email_on_restore_success" value="1"
                           <?php checked(isset($settings['email_on_restore_success']) ? $settings['email_on_restore_success'] : true); ?>>
                        <?php _e('Restore Success', 'auto-backup-restore'); ?>
                     </label><br>

                     <label>
                        <input type="checkbox" name="email_on_restore_failure" value="1"
                           <?php checked(isset($settings['email_on_restore_failure']) ? $settings['email_on_restore_failure'] : true); ?>>
                        <?php _e('Restore Failure', 'auto-backup-restore'); ?>
                     </label>
                  </fieldset>
                  <p class="description">
                     <?php _e('Choose which events should trigger email notifications.', 'auto-backup-restore'); ?></p>
               </td>
            </tr>

            <tr class="email-settings-row">
               <th scope="row"><?php _e('Email Template', 'auto-backup-restore'); ?></th>
               <td>
                  <select name="email_format">
                     <option value="html"
                        <?php selected(isset($settings['email_format']) ? $settings['email_format'] : 'html', 'html'); ?>>
                        <?php _e('HTML Format', 'auto-backup-restore'); ?></option>
                     <option value="plain"
                        <?php selected(isset($settings['email_format']) ? $settings['email_format'] : 'html', 'plain'); ?>>
                        <?php _e('Plain Text', 'auto-backup-restore'); ?></option>
                  </select>
                  <p class="description"><?php _e('Choose email format for notifications.', 'auto-backup-restore'); ?>
                  </p>
               </td>
            </tr>

            <tr class="email-settings-row">
               <th scope="row"><?php _e('Test Email', 'auto-backup-restore'); ?></th>
               <td>
                  <button type="button" id="abr-test-email-btn" class="button button-secondary">📧
                     <?php _e('Send Test Email', 'auto-backup-restore'); ?></button>
                  <p class="description">
                     <?php _e('Send a test email to verify your email configuration. On localhost, emails will be saved to files for testing.', 'auto-backup-restore'); ?>
                  </p>
                  <div id="abr-email-test-result" style="margin-top: 10px;"></div>
               </td>
            </tr>
         </table>

         <?php submit_button(__('Save Settings', 'auto-backup-restore'), 'primary', 'abr_save_settings'); ?>
      </form>
   </div>

   <div class="abr-info-box">
      <h3><?php _e('System Information', 'auto-backup-restore'); ?></h3>
      <table class="widefat">
         <tr>
            <td><strong><?php _e('Backup Directory:', 'auto-backup-restore'); ?></strong></td>
            <td><?php echo esc_html(ABR_BACKUP_DIR); ?></td>
         </tr>
         <tr>
            <td><strong><?php _e('Directory Writable:', 'auto-backup-restore'); ?></strong></td>
            <td>
               <?php echo is_writable(ABR_BACKUP_DIR) ? '<span style="color: green;">' . __('Yes', 'auto-backup-restore') . '</span>' : '<span style="color: red;">' . __('No', 'auto-backup-restore') . '</span>'; ?>
            </td>
         </tr>
         <tr>
            <td><strong><?php _e('PHP Memory Limit:', 'auto-backup-restore'); ?></strong></td>
            <td><?php echo ini_get('memory_limit'); ?></td>
         </tr>
         <tr>
            <td><strong><?php _e('PHP Max Execution Time:', 'auto-backup-restore'); ?></strong></td>
            <td><?php echo ini_get('max_execution_time'); ?> <?php _e('seconds', 'auto-backup-restore'); ?></td>
         </tr>
         <tr>
            <td><strong><?php _e('ZipArchive Available:', 'auto-backup-restore'); ?></strong></td>
            <td>
               <?php echo class_exists('ZipArchive') ? '<span style="color: green;">' . __('Yes', 'auto-backup-restore') . '</span>' : '<span style="color: red;">' . __('No', 'auto-backup-restore') . '</span>'; ?>
            </td>
         </tr>
      </table>
   </div>

   <div class="abr-brand-footer"></div>
</div>

<?php
/* translators: %s: backup directory path */
printf(__('Backup directory is not writable: %s. Please contact your hosting provider to fix file permissions.', 'auto-backup-restore'), $dir);
?>