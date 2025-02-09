<?php
if (!defined('ABSPATH')) {
    exit;
}

// Main Backup Function
function abr_create_backup() {
    $backup_dir = ABR_BACKUP_DIR;

    // Ensure backup directory exists
    if (!file_exists($backup_dir) && !mkdir($backup_dir, 0755, true) && !is_dir($backup_dir)) {
        error_log("Error: Could not create backup directory at {$backup_dir}");
        return json_encode(['status' => 'error', 'message' => 'Failed to create backup directory']);
    }

    if (!is_writable($backup_dir)) {
        error_log("Error: Backup directory is not writable: {$backup_dir}");
        return json_encode(['status' => 'error', 'message' => 'Backup directory is not writable']);
    }

    // Generate filenames with timestamp
    $timestamp = date('Y-m-d-H-i-s');
    $backup_plugins = "{$backup_dir}backup-plugins-{$timestamp}.zip";
    $backup_themes = "{$backup_dir}backup-themes-{$timestamp}.zip";
    $backup_uploads = "{$backup_dir}backup-uploads-{$timestamp}.zip";
    $backup_db = "{$backup_dir}backup-db-{$timestamp}.zip";

    // Create Backup for Plugins
    create_zip_backup(WP_PLUGIN_DIR, $backup_plugins, 'plugins/');

    // Create Backup for Themes
    create_zip_backup(get_theme_root(), $backup_themes, 'themes/');

    // Create Backup for Uploads
    create_zip_backup(WP_CONTENT_DIR . '/uploads/', $backup_uploads, 'uploads/');

    // Create Database Backup ZIP
    $db_backup_content = abr_export_database();
    if ($db_backup_content !== false) {
        $zip_db = new ZipArchive();
        if ($zip_db->open($backup_db, ZipArchive::CREATE) !== true) {
            error_log("Error: Could not create ZIP file {$backup_db}");
            return json_encode(['status' => 'error', 'message' => 'Failed to create database ZIP archive']);
        }
        $zip_db->addFromString('database.sql', $db_backup_content);
        $zip_db->setCompressionIndex(0, ZipArchive::CM_DEFLATE);
        $zip_db->close();
    } else {
        error_log("Warning: Database export failed.");
    }

    return json_encode([
        'status' => 'success',
        'message' => 'Backup completed successfully',
        'backup_plugins' => $backup_plugins,
        'backup_themes' => $backup_themes,
        'backup_uploads' => $backup_uploads,
        'backup_db' => $backup_db
    ]);
}

// Function to create ZIP backups for files
function create_zip_backup($source, $zip_file, $folder_name) {
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) !== true) {
        error_log("Error: Could not create ZIP file {$zip_file}");
        return false;
    }
    add_folder_to_zip($source, $zip, $folder_name);
    $zip->setCompressionIndex(0, ZipArchive::CM_DEFLATE);
    $zip->close();
    return true;
}

// Recursively add files to ZIP
function add_folder_to_zip($folder, $zip, $parent_folder) {
    $files = scandir($folder);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $file_path = $folder . DIRECTORY_SEPARATOR . $file;
        $relative_path = $parent_folder . $file;
        if (is_dir($file_path)) {
            add_folder_to_zip($file_path, $zip, $relative_path . '/');
        } else {
            $zip->addFile($file_path, $relative_path);
        }
    }
}

// Export database using wpdb (No CLI needed)
function abr_export_database() {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");

    if (!$tables) {
        error_log("Error: Unable to retrieve database tables.");
        return false;
    }

    $sql_dump = "-- WordPress Database Backup\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
        if (!$create_table) continue;
        $sql_dump .= "\n\n" . $create_table[1] . ";\n\n";

        $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
        foreach ($rows as $row) {
            $values = array_map(fn($value) => "'" . esc_sql($value) . "'", $row);
            $sql_dump .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
        }
    }

    return $sql_dump;
}

// WordPress Cron Job for Scheduled Backups
function abr_schedule_backup($interval) {
    if (!wp_next_scheduled('abr_scheduled_backup')) {
        wp_schedule_event(time(), $interval, 'abr_scheduled_backup');
    }
}

add_action('abr_scheduled_backup', 'abr_create_backup');
