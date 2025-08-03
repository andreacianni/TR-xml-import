<?php
/**
 * Memory Optimized XML Parser for Large Files (264MB+)
 * 
 * Handles streaming parsing of very large XML files using XMLReader
 * to avoid memory limit issues. Specifically designed for handling
 * GestionaleImmobiliare.it XML exports that can be 100MB+ in size.
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
 * TrentinoXmlParserMemoryOptimized Class
 * 
 * Memory-efficient XML parser using XMLReader for streaming large files
 * without loading entire document into memory. Processes properties one at a time.
 */
class TrentinoXmlParserMemoryOptimized {
    
    private $logger;
    private $config;
    private $enabled_provinces;
    private $property_categories;
    private $required_fields;
    private $stats;
    
    public function __construct($logger = null) {
        $this->logger = $logger ?: trentino_import_logger();
        $this->init_parser();
    }
    
    private function init_parser() {
        $this->load_config();
        $this->load_province_filter();
        $this->load_property_categories();
        $this->load_required_fields();
        $this->reset_stats();
        
        $this->logger->debug('Memory Optimized XML Parser initialized');
    }
    
    private function load_config() {
        $defaults = [
            'memory_limit' => '512M',           // Increased for large files
            'chunk_size' => 50,                 // Smaller chunks for memory efficiency
            'max_properties' => 50000,          // Higher limit for real estate feeds
            'stream_buffer_size' => 8192,       // XMLReader buffer size
            'progress_interval' => 100,         // Log progress every N properties
            'encoding' => 'UTF-8'
        ];
        
        $this->config = get_option('trentino_import_parser_memory_config', $defaults);
    }
    
    private function load_province_filter() {
        $this->enabled_provinces = get_option('trentino_import_enabled_provinces', ['TN', 'BZ']);
    }
    
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
    
    private function load_required_fields() {
        $this->required_fields = [
            'id',
            'title',
            'price',
            'categorie_id'
        ];
    }
    
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
     * Parse large XML file using streaming approach with DYNAMIC detection
     */
    public function parse_xml_file($xml_file_path) {
        $this->logger->info('Starting STREAMING XML parsing for large file', [
            'file_path' => $xml_file_path,
            'file_size' => file_exists($xml_file_path) ? size_format(filesize($xml_file_path)) : 'unknown'
        ]);
        
        if (!file_exists($xml_file_path)) {
            return $this->error_result('XML file not found: ' . $xml_file_path);
        }
        
        $file_size = filesize($xml_file_path);
        
        // For very large files, use streaming approach
        if ($file_size > 50 * 1024 * 1024) { // 50MB+
            $this->logger->info('Large file detected - using streaming parser', [
                'file_size' => size_format($file_size),
                'threshold' => '50MB'
            ]);
            return $this->parse_xml_streaming($xml_file_path);
        } else {
            // For smaller files, use standard parsing
            $this->logger->info('Small file detected - using standard parser');
            return $this->parse_xml_standard($xml_file_path);
        }
    }
    
