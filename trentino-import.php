<?php
/**
 * Plugin Name: Trentino Import Plugin
 * Plugin URI: https://trentinoimmobiliare.it/
 * Description: Plugin WordPress per import automatico annunci immobiliari da XML GestionaleImmobiliare.it. Integrazione nativa con tema WpResidence.
 * Version: 1.0.3
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
 * @version 1.0.3
 * @author Andrea Cianni
 * @copyright 2025 Novacom
 *
 * This plugin provides automated import functionality for real estate listings
 * from GestionaleImmobiliare.it XML feeds, specifically designed for integration
 * with the WpResidence theme.
 *
 * Features:
 * - Automated daily XML import from GestionaleImmobiliare.it
 * - Admin interface with 3-tab dashboard (Settings, Manual Import, Logs)
 * - Complete mapping XML fields → WpResidence properties
 * - Configurable province filtering
 * - Manual import triggers for troubleshooting
 * - Comprehensive logging and error handling
 * - WordPress cron integration for automation
 * - Secure credentials management
 * - Batch processing for performance
 *
 * Directory Structure:
 * /includes/          - Core functionality classes
 * /admin/             - Admin interface and views
 * /config/            - Configuration files and mapping
 * /logs/              - Import logs and error tracking
 *
 * Main Classes:
 * - XML_Downloader    - Download and authenticate XML from gestionale
 * - XML_Parser        - Parse and validate XML structure
 * - Property_Mapper   - Map XML fields to WpResidence format
 * - WP_Importer       - Import properties into WordPress
 * - Logger            - Comprehensive logging system
 * - Cron_Manager      - WordPress cron automation
 * - Admin_Page        - Admin interface controller
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Plugin version and core constants
define('TRENTINO_IMPORT_VERSION', '1.0.3');
define('TRENTINO_IMPORT_PLUGIN_FILE', __FILE__);
define('TRENTINO_IMPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TRENTINO_IMPORT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TRENTINO_IMPORT_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Minimum requirements
define('TRENTINO_IMPORT_MIN_PHP_VERSION', '7.4');
define('TRENTINO_IMPORT_MIN_WP_VERSION', '5.0');

/**
 * Main plugin class - TrentinoImport
 *
 * Handles plugin initialization, activation, deactivation and core functionality
 * orchestration. This is the main entry point for the plugin.
 */
class TrentinoImport {

    /**
     * Plugin instance (Singleton pattern)
     */
    private static $instance = null;

    /**
     * Plugin initialization flag
     */
    private $initialized = false;

    /**
     * Plugin components
     */
    private $admin_page = null;
    private $xml_downloader = null;
    private $xml_parser = null;
    private $property_mapper = null;
    private $wp_importer = null;
    private $logger = null;
    private $cron_manager = null;

    /**
     * Get plugin instance (Singleton)
     *
     * @return TrentinoImport
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
        // Plugin will be initialized via init_plugin() method
    }

    /**
     * Initialize the plugin
     *
     * This method handles all the plugin initialization logic including
     * requirements check, file loading, hooks registration, etc.
     */
    public function init_plugin() {
        // Prevent double initialization
        if ($this->initialized) {
            return;
        }

        // Check system requirements
        if (!$this->check_requirements()) {
            return;
        }

        // Load plugin files and initialize components
        $this->load_plugin_files();
        $this->init_components();
        $this->register_hooks();

        // Mark as initialized
        $this->initialized = true;

        // Hook for other plugins to know we're ready
        do_action('trentino_import_plugin_loaded');
    }

    /**
     * Check if system meets plugin requirements
     *
     * @return bool True if requirements are met, false otherwise
     */
    private function check_requirements() {
        // TODO: Implement requirements check
        // - PHP version
        // - WordPress version
        // - WpResidence theme active
        // - Required PHP extensions (curl, simplexml, etc.)

        return true;
    }

    /**
     * Load all plugin files
     */
    private function load_plugin_files() {
        // Load GitHub Updater first
        require_once TRENTINO_IMPORT_PLUGIN_DIR . 'includes/class-github-updater.php';

        // TODO: Load other core classes from /includes/
        // TODO: Load admin classes from /admin/
        // TODO: Load configuration files from /config/
    }

    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Initialize GitHub Updater
        if (is_admin()) {
            new TrentinoGitHubUpdater(TRENTINO_IMPORT_PLUGIN_FILE);
        }

        // TODO: Initialize other plugin components
        // Create instances of main classes
        // Set up component dependencies
    }

    /**
     * Register WordPress hooks
     */
    private function register_hooks() {
        // TODO: Register all WordPress hooks
        // Admin hooks, cron hooks, ajax hooks, etc.
    }

    /**
     * Plugin activation hook
     */
    public function activate_plugin() {
        // TODO: Implement activation logic
        // - Create database tables if needed
        // - Set default options
        // - Schedule cron events
        // - Check permissions and requirements

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation hook
     */
    public function deactivate_plugin() {
        // TODO: Implement deactivation logic
        // - Clear scheduled cron events
        // - Clean up temporary files
        // - Flush rewrite rules

        flush_rewrite_rules();
    }

    /**
     * Plugin uninstall hook
     */
    public static function uninstall_plugin() {
        // TODO: Implement uninstall logic
        // - Remove database tables
        // - Delete plugin options
        // - Clean up files and directories
        // - Remove scheduled events
    }
}

/**
 * Initialize the plugin
 *
 * This is the main entry point - WordPress will call this when loading the plugin
 */
function trentino_import_init() {
    $plugin = TrentinoImport::get_instance();
    $plugin->init_plugin();
}

// Hook plugin initialization to WordPress init
add_action('init', 'trentino_import_init');

// Register activation/deactivation hooks
register_activation_hook(__FILE__, [TrentinoImport::get_instance(), 'activate_plugin']);
register_deactivation_hook(__FILE__, [TrentinoImport::get_instance(), 'deactivate_plugin']);
register_uninstall_hook(__FILE__, ['TrentinoImport', 'uninstall_plugin']);

/**
 * Helper function to get plugin instance from anywhere
 *
 * @return TrentinoImport
 */
function trentino_import() {
    return TrentinoImport::get_instance();
}

// End of file - Ready for development!
