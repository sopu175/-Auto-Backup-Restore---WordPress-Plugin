<?php
/*
Plugin Name: Auto-Backup & One-Click Restore
Plugin URI: https://devsopu.com
Description: Easily backup and restore your WordPress plugin directory. This plugin provides a simple and efficient way to create backups of your WordPress plugin directory and restore them with just one click.
Version: 1.0
Author: Saif Islam
Author URI: https://devsopu.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ABR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABR_BACKUP_DIR', WP_CONTENT_DIR . '/uploads/backups/');
define('ABR_BACKUP_DIR_WP_CONTENT', WP_CONTENT_DIR );

// Ensure backup directory exists
if (!file_exists(ABR_BACKUP_DIR)) {
    if (!mkdir($concurrentDirectory = ABR_BACKUP_DIR, 0755, true) && !is_dir($concurrentDirectory)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
    }
}

// Include necessary files
require_once ABR_PLUGIN_DIR . 'includes/backup.php';
require_once ABR_PLUGIN_DIR . 'includes/restore.php';
require_once ABR_PLUGIN_DIR . 'includes/settings.php';
require_once ABR_PLUGIN_DIR . 'includes/delete.php';



// Enqueue scripts
function abr_enqueue_scripts($hook) {
    if ($hook === 'toplevel_page_abr_backup') {
        wp_enqueue_script('abr-backup-js', plugin_dir_url(__FILE__) . 'assets/js/backup.js', array('jquery'), null, true);
        wp_localize_script('abr-backup-js', 'abr_ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
    }
}
add_action('admin_enqueue_scripts', 'abr_enqueue_scripts');


// Admin menu hook
add_action('admin_menu', function() {
    add_menu_page('Auto Backup & Restore', 'Backup Restore', 'manage_options', 'abr_backup', 'abr_admin_page', 'dashicons-backup', 75);
    add_submenu_page('abr_backup', 'Settings', 'Settings', 'manage_options', 'abr_settings_backup', 'abr_settings_backup_page_content');
});

// Admin page
function abr_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions.'));
    }
    include ABR_PLUGIN_DIR . 'admin/admin-page.php';
}


// Handle backup, restore, and delete actions within the admin context
add_action('admin_init', function() {
    if (isset($_POST['abr_backup']) && check_admin_referer('abr_backup_action', 'abr_backup_nonce')) {
        abr_create_backup();
    }
    if (isset($_POST['abr_settings_backup']) && check_admin_referer('abr_backup_action', 'abr_backup_nonce')) {
        abr_settings_backup_page_content();
    }

    if (isset($_GET['abr_restore'])) {
        abr_restore_backup($_GET['abr_restore']);
    }

    if (isset($_GET['abr_delete'])) {
        abr_delete_backup($_GET['abr_delete']);
    }
});


// Activation hook
register_activation_hook(__FILE__, 'abr_activate_plugin');
function abr_activate_plugin() {
    // Code to run on plugin activation
    // For example, create necessary database tables or set default options
    if (!file_exists(ABR_BACKUP_DIR)) {
        if (!mkdir($concurrentDirectory = ABR_BACKUP_DIR, 0755, true) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'abr_deactivate_plugin');
function abr_deactivate_plugin() {
    // Code to run on plugin deactivation
    // Delete plugin options from wp_options table
    delete_option('abr_backup_options');

    // Delete the backups directory and its contents
    $backup_dir = ABR_BACKUP_DIR;
    if (file_exists($backup_dir)) {
        // Recursively delete the directory and its contents
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($backup_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($backup_dir);
    }
}