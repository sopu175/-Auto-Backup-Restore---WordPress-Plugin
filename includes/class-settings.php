<?php
if (!defined('ABSPATH')) {
    exit;
}

class ABR_Settings {
    
    private $settings_key = 'abr_settings';
    
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    public function register_settings() {
        register_setting('abr_settings_group', $this->settings_key, array($this, 'sanitize_settings'));
    }
    
    public function get_settings() {
        $defaults = array(
            'auto_backup_enabled' => false,
            'backup_frequency' => 'daily',
            'max_backups' => 10,
            'backup_types' => array('plugins', 'themes', 'uploads', 'database'),
            'email_notifications' => false,
            'notification_email' => get_option('admin_email')
        );
        
        return wp_parse_args(get_option($this->settings_key, array()), $defaults);
    }
    
    public function save_settings($post_data) {
        $settings = array();
        
        $settings['auto_backup_enabled'] = isset($post_data['auto_backup_enabled']) ? true : false;
        $settings['backup_frequency'] = sanitize_text_field($post_data['backup_frequency']);
        $settings['max_backups'] = intval($post_data['max_backups']);
        $settings['backup_types'] = isset($post_data['backup_types']) ? array_map('sanitize_text_field', $post_data['backup_types']) : array();
        $settings['email_notifications'] = isset($post_data['email_notifications']) ? true : false;
        $settings['notification_email'] = sanitize_email($post_data['notification_email']);
        
        update_option($this->settings_key, $settings);
        
        // Update scheduled backups
        $this->update_scheduled_backups($settings);
        
        return true;
    }
    
    public function sanitize_settings($settings) {
        $sanitized = array();
        
        if (isset($settings['auto_backup_enabled'])) {
            $sanitized['auto_backup_enabled'] = (bool) $settings['auto_backup_enabled'];
        }
        
        if (isset($settings['backup_frequency'])) {
            $allowed_frequencies = array('daily', 'weekly', 'monthly');
            $sanitized['backup_frequency'] = in_array($settings['backup_frequency'], $allowed_frequencies) ? $settings['backup_frequency'] : 'daily';
        }
        
        if (isset($settings['max_backups'])) {
            $sanitized['max_backups'] = max(1, min(50, intval($settings['max_backups'])));
        }
        
        if (isset($settings['backup_types']) && is_array($settings['backup_types'])) {
            $allowed_types = array('plugins', 'themes', 'uploads', 'database');
            $sanitized['backup_types'] = array_intersect($settings['backup_types'], $allowed_types);
        }
        
        if (isset($settings['email_notifications'])) {
            $sanitized['email_notifications'] = (bool) $settings['email_notifications'];
        }
        
        if (isset($settings['notification_email'])) {
            $sanitized['notification_email'] = sanitize_email($settings['notification_email']);
        }
        
        return $sanitized;
    }
    
    private function update_scheduled_backups($settings) {
        // Clear existing schedules
        wp_clear_scheduled_hook('abr_daily_backup');
        wp_clear_scheduled_hook('abr_weekly_backup');
        wp_clear_scheduled_hook('abr_monthly_backup');
        
        // Schedule new backup if enabled
        if ($settings['auto_backup_enabled']) {
            $hook = 'abr_' . $settings['backup_frequency'] . '_backup';
            
            if (!wp_next_scheduled($hook)) {
                wp_schedule_event(time(), $settings['backup_frequency'], $hook);
            }
        }
    }
}