    /**
     * Parse XML using streaming XMLReader (for large files) with DYNAMIC element detection
     */
    private function parse_xml_streaming($xml_file_path) {
        // Set memory limit
        if (!empty($this->config['memory_limit'])) {
            ini_set('memory_limit', $this->config['memory_limit']);
        }
        
        // Increase execution time for large files
        set_time_limit(300); // 5 minutes
        
        $this->reset_stats();
        $this->stats['start_time'] = microtime(true);
        
        $properties = [];
        $current_property = null;
        $current_element = '';
        $property_element_name = null; // DYNAMIC: Will be detected
        
        // DEBUG: Analyze XML structure first
        $this->debug_xml_structure($xml_file_path);
        
        try {
            // Create XMLReader for streaming
            $reader = new XMLReader();
            
            if (!$reader->open($xml_file_path)) {
                return $this->error_result('Cannot open XML file for reading');
            }
            
            $this->logger->info('XMLReader opened - DYNAMIC property detection started');
            
            // DEBUG: Track elements found
            $elements_found = [];
            $depth = 0;
            $property_candidates = ['annuncio', 'immobile', 'property', 'listing', 'item', 'record'];
            
            // Stream through XML
            while ($reader->read()) {
                switch ($reader->nodeType) {
                    case XMLReader::ELEMENT:
                        $depth++;
                        $element_name = $reader->localName;
                        
                        // Track all elements for debugging
                        if (!isset($elements_found[$element_name])) {
                            $elements_found[$element_name] = 0;
                        }
                        $elements_found[$element_name]++;
                        
                        // Log first 20 elements for debugging
                        if (array_sum($elements_found) <= 20) {
                            $this->logger->info('XMLReader Element found', [
                                'element' => $element_name,
                                'depth' => $depth,
                                'count' => $elements_found[$element_name]
                            ]);
                        }
                        
                        // DYNAMIC DETECTION: Auto-detect property element
                        if ($property_element_name === null && in_array($element_name, $property_candidates)) {
                            $property_element_name = $element_name;
                            $this->logger->info('🎯 PROPERTY ELEMENT DETECTED!', [
                                'element' => $property_element_name,
                                'depth' => $depth
                            ]);
                        }
                        
                        // Use detected property element OR fallback to annuncio
                        $target_element = $property_element_name ?: 'annuncio';
                        
                        if ($element_name === $target_element) {
                            // Start new property
                            $current_property = [];
                            $this->stats['total_properties']++;
                            
                            $this->logger->info('✅ Property element found!', [
                                'element' => $target_element,
                                'property_count' => $this->stats['total_properties']
                            ]);
                        } else if ($current_property !== null) {
                            // Store current element name
                            $current_element = $element_name;
                        }
                        break;
                        
                    case XMLReader::TEXT:
                        if ($current_property !== null && !empty($current_element)) {
                            // Store element value
                            $value = trim($reader->value);
                            if (!empty($value)) {
                                $current_property[$current_element] = $this->process_field_value($current_element, $value);
                            }
                        }
                        break;
                        
                    case XMLReader::END_ELEMENT:
                        $depth--;
                        $target_element = $property_element_name ?: 'annuncio';
                        
                        if ($reader->localName === $target_element && $current_property !== null) {
                            // Process completed property
                            $this->process_property($current_property, $properties);
                            $current_property = null;
                            
                            // Progress logging
                            if ($this->stats['total_properties'] % $this->config['progress_interval'] === 0) {
                                $this->logger->info('🔄 Streaming progress', [
                                    'properties_processed' => $this->stats['total_properties'],
                                    'valid_properties' => $this->stats['valid_properties'],
                                    'memory_usage' => size_format(memory_get_usage(true))
                                ]);
                            }
                        } else if ($current_property !== null) {
                            $current_element = '';
                        }
                        break;
                }
                
                // Safety check for maximum properties
                if ($this->stats['total_properties'] > $this->config['max_properties']) {
                    $this->logger->warning('Maximum properties limit reached', [
                        'limit' => $this->config['max_properties'],
                        'processed' => $this->stats['total_properties']
                    ]);
                    break;
                }
                
                // ENHANCED DEBUG: Stop after processing elements if no properties found
                if (array_sum($elements_found) > 2000 && $this->stats['total_properties'] === 0) {
                    $this->logger->warning('🚨 Processed 2000+ elements but found 0 properties', [
                        'elements_found' => $elements_found,
                        'property_element_detected' => $property_element_name,
                        'search_candidates' => $property_candidates
                    ]);
                    break;
                }
                
                // Success early exit if we found some properties
                if ($this->stats['total_properties'] >= 10 && array_sum($elements_found) > 1000) {
                    $this->logger->info('✅ Found properties - continuing with confirmed element', [
                        'confirmed_element' => $property_element_name,
                        'properties_found' => $this->stats['total_properties']
                    ]);
                }
            }
            
            $reader->close();
            
            // Log final comprehensive analysis
            $this->logger->info('🎯 DYNAMIC PARSING COMPLETED', [
                'property_element_detected' => $property_element_name,
                'total_elements_found' => $elements_found,
                'target_element_count' => $elements_found[$property_element_name ?: 'annuncio'] ?? 0,
                'properties_processed' => $this->stats['total_properties']
            ]);
            
            // Calculate final statistics
            $this->stats['end_time'] = microtime(true);
            $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];
            $this->stats['memory_peak'] = memory_get_peak_usage(true);
            
            $this->logger->info('🏁 Dynamic streaming XML parsing completed', [
                'total_properties' => $this->stats['total_properties'],
                'valid_properties' => $this->stats['valid_properties'],
                'filtered_properties' => $this->stats['filtered_properties'],
                'duration' => round($this->stats['duration'], 2) . 's',
                'memory_peak' => size_format($this->stats['memory_peak']),
                'errors_count' => count($this->stats['errors'])
            ]);
            
            return $this->success_result($properties);
            
        } catch (Exception $e) {
            $this->logger->error('Dynamic streaming XML parsing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'properties_processed' => $this->stats['total_properties'],
                'property_element_detected' => $property_element_name
            ]);
            
