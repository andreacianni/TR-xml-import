<?php
/**
 * WordPress Importer Class for Trentino Import Plugin
 * 
 * Handles the actual import of mapped property data into WordPress
 * as WpResidence posts. Manages post creation, updates, taxonomies,
 * meta fields, and duplicate handling.
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
 * TrentinoWpImporter Class
 * 
 * Manages WordPress import operations including:
 * - Post creation and updates
 * - Meta fields assignment
 * - Taxonomy terms creation and assignment
 * - Property features handling
 * - Duplicate detection and management
 * - Import statistics and reporting
 */
class TrentinoWpImporter {
    
    /**
     * Logger instance
     */
    private $logger;
    
    /**
     * Import configuration
     */
    private $config;
    
    /**
     * Import statistics
     */
    private $stats;
    
    /**
     * Property mapper instance
     */
    private $property_mapper;
    
    /**
     * Current import session ID
     */
    private $session_id;
    
    /**
     * WordPress post type for properties
     */
    private $post_type = 'estate_property';
    
    /**
     * Constructor
     *
     * @param TrentinoImportLogger $logger Logger instance
     * @param TrentinoPropertyMapper $property_mapper Property mapper instance
     */
    public function __construct($logger = null, $property_mapper = null) {
        $this->logger = $logger ?: trentino_import_logger();
        $this->property_mapper = $property_mapper;
        $this->init_importer();
    }
    
    /**
     * Initialize WordPress importer
     */
    private function init_importer() {
        $this->load_config();
        $this->reset_stats();
        
        $this->logger->debug('WordPress Importer initialized', [
            'post_type' => $this->post_type,
            'duplicate_action' => $this->config['duplicate_action'],
            'batch_size' => $this->config['batch_size']
        ]);
    }
    
    /**
     * Load importer configuration
     */
    private function load_config() {
        $defaults = [
            'duplicate_action' => 'update', // update, skip, create_new
            'batch_size' => 50,
            'create_missing_terms' => true,
            'update_existing_terms' => false,
            'assign_property_features' => true,
            'validate_before_import' => true,
            'backup_before_update' => false,
            'generate_thumbnails' => false, // For future image import
            'notify_on_errors' => true,
            'max_execution_time' => 300 // 5 minutes
        ];
        
        $this->config = get_option('trentino_import_wp_importer_config', $defaults);
    }
    
    /**
     * Reset import statistics
     */
    private function reset_stats() {
        $this->stats = [
            'start_time' => 0,
            'end_time' => 0,
            'duration' => 0,
            'total_properties' => 0,
            'imported_properties' => 0,
            'updated_properties' => 0,
            'skipped_properties' => 0,
            'failed_properties' => 0,
            'created_terms' => 0,
            'assigned_features' => 0,
            'errors' => [],
            'memory_peak' => 0
        ];
    }
    
