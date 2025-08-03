<?php
/**
 * XML Downloader Class for Trentino Import Plugin
 * 
 * Handles secure download and authentication with GestionaleImmobiliare.it
 * XML feed system. Manages credentials, authentication, file validation,
 * and provides comprehensive error handling.
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
 * TrentinoXmlDownloader Class
 * 
 * Manages XML download operations from GestionaleImmobiliare.it including:
 * - Secure authentication with username/password
 * - Download progress tracking
 * - Archive extraction (.tar.gz handling)
 * - File validation and verification
 * - Retry logic for network issues
 * - Comprehensive logging integration
 */
class TrentinoXmlDownloader {
    
    /**
     * Logger instance
     */
    private $logger;
    
    /**
     * Download configuration
     */
    private $config;
    
    /**
     * GestionaleImmobiliare.it settings
     */
    private $gestionale_settings;
    
    /**
     * Download statistics
     */
    private $stats;
    
    /**
     * Temporary directory for downloads
     */
    private $temp_dir;
    
    /**
     * Constructor
     *
     * @param TrentinoImportLogger $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger ?: trentino_import_logger();
        $this->init_downloader();
    }
    
    /**
     * Initialize downloader
     */
    private function init_downloader() {
        // Load configuration
        $this->load_config();
        
        // Setup temporary directory
        $this->setup_temp_directory();
        
        // Initialize statistics
        $this->reset_stats();
        
        $this->logger->debug('XML Downloader initialized', [
            'temp_dir' => $this->temp_dir,
            'timeout' => $this->config['timeout'],
            'max_retries' => $this->config['max_retries']
        ]);
    }
    
    /**
     * Load downloader configuration
     */
    private function load_config() {
        $defaults = [
            'timeout' => 300, // 5 minutes
            'max_retries' => 3,
            'retry_delay' => 5, // seconds
            'chunk_size' => 8192, // bytes
            'verify_ssl' => true,
            'user_agent' => 'Trentino Import Plugin/1.0',
            'max_file_size' => 50 * 1024 * 1024, // 50MB
        ];
        
        $this->config = get_option('trentino_import_downloader_config', $defaults);
        
        // Load GestionaleImmobiliare.it settings
        $this->gestionale_settings = [
            'base_url' => 'https://www.gestionaleimmobiliare.it/export/xml/trentinoimmobiliare_it/',
            'filename' => 'export_gi_full_merge_multilevel.xml.tar.gz',
            'username' => get_option('trentino_import_gestionale_username', ''),
            'password' => get_option('trentino_import_gestionale_password', '')
        ];
    }
    
    /**
     * Setup temporary directory
     */
    private function setup_temp_directory() {
        $upload_dir = wp_upload_dir();
        $this->temp_dir = $upload_dir['basedir'] . '/trentino-import-temp/';
        
        if (!file_exists($this->temp_dir)) {
            wp_mkdir_p($this->temp_dir);
        }
    }
    
    /**
     * Reset download statistics
     */
    private function reset_stats() {
        $this->stats = [
            'start_time' => 0,
            'end_time' => 0,
            'duration' => 0,
            'file_size' => 0,
            'download_speed' => 0,
            'retries' => 0,
            'success' => false
        ];
    }
    
    /**
     * Download XML file from GestionaleImmobiliare.it
     *
     * @param bool $force_download Force download even if recent file exists
     * @return array Download result with success status and file path
     */
    public function download_xml($force_download = false) {
        $this->logger->info('Starting XML download from GestionaleImmobiliare.it', [
            'force_download' => $force_download,
            'username' => $this->gestionale_settings['username'] ? 'configured' : 'missing'
        ]);
        
        // Validate credentials
        if (!$this->validate_credentials()) {
            return $this->error_result('Invalid or missing credentials');
        }
        
        // Check for existing recent file
        if (!$force_download && $this->has_recent_file()) {
            $existing_file = $this->get_existing_file_path();
            $this->logger->info('Using existing recent XML file', [
                'file_path' => $existing_file,
                'file_age_hours' => $this->get_file_age_hours($existing_file)
            ]);
            
            return $this->success_result($existing_file, 'existing');
        }
        
        // Start download process
        $this->reset_stats();
        $this->stats['start_time'] = microtime(true);
        
        $download_result = $this->perform_download();
        
        $this->stats['end_time'] = microtime(true);
        $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];
        
