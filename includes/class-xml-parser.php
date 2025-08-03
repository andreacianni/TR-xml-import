<?php
/**
 * XML Parser Class for Trentino Import Plugin
 * 
 * Handles parsing and validation of XML data from GestionaleImmobiliare.it
 * Provides structured data extraction, validation, and filtering capabilities
 * with comprehensive error handling and logging.
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
 * TrentinoXmlParser Class
 * 
 * Manages XML parsing operations including:
 * - XML structure validation and error handling
 * - Property data extraction and normalization
 * - Province filtering and data validation
 * - Memory-efficient parsing for large files
 * - Comprehensive logging and debugging
 */
class TrentinoXmlParser {
    
    /**
     * Logger instance
     */
    private $logger;
    
    /**
     * Parser configuration
     */
    private $config;
    
    /**
     * Enabled provinces for filtering
     */
    private $enabled_provinces;
    
    /**
     * XML document instance
     */
    private $xml_doc;
    
    /**
     * Parsing statistics
     */
    private $stats;
    
    /**
     * Property categories mapping
     */
    private $property_categories;
    
    /**
     * Required fields for validation
     */
    private $required_fields;
    
    /**
     * Constructor
     *
     * @param TrentinoImportLogger $logger Logger instance
     */
    public function __construct($logger = null) {
        $this->logger = $logger ?: trentino_import_logger();
        $this->init_parser();
    }
    
    /**
     * Initialize parser
     */
    private function init_parser() {
        // Load configuration
        $this->load_config();
        
        // Load province filter
        $this->load_province_filter();
        
        // Load property categories mapping
        $this->load_property_categories();
        
        // Load required fields
        $this->load_required_fields();
        
        // Initialize statistics
        $this->reset_stats();
        
        $this->logger->debug('XML Parser initialized', [
            'enabled_provinces' => $this->enabled_provinces,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        ]);
    }
    
    /**
     * Load parser configuration
     */
    private function load_config() {
        $defaults = [
            'validate_xml' => true,
            'memory_limit' => '256M',
            'chunk_size' => 100, // Properties to process at once
            'max_properties' => 10000, // Safety limit
            'encoding' => 'UTF-8',
            'strict_validation' => false,
            'debug_mode' => false
        ];
        
        $this->config = get_option('trentino_import_parser_config', $defaults);
    }
    
    /**
     * Load province filter configuration
     */
    private function load_province_filter() {
        // Default provinces: Trento (TN) and Bolzano (BZ)
        $default_provinces = ['TN', 'BZ'];
        $this->enabled_provinces = get_option('trentino_import_enabled_provinces', $default_provinces);
    }
    
    /**
     * Load property categories mapping
     */
    private function load_property_categories() {
        $this->property_categories = [
            1 => 'house',           // Casa singola
            2 => 'house',           // Bifamiliare  
            11 => 'apartment',      // Appartamento
            12 => 'penthouse',      // Attico
            18 => 'villa',          // Villa
            19 => 'land',           // Terreno
            14 => 'commercial',     // Negozio
            17 => 'office',         // Ufficio
            8 => 'garage'           // Garage
        ];
    }
    
    /**
     * Load required fields for validation
     */
    private function load_required_fields() {
        $this->required_fields = [
            'id_immobile',
            'titolo',
            'prezzo_vendita',
            'categoria',
            'provincia',
            'citta'
        ];
    }
    
    /**
     * Reset parsing statistics
     */
    private function reset_stats() {
        $this->stats = [
            'start_time' => 0,
            'end_time' => 0,
            'duration' => 0,
            'total_properties' => 0,
            'valid_properties' => 0,
            'filtered_properties' => 0,
            'invalid_properties' => 0,
            'memory_peak' => 0,
            'errors' => []
        ];
    }
    
