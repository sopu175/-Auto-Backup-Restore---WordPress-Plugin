<?php
if (!defined('ABSPATH')) {
    exit;
}

class ABR_Restore {

    private $backup_dir;

    public function __construct() {
        $this->backup_dir = ABR_BACKUP_DIR;
    }

    public function restore_backup($backup_file) {
        try {
            // Set initial progress
            $this->update_restore_progress(5, __('Starting restore process...', 'auto-backup-restore'));

            // Validate backup file name
            if (empty($backup_file) || !preg_match('/^[a-zA-Z0-9\-_.]+\.zip$/', $backup_file)) {
                throw new Exception(__('Invalid backup file name.', 'auto-backup-restore'));
            }

            $backup_path = $this->backup_dir . $backup_file;

            // Security check: ensure file is in backup directory
            if (dirname($backup_path) !== rtrim($this->backup_dir, '/')) {
                throw new Exception(__('Security violation: Invalid file path.', 'auto-backup-restore'));
            }

            if (!file_exists($backup_path)) {
                $this->update_restore_progress(0, __('Backup file not found.', 'auto-backup-restore'));
                return array('success' => false, 'message' => __('Backup file not found.', 'auto-backup-restore'));
            }

            if (!is_readable($backup_path)) {
                throw new Exception(__('Backup file is not readable. Please check file permissions.', 'auto-backup-restore'));
            }

            // Check available disk space
            $backup_size = filesize($backup_path);
            $free_space = disk_free_space($this->backup_dir);
            if ($free_space !== false && $free_space < ($backup_size * 2)) {
                throw new Exception(__('Insufficient disk space for restoration. Need at least twice the backup size.', 'auto-backup-restore'));
            }

            // Check if ZipArchive is available
            if (!class_exists('ZipArchive')) {
                $this->update_restore_progress(0, __('ZipArchive not available.', 'auto-backup-restore'));
                return array('success' => false, 'message' => __('ZipArchive extension is not available on this server.', 'auto-backup-restore'));
            }

            $this->update_restore_progress(10, __('Opening backup file...', 'auto-backup-restore'));

            $zip = new ZipArchive();
            $open_result = $zip->open($backup_path);
            if ($open_result !== TRUE) {
                $error_msg = $this->get_zip_error_message($open_result);
                $this->update_restore_progress(0, __('Could not open backup file.', 'auto-backup-restore'));
                return array('success' => false, 'message' => __('Could not open backup file: ', 'auto-backup-restore') . $error_msg);
            }

            $this->update_restore_progress(15, __('Preparing extraction directory...', 'auto-backup-restore'));

            // Create temporary extraction directory
            $temp_dir = $this->backup_dir . 'temp_restore_' . time() . '/';
            if (!wp_mkdir_p($temp_dir)) {
                $zip->close();
                $this->update_restore_progress(0, __('Failed to create temporary directory.', 'auto-backup-restore'));
                return array('success' => false, 'message' => __('Failed to create temporary extraction directory.', 'auto-backup-restore'));
            }

            $this->update_restore_progress(20, __('Extracting backup file...', 'auto-backup-restore'));

            // Extract backup with progress tracking
            $total_files = $zip->numFiles;
            $extracted = 0;

            for ($i = 0; $i < $total_files; $i++) {
                $file_info = $zip->statIndex($i);
                if ($file_info !== false) {
                    $zip->extractTo($temp_dir, array($file_info['name']));
                    $extracted++;

                    // Update progress every 10% of files
                    if ($extracted % max(1, floor($total_files / 10)) === 0) {
                        $extraction_progress = 20 + (($extracted / $total_files) * 10); // 20% to 30%
                        $this->update_restore_progress($extraction_progress, sprintf(__('Extracting files... %d/%d', 'auto-backup-restore'), $extracted, $total_files));
                    }
                }
            }

            $zip->close();
            $this->update_restore_progress(30, __('Extraction completed. Preparing restore...', 'auto-backup-restore'));

            $restored_items = array();
            $current_progress = 30;
            $step_size = 15; // Each restore step gets 15% progress

            // Restore plugins
            if (is_dir($temp_dir . 'plugins')) {
                $this->update_restore_progress($current_progress, __('Restoring plugins...', 'auto-backup-restore'));
                if ($this->restore_directory($temp_dir . 'plugins', WP_PLUGIN_DIR)) {
                    $restored_items[] = __('Plugins', 'auto-backup-restore');
                }
                $current_progress += $step_size;
            }

            // Restore themes
            if (is_dir($temp_dir . 'themes')) {
                $this->update_restore_progress($current_progress, __('Restoring themes...', 'auto-backup-restore'));
                if ($this->restore_directory($temp_dir . 'themes', get_theme_root())) {
                    $restored_items[] = __('Themes', 'auto-backup-restore');
                }
                $current_progress += $step_size;
            }

            // Restore uploads
            if (is_dir($temp_dir . 'uploads')) {
                $this->update_restore_progress($current_progress, __('Restoring uploads...', 'auto-backup-restore'));
                $upload_dir = wp_upload_dir();
                if ($this->restore_directory($temp_dir . 'uploads', $upload_dir['basedir'])) {
                    $restored_items[] = __('Uploads', 'auto-backup-restore');
                }
                $current_progress += $step_size;
            }

            // Restore database
            if (file_exists($temp_dir . 'database.sql')) {
                $this->update_restore_progress($current_progress, __('Restoring database...', 'auto-backup-restore'));
                if ($this->restore_database($temp_dir . 'database.sql')) {
                    $restored_items[] = __('Database', 'auto-backup-restore');
                }
                $current_progress += $step_size;
            }

            $this->update_restore_progress(95, __('Cleaning up temporary files...', 'auto-backup-restore'));

            // Cleanup
            $this->cleanup_temp_dir($temp_dir);

            if (empty($restored_items)) {
                $this->update_restore_progress(0, __('No items were restored from the backup.', 'auto-backup-restore'));
                return array('success' => false, 'message' => __('No items were restored from the backup.', 'auto-backup-restore'));
            }

            $this->update_restore_progress(100, __('Restore completed successfully!', 'auto-backup-restore'));

            // Send notification email
            $this->send_restore_notification(true, $backup_file, $restored_items);

            $message = sprintf(__('Successfully restored: %s', 'auto-backup-restore'), implode(', ', $restored_items));
            return array('success' => true, 'message' => $message);

        } catch (Exception $e) {
            $this->update_restore_progress(0, __('Restore failed.', 'auto-backup-restore'));
            $this->send_restore_notification(false, $backup_file, array(), $e->getMessage());
            error_log('ABR Restore Error: ' . $e->getMessage());
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function update_restore_progress($percent, $message) {
        set_transient('abr_restore_progress', array(
            'step' => 'processing',
            'percent' => $percent,
            'message' => $message
        ), 300); // 5 minutes expiry
    }

    private function get_zip_error_message($error_code) {
        $zip_errors = array(
            ZipArchive::ER_OK => 'No error',
            ZipArchive::ER_MULTIDISK => 'Multi-disk zip archives not supported',
            ZipArchive::ER_RENAME => 'Renaming temporary file failed',
            ZipArchive::ER_CLOSE => 'Closing zip archive failed',
            ZipArchive::ER_SEEK => 'Seek error',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_WRITE => 'Write error',
            ZipArchive::ER_CRC => 'CRC error',
            ZipArchive::ER_ZIPCLOSED => 'Containing zip archive was closed',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_TMPOPEN => 'Failure to create temporary file',
            ZipArchive::ER_ZLIB => 'Zlib error',
            ZipArchive::ER_MEMORY => 'Memory allocation failure',
            ZipArchive::ER_CHANGED => 'Entry has been changed',
            ZipArchive::ER_COMPNOTSUPP => 'Compression method not supported',
            ZipArchive::ER_EOF => 'Premature EOF',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_INTERNAL => 'Internal error',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_REMOVE => 'Can\'t remove file',
            ZipArchive::ER_DELETED => 'Entry has been deleted'
        );

        return isset($zip_errors[$error_code]) ? $zip_errors[$error_code] : 'Unknown error';
    }

    private function send_restore_notification($success, $backup_file = null, $restored_items = array(), $error_message = null) {
        $settings = get_option('abr_settings', array());

        // Check if email notifications are enabled
        if (empty($settings['email_notifications']) || empty($settings['notification_email'])) {
            return;
        }

        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        $current_time = current_time('mysql');
        $formatted_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($current_time));

        if ($success) {
            $subject = sprintf(__('✅ Restore Completed Successfully - %s', 'auto-backup-restore'), $site_name);

            $message = sprintf(__('Hello,

Your WordPress site has been restored successfully!

Site: %s (%s)
Backup File: %s
Restored On: %s

Restored Components:
%s

Best regards,
Auto Backup & Restore Plugin', 'auto-backup-restore'),
                $site_name,
                $site_url,
                $backup_file,
                $formatted_time,
                !empty($restored_items) ? '• ' . implode("\n• ", $restored_items) : __('No components restored', 'auto-backup-restore')
            );
        } else {
            $subject = sprintf(__('❌ Restore Failed - %s', 'auto-backup-restore'), $site_name);

            $message = sprintf(__('Hello,

Unfortunately, your WordPress restore process has failed.

Site: %s (%s)
Backup File: %s
Failed On: %s
Error: %s

Please check your site and try restoring manually. If the problem persists, please contact your website administrator.

Best regards,
Auto Backup & Restore Plugin', 'auto-backup-restore'),
                $site_name,
                $site_url,
                $backup_file ? $backup_file : __('Unknown', 'auto-backup-restore'),
                $formatted_time,
                $error_message ? $error_message : __('Unknown error occurred', 'auto-backup-restore')
            );
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        // Log email attempt for debugging
        error_log('ABR Restore: Attempting to send email to: ' . $settings['notification_email']);
        error_log('ABR Restore: Email subject: ' . $subject);

        $mail_sent = wp_mail($settings['notification_email'], $subject, $message, $headers);

        // Log email result
        if ($mail_sent) {
            error_log('ABR Restore: Email sent successfully');
        } else {
            error_log('ABR Restore: Email failed to send - check mail configuration');

            // For localhost, save email to file for testing
            if ($this->is_localhost()) {
                $this->save_email_to_file($settings['notification_email'], $subject, $message, $success);
            }
        }

        return $mail_sent;
    }

    private function restore_directory($source_dir, $destination_dir) {
        try {
            if (!is_dir($source_dir)) {
                return false;
            }

            // Create destination directory if it doesn't exist
            if (!is_dir($destination_dir)) {
                wp_mkdir_p($destination_dir);
            }

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                $source_file = $file->getRealPath();
                $relative_path = substr($source_file, strlen($source_dir) + 1);
                $destination_file = $destination_dir . '/' . $relative_path;

                if ($file->isDir()) {
                    wp_mkdir_p($destination_file);
                } elseif ($file->isFile()) {
                    // Create directory if it doesn't exist
                    $destination_dir_path = dirname($destination_file);
                    if (!is_dir($destination_dir_path)) {
                        wp_mkdir_p($destination_dir_path);
                    }

                    // Copy file
                    if (!copy($source_file, $destination_file)) {
                        error_log("Failed to copy file: $source_file to $destination_file");
                        continue;
                    }

                    // Set proper permissions
                    chmod($destination_file, 0644);
                }
            }

            return true;

        } catch (Exception $e) {
            error_log('Directory restore error: ' . $e->getMessage());
            return false;
        }
    }

    private function restore_database($sql_file) {
        global $wpdb;

        try {
            $sql_content = file_get_contents($sql_file);
            if (!$sql_content) {
                return false;
            }

            // Split SQL into individual queries
            $queries = $this->split_sql($sql_content);

            // Disable foreign key checks
            $wpdb->query("SET FOREIGN_KEY_CHECKS=0");

            foreach ($queries as $query) {
                $query = trim($query);
                if (empty($query) || substr($query, 0, 2) === '--') {
                    continue;
                }

                // Direct SQL query for database restore.
                $result = $wpdb->query($query);
                if ($result === false) {
                    error_log("Database restore query failed: " . substr($query, 0, 100) . "...");
                    // Continue with other queries instead of failing completely
                }
            }

            // Re-enable foreign key checks
            $wpdb->query("SET FOREIGN_KEY_CHECKS=1");

            return true;

        } catch (Exception $e) {
            error_log('Database restore error: ' . $e->getMessage());
            return false;
        }
    }

    private function split_sql($sql) {
        $queries = array();
        $current_query = '';
        $in_string = false;
        $string_char = '';

        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || substr($line, 0, 2) === '--') {
                continue;
            }

            $current_query .= $line . "\n";

            // Simple check for end of query (semicolon at end of line)
            if (substr($line, -1) === ';') {
                $queries[] = trim($current_query);
                $current_query = '';
            }
        }

        // Add any remaining query
        if (!empty(trim($current_query))) {
            $queries[] = trim($current_query);
        }

        return $queries;
    }

    private function cleanup_temp_dir($temp_dir) {
        if (!is_dir($temp_dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($temp_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        rmdir($temp_dir);
    }

    private function is_localhost() {
        $server_name = $_SERVER['SERVER_NAME'] ?? '';
        $localhost_indicators = array('localhost', '127.0.0.1', '::1', 'local.dev', '.local', '.test');

        foreach ($localhost_indicators as $indicator) {
            if (strpos($server_name, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    private function save_email_to_file($to, $subject, $message, $success) {
        $emails_dir = ABR_BACKUP_DIR . 'emails/';

        // Create emails directory if it doesn't exist
        if (!file_exists($emails_dir)) {
            wp_mkdir_p($emails_dir);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $status = $success ? 'restore-success' : 'restore-failure';
        $filename = "email-{$status}-{$timestamp}.html";
        $filepath = $emails_dir . $filename;

        $email_content = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>{$subject}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .email-header { background: #f0f0f0; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .email-body { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='email-header'>
        <h2>📧 Restore Email Test - Auto Backup & Restore Pro</h2>
        <p><strong>To:</strong> {$to}</p>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Status:</strong> <span style='color: " . ($success ? 'green' : 'red') . ";'>" . ($success ? 'SUCCESS' : 'FAILURE') . "</span></p>
    </div>
    <div class='email-body'>
        " . nl2br(esc_html($message)) . "
    </div>
    <div style='margin-top: 20px; padding: 15px; background: #fffbf0; border: 1px solid #f0ad4e; border-radius: 5px;'>
        <strong>⚠️ Localhost Notice:</strong> This email was saved to file because mail sending is not available on localhost.
        In production, this would be sent as a real email.
    </div>
</body>
</html>";

        file_put_contents($filepath, $email_content);
        error_log("ABR Restore: Email saved to file for localhost testing: {$filepath}");
    }
}