    /**
     * Import array of mapped properties into WordPress
     *
     * @param array $mapped_properties Array of mapped property data
     * @param string $session_id Optional session ID for logging
     * @return array Import result with statistics
     */
    public function import_properties($mapped_properties, $session_id = null) {
        $this->session_id = $session_id ?: uniqid('import_', true);
        
        $this->logger->info('Starting WordPress import', [
            'session_id' => $this->session_id,
            'properties_count' => count($mapped_properties),
            'post_type' => $this->post_type
        ]);
        
        $this->reset_stats();
        $this->stats['start_time'] = microtime(true);
        $this->stats['total_properties'] = count($mapped_properties);
        
        // Process properties in batches
        $batch_size = $this->config['batch_size'];
        $batches = array_chunk($mapped_properties, $batch_size);
        
        foreach ($batches as $batch_index => $batch) {
            $this->logger->debug('Processing batch', [
                'session_id' => $this->session_id,
                'batch_index' => $batch_index + 1,
                'batch_size' => count($batch),
                'total_batches' => count($batches)
            ]);
            
            foreach ($batch as $property_index => $mapped_property) {
                try {
                    $result = $this->import_single_property($mapped_property, $property_index);
                    
                    if ($result['success']) {
                        if ($result['action'] === 'created') {
                            $this->stats['imported_properties']++;
                        } else if ($result['action'] === 'updated') {
                            $this->stats['updated_properties']++;
                        } else {
                            $this->stats['skipped_properties']++;
                        }
                    } else {
                        $this->stats['failed_properties']++;
                        $this->stats['errors'][] = [
                            'property_index' => $property_index,
                            'property_id' => $mapped_property['source_data']['id'] ?? 'unknown',
                            'error' => $result['error']
                        ];
                    }
                    
                } catch (Exception $e) {
                    $this->stats['failed_properties']++;
                    $this->stats['errors'][] = [
                        'property_index' => $property_index,
                        'property_id' => $mapped_property['source_data']['id'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ];
                    
                    $this->logger->error('Property import exception', [
                        'session_id' => $this->session_id,
                        'property_index' => $property_index,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            
            // Log batch completion
            $this->logger->debug('Batch completed', [
                'session_id' => $this->session_id,
                'batch_index' => $batch_index + 1,
                'memory_usage' => size_format(memory_get_usage(true))
            ]);
        }
        
        // Calculate final statistics
        $this->stats['end_time'] = microtime(true);
        $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];
        $this->stats['memory_peak'] = memory_get_peak_usage(true);
        
        $this->logger->info('WordPress import completed', [
            'session_id' => $this->session_id,
            'total_properties' => $this->stats['total_properties'],
            'imported_properties' => $this->stats['imported_properties'],
            'updated_properties' => $this->stats['updated_properties'],
            'skipped_properties' => $this->stats['skipped_properties'],
            'failed_properties' => $this->stats['failed_properties'],
            'duration' => round($this->stats['duration'], 2) . 's',
            'memory_peak' => size_format($this->stats['memory_peak']),
            'errors_count' => count($this->stats['errors'])
        ]);
        
        return [
            'success' => true,
            'session_id' => $this->session_id,
            'stats' => $this->stats
        ];
    }
    
    /**
     * Import single mapped property into WordPress
     *
     * @param array $mapped_property Mapped property data
     * @param int $index Property index for logging
     * @return array Import result
     */
    private function import_single_property($mapped_property, $index = 0) {
        // Validate mapped property if enabled
        if ($this->config['validate_before_import']) {
            if ($this->property_mapper) {
                $validation = $this->property_mapper->validate_mapped_property($mapped_property);
                if (!$validation['valid']) {
                    return [
                        'success' => false,
                        'error' => 'Validation failed: ' . implode(', ', $validation['errors'])
                    ];
                }
            }
        }
        
        // Get import ID for duplicate checking
        $import_id = $mapped_property['source_data']['id'] ?? null;
        if (!$import_id) {
            return [
                'success' => false,
                'error' => 'Missing import ID'
            ];
        }
        
        // Check for existing property
        $existing_post_id = $this->find_existing_property($import_id);
        
        if ($existing_post_id) {
            // Handle duplicate based on configuration
            return $this->handle_duplicate_property($existing_post_id, $mapped_property, $import_id);
        } else {
            // Create new property
            return $this->create_new_property($mapped_property, $import_id);
        }
    }
    
    /**
     * Find existing property by import ID
     *
     * @param string $import_id Import ID from source
     * @return int|null WordPress post ID or null if not found
     */
    private function find_existing_property($import_id) {
        if ($this->property_mapper) {
            return $this->property_mapper->find_existing_property($import_id);
        }
        
        // Fallback method
        $query = new WP_Query([
            'post_type' => $this->post_type,
            'meta_query' => [
                [
                    'key' => 'property_import_id',
                    'value' => $import_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);
        
        return $query->have_posts() ? $query->posts[0] : null;
    }
    
    /**
     * Handle duplicate property based on configuration
     *
     * @param int $existing_post_id Existing WordPress post ID
     * @param array $mapped_property New mapped property data
     * @param string $import_id Import ID
     * @return array Import result
     */
    private function handle_duplicate_property($existing_post_id, $mapped_property, $import_id) {
        switch ($this->config['duplicate_action']) {
            case 'update':
                return $this->update_existing_property($existing_post_id, $mapped_property, $import_id);
                
            case 'skip':
                $this->logger->debug('Property skipped - duplicate found', [
                    'session_id' => $this->session_id,
                    'import_id' => $import_id,
                    'existing_post_id' => $existing_post_id
                ]);
                
                return [
                    'success' => true,
                    'action' => 'skipped',
                    'post_id' => $existing_post_id,
                    'message' => 'Duplicate skipped'
                ];
                
            case 'create_new':
                // Remove import ID to force new creation
                unset($mapped_property['meta_fields']['property_import_id']);
                return $this->create_new_property($mapped_property, $import_id . '_' . time());
                
            default:
                return [
                    'success' => false,
                    'error' => 'Invalid duplicate action configuration'
                ];
        }
    }
    
    /**
     * Create new property post
     *
     * @param array $mapped_property Mapped property data
     * @param string $import_id Import ID
     * @return array Creation result
     */
    private function create_new_property($mapped_property, $import_id) {
        $this->logger->debug('Creating new property', [
            'session_id' => $this->session_id,
            'import_id' => $import_id,
            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'
        ]);
        
        // Insert post
        $post_id = wp_insert_post($mapped_property['post_data'], true);
        
        if (is_wp_error($post_id)) {
            return [
                'success' => false,
                'error' => 'Post creation failed: ' . $post_id->get_error_message()
            ];
        }
        
        // Add meta fields
        $this->assign_meta_fields($post_id, $mapped_property['meta_fields'] ?? []);
        
        // Assign taxonomies
        $this->assign_taxonomies($post_id, $mapped_property['taxonomies'] ?? []);
        
        // Assign property features
        if ($this->config['assign_property_features']) {
            $this->assign_property_features($post_id, $mapped_property['features'] ?? []);
        }
        
        // Add custom fields
        $this->assign_custom_fields($post_id, $mapped_property['custom_fields'] ?? []);
        
        $this->logger->info('Property created successfully', [
            'session_id' => $this->session_id,
            'import_id' => $import_id,
            'post_id' => $post_id,
            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'
        ]);
        
        return [
            'success' => true,
            'action' => 'created',
            'post_id' => $post_id,
            'message' => 'Property created successfully'
        ];
    }
    
    /**
     * Update existing property post
     *
     * @param int $post_id Existing WordPress post ID
     * @param array $mapped_property New mapped property data
     * @param string $import_id Import ID
     * @return array Update result
     */
    private function update_existing_property($post_id, $mapped_property, $import_id) {
        // Check if content has changed
        if ($this->property_mapper && isset($mapped_property['content_hash'])) {
            if (!$this->property_mapper->has_content_changed($post_id, $mapped_property['content_hash'])) {
                $this->logger->debug('Property content unchanged - skipping update', [
                    'session_id' => $this->session_id,
                    'import_id' => $import_id,
                    'post_id' => $post_id
                ]);
                
                // Update only the sync timestamp
                update_post_meta($post_id, 'property_last_sync', current_time('mysql'));
                
                return [
                    'success' => true,
                    'action' => 'skipped',
                    'post_id' => $post_id,
                    'message' => 'No changes detected'
                ];
            }
        }
        
        $this->logger->debug('Updating existing property', [
            'session_id' => $this->session_id,
            'import_id' => $import_id,
            'post_id' => $post_id,
            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'
        ]);
        
        // Backup existing post if enabled
        if ($this->config['backup_before_update']) {
            $this->backup_post_data($post_id);
        }
        
        // Update post data
        $post_data = $mapped_property['post_data'];
        $post_data['ID'] = $post_id;
        
        $result = wp_update_post($post_data, true);
        
        if (is_wp_error($result)) {
            return [
                'success' => false,
                'error' => 'Post update failed: ' . $result->get_error_message()
            ];
        }
        
        // Update meta fields
        $this->assign_meta_fields($post_id, $mapped_property['meta_fields'] ?? []);
        
        // Update taxonomies
        $this->assign_taxonomies($post_id, $mapped_property['taxonomies'] ?? []);
        
        // Update property features
        if ($this->config['assign_property_features']) {
            $this->assign_property_features($post_id, $mapped_property['features'] ?? []);
        }
        
        // Update custom fields
        $this->assign_custom_fields($post_id, $mapped_property['custom_fields'] ?? []);
        
        // Update content hash
        if (isset($mapped_property['content_hash'])) {
            update_post_meta($post_id, 'property_import_hash', $mapped_property['content_hash']);
        }
        
        $this->logger->info('Property updated successfully', [
            'session_id' => $this->session_id,
            'import_id' => $import_id,
            'post_id' => $post_id,
            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'
        ]);
        
        return [
            'success' => true,
            'action' => 'updated',
            'post_id' => $post_id,
            'message' => 'Property updated successfully'
        ];
    }
    
    /**
     * Assign meta fields to post
     *
     * @param int $post_id WordPress post ID
     * @param array $meta_fields Meta fields array
     */
    private function assign_meta_fields($post_id, $meta_fields) {
        foreach ($meta_fields as $meta_key => $meta_value) {
            if ($meta_value !== null && $meta_value !== '') {
                update_post_meta($post_id, $meta_key, $meta_value);
            }
        }
    }
    
    /**
     * Assign taxonomies and terms to post
     *
     * @param int $post_id WordPress post ID
     * @param array $taxonomies Taxonomies array
     */
    private function assign_taxonomies($post_id, $taxonomies) {
        foreach ($taxonomies as $taxonomy => $terms) {
            if (empty($terms)) {
                continue;
            }
            
            $term_ids = [];
            
            foreach ($terms as $term_name) {
                $term_id = $this->get_or_create_term($term_name, $taxonomy);
                if ($term_id) {
                    $term_ids[] = $term_id;
                }
            }
            
            if (!empty($term_ids)) {
                wp_set_object_terms($post_id, $term_ids, $taxonomy);
            }
        }
    }
    
    /**
     * Get existing term ID or create new term
     *
     * @param string $term_name Term name
     * @param string $taxonomy Taxonomy name
     * @return int|null Term ID or null on failure
     */
    private function get_or_create_term($term_name, $taxonomy) {
        // Check if term exists
        $term = get_term_by('name', $term_name, $taxonomy);
        
        if ($term) {
            return $term->term_id;
        }
        
        // Create term if enabled
        if ($this->config['create_missing_terms']) {
            $result = wp_insert_term($term_name, $taxonomy);
            
            if (is_wp_error($result)) {
                $this->logger->warning('Failed to create term', [
                    'session_id' => $this->session_id,
                    'term_name' => $term_name,
                    'taxonomy' => $taxonomy,
                    'error' => $result->get_error_message()
                ]);
                return null;
            }
            
            $this->stats['created_terms']++;
            
            $this->logger->debug('Term created', [
                'session_id' => $this->session_id,
                'term_name' => $term_name,
                'taxonomy' => $taxonomy,
                'term_id' => $result['term_id']
            ]);
            
            return $result['term_id'];
        }
        
        return null;
    }
    
    /**
     * Assign property features to post
     *
     * @param int $post_id WordPress post ID
     * @param array $features Features array
     */
    private function assign_property_features($post_id, $features) {
        if (empty($features)) {
            return;
        }
        
        $feature_term_ids = [];
        
        foreach ($features as $feature_slug) {
            $term_id = $this->get_or_create_term($feature_slug, 'property_features');
            if ($term_id) {
                $feature_term_ids[] = $term_id;
            }
        }
        
        if (!empty($feature_term_ids)) {
            wp_set_object_terms($post_id, $feature_term_ids, 'property_features');
            $this->stats['assigned_features'] += count($feature_term_ids);
        }
    }
    
    /**
     * Assign custom fields to post
     *
     * @param int $post_id WordPress post ID
     * @param array $custom_fields Custom fields array
     */
    private function assign_custom_fields($post_id, $custom_fields) {
        foreach ($custom_fields as $field_key => $field_value) {
            if ($field_value !== null && $field_value !== '') {
                update_post_meta($post_id, $field_key, $field_value);
            }
        }
    }
    
    /**
     * Backup post data before update
     *
     * @param int $post_id WordPress post ID
     */
    private function backup_post_data($post_id) {
        $backup_data = [
            'post' => get_post($post_id, ARRAY_A),
            'meta' => get_post_meta($post_id),
            'terms' => wp_get_object_terms($post_id, get_object_taxonomies($this->post_type)),
            'timestamp' => current_time('mysql')
        ];
        
        update_post_meta($post_id, '_trentino_import_backup', $backup_data);
    }
    
    /**
     * Get import statistics
     *
     * @return array Statistics
     */
    public function get_stats() {
        return $this->stats;
    }
    
    /**
     * Get import configuration
     *
     * @return array Configuration
     */
    public function get_config() {
        return $this->config;
    }
    
    /**
     * Update import configuration
     *
     * @param array $config New configuration
     * @return bool Success status
     */
    public function update_config($config) {
        $this->config = array_merge($this->config, $config);
        $success = update_option('trentino_import_wp_importer_config', $this->config);
        
        if ($success) {
            $this->logger->info('WordPress Importer configuration updated', [
                'updated_keys' => array_keys($config)
            ]);
        }
        
        return $success;
    }
    
    /**
     * Create property - Method required by ChunkedImportEngine
     *
     * @param array $property_data Property data from XML
     * @return array Result with success status and post_id
     */
    public function create_property($property_data) {
        try {
            // Use the main import method for single property
            $result = $this->import_single_property([
                'post_data' => [
                    'post_type' => 'estate_property',
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'post_title' => $property_data['abstract'] ?? $property_data['seo_title'] ?? 'Proprietà',
                    'post_content' => $property_data['description'] ?? '',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ],
                'meta_fields' => [
                    'property_price' => $property_data['price'] ?? null,
                    'property_size' => $property_data['mq'] ?? null,
                    'property_import_id' => $property_data['id'],
                    'property_import_source' => 'GestionaleImmobiliare',
                    'property_import_date' => current_time('mysql')
                ],
                'taxonomies' => [],
                'features' => [],
                'custom_fields' => [],
                'source_data' => $property_data
            ], 0);
            
            return [
                'success' => $result['success'],
                'post_id' => $result['post_id'] ?? null,
                'action' => $result['action'] ?? 'unknown',
                'message' => $result['message'] ?? $result['error'] ?? 'Unknown result'
            ];
            
        } catch (Exception $e) {
            $this->logger->error('create_property failed', [
                'property_id' => $property_data['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'post_id' => null,
                'message' => 'Create failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Update property - Method required by ChunkedImportEngine
     *
     * @param int $post_id WordPress post ID
     * @param array $property_data Property data from XML
     * @return array Result with success status
     */
    public function update_property($post_id, $property_data) {
        try {
            // Use the main update method
            $result = $this->update_existing_property($post_id, [
                'post_data' => [
                    'ID' => $post_id,
                    'post_title' => $property_data['abstract'] ?? $property_data['seo_title'] ?? 'Proprietà',
                    'post_content' => $property_data['description'] ?? '',
                ],
                'meta_fields' => [
                    'property_price' => $property_data['price'] ?? null,
                    'property_size' => $property_data['mq'] ?? null,
                    'property_last_sync' => current_time('mysql')
                ],
                'taxonomies' => [],
                'features' => [],
                'custom_fields' => [],
                'source_data' => $property_data,
                'content_hash' => md5(serialize($property_data))
            ], $property_data['id'] ?? 'unknown');
            
            return [
                'success' => $result['success'],
                'post_id' => $post_id,
                'action' => $result['action'] ?? 'unknown',
                'message' => $result['message'] ?? $result['error'] ?? 'Unknown result'
            ];
            
        } catch (Exception $e) {
            $this->logger->error('update_property failed', [
                'post_id' => $post_id,
                'property_id' => $property_data['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'post_id' => $post_id,
                'message' => 'Update failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Clean up orphaned import data
     *
     * @param int $days_old Remove data older than X days
     * @return array Cleanup result
     */
    public function cleanup_import_data($days_old = 30) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $days_old . ' days'));
        
        // Find posts with old import data
        $query = new WP_Query([
            'post_type' => $this->post_type,
            'meta_query' => [
                [
                    'key' => 'property_import_date',
                    'value' => $cutoff_date,
                    'compare' => '<',
                    'type' => 'DATETIME'
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        $cleaned_posts = 0;
        
        foreach ($query->posts as $post_id) {
            delete_post_meta($post_id, '_trentino_import_backup');
            $cleaned_posts++;
        }
        
        $this->logger->info('Import data cleanup completed', [
            'days_old' => $days_old,
            'cleaned_posts' => $cleaned_posts
        ]);
        
        return [
            'success' => true,
            'cleaned_posts' => $cleaned_posts,
            'cutoff_date' => $cutoff_date
        ];
    }
}
