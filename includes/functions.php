<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get list of backup files
 */
function abr_get_backup_files()
{
    $backup_files = array();
    $files = glob(ABR_BACKUP_DIR . '*.zip');

    if ($files) {
        foreach ($files as $file) {
            $file_info = array(
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'date' => filemtime($file),
                'formatted_size' => size_format(filesize($file)),
                'formatted_date' => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($file))
            );
            $backup_files[] = $file_info;
        }

        // Sort by date (newest first)
        usort($backup_files, function ($a, $b) {
            return $b['date'] - $a['date'];
        });
    }

    return $backup_files;
}

/**
 * Format file size
 */
function abr_format_bytes($size, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }

    return round($size, $precision) . ' ' . $units[$i];
}

/**
 * Check if backup directory is writable
 */
function abr_is_backup_dir_writable()
{
    return is_writable(ABR_BACKUP_DIR);
}

/**
 * Get backup directory size
 */
function abr_get_backup_dir_size()
{
    $size = 0;
    $files = glob(ABR_BACKUP_DIR . '*');

    foreach ($files as $file) {
        if (is_file($file)) {
            $size += filesize($file);
        }
    }

    return $size;
}

/**
 * Send notification email
 */
function abr_send_notification($subject, $message)
{
    $settings = get_option('abr_settings', array());

    if (empty($settings['email_notifications']) || empty($settings['notification_email'])) {
        return false;
    }

    $to = $settings['notification_email'];
    $headers = array('Content-Type: text/html; charset=UTF-8');

    $email_message = '<html><body>';
    $email_message .= '<h2>' . get_bloginfo('name') . ' - ' . $subject . '</h2>';
    $email_message .= '<p>' . $message . '</p>';
    $email_message .= '<p><strong>Site:</strong> ' . get_site_url() . '</p>';
    $email_message .= '<p><strong>Time:</strong> ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format')) . '</p>';
    $email_message .= '</body></html>';

    return wp_mail($to, $subject, $email_message, $headers);
}