        if ($download_result['success']) {
            $this->stats['success'] = true;
            $this->calculate_download_speed();
            
            $this->logger->info('XML download completed successfully', [
                'file_path' => $download_result['file_path'],
                'file_size' => size_format($this->stats['file_size']),
                'duration' => round($this->stats['duration'], 2) . 's',
                'speed' => size_format($this->stats['download_speed']) . '/s',
                'retries' => $this->stats['retries']
            ]);
            
            return $download_result;
        } else {
            $this->logger->error('XML download failed', [
                'error' => $download_result['error'],
                'retries' => $this->stats['retries'],
                'duration' => round($this->stats['duration'], 2) . 's'
            ]);
            
            return $download_result;
        }
    }
    
    /**
     * Validate GestionaleImmobiliare.it credentials
     *
     * @return bool Credentials are valid
     */
    private function validate_credentials() {
        if (empty($this->gestionale_settings['username']) || empty($this->gestionale_settings['password'])) {
            $this->logger->error('Missing GestionaleImmobiliare.it credentials', [
                'username_set' => !empty($this->gestionale_settings['username']),
                'password_set' => !empty($this->gestionale_settings['password'])
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if recent XML file exists
     *
     * @return bool Recent file exists
     */
    private function has_recent_file() {
        $file_path = $this->get_existing_file_path();
        
        if (!file_exists($file_path)) {
            return false;
        }
        
        $file_age_hours = $this->get_file_age_hours($file_path);
        $max_age_hours = 12; // Consider files older than 12 hours as stale
        
        return $file_age_hours < $max_age_hours;
    }
    
    /**
     * Get existing file path
     *
     * @return string File path
     */
    private function get_existing_file_path() {
        return $this->temp_dir . 'latest_' . $this->gestionale_settings['filename'];
    }
    
    /**
     * Get file age in hours
     *
     * @param string $file_path File path
     * @return float File age in hours
     */
    private function get_file_age_hours($file_path) {
        if (!file_exists($file_path)) {
            return PHP_FLOAT_MAX;
        }
        
        return (time() - filemtime($file_path)) / 3600;
    }
    
    /**
     * Perform the actual download with retry logic
     *
     * @return array Download result
     */
    private function perform_download() {
        $url = $this->gestionale_settings['base_url'] . $this->gestionale_settings['filename'];
        $temp_file = $this->temp_dir . 'download_' . uniqid() . '.tar.gz';
        
        for ($attempt = 1; $attempt <= $this->config['max_retries']; $attempt++) {
            if ($attempt > 1) {
                $this->stats['retries']++;
                $this->logger->warning("Download attempt #{$attempt}", [
                    'url' => $url,
                    'previous_attempts' => $attempt - 1
                ]);
                
                sleep($this->config['retry_delay']);
            }
            
            $result = $this->download_file($url, $temp_file);
            
            if ($result['success']) {
                // Validate downloaded file
                if ($this->validate_downloaded_file($temp_file)) {
                    // Move to final location
                    $final_path = $this->get_existing_file_path();
                    
                    if (rename($temp_file, $final_path)) {
                        return $this->success_result($final_path, 'downloaded');
                    } else {
                        return $this->error_result('Failed to move downloaded file to final location');
                    }
                } else {
                    // Clean up invalid file
                    if (file_exists($temp_file)) {
                        unlink($temp_file);
                    }
                    
                    if ($attempt < $this->config['max_retries']) {
                        $this->logger->warning('Downloaded file validation failed, retrying', [
                            'attempt' => $attempt,
                            'max_retries' => $this->config['max_retries']
                        ]);
                        continue;
                    } else {
                        return $this->error_result('Downloaded file validation failed after all retries');
                    }
                }
            } else {
                if ($attempt < $this->config['max_retries']) {
                    $this->logger->warning('Download failed, retrying', [
                        'attempt' => $attempt,
                        'error' => $result['error'],
                        'max_retries' => $this->config['max_retries']
                    ]);
                    continue;
                } else {
                    return $this->error_result($result['error']);
                }
            }
        }
        
        return $this->error_result('Max retries exceeded');
    }
    
    /**
     * Download file using cURL
     *
     * @param string $url Download URL
     * @param string $file_path Local file path
     * @return array Download result
     */
    private function download_file($url, $file_path) {
        $this->logger->debug('Starting file download', [
            'url' => $url,
            'file_path' => $file_path
        ]);
        
        // Initialize cURL
        $ch = curl_init();
        
        // Open file for writing
        $fp = fopen($file_path, 'w+');
        if (!$fp) {
            return $this->error_result('Cannot open file for writing: ' . $file_path);
        }
        
        // Configure cURL
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $fp,
            CURLOPT_TIMEOUT => $this->config['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => $this->config['verify_ssl'],
            CURLOPT_USERAGENT => $this->config['user_agent'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->gestionale_settings['username'] . ':' . $this->gestionale_settings['password'],
            CURLOPT_PROGRESSFUNCTION => [$this, 'curl_progress_callback'],
            CURLOPT_NOPROGRESS => false,
            CURLOPT_BUFFERSIZE => $this->config['chunk_size']
        ]);
        
        // Execute download
        $success = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $info = curl_getinfo($ch);
        
        curl_close($ch);
        fclose($fp);
        
        // Check for errors
        if (!$success) {
            unlink($file_path); // Clean up failed download
            return $this->error_result('cURL error: ' . $error);
        }
        
        if ($http_code !== 200) {
            unlink($file_path); // Clean up failed download
            return $this->error_result("HTTP error: {$http_code}");
        }
        
        $this->stats['file_size'] = filesize($file_path);
        
        $this->logger->debug('File download completed', [
            'file_size' => size_format($this->stats['file_size']),
            'http_code' => $http_code,
            'download_time' => $info['total_time']
        ]);
        
        return ['success' => true, 'file_path' => $file_path];
    }
    
    /**
     * cURL progress callback
     *
     * @param resource $resource cURL resource
     * @param int $download_size Total download size
     * @param int $downloaded Downloaded bytes
     * @param int $upload_size Total upload size
     * @param int $uploaded Uploaded bytes
     * @return int Continue download (0) or abort (non-zero)
     */
    public function curl_progress_callback($resource, $download_size, $downloaded, $upload_size, $uploaded) {
        // Check file size limit
        if ($download_size > $this->config['max_file_size']) {
            $this->logger->error('File size exceeds limit', [
                'file_size' => size_format($download_size),
                'max_size' => size_format($this->config['max_file_size'])
            ]);
            return 1; // Abort download
        }
        
        // Log progress every 10%
        if ($download_size > 0) {
            $progress = ($downloaded / $download_size) * 100;
            static $last_logged_progress = 0;
            
            if ($progress - $last_logged_progress >= 10) {
                $this->logger->debug('Download progress', [
                    'progress' => round($progress, 1) . '%',
                    'downloaded' => size_format($downloaded),
                    'total' => size_format($download_size)
                ]);
                $last_logged_progress = $progress;
            }
        }
        
        return 0; // Continue download
    }
    
    /**
     * Validate downloaded file
     *
     * @param string $file_path File path
     * @return bool File is valid
     */
    private function validate_downloaded_file($file_path) {
        if (!file_exists($file_path)) {
            $this->logger->error('Downloaded file does not exist', ['file_path' => $file_path]);
            return false;
        }
        
        $file_size = filesize($file_path);
        if ($file_size === 0) {
            $this->logger->error('Downloaded file is empty', ['file_path' => $file_path]);
            return false;
        }
        
        // Check if it's a valid gzip file
        $file_info = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($file_info, $file_path);
        finfo_close($file_info);
        
        if ($mime_type !== 'application/gzip' && $mime_type !== 'application/x-gzip') {
            $this->logger->error('Downloaded file is not a valid gzip archive', [
                'file_path' => $file_path,
                'mime_type' => $mime_type,
                'expected' => 'application/gzip'
            ]);
            return false;
        }
        
        $this->logger->debug('Downloaded file validation passed', [
            'file_path' => $file_path,
            'file_size' => size_format($file_size),
            'mime_type' => $mime_type
        ]);
        
        return true;
    }
    
    /**
     * Extract XML from downloaded archive
     *
     * @param string $archive_path Path to .tar.gz file
     * @return array Extraction result with XML file path
     */
    public function extract_xml($archive_path) {
        $this->logger->info('Starting XML extraction', ['archive_path' => $archive_path]);
        
        if (!file_exists($archive_path)) {
            return $this->error_result('Archive file not found: ' . $archive_path);
        }
        
        $extract_dir = $this->temp_dir . 'extracted_' . uniqid() . '/';
        
        if (!wp_mkdir_p($extract_dir)) {
            return $this->error_result('Cannot create extraction directory: ' . $extract_dir);
        }
        
        try {
            // Extract tar.gz file
            $phar = new PharData($archive_path);
            $phar->extractTo($extract_dir);
            
            // Find XML file in extracted contents
            $xml_file = $this->find_xml_file($extract_dir);
            
            if (!$xml_file) {
                return $this->error_result('No XML file found in archive');
            }
            
            $this->logger->info('XML extraction completed', [
                'xml_file' => $xml_file,
                'xml_size' => size_format(filesize($xml_file))
            ]);
            
            return $this->success_result($xml_file, 'extracted');
            
        } catch (Exception $e) {
            $this->logger->error('XML extraction failed', [
                'error' => $e->getMessage(),
                'archive_path' => $archive_path
            ]);
            
            return $this->error_result('Extraction failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Find XML file in extracted directory
     *
     * @param string $directory Directory to search
     * @return string|false XML file path or false if not found
     */
    private function find_xml_file($directory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'xml') {
                return $file->getPathname();
            }
        }
        
        return false;
    }
    
    /**
     * Calculate download speed
     */
    private function calculate_download_speed() {
        if ($this->stats['duration'] > 0) {
            $this->stats['download_speed'] = $this->stats['file_size'] / $this->stats['duration'];
        }
    }
    
    /**
     * Clean up temporary files
     *
     * @param int $max_age_hours Maximum age in hours for files to keep
     * @return int Number of files cleaned
     */
    public function cleanup_temp_files($max_age_hours = 24) {
        $files = glob($this->temp_dir . '*');
        $cleaned = 0;
        $cutoff_time = time() - ($max_age_hours * 3600);
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }
        
        if ($cleaned > 0) {
            $this->logger->info('Temporary files cleaned up', [
                'files_removed' => $cleaned,
                'max_age_hours' => $max_age_hours
            ]);
        }
        
        return $cleaned;
    }
    
    /**
     * Get download statistics
     *
     * @return array Statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Test connection to GestionaleImmobiliare.it
     *
     * @return array Test result
     */
    public function test_connection() {
        $this->logger->info('Testing connection to GestionaleImmobiliare.it');
        
        if (!$this->validate_credentials()) {
            return $this->error_result('Invalid credentials');
        }
        
        $url = $this->gestionale_settings['base_url'];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => $this->config['verify_ssl'],
            CURLOPT_USERAGENT => $this->config['user_agent'],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->gestionale_settings['username'] . ':' . $this->gestionale_settings['password']
        ]);
        
        $success = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if (!$success) {
            return $this->error_result('Connection failed: ' . $error);
        }
        
        if ($http_code === 200 || $http_code === 404) { // 404 is ok for directory listing
            return $this->success_result(null, 'connection_ok');
        } else {
            return $this->error_result("HTTP error: {$http_code}");
        }
    }
    
    /**
     * Create success result array
     *
     * @param string|null $file_path File path
     * @param string $source Source type
     * @return array Success result
     */
    private function success_result($file_path, $source) {
        return [
            'success' => true,
            'file_path' => $file_path,
            'source' => $source,
            'stats' => $this->stats
        ];
    }
    
    /**
     * Create error result array
     *
     * @param string $error Error message
     * @return array Error result
     */
    private function error_result($error) {
        return [
            'success' => false,
            'error' => $error,
            'stats' => $this->stats
        ];
    }
}

// End of file