    /**
     * Parse XML file and extract property data
     *
     * @param string $xml_file_path Path to XML file
     * @return array Parsing result with extracted properties
     */
    public function parse_xml_file($xml_file_path) {
        $this->logger->info('Starting XML parsing', [
            'file_path' => $xml_file_path,
            'file_size' => file_exists($xml_file_path) ? size_format(filesize($xml_file_path)) : 'unknown'
        ]);
        
        // Validate file exists
        if (!file_exists($xml_file_path)) {
            return $this->error_result('XML file not found: ' . $xml_file_path);
        }
        
        // Check file size and memory requirements
        $file_size = filesize($xml_file_path);
        if (!$this->check_memory_requirements($file_size)) {
            return $this->error_result('Insufficient memory for parsing this XML file');
        }
        
        // Reset statistics
        $this->reset_stats();
        $this->stats['start_time'] = microtime(true);
        
        // Set memory limit if configured
        if (!empty($this->config['memory_limit'])) {
            ini_set('memory_limit', $this->config['memory_limit']);
        }
        
        try {
            // Load and validate XML
            $load_result = $this->load_xml_file($xml_file_path);
            if (!$load_result['success']) {
                return $load_result;
            }
            
            // Extract properties
            $extraction_result = $this->extract_properties();
            if (!$extraction_result['success']) {
                return $extraction_result;
            }
            
            // Calculate final statistics
            $this->stats['end_time'] = microtime(true);
            $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];
            $this->stats['memory_peak'] = memory_get_peak_usage(true);
            
            $this->logger->info('XML parsing completed successfully', [
                'total_properties' => $this->stats['total_properties'],
                'valid_properties' => $this->stats['valid_properties'],
                'filtered_properties' => $this->stats['filtered_properties'],
                'duration' => round($this->stats['duration'], 2) . 's',
                'memory_peak' => size_format($this->stats['memory_peak']),
                'errors_count' => count($this->stats['errors'])
            ]);
            
            return $this->success_result($extraction_result['properties']);
            
        } catch (Exception $e) {
            $this->logger->error('XML parsing failed with exception', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->error_result('Parsing failed: ' . $e->getMessage());
        } finally {
            // Clean up XML document
            $this->xml_doc = null;
        }
    }
    
    /**
     * Check memory requirements for XML parsing
     *
     * @param int $file_size File size in bytes
     * @return bool Memory is sufficient
     */
    private function check_memory_requirements($file_size) {
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $current_usage = memory_get_usage(true);
        $estimated_usage = $file_size * 3; // XML parsing typically uses 3x file size
        $required_memory = $current_usage + $estimated_usage;
        
        $this->logger->debug('Memory requirements check', [
            'file_size' => size_format($file_size),
            'memory_limit' => size_format($memory_limit),
            'current_usage' => size_format($current_usage),
            'estimated_usage' => size_format($estimated_usage),
            'required_memory' => size_format($required_memory),
            'sufficient' => $required_memory < $memory_limit
        ]);
        
        return $required_memory < $memory_limit;
    }
    
    /**
     * Load and validate XML file
     *
     * @param string $xml_file_path File path
     * @return array Load result
     */
    private function load_xml_file($xml_file_path) {
        $this->logger->debug('Loading XML file', ['file_path' => $xml_file_path]);
        
        // Enable user error handling
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        // Create DOMDocument with proper settings
        $this->xml_doc = new DOMDocument('1.0', $this->config['encoding']);
        $this->xml_doc->preserveWhiteSpace = false;
        $this->xml_doc->formatOutput = false;
        
        // Load XML file
        $loaded = $this->xml_doc->load($xml_file_path, LIBXML_NOCDATA | LIBXML_NOBLANKS);
        
        if (!$loaded) {
            $xml_errors = libxml_get_errors();
            $error_messages = [];
            
            foreach ($xml_errors as $error) {
                $error_messages[] = "Line {$error->line}: {$error->message}";
            }
            
            libxml_clear_errors();
            
            $this->logger->error('XML loading failed', [
                'file_path' => $xml_file_path,
                'errors' => $error_messages
            ]);
            
            return $this->error_result('Invalid XML file: ' . implode('; ', $error_messages));
        }
        
        // Validate XML structure if enabled
        if ($this->config['validate_xml']) {
            $validation_result = $this->validate_xml_structure();
            if (!$validation_result['success']) {
                return $validation_result;
            }
        }
        
        $this->logger->debug('XML file loaded successfully');
        return ['success' => true];
    }
    
    /**
     * Validate XML structure
     *
     * @return array Validation result
     */
    private function validate_xml_structure() {
        $this->logger->debug('Validating XML structure');
        
        // Check for root element
        $root = $this->xml_doc->documentElement;
        if (!$root) {
            return $this->error_result('XML has no root element');
        }
        
        // Look for properties container
        $properties = $root->getElementsByTagName('immobile');
        if ($properties->length === 0) {
            // Try alternative structure
            $properties = $root->getElementsByTagName('property');
            if ($properties->length === 0) {
                return $this->error_result('No properties found in XML structure');
            }
        }
        
        $this->logger->debug('XML structure validation passed', [
            'root_element' => $root->nodeName,
            'properties_found' => $properties->length
        ]);
        
        return ['success' => true];
    }
    
