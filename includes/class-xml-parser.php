<?php
/**
 * XML Parser Class for Trentino Import Plugin - CORRECTED VERSION
 * 
 * Handles parsing and validation of XML data from GestionaleImmobiliare.it
 * Updated to handle the correct nested XML structure: <dataset><annuncio><info>
 * 
 * @package TrentinoImport
 * @version 1.1.0 - FIXED XML STRUCTURE
 * @author Andrea Cianni - Novacom
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

/**
 * TrentinoXmlParser Class - CORRECTED FOR REAL XML STRUCTURE
 * 
 * NOW CORRECTLY HANDLES:
 * - <dataset><annuncio><info> structure
 * - <info_inserite><info id="X"><valore_assegnato> features
 * - <dati_inseriti><dati id="X"><valore_assegnato> numeric data
 * - <file_allegati> media files
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
     * Property categories mapping (from GI documentation)
     */
    private $property_categories;
    
    /**
     * Required fields for validation (corrected field names)
     */
    private $required_fields;
    
    /**
     * Constructor
     */
    public function __construct($logger = null) {
        $this->logger = $logger ?: trentino_import_logger();
        $this->init_parser();
    }
    
    /**
     * Initialize parser with correct GI structure
     */
    private function init_parser() {
        $this->load_config();
        $this->load_province_filter();
        $this->load_property_categories();
        $this->load_required_fields();
        $this->reset_stats();
        
        $this->logger->debug('XML Parser initialized - CORRECTED VERSION', [
            'enabled_provinces' => $this->enabled_provinces,
            'structure' => 'dataset>annuncio>info',
            'memory_limit' => ini_get('memory_limit')
        ]);
    }
    
    /**
     * Load parser configuration
     */
    private function load_config() {
        $defaults = [
            'validate_xml' => true,
            'memory_limit' => '512M', // Increased for real data
            'chunk_size' => 50,       // Smaller chunks for stability
            'max_properties' => 50000, // Increased for real data
            'encoding' => 'UTF-8',
            'strict_validation' => false,
            'debug_mode' => true // Enable for debugging
        ];
        
        $this->config = get_option('trentino_import_parser_config', $defaults);
    }
    
    /**
     * Load province filter - Trentino specific
     */
    private function load_province_filter() {
        $default_provinces = ['TN', 'BZ']; // Trentino Alto-Adige
        $this->enabled_provinces = get_option('trentino_import_enabled_provinces', $default_provinces);
    }
    
    /**
     * Load property categories mapping - FROM GI DOCUMENTATION
     */
    private function load_property_categories() {
        $this->property_categories = [
            1 => 'house',           // casa singola
            2 => 'house',           // bifamiliare  
            3 => 'house',           // trifamiliare
            4 => 'house',           // casa a schiera
            11 => 'apartment',      // appartamento ⭐ MAIN
            12 => 'penthouse',      // attico ⭐ MAIN
            18 => 'villa',          // villa ⭐ MAIN
            19 => 'land',           // terreno ⭐ MAIN
            14 => 'commercial',     // negozio ⭐ MAIN
            17 => 'office',         // ufficio
            8 => 'garage',          // garage
            21 => 'garage',         // posto auto
            13 => 'house',          // rustico
            23 => 'apartment',      // loft
            26 => 'commercial'      // palazzo
        ];
    }
    
    /**
     * Load required fields - CORRECTED FIELD NAMES FROM GI DOCS
     */
    private function load_required_fields() {
        $this->required_fields = [
            'id',           // ID annuncio (obbligatorio GI)
            'categorie_id', // Categoria (obbligatorio GI)
            'mq',           // Metri quadri (obbligatorio GI)
            'price'         // Prezzo (obbligatorio GI)
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
            'errors' => [],
            'provinces' => [],
            'categories' => [],
            'sample_property' => null
        ];
    }
    
    /**
     * Parse XML file - CORRECTED FOR GI STRUCTURE
     */
    public function parse_xml_file($xml_file_path) {
        $this->logger->info('Starting XML parsing - CORRECTED VERSION', [
            'file_path' => $xml_file_path,
            'file_size' => file_exists($xml_file_path) ? size_format(filesize($xml_file_path)) : 'unknown',
            'expected_structure' => '<dataset><annuncio><info>'
        ]);
        
        if (!file_exists($xml_file_path)) {
            return $this->error_result('XML file not found: ' . $xml_file_path);
        }
        
        $file_size = filesize($xml_file_path);
        if (!$this->check_memory_requirements($file_size)) {
            return $this->error_result('Insufficient memory for parsing this XML file');
        }
        
        $this->reset_stats();
        $this->stats['start_time'] = microtime(true);
        
        // Set memory limit
        if (!empty($this->config['memory_limit'])) {
            ini_set('memory_limit', $this->config['memory_limit']);
        }
        
        try {
            // Load XML with corrected validation
            $load_result = $this->load_xml_file($xml_file_path);
            if (!$load_result['success']) {
                return $load_result;
            }
            
            // Extract properties with corrected structure
            $extraction_result = $this->extract_properties_corrected();
            if (!$extraction_result['success']) {
                return $extraction_result;
            }
            
            // Calculate statistics
            $this->stats['end_time'] = microtime(true);
            $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];
            $this->stats['memory_peak'] = memory_get_peak_usage(true);
            
            $this->logger->info('XML parsing completed - CORRECTED VERSION', [
                'total_properties' => $this->stats['total_properties'],
                'valid_properties' => $this->stats['valid_properties'],
                'provinces_found' => array_keys($this->stats['provinces']),
                'categories_found' => array_keys($this->stats['categories']),
                'duration' => round($this->stats['duration'], 2) . 's',
                'memory_peak' => size_format($this->stats['memory_peak'])
            ]);
            
            return $this->success_result($extraction_result['properties']);
            
        } catch (Exception $e) {
            $this->logger->error('XML parsing failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return $this->error_result('Parsing failed: ' . $e->getMessage());
        } finally {
            $this->xml_doc = null;
        }
    }
    
    /**
     * Check memory requirements
     */
    private function check_memory_requirements($file_size) {
        $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
        $current_usage = memory_get_usage(true);
        $estimated_usage = $file_size * 4; // XML parsing uses more memory
        $required_memory = $current_usage + $estimated_usage;
        
        $this->logger->debug('Memory check', [
            'file_size' => size_format($file_size),
            'memory_limit' => size_format($memory_limit),
            'estimated_usage' => size_format($estimated_usage),
            'sufficient' => $required_memory < ($memory_limit * 0.8) // 80% safety margin
        ]);
        
        return $required_memory < ($memory_limit * 0.8);
    }
    
    /**
     * Load XML file with corrected validation
     */
    private function load_xml_file($xml_file_path) {
        $this->logger->debug('Loading XML file with corrected validation');
        
        libxml_use_internal_errors(true);
        libxml_clear_errors();
        
        $this->xml_doc = new DOMDocument('1.0', 'UTF-8');
        $this->xml_doc->preserveWhiteSpace = false;
        $this->xml_doc->formatOutput = false;
        
        $loaded = $this->xml_doc->load($xml_file_path, LIBXML_NOCDATA | LIBXML_NOBLANKS);
        
        if (!$loaded) {
            $xml_errors = libxml_get_errors();
            $error_messages = [];
            foreach ($xml_errors as $error) {
                $error_messages[] = "Line {$error->line}: {$error->message}";
            }
            libxml_clear_errors();
            return $this->error_result('Invalid XML: ' . implode('; ', $error_messages));
        }
        
        // Validate CORRECT GI structure
        if ($this->config['validate_xml']) {
            $validation_result = $this->validate_gi_xml_structure();
            if (!$validation_result['success']) {
                return $validation_result;
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * Validate GI XML structure - CORRECTED FOR REAL STRUCTURE
     */
    private function validate_gi_xml_structure() {
        $this->logger->debug('Validating GI XML structure');
        
        $root = $this->xml_doc->documentElement;
        if (!$root) {
            return $this->error_result('XML has no root element');
        }
        
        $this->logger->debug('Root element found', ['root_name' => $root->nodeName]);
        
        // Look for <dataset> root or direct <annuncio> elements
        $annunci = null;
        if ($root->nodeName === 'dataset') {
            $annunci = $root->getElementsByTagName('annuncio');
        } else {
            // Maybe root is different, try to find annuncio anywhere
            $annunci = $this->xml_doc->getElementsByTagName('annuncio');
        }
        
        if (!$annunci || $annunci->length === 0) {
            return $this->error_result('No <annuncio> elements found in XML. Expected GI structure: <dataset><annuncio>');
        }
        
        // Check first annuncio for <info> section
        $first_annuncio = $annunci->item(0);
        $info_elements = $first_annuncio->getElementsByTagName('info');
        
        if ($info_elements->length === 0) {
            return $this->error_result('No <info> section found in <annuncio>. Expected: <annuncio><info>');
        }
        
        $this->logger->info('GI XML structure validation passed', [
            'root_element' => $root->nodeName,
            'annunci_count' => $annunci->length,
            'first_annuncio_has_info' => $info_elements->length > 0
        ]);
        
        return ['success' => true];
    }
    
    /**
     * Extract properties - CORRECTED FOR GI STRUCTURE
     */
    private function extract_properties_corrected() {
        $this->logger->debug('Starting property extraction - CORRECTED VERSION');
        
        $properties = [];
        
        // Get all <annuncio> elements - CORRECT PATH
        $annuncio_elements = $this->xml_doc->getElementsByTagName('annuncio');
        $this->stats['total_properties'] = $annuncio_elements->length;
        
        $this->logger->info('Found annunci in XML', [
            'total_count' => $this->stats['total_properties']
        ]);
        
        if ($this->stats['total_properties'] === 0) {
            return $this->error_result('No <annuncio> elements found in XML');
        }
        
        foreach ($annuncio_elements as $index => $annuncio) {
            try {
                // Extract property data from <annuncio> - CORRECTED METHOD
                $property_data = $this->extract_annuncio_data($annuncio);
                
                if ($property_data) {
                    // Update statistics
                    $this->update_stats($property_data);
                    
                    // Save first property as sample
                    if ($this->stats['sample_property'] === null) {
                        $this->stats['sample_property'] = $property_data;
                    }
                    
                    // Validate property
                    $validation_result = $this->validate_property_corrected($property_data);
                    
                    if ($validation_result['valid']) {
                        // Apply province filter if available
                        if ($this->passes_province_filter_corrected($property_data)) {
                            $properties[] = $property_data;
                            $this->stats['valid_properties']++;
                        } else {
                            $this->stats['filtered_properties']++;
                        }
                    } else {
                        $this->stats['invalid_properties']++;
                        if ($this->config['debug_mode']) {
                            $this->logger->warning('Property validation failed', [
                                'property_index' => $index,
                                'property_id' => $property_data['id'] ?? 'unknown',
                                'errors' => $validation_result['errors']
                            ]);
                        }
                    }
                } else {
                    $this->stats['invalid_properties']++;
                }
                
                // Progress logging
                if (($index + 1) % $this->config['chunk_size'] === 0) {
                    $this->logger->debug('Progress', [
                        'processed' => $index + 1,
                        'total' => $this->stats['total_properties'],
                        'valid' => $this->stats['valid_properties'],
                        'memory_usage' => size_format(memory_get_usage(true))
                    ]);
                }
                
            } catch (Exception $e) {
                $this->stats['invalid_properties']++;
                $this->logger->warning('Property extraction error', [
                    'property_index' => $index,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $this->success_result($properties);
    }
    
    /**
     * Extract data from <annuncio> element - CORRECT GI STRUCTURE
     */
    private function extract_annuncio_data($annuncio) {
        $property = [];
        
        // 1. Extract <info> section - BASE PROPERTY DATA
        $info_elements = $annuncio->getElementsByTagName('info');
        if ($info_elements->length > 0) {
            $info = $info_elements->item(0);
            foreach ($info->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $tag_name = $child->nodeName;
                    $value = trim($child->textContent);
                    
                    // Handle CDATA sections
                    if (empty($value) && $child->hasChildNodes()) {
                        foreach ($child->childNodes as $textNode) {
                            if ($textNode->nodeType === XML_CDATA_SECTION_NODE || $textNode->nodeType === XML_TEXT_NODE) {
                                $value = trim($textNode->textContent);
                                break;
                            }
                        }
                    }
                    
                    // Parse values according to type
                    if (in_array($tag_name, ['price', 'price_real', 'mq', 'age', 'ipe', 'spese_condominiali'])) {
                        $value = $this->parse_numeric_value($value);
                    } else if (in_array($tag_name, ['latitude', 'longitude'])) {
                        $value = $this->parse_float_value($value);
                    } else if (in_array($tag_name, ['categorie_id', 'categorie_micro_id', 'zona_id', 'comune_istat', 'civico', 'unita_abitative'])) {
                        $value = $this->parse_int_value($value);
                    }
                    
                    $property[$tag_name] = $value;
                }
            }
        }
        
        // 2. Extract <info_inserite> section - FEATURES
        $info_inserite = $annuncio->getElementsByTagName('info_inserite');
        if ($info_inserite->length > 0) {
            $property['features'] = [];
            $info_items = $info_inserite->item(0)->getElementsByTagName('info');
            
            foreach ($info_items as $info_item) {
                $feature_id = $info_item->getAttribute('id');
                $feature_value = null;
                
                $valore_elements = $info_item->getElementsByTagName('valore_assegnato');
                if ($valore_elements->length > 0) {
                    $feature_value = $valore_elements->item(0)->textContent;
                }
                
                if ($feature_id && $feature_value !== null) {
                    $property['features'][$feature_id] = $this->parse_int_value($feature_value);
                }
            }
        }
        
        // 3. Extract <dati_inseriti> section - NUMERIC DATA
        $dati_inseriti = $annuncio->getElementsByTagName('dati_inseriti');
        if ($dati_inseriti->length > 0) {
            $property['numeric_data'] = [];
            $dati_items = $dati_inseriti->item(0)->getElementsByTagName('dati');
            
            foreach ($dati_items as $dati_item) {
                $data_id = $dati_item->getAttribute('id');
                $data_value = null;
                
                $valore_elements = $dati_item->getElementsByTagName('valore_assegnato');
                if ($valore_elements->length > 0) {
                    $data_value = $valore_elements->item(0)->textContent;
                }
                
                if ($data_id && $data_value !== null) {
                    $property['numeric_data'][$data_id] = $this->parse_numeric_value($data_value);
                }
            }
        }
        
        // 4. Extract <file_allegati> section - MEDIA
        $file_allegati = $annuncio->getElementsByTagName('file_allegati');
        if ($file_allegati->length > 0) {
            $property['media'] = [];
            $allegati = $file_allegati->item(0)->getElementsByTagName('allegato');
            
            foreach ($allegati as $allegato) {
                $allegato_id = $allegato->getAttribute('id');
                $allegato_type = $allegato->getAttribute('type');
                
                $file_path_elements = $allegato->getElementsByTagName('file_path');
                if ($file_path_elements->length > 0) {
                    $file_path = $file_path_elements->item(0)->textContent;
                    $property['media'][] = [
                        'id' => $allegato_id,
                        'type' => $allegato_type,
                        'url' => $file_path
                    ];
                }
            }
        }
        
        // 5. Add derived fields for WP compatibility
        $property = $this->add_derived_fields_corrected($property);
        
        return !empty($property) ? $property : null;
    }
    
    /**
     * Parse numeric value
     */
    private function parse_numeric_value($value) {
        if (empty($value)) return null;
        $cleaned = preg_replace('/[€$,\s]/', '', $value);
        return is_numeric($cleaned) ? (strpos($cleaned, '.') !== false ? (float)$cleaned : (int)$cleaned) : null;
    }
    
    /**
     * Parse float value
     */
    private function parse_float_value($value) {
        if (empty($value)) return null;
        return is_numeric($value) ? (float)$value : null;
    }
    
    /**
     * Parse int value
     */
    private function parse_int_value($value) {
        if (empty($value)) return null;
        return is_numeric($value) ? (int)$value : null;
    }
    
    /**
     * Add derived fields - CORRECTED FOR GI DATA
     */
    private function add_derived_fields_corrected($property) {
        // Add WP-compatible category
        if (isset($property['categorie_id'])) {
            $property['wp_category'] = $this->property_categories[$property['categorie_id']] ?? 'property';
        }
        
        // Add human-readable features
        if (isset($property['features'])) {
            $property['readable_features'] = $this->map_features_to_readable($property['features']);
        }
        
        // Extract key features for easy access
        if (isset($property['features'])) {
            $property['bathrooms'] = $property['features'][1] ?? null; // ID 1 = bagni
            $property['bedrooms'] = $property['features'][2] ?? null;  // ID 2 = camere
            $property['elevator'] = $property['features'][13] ?? null; // ID 13 = ascensore
            $property['garden'] = $property['features'][17] ?? null;   // ID 17 = giardino
            $property['pool'] = $property['features'][66] ?? null;     // ID 66 = piscina
            $property['garage'] = $property['features'][5] ?? null;    // ID 5 = garage
        }
        
        // Extract key numeric data
        if (isset($property['numeric_data'])) {
            $property['garden_size'] = $property['numeric_data'][4] ?? null;      // ID 4 = mq giardino
            $property['commercial_size'] = $property['numeric_data'][20] ?? null; // ID 20 = superficie commerciale
            $property['useful_size'] = $property['numeric_data'][21] ?? null;     // ID 21 = superficie utile
        }
        
        // Add price display
        if (isset($property['price']) && $property['price'] > 0) {
            $property['price_display'] = '€ ' . number_format($property['price'], 0, ',', '.');
        }
        
        return $property;
    }
    
    /**
     * Map feature IDs to readable names
     */
    private function map_features_to_readable($features) {
        $feature_map = [
            1 => 'bagni', 2 => 'camere', 3 => 'cucina', 4 => 'soggiorno', 5 => 'garage',
            13 => 'ascensore', 14 => 'aria_condizionata', 17 => 'giardino', 20 => 'posto_auto',
            24 => 'terrazzi', 25 => 'poggioli', 33 => 'piano_numero', 36 => 'montagna',
            37 => 'lago', 41 => 'nuovo', 66 => 'piscina', 67 => 'porticato'
        ];
        
        $readable = [];
        foreach ($features as $id => $value) {
            $name = $feature_map[$id] ?? "feature_$id";
            $readable[$name] = $value;
        }
        
        return $readable;
    }
    
    /**
     * Update statistics
     */
    private function update_stats($property) {
        // Count provinces
        if (isset($property['indirizzo'])) {
            // Try to extract province from address or other fields
            $province = $this->extract_province_from_property($property);
            if ($province) {
                $this->stats['provinces'][$province] = ($this->stats['provinces'][$province] ?? 0) + 1;
            }
        }
        
        // Count categories
        if (isset($property['categorie_id'])) {
            $cat_id = $property['categorie_id'];
            $this->stats['categories'][$cat_id] = ($this->stats['categories'][$cat_id] ?? 0) + 1;
        }
    }
    
    /**
     * Extract province from property data
     */
    private function extract_province_from_property($property) {
        // Look in common places for province info
        $possible_fields = ['provincia', 'state', 'region'];
        
        foreach ($possible_fields as $field) {
            if (isset($property[$field]) && !empty($property[$field])) {
                return strtoupper(trim($property[$field]));
            }
        }
        
        // Try to extract from address or city
        if (isset($property['indirizzo'])) {
            if (strpos($property['indirizzo'], 'Trento') !== false || strpos($property['indirizzo'], 'TN') !== false) {
                return 'TN';
            }
            if (strpos($property['indirizzo'], 'Bolzano') !== false || strpos($property['indirizzo'], 'BZ') !== false) {
                return 'BZ';
            }
        }
        
        return null;
    }
    
    /**
     * Validate property - CORRECTED FIELDS
     */
    private function validate_property_corrected($property) {
        $errors = [];
        
        // Check required fields from GI documentation
        foreach ($this->required_fields as $field) {
            if (!isset($property[$field]) || $property[$field] === null || $property[$field] === '') {
                $errors[] = "Missing required field: {$field}";
            }
        }
        
        // Validate category exists
        if (isset($property['categorie_id']) && !isset($this->property_categories[$property['categorie_id']])) {
            // Don't error for unknown categories, just log
            $this->logger->debug('Unknown category found', [
                'categorie_id' => $property['categorie_id'],
                'property_id' => $property['id'] ?? 'unknown'
            ]);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Province filter - CORRECTED
     */
    private function passes_province_filter_corrected($property) {
        if (empty($this->enabled_provinces)) {
            return true;
        }
        
        $property_province = $this->extract_province_from_property($property);
        return $property_province ? in_array($property_province, $this->enabled_provinces) : true;
    }
    
    /**
     * Get parsing statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Create success result
     */
    private function success_result($properties = null, $extra_data = []) {
        return array_merge([
            'success' => true,
            'properties' => $properties,
            'stats' => $this->stats
        ], $extra_data);
    }
    
    /**
     * Create error result
     */
    private function error_result($error) {
        return [
            'success' => false,
            'error' => $error,
            'stats' => $this->stats
        ];
    }
    
    /**
     * Quick validation for testing
     */
    public function quick_validate_xml($xml_file_path) {
        if (!file_exists($xml_file_path)) {
            return $this->error_result('File not found');
        }
        
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $loaded = $doc->load($xml_file_path);
        
        if (!$loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            return $this->error_result('Invalid XML: ' . $errors[0]->message);
        }
        
        // Count annunci
        $annunci = $doc->getElementsByTagName('annuncio');
        
        return $this->success_result(null, [
            'property_count' => $annunci->length,
            'file_size' => filesize($xml_file_path),
            'structure' => 'dataset>annuncio detected'
        ]);
    }
}

// End of corrected XML parser
