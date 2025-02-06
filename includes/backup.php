<?php
if (!defined('ABSPATH')) {
    exit;
}

// Backup function
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

    // Define a single ZIP file for this backup
    $backup_file = $backup_dir . 'backup-' . date('Y-m-d-H-i-s') . '.zip';

    // Prevent duplicate backups by checking if the file already exists
    if (file_exists($backup_file)) {
        return json_encode(['status' => 'error', 'message' => 'A backup is already in progress']);
    }

    $zip = new ZipArchive();
    if ($zip->open($backup_file, ZipArchive::CREATE) !== true) {
        error_log("Error: Could not create ZIP file {$backup_file}");
        return json_encode(['status' => 'error', 'message' => 'Failed to create ZIP archive']);
    }

    // Add wp-content folder to ZIP
    $wp_content_path = ABSPATH . 'wp-content/';
    add_folder_to_zip($wp_content_path, $zip, 'wp-content/');

    // Export database and add to ZIP
    $db_backup_content = abr_export_database();
    if ($db_backup_content !== false) {
        $zip->addFromString('database.sql', $db_backup_content);
    } else {
        error_log("Warning: Database export failed.");
    }

    // Close ZIP file
    $zip->close();

    return json_encode(['status' => 'success', 'message' => 'Backup completed successfully', 'backup_file' => $backup_file]);
}

// Function to recursively add files to ZIP
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

// Dynamic WordPress Database Export without mysqldump
function abr_export_database() {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");

    if (!$tables) {
        error_log("Error: Unable to retrieve database tables.");
        return false;
    }

    $sql_dump = "-- WordPress Database Backup\n-- Generated on " . date('Y-m-d H:i:s') . "\n\n";

    foreach ($tables as $table) {
        // Get table structure
        $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
        if (!$create_table) continue;
        $sql_dump .= "\n\n" . $create_table[1] . ";\n\n";

        // Get table data
        $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
        foreach ($rows as $row) {
            $values = array_map(fn($value) => "'" . esc_sql($value) . "'", $row);
            $sql_dump .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
        }
    }

    return $sql_dump;
}

// AJAX Handler for Backup Process
add_action('wp_ajax_abr_ajax_backup', 'abr_ajax_backup');

function abr_ajax_backup() {
    // Ensure only one backup is created per request
    check_ajax_referer('abr_ajax_backup_nonce', 'security');

    // Prevent multiple backups by adding a transient lock
    if (get_transient('abr_backup_in_progress')) {
        echo json_encode(['status' => 'error', 'message' => 'A backup is already running']);
        wp_die();
    }

    set_transient('abr_backup_in_progress', true, 300); // Lock for 5 minutes
    $response = abr_create_backup();
    delete_transient('abr_backup_in_progress'); // Remove lock after completion

    echo $response;
    wp_die();
}

// Enqueue necessary scripts for AJAX
add_action('admin_enqueue_scripts', 'abr_enqueue_admin_scripts');
function abr_enqueue_admin_scripts() {
    wp_enqueue_script('abr-backup-script', plugin_dir_url(__FILE__) . 'js/abr-backup.js', ['jquery'], null, true);
    wp_localize_script('abr-backup-script', 'abr_backup', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('abr_ajax_backup_nonce')
    ]);
}