            return $this->error_result('Dynamic streaming parsing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Debug XML structure by reading first chunk
     */
    private function debug_xml_structure($xml_file_path) {
        $this->logger->info('=== XML STRUCTURE DEBUG ===');
        
        try {
            $handle = fopen($xml_file_path, 'r');
            $sample = fread($handle, 5120); // 5KB sample
            fclose($handle);
            
            $this->logger->info('XML file sample (first 500 chars)', [
                'sample' => substr($sample, 0, 500)
            ]);
            
            // Count occurrences of common elements
            $patterns = [
                'annuncio' => substr_count($sample, '<annuncio'),
                'immobile' => substr_count($sample, '<immobile'),
                'property' => substr_count($sample, '<property'),
                'listing' => substr_count($sample, '<listing'),
                'item' => substr_count($sample, '<item')
            ];
            
            $this->logger->info('Element patterns in sample', $patterns);
            
        } catch (Exception $e) {
            $this->logger->error('XML structure debug failed', ['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Parse XML using standard DOMDocument (for smaller files)
     */
    private function parse_xml_standard($xml_file_path) {
        // Use original parser for smaller files
        $original_parser = new TrentinoXmlParser($this->logger);
        return $original_parser->parse_xml_file($xml_file_path);
    }
    
    /**
     * Process field value based on field type
     */
    private function process_field_value($field_name, $value) {
        // Handle numeric values
        if (in_array($field_name, ['price', 'prezzo_vendita', 'prezzo_affitto', 'mq', 'superficie_commerciale', 'numero_camere', 'numero_bagni'])) {
            return $this->parse_numeric_value($value);
        }
        
        // Handle boolean values
        if (in_array($field_name, ['ascensore', 'giardino', 'piscina', 'garage', 'aria_condizionata'])) {
            return $this->parse_boolean_value($value);
        }
        
        return $value;
    }
    
    /**
     * Parse numeric value from XML
     */
    private function parse_numeric_value($value) {
        if (empty($value)) {
            return null;
        }
        
        $cleaned = preg_replace('/[€$,\s]/', '', $value);
        
        if (is_numeric($cleaned)) {
            return strpos($cleaned, '.') !== false ? (float)$cleaned : (int)$cleaned;
        }
        
        return null;
    }
    
    /**
     * Parse boolean value from XML
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
     * Process completed property
     */
    private function process_property($property_data, &$properties) {
        // Add derived fields
        $property_data = $this->add_derived_fields($property_data);
        
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
                'property_id' => $property_data['id'] ?? 'unknown',
                'errors' => $validation_result['errors']
            ];
        }
    }
    
    /**
     * Add derived fields to property data
     */
    private function add_derived_fields($property) {
        // Add WP-compatible category
        if (isset($property['categorie_id'])) {
            $property['wp_category'] = $this->property_categories[$property['categorie_id']] ?? 'property';
        }
        
        // Add price display
        if (isset($property['price']) && $property['price'] > 0) {
            $property['price_display'] = '€ ' . number_format($property['price'], 0, ',', '.');
            $property['listing_type'] = 'rent'; // Default for GestionaleImmobiliare
        }
        
        // Add full address
        $address_parts = array_filter([
            $property['indirizzo'] ?? '',
            $property['zona'] ?? '',
            $property['comune'] ?? ''
        ]);
        $property['full_address'] = implode(', ', $address_parts);
        
        // Add unique hash for duplicate detection
        $property['content_hash'] = md5(serialize(array_intersect_key($property, array_flip($this->required_fields))));
        
        return $property;
    }
    
    /**
     * Validate property data
     */
    private function validate_property($property) {
        $errors = [];
        
        // Check required fields
        foreach ($this->required_fields as $field) {
            if (!isset($property[$field]) || empty($property[$field])) {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Validate category
        if (isset($property['categorie_id']) && !isset($this->property_categories[$property['categorie_id']])) {
            $errors[] = "Unknown property category: {$property['categorie_id']}";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Check if property passes province filter
     */
    private function passes_province_filter($property) {
        if (empty($this->enabled_provinces)) {
            return true;
        }
        
        // For GestionaleImmobiliare, need to extract province from comune or other fields
        $property_province = '';
        
        // Try to extract province from available fields
        if (isset($property['provincia'])) {
            $property_province = $property['provincia'];
        } elseif (isset($property['comune'])) {
            // Parse province from comune field if needed
            $comune = $property['comune'];
            if (strpos($comune, 'Trento') !== false || strpos($comune, 'TN') !== false) {
                $property_province = 'TN';
            } elseif (strpos($comune, 'Bolzano') !== false || strpos($comune, 'BZ') !== false) {
                $property_province = 'BZ';
            }
        }
        
        return in_array($property_province, $this->enabled_provinces);
    }
    
    /**
     * Get parsing statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Create success result array
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