    /**
     * Extract properties from XML document
     *
     * @return array Extraction result
     */
    private function extract_properties() {
        $this->logger->debug('Starting property extraction');
        
        $properties = [];
        $chunk_counter = 0;
        
        // Get all property nodes
        $property_nodes = $this->xml_doc->getElementsByTagName('immobile');
        $this->stats['total_properties'] = $property_nodes->length;
        
        $this->logger->info('Found properties in XML', [
            'total_count' => $this->stats['total_properties']
        ]);
        
        // Safety check
        if ($this->stats['total_properties'] > $this->config['max_properties']) {
            return $this->error_result("Too many properties ({$this->stats['total_properties']}). Maximum allowed: {$this->config['max_properties']}");
        }
        
        foreach ($property_nodes as $index => $property_node) {
            try {
                // Extract property data
                $property_data = $this->extract_property_data($property_node);
                
                if ($property_data) {
                    // Validate property
                    $validation_result = $this->validate_property($property_data);
                    
                    if ($validation_result['valid']) {
                        // Apply province filter
                        if ($this->passes_province_filter($property_data)) {
                            $properties[] = $property_data;
                            $this->stats['valid_properties']++;
                        } else {
                            $this->stats['filtered_properties']++;
                        }
                    } else {
                        $this->stats['invalid_properties']++;
                        $this->stats['errors'][] = [
                            'property_index' => $index,
                            'property_id' => $property_data['id_immobile'] ?? 'unknown',
                            'errors' => $validation_result['errors']
                        ];
                        
                        if ($this->config['debug_mode']) {
                            $this->logger->warning('Property validation failed', [
                                'property_index' => $index,
                                'property_id' => $property_data['id_immobile'] ?? 'unknown',
                                'errors' => $validation_result['errors']
                            ]);
                        }
                    }
                } else {
                    $this->stats['invalid_properties']++;
                }
                
                // Progress logging every chunk
                $chunk_counter++;
                if ($chunk_counter >= $this->config['chunk_size']) {
                    $this->logger->debug('Property extraction progress', [
                        'processed' => $index + 1,
                        'total' => $this->stats['total_properties'],
                        'valid' => $this->stats['valid_properties'],
                        'memory_usage' => size_format(memory_get_usage(true))
                    ]);
                    $chunk_counter = 0;
                }
                
            } catch (Exception $e) {
                $this->stats['invalid_properties']++;
                $this->stats['errors'][] = [
                    'property_index' => $index,
                    'error' => $e->getMessage()
                ];
                
                $this->logger->warning('Property extraction error', [
                    'property_index' => $index,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->logger->info('Property extraction completed', [
            'total_processed' => $this->stats['total_properties'],
            'valid_properties' => $this->stats['valid_properties'],
            'filtered_properties' => $this->stats['filtered_properties'],
            'invalid_properties' => $this->stats['invalid_properties'],
            'errors_count' => count($this->stats['errors'])
        ]);
        
        return $this->success_result($properties);
    }
    
    /**
     * Extract data from single property node
     *
     * @param DOMElement $property_node Property XML node
     * @return array|null Property data array or null if extraction fails
     */
    private function extract_property_data($property_node) {
        $property = [];
        
        // Extract all child elements
        foreach ($property_node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag_name = $child->nodeName;
                $value = trim($child->textContent);
                
                // Handle numeric values
                if (in_array($tag_name, ['prezzo_vendita', 'prezzo_affitto', 'superficie_commerciale', 'numero_camere', 'numero_bagni'])) {
                    $value = $this->parse_numeric_value($value);
                }
                
                // Handle boolean values
                if (in_array($tag_name, ['ascensore', 'giardino', 'piscina', 'garage', 'aria_condizionata'])) {
                    $value = $this->parse_boolean_value($value);
                }
                
                $property[$tag_name] = $value;
            }
        }
        
        // Add derived fields
        $property = $this->add_derived_fields($property);
        
        return !empty($property) ? $property : null;
    }
    
    /**
     * Parse numeric value from XML
     *
     * @param string $value Raw value
     * @return float|int|null Parsed numeric value
     */
    private function parse_numeric_value($value) {
        if (empty($value)) {
            return null;
        }
        
        // Remove currency symbols and formatting
        $cleaned = preg_replace('/[€$,\s]/', '', $value);
        
        if (is_numeric($cleaned)) {
            return strpos($cleaned, '.') !== false ? (float)$cleaned : (int)$cleaned;
        }
        
        return null;
    }
    
    /**
     * Parse boolean value from XML
     *
     * @param string $value Raw value
     * @return bool|null Parsed boolean value
     */
    private function parse_boolean_value($value) {
        if (empty($value)) {
            return null;
        }
        
        $value = strtolower(trim($value));
        
        if (in_array($value, ['1', 'true', 'yes', 'si', 'sì'])) {
            return true;
        } else if (in_array($value, ['0', 'false', 'no'])) {
            return false;
        }
        
        return null;
    }
    
    /**
     * Add derived fields to property data
     *
     * @param array $property Property data
     * @return array Property data with derived fields
     */
    private function add_derived_fields($property) {
        // Add WP-compatible category
        if (isset($property['categoria'])) {
            $property['wp_category'] = $this->property_categories[$property['categoria']] ?? 'property';
        }
        
        // Add price display
        if (isset($property['prezzo_vendita']) && $property['prezzo_vendita'] > 0) {
            $property['price_display'] = '€ ' . number_format($property['prezzo_vendita'], 0, ',', '.');
            $property['listing_type'] = 'sale';
        } else if (isset($property['prezzo_affitto']) && $property['prezzo_affitto'] > 0) {
            $property['price_display'] = '€ ' . number_format($property['prezzo_affitto'], 0, ',', '.') . '/mese';
            $property['listing_type'] = 'rent';
        }
        
        // Add full address
        $address_parts = array_filter([
            $property['indirizzo'] ?? '',
            $property['citta'] ?? '',
            $property['provincia'] ?? ''
        ]);
        $property['full_address'] = implode(', ', $address_parts);
        
        // Add unique hash for duplicate detection
        $property['content_hash'] = md5(serialize(array_intersect_key($property, array_flip($this->required_fields))));
        
        return $property;
    }
    
    /**
     * Validate property data
     *
     * @param array $property Property data
     * @return array Validation result
     */
    private function validate_property($property) {
        $errors = [];
        
        // Check required fields
        foreach ($this->required_fields as $field) {
            if (!isset($property[$field]) || empty($property[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Validate price
        if (!isset($property['prezzo_vendita']) && !isset($property['prezzo_affitto'])) {
            $errors[] = "Missing both sale and rent price";
        }
        
        // Validate category
        if (isset($property['categoria']) && !isset($this->property_categories[$property['categoria']])) {
            $errors[] = "Unknown property category: {$property['categoria']}";
        }
        
        // Additional strict validation
        if ($this->config['strict_validation']) {
            // Validate province code
            if (isset($property['provincia']) && !in_array($property['provincia'], ['TN', 'BZ', 'TV', 'VE', 'VI', 'VR', 'PD', 'RO', 'BL'])) {
                $errors[] = "Invalid province code: {$property['provincia']}";
            }
            
            // Validate numeric ranges
            if (isset($property['numero_camere']) && ($property['numero_camere'] < 0 || $property['numero_camere'] > 20)) {
                $errors[] = "Invalid number of rooms: {$property['numero_camere']}";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check if property passes province filter
     *
     * @param array $property Property data
     * @return bool Property passes filter
     */
    private function passes_province_filter($property) {
        if (empty($this->enabled_provinces)) {
            return true; // No filter applied
        }
        
        $property_province = $property['provincia'] ?? '';
        return in_array($property_province, $this->enabled_provinces);
    }
    
    /**
     * Get parsing statistics
     *
     * @return array Statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Get enabled provinces
     *
     * @return array Enabled provinces
     */
    public function get_enabled_provinces() {
        return $this->enabled_provinces;
    }
    
    /**
     * Set enabled provinces
     *
     * @param array $provinces Province codes
     */
    public function set_enabled_provinces($provinces) {
        $this->enabled_provinces = $provinces;
        update_option('trentino_import_enabled_provinces', $provinces);
        
        $this->logger->info('Province filter updated', [
            'enabled_provinces' => $provinces
        ]);
    }
    
    /**
     * Get property categories mapping
     *
     * @return array Categories mapping
     */
    public function get_property_categories() {
        return $this->property_categories;
    }
    
    /**
     * Validate XML file without full parsing
     *
     * @param string $xml_file_path File path
     * @return array Validation result
     */
    public function quick_validate_xml($xml_file_path) {
        $this->logger->debug('Quick XML validation', ['file_path' => $xml_file_path]);
        
        if (!file_exists($xml_file_path)) {
            return $this->error_result('File not found');
        }
        
        // Basic XML validation
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $doc = new DOMDocument();
        $loaded = $doc->load($xml_file_path);
        
        if (!$loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return $this->error_result('Invalid XML: ' . $errors[0]->message);
        }
        
        // Count properties
        $properties = $doc->getElementsByTagName('immobile');
        $property_count = $properties->length;
        
        return $this->success_result(null, [
            'property_count' => $property_count,
            'file_size' => filesize($xml_file_path)
        ]);
    }
    
    /**
     * Create success result array
     *
     * @param array|null $properties Extracted properties
     * @param array $extra_data Extra data
     * @return array Success result
     */
    private function success_result($properties = null, $extra_data = []) {
        return array_merge([
            'success' => true,
            'properties' => $properties,
            'stats' => $this->stats
        ], $extra_data);
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
