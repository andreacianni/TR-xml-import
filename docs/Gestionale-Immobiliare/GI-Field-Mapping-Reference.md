# GestionaleImmobiliare.it - Field Mapping Reference

## 🔢 **INFO_INSERITE FIELDS** (Characteristics - Domain Values)

### **📊 Property Features with Fixed Values**

| ID | Descrizione | Possibili Valori | Note |
|----|-------------|------------------|------|
| 1 | bagni | 0;1;2;3;-1 | -1 = più di 3 |
| 2 | camere | 0;1;2;3;-1 | -1 = più di 3 |
| 3 | cucina | 0;1 | 0=no, 1=sì |
| 4 | soggiorno | 0;1 | 0=no, 1=sì |
| 5 | garage | 0;1 | 0=no, 1=sì |
| 6 | asta | 0;1 | 0=no, 1=sì |
| 7 | ripostigli | 0;1;2;-1 | -1 = più di 2 |
| 8 | cantina | 0;1 | 0=no, 1=sì |
| 9 | vendita | 0;1 | 0=no, 1=sì |
| 10 | affitto | 0;1 | 0=no, 1=sì |
| 11 | mansarda | 0;1 | 0=no, 1=sì |
| 12 | taverna | 0;1 | 0=no, 1=sì |
| 13 | ascensore | 0;1 | 0=no, 1=sì |
| 14 | aria condizionata | 0;1 | 0=no, 1=sì |
| 15 | arredo | 0;1 | 0=no, 1=sì |
| 16 | riscaldamento autonomo | 0;1 | 0=no, 1=sì |
| 17 | giardino | 0;1 | 0=no, 1=sì |
| 18 | ingresso indipendente | 0;1 | 0=no, 1=sì |
| 19 | garage doppio | 0;1 | 0=no, 1=sì |
| 20 | posto auto | 0;1 | 0=no, 1=sì |
| 21 | riscaldamento a pavimento | 0;1 | 0=no, 1=sì |
| 22 | soggiorno con angolo cottura | 0;1 | 0=no, 1=sì |
| 23 | allarme | 0;1 | 0=no, 1=sì |
| 24 | terrazzi | 0;1;2;3;-1 | -1 = più di 3 |
| 25 | poggioli | 0;1;2;3;-1 | -1 = più di 3 |
| 26 | lavanderia | 0;1 | 0=no, 1=sì |
| 27 | piano interrato | 0;1 | 0=no, 1=sì |
| 28 | piano terra | 0;1 | 0=no, 1=sì |
| 29 | primo piano | 0;1 | 0=no, 1=sì |
| 30 | piano intermedio | 0;1 | 0=no, 1=sì |
| 31 | ultimo piano | 0;1 | 0=no, 1=sì |
| 32 | totale piani | -2;0;1;2;3;4;5;6;7;8;9;-1 | -2=interrato, -1=più di 9 |
| 33 | piano numero | -2;0;1;2;3;4;5;6;7;8;9;10;11;12;13;14;15;16;17;18;19;20;21;22;23;24;25;26;27;28;29;30;-1 | -2=interrato, -1=più di 30 |
| 34 | riscaldamento centralizzato | 0;1 | 0=no, 1=sì |
| 35 | mare | 0;1 | 0=no, 1=sì |
| 36 | montagna | 0;1 | 0=no, 1=sì |
| 37 | lago | 0;1 | 0=no, 1=sì |
| 38 | terme | 0;1 | 0=no, 1=sì |
| 39 | collina | 0;1 | 0=no, 1=sì |
| 40 | campagna | 0;1 | 0=no, 1=sì |
| 41 | nuovo | 0;1 | 0=no, 1=sì |
| 42 | immobile di prestigio | 0;1 | 0=no, 1=sì |
| 43 | giardino_condominiale | 0;1 | 0=no, 1=sì |
| 44 | soffitta | 0;1 | 0=no, 1=sì |
| 45 | grezzo | 0;1 | 0=no, 1=sì |
| 46 | camino | 0;1 | 0=no, 1=sì |
| 47 | predisposizione_aria_condizionata | 0;1 | 0=no, 1=sì |
| 48 | predisposizione_allarme | 0;1 | 0=no, 1=sì |
| 49 | pannelli_solari | 0;1 | 0=no, 1=sì |
| 50 | pannelli_fotovoltaici | 0;1 | 0=no, 1=sì |
| 51 | impianto_geotermico | 0;1 | 0=no, 1=sì |
| 52 | aree_esterne | 0;1 | 0=no, 1=sì |
| 53 | ribalte | 0;1 | 0=no, 1=sì |
| 54 | urbanizzato | 0;1 | 0=no, 1=sì |
| 55 | classe_energetica | vedi tabella specifica | A, B, C, etc. |
| 56 | posizione | vedi tabella specifica | centrale, etc. |
| 57 | stato_manutenzione | vedi tabella specifica | buono, etc. |
| 58 | numero_vetrine | 0;1;2;3;-1 | -1 = più di 3 |
| 59 | carro_ponte | 0;1 | 0=no, 1=sì |
| 60 | impianto_anti_incendio | 0;1 | 0=no, 1=sì |
| 61 | cabina_elettrica | 0;1 | 0=no, 1=sì |
| 62 | panorama | vedi tabella specifica | vista mare, etc. |
| 63 | piano_semi_interrato | 0;1 | 0=no, 1=sì |
| 64 | piano_rialzato | 0;1 | 0=no, 1=sì |
| 65 | numero_locali | 0(automatico);1;2;...n...;15 | 0=calcolato automaticamente |
| 66 | piscina | 0;1 | 0=no, 1=sì |
| 67 | porticato | 0;1 | 0=no, 1=sì |
| 68 | soppalco | 0;1 | 0=no, 1=sì |
| 69 | sottotetto | 0;1 | 0=no, 1=sì |
| 70 | chiavi_in_agenzia | 0;1 | 0=no, 1=sì |
| 71 | accesso_disabili | 0;1 | 0=no, 1=sì |
| 72 | area_fitness | 0;1 | 0=no, 1=sì |
| 73 | frigorifero | 0;1 | 0=no, 1=sì |
| 74 | lavatrice | 0;1 | 0=no, 1=sì |
| 75 | lavastoviglie | 0;1 | 0=no, 1=sì |
| 76 | posto_spiaggia | 0;1 | 0=no, 1=sì |
| 77 | cassaforte | 0;1 | 0=no, 1=sì |
| 78 | animali_ammessi | 0;1 | 0=no, 1=sì |
| 79 | televisione | 0;1 | 0=no, 1=sì |
| 80 | forno | 0;1 | 0=no, 1=sì |
| 81 | vasca_idromassaggio | 0;1 | 0=no, 1=sì |
| 82 | caldaia_a_condensazione | 0;1 | 0=no, 1=sì |
| 83 | riscaldamento_semi_autonomo | 0;1 | 0=no, 1=sì |
| 84 | riscaldamento_termopompa | 0;1 | 0=no, 1=sì |
| 85 | raffreddamento | 0;1 | 0=no, 1=sì |
| 86 | cucina_arredata | 0;1 | 0=no, 1=sì |
| 87 | portineria | 0;1 | 0=no, 1=sì |
| 88 | domotica | 0;1 | 0=no, 1=sì |
| 89 | tapparelle motorizzate | 0;1 | 0=no, 1=sì |
| 90 | porta blindata | 0;1 | 0=no, 1=sì |
| 91 | contacalorie | 0;1 | 0=no, 1=sì |
| 92 | montacarichi | 0;1;2;3;-1 | -1 = più di 3 |
| 93 | banchine di carico | 0;1;2;3;-1 | -1 = più di 3 |
| 94 | numero portoni | 0;1;2;3;-1 | -1 = più di 3 |
| 95 | numero accessi carrai | 0;1;2;3;-1 | -1 = più di 3 |
| 96 | cartello | vedi tabella specifica | sì/no/rimosso |
| 97 | saracinesche | 0;1;2;3;-1 | -1 = più di 3 |
| 98 | vasca | 0;1 | 0=no, 1=sì |
| 99 | zanzariere | 0;1 | 0=no, 1=sì |
| 100 | tende da sole | vedi tabella specifica | sì/no/predisposto |
| 101 | impianto elettrico | 0;1;2;3 | 0=non definito, 1=da fare, 2=a norma, 3=da verificare |
| 102 | allacciamento fognatura | 0;1 | 0=no, 1=sì |
| 103 | canna fumaria | 0;1 | 0=no, 1=sì |
| 104 | connettività | 0;1;2 | 0=nessuna, 1=adsl, 2=fibra |
| 105 | impianto illuminazione | 0;1 | 0=no, 1=sì |

