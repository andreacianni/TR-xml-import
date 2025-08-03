<?php
/**
 * Test Script per Chunked Processing System
 * File: test-chunked-processing.php
 * 
 * Test completo sistema chunked processing con XML reale da 264MB
 * Monitora performance, memory usage, e genera statistiche dettagliate
 */

// Include WordPress environment
require_once('../../../../wp-config.php');
require_once('../../../../wp-load.php');

// Include plugin classes
require_once('../includes/class-tracking-manager.php');
require_once('../includes/class-xml-streaming-parser.php');
require_once('../includes/class-chunked-import-engine.php');
require_once('../includes/class-property-mapper.php');
require_once('../includes/class-wp-importer.php');
require_once('../includes/class-logger.php');

class ChunkedProcessingTester {
    
    private $xml_file_path;
    private $start_time;
    private $start_memory;
    private $test_results = array();
    
    public function __construct() {
        $this->xml_file_path = __DIR__ . '/xml-data/export_gi_full_merge_multilevel.xml';
        echo "🚀 CHUNKED PROCESSING TEST INIZIATO\n";
        echo "=============================================\n";
        echo "File XML: " . $this->xml_file_path . "\n";
        echo "Dimensioni file: " . $this->format_bytes(filesize($this->xml_file_path)) . "\n";
        echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
        echo "=============================================\n\n";
    }
    
