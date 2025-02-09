<?php
if (!defined('ABSPATH')) {
    exit;
}

// Restore Function
function abr_restore_backup($backup_type = 'database', $backup_file = '') {
    if (empty($backup_file)) {
        error_log("Error: No backup file provided.");
        return false;
    }

    if (!defined('ABR_BACKUP_DIR')) {
        define('ABR_BACKUP_DIR', WP_CONTENT_DIR . '/abr_backups/');
    }

    $file_path = ABR_BACKUP_DIR . $backup_file;

    if (!file_exists($file_path)) {
        error_log("Error: Backup file not found - {$file_path}");
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($file_path) !== true) {
        error_log("Error: Could not open ZIP file - {$file_path}");
        return false;
    }

    switch ($backup_type) {
        case 'plugins':
            if (!is_writable(WP_PLUGIN_DIR)) {
                error_log("Error: Plugins directory is not writable.");
                return false;
            }
            $zip->extractTo(WP_PLUGIN_DIR);
            break;

        case 'themes':
            if (!is_writable(get_theme_root())) {
                error_log("Error: Themes directory is not writable.");
                return false;
            }
            $zip->extractTo(get_theme_root());
            break;

        case 'uploads':
            $uploads_dir = WP_CONTENT_DIR . '/uploads/';
            if (!is_writable($uploads_dir)) {
                error_log("Error: Uploads directory is not writable.");
                return false;
            }
            $zip->extractTo($uploads_dir);
            break;

        case 'database':
            if ($zip->locateName('database.sql') !== false) {
                $zip->extractTo(ABR_BACKUP_DIR, 'database.sql');
                $db_backup_file = ABR_BACKUP_DIR . 'database.sql';

                if (file_exists($db_backup_file)) {
                    abr_import_database($db_backup_file);
                    unlink($db_backup_file);
                } else {
                    error_log("Error: Extracted database.sql not found.");
                    return false;
                }
            } else {
                error_log("Error: database.sql not found in backup.");
                return false;
            }
            break;

        default:
            error_log("Error: Invalid backup type specified - {$backup_type}");
            return false;
    }

    $zip->close();
    abr_send_restore_email($backup_type, $backup_file);

    // Redirect after success
    wp_redirect(admin_url("admin.php?page=abr_backup&message=success_restore&restored={$backup_type}"));
    exit;
}

// Function to import database using wpdb (No CLI needed)
function abr_import_database($file_path) {
    global $wpdb;

    if (!file_exists($file_path)) {
        error_log("Error: Database backup file missing - {$file_path}");
        return false;
    }

    $sql = file_get_contents($file_path);
    if (!$sql) {
        error_log("Error: Could not read SQL file.");
        return false;
    }

    $queries = explode(";\n", $sql);
    foreach ($queries as $query) {
        if (!empty(trim($query))) {
            $result = $wpdb->query($query);
            if ($result === false) {
                error_log("Error: Failed to execute query - " . $query);
            }
        }
    }

    return true;
}

// Send Email Notification After Restore
function abr_send_restore_email($backup_type, $backup_file) {
    $admin_email = get_option('admin_email');
    $subject = "WordPress Backup Restored - {$backup_type}";
    $message = "Your WordPress backup for '{$backup_type}' has been successfully restored.\n\n";
    $message .= "Backup File: {$backup_file}\n";
    $message .= "Date: " . date('Y-m-d H:i:s') . "\n\n";
    $message .= "If you did not request this action, please check your site immediately.";

    wp_mail($admin_email, $subject, $message);
}

// Upload Backups to Cloud (Google Drive, Dropbox, AWS S3)
function abr_upload_to_cloud($backup_file) {
    $cloud_provider = get_option('abr_cloud_storage'); // Get cloud storage option

    switch ($cloud_provider) {
        case 'google_drive':
            abr_upload_to_google_drive($backup_file);
            break;
        case 'dropbox':
            abr_upload_to_dropbox($backup_file);
            break;
        case 'aws_s3':
            abr_upload_to_aws_s3($backup_file);
            break;
        default:
            error_log("Error: No cloud storage provider selected.");
            return false;
    }

    return true;
}

// Google Drive Upload (Placeholder)
function abr_upload_to_google_drive($backup_file) {
    error_log("Uploading {$backup_file} to Google Drive... (Feature Not Implemented Yet)");
}

// Dropbox Upload (Placeholder)
function abr_upload_to_dropbox($backup_file) {
    error_log("Uploading {$backup_file} to Dropbox... (Feature Not Implemented Yet)");
}

// AWS S3 Upload (Placeholder)
function abr_upload_to_aws_s3($backup_file) {
    error_log("Uploading {$backup_file} to AWS S3... (Feature Not Implemented Yet)");
}