### **📊 Special Domain Fields**

#### **Classe Energetica (ID: 55)**
| Valore | Significato |
|--------|-------------|
| 0 | In fase di definizione |
| 1 | A+/passivo (vecchie certificazioni) |
| 2 | A |
| 3 | B |
| 4 | C |
| 5 | D |
| 6 | E |
| 7 | F |
| 8 | G |
| 9 | Non soggetto a Certificazione |
| 10 | A4 (APE 2015) |
| 11 | A3 (APE 2015) |
| 12 | A2 (APE 2015) |
| 13 | A1 (APE 2015) |

#### **Posizione (ID: 56)**
| Valore | Significato |
|--------|-------------|
| 0 | sconosciuto |
| 1 | area industriale/artigianale |
| 2 | centro commerciale |
| 3 | ad angolo |
| 4 | centrale |
| 5 | servita |
| 6 | forte passaggio |
| 7 | fronte lago |
| 8 | fronte strada |
| 9 | interna |

#### **Stato Manutenzione (ID: 57)**
| Valore | Significato |
|--------|-------------|
| 0 | sconosciuto |
| 1 | da ristrutturare |
| 2 | ristrutturato |
| 3 | discreto |
| 4 | buono |
| 5 | ottimo |
| 6 | nuovo |
| 7 | impianti da fare |
| 8 | impianti da rifare |
| 9 | impianti a norma |

