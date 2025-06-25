<?php
/*
Plugin Name: Auto-Backup & One-Click Restore
Plugin URI: https://devsopu.com
Description: Easily backup and restore your WordPress site including plugins, themes, uploads, and database. This plugin provides a simple and efficient way to create backups and restore them with just one click.
Version: 1.0.0
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
define('ABR_VERSION', '1.0.0');
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
        
        // Cron hooks
        add_action('abr_daily_backup', array($this, 'run_scheduled_backup'));
        add_action('abr_weekly_backup', array($this, 'run_scheduled_backup'));
        add_action('abr_monthly_backup', array($this, 'run_scheduled_backup'));
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
            wp_mkdir_p(ABR_BACKUP_DIR);
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
            __('Auto Backup & Restore', ABR_TEXT_DOMAIN),
            __('Backup & Restore', ABR_TEXT_DOMAIN),
            'manage_options',
            'abr-backup',
            array($this, 'admin_page'),
            'dashicons-backup',
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
                    'confirm_restore' => __('Are you sure you want to restore this backup? This will overwrite current files.', ABR_TEXT_DOMAIN),
                    'confirm_delete' => __('Are you sure you want to delete this backup?', ABR_TEXT_DOMAIN)
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
            return;
        }
        
        // Handle backup creation
        if (isset($_POST['abr_create_backup']) && wp_verify_nonce($_POST['abr_nonce'], 'abr_backup_action')) {
            $backup = new ABR_Backup();
            $result = $backup->create_full_backup();
            
            if ($result['success']) {
                add_settings_error('abr_messages', 'backup_success', __('Backup created successfully!', ABR_TEXT_DOMAIN), 'success');
            } else {
                add_settings_error('abr_messages', 'backup_error', $result['message'], 'error');
            }
        }
        
        // Handle backup restoration
        if (isset($_GET['abr_restore']) && wp_verify_nonce($_GET['_wpnonce'], 'abr_restore_backup')) {
            $backup_file = sanitize_file_name($_GET['abr_restore']);
            $restore = new ABR_Restore();
            $result = $restore->restore_backup($backup_file);
            
            $message_type = $result['success'] ? 'success' : 'error';
            wp_redirect(add_query_arg(array(
                'page' => 'abr-backup',
                'message' => $message_type,
                'details' => urlencode($result['message'])
            ), admin_url('admin.php')));
            exit;
        }
        
        // Handle backup deletion
        if (isset($_GET['abr_delete']) && wp_verify_nonce($_GET['_wpnonce'], 'abr_delete_backup')) {
            $backup_file = sanitize_file_name($_GET['abr_delete']);
            $file_path = ABR_BACKUP_DIR . $backup_file;
            
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
        }
        
        // Handle settings save
        if (isset($_POST['abr_save_settings']) && wp_verify_nonce($_POST['abr_settings_nonce'], 'abr_settings_action')) {
            $settings = new ABR_Settings();
            $settings->save_settings($_POST);
            
            add_settings_error('abr_messages', 'settings_saved', __('Settings saved successfully!', ABR_TEXT_DOMAIN), 'success');
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
        $backup->create_full_backup();
        
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
}

// Initialize the plugin
AutoBackupRestore::get_instance();