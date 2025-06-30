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
            // Ensure backup directory exists
            if (!file_exists($this->backup_dir)) {
                wp_mkdir_p($this->backup_dir);
            }
            
            $timestamp = date('Y-m-d_H-i-s');
            $backup_file = $this->backup_dir . 'full-backup-' . $timestamp . '.zip';
            
            $zip = new ZipArchive();
            if ($zip->open($backup_file, ZipArchive::CREATE) !== TRUE) {
                return array('success' => false, 'message' => __('Could not create backup file.', ABR_TEXT_DOMAIN));
            }
            
            $settings = get_option('abr_settings', array());
            $backup_types = isset($settings['backup_types']) ? $settings['backup_types'] : array('plugins', 'themes', 'uploads', 'database');
            
            // Backup plugins
            if (in_array('plugins', $backup_types)) {
                $this->add_directory_to_zip($zip, WP_PLUGIN_DIR, 'plugins/');
            }
            
            // Backup themes
            if (in_array('themes', $backup_types)) {
                $this->add_directory_to_zip($zip, get_theme_root(), 'themes/');
            }
            
            // Backup uploads
            if (in_array('uploads', $backup_types)) {
                $upload_dir = wp_upload_dir();
                $this->add_directory_to_zip($zip, $upload_dir['basedir'], 'uploads/');
            }
            
            // Backup database
            if (in_array('database', $backup_types)) {
                $db_backup = $this->export_database();
                if ($db_backup) {
                    $zip->addFromString('database.sql', $db_backup);
                }
            }
            
            // Add site info
            $site_info = $this->get_site_info();
            $zip->addFromString('site-info.json', json_encode($site_info, JSON_PRETTY_PRINT));
            
            $zip->close();
            
            return array(
                'success' => true, 
                'message' => __('Backup created successfully!', ABR_TEXT_DOMAIN),
                'file' => basename($backup_file)
            );
            
        } catch (Exception $e) {
            error_log('ABR Backup Error: ' . $e->getMessage());
            return array('success' => false, 'message' => $e->getMessage());
        }
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