#### **Panorama (ID: 62)**
| Valore | Significato |
|--------|-------------|
| 0 | non indicato |
| 1 | vista mare |
| 2 | vista lago |
| 3 | vista monti |
| 4 | vista aperta |
| 5 | vista monumento |
| 6 | vista giardino |
| 7 | fronte mare |
| 8 | lato mare |

#### **Cartello (ID: 96)**
| Valore | Significato |
|--------|-------------|
| 0 | no |
| 1 | si |
| 2 | rimosso |
| 3 | da rimuovere |

#### **Tende da Sole (ID: 100)**
| Valore | Significato |
|--------|-------------|
| 0 | no |
| 1 | si |
| 2 | predisposto |

#### **Connettività (ID: 104)**
| Valore | Significato |
|--------|-------------|
| 0 | nessuna |
| 1 | adsl |
| 2 | fibra |

---

## 🔢 **DATI_INSERITI FIELDS** (Free Numeric Values)

### **📊 Numeric Data Fields**

| ID | Descrizione | Formato | Note |
|----|-------------|---------|------|
| 1 | fatturato | numeric | € annuale |
| 2 | fee di ingresso | numeric | € |
| 3 | volumetria | numeric | m³ |
| 4 | mq giardino | numeric | m² |
| 5 | mq aree esterne | numeric | m² |
| 6 | altezza piano | numeric | metri |
| 7 | kw cabina elettrica | numeric | kW |
| 8 | distanza dal mare | numeric | metri |
| 12 | catasto_destinazione | text | es: A1, C2 |
| 13 | catasto_rendita | numeric | € |
| 14 | catasto_foglio | numeric | numero |
| 15 | catasto_particella | numeric | numero |
| 16 | catasto_subalterno | numeric | numero |
| 17 | numero chiavi | numeric | quantità |
| 18 | mq ufficio | numeric | m² |
| 19 | superficie lotto | numeric | m² |
| 20 | superficie commerciale | numeric | m² |
| 21 | superficie utile | numeric | m² |
| 22 | dimensione accesso carraio | numeric | metri |
| 23 | lunghezza | numeric | metri |
| 24 | larghezza | numeric | metri |
| 25 | altezza | numeric | metri |
| 26 | potenza impianto elettrico | numeric | kW |
| 27 | deposito cauzionale | numeric | € |
| 28 | fideiussione | numeric | € |
| 29 | totale piani unità | numeric | numero |

