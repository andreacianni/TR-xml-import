# trentino-immobiliare-KB-Context.md

## 🎯 **CONTESTO PROGETTO**

### **📋 Descrizione Progetto**
- **Nome**: Trentino Immobiliare - Plugin WordPress per Import XML Automatizzato
- **Tipo**: **WordPress Plugin Dedicato** + Sistema Import Automatico
- **Obiettivo**: Plugin WordPress per import automatico giornaliero annunci immobiliari da XML GestionaleImmobiliare.it
- **Target**: Plugin professionale con admin interface per gestione import automatizzati
- **Cliente**: Agenzia immobiliare Trentino

---

## 🏗️ **ARCHITETTURA TECNICA - PLUGIN WORDPRESS**

### **🔌 Plugin WordPress Dedicato**
- **Nome Plugin**: Trentino Import Plugin
- **Integrazione**: WpResidence theme nativa
- **Admin Interface**: Dashboard WordPress completa
- **Automazione**: Cron WordPress scheduling
- **Manutenibilità**: Interface user-friendly per troubleshooting

### **🔐 Server Access - spaziodemo.xyz**
- **SSH**: `ssh u996-hh9emyr0bbn6@ams11.siteground.eu -p 18765`
- **Prompt**: `baseos | spaziodemo.xyz | u996-hh9emyr0bbn6@ams11.siteground.eu:~# trentino-immobiliare-KB-Context.md

## 🎯 **CONTESTO PROGETTO**

### **📋 Descrizione Progetto**
- **Nome**: Trentino Immobiliare - Plugin WordPress per Import XML Automatizzato
- **Tipo**: **WordPress Plugin Dedicato** + Sistema Import Automatico
- **Obiettivo**: Plugin WordPress per import automatico giornaliero annunci immobiliari da XML GestionaleImmobiliare.it
- **Target**: Plugin professionale con admin interface per gestione import automatizzati
- **Cliente**: Agenzia immobiliare Trentino

---

## 🏗️ **ARCHITETTURA TECNICA - PLUGIN WORDPRESS**

### **🔌 Plugin WordPress Dedicato**
- **Nome Plugin**: Trentino Import Plugin
- **Integrazione**: WpResidence theme nativa
- **Admin Interface**: Dashboard WordPress completa
- **Automazione**: Cron WordPress scheduling
- **Manutenibilità**: Interface user-friendly per troubleshooting


- **Plugin Path**: `~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin`
- **URL**: https://spaziodemo.xyz

### **🌐 Sito WordPress Target**
- **URL**: https://trentinoimmobiliare.it/
- **Tema**: WpResidence (tema premium real estate)
- **Hosting**: SiteGround 
- **Accesso Server**: SSH (in attesa credenziali cPanel)

### **📁 Struttura Plugin**
```
wp-content/plugins/trentino-import-plugin/
├── trentino-import.php              # Main plugin file + headers
├── includes/                        # Core functionality
│   ├── class-xml-downloader.php     # Download + auth GestionaleImmobiliare
│   ├── class-xml-parser.php         # Parsing XML + data validation
│   ├── class-property-mapper.php    # Mapping XML → WpResidence fields
│   ├── class-wp-importer.php        # WordPress properties import
│   ├── class-logger.php             # Sistema logging completo
│   └── class-cron-manager.php       # Gestione automazione cron
├── admin/                           # Admin interface
│   ├── class-admin-page.php         # Main admin controller
│   ├── views/                       # Admin templates
│   │   ├── settings.php             # Configurazioni plugin
│   │   ├── import-status.php        # Status import + log viewer
│   │   └── manual-import.php        # "Scarica ora" interface
│   └── assets/                      # Admin CSS/JS
│       ├── admin.css                # Styling admin interface
│       └── admin.js                 # Ajax calls + interactions
├── config/                          # Configurazioni
│   ├── field-mapping.php            # Mapping completo XML→WpResidence
│   ├── province-config.php          # Gestione provincie importazione
│   └── default-settings.php         # Default plugin settings
├── logs/                            # Log system
│   └── import-logs/                 # Automated import logs
└── readme.txt                      # WordPress plugin readme
```

### **🎨 Stack Tecnologico Plugin**
- **Core**: PHP 7.4+ Plugin WordPress
- **Admin UI**: WordPress admin interface + Ajax
- **Database**: WordPress custom tables + options API
- **Automation**: WordPress cron scheduling
- **Integration**: WpResidence theme hooks e filters
- **Logging**: Custom logging system con admin viewer

---

## 🎮 **ADMIN INTERFACE - FUNZIONALITÀ COMPLETE**

### **📊 Dashboard Plugin (3 Tab Structure)**

**🔧 Tab 1: Configurazioni**
- **Credenziali GestionaleImmobiliare**: Username/Password secure storage
- **Selezione provincie**: Checkbox X, Y (future Z espandibile)
- **Scheduling**: Orario esecuzione automatica (default 02:00)
- **Backup Settings**: Pre-import database backup options
- **Email Notifications**: Admin alerts per errori/completamento
- **Import Options**: Batch size, timeout settings, retry logic

**⚡ Tab 2: Import Manuale**
- **"Scarica e Importa Ora"** - Main action button
- **Progress Bar**: Real-time import progress + statistics
- **Preview Dati**: Anteprima XML prima dell'import effettivo
- **Stop/Resume**: Controls per gestione import lunghi
- **Test Download**: Verifica connessione e credenziali
- **Dry Run**: Simulazione import senza modifiche database

**📈 Tab 3: Log e Monitoring**
- **Storico Importazioni**: Data, durata, properties importate, errori
- **Log File Viewer**: Visualizzazione log integrata in admin
- **Statistics Dashboard**: Grafici performance e success rate
- **Error Analysis**: Dettaglio errori con suggested actions
- **Export Logs**: Download log per analisi esterna
- **System Status**: Health check plugin e dipendenze

### **🔔 Notification System**
- **Success**: Import completato con summary
- **Warnings**: Dati parziali o problemi minori
- **Errors**: Fallimenti con troubleshooting suggestions
- **Email Alerts**: Notifiche admin per situazioni critiche

---

## 🔗 **SISTEMA SORGENTE DATI**

### **📊 GestionaleImmobiliare.it - Fonte XML**
- **URL Download**: https://www.gestionaleimmobiliare.it/export/xml/trentinoimmobiliare_it/
- **File**: export_gi_full_merge_multilevel.xml.tar.gz
- **Autenticazione**: Username/Password (gestita via admin interface)
- **Frequenza**: Aggiornamento giornaliero automatico
- **Formato**: XML con specifiche dettagliate (105 campi)

### **📋 Struttura Dati XML**
**Categorie Immobili Principali:**
- Casa singola (1), Bifamiliare (2), Appartamento (11)
- Attico (12), Villa (18), Terreno (19)
- Negozio (14), Ufficio (17), Garage (8)

**Attributi Disponibili (105 campi):**
- **Base**: bagni, camere, cucina, soggiorno, garage
- **Caratteristiche**: ascensore, aria condizionata, giardino, piscina
- **Posizione**: piano, totale piani, panorama vista
- **Impianti**: riscaldamento, classe energetica, domotica
- **Specifici**: superficie, volumetria, catasto

**Dati Numerici Aggiuntivi:**
- Superfici (commerciale, utile, giardino)
- Dati catastali (foglio, particella, rendita)
- Dimensioni (lunghezza, larghezza, altezza)
- Potenze impianti

---

## 🏠 **TEMA WPRESIDENCE - INTEGRAZIONE PLUGIN**

### **✅ Integrazione Nativa Plugin**
- **Custom Post Type**: Properties con meta fields mapping completo
- **Hooks Integration**: Plugin utilizza WpResidence action hooks
- **Taxonomies**: Automatic assignment categorie e features
- **Media Handling**: Import immagini nelle gallery properties
- **SEO Integration**: Meta fields automatici per SEO
- **Cache Compatibility**: Compatible con caching plugins

### **🔧 WordPress Integration Points**
- **activation_hook**: Setup database tables e default settings
- **wp_schedule_event**: Automazione import giornaliera
- **admin_menu**: Registration admin pages plugin
- **wp_ajax_**: Ajax endpoints per admin interface
- **wp_enqueue_scripts**: Admin assets loading
- **register_setting**: Settings API per configurazioni

### **📊 Campi WpResidence - Mapping Plugin**
- **Base**: Property ID, Title, Description, Price, Status
- **Location**: Address, City, Area, State, ZIP, Country
- **Details**: Bedrooms, Bathrooms, Property Size, Lot Size
- **Features**: Amenities automaticamente assegnate da XML
- **Media**: Automatic gallery creation + image import
- **SEO**: Auto-generated meta da dati property

---

## 🔄 **WORKFLOW IMPORT AUTOMATIZZATO PLUGIN**

### **📋 Plugin Import Process**
1. **Cron Trigger**
   - WordPress scheduled event (daily 02:00)
   - Plugin activation automatic scheduling
   - Manual trigger via admin interface

2. **XML Download & Processing**
   - Authenticated download via cURL + credentials admin
   - Archive extraction (.tar.gz) con error handling
   - XML validation e structure verification

3. **Data Processing**
   - Parsing XML con mapping configurabile
   - Data validation e sanitization
   - Duplicate detection logic

4. **WordPress Import**
   - Properties creation/update via WP API
   - Media import con gallery assignment
   - Taxonomies e meta fields population
   - WpResidence specific field mapping

5. **Post-Processing**
   - Import statistics generation
   - Log file creation con detailed results
   - Admin notifications (email/dashboard)
   - Cleanup temporary files

### **⏰ Automazione WordPress Cron**
- **wp_schedule_event**: Daily execution setup
- **Custom Hook**: 'trentino_import_daily_cron'
- **Error Recovery**: Automatic retry logic
- **Performance**: Batch processing per large datasets
- **Safety**: Pre-import backup options

---

## 🗺️ **MAPPING DATI CRITICI - PLUGIN LOGIC**

### **📊 XML → WpResidence Mapping System**
```php
// Plugin field mapping configuration
class PropertyMapper {
    
