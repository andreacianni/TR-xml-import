# Trentino Import Plugin

WordPress plugin per import automatico annunci immobiliari da XML GestionaleImmobiliare.it

## 📋 Descrizione
Plugin dedicato per trentinoimmobiliare.it che automatizza l'importazione giornaliera di annunci immobiliari dal gestionale. Integrazione nativa con tema WpResidence.

## 🎯 Funzionalità
- Import automatico XML da GestionaleImmobiliare.it
- Admin interface a 3 tab (Settings, Import Manuale, Logs)
- Mapping completo XML → WpResidence properties 
- Filtro provincie configurabile
- Import manuale per troubleshooting
- Logging completo e error handling
- Automazione WordPress cron
- Gestione sicura credenziali

## 🏗️ Struttura Plugin
```
trentino-import-plugin/
├── trentino-import.php          # File principale plugin
├── includes/                    # Core functionality
│   ├── class-xml-downloader.php
│   ├── class-xml-parser.php  
│   ├── class-property-mapper.php
│   ├── class-wp-importer.php
│   ├── class-logger.php
│   └── class-cron-manager.php
├── admin/                       # Admin interface
│   ├── class-admin-page.php
│   ├── views/
│   └── assets/
├── config/                      # Configurazioni
├── logs/                        # Log files
└── readme.txt                  # WordPress plugin readme
```

## 🔧 Requisiti
- WordPress 5.0+
- PHP 7.4+
- Tema WpResidence attivo
- Estensioni PHP: curl, simplexml, zip

## 🚀 Installazione
1. Upload cartella plugin in `/wp-content/plugins/`
2. Attiva plugin da admin WordPress
3. Configura credenziali in Settings tab
4. Test import manuale
5. Configura automazione

## 📊 Development Status
- [x] Plugin structure creata
- [x] Main file con headers WordPress
- [ ] Core classes development
- [ ] Admin interface
- [ ] WordPress integration
- [ ] Testing e deployment

## 👨‍💻 Developer
Andrea Cianni - Novacom  
Progetto: Trentino Immobiliare  
Data: Agosto 2025