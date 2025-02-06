<?php
/*
Plugin Name: Auto-Backup & One-Click Restore
Plugin URI: https://yourwebsite.com
Description: Backup and restore the WordPress plugin directory easily.
Version: 1.0
Author: Your Name
Author URI: https://yourwebsite.com
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ABR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ABR_BACKUP_DIR', WP_CONTENT_DIR . '/uploads/backups/');

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



function abr_enqueue_scripts($hook) {
    if ($hook === 'toplevel_page_abr_backup') {
        wp_enqueue_script('abr-backup-js', plugin_dir_url(__FILE__) . 'assets/js/backup.js', array('jquery'), null, true);

        // Correctly passing AJAX URL
        $ajax_data = array('ajaxurl' => admin_url('admin-ajax.php'));
        wp_localize_script('abr-backup-js', 'abr_ajax_object', $ajax_data);
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
