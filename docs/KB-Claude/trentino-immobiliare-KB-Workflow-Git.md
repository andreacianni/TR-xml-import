# TRENTINO IMMOBILIARE - WORKFLOW GIT STAGING DEFINITO

## 🔄 **WORKFLOW SVILUPPO BRANCH - CONSOLIDATO**

### **📋 SETUP INIZIALE (UNA VOLTA SOLA)**

```bash
# 1. Configura remote staging
git remote add staging ssh://u996-hh9emyr0bbn6@ams11.siteground.eu:18765/~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin

# 2. Configura server per accettare push (SSH)
ssh u996-hh9emyr0bbn6@ams11.siteground.eu -p 18765
cd ~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin
git config receive.denyCurrentBranch ignore
exit
```

---

## 🚀 **WORKFLOW SVILUPPO STANDARD**

### **📋 CICLO SVILUPPO COMPLETO**

```bash
# 1. SVILUPPO SU BRANCH DEVELOP
git checkout develop  # (o git checkout -b develop se non esiste)

# 2. MODIFICHE + COMMIT
[fai modifiche ai file]
git add [file_modificati]
git commit -m "[tipo]: [descrizione modifica]"

# 3. PUSH SU STAGING PER TEST
git push staging develop:main --force

# 4. ATTIVA MODIFICHE SU SERVER STAGING
ssh u996-hh9emyr0bbn6@ams11.siteground.eu -p 18765
cd ~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin
git reset --hard HEAD
exit

# 5. TEST SU SPAZIODEMO.XYZ
# → Verifica funzionalità

# 6. QUANDO OK → MERGE SU MAIN (locale)
git checkout main
git merge develop

# 7. PUSH GITHUB (solo quando pronto per release)
git push origin main
git tag v1.0.X && git push origin v1.0.X  # per GitHub releases
```

---

## 🎯 **WORKFLOW SEMPLIFICATO QUOTIDIANO**

### **📋 PER MODIFICHE ITERATIVE**

```bash
# Sviluppo
git checkout develop
[modifiche]
git add . && git commit -m "fix: [descrizione]"

# Test staging
git push staging develop:main --force
ssh u996-hh9emyr0bbn6@ams11.siteground.eu -p 18765 "cd ~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin && git reset --hard HEAD"

# Test su spaziodemo.xyz
```

---

## 🔧 **REMOTES CONFIGURATI**

```bash
# Verifica remotes
git remote -v

# Output atteso:
origin  https://github.com/andreacianni/TR-xml-import.git (fetch)
origin  https://github.com/andreacianni/TR-xml-import.git (push)
staging ssh://u996-hh9emyr0bbn6@ams11.siteground.eu:18765/~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin (fetch)
staging ssh://u996-hh9emyr0bbn6@ams11.siteground.eu:18765/~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin (push)
```

---

## 🌐 **ENVIRONMENT CHAIN**

```
Local Development (develop branch)
           ↓ git push staging
Staging Test (spaziodemo.xyz)
           ↓ validation
Local Main Branch (git merge develop)
           ↓ git push origin
GitHub Repository (releases)
           ↓ auto-updater
Production (trentinoimmobiliare.it)
```

---

## ✅ **VANTAGGI WORKFLOW**

- **🔧 Development Branch**: Modifiche isolate su develop
- **🧪 Staging Test**: Test immediato su server staging
- **🎯 Quality Control**: Solo codice testato va su main
- **📦 Release Control**: GitHub releases controllate
- **🚀 Auto-Update**: Deploy produzione automatico
- **🔄 Iteration**: Cycle rapido development → test → merge

---

## 🚨 **TROUBLESHOOTING COMUNE**

### **❌ Errore: "branch is currently checked out"**
```bash
# Soluzione - configura server:
ssh u996-hh9emyr0bbn6@ams11.siteground.eu -p 18765
cd ~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin
git config receive.denyCurrentBranch ignore
```

### **❌ Modifiche non visibili su staging**
```bash
# Soluzione - reset server:
ssh u996-hh9emyr0bbn6@ams11.siteground.eu -p 18765
cd ~/www/spaziodemo.xyz/public_html/wp-content/plugins/trentino-import-plugin
git reset --hard HEAD
```

---

## 📊 **STATUS WORKFLOW**

**✅ SETUP COMPLETATO** - 02/08/2025
- Remote staging configurato
- Server Git config completato
- Primo push successful: develop → staging
- Workflow testato e funzionante

**🎯 READY FOR ITERATIVE DEVELOPMENT**