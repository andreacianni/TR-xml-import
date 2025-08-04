# GestionaleImmobiliare.it - XML Structure Documentation

## 📋 **STRUTTURA XML STANDARD**

### **🏗️ Schema Base**
```xml
<?xml version="1.0" encoding="UTF-8"?> 
<dataset> 
  <annuncio>
    <info>
      <id>14503</id>
      <deleted>0</deleted>
      <agency_code>AP56</agency_code>
      <categorie_id>10</categorie_id>
      <categorie_micro_id>7</categorie_micro_id>
      <mq>121</mq>
      <price>100000</price>
      <price_real>95000</price_real>
      <age>2013</age>
      <ipe>67.50</ipe>
      <seo_title><![CDATA[appartamento in buone condizioni]]></seo_title>
      <abstract><![CDATA[appartamento ristrutturato]]></abstract>
      <description><![CDATA[appartamento ristrutturato dettagli]]></description>
      <latitude>3.345234</latitude>
      <longitude>11.4583624</longitude>
      <zona_id>63252</zona_id>
      <comune_istat>144435</comune_istat>
      <indirizzo><![CDATA[via donatello, 45]]></indirizzo>
      <civico>23</civico>
      <interno><![CDATA[C]]></interno>
      <indirizzo_portali><![CDATA[via donatello, 10]]></indirizzo_portali>
      <consegna>2012-07-01</consegna>
      <spese_condominiali>300</spese_condominiali>
      <unita_abitative>2</unita_abitative>
      <finiture><![CDATA[finiture molto eleganti]]></finiture>
      <esposizione>est-sud-no_nord</esposizione>
      <incarico>normale</incarico>
      <tipo_affitto>non_definito</tipo_affitto>
      <tipo_proprieta>non_definito</tipo_proprieta>
      <stato_rogito>libero</stato_rogito>
      <virtual_tour_panora><![CDATA[https://easy.panora.eu/tours/panora/esempio/]]></virtual_tour_panora>
      <virtual_tour_esterno><![CDATA[https://link/virtual/tour/esterno/]]></virtual_tour_esterno>
      <video_tour><![CDATA[http://www.youtube.com/watch?v=h07-qijBIdU]]></video_tour>
      <note><![CDATA[il proprietario è antipatico]]></note>
      <flag_vacanza>0</flag_vacanza>
      <visibilita_imm_it>top_star</visibilita_imm_it>
    </info>
    
    <i18n>
      <description lang="en"><![CDATA[renovated apartment blah blah blah]]></description>
      <description lang="de"><![CDATA[renovierte wohnung blah blah blah]]></description>
    </i18n>
    
    <file_allegati>
      <allegato id="1" type="planimetria">
        <id>1</id>
        <file_path>http://www.sitoweb.it/url_della_foto1.jpg</file_path>
      </allegato>
      <allegato id="2">
        <id>2</id>
        <file_path>http://www.sitoweb.it/url_della_foto23.gif</file_path>
      </allegato>
    </file_allegati>
    
    <info_inserite>
      <info id="1">
        <id>1</id>
        <valore_assegnato>2</valore_assegnato>
      </info>
      <info id="2">
        <id>2</id>
        <valore_assegnato>1</valore_assegnato>
      </info>
    </info_inserite>
    
    <dati_inseriti>
      <dati id="4">
        <id>4</id>
        <valore_assegnato>200</valore_assegnato>
      </dati>
      <dati id="5">
        <id>5</id>
        <valore_assegnato>100</valore_assegnato>
      </dati>
    </dati_inseriti>
    
    <export_portali>eurekasa;vendesiaffittasi_net;123case_it</export_portali>
  </annuncio>
  
  <annuncio>
    <!-- Altro annuncio... -->
  </annuncio>
</dataset>
```

## 🔑 **CAMPI OBBLIGATORI**

Per import successful, **DEVONO essere presenti**:
- `<id>` - ID univoco annuncio
- `<categorie_id>` - Categoria immobile (vedi tabella)
- `<mq>` - Metri quadri
- `<price>` - Prezzo

**⚠️ IMPORTANTE**: 
- Tutti i campi DEVONO essere codificati UTF-8
- I dati sono in `<info>` dentro `<annuncio>`
- Campo `<deleted>` se omesso = 0 (attivo)

## 📊 **NODI PRINCIPALI**

### **1. `<info>` - Dati Base Property**
- Informazioni principali dell'immobile
- Prezzo, superficie, coordinate, indirizzi
- Tutti i dati "fissi" dell'annuncio

### **2. `<info_inserite>` - Caratteristiche (ID-Based)**
- Features come bagni, camere, ascensore, etc.
- Struttura: `<info id="X"><valore_assegnato>Y</valore_assegnato></info>`
- Vedi tabella ID → significato

