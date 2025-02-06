<?php


if (!defined('ABSPATH')) {
    exit;
}

function abr_restore_backup($backup_file)
{
    $file_path = ABR_BACKUP_DIR . $backup_file;

    if (!file_exists($file_path)) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($file_path) !== true) {
        return false;
    }

    // Extract wp-content
    $zip->extractTo(ABSPATH);

    // Find and import database
    $db_backup_file = ABR_BACKUP_DIR . 'database.sql';
    if ($zip->locateName('database.sql') !== false) {
        $zip->extractTo(ABR_BACKUP_DIR, 'database.sql');
        abr_import_database($db_backup_file);
        unlink($db_backup_file);
    }

    $zip->close();
    wp_redirect(admin_url('admin.php?page=abr_backup&message=success_restore'));
    
    return true;
}

// Function to import database
function abr_import_database($file_path) {
    global $wpdb;
    $db_host = DB_HOST;
    $db_user = DB_USER;
    $db_pass = DB_PASSWORD;
    $db_name = DB_NAME;

    // Check if MySQL is available
    $command = "mysql --version";
    $output = shell_exec($command);
    if (!$output) {
        error_log("Error: mysql command is not available.");
        return false;
    }

    // Escape password properly if needed (Linux/Mac fix)
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        $db_pass = escapeshellarg($db_pass);
    }

    // Run MySQL import command
    $command = "mysql --host={$db_host} --user={$db_user} --password={$db_pass} {$db_name} < {$file_path} 2>&1";
    $output = shell_exec($command);

    if ($output) {
        error_log("Database Import Output: " . print_r($output, true));
    }

    return true;
}
