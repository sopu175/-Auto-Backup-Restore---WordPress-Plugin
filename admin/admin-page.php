<?php
if (!defined('ABSPATH')) {
    exit; // Prevent direct access
}

?>

<div class="wrap">
    <h1 class="wp-heading-inline">Auto-Backup & One-Click Restore</h1>

    <form method="post" style="margin-top: 20px;">
        <?php wp_nonce_field('abr_backup_action', 'abr_backup_nonce'); ?>
        <input id="abr-start-backup" type="submit" name="abr_backup" class="button button-primary" value="Backup Now">
    </form>

    <h2 class="title">Available Backups</h2>

    <table class="wp-list-table widefat fixed striped table-view-list" style="margin-top: 20px;">
        <thead>
        <tr>
            <th scope="col">Backup Name</th>
            <th scope="col">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php
        $files = glob(ABR_BACKUP_DIR . '*.zip');
        if ($files) {
            foreach ($files as $file) {
                $file_name = basename($file);
                ?>
                <tr>
                    <td><?php echo esc_html($file_name); ?></td>
                    <td>
                        <a href="?abr_restore=<?php echo urlencode($file_name); ?>" class="button button-secondary">Restore</a>
                        <a href="?abr_delete=<?php echo urlencode($file_name); ?>" class="button button-danger">Delete</a>
                    </td>
                </tr>
                <?php
            }
        } else {
            ?>
            <tr>
                <td colspan="2">No backups available.</td>
            </tr>
            <?php
        }
        ?>
        </tbody>
    </table>

    <?php
    // Handle Backup Creation
    if (isset($_POST['abr_backup']) && check_admin_referer('abr_backup_action', 'abr_backup_nonce')) {
        require_once ABR_PLUGIN_DIR . 'includes/backup.php';
        abr_create_backup();
        echo '<div class="notice notice-success is-dismissible"><p>Backup created successfully!</p></div>';
    }

    // Handle Backup Restore
    if (isset($_GET['abr_restore'])) {
        require_once ABR_PLUGIN_DIR . 'includes/restore.php';
        abr_restore_backup($_GET['abr_restore']);
        echo '<div class="notice notice-success is-dismissible"><p>Backup restored successfully!</p></div>';
    }

    // Handle Backup Deletion
    if (isset($_GET['abr_delete'])) {
        require_once ABR_PLUGIN_DIR . 'includes/delete.php';
        abr_delete_backup($_GET['abr_delete']);
        echo '<div class="notice notice-success is-dismissible"><p>Backup deleted successfully!</p></div>';
    }

    // Handle Custom Messages
    if (isset($_GET['message'])) {
        if ($_GET['message'] === 'success_restore') {
            echo '<div class="notice notice-success is-dismissible"><p>Backup restored successfully.</p></div>';
        } elseif ($_GET['message'] === 'error_file_not_found') {
            echo '<div class="notice notice-error is-dismissible"><p>Backup file not found.</p></div>';
        } elseif ($_GET['message'] === 'error_open_failed') {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to open the backup file.</p></div>';
        }
    }
    ?>
</div>
