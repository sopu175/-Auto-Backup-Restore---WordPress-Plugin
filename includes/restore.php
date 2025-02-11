<?php
if (!defined('ABSPATH')) {
    exit;
}

// Restore Function
function abr_restore_backup($zip_file) {
    $backup_dir = WP_CONTENT_DIR . '/uploads/backups/'; // Correct backup directory
    $zip_path = $backup_dir . basename($zip_file); // Full path to ZIP file

    error_log("Starting backup restore for: " . $zip_path);

    // Check if the ZIP file exists
    if (!file_exists($zip_path)) {
        error_log("Error: ZIP file not found - " . $zip_path);
        wp_redirect(admin_url('admin.php?page=abr_backup&message=ZipFileNotFound'));
        exit;
    }

    // Extract ZIP file
    $zip = new ZipArchive();
    if ($zip->open($zip_path) === TRUE) {
        error_log("Extracting ZIP file...");

        // Determine the restore location
        if (strpos($zip_file, 'backup-plugins-') !== false) {
            $restore_location = WP_CONTENT_DIR; // Plugins will be extracted directly here

            wp_redirect(admin_url('admin.php?page=abr_backup&message=success_restore-plugins'));

        }elseif (strpos($zip_file, 'backup-themes-') !== false) {
            $restore_location = WP_CONTENT_DIR; // Plugins will be extracted directly here

            wp_redirect(admin_url('admin.php?page=abr_backup&message=success_restore-themes'));

        }elseif (strpos($zip_file, 'backup-uploads-') !== false) {
            $restore_location = WP_CONTENT_DIR; // Plugins will be extracted directly here

            wp_redirect(admin_url('admin.php?page=abr_backup&message=success_restore-uploads'));

        } elseif (strpos($zip_file, 'backup-db-') !== false) {

            $restore_location = $backup_dir; // Plugins will be extracted directly here
            $zip->extractTo($restore_location);
            error_log("Detected database backup. Restoring database...");


            $filename_without_extension = str_replace('.zip', '', $zip_file);
            $db_file = $backup_dir .  '/database.sql';

            if (file_exists($db_file)) {
                $restore_result = abr_import_database($db_file);
                if (!$restore_result) {
                    error_log("Error: Database restoration failed.");
                    wp_redirect(admin_url('admin.php?page=abr_backup&message=DatabaseRestoreFailed'));
                    exit;
                }else{
                    wp_redirect(admin_url('admin.php?page=abr_backup&message=DatabaseRestoreSucces'));

                }
            } else {
                error_log("No database file found!");
                wp_redirect(admin_url('admin.php?page=abr_backup&message=DatabaseFileMissing'));
                exit;
            }
        } else {
            error_log("Unknown backup type.");
            wp_redirect(admin_url('admin.php?page=abr_backup&message=InvalidBackupType'));
            exit;
        }
        $zip->extractTo($restore_location);
        $zip->close();
        error_log("Extraction complete.");

    } else {
        error_log("Error: Could not open ZIP file " . $zip_path);
        wp_redirect(admin_url('admin.php?page=abr_backup&message=ZipExtractFailed'));
        exit;
    }

    // If it's not a plugin backup, move files to their respective locations
    error_log("Backup restoration completed successfully!");

    // ✅ Redirect to admin page with success message
//    wp_redirect(admin_url('admin.php?page=abr_backup&message=success_restore'));
//    exit;
}

// Function to move extracted files to the correct location
function abr_restore_files($source_dir, $destination_dir) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $file) {
        if ($file->isFile()) {
            $file_path = $file->getRealPath();
            $relative_path = substr($file_path, strlen($source_dir) + 1); // Ensure correct relative path
            $destination = $destination_dir . DIRECTORY_SEPARATOR . $relative_path;

            // Ensure destination directory exists
            wp_mkdir_p(dirname($destination));

            // Move the file
            if (!rename($file_path, $destination)) {
                error_log("Error: Failed to restore file {$relative_path}");
                return false;
            }

            // Set proper file permissions
            chmod($destination, 0644);
        }
    }
    return true;
}

// Function to import database using wpdb
// ✅ Fixed Database Restore Function (Ignores Duplicate Tables)
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

    // Fix: Modify `CREATE TABLE` statements to include `IF NOT EXISTS`
    $sql = preg_replace('/CREATE TABLE `([^`]+)`/', 'CREATE TABLE IF NOT EXISTS `$1`', $sql);

    // Fix: Use `INSERT IGNORE` instead of `INSERT` to prevent duplicate entries
    $sql = str_replace('INSERT INTO', 'INSERT IGNORE INTO', $sql);

    // Split SQL file into valid queries
    $queries = preg_split('/;\s*\n/', $sql, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $result = $wpdb->query($query);
            if ($result === false) {
                error_log("Error: Failed to execute query - " . substr($query, 0, 100) . "...");
                return false;
            }
        }
    }

    return true;
}