    private $field_mapping = [
        // Dati base property
        'titolo' => 'post_title',
        'descrizione' => 'post_content', 
        'prezzo_vendita' => 'property_price',
        'prezzo_affitto' => 'property_rent_price',
        
        // Location data
        'citta' => 'property_city',
        'indirizzo' => 'property_address',
        'provincia' => 'property_state',
        'cap' => 'property_zip',
        
        // Property details
        'superficie_commerciale' => 'property_size',
        'numero_camere' => 'property_bedrooms',
        'numero_bagni' => 'property_bathrooms',
        'piano' => 'property_floor',
        'totale_piani' => 'property_floors',
        
        // Features mapping
        'ascensore' => 'property_feature_elevator',
        'giardino' => 'property_feature_garden',
        'piscina' => 'property_feature_pool',
        'garage' => 'property_feature_garage',
    ];
    
    // Configurable province filter
    public function get_enabled_provinces() {
        return get_option('trentino_import_provinces', ['TN', 'BZ']);
    }
    
    // Category mapping con configurazione admin
    public function map_property_category($xml_category_id) {
        $category_mapping = get_option('trentino_category_mapping', [
            1 => 'house',      // casa singola
            11 => 'apartment', // appartamento
            12 => 'penthouse', // attico
            18 => 'villa',     // villa
            19 => 'land',      // terreno
            14 => 'commercial', // negozio
        ]);
        
        return $category_mapping[$xml_category_id] ?? 'property';
    }
}
```

### **🏷️ Gestione Tassonomie Plugin**
- **Auto-Assignment**: Categorie automatiche da XML
- **Custom Terms**: Creazione automatica nuovi termini
- **Hierarchy**: Gestione categorie padre/figlio
- **Cleanup**: Rimozione tassonomie non utilizzate

---

## 🔧 **PLUGIN FEATURES - MANUTENIBILITÀ**

### **🛠️ Personalizzazioni Future Supportate**

**1. Espansione Provincie**
- **Admin Interface**: Semplice checkbox per nuove provincie
- **Configurabile**: No editing codice richiesto
- **Scalabile**: Sistema supporta infinite provincie

**2. Mapping Fields Personalizzato**
- **Config File**: `field-mapping.php` modificabile
- **No Database**: Mapping in file per easy versioning
- **Extensible**: Supporto nuovi campi XML future

**3. Troubleshooting Tools**
- **"Scarica Ora"**: Manual import per testing/debug
- **Log Viewer**: Analisi errori direttamente da admin
- **Dry Run**: Test import senza modifiche database
- **System Check**: Verifica prerequisites e configurazione

### **🔒 Security & Credentials Management**
- **Encrypted Storage**: Credenziali GestionaleImmobiliare in options criptate
- **Admin Only**: Interface riservata ad amministratori
- **Audit Trail**: Log completo attività per security
- **Safe Mode**: Opzioni per disable automatic import

### **📊 Performance & Reliability**
- **Batch Processing**: Import configurabile (100/500/1000 properties)
- **Memory Management**: Efficient handling large XML files
- **Timeout Protection**: Long-running process handling
- **Error Recovery**: Retry logic per network/server issues
- **Resource Monitoring**: CPU/Memory usage tracking

---

## 📱 **AMBIENTE SVILUPPO**

### **🌐 Development Environment**
- **Local Development**: Plugin development in local WordPress
- **Path Progetto**: `C:\Users\Andrea\OneDrive\Lavori\novacom\Trentino-immobiliare`
- **Plugin Path**: `/wp-content/plugins/trentino-import-plugin/`
- **Testing**: Local WordPress + WpResidence theme
- **Version Control**: Git repository per plugin versioning

### **📋 Deployment Workflow**
- **Development**: Local WordPress environment
- **Testing**: Plugin testing con sample XML data
- **Staging**: Deploy su server staging per validation
- **Production**: Install plugin su trentinoimmobiliare.it live
- **Maintenance**: Updates via WordPress admin interface

### **🔐 Security & Access Management**
- **Plugin Security**: WordPress nonces e capability checks
- **Credentials**: Admin interface per management sicuro
- **Server Access**: SSH for plugin deployment e maintenance
- **Backup**: Pre-deployment backup automatico

---

## 📚 **DOCUMENTAZIONE GESTIONALE IMMOBILIARE**

### **📁 Documentazione Locale Completa**
- **Path**: `C:\Users\Andrea\OneDrive\Lavori\novacom\Trentino-immobiliare\docs\Gestionale-Immobiliare\`
- **GI-XML-Structure-Documentation.md** - Struttura XML completa e parsing strategy
- **GI-Categories-Reference.md** - Categorie e micro-categorie proprietà
- **GI-Field-Mapping-Reference.md** - Field mapping completo per WpResidence
- **geodata/** - Dati geografici province TN e BZ

### **🌐 Accesso Documentazione Online**
- **URL**: https://gestionaleimmobiliare.it/export/help/
- **Credenziali**: trentinoimmobiliare_it / dget6g52
- **Sezioni**: Specifiche import, struttura XML, attributi, esempi

### **⚠️ CRITICAL XML STRUCTURE**
```xml
<dataset>
  <annuncio>
    <info>
      <id>14503</id>
      <categorie_id>11</categorie_id>
      <mq>121</mq>
      <price>100000</price>
      <!-- Dati base -->
    </info>
    <info_inserite>
      <info id="1"><valore_assegnato>2</valore_assegnato></info> <!-- bagni -->
      <info id="2"><valore_assegnato>3</valore_assegnato></info> <!-- camere -->
    </info_inserite>
    <dati_inseriti>
      <dati id="4"><valore_assegnato>200</valore_assegnato></dati> <!-- mq giardino -->
    </dati_inseriti>
  </annuncio>
