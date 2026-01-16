# 🔧 DÉPANNAGE - Tests VS Code

## ❌ Problème : Les tests n'apparaissent pas dans VS Code

### ✅ SOLUTION RAPIDE (à faire dans l'ordre)

#### 1️⃣ Vérifier que les tests fonctionnent
```bash
npm test
```
**Résultat attendu** : `Tests: 21 passed, 21 total` ✓

Si ça ne fonctionne pas, c'est un problème Jest, pas VS Code.

---

#### 2️⃣ Nettoyer et Réinitialiser
```bash
.\reset-jest-vscode.ps1
```

Ou manuellement :
```bash
# Nettoyer le cache
npm run test -- --clearCache

# Supprimer les artefacts
Remove-Item -Recurse -Force .jest-cache, coverage, tests/coverage -ErrorAction SilentlyContinue

# Relancer les tests
npm test
```

---

#### 3️⃣ Dans VS Code - Recharger la Fenêtre

**IMPORTANT** : C'est souvent la solution !

1. `Ctrl+Shift+P`
2. Taper : `reload`
3. Sélectionner : **"Developer: Reload Window"**
4. Attendre 5-10 secondes

---

#### 4️⃣ Redémarrer Jest dans VS Code

1. `Ctrl+Shift+P`
2. Taper : `jest`
3. Essayer dans cet ordre :
   - **"Jest: Start All Runners"**
   - **"Jest: Restart Jest Runner"**
   - **"Jest: Stop All Runners"** puis **"Jest: Start All Runners"**

---

#### 5️⃣ Vérifier l'Extension Jest

1. `Ctrl+Shift+X` (Extensions)
2. Rechercher : **"Jest"**
3. Vérifier que **"Jest"** par **Orta** est installée
4. Si pas installée : Installer et **recharger VS Code**

---

#### 6️⃣ Vérifier la Barre de Statut

En bas de VS Code, cherchez :
```
Jest ✓
```

Si vous voyez `Jest ⚠` ou `Jest ✗` :
- Cliquez dessus
- Regardez les erreurs
- Suivez les instructions

---

#### 7️⃣ Ouvrir le Canal de Sortie Jest

1. `Ctrl+Shift+U` (Output)
2. Dans le dropdown, sélectionner : **"Jest"**
3. Lire les erreurs éventuelles

---

#### 8️⃣ Forcer le Redémarrage Complet

```bash
# Fermer VS Code complètement

# Nettoyer tout
npm run test -- --clearCache
Remove-Item -Recurse -Force .jest-cache

# Rouvrir VS Code
code .

# Attendre que Jest s'initialise (barre de statut)
```

---

## 🔍 DIAGNOSTIC

### Vérifier la Configuration

1. **Fichier `.vscode/settings.json` existe ?**
   ```
   C:\...\custom_apps\crm\.vscode\settings.json
   ```

2. **Contient les bonnes valeurs ?**
   ```json
   {
     "jest.autoRun": "off",
     "jest.runMode": "on-demand",
     "jest.rootPath": "."
   }
   ```

3. **Fichier `jest.config.js` correct ?**
   ```javascript
   rootDir: '.',
   testMatch: [
     '**/tests/Frontend/**/*.test.{js,ts}'
   ]
   ```

---

## 🎯 SOLUTION ALTERNATIVE : Mode Terminal

Si vraiment rien ne fonctionne dans l'interface graphique, utilisez le mode terminal :

### Mode Watch Automatique
```bash
npm run test:watch
```
Les tests se relancent automatiquement quand vous modifiez le code.

### Dans l'Éditeur
Même sans l'interface Testing, vous pouvez :
1. Ouvrir un fichier de test
2. `F5` pour debug
3. Configuration "Debug Jest Test" dans le menu debug

---

## ❓ QUESTIONS FRÉQUENTES

### Q: L'icône Testing n'existe pas ?
**R:** L'icône Testing est native à VS Code. Si elle n'existe pas :
- Vérifier version VS Code (doit être récente)
- `Ctrl+Shift+P` > "Testing: Focus on Test Explorer View"

### Q: Jest dit "No tests found" ?
**R:** 
```bash
# Vérifier le pattern
npm test -- tests/Frontend/App.test.ts

# Si ça marche, c'est un problème de config VS Code
# Redémarrer Jest : Ctrl+Shift+P > "Jest: Restart"
```

### Q: Les tests s'exécutent mais n'apparaissent pas ?
**R:** Extension Jest pas activée. Dans Command Palette :
```
Jest: Toggle Coverage
Jest: Start All Runners
```

### Q: Erreur "Cannot find module" ?
**R:**
```bash
npm install
npm test
# Puis recharger VS Code
```

---

## 🆘 DERNIER RECOURS

Si vraiment rien ne fonctionne :

### 1. Désinstaller/Réinstaller l'Extension
```bash
code --uninstall-extension orta.vscode-jest
code --install-extension orta.vscode-jest
```

### 2. Reset Complet
```bash
# Supprimer la config VS Code
Remove-Item -Recurse -Force .vscode

# Recréer
.\open-vscode-with-tests.ps1 -ReloadExtensions
```

### 3. Vérifier les Logs
```bash
# Dans VS Code
Ctrl+Shift+P > "Developer: Show Logs"
# Chercher les erreurs liées à Jest
```

---

## ✅ CHECKLIST DE VÉRIFICATION

- [ ] `npm test` fonctionne dans le terminal
- [ ] Extension Jest installée (orta.vscode-jest)
- [ ] VS Code rechargé (`Ctrl+Shift+P` > Reload Window)
- [ ] Barre de statut montre "Jest"
- [ ] `.vscode/settings.json` existe et contient config Jest
- [ ] `jest.config.js` a `rootDir: '.'`
- [ ] Cache Jest nettoyé (`--clearCache`)

---

## 📞 SUPPORT

Si tout échoue, les tests fonctionnent quand même :
```bash
# Terminal classique
npm test

# Mode watch
npm run test:watch

# Avec couverture
npm run test:coverage
```

**Les tests sont fonctionnels, c'est juste l'interface VS Code qui pose problème !**