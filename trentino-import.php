<?php
/**
 * Plugin Name: Trentino Import Plugin
 * Plugin URI: https://trentinoimmobiliare.it/
 * Description: Plugin WordPress per import automatico annunci immobiliari da XML GestionaleImmobiliare.it. Integrazione nativa con tema WpResidence.
 * Version: 1.0.4
 * Author: Andrea Cianni - Novacom
 * Author URI: https://www.novacomitalia.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: trentino-import
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: false
 *
 * @package TrentinoImport
 *
 * This plugin provides automated import functionality for real estate listings
 * from GestionaleImmobiliare.it XML feeds, specifically designed for integration
 * with the WpResidence theme.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Plugin version and core constants
define('TRENTINO_IMPORT_VERSION', '1.0.4');
define('TRENTINO_IMPORT_PLUGIN_FILE', __FILE__);
define('TRENTINO_IMPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TRENTINO_IMPORT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRENTINO_IMPORT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('TRENTINO_IMPORT_MIN_PHP_VERSION', '7.4');
define('TRENTINO_IMPORT_MIN_WP_VERSION', '5.0');

/**
 * Main plugin class - TrentinoImport
 */
class TrentinoImport {

    private static $instance = null;
    private $initialized = false;
    private $admin_page = null;
    private $xml_downloader = null;
    private $xml_parser = null;
    private $property_mapper = null;
    private $wp_importer = null;
    private $logger = null;
    private $cron_manager = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        // Test Property Mapper with parsed data
        if (isset($_POST['test_mapper'])) {
            $parser = new TrentinoXmlParser($logger);
            $mapper = new TrentinoPropertyMapper($logger);
            
            $sample_xml = $this->create_sample_xml();
            $temp_file = wp_upload_dir()['basedir'] . '/trentino-import-temp/sample.xml';
            
            wp_mkdir_p(dirname($temp_file));
            file_put_contents($temp_file, $sample_xml);
            
            $parse_result = $parser->parse_xml_file($temp_file);
            
            if ($parse_result['success']) {
                $map_result = $mapper->map_properties($parse_result['properties']);
                
                if ($map_result['success']) {
                    echo '<div class="notice notice-success"><p>Property Mapper test successful! Mapped ' . count($map_result['properties']) . ' properties with ' . count($map_result['properties'][0]['meta_fields']) . ' meta fields each.</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Property Mapper test failed: Mapping error</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Property Mapper test failed: XML parsing failed</p></div>';
            }
            
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
        return self::$instance;
    }

    private function __construct() {
        // Plugin will be initialized via init_plugin() method
    }

    public function init_plugin() {
        if ($this->initialized) {
            return;
        }

        if (!$this->check_requirements()) {
            return;
        }

        $this->load_plugin_files();
        $this->init_components();
        $this->register_hooks();

        $this->initialized = true;
        do_action('trentino_import_plugin_loaded');
    }

    private function check_requirements() {
        return true;
    }

    private function load_plugin_files() {
        require_once TRENTINO_IMPORT_PLUGIN_DIR . 'includes/class-logger.php';
        require_once TRENTINO_IMPORT_PLUGIN_DIR . 'includes/class-xml-downloader.php';
        require_once TRENTINO_IMPORT_PLUGIN_DIR . 'includes/class-xml-parser.php';
        require_once TRENTINO_IMPORT_PLUGIN_DIR . 'includes/class-property-mapper.php';
        require_once TRENTINO_IMPORT_PLUGIN_DIR . 'includes/class-github-updater.php';
    }

    private function init_components() {
        $this->logger = TrentinoImportLogger::get_instance();
        $this->xml_downloader = new TrentinoXmlDownloader($this->logger);
        $this->xml_parser = new TrentinoXmlParser($this->logger);
        $this->property_mapper = new TrentinoPropertyMapper($this->logger);
        
        if (is_admin()) {
            new TrentinoGitHubUpdater(TRENTINO_IMPORT_PLUGIN_FILE);
        }
    }

    private function register_hooks() {
        add_action('admin_menu', [$this, 'add_test_logger_page']);
    }
    
    public function add_test_logger_page() {
        add_plugins_page(
            'Logger Test',
            'Logger Test',
            'manage_options',
            'trentino-logger-test',
            [$this, 'logger_test_page']
        );
    }
    
    public function logger_test_page() {
        $logger = trentino_import_logger();
        
        // Test XML Downloader connection
        if (isset($_POST['test_connection'])) {
            $downloader = new TrentinoXmlDownloader($logger);
            $result = $downloader->test_connection();
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>Connection test successful!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Connection test failed: ' . esc_html($result['error']) . '</p></div>';
            }
        }
        
        // Test XML Parser with sample data
        if (isset($_POST['test_parser'])) {
            $parser = new TrentinoXmlParser($logger);
            
            $sample_xml = $this->create_sample_xml();
            $temp_file = wp_upload_dir()['basedir'] . '/trentino-import-temp/sample.xml';
            
            wp_mkdir_p(dirname($temp_file));
            file_put_contents($temp_file, $sample_xml);
            
            $result = $parser->parse_xml_file($temp_file);
            
            if ($result['success']) {
                echo '<div class="notice notice-success"><p>XML Parser test successful! Parsed ' . count($result['properties']) . ' properties.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>XML Parser test failed: ' . esc_html($result['error']) . '</p></div>';
            }
            
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
        }
        
        // Test all log levels
        if (isset($_POST['test_logger'])) {
            $session_id = $logger->start_import_session('test');
            
            $logger->debug('This is a debug message', ['test_data' => 'debug_value']);
            $logger->info('This is an info message', ['test_data' => 'info_value']);
            $logger->warning('This is a warning message', ['test_data' => 'warning_value']);
            $logger->error('This is an error message', ['test_data' => 'error_value']);
            
            $logger->log_import_step('xml_download', 'started');
            $logger->log_import_step('xml_download', 'completed', ['file_size' => '2.5MB']);
            
            $logger->end_import_session([
                'duration' => 5,
                'properties_processed' => 100,
                'properties_imported' => 95,
                'errors_count' => 0
            ]);
            
            echo '<div class="notice notice-success"><p>Logger test completed! Check logs below.</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Trentino Import - Logger Test</h1>
            
            <div class="card">
                <h2>Test Logger</h2>
                <form method="post">
                    <p>Click this button to test all logger functions:</p>
                    <input type="submit" name="test_logger" class="button button-primary" value="Run Logger Test">
                </form>
            </div>
            
            <div class="card">
                <h2>Test Property Mapper</h2>
                <form method="post">
                    <p>Test complete XML parsing + property mapping chain:</p>
                    <input type="submit" name="test_mapper" class="button button-secondary" value="Test Property Mapper">
                </form>
            </div>
            
            <div class="card">
                <h2>Test XML Downloader</h2>
                <form method="post">
                    <p>Test connection to GestionaleImmobiliare.it (requires credentials):</p>
                    <input type="submit" name="test_connection" class="button button-secondary" value="Test Connection">
                </form>
                <p><strong>Note:</strong> You need to configure username/password in plugin settings first.</p>
            </div>
            
            <div class="card">
                <h2>Test XML Parser</h2>
                <form method="post">
                    <p>Test XML parsing with sample data:</p>
                    <input type="submit" name="test_parser" class="button button-secondary" value="Test XML Parser">
                </form>
            </div>
            
            <div class="card">
                <h2>Recent Logs (Last 20)</h2>
                <div style="background: #f1f1f1; padding: 10px; font-family: monospace; max-height: 400px; overflow-y: auto;">
                    <?php
                    $recent_logs = $logger->get_recent_logs(20);
                    if (empty($recent_logs)) {
                        echo '<p>No logs found.</p>';
                    } else {
                        foreach ($recent_logs as $log) {
                            $level_color = [
                                'DEBUG' => '#666',
                                'INFO' => '#0073aa',
                                'WARNING' => '#f56e28',
                                'ERROR' => '#dc3232',
                                'CRITICAL' => '#dc3232'
                            ];
                            $color = $level_color[$log['level']] ?? '#333';
                            echo '<div style="margin-bottom: 5px; color: ' . $color . ';">' . esc_html($log['raw']) . '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="card">
                <h2>Log Files</h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>File</th>
                            <th>Size</th>
                            <th>Modified</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $log_files = $logger->get_log_files();
                        if (empty($log_files)) {
                            echo '<tr><td colspan="3">No log files found.</td></tr>';
                        } else {
                            foreach ($log_files as $file) {
                                echo '<tr>';
                                echo '<td>' . esc_html($file['filename']) . '</td>';
                                echo '<td>' . esc_html($file['size_formatted']) . '</td>';
                                echo '<td>' . esc_html($file['modified_formatted']) . '</td>';
                                echo '</tr>';
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
    
    private function create_sample_xml() {
        return '<?xml version="1.0" encoding="UTF-8"?>
<root>
    <immobile>
        <id_immobile>1001</id_immobile>
        <titolo>Appartamento centro Trento</titolo>
        <descrizione>Bellissimo appartamento nel centro storico di Trento</descrizione>
        <prezzo_vendita>250000</prezzo_vendita>
        <categoria>11</categoria>
        <provincia>TN</provincia>
        <citta>Trento</citta>
        <indirizzo>Via Roma 15</indirizzo>
        <superficie_commerciale>85</superficie_commerciale>
        <numero_camere>3</numero_camere>
        <numero_bagni>2</numero_bagni>
        <ascensore>1</ascensore>
        <giardino>0</giardino>
    </immobile>
    <immobile>
        <id_immobile>1002</id_immobile>
        <titolo>Villa con giardino Bolzano</titolo>
        <descrizione>Villa indipendente con ampio giardino</descrizione>
        <prezzo_vendita>450000</prezzo_vendita>
        <categoria>18</categoria>
        <provincia>BZ</provincia>
        <citta>Bolzano</citta>
        <indirizzo>Via dei Pini 8</indirizzo>
        <superficie_commerciale>150</superficie_commerciale>
        <numero_camere>4</numero_camere>
        <numero_bagni>3</numero_bagni>
        <ascensore>0</ascensore>
        <giardino>1</giardino>
        <piscina>1</piscina>
    </immobile>
</root>';
    }

    public function activate_plugin() {
        flush_rewrite_rules();
    }

    public function deactivate_plugin() {
        flush_rewrite_rules();
    }

    public static function uninstall_plugin() {
        // TODO: Implement uninstall logic
    }
}

function trentino_import_init() {
    $plugin = TrentinoImport::get_instance();
    $plugin->init_plugin();
}

add_action('init', 'trentino_import_init');
register_activation_hook(__FILE__, [TrentinoImport::get_instance(), 'activate_plugin']);
register_deactivation_hook(__FILE__, [TrentinoImport::get_instance(), 'deactivate_plugin']);
register_uninstall_hook(__FILE__, ['TrentinoImport', 'uninstall_plugin']);

function trentino_import() {
    return TrentinoImport::get_instance();
}

// End of file
