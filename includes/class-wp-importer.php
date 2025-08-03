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
        
        // Set execution time limit
        if ($this->config['max_execution_time'] > 0) {\n            set_time_limit($this->config['max_execution_time']);\n        }\n        \n        $this->reset_stats();\n        $this->stats['start_time'] = microtime(true);\n        $this->stats['total_properties'] = count($mapped_properties);\n        \n        // Process properties in batches\n        $batch_size = $this->config['batch_size'];\n        $batches = array_chunk($mapped_properties, $batch_size);\n        \n        foreach ($batches as $batch_index => $batch) {\n            $this->logger->debug('Processing batch', [\n                'session_id' => $this->session_id,\n                'batch_index' => $batch_index + 1,\n                'batch_size' => count($batch),\n                'total_batches' => count($batches)\n            ]);\n            \n            foreach ($batch as $property_index => $mapped_property) {\n                try {\n                    $result = $this->import_single_property($mapped_property, $property_index);\n                    \n                    if ($result['success']) {\n                        if ($result['action'] === 'created') {\n                            $this->stats['imported_properties']++;\n                        } else if ($result['action'] === 'updated') {\n                            $this->stats['updated_properties']++;\n                        } else {\n                            $this->stats['skipped_properties']++;\n                        }\n                    } else {\n                        $this->stats['failed_properties']++;\n                        $this->stats['errors'][] = [\n                            'property_index' => $property_index,\n                            'property_id' => $mapped_property['source_data']['id_immobile'] ?? 'unknown',\n                            'error' => $result['error']\n                        ];\n                    }\n                    \n                } catch (Exception $e) {\n                    $this->stats['failed_properties']++;\n                    $this->stats['errors'][] = [\n                        'property_index' => $property_index,\n                        'property_id' => $mapped_property['source_data']['id_immobile'] ?? 'unknown',\n                        'error' => $e->getMessage()\n                    ];\n                    \n                    $this->logger->error('Property import exception', [\n                        'session_id' => $this->session_id,\n                        'property_index' => $property_index,\n                        'error' => $e->getMessage(),\n                        'trace' => $e->getTraceAsString()\n                    ]);\n                }\n            }\n            \n            // Log batch completion\n            $this->logger->debug('Batch completed', [\n                'session_id' => $this->session_id,\n                'batch_index' => $batch_index + 1,\n                'memory_usage' => size_format(memory_get_usage(true))\n            ]);\n        }\n        \n        // Calculate final statistics\n        $this->stats['end_time'] = microtime(true);\n        $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];\n        $this->stats['memory_peak'] = memory_get_peak_usage(true);\n        \n        $this->logger->info('WordPress import completed', [\n            'session_id' => $this->session_id,\n            'total_properties' => $this->stats['total_properties'],\n            'imported_properties' => $this->stats['imported_properties'],\n            'updated_properties' => $this->stats['updated_properties'],\n            'skipped_properties' => $this->stats['skipped_properties'],\n            'failed_properties' => $this->stats['failed_properties'],\n            'duration' => round($this->stats['duration'], 2) . 's',\n            'memory_peak' => size_format($this->stats['memory_peak']),\n            'errors_count' => count($this->stats['errors'])\n        ]);\n        \n        return [\n            'success' => true,\n            'session_id' => $this->session_id,\n            'stats' => $this->stats\n        ];\n    }\n    \n    /**\n     * Import single mapped property into WordPress\n     *\n     * @param array $mapped_property Mapped property data\n     * @param int $index Property index for logging\n     * @return array Import result\n     */\n    private function import_single_property($mapped_property, $index = 0) {\n        // Validate mapped property if enabled\n        if ($this->config['validate_before_import']) {\n            if ($this->property_mapper) {\n                $validation = $this->property_mapper->validate_mapped_property($mapped_property);\n                if (!$validation['valid']) {\n                    return [\n                        'success' => false,\n                        'error' => 'Validation failed: ' . implode(', ', $validation['errors'])\n                    ];\n                }\n            }\n        }\n        \n        // Get import ID for duplicate checking\n        $import_id = $mapped_property['source_data']['id_immobile'] ?? null;\n        if (!$import_id) {\n            return [\n                'success' => false,\n                'error' => 'Missing import ID'\n            ];\n        }\n        \n        // Check for existing property\n        $existing_post_id = $this->find_existing_property($import_id);\n        \n        if ($existing_post_id) {\n            // Handle duplicate based on configuration\n            return $this->handle_duplicate_property($existing_post_id, $mapped_property, $import_id);\n        } else {\n            // Create new property\n            return $this->create_new_property($mapped_property, $import_id);\n        }\n    }\n    \n    /**\n     * Find existing property by import ID\n     *\n     * @param string $import_id Import ID from source\n     * @return int|null WordPress post ID or null if not found\n     */\n    private function find_existing_property($import_id) {\n        if ($this->property_mapper) {\n            return $this->property_mapper->find_existing_property($import_id);\n        }\n        \n        // Fallback method\n        $query = new WP_Query([\n            'post_type' => $this->post_type,\n            'meta_query' => [\n                [\n                    'key' => 'property_import_id',\n                    'value' => $import_id,\n                    'compare' => '='\n                ]\n            ],\n            'posts_per_page' => 1,\n            'fields' => 'ids'\n        ]);\n        \n        return $query->have_posts() ? $query->posts[0] : null;\n    }\n    \n    /**\n     * Handle duplicate property based on configuration\n     *\n     * @param int $existing_post_id Existing WordPress post ID\n     * @param array $mapped_property New mapped property data\n     * @param string $import_id Import ID\n     * @return array Import result\n     */\n    private function handle_duplicate_property($existing_post_id, $mapped_property, $import_id) {\n        switch ($this->config['duplicate_action']) {\n            case 'update':\n                return $this->update_existing_property($existing_post_id, $mapped_property, $import_id);\n                \n            case 'skip':\n                $this->logger->debug('Property skipped - duplicate found', [\n                    'session_id' => $this->session_id,\n                    'import_id' => $import_id,\n                    'existing_post_id' => $existing_post_id\n                ]);\n                \n                return [\n                    'success' => true,\n                    'action' => 'skipped',\n                    'post_id' => $existing_post_id,\n                    'message' => 'Duplicate skipped'\n                ];\n                \n            case 'create_new':\n                // Remove import ID to force new creation\n                unset($mapped_property['meta_fields']['property_import_id']);\n                return $this->create_new_property($mapped_property, $import_id . '_' . time());\n                \n            default:\n                return [\n                    'success' => false,\n                    'error' => 'Invalid duplicate action configuration'\n                ];\n        }\n    }\n    \n    /**\n     * Create new property post\n     *\n     * @param array $mapped_property Mapped property data\n     * @param string $import_id Import ID\n     * @return array Creation result\n     */\n    private function create_new_property($mapped_property, $import_id) {\n        $this->logger->debug('Creating new property', [\n            'session_id' => $this->session_id,\n            'import_id' => $import_id,\n            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'\n        ]);\n        \n        // Insert post\n        $post_id = wp_insert_post($mapped_property['post_data'], true);\n        \n        if (is_wp_error($post_id)) {\n            return [\n                'success' => false,\n                'error' => 'Post creation failed: ' . $post_id->get_error_message()\n            ];\n        }\n        \n        // Add meta fields\n        $this->assign_meta_fields($post_id, $mapped_property['meta_fields'] ?? []);\n        \n        // Assign taxonomies\n        $this->assign_taxonomies($post_id, $mapped_property['taxonomies'] ?? []);\n        \n        // Assign property features\n        if ($this->config['assign_property_features']) {\n            $this->assign_property_features($post_id, $mapped_property['features'] ?? []);\n        }\n        \n        // Add custom fields\n        $this->assign_custom_fields($post_id, $mapped_property['custom_fields'] ?? []);\n        \n        $this->logger->info('Property created successfully', [\n            'session_id' => $this->session_id,\n            'import_id' => $import_id,\n            'post_id' => $post_id,\n            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'\n        ]);\n        \n        return [\n            'success' => true,\n            'action' => 'created',\n            'post_id' => $post_id,\n            'message' => 'Property created successfully'\n        ];\n    }\n    \n    /**\n     * Update existing property post\n     *\n     * @param int $post_id Existing WordPress post ID\n     * @param array $mapped_property New mapped property data\n     * @param string $import_id Import ID\n     * @return array Update result\n     */\n    private function update_existing_property($post_id, $mapped_property, $import_id) {\n        // Check if content has changed\n        if ($this->property_mapper && isset($mapped_property['content_hash'])) {\n            if (!$this->property_mapper->has_content_changed($post_id, $mapped_property['content_hash'])) {\n                $this->logger->debug('Property content unchanged - skipping update', [\n                    'session_id' => $this->session_id,\n                    'import_id' => $import_id,\n                    'post_id' => $post_id\n                ]);\n                \n                // Update only the sync timestamp\n                update_post_meta($post_id, 'property_last_sync', current_time('mysql'));\n                \n                return [\n                    'success' => true,\n                    'action' => 'skipped',\n                    'post_id' => $post_id,\n                    'message' => 'No changes detected'\n                ];\n            }\n        }\n        \n        $this->logger->debug('Updating existing property', [\n            'session_id' => $this->session_id,\n            'import_id' => $import_id,\n            'post_id' => $post_id,\n            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'\n        ]);\n        \n        // Backup existing post if enabled\n        if ($this->config['backup_before_update']) {\n            $this->backup_post_data($post_id);\n        }\n        \n        // Update post data\n        $post_data = $mapped_property['post_data'];\n        $post_data['ID'] = $post_id;\n        \n        $result = wp_update_post($post_data, true);\n        \n        if (is_wp_error($result)) {\n            return [\n                'success' => false,\n                'error' => 'Post update failed: ' . $result->get_error_message()\n            ];\n        }\n        \n        // Update meta fields\n        $this->assign_meta_fields($post_id, $mapped_property['meta_fields'] ?? []);\n        \n        // Update taxonomies\n        $this->assign_taxonomies($post_id, $mapped_property['taxonomies'] ?? []);\n        \n        // Update property features\n        if ($this->config['assign_property_features']) {\n            $this->assign_property_features($post_id, $mapped_property['features'] ?? []);\n        }\n        \n        // Update custom fields\n        $this->assign_custom_fields($post_id, $mapped_property['custom_fields'] ?? []);\n        \n        // Update content hash\n        if (isset($mapped_property['content_hash'])) {\n            update_post_meta($post_id, 'property_import_hash', $mapped_property['content_hash']);\n        }\n        \n        $this->logger->info('Property updated successfully', [\n            'session_id' => $this->session_id,\n            'import_id' => $import_id,\n            'post_id' => $post_id,\n            'title' => $mapped_property['post_data']['post_title'] ?? 'Unknown'\n        ]);\n        \n        return [\n            'success' => true,\n            'action' => 'updated',\n            'post_id' => $post_id,\n            'message' => 'Property updated successfully'\n        ];\n    }\n    \n    /**\n     * Assign meta fields to post\n     *\n     * @param int $post_id WordPress post ID\n     * @param array $meta_fields Meta fields array\n     */\n    private function assign_meta_fields($post_id, $meta_fields) {\n        foreach ($meta_fields as $meta_key => $meta_value) {\n            if ($meta_value !== null && $meta_value !== '') {\n                update_post_meta($post_id, $meta_key, $meta_value);\n            }\n        }\n    }\n    \n    /**\n     * Assign taxonomies and terms to post\n     *\n     * @param int $post_id WordPress post ID\n     * @param array $taxonomies Taxonomies array\n     */\n    private function assign_taxonomies($post_id, $taxonomies) {\n        foreach ($taxonomies as $taxonomy => $terms) {\n            if (empty($terms)) {\n                continue;\n            }\n            \n            $term_ids = [];\n            \n            foreach ($terms as $term_name) {\n                $term_id = $this->get_or_create_term($term_name, $taxonomy);\n                if ($term_id) {\n                    $term_ids[] = $term_id;\n                }\n            }\n            \n            if (!empty($term_ids)) {\n                wp_set_object_terms($post_id, $term_ids, $taxonomy);\n            }\n        }\n    }\n    \n    /**\n     * Get existing term ID or create new term\n     *\n     * @param string $term_name Term name\n     * @param string $taxonomy Taxonomy name\n     * @return int|null Term ID or null on failure\n     */\n    private function get_or_create_term($term_name, $taxonomy) {\n        // Check if term exists\n        $term = get_term_by('name', $term_name, $taxonomy);\n        \n        if ($term) {\n            return $term->term_id;\n        }\n        \n        // Create term if enabled\n        if ($this->config['create_missing_terms']) {\n            $result = wp_insert_term($term_name, $taxonomy);\n            \n            if (is_wp_error($result)) {\n                $this->logger->warning('Failed to create term', [\n                    'session_id' => $this->session_id,\n                    'term_name' => $term_name,\n                    'taxonomy' => $taxonomy,\n                    'error' => $result->get_error_message()\n                ]);\n                return null;\n            }\n            \n            $this->stats['created_terms']++;\n            \n            $this->logger->debug('Term created', [\n                'session_id' => $this->session_id,\n                'term_name' => $term_name,\n                'taxonomy' => $taxonomy,\n                'term_id' => $result['term_id']\n            ]);\n            \n            return $result['term_id'];\n        }\n        \n        return null;\n    }\n    \n    /**\n     * Assign property features to post\n     *\n     * @param int $post_id WordPress post ID\n     * @param array $features Features array\n     */\n    private function assign_property_features($post_id, $features) {\n        if (empty($features)) {\n            return;\n        }\n        \n        $feature_term_ids = [];\n        \n        foreach ($features as $feature_slug) {\n            $term_id = $this->get_or_create_term($feature_slug, 'property_features');\n            if ($term_id) {\n                $feature_term_ids[] = $term_id;\n            }\n        }\n        \n        if (!empty($feature_term_ids)) {\n            wp_set_object_terms($post_id, $feature_term_ids, 'property_features');\n            $this->stats['assigned_features'] += count($feature_term_ids);\n        }\n    }\n    \n    /**\n     * Assign custom fields to post\n     *\n     * @param int $post_id WordPress post ID\n     * @param array $custom_fields Custom fields array\n     */\n    private function assign_custom_fields($post_id, $custom_fields) {\n        foreach ($custom_fields as $field_key => $field_value) {\n            if ($field_value !== null && $field_value !== '') {\n                update_post_meta($post_id, $field_key, $field_value);\n            }\n        }\n    }\n    \n    /**\n     * Backup post data before update\n     *\n     * @param int $post_id WordPress post ID\n     */\n    private function backup_post_data($post_id) {\n        $backup_data = [\n            'post' => get_post($post_id, ARRAY_A),\n            'meta' => get_post_meta($post_id),\n            'terms' => wp_get_object_terms($post_id, get_object_taxonomies($this->post_type)),\n            'timestamp' => current_time('mysql')\n        ];\n        \n        update_post_meta($post_id, '_trentino_import_backup', $backup_data);\n    }\n    \n    /**\n     * Get import statistics\n     *\n     * @return array Statistics\n     */\n    public function get_stats() {\n        return $this->stats;\n    }\n    \n    /**\n     * Get import configuration\n     *\n     * @return array Configuration\n     */\n    public function get_config() {\n        return $this->config;\n    }\n    \n    /**\n     * Update import configuration\n     *\n     * @param array $config New configuration\n     * @return bool Success status\n     */\n    public function update_config($config) {\n        $this->config = array_merge($this->config, $config);\n        $success = update_option('trentino_import_wp_importer_config', $this->config);\n        \n        if ($success) {\n            $this->logger->info('WordPress Importer configuration updated', [\n                'updated_keys' => array_keys($config)\n            ]);\n        }\n        \n        return $success;\n    }\n    \n    /**\n     * Clean up orphaned import data\n     *\n     * @param int $days_old Remove data older than X days\n     * @return array Cleanup result\n     */\n    public function cleanup_import_data($days_old = 30) {\n        $cutoff_date = date('Y-m-d H:i:s', strtotime('-' . $days_old . ' days'));\n        \n        // Find posts with old import data\n        $query = new WP_Query([\n            'post_type' => $this->post_type,\n            'meta_query' => [\n                [\n                    'key' => 'property_import_date',\n                    'value' => $cutoff_date,\n                    'compare' => '<',\n                    'type' => 'DATETIME'\n                ]\n            ],\n            'posts_per_page' => -1,\n            'fields' => 'ids'\n        ]);\n        \n        $cleaned_posts = 0;\n        \n        foreach ($query->posts as $post_id) {\n            delete_post_meta($post_id, '_trentino_import_backup');\n            $cleaned_posts++;\n        }\n        \n        $this->logger->info('Import data cleanup completed', [\n            'days_old' => $days_old,\n            'cleaned_posts' => $cleaned_posts\n        ]);\n        \n        return [\n            'success' => true,\n            'cleaned_posts' => $cleaned_posts,\n            'cutoff_date' => $cutoff_date\n        ];\n    }\n}\n\n// End of file", "oldText": "        // Set execution time limit\n        if ($this->config['max_execution_time'] > 0) {"}]