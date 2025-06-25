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
            $backup_path = $this->backup_dir . $backup_file;
            
            if (!file_exists($backup_path)) {
                return array('success' => false, 'message' => __('Backup file not found.', ABR_TEXT_DOMAIN));
            }
            
            $zip = new ZipArchive();
            if ($zip->open($backup_path) !== TRUE) {
                return array('success' => false, 'message' => __('Could not open backup file.', ABR_TEXT_DOMAIN));
            }
            
            // Create temporary extraction directory
            $temp_dir = $this->backup_dir . 'temp_restore_' . time() . '/';
            wp_mkdir_p($temp_dir);
            
            // Extract backup
            if (!$zip->extractTo($temp_dir)) {
                $zip->close();
                $this->cleanup_temp_dir($temp_dir);
                return array('success' => false, 'message' => __('Failed to extract backup file.', ABR_TEXT_DOMAIN));
            }
            
            $zip->close();
            
            $restored_items = array();
            
            // Restore plugins
            if (is_dir($temp_dir . 'plugins')) {
                if ($this->restore_directory($temp_dir . 'plugins', WP_PLUGIN_DIR)) {
                    $restored_items[] = __('Plugins', ABR_TEXT_DOMAIN);
                }
            }
            
            // Restore themes
            if (is_dir($temp_dir . 'themes')) {
                if ($this->restore_directory($temp_dir . 'themes', get_theme_root())) {
                    $restored_items[] = __('Themes', ABR_TEXT_DOMAIN);
                }
            }
            
            // Restore uploads
            if (is_dir($temp_dir . 'uploads')) {
                $upload_dir = wp_upload_dir();
                if ($this->restore_directory($temp_dir . 'uploads', $upload_dir['basedir'])) {
                    $restored_items[] = __('Uploads', ABR_TEXT_DOMAIN);
                }
            }
            
            // Restore database
            if (file_exists($temp_dir . 'database.sql')) {
                if ($this->restore_database($temp_dir . 'database.sql')) {
                    $restored_items[] = __('Database', ABR_TEXT_DOMAIN);
                }
            }
            
            // Cleanup
            $this->cleanup_temp_dir($temp_dir);
            
            if (empty($restored_items)) {
                return array('success' => false, 'message' => __('No items were restored from the backup.', ABR_TEXT_DOMAIN));
            }
            
            $message = sprintf(__('Successfully restored: %s', ABR_TEXT_DOMAIN), implode(', ', $restored_items));
            return array('success' => true, 'message' => $message);
            
        } catch (Exception $e) {
            error_log('ABR Restore Error: ' . $e->getMessage());
            return array('success' => false, 'message' => $e->getMessage());
        }
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
}