    /**
     * Test completo sistema chunked processing
     */
    public function run_complete_test() {
        try {
            // Pre-test setup
            $this->setup_test_environment();
            
            // Test 1: Primo import completo
            echo "📊 TEST 1: PRIMO IMPORT COMPLETO (CHUNKED PROCESSING)\n";
            echo "------------------------------------------------------\n";
            $first_import_results = $this->test_chunked_import("FIRST_IMPORT");
            
            // Pausa tra test
            echo "\n⏱️  Pausa 5 secondi tra test...\n\n";
            sleep(5);
            
            // Test 2: Secondo import (differenziale)
            echo "📊 TEST 2: SECONDO IMPORT (DIFFERENZIALE)\n";
            echo "-------------------------------------------\n";
            $second_import_results = $this->test_chunked_import("DIFFERENTIAL_IMPORT");
            
            // Analisi comparativa
            $this->compare_import_results($first_import_results, $second_import_results);
            
            // Report finale
            $this->generate_final_report();
            
        } catch (Exception $e) {
            echo "❌ ERRORE DURANTE TEST: " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    /**
     * Setup ambiente di test
     */
    private function setup_test_environment() {
        echo "🔧 Setup ambiente di test...\n";
        
        // Verifica file XML
        if (!file_exists($this->xml_file_path)) {
            throw new Exception("File XML non trovato: " . $this->xml_file_path);
        }
        
        // Memory limit check
        $memory_limit = ini_get('memory_limit');
        echo "Memory limit PHP: " . $memory_limit . "\n";
        
        // Max execution time
        $max_execution = ini_get('max_execution_time');
        echo "Max execution time: " . $max_execution . " secondi\n";
        
        // Imposta execution time a 0 (unlimited) per il test
        set_time_limit(0);
        echo "✅ Max execution time impostato a unlimited per test\n";
        
        echo "✅ Ambiente di test pronto\n\n";
    }
    
    /**
     * Test import chunked con monitoring
     */
    private function test_chunked_import($test_type) {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage(true);
        
        echo "▶️  Inizio " . $test_type . " - " . date('H:i:s') . "\n";
        
        $results = array(
            'test_type' => $test_type,
            'start_time' => $this->start_time,
            'start_memory' => $this->start_memory,
            'properties_processed' => 0,
            'properties_imported' => 0,
            'properties_updated' => 0,
            'properties_skipped' => 0,
            'chunks_processed' => 0,
            'errors_count' => 0,
            'peak_memory' => 0,
            'processing_time' => 0
        );
        
        try {
            // Configura chunked engine
            $config = array(
                'chunk_size' => 25,
                'sleep_seconds' => 1,
                'max_memory_mb' => 256,
                'enabled_provinces' => ['TN', 'BZ'],
                'verbose_output' => true
            );
            
            // Crea engine con callback per monitoring
            $engine = new TrentinoChunkedImportEngine();
            
            // Callback per progress monitoring
            $progress_callback = function($progress_data) use (&$results) {
                $current_memory = memory_get_usage(true);
                $results['peak_memory'] = max($results['peak_memory'], $current_memory);
                
                echo sprintf(
                    "  ⚡ Chunk %d: %d properties | Memory: %s | Time: %s\n",
                    $progress_data['current_chunk'],
                    $progress_data['properties_in_chunk'],
                    $this->format_bytes($current_memory),
                    $this->format_duration(microtime(true) - $this->start_time)
                );
                
                $results['chunks_processed'] = $progress_data['current_chunk'];
                $results['properties_processed'] = $progress_data['total_processed'];
                
                return true; // Continue processing
            };
            
            // Esegui import chunked
            $import_results = $engine->execute_chunked_import(
                $this->xml_file_path,
                $config,
                $progress_callback
            );
            
            // Calcola risultati finali
            $end_time = microtime(true);
            $end_memory = memory_get_usage(true);
            
            $results['processing_time'] = $end_time - $this->start_time;
            $results['end_memory'] = $end_memory;
            $results['properties_imported'] = $import_results['imported'] ?? 0;
            $results['properties_updated'] = $import_results['updated'] ?? 0;
            $results['properties_skipped'] = $import_results['skipped'] ?? 0;
            $results['errors_count'] = $import_results['errors'] ?? 0;
            
            // Output risultati
            echo "\n✅ " . $test_type . " COMPLETATO\n";
            echo "   Tempo totale: " . $this->format_duration($results['processing_time']) . "\n";
            echo "   Properties processate: " . $results['properties_processed'] . "\n";
            echo "   Properties importate: " . $results['properties_imported'] . "\n";
            echo "   Properties aggiornate: " . $results['properties_updated'] . "\n";
            echo "   Properties saltate: " . $results['properties_skipped'] . "\n";
            echo "   Chunks processati: " . $results['chunks_processed'] . "\n";
            echo "   Errori: " . $results['errors_count'] . "\n";
            echo "   Memory peak: " . $this->format_bytes($results['peak_memory']) . "\n";
            echo "   Memory final: " . $this->format_bytes($results['end_memory']) . "\n\n";
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
            $results['processing_time'] = microtime(true) - $this->start_time;
            echo "❌ ERRORE durante " . $test_type . ": " . $e->getMessage() . "\n\n";
        }
        
        $this->test_results[] = $results;
        return $results;
    }
    
    /**
     * Confronta risultati dei due import
     */
    private function compare_import_results($first, $second) {
        echo "📊 ANALISI COMPARATIVA IMPORT\n";
        echo "===============================\n";
        
        if (isset($first['error']) || isset($second['error'])) {
            echo "⚠️  Impossibile confrontare - errori nei test\n\n";
            return;
        }
        
        $time_improvement = (($first['processing_time'] - $second['processing_time']) / $first['processing_time']) * 100;
        $memory_diff = $second['peak_memory'] - $first['peak_memory'];
        
        echo "Tempo primo import: " . $this->format_duration($first['processing_time']) . "\n";
        echo "Tempo secondo import: " . $this->format_duration($second['processing_time']) . "\n";
        echo "Miglioramento tempo: " . number_format($time_improvement, 1) . "%\n\n";
        
        echo "Memory peak primo: " . $this->format_bytes($first['peak_memory']) . "\n";
        echo "Memory peak secondo: " . $this->format_bytes($second['peak_memory']) . "\n";
        echo "Differenza memory: " . $this->format_bytes($memory_diff) . "\n\n";
        
        echo "Properties primo import: " . $first['properties_imported'] . " imported, " . $first['properties_updated'] . " updated\n";
        echo "Properties secondo import: " . $second['properties_imported'] . " imported, " . $second['properties_updated'] . " updated\n\n";
        
        // Valutazione performance differenziale
        if ($time_improvement > 70) {
            echo "🎉 ECCELLENTE: Import differenziale molto efficiente (>" . number_format($time_improvement, 1) . "% miglioramento)\n";
        } elseif ($time_improvement > 50) {
            echo "✅ BUONO: Import differenziale efficiente (" . number_format($time_improvement, 1) . "% miglioramento)\n";
        } elseif ($time_improvement > 0) {
            echo "⚠️  MODERATO: Import differenziale con miglioramento limitato (" . number_format($time_improvement, 1) . "%)\n";
        } else {
            echo "❌ PROBLEMA: Nessun miglioramento nell'import differenziale\n";
        }
        echo "\n";
    }
    
    /**
     * Report finale completo
     */
    private function generate_final_report() {
        echo "📋 REPORT FINALE TEST CHUNKED PROCESSING\n";
        echo "==========================================\n";
        
        $xml_size = filesize($this->xml_file_path);
        echo "File XML testato: " . $this->format_bytes($xml_size) . "\n";
        echo "Data test: " . date('Y-m-d H:i:s') . "\n\n";
        
        echo "🎯 OBIETTIVI PERFORMANCE:\n";
        echo "- Gestire file 264MB+ senza timeout: ";
        $first_result = $this->test_results[0] ?? null;
        if ($first_result && !isset($first_result['error']) && $first_result['processing_time'] < 1800) {
            echo "✅ SUCCESSO\n";
        } else {
            echo "❌ FALLITO\n";
        }
        
        echo "- Memory usage < 256MB: ";
        if ($first_result && $first_result['peak_memory'] < (256 * 1024 * 1024)) {
            echo "✅ SUCCESSO (" . $this->format_bytes($first_result['peak_memory']) . ")\n";
        } else {
            echo "❌ FALLITO (" . $this->format_bytes($first_result['peak_memory'] ?? 0) . ")\n";
        }
        
        echo "- Import differenziale > 50% miglioramento: ";
        if (count($this->test_results) >= 2) {
            $improvement = (($this->test_results[0]['processing_time'] - $this->test_results[1]['processing_time']) / $this->test_results[0]['processing_time']) * 100;
            if ($improvement > 50) {
                echo "✅ SUCCESSO (" . number_format($improvement, 1) . "%)\n";
            } else {
                echo "⚠️  PARZIALE (" . number_format($improvement, 1) . "%)\n";
            }
        } else {
            echo "❌ NON TESTATO\n";
        }
        
        echo "\n🏆 VALUTAZIONE SISTEMA:\n";
        $success_count = 0;
        $total_tests = 3;
        
        // Valuta ogni criterio
        if ($first_result && !isset($first_result['error']) && $first_result['processing_time'] < 1800) $success_count++;
        if ($first_result && $first_result['peak_memory'] < (256 * 1024 * 1024)) $success_count++;
        if (count($this->test_results) >= 2) {
            $improvement = (($this->test_results[0]['processing_time'] - $this->test_results[1]['processing_time']) / $this->test_results[0]['processing_time']) * 100;
            if ($improvement > 50) $success_count++;
        }
        
        $success_rate = ($success_count / $total_tests) * 100;
        
        if ($success_rate >= 100) {
            echo "🎉 SISTEMA PERFETTO - Tutti i test superati!\n";
        } elseif ($success_rate >= 66) {
            echo "✅ SISTEMA BUONO - Maggior parte test superati\n";
        } elseif ($success_rate >= 33) {
            echo "⚠️  SISTEMA ACCETTABILE - Alcuni problemi da risolvere\n";
        } else {
            echo "❌ SISTEMA PROBLEMATICO - Necessari miglioramenti\n";
        }
        
        echo "\n📊 RACCOMANDAZIONI:\n";
        if ($first_result && isset($first_result['error'])) {
            echo "- ❌ Risolvere errori nel processing engine\n";
        }
        if ($first_result && $first_result['peak_memory'] > (256 * 1024 * 1024)) {
            echo "- ⚠️  Ottimizzare memory usage (attuale: " . $this->format_bytes($first_result['peak_memory']) . ")\n";
        }
        if ($first_result && $first_result['processing_time'] > 1800) {
            echo "- ⚠️  Ottimizzare velocità processing (attuale: " . $this->format_duration($first_result['processing_time']) . ")\n";
        }
        if ($success_rate >= 66) {
            echo "- ✅ Sistema pronto per production deployment\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test completato: " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 50) . "\n";
    }
    
    /**
     * Formatta bytes in formato leggibile
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Formatta durata in formato leggibile
     */
    private function format_duration($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%dh %dm %.1fs', $hours, $minutes, $secs);
        } elseif ($minutes > 0) {
            return sprintf('%dm %.1fs', $minutes, $secs);
        } else {
            return sprintf('%.2fs', $secs);
        }
    }
}

// Esegui test
echo "\n";
echo "🚀 TRENTINO IMPORT - CHUNKED PROCESSING TEST\n";
echo "============================================\n";
echo "Versione: 1.0\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";
echo "Server: " . php_uname('n') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "============================================\n\n";

$tester = new ChunkedProcessingTester();
$tester->run_complete_test();

echo "\n🎉 TEST CHUNKED PROCESSING COMPLETATO!\n\n";
?>