<?php
/**
 * Logger Class for Trentino Import Plugin
 * 
 * Comprehensive logging system for import operations, errors, and debugging.
 * Provides admin interface integration and file-based logging with rotation.
 * 
 * @package TrentinoImport
 * @version 1.0.0
 * @author Andrea Cianni - Novacom
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

/**
 * TrentinoImportLogger Class
 * 
 * Handles all logging operations for the plugin including:
 * - Import operations logging
 * - Error tracking and debugging
 * - Admin interface log viewing
 * - Log file rotation and cleanup
 * - Performance metrics
 */
class TrentinoImportLogger {
    
    /**
     * Log levels constants
     */
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    /**
     * Logger instance (Singleton)
     */
    private static $instance = null;
    
    /**
     * Log directory path
     */
    private $log_dir;
    
    /**
     * Current log file path
     */
    private $current_log_file;
    
    /**
     * Maximum log file size (MB)
     */
    private $max_file_size = 5;
    
    /**
     * Maximum number of log files to keep
     */
    private $max_files = 10;
    
    /**
     * Current import session ID
     */
    private $session_id;
    
    /**
     * Logger configuration
     */
    private $config;
    
    /**
     * Get logger instance (Singleton)
     *
     * @return TrentinoImportLogger
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor (Singleton pattern)
     */
    private function __construct() {
        $this->init_logger();
    }
    
    /**
     * Initialize logger system
     */
    private function init_logger() {
        // Set log directory
        $this->log_dir = TRENTINO_IMPORT_PLUGIN_DIR . 'logs/';
        
        // Create logs directory if it doesn't exist
        if (!file_exists($this->log_dir)) {
            wp_mkdir_p($this->log_dir);
        }
        
        // Set current log file
        $this->current_log_file = $this->log_dir . 'trentino-import-' . date('Y-m-d') . '.log';
        
        // Generate session ID for current import
        $this->session_id = uniqid('ti_', true);
        
        // Load configuration
        $this->load_config();
        
        // Setup log rotation if needed
        $this->check_log_rotation();
    }
    
    /**
     * Load logger configuration
     */
    private function load_config() {
        $defaults = [
            'enabled' => true,
            'level' => self::LEVEL_INFO,
            'max_file_size' => 5, // MB
            'max_files' => 10,
            'include_context' => true,
            'performance_logging' => true
        ];
        
        $this->config = get_option('trentino_import_logger_config', $defaults);
    }
    
    /**
     * Log a message with specified level
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @return bool Success status
     */
    public function log($level, $message, $context = []) {
        // Check if logging is enabled
        if (!$this->config['enabled']) {
            return false;
        }
        
        // Check log level filtering
        if (!$this->should_log($level)) {
            return false;
        }
        
        // Format log entry
        $log_entry = $this->format_log_entry($level, $message, $context);
        
        // Write to file
        $success = $this->write_to_file($log_entry);
        
        // Also log critical errors to WordPress debug log
        if ($level === self::LEVEL_CRITICAL || $level === self::LEVEL_ERROR) {
            error_log("Trentino Import [{$level}]: {$message}");
        }
        
        return $success;
    }
    