### **3. `<dati_inseriti>` - Dati Numerici Liberi**
- Valori numerici aggiuntivi (volumetria, superfici)
- Struttura: `<dati id="X"><valore_assegnato>Y</valore_assegnato></dati>`

### **4. `<file_allegati>` - Media Files**
- Photos, planimetrie, virtual tour
- Struttura: `<allegato id="X" type="planimetria"><file_path>URL</file_path></allegato>`

---

## 🎯 **PARSING STRATEGY**

### **✅ CORRECT XML Navigation**
```php
// ✅ CORRETTO - Navigazione nidificata
foreach ($xml->annuncio as $annuncio) {
    $info = $annuncio->info;
    $id = (string)$info->id;
    $categoria = (string)$info->categorie_id;
    $prezzo = (string)$info->price;
    $superficie = (string)$info->mq;
    
    // Info inserite (caratteristiche)
    foreach ($annuncio->info_inserite->info as $feature) {
        $feature_id = (string)$feature['id'];
        $feature_value = (string)$feature->valore_assegnato;
    }
    
    // Dati inseriti (numerici)
    foreach ($annuncio->dati_inseriti->dati as $data) {
        $data_id = (string)$data['id'];
        $data_value = (string)$data->valore_assegnato;
    }
}
```

### **❌ WRONG XML Navigation**
```php
// ❌ SBAGLIATO - Cerca direttamente
$properties = $xml->annuncio; // Non trova i dati nested
$id = $xml->id; // Non esiste a questo livello
```

---

---

## 🔄 **IMPORT DIFFERENZIALE - STRATEGIA CONFERMATA**

### **🔑 PRIMARY KEY IDENTIFICATO**
- **Campo Univoco**: `<id>` dentro `<info>` - ID univoco annuncio GestionaleImmobiliare
- **Tipo**: Numerico, garantito univoco dal sistema sorgente
- **Uso**: Primary key per tracking properties esistenti e change detection

### **🚨 CAMPO DELETED - GESTIONE RIMOZIONI**
- **Campo**: `<deleted>0</deleted>` o `<deleted>1</deleted>`
- **Logica**: 
  - `deleted=0` = annuncio attivo (da importare)
  - `deleted=1` = annuncio rimosso (non importare)
  - **Default**: Se campo omesso = 0 (attivo)
- **Strategy**: Plugin processa solo `deleted=0`, marca `deleted=1` come non disponibili

### **📊 CHANGE DETECTION STRATEGY**
- **Approccio**: Hash-based comparison per detecting changes
- **Hash Source**: MD5 di campi critici (prezzo, descrizione, caratteristiche principali)
- **Performance**: Solo properties con hash diverso vengono aggiornate
- **Tracking**: Plugin mantiene tabella con property_id + last_hash + last_import_date

### **⚡ INCREMENTAL IMPORT WORKFLOW**

**FIRST IMPORT (Inizializzazione)**
1. Import completo tutte properties con `deleted=0`
2. Calcolo e salvataggio hash per ogni property
3. Creazione tracking table con property_id mapping

**SUBSEQUENT IMPORTS (Differenziale)**
1. **Stream Processing**: XMLReader chunk-by-chunk del file completo
2. **New Properties**: ID non presenti nel tracking table → INSERT
3. **Existing Properties**: Confronto hash → UPDATE solo se diverso
4. **Deleted Properties**: Presenti nel DB ma non nel XML o `deleted=1` → MARK INACTIVE
5. **Performance**: Drastica riduzione processing time dopo primo import

### **🗄️ TRACKING TABLE STRUCTURE**
```sql
CREATE TABLE wp_trentino_import_tracking (
    property_id INT NOT NULL,           -- GI property ID
    wp_post_id INT NULL,                -- WordPress post ID
    property_hash VARCHAR(32) NOT NULL, -- MD5 hash dei dati
    last_import_date DATETIME NOT NULL, -- Ultimo import/update
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    PRIMARY KEY (property_id),
    INDEX (wp_post_id),
    INDEX (last_import_date)
);
```

### **🔐 DOCUMENTAZIONE ONLINE ACCESS**
- **URL Principale**: https://gestionaleimmobiliare.it/export/help/
- **Credenziali**: username: `trentinoimmobiliare_it` / password: `dget6g52`
- **Accesso**: Richiede autenticazione HTTP per visualizzare specifiche complete
- **Sezioni Disponibili**:
  - Specifiche import/export feeds
  - Struttura XML dettagliata
  - Domini valori per tutti i campi
  - Esempi pratici e reference completa
- **Update Policy**: Consultare regolarmente per aggiornamenti API

**📅 Fonte**: https://gestionaleimmobiliare.it/export/help/specifiche_import_agenzia.php  
**🔐 Auth**: trentinoimmobiliare_it / dget6g52  
**📅 Aggiornato**: 03/08/2025