---

## 🎯 **MAPPING STRATEGY FOR WPRESIDENCE**

### **🏠 Core Property Fields**
```php
// Base property info from <info>
'info/id' => 'property_id',
'info/abstract' => 'post_title', 
'info/description' => 'post_content',
'info/price' => 'property_price',
'info/mq' => 'property_size',
'info/categorie_id' => 'property_category',
'info/indirizzo' => 'property_address',
'info/latitude' => 'property_latitude',
'info/longitude' => 'property_longitude',
```

### **🔧 Features from info_inserite**
```php
// Key features mapping
'info_inserite[id=1]' => 'property_bathrooms',    // bagni
'info_inserite[id=2]' => 'property_bedrooms',     // camere
'info_inserite[id=13]' => 'feature_elevator',     // ascensore
'info_inserite[id=14]' => 'feature_ac',           // aria condizionata
'info_inserite[id=17]' => 'feature_garden',       // giardino
'info_inserite[id=66]' => 'feature_pool',         // piscina
'info_inserite[id=5]' => 'feature_garage',        // garage
'info_inserite[id=20]' => 'feature_parking',      // posto auto
```

### **📏 Numeric data from dati_inseriti**
```php
// Additional numeric fields
'dati_inseriti[id=4]' => 'property_garden_size',     // mq giardino
'dati_inseriti[id=20]' => 'property_commercial_size', // superficie commerciale
'dati_inseriti[id=21]' => 'property_useful_size',     // superficie utile
```

### **🖼️ Media from file_allegati**
```php
// Images and media
'file_allegati/allegato[type=planimetria]' => 'property_floorplan',
'file_allegati/allegato[type!=planimetria]' => 'property_gallery',
```

---

## 🎯 **TRENTINO-SPECIFIC PRIORITIES**

### **🔑 Essential Fields for Trentino Market**
1. **Bagni** (ID: 1) → WpResidence bathrooms
2. **Camere** (ID: 2) → WpResidence bedrooms  
3. **Ascensore** (ID: 13) → Important for apartments
4. **Giardino** (ID: 17) → Popular in Trentino
5. **Montagna** (ID: 36) → Key location feature
6. **Lago** (ID: 37) → Trentino lakes proximity
7. **Piano numero** (ID: 33) → Floor level
8. **Totale piani** (ID: 32) → Building floors
9. **Posto auto** (ID: 20) → Essential in cities
10. **Piscina** (ID: 66) → Luxury properties

### **🏔️ Trentino Location Features**
- **ID 36**: montagna = 1 (mountain properties)
- **ID 37**: lago = 1 (lake properties)  
- **ID 38**: terme = 1 (thermal spa proximity)
- **ID 39**: collina = 1 (hillside properties)

---

**📅 Fonte**: Documentazione GestionaleImmobiliare.it  
**🔐 Auth**: trentinoimmobiliare_it / dget6g52  
**📅 Aggiornato**: 03/08/2025
