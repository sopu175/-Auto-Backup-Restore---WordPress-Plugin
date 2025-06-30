<?php
if (!defined('ABSPATH')) {
    exit;
}

class ABR_Backup {

    private $backup_dir;

    public function __construct() {
        $this->backup_dir = ABR_BACKUP_DIR;
    }

    public function create_full_backup() {
        try {
            // Ensure backup directory exists and is writable
            if (!file_exists($this->backup_dir)) {
                if (!wp_mkdir_p($this->backup_dir)) {
                    throw new Exception(__('Failed to create backup directory. Please check file permissions.', 'auto-backup-restore'));
                }
            }

            if (!is_writable($this->backup_dir)) {
                throw new Exception(__('Backup directory is not writable. Please check file permissions.', 'auto-backup-restore'));
            }

            // Check available disk space (at least 100MB required)
            $free_space = disk_free_space($this->backup_dir);
            if ($free_space !== false && $free_space < 100 * 1024 * 1024) {
                throw new Exception(__('Insufficient disk space. At least 100MB required for backup creation.', 'auto-backup-restore'));
            }

            $this->update_progress(5, __('Creating backup file...', 'auto-backup-restore'));

            $timestamp = date('Y-m-d_H-i-s');
            $backup_file = $this->backup_dir . 'full-backup-' . $timestamp . '.zip';

            $zip = new ZipArchive();
            if ($zip->open($backup_file, ZipArchive::CREATE) !== TRUE) {
                return array('success' => false, 'message' => __('Could not create backup file.', 'auto-backup-restore'));
            }

            $settings = get_option('abr_settings', array());
            $backup_types = isset($settings['backup_types']) ? $settings['backup_types'] : array('plugins', 'themes', 'uploads', 'database');

            $total_steps = count($backup_types) + 1; // +1 for site info
            $current_step = 0;

            // Backup plugins
            if (in_array('plugins', $backup_types)) {
                $current_step++;
                $progress = 10 + (($current_step / $total_steps) * 70);
                $this->update_progress($progress, __('Backing up plugins...', 'auto-backup-restore'));
                $this->add_directory_to_zip($zip, WP_PLUGIN_DIR, 'plugins/');
            }

            // Backup themes
            if (in_array('themes', $backup_types)) {
                $current_step++;
                $progress = 10 + (($current_step / $total_steps) * 70);
                $this->update_progress($progress, __('Backing up themes...', 'auto-backup-restore'));
                $this->add_directory_to_zip($zip, get_theme_root(), 'themes/');
            }

            // Backup uploads
            if (in_array('uploads', $backup_types)) {
                $current_step++;
                $progress = 10 + (($current_step / $total_steps) * 70);
                $this->update_progress($progress, __('Backing up uploads...', 'auto-backup-restore'));
                $upload_dir = wp_upload_dir();
                $this->add_directory_to_zip($zip, $upload_dir['basedir'], 'uploads/');
            }

            // Backup database
            if (in_array('database', $backup_types)) {
                $current_step++;
                $progress = 10 + (($current_step / $total_steps) * 70);
                $this->update_progress($progress, __('Backing up database...', 'auto-backup-restore'));
                $db_backup = $this->export_database();
                if ($db_backup) {
                    $zip->addFromString('database.sql', $db_backup);
                }
            }

            // Add site info
            $current_step++;
            $this->update_progress(90, __('Adding site information...', 'auto-backup-restore'));
            $site_info = $this->get_site_info();
            $zip->addFromString('site-info.json', json_encode($site_info, JSON_PRETTY_PRINT));

            $this->update_progress(95, __('Finalizing backup...', 'auto-backup-restore'));

            // Close the ZIP file with error checking
            if (!$zip->close()) {
                throw new Exception(__('Failed to finalize backup file.', 'auto-backup-restore'));
            }

            // Verify the backup file was created and is valid
            if (!file_exists($backup_file) || filesize($backup_file) === 0) {
                throw new Exception(__('Backup file was not created properly.', 'auto-backup-restore'));
            }

            $this->update_progress(98, __('Verifying backup...', 'auto-backup-restore'));

            // Quick verification that the ZIP file is valid
            $test_zip = new ZipArchive();
            if ($test_zip->open($backup_file, ZipArchive::CHECKCONS) !== TRUE) {
                @unlink($backup_file); // Remove invalid file
                throw new Exception(__('Created backup file is corrupted.', 'auto-backup-restore'));
            }
            $test_zip->close();

            $this->update_progress(100, __('Backup completed successfully!', 'auto-backup-restore'));

            // Send email notification if enabled (in background to avoid blocking)
            $this->send_backup_notification(true, basename($backup_file));

            // Clear progress after a short delay to show 100% briefly
            wp_schedule_single_event(time() + 2, 'abr_clear_backup_progress');

            return array(
                'success' => true,
                'message' => __('Backup created successfully!', 'auto-backup-restore'),
                'file' => basename($backup_file),
                'size' => size_format(filesize($backup_file))
            );

        } catch (Exception $e) {
            error_log('ABR Backup Error: ' . $e->getMessage());

            // Send error notification if enabled
            $this->send_backup_notification(false, null, $e->getMessage());

            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    private function update_progress($percent, $message) {
        set_transient('abr_backup_progress', array(
            'step' => 'processing',
            'percent' => $percent,
            'message' => $message
        ), 300); // 5 minutes expiry
    }

    private function send_backup_notification($success, $backup_file = null, $error_message = null) {
        $settings = get_option('abr_settings', array());

        // Check if email notifications are enabled
        if (empty($settings['email_notifications']) || empty($settings['notification_email'])) {
            return;
        }

        // Check if this type of notification is enabled
        $event_key = $success ? 'email_on_backup_success' : 'email_on_backup_failure';
        if (isset($settings[$event_key]) && !$settings[$event_key]) {
            return;
        }

        $site_name = get_bloginfo('name');
        $site_url = get_site_url();
        $current_time = current_time('mysql');
        $formatted_time = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($current_time));

        if ($success) {
            $subject = sprintf(__('✅ Backup Completed Successfully - %s', 'auto-backup-restore'), $site_name);

            $message = sprintf(__('Hello,

Your WordPress backup has been completed successfully!

Site: %s (%s)
Backup File: %s
Completed On: %s

The backup includes:
%s

Best regards,
Auto Backup & Restore Plugin', 'auto-backup-restore'),
                $site_name,
                $site_url,
                $backup_file,
                $formatted_time,
                $this->get_backup_types_text($settings)
            );
        } else {
            $subject = sprintf(__('❌ Backup Failed - %s', 'auto-backup-restore'), $site_name);

            $message = sprintf(__('Hello,

Unfortunately, your WordPress backup has failed.

Site: %s (%s)
Failed On: %s
Error: %s

Please check your site and try creating a backup manually. If the problem persists, please contact your website administrator.

Best regards,
Auto Backup & Restore Plugin', 'auto-backup-restore'),
                $site_name,
                $site_url,
                $formatted_time,
                $error_message ? $error_message : __('Unknown error occurred', 'auto-backup-restore')
            );
        }

        $email_format = isset($settings['email_format']) ? $settings['email_format'] : 'html';

        if ($email_format === 'html') {
            $message = $this->format_html_email($message, $subject, $success);
            $headers = array('Content-Type: text/html; charset=UTF-8');
        } else {
            $headers = array('Content-Type: text/plain; charset=UTF-8');
        }

        // Log email attempt for debugging
        error_log('ABR: Attempting to send email to: ' . $settings['notification_email']);
        error_log('ABR: Email subject: ' . $subject);

        // Add email debugging for localhost
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('ABR: Email content: ' . $message);
        }

        $mail_sent = wp_mail($settings['notification_email'], $subject, $message, $headers);

        // Log email result
        if ($mail_sent) {
            error_log('ABR: Email sent successfully');
        } else {
            error_log('ABR: Email failed to send - check mail configuration');

            // For localhost, save email to file for testing
            if ($this->is_localhost()) {
                $this->save_email_to_file($settings['notification_email'], $subject, $message, $success);
            }
        }

        return $mail_sent;
    }

    private function format_html_email($text_message, $subject, $success) {
        $site_name = get_bloginfo('name');
        $logo_url = get_site_icon_url(64);
        $status_color = $success ? '#00a32a' : '#dc2626';
        $status_icon = $success ? '✅' : '❌';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . esc_html($subject) . '</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f7fafc; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
                .header { background: ' . $status_color . '; color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .status-badge { display: inline-block; background: ' . $status_color . '; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
                .details { background: #f8fafc; padding: 20px; border-radius: 6px; border-left: 4px solid ' . $status_color . '; margin: 20px 0; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; color: #666; font-size: 14px; }
                .button { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $status_icon . ' ' . esc_html($subject) . '</h1>
                </div>
                <div class="content">
                    <div class="status-badge">' . ($success ? 'SUCCESS' : 'FAILED') . '</div>
                    <div class="details">' . nl2br(esc_html($text_message)) . '</div>
                    <a href="' . admin_url('admin.php?page=abr-backup') . '" class="button">View Backups</a>
                </div>
                <div class="footer">
                    <p>This email was sent by the Auto Backup & Restore plugin on ' . esc_html($site_name) . '</p>
                    <p><a href="' . get_site_url() . '">' . get_site_url() . '</a></p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
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
        $status = $success ? 'success' : 'failure';
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
        <h2>📧 Email Test - Auto Backup & Restore Pro</h2>
        <p><strong>To:</strong> {$to}</p>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Sent:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Status:</strong> <span style='color: " . ($success ? 'green' : 'red') . ";'>" . ($success ? 'SUCCESS' : 'FAILURE') . "</span></p>
    </div>
    <div class='email-body'>
        {$message}
    </div>
    <div style='margin-top: 20px; padding: 15px; background: #fffbf0; border: 1px solid #f0ad4e; border-radius: 5px;'>
        <strong>⚠️ Localhost Notice:</strong> This email was saved to file because mail sending is not available on localhost.
        In production, this would be sent as a real email.
    </div>
</body>
</html>";

        file_put_contents($filepath, $email_content);
        error_log("ABR: Email saved to file for localhost testing: {$filepath}");
    }

    private function get_backup_types_text($settings) {
        $backup_types = isset($settings['backup_types']) ? $settings['backup_types'] : array();
        $types_text = array();

        if (in_array('plugins', $backup_types)) {
            $types_text[] = '• ' . __('Plugins', 'auto-backup-restore');
        }
        if (in_array('themes', $backup_types)) {
            $types_text[] = '• ' . __('Themes', 'auto-backup-restore');
        }
        if (in_array('uploads', $backup_types)) {
            $types_text[] = '• ' . __('Media/Uploads', 'auto-backup-restore');
        }
        if (in_array('database', $backup_types)) {
            $types_text[] = '• ' . __('Database', 'auto-backup-restore');
        }

        return implode("\n", $types_text);
    }

    private function add_directory_to_zip($zip, $source_dir, $zip_path = '') {
        if (!is_dir($source_dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $file_path = $file->getRealPath();
            $relative_path = $zip_path . substr($file_path, strlen($source_dir) + 1);

            // Skip backup directory itself
            if (strpos($file_path, ABR_BACKUP_DIR) === 0) {
                continue;
            }

            // Skip large files (over 100MB)
            if ($file->isFile() && $file->getSize() > 100 * 1024 * 1024) {
                continue;
            }

            if ($file->isDir()) {
                $zip->addEmptyDir($relative_path);
            } elseif ($file->isFile()) {
                $zip->addFile($file_path, $relative_path);
            }
        }
    }

    private function export_database() {
        global $wpdb;

        try {
            $tables = $wpdb->get_col("SHOW TABLES");
            if (!$tables) {
                return false;
            }

            $sql_dump = "-- WordPress Database Backup\n";
            $sql_dump .= "-- Generated on " . date('Y-m-d H:i:s') . "\n";
            $sql_dump .= "-- Site URL: " . get_site_url() . "\n\n";
            $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                // Get table structure
                $create_table = $wpdb->get_row("SHOW CREATE TABLE `$table`", ARRAY_N);
                if ($create_table) {
                    $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
                    $sql_dump .= $create_table[1] . ";\n\n";
                }

                // Get table data
                $rows = $wpdb->get_results("SELECT * FROM `$table`", ARRAY_A);
                if ($rows) {
                    foreach ($rows as $row) {
                        $values = array();
                        foreach ($row as $value) {
                            if (is_null($value)) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = "'" . $wpdb->_real_escape($value) . "'";
                            }
                        }
                        $sql_dump .= "INSERT INTO `$table` VALUES (" . implode(", ", $values) . ");\n";
                    }
                    $sql_dump .= "\n";
                }
            }

            $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";

            return $sql_dump;

        } catch (Exception $e) {
            error_log('Database export error: ' . $e->getMessage());
            return false;
        }
    }

    private function get_site_info() {
        global $wp_version;

        return array(
            'site_url' => get_site_url(),
            'home_url' => get_home_url(),
            'wp_version' => $wp_version,
            'php_version' => PHP_VERSION,
            'mysql_version' => $this->get_mysql_version(),
            'backup_date' => date('Y-m-d H:i:s'),
            'backup_plugin_version' => ABR_VERSION,
            'active_theme' => get_option('stylesheet'),
            'active_plugins' => get_option('active_plugins')
        );
    }

    private function get_mysql_version() {
        global $wpdb;
        return $wpdb->get_var("SELECT VERSION()");
    }
}