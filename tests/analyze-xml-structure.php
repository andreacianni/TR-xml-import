<?php
/**
 * Quick XML Structure Analyzer for Trentino Import
 * 
 * Analyzes the first property in XML to identify exact field names
 * Run on server to see real XML structure from GestionaleImmobiliare
 */

// Set memory and time limits
ini_set('memory_limit', '512M');
set_time_limit(0);

echo "🔍 XML Structure Analyzer - Trentino Import\n";
echo "==========================================\n\n";

// XML file path on server
$xml_file = '/tmp/export_gi_full_merge_multilevel.xml';

if (!file_exists($xml_file)) {
    echo "❌ XML file not found: $xml_file\n";
    echo "Run the main test script first to download XML\n";
    exit(1);
}

echo "📁 XML file found: " . filesize($xml_file) . " bytes\n\n";

// Create XMLReader for streaming
$reader = new XMLReader();
$reader->open($xml_file);

$property_count = 0;
$first_property = null;

echo "🔍 Scanning for first property...\n";

// Find first <annuncio> element
while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'annuncio') {
        $property_count++;
        
        // Get full XML of first property
        $property_xml = $reader->readOuterXML();
        
        // Parse the property XML
        $property_dom = new DOMDocument();
        $property_dom->loadXML($property_xml);
        
        // Extract all info fields
        $info_node = $property_dom->getElementsByTagName('info')->item(0);
        
        if ($info_node) {
            echo "✅ First property found!\n\n";
            echo "📋 INFO FIELDS AVAILABLE:\n";
            echo "========================\n";
            
            foreach ($info_node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $value = trim($child->textContent);
                    $value_preview = strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
                    printf("• %-20s = %s\n", $child->nodeName, $value_preview);
                }
            }
            
            echo "\n📋 INFO_INSERITE FIELDS:\n";
            echo "========================\n";
            
            $info_inserite = $property_dom->getElementsByTagName('info_inserite')->item(0);
            if ($info_inserite) {
                foreach ($info_inserite->getElementsByTagName('info') as $info_item) {
                    $id = $info_item->getAttribute('id');
                    $value = $info_item->getElementsByTagName('valore_assegnato')->item(0);
                    if ($value) {
                        printf("• ID %-3s = %s\n", $id, $value->textContent);
                    }
                }
            }
            
            echo "\n📋 DATI_INSERITI FIELDS:\n";
            echo "========================\n";
            
            $dati_inseriti = $property_dom->getElementsByTagName('dati_inseriti')->item(0);
            if ($dati_inseriti) {
                foreach ($dati_inseriti->getElementsByTagName('dati') as $data_item) {
                    $id = $data_item->getAttribute('id');
                    $value = $data_item->getElementsByTagName('valore_assegnato')->item(0);
                    if ($value) {
                        printf("• ID %-3s = %s\n", $id, $value->textContent);
                    }
                }
            }
            
            echo "\n📋 FIELD MAPPING SUGGESTIONS:\n";
            echo "=============================\n";
            
            // Check for key fields
            $key_fields = ['id', 'abstract', 'seo_title', 'description', 'price', 'mq', 'categorie_id'];
            
            foreach ($key_fields as $field) {
                $field_node = $info_node->getElementsByTagName($field)->item(0);
                if ($field_node) {
                    $value = trim($field_node->textContent);
                    $value_preview = strlen($value) > 30 ? substr($value, 0, 30) . '...' : $value;
                    printf("✅ %-15s FOUND = %s\n", $field, $value_preview);
                } else {
                    printf("❌ %-15s NOT FOUND\n", $field);
                }
            }
        }
        
        break; // Only analyze first property
    }
}

$reader->close();

if ($property_count === 0) {
    echo "❌ No properties found in XML file\n";
    exit(1);
}

echo "\n🎯 ANALYSIS COMPLETE!\n";
echo "Total properties in file: scanning...\n";

// Quick count total properties
$reader = new XMLReader();
$reader->open($xml_file);
$total_count = 0;

while ($reader->read()) {
    if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'annuncio') {
        $total_count++;
    }
}
$reader->close();

echo "Total properties in file: $total_count\n\n";
echo "✅ Use this field mapping info to fix PropertyMapper!\n";
