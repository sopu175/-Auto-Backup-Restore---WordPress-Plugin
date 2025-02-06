<?php
if (!defined('ABSPATH')) {
    exit;
}

function abr_delete_backup($backup_file) {
    $file_path = ABR_BACKUP_DIR . $backup_file;

    if (file_exists($file_path)) {
        unlink($file_path);

        // Add a success message
        add_settings_error(
            'abr_backup_messages',   // Setting name (unique ID)
            'abr_backup_deleted',    // Error code
            'Backup deleted successfully.', // Message
            'success'                // Type of message ('success', 'error')
        );

        // Redirect to the backup page after successful deletion
        wp_redirect(admin_url('admin.php?page=abr_backup'));
        exit;
    } else {
        // Add an error message if the file does not exist
        add_settings_error(
            'abr_backup_messages',
            'abr_backup_error',
            'Backup file not found.',
            'error'
        );

        wp_redirect(admin_url('admin.php?page=abr_backup'));
        exit;
    }
}
