<?php
if (!defined('ABSPATH')) {
    exit;
}

$backup_files = abr_get_backup_files();
$backup_dir_size = abr_get_backup_dir_size();
$is_writable = abr_is_backup_dir_writable();

// Display messages
if (isset($_GET['message'])) {
    $message_type = sanitize_text_field($_GET['message']);
    $message_details = isset($_GET['details']) ? urldecode($_GET['details']) : '';
    
    $class = $message_type === 'success' ? 'notice-success' : 'notice-error';
    echo '<div class="notice ' . $class . ' is-dismissible"><p>' . esc_html($message_details) . '</p></div>';
}

settings_errors('abr_messages');

if (isset($_POST['abr_create_backup']) && check_admin_referer('abr_backup_action', 'abr_nonce')) {
    // Call your backup function here, e.g.:
    abr_run_backup_now();
    // Redirect to avoid resubmission
    wp_redirect(add_query_arg(array(
        'page' => 'abr-backup',
        'message' => 'success',
        'details' => urlencode(__('Backup created successfully.', ABR_TEXT_DOMAIN))
    ), admin_url('admin.php')));
    exit;
}
?>

<div class="wrap">
   <h1 class="wp-heading-inline"><?php _e('Auto-Backup & One-Click Restore', ABR_TEXT_DOMAIN); ?></h1>

   <?php if (!$is_writable): ?>
   <div class="notice notice-error">
      <p><?php printf(__('Backup directory is not writable: %s', ABR_TEXT_DOMAIN), ABR_BACKUP_DIR); ?></p>
   </div>
   <?php endif; ?>

   <div class="abr-stats">
      <div class="abr-stat-box">
         <h3><?php _e('Total Backups', ABR_TEXT_DOMAIN); ?></h3>
         <span class="abr-stat-number"><?php echo count($backup_files); ?></span>
      </div>
      <div class="abr-stat-box">
         <h3><?php _e('Total Size', ABR_TEXT_DOMAIN); ?></h3>
         <span class="abr-stat-number"><?php echo size_format($backup_dir_size); ?></span>
      </div>
   </div>

   <div class="abr-actions">
      <form id="abr-backup-now-form" method="post" style="display: inline-block;">
         <?php wp_nonce_field('abr_backup_action', 'abr_nonce'); ?>
         <input type="submit" id="abr-create-backup-btn" name="abr_create_backup"
            class="button button-primary button-large" value="<?php _e('Create Backup Now', ABR_TEXT_DOMAIN); ?>">
         <span id="abr-backup-loader" style="display:none; margin-left:10px;"><img
               src="<?php echo esc_url(plugins_url('../assets/img/loader.gif', __FILE__)); ?>" alt="Loading..."
               height="24"></span>
      </form>
      <a href="<?php echo admin_url('admin.php?page=abr-settings'); ?>"
         class="button button-secondary"><?php _e('Settings', ABR_TEXT_DOMAIN); ?></a>
   </div>

   <h2><?php _e('Available Backups', ABR_TEXT_DOMAIN); ?></h2>

   <?php if (empty($backup_files)): ?>
   <div class="notice notice-info">
      <p><?php _e('No backups found. Create your first backup using the button above.', ABR_TEXT_DOMAIN); ?></p>
   </div>
   <?php else: ?>
   <table class="wp-list-table widefat fixed striped">
      <thead>
         <tr>
            <th scope="col"><?php _e('Backup Name', ABR_TEXT_DOMAIN); ?></th>
            <th scope="col"><?php _e('Date Created', ABR_TEXT_DOMAIN); ?></th>
            <th scope="col"><?php _e('Size', ABR_TEXT_DOMAIN); ?></th>
            <th scope="col"><?php _e('Actions', ABR_TEXT_DOMAIN); ?></th>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($backup_files as $backup): ?>
         <tr>
            <td><strong><?php echo esc_html($backup['name']); ?></strong></td>
            <td><?php echo esc_html($backup['formatted_date']); ?></td>
            <td><?php echo esc_html($backup['formatted_size']); ?></td>
            <td>
               <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=abr-backup&abr_restore=' . urlencode($backup['name'])), 'abr_restore_backup'); ?>"
                  class="button button-secondary abr-restore-btn"
                  onclick="return confirm('<?php _e('Are you sure you want to restore this backup? This will overwrite current files and database.', ABR_TEXT_DOMAIN); ?>')">
                  <?php _e('Restore', ABR_TEXT_DOMAIN); ?>
               </a>

               <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=abr-backup&abr_delete=' . urlencode($backup['name'])), 'abr_delete_backup'); ?>"
                  class="button button-link-delete"
                  onclick="return confirm('<?php _e('Are you sure you want to delete this backup?', ABR_TEXT_DOMAIN); ?>')">
                  <?php _e('Delete', ABR_TEXT_DOMAIN); ?>
               </a>
            </td>
         </tr>
         <?php endforeach; ?>
      </tbody>
   </table>
   <?php endif; ?>
</div>