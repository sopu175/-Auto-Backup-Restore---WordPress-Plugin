<?php
/*
Plugin Name: Auto-Backup & One-Click Restore Pro
Plugin URI: https://devsopu.com/auto-backup-restore
Description: Professional backup and restore solution for WordPress. Create complete site backups with advanced progress tracking, email notifications, and one-click restoration. Developed by Saif Islam.
Version: 1.2.0
Author: Saif Islam
Author URI: https://devsopu.com
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: auto-backup-restore
Domain Path: /languages
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Network: false
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ABR_VERSION', '1.2.0');
define('ABR_PLUGIN_FILE', __FILE__);
define('ABR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ABR_BACKUP_DIR', WP_CONTENT_DIR . '/uploads/abr-backups/');
define('ABR_TEXT_DOMAIN', 'auto-backup-restore');

// Main plugin class
class AutoBackupRestore {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
    }

    private function init_hooks() {
        register_activation_hook(ABR_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(ABR_PLUGIN_FILE, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'handle_admin_actions'));

        // AJAX hooks
        add_action('wp_ajax_abr_create_backup', array($this, 'ajax_create_backup'));
        add_action('wp_ajax_abr_backup_progress', array($this, 'ajax_backup_progress'));
        add_action('wp_ajax_abr_restore_backup', array($this, 'ajax_restore_backup'));
        add_action('wp_ajax_abr_restore_progress', array($this, 'ajax_restore_progress'));
        add_action('wp_ajax_abr_refresh_data', array($this, 'ajax_refresh_data'));
        add_action('wp_ajax_abr_delete_backup', array($this, 'ajax_delete_backup'));
        add_action('wp_ajax_abr_test_email', array($this, 'ajax_test_email'));

        // Cron hooks
        add_action('abr_daily_backup', array($this, 'run_scheduled_backup'));
        add_action('abr_weekly_backup', array($this, 'run_scheduled_backup'));
        add_action('abr_monthly_backup', array($this, 'run_scheduled_backup'));

        // Progress cleanup hook
        add_action('abr_clear_backup_progress', array($this, 'clear_backup_progress'));
    }

    private function load_dependencies() {
        require_once ABR_PLUGIN_DIR . 'includes/class-backup.php';
        require_once ABR_PLUGIN_DIR . 'includes/class-restore.php';
        require_once ABR_PLUGIN_DIR . 'includes/class-settings.php';
        require_once ABR_PLUGIN_DIR . 'includes/functions.php';
    }

    public function init() {
        // Load text domain for translations
        load_plugin_textdomain(ABR_TEXT_DOMAIN, false, dirname(plugin_basename(ABR_PLUGIN_FILE)) . '/languages');
    }

    public function activate() {
        // Create backup directory
        $this->create_backup_directory();

        // Set default options
        if (!get_option('abr_settings')) {
            add_option('abr_settings', array(
                'auto_backup_enabled' => false,
                'backup_frequency' => 'daily',
                'max_backups' => 10,
                'backup_types' => array('plugins', 'themes', 'uploads', 'database')
            ));
        }

        // Schedule default backup if enabled
        $this->schedule_backups();

        // Create .htaccess file for backup directory security
        $this->secure_backup_directory();
    }

    public function deactivate() {
        // Clear scheduled backups
        wp_clear_scheduled_hook('abr_daily_backup');
        wp_clear_scheduled_hook('abr_weekly_backup');
        wp_clear_scheduled_hook('abr_monthly_backup');
    }

    private function create_backup_directory() {
        if (!file_exists(ABR_BACKUP_DIR)) {
            $created = wp_mkdir_p(ABR_BACKUP_DIR);
            if (!$created) {
                error_log('ABR: Failed to create backup directory: ' . ABR_BACKUP_DIR);
            } else {
                error_log('ABR: Backup directory created: ' . ABR_BACKUP_DIR);
            }
        }
    }

    private function secure_backup_directory() {
        $htaccess_file = ABR_BACKUP_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "Order deny,allow\nDeny from all\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }

        $index_file = ABR_BACKUP_DIR . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Auto Backup & Restore Pro', ABR_TEXT_DOMAIN),
            __('Backup Pro', ABR_TEXT_DOMAIN),
            'manage_options',
            'abr-backup',
            array($this, 'admin_page'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>'),
            75
        );

        add_submenu_page(
            'abr-backup',
            __('Settings', ABR_TEXT_DOMAIN),
            __('Settings', ABR_TEXT_DOMAIN),
            'manage_options',
            'abr-settings',
            array($this, 'settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'abr-') !== false) {
            wp_enqueue_style('abr-admin-style', ABR_PLUGIN_URL . 'assets/css/admin.css', array(), ABR_VERSION);
            wp_enqueue_script('abr-admin-script', ABR_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), ABR_VERSION, true);

            wp_localize_script('abr-admin-script', 'abr_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('abr_ajax_nonce'),
                'strings' => array(
                    'backup_in_progress' => __('Backup in progress...', ABR_TEXT_DOMAIN),
                    'backup_completed' => __('Backup completed successfully!', ABR_TEXT_DOMAIN),
                    'backup_failed' => __('Backup failed. Please try again.', ABR_TEXT_DOMAIN),
                    'confirm_restore' => __('Are you sure you want to restore this backup? This will overwrite current files and database.', ABR_TEXT_DOMAIN),
                    'confirm_delete' => __('Are you sure you want to delete this backup?', ABR_TEXT_DOMAIN),
                    'restore_in_progress' => __('Restore in progress...', ABR_TEXT_DOMAIN),
                    'restore_completed' => __('Restore completed successfully!', ABR_TEXT_DOMAIN),
                    'restore_failed' => __('Restore failed. Please try again.', ABR_TEXT_DOMAIN)
                )
            ));
        }
    }

    public function admin_page() {
        include ABR_PLUGIN_DIR . 'admin/backup-page.php';
    }

    public function settings_page() {
        include ABR_PLUGIN_DIR . 'admin/settings-page.php';
    }

    public function handle_admin_actions() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', ABR_TEXT_DOMAIN));
        }

        // Handle backup creation
        if (isset($_POST['abr_create_backup']) && wp_verify_nonce($_POST['abr_nonce'], 'abr_backup_action')) {
            try {
                $backup = new ABR_Backup();
                $result = $backup->create_full_backup();

                if ($result['success']) {
                    add_settings_error('abr_messages', 'backup_success', esc_html__('Backup created successfully!', ABR_TEXT_DOMAIN), 'success');
                } else {
                    add_settings_error('abr_messages', 'backup_error', esc_html($result['message']), 'error');
                }
            } catch (Exception $e) {
                error_log('ABR Admin Action Error: ' . $e->getMessage());
                add_settings_error('abr_messages', 'backup_error', esc_html__('An error occurred while creating the backup. Please try again.', ABR_TEXT_DOMAIN), 'error');
            }
        }

        // Handle backup restoration
        if (isset($_GET['abr_restore']) && wp_verify_nonce($_GET['_wpnonce'], 'abr_restore_backup')) {
            try {
                $backup_file = sanitize_file_name($_GET['abr_restore']);

                // Validate backup file
                if (empty($backup_file) || !preg_match('/^[a-zA-Z0-9\-_.]+\.zip$/', $backup_file)) {
                    throw new Exception(__('Invalid backup file name.', ABR_TEXT_DOMAIN));
                }

                $restore = new ABR_Restore();
                $result = $restore->restore_backup($backup_file);

                $message_type = $result['success'] ? 'success' : 'error';
                wp_redirect(add_query_arg(array(
                    'page' => 'abr-backup',
                    'message' => $message_type,
                    'details' => urlencode($result['message'])
                ), admin_url('admin.php')));
                exit;
            } catch (Exception $e) {
                error_log('ABR Restore Error: ' . $e->getMessage());
                wp_redirect(add_query_arg(array(
                    'page' => 'abr-backup',
                    'message' => 'error',
                    'details' => urlencode(__('Restore failed: ', ABR_TEXT_DOMAIN) . $e->getMessage())
                ), admin_url('admin.php')));
                exit;
            }
        }

        // Handle backup deletion
        if (isset($_GET['abr_delete']) && wp_verify_nonce($_GET['_wpnonce'], 'abr_delete_backup')) {
            try {
                $backup_file = sanitize_file_name($_GET['abr_delete']);

                // Validate backup file
                if (empty($backup_file) || !preg_match('/^[a-zA-Z0-9\-_.]+\.zip$/', $backup_file)) {
                    throw new Exception(__('Invalid backup file name.', ABR_TEXT_DOMAIN));
                }

                $file_path = ABR_BACKUP_DIR . $backup_file;

                // Security check
                if (dirname($file_path) !== rtrim(ABR_BACKUP_DIR, '/')) {
                    throw new Exception(__('Invalid file path.', ABR_TEXT_DOMAIN));
                }

                if (file_exists($file_path) && unlink($file_path)) {
                    $message = __('Backup deleted successfully.', ABR_TEXT_DOMAIN);
                    $type = 'success';
                } else {
                    $message = __('Failed to delete backup file.', ABR_TEXT_DOMAIN);
                    $type = 'error';
                }

                wp_redirect(add_query_arg(array(
                    'page' => 'abr-backup',
                    'message' => $type,
                    'details' => urlencode($message)
                ), admin_url('admin.php')));
                exit;
            } catch (Exception $e) {
                error_log('ABR Delete Error: ' . $e->getMessage());
                wp_redirect(add_query_arg(array(
                    'page' => 'abr-backup',
                    'message' => 'error',
                    'details' => urlencode(__('Delete failed: ', ABR_TEXT_DOMAIN) . $e->getMessage())
                ), admin_url('admin.php')));
                exit;
            }
        }

        // Handle settings save
        if (isset($_POST['abr_save_settings']) && wp_verify_nonce($_POST['abr_settings_nonce'], 'abr_settings_action')) {
            try {
                $settings = new ABR_Settings();
                $result = $settings->save_settings($_POST);

                if ($result) {
                    add_settings_error('abr_messages', 'settings_saved', esc_html__('Settings saved successfully!', ABR_TEXT_DOMAIN), 'success');
                } else {
                    add_settings_error('abr_messages', 'settings_error', esc_html__('Some settings could not be saved. Please check the validation errors above.', ABR_TEXT_DOMAIN), 'error');
                }
            } catch (Exception $e) {
                error_log('ABR Settings Save Error: ' . $e->getMessage());
                add_settings_error('abr_messages', 'settings_error', esc_html__('An error occurred while saving settings. Please try again.', ABR_TEXT_DOMAIN), 'error');
            }
        }
    }

    public function schedule_backups() {
        $settings = get_option('abr_settings', array());

        if (!empty($settings['auto_backup_enabled'])) {
            $frequency = isset($settings['backup_frequency']) ? $settings['backup_frequency'] : 'daily';
            $hook = 'abr_' . $frequency . '_backup';

            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $frequency, $hook);
            }
        }
    }

    public function run_scheduled_backup() {
        $backup = new ABR_Backup();
        $result = $backup->create_full_backup();

        // Log the result
        if ($result['success']) {
            error_log('ABR: Scheduled backup completed successfully - ' . $result['file']);
        } else {
            error_log('ABR: Scheduled backup failed - ' . $result['message']);
        }

        // Clean up old backups
        $this->cleanup_old_backups();
    }

    private function cleanup_old_backups() {
        $settings = get_option('abr_settings', array());
        $max_backups = isset($settings['max_backups']) ? intval($settings['max_backups']) : 10;

        $files = glob(ABR_BACKUP_DIR . '*.zip');
        if (count($files) > $max_backups) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });

            // Delete oldest files
            $files_to_delete = array_slice($files, 0, count($files) - $max_backups);
            foreach ($files_to_delete as $file) {
                unlink($file);
            }
        }
    }

    public function ajax_create_backup() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', ABR_TEXT_DOMAIN));
        }

        // Check if backup is already in progress
        if (get_transient('abr_backup_progress')) {
            wp_send_json_error(__('Another backup is already in progress. Please wait for it to complete.', ABR_TEXT_DOMAIN));
        }

        // Increase time and memory limits for large backups
        @ini_set('max_execution_time', 300); // 5 minutes
        @ini_set('memory_limit', '512M');

        // Set progress to 0
        set_transient('abr_backup_progress', array('step' => 'starting', 'percent' => 0, 'message' => 'Initializing backup...'), 300);

        try {
            $backup = new ABR_Backup();
            $result = $backup->create_full_backup();

            // Don't clear progress here - let the scheduled action handle it
            // delete_transient('abr_backup_progress');

            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                // Clear progress on failure
                delete_transient('abr_backup_progress');
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            // Clear progress on exception
            delete_transient('abr_backup_progress');
            error_log('ABR AJAX Backup Error: ' . $e->getMessage());
            wp_send_json_error(__('Backup failed: ', ABR_TEXT_DOMAIN) . $e->getMessage());
        }
    }

    public function ajax_backup_progress() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Security check failed');
        }

        $progress = get_transient('abr_backup_progress');
        if (!$progress) {
            $progress = array('step' => 'idle', 'percent' => 0, 'message' => 'No backup in progress');
        }

        wp_send_json_success($progress);
    }

    public function clear_backup_progress() {
        delete_transient('abr_backup_progress');
        delete_transient('abr_restore_progress');
    }

    public function ajax_restore_backup() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', ABR_TEXT_DOMAIN));
        }

        // Check if restore is already in progress
        if (get_transient('abr_restore_progress')) {
            wp_send_json_error(__('Another restore is already in progress. Please wait for it to complete.', ABR_TEXT_DOMAIN));
        }

        $backup_file = sanitize_file_name($_POST['backup_file']);
        if (empty($backup_file) || !preg_match('/^[a-zA-Z0-9\-_.]+\.zip$/', $backup_file)) {
            wp_send_json_error(__('Invalid backup file name.', ABR_TEXT_DOMAIN));
        }

        // Verify backup file exists and is in backup directory
        $backup_path = ABR_BACKUP_DIR . $backup_file;
        if (!file_exists($backup_path) || !is_readable($backup_path)) {
            wp_send_json_error(__('Backup file not found or not readable.', ABR_TEXT_DOMAIN));
        }

        // Set initial progress
        set_transient('abr_restore_progress', array('step' => 'starting', 'percent' => 0, 'message' => 'Initializing restore...'), 300);

        $restore = new ABR_Restore();
        $result = $restore->restore_backup($backup_file);

        // Clear progress
        delete_transient('abr_restore_progress');

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    public function ajax_restore_progress() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Security check failed');
        }

        $progress = get_transient('abr_restore_progress');
        if (!$progress) {
            $progress = array('step' => 'idle', 'percent' => 0, 'message' => 'No restore in progress');
        }

        wp_send_json_success($progress);
    }

    public function ajax_refresh_data() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Security check failed');
        }

        $backup_files = abr_get_backup_files();
        $backup_dir_size = abr_get_backup_dir_size();

        // Generate backup list HTML
        ob_start();
        if (empty($backup_files)) {
            echo '<tr><td colspan="4" class="no-backups-message">' . __('No backup files found. Create your first backup using the button above.', ABR_TEXT_DOMAIN) . '</td></tr>';
        } else {
            foreach ($backup_files as $backup) {
                if (isset($backup['name']) && isset($backup['formatted_date']) && isset($backup['formatted_size'])) {
                    echo '<tr>';
                    echo '<td><strong>' . esc_html($backup['name']) . '</strong></td>';
                    echo '<td>' . esc_html($backup['formatted_date']) . '</td>';
                    echo '<td>' . esc_html($backup['formatted_size']) . '</td>';
                    echo '<td>';
                    echo '<button type="button" class="button button-secondary abr-restore-btn" data-backup-file="' . esc_attr($backup['name']) . '">' . __('Restore', ABR_TEXT_DOMAIN) . '</button> ';
                    echo '<button type="button" class="button button-link-delete abr-delete-btn" data-backup-file="' . esc_attr($backup['name']) . '">' . __('Delete', ABR_TEXT_DOMAIN) . '</button>';
                    echo '</td>';
                    echo '</tr>';
                }
            }
        }
        $backup_list_html = ob_get_clean();

        wp_send_json_success(array(
            'backup_count' => count($backup_files),
            'total_size' => size_format($backup_dir_size),
            'backup_list_html' => $backup_list_html
        ));
    }

    public function ajax_delete_backup() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', ABR_TEXT_DOMAIN));
        }

        $backup_file = sanitize_file_name($_POST['backup_file']);
        if (empty($backup_file) || !preg_match('/^[a-zA-Z0-9\-_.]+\.zip$/', $backup_file)) {
            wp_send_json_error(__('Invalid backup file name.', ABR_TEXT_DOMAIN));
        }

        $file_path = ABR_BACKUP_DIR . $backup_file;

        // Security check: ensure file is in backup directory
        if (dirname($file_path) !== rtrim(ABR_BACKUP_DIR, '/')) {
            wp_send_json_error(__('Invalid file path.', ABR_TEXT_DOMAIN));
        }

        if (!file_exists($file_path)) {
            wp_send_json_error(__('Backup file not found.', ABR_TEXT_DOMAIN));
        }

        if (unlink($file_path)) {
            wp_send_json_success(array(
                'message' => __('Backup deleted successfully.', ABR_TEXT_DOMAIN)
            ));
        } else {
            wp_send_json_error(__('Failed to delete backup file. Please check file permissions.', ABR_TEXT_DOMAIN));
        }
    }

    public function ajax_test_email() {
        // Verify nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'abr_ajax_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error(__('Security check failed. Please refresh the page and try again.', ABR_TEXT_DOMAIN));
        }

        $settings = get_option('abr_settings', array());

        if (empty($settings['notification_email'])) {
            wp_send_json_error(__('Please set a notification email address before testing.', ABR_TEXT_DOMAIN));
        }

        $to = $settings['notification_email'];
        $subject = sprintf(__('🧪 Test Email - Auto Backup & Restore Pro - %s', ABR_TEXT_DOMAIN), get_bloginfo('name'));

        $message = sprintf(__('Hello,

This is a test email from Auto Backup & Restore Pro plugin.

Site: %s (%s)
Test Date: %s
WordPress Version: %s
Plugin Version: %s

If you received this email, your email configuration is working correctly!

Best regards,
Auto Backup & Restore Pro', ABR_TEXT_DOMAIN),
            get_bloginfo('name'),
            get_site_url(),
            date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
            get_bloginfo('version'),
            ABR_VERSION
        );

        $email_format = isset($settings['email_format']) ? $settings['email_format'] : 'html';

        if ($email_format === 'html') {
            $message = $this->format_test_email_html($message, $subject);
            $headers = array('Content-Type: text/html; charset=UTF-8');
        } else {
            $headers = array('Content-Type: text/plain; charset=UTF-8');
        }

        // Log email attempt
        error_log('ABR: Testing email to: ' . $to);

        $mail_sent = wp_mail($to, $subject, $message, $headers);

        if ($mail_sent) {
            wp_send_json_success(array(
                'message' => __('Test email sent successfully! Check your inbox.', ABR_TEXT_DOMAIN)
            ));
        } else {
            // Check if localhost and save to file
            if ($this->is_localhost()) {
                $this->save_test_email_to_file($to, $subject, $message);
                wp_send_json_success(array(
                    'message' => __('Email saved to file for localhost testing. Check: /wp-content/uploads/abr-backups/emails/', ABR_TEXT_DOMAIN)
                ));
            } else {
                wp_send_json_error(__('Failed to send test email. Please check your email configuration.', ABR_TEXT_DOMAIN));
            }
        }
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

    private function format_test_email_html($text_message, $subject) {
        $site_name = get_bloginfo('name');

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
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; }
                .test-badge { display: inline-block; background: #10b981; color: white; padding: 8px 16px; border-radius: 20px; font-weight: bold; margin-bottom: 20px; }
                .details { background: #f8fafc; padding: 20px; border-radius: 6px; border-left: 4px solid #10b981; margin: 20px 0; }
                .footer { background: #f8fafc; padding: 20px; text-align: center; color: #666; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🧪 ' . esc_html($subject) . '</h1>
                </div>
                <div class="content">
                    <div class="test-badge">EMAIL TEST SUCCESS</div>
                    <div class="details">' . nl2br(esc_html($text_message)) . '</div>
                </div>
                <div class="footer">
                    <p>This is a test email from Auto Backup & Restore Pro on ' . esc_html($site_name) . '</p>
                    <p><a href="' . get_site_url() . '">' . get_site_url() . '</a></p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    private function save_test_email_to_file($to, $subject, $message) {
        $emails_dir = ABR_BACKUP_DIR . 'emails/';

        if (!file_exists($emails_dir)) {
            wp_mkdir_p($emails_dir);
        }

        $timestamp = date('Y-m-d_H-i-s');
        $filename = "email-test-{$timestamp}.html";
        $filepath = $emails_dir . $filename;

        $email_content = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>{$subject}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
        .email-header { background: #e1f5fe; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #0288d1; }
        .email-body { background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='email-header'>
        <h2>🧪 Email Test - Auto Backup & Restore Pro</h2>
        <p><strong>To:</strong> {$to}</p>
        <p><strong>Subject:</strong> {$subject}</p>
        <p><strong>Test Date:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Status:</strong> <span style='color: green;'>TEST EMAIL</span></p>
    </div>
    <div class='email-body'>
        {$message}
    </div>
    <div style='margin-top: 20px; padding: 15px; background: #fffbf0; border: 1px solid #f0ad4e; border-radius: 5px;'>
        <strong>⚠️ Localhost Notice:</strong> This email was saved to file because mail sending is not available on localhost.
        In production, this would be sent as a real email to: <strong>{$to}</strong>
        <br><br>
        <strong>File Location:</strong> {$filepath}
    </div>
</body>
</html>";

        file_put_contents($filepath, $email_content);
        error_log("ABR: Test email saved to file: {$filepath}");
    }
}

// Initialize the plugin
AutoBackupRestore::get_instance();