    /**
     * Log debug message
     *
     * @param string $message Debug message
     * @param array $context Additional context
     * @return bool
     */
    public function debug($message, $context = []) {
        return $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     *
     * @param string $message Info message
     * @param array $context Additional context
     * @return bool
     */
    public function info($message, $context = []) {
        return $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     *
     * @param string $message Warning message
     * @param array $context Additional context
     * @return bool
     */
    public function warning($message, $context = []) {
        return $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @return bool
     */
    public function error($message, $context = []) {
        return $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     *
     * @param string $message Critical message
     * @param array $context Additional context
     * @return bool
     */
    public function critical($message, $context = []) {
        return $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Start import session logging
     *
     * @param string $import_type Type of import (manual/automatic)
     * @return string Session ID
     */
    public function start_import_session($import_type = 'manual') {
        $this->session_id = uniqid('ti_', true);
        
        $this->info("=== IMPORT SESSION STARTED ===", [
            'session_id' => $this->session_id,
            'import_type' => $import_type,
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit')
        ]);
        
        return $this->session_id;
    }
    
    /**
     * End import session logging
     *
     * @param array $summary Import summary data
     * @return bool
     */
    public function end_import_session($summary = []) {
        $default_summary = [
            'session_id' => $this->session_id,
            'duration' => 0,
            'properties_processed' => 0,
            'properties_imported' => 0,
            'properties_updated' => 0,
            'properties_skipped' => 0,
            'errors_count' => 0,
            'memory_peak' => memory_get_peak_usage(true)
        ];
        
        $summary = array_merge($default_summary, $summary);
        
        $this->info("=== IMPORT SESSION COMPLETED ===", $summary);
        
        return true;
    }
    
    /**
     * Log import step progress
     *
     * @param string $step Step name
     * @param string $status Step status (started/completed/failed)
     * @param array $data Step data
     * @return bool
     */
    public function log_import_step($step, $status, $data = []) {
        $context = array_merge([
            'session_id' => $this->session_id,
            'step' => $step,
            'status' => $status,
            'timestamp' => current_time('mysql')
        ], $data);
        
        $message = "Import Step: {$step} - {$status}";
        
        if ($status === 'failed') {
            return $this->error($message, $context);
        } else {
            return $this->info($message, $context);
        }
    }
    
    /**
     * Get recent log entries for admin interface
     *
     * @param int $limit Number of entries to retrieve
     * @param string $level_filter Filter by log level
     * @return array Log entries
     */
    public function get_recent_logs($limit = 50, $level_filter = null) {
        if (!file_exists($this->current_log_file)) {
            return [];
        }
        
        $logs = [];
        $file = new SplFileObject($this->current_log_file);
        $file->seek(PHP_INT_MAX);
        $total_lines = $file->key();
        
        // Read from the end
        $start_line = max(0, $total_lines - $limit);
        $file->seek($start_line);
        
        while (!$file->eof() && count($logs) < $limit) {
            $line = trim($file->fgets());
            if (empty($line)) continue;
            
            $parsed = $this->parse_log_line($line);
            if ($parsed && ($level_filter === null || $parsed['level'] === $level_filter)) {
                $logs[] = $parsed;
            }
        }
        
        return array_reverse($logs); // Most recent first
    }
    
    /**
     * Get log file list for admin interface
     *
     * @return array List of log files with metadata
     */
    public function get_log_files() {
        $files = glob($this->log_dir . '*.log');
        $log_files = [];
        
        foreach ($files as $file) {
            $log_files[] = [
                'filename' => basename($file),
                'filepath' => $file,
                'size' => filesize($file),
                'size_formatted' => size_format(filesize($file)),
                'modified' => filemtime($file),
                'modified_formatted' => date('Y-m-d H:i:s', filemtime($file))
            ];
        }
        
        // Sort by modification time (newest first)
        usort($log_files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        
        return $log_files;
    }
    
    /**
     * Clear all log files
     *
     * @return bool Success status
     */
    public function clear_logs() {
        $files = glob($this->log_dir . '*.log');
        $success = true;
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                $success = false;
            }
        }
        
        // Log the clear action
        if ($success) {
            $this->info("Log files cleared by user", [
                'user_id' => get_current_user_id(),
                'files_count' => count($files)
            ]);
        }
        
        return $success;
    }
    
    /**
     * Format log entry
     *
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Context data
     * @return string Formatted log entry
     */
    private function format_log_entry($level, $message, $context) {
        $timestamp = current_time('Y-m-d H:i:s');
        $memory = memory_get_usage(true);
        $memory_formatted = size_format($memory);
        
        $entry = "[{$timestamp}] [{$level}] [{$memory_formatted}]";
        
        if (!empty($this->session_id)) {
            $entry .= " [Session: {$this->session_id}]";
        }
        
        $entry .= " {$message}";
        
        // Add context if enabled and present
        if ($this->config['include_context'] && !empty($context)) {
            $entry .= " | Context: " . wp_json_encode($context, JSON_UNESCAPED_SLASHES);
        }
        
        return $entry . PHP_EOL;
    }
    
    /**
     * Check if message should be logged based on level
     *
     * @param string $level Message level
     * @return bool Should log
     */
    private function should_log($level) {
        $levels = [
            self::LEVEL_DEBUG => 0,
            self::LEVEL_INFO => 1,
            self::LEVEL_WARNING => 2,
            self::LEVEL_ERROR => 3,
            self::LEVEL_CRITICAL => 4
        ];
        
        $current_level = $levels[$this->config['level']] ?? 1;
        $message_level = $levels[$level] ?? 1;
        
        return $message_level >= $current_level;
    }
    
    /**
     * Write log entry to file
     *
     * @param string $entry Log entry
     * @return bool Success status
     */
    private function write_to_file($entry) {
        return file_put_contents($this->current_log_file, $entry, FILE_APPEND | LOCK_EX) !== false;
    }
    
    /**
     * Parse a log line into components
     *
     * @param string $line Log line
     * @return array|null Parsed components
     */
    private function parse_log_line($line) {
        // Pattern: [timestamp] [level] [memory] [session] message | context
        $pattern = '/^\[([^\]]+)\] \[([^\]]+)\] \[([^\]]+)\](?:\s\[Session: ([^\]]+)\])?\s(.+?)(?:\s\|\sContext:\s(.+))?$/';
        
        if (preg_match($pattern, $line, $matches)) {
            return [
                'timestamp' => $matches[1],
                'level' => $matches[2],
                'memory' => $matches[3],
                'session_id' => $matches[4] ?? null,
                'message' => $matches[5],
                'context' => isset($matches[6]) ? json_decode($matches[6], true) : null,
                'raw' => $line
            ];
        }
        
        return null;
    }
    
    /**
     * Check and perform log rotation if needed
     */
    private function check_log_rotation() {
        if (!file_exists($this->current_log_file)) {
            return;
        }
        
        // Check file size
        $file_size_mb = filesize($this->current_log_file) / (1024 * 1024);
        
        if ($file_size_mb > $this->max_file_size) {
            $this->rotate_logs();
        }
        
        // Clean old log files
        $this->cleanup_old_logs();
    }
    
    /**
     * Rotate log files
     */
    private function rotate_logs() {
        $backup_file = $this->log_dir . 'trentino-import-' . date('Y-m-d-H-i-s') . '.log';
        
        if (rename($this->current_log_file, $backup_file)) {
            $this->info("Log file rotated", [
                'old_file' => basename($backup_file),
                'new_file' => basename($this->current_log_file)
            ]);
        }
    }
    
    /**
     * Clean up old log files
     */
    private function cleanup_old_logs() {
        $files = glob($this->log_dir . '*.log');
        
        if (count($files) > $this->max_files) {
            // Sort by modification time (oldest first)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Remove excess files
            $to_remove = array_slice($files, 0, count($files) - $this->max_files);
            
            foreach ($to_remove as $file) {
                unlink($file);
            }
        }
    }
}

/**
 * Helper function to get logger instance
 *
 * @return TrentinoImportLogger
 */
function trentino_import_logger() {
    return TrentinoImportLogger::get_instance();
}

// End of file
