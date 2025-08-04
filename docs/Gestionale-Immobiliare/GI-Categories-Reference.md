# GestionaleImmobiliare.it - Property Categories & Types

## 🏠 **CATEGORIE PRINCIPALI** (`categorie_id`)

| ID | Descrizione |
|----|-------------|
| 1 | casa singola |
| 2 | bifamiliare |
| 3 | trifamiliare |
| 4 | casa a schiera |
| 5 | monolocale |
| 7 | cantina |
| 8 | garage |
| 9 | magazzino |
| 10 | attivita commerciale |
| 11 | appartamento |
| 12 | attico |
| 13 | rustico |
| 14 | negozio |
| 15 | quadrifamiliare |
| 16 | capannone |
| 17 | ufficio |
| 18 | villa |
| 19 | terreno |
| 20 | laboratorio |
| 21 | posto auto |
| 22 | bed and breakfast |
| 23 | loft |
| 24 | multiproprietà |
| 25 | agriturismo |
| 26 | palazzo |
| 27 | hotel - albergo |
| 28 | stanze |

## 🏪 **MICRO CATEGORIE** (`categorie_micro_id`)

### **Attività Commerciali (categorie_id = 10)**
| Micro ID | Descrizione |
|----------|-------------|
| 1 | alimentari |
| 2 | attività varie |
| 3 | autorimesse |
| 4 | bar |
| 5 | centro commerciale |
| 6 | edicole |
| 7 | farmacie |
| 8 | ferramenta/casalinghi |
| 9 | sale gioco/scommesse |
| 10 | gelaterie |
| 11 | palestre |
| 12 | panifici |
| 13 | pasticcerie |
| 14 | parrucchiere uomo/donna |
| 15 | pubs e locali serali |
| 16 | ristoranti |
| 17 | pizzerie |
| 18 | solarium e centri estetica |
| 19 | tabaccherie |
| 25 | telefonia/informatica |
| 26 | tintorie/lavanderie |
| 27 | video noleggi |
| 28 | showroom |
| 29 | abbigliamento |
| 30 | cartoleria/libreria |
| 31 | attività in franchising |
| 32 | fruttivendolo |
| 33 | macelleria |
| 34 | gastronomia |
| 35 | enoteca |
| 36 | negozio di giocattoli |
| 37 | articoli sanitari |
| 38 | calzature |
| 39 | prodotti per animali |
| 40 | tessuti e tende/merceria |
| 41 | borse e pelletterie |
| 42 | fioreria |
| 43 | oreficeria |
| 92 | azienda agricola |
| 96 | friggitorie |
| 97 | rosticcerie |

### **Terreni (categorie_id = 19)**
| Micro ID | Descrizione |
|----------|-------------|
| 20 | terreno agricolo/coltura |
| 21 | terreno boschivo |
| 22 | terreno edificabile commerciale |
| 23 | terreno edificabile industriale |
| 24 | terreno edificabile residenziale |
| 82 | lottizzazione |
| 83 | completamento |
| 84 | perequazione urbana |
| 85 | insediativa |
| 86 | peri urbana |
| 87 | artigianale |
| 88 | di tutela |
| 89 | di rispetto |
| 90 | di interesse paesaggistico |
| 98 | vigneto |
| 99 | seminativo |

### **Appartamenti (categorie_id = 11)**
| Micro ID | Descrizione |
|----------|-------------|
| 44 | monolocale |
| 45 | bilocale |
| 46 | trilocale |
| 47 | quadrilocale |
| 48 | pentalocale |
| 49 | più di 5 locali |
| 50 | duplex |
| 51 | mansarda |

### **Garage (categorie_id = 8)**
| Micro ID | Descrizione |
|----------|-------------|
| 58 | singolo |
| 59 | doppio |
| 60 | triplo |

### **Posto Auto (categorie_id = 21)**
| Micro ID | Descrizione |
|----------|-------------|
| 61 | singolo |
| 62 | doppio |
| 63 | triplo |
| 64 | silos |

### **Rustici (categorie_id = 13)**
| Micro ID | Descrizione |
|----------|-------------|
| 65 | rustico di campagna |
| 66 | baita |
| 67 | chalet |
| 68 | trullo |
| 69 | rudere |
| 70 | masseria |
| 71 | cascina |
| 72 | casale |
| 73 | castello |
| 80 | maso |
| 81 | tabià |
| 91 | stalla |
| 93 | casa colonica |

### **Ville (categorie_id = 18)**
| Micro ID | Descrizione |
|----------|-------------|
| 77 | moderna |
| 78 | contemporanea |
| 79 | d'epoca |
| 95 | ville venete |

### **Case (varie)**
| Micro ID | Categoria | Descrizione |
|----------|-----------|-------------|
| 52 | 3 | porzione di testa (trifamiliare) |
| 53 | 3 | porzione centrale (trifamiliare) |
| 54 | 4 | porzione di testa (schiera) |
| 55 | 4 | porzione centrale (schiera) |
| 56 | 15 | porzione di testa (quadrifamiliare) |
| 57 | 15 | porzione centrale (quadrifamiliare) |
| 94 | 1 | terratetto |

### **Stanze (categorie_id = 28)**
| Micro ID | Descrizione |
|----------|-------------|
| 74 | studenti |
| 75 | lavoratori |
| 76 | entrambi |

---

## 🎯 **MAPPING CONSIGLIATO PER TRENTINO**

### **🏠 Focus Residenziale Trentino**
- **11** (appartamento) → WpResidence "apartment"
- **1** (casa singola) → WpResidence "house" 
- **18** (villa) → WpResidence "villa"
- **12** (attico) → WpResidence "penthouse"
- **19** (terreno) → WpResidence "land"

### **🏪 Commerciale**
- **14** (negozio) → WpResidence "commercial"
- **17** (ufficio) → WpResidence "office"
- **8** (garage) → WpResidence "garage"

---

**📅 Fonte**: Documentazione GestionaleImmobiliare.it  
**📅 Aggiornato**: 03/08/2025