</dataset>
```

---

## 📊 **OBIETTIVI PROGETTO PLUGIN**

### **🎯 Obiettivi Primari Plugin**
1. **Plugin Professionale**: Standard WordPress plugin con admin interface
2. **Import Automatico**: Sistema completamente automatizzato
3. **User-Friendly**: Admin può gestire tutto via dashboard
4. **Troubleshooting**: Tools integrati per debug e maintenance
5. **Scalabilità**: Facilmente estendibile per nuove esigenze
6. **Performance**: Import efficiente senza impatto sito
7. **Reliability**: Sistema robusto con error handling completo

### **🔧 Obiettivi Tecnici Plugin**
- **WordPress Standards**: Rispetta coding standards e best practices
- **WpResidence Integration**: Integrazione nativa seamless
- **Admin Experience**: Interface intuitiva e professionale
- **Maintenance**: Easy updates e configuration changes
- **Documentation**: Codice ben documentato per future modifiche
- **Extensibility**: Architecture modulare per customizzazioni

---

## 📈 **SUCCESS METRICS PLUGIN**

### **📊 KPI Plugin System**
- **Admin Usability**: Admin può gestire tutto senza supporto tecnico
- **Import Reliability**: > 95% import success rate
- **Performance**: < 30 minuti per import completo
- **Error Handling**: Admin comprende e risolve errori facilmente
- **Scalability**: Supporta future espansioni senza refactoring

### **⚡ Technical Performance**
- **Memory Usage**: < 512MB per import session
- **Processing Time**: Configurabile batch size per performance
- **Error Recovery**: Automatic retry + admin notification system
- **Database Impact**: Optimized queries e proper indexing
- **Server Load**: < 50% CPU usage durante import

---

## 🎉 **FASE PROGETTO**

**🚀 PROGETTO IN FASE DEVELOPMENT READY**

- ✅ **Analisi Requisiti**: Completata
- ✅ **Architecture Decision**: Plugin WordPress confermato
- ✅ **Technical Specs**: Plugin structure definita
- ✅ **Admin Interface**: UX/UI requirements definiti
- ⏳ **Accessi Server**: In attesa credenziali per deployment
- 🚧 **Development**: Ready to start plugin development

**📋 READY FOR PLUGIN DEVELOPMENT**

**💡 DECISIONE ARCHITECTURALE**: Plugin WordPress per massima manutenibilità, professionalità e user experience admin.

---

**📅 Aggiornato**: 03/08/2025  
**🔄 Versione Context**: v2.1 - Plugin Architecture + Standard Info  
**👨‍💻 Progetto**: Trentino Immobiliare WordPress Plugin  
**🎯 Status**: ✅ PLUGIN ARCHITECTURE DEFINITA - Ready for development

**🏢 STANDARD INFO PROGETTO:**
- **👨‍💻 Autore**: Andrea Cianni - Novacom
- **📦 Package**: TrentinoImport
- **🌐 URI**: https://www.novacomitalia.com/

**🌟 PLUGIN APPROACH = SOLUZIONE PROFESSIONALE E MANUTENIBILE**