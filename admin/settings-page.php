<?php
if (!defined('ABSPATH')) {
    exit;
}

$settings_obj = new ABR_Settings();
$settings = $settings_obj->get_settings();

settings_errors('abr_messages');
?>

<div class="wrap">
   <h1><?php _e('Backup Settings', ABR_TEXT_DOMAIN); ?></h1>

   <form method="post" action="">
      <?php wp_nonce_field('abr_settings_action', 'abr_settings_nonce'); ?>

      <table class="form-table">
         <tr>
            <th scope="row"><?php _e('Automatic Backups', ABR_TEXT_DOMAIN); ?></th>
            <td>
               <label>
                  <input type="checkbox" name="auto_backup_enabled" value="1"
                     <?php checked($settings['auto_backup_enabled']); ?>>
                  <?php _e('Enable automatic backups', ABR_TEXT_DOMAIN); ?>
               </label>
               <p class="description">
                  <?php _e('When enabled, backups will be created automatically based on the frequency setting below.', ABR_TEXT_DOMAIN); ?>
               </p>
            </td>
         </tr>

         <tr>
            <th scope="row"><?php _e('Backup Frequency', ABR_TEXT_DOMAIN); ?></th>
            <td>
               <select name="backup_frequency">
                  <option value="daily" <?php selected($settings['backup_frequency'], 'daily'); ?>>
                     <?php _e('Daily', ABR_TEXT_DOMAIN); ?></option>
                  <option value="weekly" <?php selected($settings['backup_frequency'], 'weekly'); ?>>
                     <?php _e('Weekly', ABR_TEXT_DOMAIN); ?></option>
                  <option value="monthly" <?php selected($settings['backup_frequency'], 'monthly'); ?>>
                     <?php _e('Monthly', ABR_TEXT_DOMAIN); ?></option>
               </select>
               <p class="description"><?php _e('How often automatic backups should be created.', ABR_TEXT_DOMAIN); ?>
               </p>
            </td>
         </tr>

         <tr>
            <th scope="row"><?php _e('Maximum Backups', ABR_TEXT_DOMAIN); ?></th>
            <td>
               <input type="number" name="max_backups" value="<?php echo esc_attr($settings['max_backups']); ?>" min="1"
                  max="50" class="small-text">
               <p class="description">
                  <?php _e('Maximum number of backups to keep. Older backups will be automatically deleted.', ABR_TEXT_DOMAIN); ?>
               </p>
            </td>
         </tr>

         <tr>
            <th scope="row"><?php _e('Backup Content', ABR_TEXT_DOMAIN); ?></th>
            <td>
               <fieldset>
                  <label>
                     <input type="checkbox" name="backup_types[]" value="plugins"
                        <?php checked(in_array('plugins', $settings['backup_types'])); ?>>
                     <?php _e('Plugins', ABR_TEXT_DOMAIN); ?>
                  </label><br>

                  <label>
                     <input type="checkbox" name="backup_types[]" value="themes"
                        <?php checked(in_array('themes', $settings['backup_types'])); ?>>
                     <?php _e('Themes', ABR_TEXT_DOMAIN); ?>
                  </label><br>

                  <label>
                     <input type="checkbox" name="backup_types[]" value="uploads"
                        <?php checked(in_array('uploads', $settings['backup_types'])); ?>>
                     <?php _e('Uploads', ABR_TEXT_DOMAIN); ?>
                  </label><br>

                  <label>
                     <input type="checkbox" name="backup_types[]" value="database"
                        <?php checked(in_array('database', $settings['backup_types'])); ?>>
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
                  <input type="checkbox" name="email_notifications" value="1"
                     <?php checked($settings['email_notifications']); ?>>
                  <?php _e('Send email notifications', ABR_TEXT_DOMAIN); ?>
               </label>
               <p class="description">
                  <?php _e('Receive email notifications when backups are created or when errors occur.', ABR_TEXT_DOMAIN); ?>
               </p>
            </td>
         </tr>

         <tr id="abr-notification-email-row">
            <th scope="row"><?php _e('Notification Email', ABR_TEXT_DOMAIN); ?></th>
            <td>
               <input type="email" name="notification_email"
                  value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text">
               <p class="description"><?php _e('Email address to receive backup notifications.', ABR_TEXT_DOMAIN); ?>
               </p>
            </td>
         </tr>
      </table>

      <?php submit_button(__('Save Settings', ABR_TEXT_DOMAIN), 'primary', 'abr_save_settings'); ?>
   </form>

   <div class="abr-info-box">
      <h3><?php _e('System Information', ABR_TEXT_DOMAIN); ?></h3>
      <table class="widefat">
         <tr>
            <td><strong><?php _e('Backup Directory:', ABR_TEXT_DOMAIN); ?></strong></td>
            <td><?php echo esc_html(ABR_BACKUP_DIR); ?></td>
         </tr>
         <tr>
            <td><strong><?php _e('Directory Writable:', ABR_TEXT_DOMAIN); ?></strong></td>
            <td>
               <?php echo is_writable(ABR_BACKUP_DIR) ? '<span style="color: green;">' . __('Yes', ABR_TEXT_DOMAIN) . '</span>' : '<span style="color: red;">' . __('No', ABR_TEXT_DOMAIN) . '</span>'; ?>
            </td>
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
            <td>
               <?php echo class_exists('ZipArchive') ? '<span style="color: green;">' . __('Yes', ABR_TEXT_DOMAIN) . '</span>' : '<span style="color: red;">' . __('No', ABR_TEXT_DOMAIN) . '</span>'; ?>
            </td>
         </tr>
      </table>
   </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
   var emailCheckbox = document.querySelector('input[name="email_notifications"]');
   var emailRow = document.getElementById('abr-notification-email-row');

   function toggleEmailRow() {
      if (emailCheckbox && emailRow) {
         if (emailCheckbox.checked) {
            emailRow.style.display = '';
         } else {
            emailRow.style.display = 'none';
         }
      }
   }
   if (emailCheckbox && emailRow) {
      emailCheckbox.addEventListener('change', toggleEmailRow);
      toggleEmailRow();
   }
});
</script>